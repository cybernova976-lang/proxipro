<?php

namespace App\Services;

use App\Models\ServiceOrder;
use App\Models\User;
use RuntimeException;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Transfer;

class StripeConnectService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createOrGetAccount(User $user): User
    {
        if ($user->stripe_connect_account_id) {
            return $user;
        }

        $account = Account::create([
            'type' => 'express',
            'country' => 'FR',
            'email' => $user->email,
            'business_type' => $user->company_name ? 'company' : 'individual',
            'capabilities' => [
                'transfers' => ['requested' => true],
            ],
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        $user->forceFill([
            'stripe_connect_account_id' => $account->id,
            'stripe_connect_payouts_enabled' => (bool) ($account->payouts_enabled ?? false),
            'stripe_connect_charges_enabled' => (bool) ($account->charges_enabled ?? false),
        ])->save();

        return $user->fresh();
    }

    public function createOnboardingLink(User $user, string $returnUrl, string $refreshUrl): string
    {
        $user = $this->createOrGetAccount($user);

        $link = AccountLink::create([
            'account' => $user->stripe_connect_account_id,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]);

        return $link->url;
    }

    public function syncAccountStatus(User $user): User
    {
        if (!$user->stripe_connect_account_id) {
            return $user;
        }

        $account = Account::retrieve($user->stripe_connect_account_id);

        $user->forceFill([
            'stripe_connect_payouts_enabled' => (bool) ($account->payouts_enabled ?? false),
            'stripe_connect_charges_enabled' => (bool) ($account->charges_enabled ?? false),
            'stripe_connect_onboarding_completed_at' => !empty($account->details_submitted) ? now() : $user->stripe_connect_onboarding_completed_at,
        ])->save();

        return $user->fresh();
    }

    /**
     * Le prestataire peut-il recevoir un versement ? L'état enregistré peut
     * dater : s'il n'est pas encore actif, on le relit chez Stripe avant de
     * répondre (la vérification du compte se termine parfois après coup).
     */
    public function sellerCanBePaid(?User $seller): bool
    {
        if (! $seller?->stripe_connect_account_id) {
            return false;
        }
        if ($seller->stripe_connect_payouts_enabled) {
            return true;
        }

        try {
            return (bool) $this->syncAccountStatus($seller)->stripe_connect_payouts_enabled;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * Verse au prestataire sa part de la commande (prix moins la commission).
     *
     * Le versement est rattaché au paiement du client (« source_transaction ») :
     * il aboutit même si l'argent n'est pas encore disponible sur le solde de
     * la plateforme, et Stripe refuse tout versement dont le total dépasserait
     * le paiement d'origine, ce qui empêche de verser deux fois.
     */
    public function transferToSeller(ServiceOrder $serviceOrder): string
    {
        $seller = $serviceOrder->seller;
        if (! $seller?->stripe_connect_account_id) {
            throw new RuntimeException('Le prestataire doit d’abord activer la réception de ses paiements.');
        }

        if (! $this->sellerCanBePaid($seller)) {
            throw new RuntimeException('Le compte de paiement du prestataire n’est pas encore activé pour les virements.');
        }

        if ($serviceOrder->stripe_transfer_id) {
            throw new RuntimeException('Cette commande a déjà été versée au prestataire.');
        }

        $transfer = Transfer::create([
            'amount' => (int) round(((float) $serviceOrder->seller_amount) * 100),
            'currency' => strtolower((string) config('marketplace.currency', 'eur')),
            'destination' => $seller->stripe_connect_account_id,
            'source_transaction' => $this->chargeIdFor($serviceOrder),
            'transfer_group' => $serviceOrder->order_number,
            'metadata' => [
                'service_order_id' => $serviceOrder->id,
                'order_number' => $serviceOrder->order_number,
                'seller_id' => $seller->id,
            ],
        ]);

        return $transfer->id;
    }

    /** Identifiant du paiement par carte du client, auquel le versement est rattaché. */
    private function chargeIdFor(ServiceOrder $serviceOrder): string
    {
        if (! $serviceOrder->stripe_payment_intent_id) {
            throw new RuntimeException('Aucun paiement confirmé n’est rattaché à cette commande.');
        }

        $paymentIntent = PaymentIntent::retrieve($serviceOrder->stripe_payment_intent_id);
        $charge = $paymentIntent->latest_charge ?? null;
        $chargeId = is_string($charge) ? $charge : ($charge->id ?? null);

        if (! $chargeId) {
            throw new RuntimeException('Le paiement de cette commande est introuvable chez Stripe.');
        }

        return $chargeId;
    }

    /**
     * Ferme la page de paiement d'une commande que l'on annule, pour qu'aucun
     * paiement ne puisse arriver après coup. Refuse si le client vient de payer.
     */
    public function expireCheckoutSession(ServiceOrder $serviceOrder): void
    {
        if (! $serviceOrder->stripe_checkout_session_id) {
            return;
        }

        $session = \Stripe\Checkout\Session::retrieve($serviceOrder->stripe_checkout_session_id);

        if (($session->status ?? null) === 'complete') {
            throw new RuntimeException('Un paiement vient d’être enregistré pour cette commande : elle ne peut plus être annulée.');
        }
        if (($session->status ?? null) === 'open') {
            $session->expire();
        }
    }

    public function refundOrder(ServiceOrder $serviceOrder): string
    {
        if (!$serviceOrder->stripe_payment_intent_id) {
            throw new RuntimeException('Aucun paiement Stripe confirmé n\'est disponible pour ce remboursement.');
        }

        $refund = Refund::create([
            'payment_intent' => $serviceOrder->stripe_payment_intent_id,
            'metadata' => [
                'service_order_id' => $serviceOrder->id,
                'order_number' => $serviceOrder->order_number,
                'buyer_id' => $serviceOrder->buyer_id,
            ],
        ]);

        return $refund->id;
    }
}
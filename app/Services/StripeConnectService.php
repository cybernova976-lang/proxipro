<?php

namespace App\Services;

use App\Models\ServiceOrder;
use App\Models\User;
use RuntimeException;
use Stripe\Account;
use Stripe\AccountLink;
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

    public function transferToSeller(ServiceOrder $serviceOrder): string
    {
        $seller = $serviceOrder->seller;
        if (!$seller?->stripe_connect_account_id) {
            throw new RuntimeException('Le vendeur doit finaliser Stripe Connect avant la libération des fonds.');
        }

        if (!$seller->stripe_connect_payouts_enabled) {
            throw new RuntimeException('Le compte Stripe Connect du vendeur n\'est pas encore activé pour les virements.');
        }

        $transfer = Transfer::create([
            'amount' => (int) round(((float) $serviceOrder->seller_amount) * 100),
            'currency' => 'eur',
            'destination' => $seller->stripe_connect_account_id,
            'transfer_group' => $serviceOrder->order_number,
            'metadata' => [
                'service_order_id' => $serviceOrder->id,
                'order_number' => $serviceOrder->order_number,
                'seller_id' => $seller->id,
            ],
        ]);

        return $transfer->id;
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
<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Services\ProviderSubscriptionService;
use App\Services\ReferralService;
use App\Services\ServiceOrderWorkflowService;
use App\Support\PointPackCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class StripeCheckoutController extends Controller
{
    public function __construct(
        protected ReferralService $referralService,
        protected ServiceOrderWorkflowService $serviceOrderWorkflowService,
        protected ProviderSubscriptionService $providerSubscriptionService,
    ) {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Créer une session Stripe Checkout
     */
    public function createCheckout(Request $request)
    {
        $request->validate([
            'product_key' => 'required|string',
        ]);

        $user = Auth::user();
        $productKey = $request->product_key;

        $product = PointPackCatalog::find($productKey);
        if (! $product) {
            return response()->json(['error' => 'Produit invalide'], 400);
        }

        try {
            // Créer ou récupérer le customer Stripe
            $stripeCustomerId = $user->stripe_id;

            if (! $stripeCustomerId) {
                $customer = \Stripe\Customer::create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => ['user_id' => $user->id],
                ]);
                $stripeCustomerId = $customer->id;
                $user->update(['stripe_id' => $stripeCustomerId]);
            }

            // Créer la session Checkout
            $session = StripeSession::create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur', // Euro
                        'product_data' => [
                            'name' => $product['name'],
                            'description' => $product['type'] === 'subscription'
                                ? $product['points'].' points/mois inclus'
                                : 'Pack de '.$product['points'].' points',
                        ],
                        'unit_amount' => $product['price_cents'],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('stripe.success').'?session_id={CHECKOUT_SESSION_ID}&product='.$productKey,
                'cancel_url' => route('pricing.index').'?canceled=true',
                'metadata' => [
                    'user_id' => $user->id,
                    'product_key' => $productKey,
                    'type' => $product['type'],
                    'points' => $product['points'],
                ],
            ]);

            return response()->json([
                'url' => $session->url,
                'session_id' => $session->id,
            ]);

        } catch (\Exception $e) {
            \Log::error('Stripe Checkout error: '.$e->getMessage());

            return response()->json(['error' => 'Erreur lors de la création du paiement'], 500);
        }
    }

    /**
     * Page de succès après paiement
     */
    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');

        if (! $sessionId) {
            return redirect()->route('pricing.index')->with('error', 'Session de paiement invalide');
        }

        try {
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return redirect()->route('pricing.index')->with('error', 'Paiement non confirmé');
            }

            if (($session->metadata->type ?? null) === 'service_order') {
                $serviceOrder = $this->serviceOrderWorkflowService->markPaidFromCheckoutSession($session);

                if (! $serviceOrder) {
                    return redirect()->route('service-orders.index')->with('error', 'Commande introuvable pour ce paiement.');
                }

                return redirect()->route('service-orders.index')
                    ->with('success', 'Paiement Stripe confirme. Les fonds sont bloques jusqu\'a liberation ou litige.');
            }

            // Le produit et le beneficiaire proviennent exclusivement des
            // metadonnees Stripe signees, jamais des parametres de retour.
            $productKey = $session->metadata->product_key ?? null;

            if (! $productKey) {
                return redirect()->route('pricing.index')->with('error', 'Produit invalide');
            }

            $userId = $session->metadata->user_id;
            $product = PointPackCatalog::find($productKey);

            if (! $product) {
                return redirect()->route('pricing.index')->with('error', 'Produit invalide');
            }

            if ((int) $userId !== (int) Auth::id()) {
                return redirect()->route('pricing.index')->with('error', 'Cette session de paiement ne correspond pas a votre compte.');
            }

            $user = User::find($userId);
            if (! $user) {
                return redirect()->route('pricing.index')->with('error', 'Utilisateur non trouvé');
            }

            // Vérifier si cette session a déjà été traitée
            $existingTransaction = Transaction::where('stripe_session_id', $sessionId)->first();
            if ($existingTransaction) {
                return redirect()->route('pricing.index')
                    ->with('success', 'Paiement déjà traité ! Vos points ont été crédités.');
            }

            // Traiter le paiement
            $this->processPayment($user, $product, $productKey, $session);

            $message = '🎉 '.$product['points'].' points ajoutés à votre compte !';

            return redirect()->route('pricing.index')->with('success', $message);

        } catch (\Exception $e) {
            \Log::error('Stripe success callback error: '.$e->getMessage());

            return redirect()->route('pricing.index')->with('error', 'Erreur lors de la confirmation du paiement');
        }
    }

    /**
     * Traiter le paiement et créditer les points/abonnement
     */
    private function processPayment($user, $product, $productKey, $session)
    {
        // Enregistrer la transaction
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => $product['price'],
            'type' => 'POINTS',
            'description' => 'Achat de '.$product['points'].' points',
            'status' => 'completed',
            'stripe_session_id' => $session->id,
        ]);

        // Créditer les points (via available_points et total_points)
        $user->addPoints($product['points'], 'purchase', 'Achat de '.$product['points'].' points', 'stripe');
        $this->referralService->grantFirstPurchaseRewards($user->fresh(), $transaction);
    }

    /**
     * Webhook Stripe
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            if (! $endpointSecret || ! $sigHeader) {
                \Log::warning('Stripe webhook refused because signature verification is not configured.');

                return response()->json(['error' => 'Signature du webhook indisponible'], 400);
            }

            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            \Log::warning('Stripe webhook signature verification failed.', [
                'exception' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Signature du webhook invalide'], 400);
        }

        try {
            if (isset($event->type) && $event->type === 'checkout.session.completed') {
                $session = $event->data->object;
                $metadata = $session->metadata;
                $type = $metadata->type ?? null;

                if ($type === 'service_order') {
                    $this->serviceOrderWorkflowService->markPaidFromCheckoutSession($session);

                    return response()->json(['received' => true]);
                }

                if (in_array($type, ProviderSubscriptionService::CHECKOUT_TYPES, true)) {
                    $this->providerSubscriptionService->completeCheckoutSession($session);

                    return response()->json(['received' => true]);
                }

                $user = User::find($metadata->user_id ?? null);
                $productKey = $metadata->product_key ?? null;

                if ($user && $productKey && ($product = PointPackCatalog::find($productKey))) {

                    // Vérifier si pas déjà traité
                    $existingTransaction = Transaction::where('stripe_session_id', $session->id)->first();
                    if (! $existingTransaction) {
                        $this->processPayment($user, $product, $productKey, $session);
                    }
                }
            }

            if (in_array($event->type ?? null, [
                'customer.subscription.created',
                'customer.subscription.updated',
                'customer.subscription.deleted',
                'customer.subscription.paused',
                'customer.subscription.resumed',
            ], true)) {
                if ($this->providerSubscriptionService->managesStripeSubscription($event->data->object)) {
                    $this->providerSubscriptionService->syncStripeSubscription($event->data->object);
                }
            }

            if (($event->type ?? null) === 'invoice.paid') {
                $this->providerSubscriptionService->syncInvoicePaid($event->data->object);
            }

            if (($event->type ?? null) === 'invoice.payment_failed') {
                $this->providerSubscriptionService->markInvoiceFailed($event->data->object);
            }
        } catch (\Throwable $exception) {
            \Log::error('Stripe webhook processing failed.', [
                'event_id' => $event->id ?? null,
                'event_type' => $event->type ?? null,
                'exception' => $exception->getMessage(),
            ]);

            // Stripe réessaiera automatiquement les événements non acquittés.
            return response()->json(['error' => 'Échec du traitement du webhook'], 500);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Récupérer les transactions d'un utilisateur
     */
    public function transactions()
    {
        $user = Auth::user();
        $transactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return response()->json($transactions);
    }
}

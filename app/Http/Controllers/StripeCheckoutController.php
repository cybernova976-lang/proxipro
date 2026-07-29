<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\PointPurchasePaymentService;
use App\Services\ServiceOrderWorkflowService;
use App\Services\StripeWebhookService;
use App\Support\PointPackCatalog;
use App\Support\StripeSessionData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class StripeCheckoutController extends Controller
{
    public function __construct(
        protected PointPurchasePaymentService $pointPurchases,
        protected ServiceOrderWorkflowService $serviceOrderWorkflowService,
        protected StripeWebhookService $stripeWebhooks,
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
                    'expected_amount_cents' => $product['price_cents'],
                    'expected_currency' => 'eur',
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

            if (! StripeSessionData::isPaid($session)) {
                return redirect()->route('pricing.index')->with('error', 'Paiement non confirmé');
            }

            if (($session->metadata->type ?? null) === 'service_order') {
                if ((int) ($session->metadata->buyer_id ?? 0) !== (int) Auth::id()) {
                    return redirect()->route('service-orders.index')->with('error', 'Cette session de paiement ne correspond pas à votre compte.');
                }

                $serviceOrder = $this->serviceOrderWorkflowService->markPaidFromCheckoutSession($session);

                if (! $serviceOrder) {
                    return redirect()->route('service-orders.index')->with('error', 'Commande introuvable pour ce paiement.');
                }

                return redirect()->route('service-orders.index')
                    ->with('success', 'Paiement Stripe confirme. Les fonds sont bloques jusqu\'a liberation ou litige.');
            }

            $result = $this->pointPurchases->fulfill($session, Auth::id());
            $message = $result['status'] === 'processed'
                ? '🎉 '.$result['product']['points'].' points ajoutés à votre compte !'
                : 'Ce paiement avait déjà été traité. Vos points sont disponibles.';

            return redirect()->route('pricing.index')->with('success', $message);

        } catch (\Exception $e) {
            \Log::error('Stripe success callback error: '.$e->getMessage());

            return redirect()->route('pricing.index')->with('error', 'Erreur lors de la confirmation du paiement');
        }
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
            $result = $this->stripeWebhooks->process($event);
        } catch (\Throwable $exception) {
            \Log::error('Stripe webhook processing failed.', [
                'event_id' => $event->id ?? null,
                'event_type' => $event->type ?? null,
                'exception' => $exception->getMessage(),
            ]);

            // Stripe réessaiera automatiquement les événements non acquittés.
            return response()->json(['error' => 'Échec du traitement du webhook'], 500);
        }

        return response()->json(['received' => true, 'result' => $result]);
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

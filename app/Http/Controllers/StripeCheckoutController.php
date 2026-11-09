<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\PointPurchasePaymentService;
use App\Services\ServiceOrderWorkflowService;
use App\Services\StripeWebhookService;
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
        abort(410, \App\Support\RetiredPoints::MESSAGE);
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
            $message = 'Votre ancien paiement a été enregistré. Le système de points est arrêté ; contactez le support pour toute question sur cet achat.';

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

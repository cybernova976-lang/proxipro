<?php

namespace App\Http\Controllers;

use App\Models\ProSubscription;
use App\Models\Transaction;
use App\Services\ProviderSubscriptionService;
use App\Support\PlatformFeatures;
use App\Support\ProviderSubscriptionPlans;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Page « Facturation » : les abonnements du membre, leur moyen de paiement,
 * leur résiliation, et les formules disponibles quand il n'en a aucune.
 */
class BillingController extends Controller
{
    public function __construct(private readonly ProviderSubscriptionService $subscriptions) {}

    public function index()
    {
        $user = Auth::user();
        $all = $user->proSubscriptions()->latest('id')->get();

        // En cours : ce qui ouvre des avantages ou peut encore être prélevé.
        $current = $all->filter(fn (ProSubscription $subscription) => $subscription->isActive() || $subscription->hasOpenBilling())->values();
        $past = $all->reject(fn (ProSubscription $subscription) => $subscription->isActive() || $subscription->hasOpenBilling())->values();
        $late = $current->first(fn (ProSubscription $subscription) => $subscription->inPaymentGrace());
        $proSubscriptionsEnabled = PlatformFeatures::proSubscriptionsEnabled();

        return view('billing.index', [
            'user' => $user,
            'current' => $current,
            'past' => $past,
            'late' => $late,
            'paymentMethods' => $current->mapWithKeys(fn (ProSubscription $subscription) => [
                $subscription->id => $this->subscriptions->paymentMethodSummary($subscription),
            ]),
            'sepaAvailable' => $current->contains(fn (ProSubscription $subscription) => $subscription->hasOpenBilling())
                && $this->subscriptions->sepaDebitAvailable(),
            'plans' => $current->isEmpty() && $proSubscriptionsEnabled ? ProviderSubscriptionPlans::active() : [],
            'proSubscriptionsEnabled' => $proSubscriptionsEnabled,
            'payments' => Transaction::where('user_id', $user->id)->where('type', 'SUBSCRIPTION')
                ->where('status', 'completed')->latest()->take(12)->get(),
        ]);
    }

    /** Télécharge la facture d'un paiement d'abonnement : le PDF émis par Stripe. */
    public function invoice(Transaction $transaction)
    {
        abort_unless($transaction->user_id === Auth::id() && $transaction->type === 'SUBSCRIPTION', 404);

        try {
            $url = $this->subscriptions->invoicePdfUrl(Auth::user(), (string) $transaction->stripe_session_id);
        } catch (\Throwable $exception) {
            report($exception);
            $url = null;
        }

        return $url !== null
            ? redirect()->away($url)
            : redirect()->route('billing.index')->with('error', 'Cette facture n’a pas pu être récupérée pour le moment. Réessayez dans quelques minutes ou contactez l’assistance.');
    }

    /** Ouvre la page Stripe où le membre saisit sa nouvelle carte ou son compte à prélever. */
    public function changePaymentMethod()
    {
        $subscription = Auth::user()->proSubscriptions()->billingOpen()->latest('id')->first();
        if (! $subscription) {
            return redirect()->route('billing.index')->with('error', 'Aucun abonnement prélevé : il n’y a pas de moyen de paiement à modifier.');
        }

        try {
            $session = $this->subscriptions->createPaymentMethodSession(
                $subscription,
                route('billing.payment-method.return').'?session_id={CHECKOUT_SESSION_ID}',
                route('billing.index')
            );

            return redirect()->away($session->url);
        } catch (DomainException $exception) {
            return redirect()->route('billing.index')->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('billing.index')->with('error', 'La page de paiement sécurisée est momentanément indisponible. Réessayez dans quelques minutes.');
        }
    }

    public function paymentMethodReturn(Request $request)
    {
        $sessionId = (string) $request->query('session_id', '');
        if ($sessionId === '') {
            return redirect()->route('billing.index');
        }

        try {
            $result = $this->subscriptions->completePaymentMethodCheckout($sessionId, Auth::user());
        } catch (DomainException $exception) {
            return redirect()->route('billing.index')->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('billing.index')->with('error', 'Votre moyen de paiement n’a pas pu être confirmé. Rien n’a été débité : réessayez ou contactez l’assistance.');
        }

        [$tone, $message] = match ($result['outcome']) {
            'paid' => ['success', 'Votre nouveau moyen de paiement est enregistré et votre paiement en retard est réglé. Votre abonnement se poursuit normalement.'],
            'processing' => ['success', 'Votre nouveau moyen de paiement est enregistré. Le paiement en retard est en cours de traitement : un prélèvement prend quelques jours ouvrés, vos avantages sont conservés en attendant.'],
            'refused' => ['error', 'Votre nouveau moyen de paiement est enregistré, mais le paiement en retard a encore été refusé. Vérifiez auprès de votre banque ou choisissez un autre moyen de paiement.'],
            default => ['success', 'Votre nouveau moyen de paiement est enregistré. Il sera utilisé pour vos prochaines échéances.'],
        };

        return redirect()->route('billing.index')->with($tone, $message);
    }
}

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
use Illuminate\Validation\Rule;

/**
 * Page « Facturation » : les abonnements du membre, leur moyen de paiement,
 * leur résiliation, les formules disponibles quand il n'en a aucune, et l'accès
 * Premium à paiement unique (un paiement par carte, sans abonnement).
 */
class BillingController extends Controller
{
    public function __construct(private readonly ProviderSubscriptionService $subscriptions) {}

    public function index()
    {
        $user = Auth::user();
        $all = $user->proSubscriptions()->latest('id')->get();

        // En cours : ce qui ouvre des avantages, peut encore être prélevé, ou est déjà payé pour plus tard.
        $isCurrent = fn (ProSubscription $subscription) => $subscription->isActive() || $subscription->hasOpenBilling() || $subscription->isPaidAhead();
        $current = $all->filter($isCurrent)->values();
        $past = $all->reject($isCurrent)->values();
        $late = $current->first(fn (ProSubscription $subscription) => $subscription->inPaymentGrace());
        $proSubscriptionsEnabled = PlatformFeatures::proSubscriptionsEnabled();
        // Accès à paiement unique : proposés seulement à qui peut réellement les payer.
        $oneTime = $this->subscriptions->oneTimeSalesFor($user);

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
            'oneTimeOffers' => $oneTime['offers'],
            'oneTimeExtendsFrom' => $oneTime['extends_from'],
            'payments' => Transaction::where('user_id', $user->id)->where('type', 'SUBSCRIPTION')
                ->where('status', 'completed')->latest()->take(12)->get(),
        ]);
    }

    /** Télécharge la facture d'un paiement d'abonnement : le PDF émis par Stripe. */
    public function invoice(Transaction $transaction)
    {
        abort_unless($transaction->user_id === Auth::id() && $transaction->type === 'SUBSCRIPTION', 404);

        try {
            // Abonnement : le paiement porte l'identifiant de sa facture. Paiement unique :
            // le justificatif est rattaché au paiement, ou relu dans la session de paiement.
            $invoiceId = (string) $transaction->stripe_session_id;
            if (! str_starts_with($invoiceId, 'in_')) {
                $invoiceId = (string) $this->subscriptions->oneTimeInvoiceId(Auth::user(), $transaction);
            }
            $url = $this->subscriptions->invoicePdfUrl(Auth::user(), $invoiceId);
        } catch (\Throwable $exception) {
            report($exception);
            $url = null;
        }

        return $url !== null
            ? redirect()->away($url)
            : redirect()->route('billing.index')->with('error', 'Cette facture n’a pas pu être récupérée pour le moment. Réessayez dans quelques minutes ou contactez l’assistance.');
    }

    /**
     * Ouvre le paiement d'un accès Premium à paiement unique : un seul paiement par
     * carte, sans abonnement. Rien n'est activé avant le paiement.
     */
    public function buyOneTimeAccess(Request $request)
    {
        $validated = $request->validate([
            'plan' => ['required', 'string', Rule::in(array_keys(ProviderSubscriptionPlans::oneTime()))],
            'from' => ['nullable', 'string', Rule::in(['billing', 'subscription'])],
            // Demande d'exécution immédiate et information sur la rétractation (C. consom. L221-25 et L221-28).
            'immediate_start' => ['accepted'],
        ], [
            'immediate_start.accepted' => 'Cochez la case pour demander l’ouverture immédiate de votre accès Premium : sans elle, le paiement ne peut pas commencer.',
        ]);
        $from = $validated['from'] ?? 'billing';
        $back = $from === 'subscription' ? 'pro.subscription' : 'billing.index';

        try {
            $session = $this->subscriptions->createOneTimeCheckoutSession(
                Auth::user(),
                $validated['plan'],
                route('billing.one-time.return').'?session_id={CHECKOUT_SESSION_ID}',
                route('billing.one-time.return', ['from' => $from]),
                now()
            );

            return redirect()->away($session->url);
        } catch (DomainException $exception) {
            return redirect()->route($back)->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route($back)->with('error', 'La page de paiement sécurisée est momentanément indisponible. Rien n’a été débité : réessayez dans quelques minutes.');
        }
    }

    /** Retour de Stripe : paiement abandonné (sans session), ou paiement à vérifier puis accès à ouvrir. */
    public function oneTimeAccessReturn(Request $request)
    {
        $sessionId = (string) $request->query('session_id', '');
        if ($sessionId === '') {
            return redirect()->route($request->query('from') === 'subscription' ? 'pro.subscription' : 'billing.index')
                ->with('info', 'Le paiement a été annulé : rien n’a été débité. Vous pouvez réessayer quand vous le souhaitez.');
        }
        if (preg_match('/^cs_[A-Za-z0-9_]+$/', $sessionId) !== 1) {
            return redirect()->route('billing.index')->with('error', 'Cette adresse de retour de paiement est invalide.');
        }

        try {
            $access = $this->subscriptions->completeOneTimeCheckout($sessionId, Auth::user());
        } catch (DomainException $exception) {
            return redirect()->route('billing.index')->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('billing.index')->with('error', 'Votre paiement n’a pas pu être vérifié pour le moment. S’il a bien été débité, votre accès Premium s’ouvrira automatiquement dans quelques minutes ; sinon, contactez l’assistance.');
        }

        // Un achat qui prolonge un accès en cours : la date annoncée est celle de la fin de tout l'accès.
        $until = $this->subscriptions->oneTimeAccessEnd(Auth::user()) ?? $access->ends_at;

        return redirect()->route('billing.index')
            ->with('success', 'Premium est actif jusqu’au '.$until?->format('d/m/Y').'. Aucun prélèvement ne suivra.');
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

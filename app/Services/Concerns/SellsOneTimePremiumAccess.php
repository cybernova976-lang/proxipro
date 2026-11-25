<?php

namespace App\Services\Concerns;

use App\Models\ProSubscription;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\AccountStatusNotification;
use App\Notifications\SubscriptionInvoiceNotification;
use App\Support\PlatformFeatures;
use App\Support\ProviderSubscriptionPlans;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Checkout\Session as StripeCheckoutSession;

/**
 * Accès Premium à paiement unique (décision du 25/11/2026).
 *
 * Un seul paiement par carte ouvre Premium pour 3 ou 6 mois : pas d'abonnement
 * Stripe, pas de carte gardée pour plus tard, aucun prélèvement ni reconduction.
 * L'accès s'arrête seul à sa date de fin (commande subscriptions:expire-pro).
 *
 * Il partage avec l'abonnement le verrou du membre, le client Stripe et la liste
 * des sessions de paiement ouvertes : un membre ne peut pas payer les deux à la fois.
 * Ce trait complète App\Services\ProviderSubscriptionService, dont il utilise les
 * outils internes (client Stripe, lecture des objets Stripe, adresses de retour).
 */
trait SellsOneTimePremiumAccess
{
    /**
     * Ouvre la page de paiement Stripe d'un accès à paiement unique. Rien n'est activé avant le paiement.
     * $immediateStartRequestedAt : moment où le membre a demandé l'ouverture immédiate de son accès,
     * en connaissant son délai de rétractation de 14 jours (C. consom. L221-25) ; noté chez Stripe.
     */
    public function createOneTimeCheckoutSession(User $user, string $plan, string $successUrl, string $cancelUrl, Carbon $immediateStartRequestedAt): StripeCheckoutSession
    {
        if (! PlatformFeatures::proSubscriptionsEnabled()) {
            throw new DomainException(PlatformFeatures::proSubscriptionUnavailableMessage());
        }

        if (! array_key_exists($plan, ProviderSubscriptionPlans::activeOneTime())) {
            throw new DomainException('Cet accès à paiement unique n’est pas disponible pour le moment.');
        }

        // Même verrou que l'abonnement et que la suppression du compte.
        return DB::transaction(function () use ($user, $plan, $successUrl, $cancelUrl, $immediateStartRequestedAt) {
            $member = User::query()->lockForUpdate()->findOrFail($user->getKey());

            try {
                return $this->prepareOneTimeCheckout($member, $plan, $successUrl, $cancelUrl, $immediateStartRequestedAt);
            } catch (\Throwable $exception) {
                if (! $member->stripe_id || ! self::isUnknownStripeCustomer($exception)) {
                    throw $exception;
                }
                // Client inconnu des clés actuelles : on en crée un nouveau, une seule fois.
                $stale = $member->stripe_id;
                $member->forceFill(['stripe_id' => null])->save();

                return $this->prepareOneTimeCheckout($member, $plan, $successUrl, $cancelUrl, $immediateStartRequestedAt, $stale);
            }
        });
    }

    /**
     * Ce qui empêche ce membre d'acheter un accès à paiement unique, ou null si rien
     * ne s'y oppose. Les pages s'en servent pour ne proposer que ce qui sera accepté.
     */
    public function oneTimePurchaseBlocker(User $user): ?string
    {
        if ($user->proSubscriptions()->billingOpen()->exists()) {
            return 'Vous avez déjà un abonnement Premium en cours ou en cours de facturation. Un accès à paiement unique se prend une fois cet abonnement terminé : gérez-le depuis la page Facturation.';
        }

        $unlimited = $user->proSubscriptions()->currentlyActive()
            ->whereNull('stripe_subscription_id')->whereNull('ends_at')->exists();

        return $unlimited
            ? 'Vous avez déjà un accès Premium sans date de fin : un accès à paiement unique ne vous apporterait rien de plus.'
            : null;
    }

    /**
     * Ce que les pages Facturation et Abonnement Premium proposent à ce membre : les
     * accès à paiement unique qu'il peut réellement payer, et la date à partir de
     * laquelle un achat prolongerait son accès en cours (null s'il commence tout de suite).
     *
     * @return array{offers: array<string, array<string, mixed>>, extends_from: ?Carbon}
     */
    public function oneTimeSalesFor(User $user): array
    {
        $offers = PlatformFeatures::proSubscriptionsEnabled() && $this->oneTimePurchaseBlocker($user) === null
            ? ProviderSubscriptionPlans::oneTimeOffers()
            : [];
        $start = $offers === [] ? null : $this->nextOneTimeStart($user);

        return ['offers' => $offers, 'extends_from' => $start?->isFuture() ? $start : null];
    }

    /**
     * Début d'un nouvel accès : maintenant, ou la fin du dernier accès Premium sans
     * prélèvement déjà payé (en cours ou en file : accès à paiement unique ou accès
     * offert), que l'achat prolonge.
     */
    public function nextOneTimeStart(User $user): Carbon
    {
        $latest = $user->proSubscriptions()->activeOrPaidAhead()
            ->whereNull('stripe_subscription_id')->whereNotNull('ends_at')->max('ends_at');
        $latest = $latest ? Carbon::parse($latest) : null;

        return $latest && $latest->isFuture() ? $latest : now();
    }

    /**
     * Fin de la couverture Premium continue du membre par ses accès sans prélèvement :
     * l'accès en cours, puis les accès payés d'avance qui s'y enchaînent sans trou.
     * Null si aucun accès daté n'est en cours.
     */
    public function oneTimeAccessEnd(User $user): ?Carbon
    {
        $accesses = $user->proSubscriptions()->activeOrPaidAhead()
            ->whereNull('stripe_subscription_id')->whereNotNull('ends_at')
            ->orderBy('starts_at')->get();

        $end = null;
        foreach ($accesses->filter(fn (ProSubscription $access) => $access->isActive()) as $access) {
            $end = $end && $end->greaterThan($access->ends_at) ? $end : $access->ends_at->copy();
        }
        if ($end === null) {
            return null;
        }

        // Accès en file qui commencent au plus tard à la fin de la couverture : ils la prolongent.
        foreach ($accesses->filter(fn (ProSubscription $access) => $access->isPaidAhead()) as $queued) {
            if ($queued->starts_at->lessThanOrEqualTo($end) && $queued->ends_at->greaterThan($end)) {
                $end = $queued->ends_at->copy();
            }
        }

        return $end;
    }

    /**
     * Fin d'un accès de $months mois commençant à $startsAt. S'il prolonge une chaîne
     * d'accès à paiement unique, la fin se compte depuis le début de la chaîne : un
     * accès qui finit un 28, 29 ou 30 ne fait pas perdre de jours aux suivants
     * (30/11 + 3 mois, puis + 3 mois : 30/05, et non 28/05).
     */
    private function oneTimeAccessEndFrom(User $member, Carbon $startsAt, int $months): Carbon
    {
        $plain = $startsAt->copy()->addMonthsNoOverflow($months);
        $previous = $member->proSubscriptions()->oneTimeAccess()->whereNull('stripe_subscription_id')
            ->whereIn('status', ['active', 'expired'])->whereNotNull('starts_at')->whereNotNull('ends_at')
            ->get();

        // On remonte la chaîne : chaque accès finit exactement là où commence le suivant.
        $chain = [];
        $link = $startsAt;
        for ($step = 0; $step < 50; $step++) {
            $before = $previous->first(fn (ProSubscription $access) => $access->ends_at->equalTo($link));
            if (! $before) {
                break;
            }
            array_unshift($chain, $before);
            $link = $before->starts_at;
            $previous = $previous->reject(fn (ProSubscription $access) => $access->is($before));
        }

        // Du plus ancien au plus récent : l'ancrage ne vaut que tant que chaque fin est bien
        // « ancrage + mois cumulés ». Une date corrigée à la main recommence la chaîne après elle.
        $anchor = null;
        $total = 0;
        foreach ($chain as $access) {
            if ($anchor === null) {
                $anchor = $access->starts_at->copy();
                $total = 0;
            }
            $total += $access->oneTimeMonths();
            if (! $access->ends_at->equalTo($anchor->copy()->addMonthsNoOverflow($total))) {
                $anchor = null;
            }
        }

        if ($anchor === null) {
            return $plain;
        }

        $anchored = $anchor->addMonthsNoOverflow($total + $months);

        // Jamais moins que la durée payée comptée depuis le début du nouvel accès.
        return $anchored->greaterThan($plain) ? $anchored : $plain;
    }

    /**
     * Paiements uniques déjà réglés chez Stripe dans les dernières 24 heures mais pas
     * encore enregistrés ici (événement Stripe en retard, retour de paiement non fait) :
     * ils sont enregistrés maintenant. Sert à refuser un abonnement qui ferait payer
     * Premium deux fois. Retourne vrai si un tel paiement existe.
     */
    private function recordPaidOneTimeCheckouts(User $user): bool
    {
        if (! $user->stripe_id) {
            return false;
        }

        try {
            $sessions = $this->client()->checkout->sessions->all([
                'customer' => $user->stripe_id,
                'status' => 'complete',
                'created' => ['gte' => now()->subDay()->getTimestamp()],
                'limit' => 100,
            ]);
        } catch (\Throwable $exception) {
            // Client inconnu des clés actuelles : aucun paiement à retrouver, la souscription le remplacera.
            if (self::isUnknownStripeCustomer($exception)) {
                return false;
            }
            throw $exception;
        }

        $found = false;
        foreach ($sessions->autoPagingIterator() as $session) {
            $metadata = $this->metadata($session);
            if ($this->value($session, 'mode') !== 'payment'
                || ($metadata['type'] ?? null) !== self::ONE_TIME_CHECKOUT_TYPE
                || ($metadata['user_id'] ?? null) !== (string) $user->id
                || $this->value($session, 'payment_status') !== 'paid'
                || ProSubscription::where('stripe_checkout_session_id', (string) $this->value($session, 'id'))->exists()) {
                continue;
            }

            $found = true;
            try {
                $this->completeOneTimeCheckoutSession($session, $user);
            } catch (DomainException $exception) {
                // Paiement à vérifier à la main : il bloque quand même l'abonnement.
                report($exception);
            }
        }

        return $found;
    }

    private function prepareOneTimeCheckout(User $user, string $plan, string $successUrl, string $cancelUrl, Carbon $immediateStartRequestedAt, ?string $replacesCustomer = null): StripeCheckoutSession
    {
        if (($blocker = $this->oneTimePurchaseBlocker($user)) !== null) {
            throw new DomainException($blocker);
        }

        $client = $this->client();
        $customerId = $this->ensureStripeCustomer($client, $user, $replacesCustomer);
        $amount = ProviderSubscriptionPlans::amount($plan);
        $cents = (int) round($amount * 100);
        if ($amount < ProviderSubscriptionPlans::MINIMUM_AMOUNT) {
            throw new DomainException('Le montant de cet accès est invalide.');
        }

        $metadata = [
            'user_id' => (string) $user->id,
            'type' => self::ONE_TIME_CHECKOUT_TYPE,
            'plan' => $plan,
            'months' => (string) ProviderSubscriptionPlans::months($plan),
            'expected_amount_cents' => (string) $cents,
            'expected_currency' => 'eur',
        ];

        $reusable = null;
        foreach ($this->openProviderCheckouts($client, $customerId, $user) as $session) {
            $existing = $this->metadata($session);
            $identical = $session->mode === 'payment' && $session->url
                && array_intersect_key($existing, $metadata) == $metadata;
            if (! $reusable && $identical) {
                $reusable = $session;
            } else {
                // Autre durée, ancien prix ou abonnement resté ouvert : la session ne doit plus pouvoir être payée.
                $client->checkout->sessions->expire($session->id);
            }
        }

        // Un abonnement déjà payé chez Stripe peut précéder le retour de paiement et le webhook.
        $subscriptions = $client->subscriptions->all(['customer' => $customerId, 'status' => 'all', 'limit' => 100]);
        foreach ($subscriptions->autoPagingIterator() as $remote) {
            if (in_array($remote->status, ProSubscription::TERMINAL_STRIPE_STATUSES, true)
                || ! $this->managesStripeSubscription($remote)) {
                continue;
            }
            if ($reusable) {
                $client->checkout->sessions->expire($reusable->id);
            }
            throw new DomainException('Stripe a déjà enregistré un abonnement à votre nom. Consultez la page Facturation dans quelques instants avant de prendre un accès à paiement unique.');
        }
        if ($reusable) {
            return $reusable;
        }

        // Référence propre à cet achat : elle relie plus tard le justificatif Stripe au paiement.
        $metadata['purchase_ref'] = (string) Str::uuid();
        // Preuve de la demande d'ouverture immédiate, avec l'information sur la rétractation.
        $metadata['immediate_start_requested_at'] = $immediateStartRequestedAt->copy()->utc()->toIso8601String();
        $label = ProviderSubscriptionPlans::stripeLabel($plan);

        return $client->checkout->sessions->create([
            'customer' => $customerId,
            'client_reference_id' => (string) $user->id,
            'payment_method_types' => ['card'],
            'billing_address_collection' => 'required',
            'customer_update' => [
                'address' => 'auto',
                'name' => 'auto',
            ],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $label,
                        'description' => ProviderSubscriptionPlans::description($plan),
                    ],
                    'unit_amount' => $cents,
                ],
                'quantity' => 1,
            ]],
            // Paiement simple : aucun abonnement n'est créé et la carte n'est pas gardée pour plus tard.
            'mode' => 'payment',
            'success_url' => $this->publicReturnUrl($successUrl),
            'cancel_url' => $this->publicReturnUrl($cancelUrl),
            'metadata' => $metadata,
            'payment_intent_data' => [
                'description' => $label,
                'metadata' => $metadata,
            ],
            // Justificatif émis par Stripe après le paiement, téléchargeable depuis « Mes paiements ».
            'invoice_creation' => [
                'enabled' => true,
                'invoice_data' => [
                    'description' => $label,
                    'footer' => 'Paiement unique : aucun abonnement, aucun prélèvement ultérieur.',
                    'metadata' => $metadata,
                ],
            ],
        ]);
    }

    /** Retour du membre après le paiement : la session est relue chez Stripe avant toute activation. */
    public function completeOneTimeCheckout(string $sessionId, User $expectedUser): ProSubscription
    {
        $session = $this->client()->checkout->sessions->retrieve($sessionId, ['expand' => ['invoice']]);

        return $this->completeOneTimeCheckoutSession($session, $expectedUser);
    }

    /**
     * Active l'accès payé. Le retour du membre et l'événement Stripe arrivent tous
     * les deux, parfois en même temps : la session n'ouvre qu'un seul accès.
     */
    public function completeOneTimeCheckoutSession(object $session, ?User $expectedUser = null): ProSubscription
    {
        $metadata = $this->metadata($session);
        $userId = (int) ($metadata['user_id'] ?? 0);
        $plan = (string) ($metadata['plan'] ?? '');
        $sessionId = (string) $this->value($session, 'id');

        if (($metadata['type'] ?? null) !== self::ONE_TIME_CHECKOUT_TYPE
            || $this->value($session, 'mode') !== 'payment' || $userId <= 0 || $sessionId === '') {
            throw new DomainException('Cette session Stripe ne correspond pas à un accès Premium à paiement unique.');
        }
        if (! ProviderSubscriptionPlans::isOneTime($plan)) {
            throw new DomainException('La formule de cet accès est inconnue.');
        }

        $reference = (string) $this->value($session, 'client_reference_id');
        if (($expectedUser && (int) $expectedUser->id !== $userId) || ($reference !== '' && $reference !== (string) $userId)) {
            throw new DomainException('La session de paiement ne correspond pas à cet utilisateur.');
        }
        if ($this->value($session, 'payment_status') !== 'paid') {
            throw new DomainException('Le paiement n’est pas encore confirmé : votre accès Premium s’ouvrira dès sa confirmation.');
        }

        // Ce qui a réellement été payé doit être le prix de la formule au moment de l'achat.
        $expectedAmount = (int) ($metadata['expected_amount_cents'] ?? 0);
        $paidAmount = $this->value($session, 'amount_total');
        $minimum = (int) round(ProviderSubscriptionPlans::MINIMUM_AMOUNT * 100);
        if ($expectedAmount < $minimum || ! is_numeric($paidAmount) || (int) $paidAmount !== $expectedAmount
            || ($metadata['expected_currency'] ?? null) !== 'eur'
            || strtolower((string) $this->value($session, 'currency')) !== 'eur') {
            throw new DomainException('Le montant ou la devise du paiement ne correspond pas à l’accès acheté.');
        }

        // La durée est celle annoncée et payée, notée à l'ouverture du paiement ; à défaut, celle de la formule.
        $months = is_numeric($metadata['months'] ?? null)
            ? (int) $metadata['months']
            : (ProviderSubscriptionPlans::get($plan) !== null ? ProviderSubscriptionPlans::months($plan) : 0);
        if ($months < 1 || $months > 36) {
            throw new DomainException('La durée de cet accès est invalide.');
        }

        $invoice = $this->value($session, 'invoice');
        $attributes = [
            'plan' => $plan,
            'months' => $months,
            'amount' => $expectedAmount / 100,
            'payment_intent' => $this->resourceId($this->value($session, 'payment_intent')) ?: null,
            'invoice_id' => $this->resourceId($invoice) ?: null,
            'purchase_ref' => filled($metadata['purchase_ref'] ?? null) ? (string) $metadata['purchase_ref'] : null,
            'immediate_start_requested_at' => filled($metadata['immediate_start_requested_at'] ?? null)
                ? (string) $metadata['immediate_start_requested_at'] : null,
        ];

        try {
            [$access, $created] = $this->openOneTimeAccess($userId, $sessionId, $attributes);
        } catch (UniqueConstraintViolationException) {
            // Deux passages simultanés : l'autre a ouvert l'accès, celui-ci ne le double pas.
            [$access, $created] = [ProSubscription::where('stripe_checkout_session_id', $sessionId)->firstOrFail(), false];
        }

        if ($created) {
            AccountStatusNotification::dispatch($access->user, 'premium_pass_activated', [
                'until' => $access->ends_at?->format('d/m/Y'),
            ]);
        }

        $payment = Transaction::firstWhere('stripe_session_id', $sessionId);
        if ($payment && $invoice !== null) {
            $this->attachOneTimeInvoice($payment, $invoice);
        }

        return $access->fresh();
    }

    /** @return array{0: ProSubscription, 1: bool} l'accès, et s'il vient d'être ouvert */
    private function openOneTimeAccess(int $userId, string $sessionId, array $attributes): array
    {
        return DB::transaction(function () use ($userId, $sessionId, $attributes) {
            // Le verrou du membre met en file le retour de paiement et l'événement Stripe.
            $member = User::query()->lockForUpdate()->find($userId);
            if (! $member) {
                throw new DomainException('Utilisateur local introuvable pour ce paiement.');
            }

            $existing = ProSubscription::where('stripe_checkout_session_id', $sessionId)->first();
            if ($existing) {
                return [$existing, false];
            }

            // Filet : un abonnement prélevé existe déjà. Le paiement unique est encaissé, il est
            // donc enregistré, mais le membre paie Premium deux fois : à régler à la main.
            if ($member->proSubscriptions()->billingOpen()->exists()) {
                Log::warning('Accès Premium à paiement unique payé alors qu’un abonnement prélevé est en cours : double paiement à régler (remboursement depuis Stripe).', [
                    'user_id' => $member->id,
                    'stripe_checkout_session_id' => $sessionId,
                    'plan' => $attributes['plan'],
                    'amount' => $attributes['amount'],
                ]);
            }

            // Un accès déjà en cours est prolongé : le nouveau commence à sa date de fin.
            $startsAt = $this->nextOneTimeStart($member);
            $access = ProSubscription::create([
                'user_id' => $member->id,
                'plan' => $attributes['plan'],
                'amount' => $attributes['amount'],
                'status' => 'active',
                'starts_at' => $startsAt,
                'ends_at' => $this->oneTimeAccessEndFrom($member, $startsAt, $attributes['months']),
                'auto_renew' => false,
                'stripe_checkout_session_id' => $sessionId,
                'stripe_payment_intent' => $attributes['payment_intent'],
                'last_payment_at' => now(),
                'notifications_enabled' => true,
                'realtime_notifications' => $member->pro_notifications_realtime ?? true,
                'selected_categories' => $member->pro_service_categories ?? [],
                'intervention_radius' => $member->pro_intervention_radius ?? 30,
            ]);

            $member->update([
                'pro_subscription_plan' => $attributes['plan'],
                'pro_status' => 'active',
            ]);

            // Le paiement rejoint « Mes paiements » de la page Facturation.
            Transaction::firstOrCreate(
                ['stripe_session_id' => $sessionId],
                [
                    'user_id' => $member->id,
                    'amount' => $attributes['amount'],
                    'type' => 'SUBSCRIPTION',
                    'description' => 'Paiement unique Premium '.$attributes['months'].' mois',
                    'status' => 'completed',
                    'metadata' => [
                        'kind' => self::ONE_TIME_CHECKOUT_TYPE,
                        'plan' => $attributes['plan'],
                        'months' => $attributes['months'],
                        'pro_subscription_id' => $access->id,
                        'purchase_ref' => $attributes['purchase_ref'],
                        'stripe_payment_intent' => $attributes['payment_intent'],
                        'stripe_invoice_id' => $attributes['invoice_id'],
                        'invoice_number' => null,
                        // Demande d'ouverture immédiate et information sur la rétractation, cochées avant le paiement.
                        'immediate_start_requested_at' => $attributes['immediate_start_requested_at'],
                    ],
                ]
            );

            return [$access, true];
        });
    }

    /** Événement « facture payée » d'un paiement unique : le justificatif rejoint son paiement. */
    private function syncOneTimeInvoicePaid(object $invoice): void
    {
        $metadata = $this->metadata($invoice);
        $userId = (int) ($metadata['user_id'] ?? 0);
        $reference = (string) ($metadata['purchase_ref'] ?? '');
        $invoiceId = (string) $this->value($invoice, 'id');
        if (($metadata['type'] ?? null) !== self::ONE_TIME_CHECKOUT_TYPE || $userId <= 0 || $invoiceId === '') {
            return;
        }

        $payment = Transaction::where('user_id', $userId)->where('type', 'SUBSCRIPTION')->latest('id')->take(30)->get()
            ->first(function (Transaction $payment) use ($reference, $invoiceId) {
                $details = (array) $payment->metadata;

                return ($details['kind'] ?? null) === self::ONE_TIME_CHECKOUT_TYPE
                    && (($details['stripe_invoice_id'] ?? null) === $invoiceId
                        || ($reference !== '' && ($details['purchase_ref'] ?? null) === $reference));
            });

        // Paiement pas encore enregistré : l'activation rattachera elle-même le justificatif.
        if ($payment) {
            $this->attachOneTimeInvoice($payment, $invoice);
        }
    }

    /**
     * Rattache le justificatif Stripe au paiement, puis l'envoie au membre par e-mail
     * dès qu'il est émis. Une seule fois, même si plusieurs passages se croisent.
     */
    private function attachOneTimeInvoice(Transaction $payment, mixed $invoice): void
    {
        $invoiceId = $this->resourceId($invoice);
        if (! str_starts_with($invoiceId, 'in_')) {
            return;
        }

        try {
            if (! is_object($invoice)) {
                if (! empty($payment->metadata['invoice_emailed_at'])) {
                    return;
                }
                $invoice = $this->client()->invoices->retrieve($invoiceId);
            }

            $issued = $this->value($invoice, 'status') === 'paid';
            $number = (string) $this->value($invoice, 'number');
            $send = DB::transaction(function () use ($payment, $invoiceId, $number, $issued) {
                $locked = Transaction::whereKey($payment->getKey())->lockForUpdate()->first();
                if (! $locked) {
                    return false;
                }
                $details = (array) $locked->metadata;
                $send = $issued && empty($details['invoice_emailed_at']);
                $details['stripe_invoice_id'] = $invoiceId;
                if ($number !== '') {
                    $details['invoice_number'] = $number;
                }
                if ($send) {
                    $details['invoice_emailed_at'] = now()->toIso8601String();
                }
                $locked->update(['metadata' => $details]);

                return $send;
            });

            if ($send) {
                SubscriptionInvoiceNotification::dispatch(
                    $payment->user,
                    $payment,
                    $number !== '' ? $number : null,
                    ((string) $this->value($invoice, 'invoice_pdf')) ?: null,
                );
            }
        } catch (\Throwable $exception) {
            // L'accès est ouvert : un justificatif en retard ne doit jamais le remettre en cause.
            report($exception);
        }
    }

    /**
     * Identifiant du justificatif Stripe d'un paiement unique, pour son téléchargement.
     * S'il n'a pas encore été rattaché au paiement, il est relu dans la session de paiement.
     */
    public function oneTimeInvoiceId(User $user, Transaction $payment): ?string
    {
        $details = (array) $payment->metadata;
        if (($details['kind'] ?? null) !== self::ONE_TIME_CHECKOUT_TYPE || $payment->user_id !== $user->id) {
            return null;
        }
        if (str_starts_with((string) ($details['stripe_invoice_id'] ?? ''), 'in_')) {
            return $details['stripe_invoice_id'];
        }

        $session = $this->client()->checkout->sessions->retrieve((string) $payment->stripe_session_id);
        $invoiceId = $this->resourceId($this->value($session, 'invoice'));
        if (($this->metadata($session)['user_id'] ?? null) !== (string) $user->id || ! str_starts_with($invoiceId, 'in_')) {
            return null;
        }

        $details['stripe_invoice_id'] = $invoiceId;
        $payment->update(['metadata' => $details]);

        return $invoiceId;
    }

    /**
     * Rappel avant la fin : rien ne se renouvelle, le membre est donc prévenu quelques
     * jours avant, une seule fois par accès. Retourne vrai si le rappel est parti.
     */
    public function remindOneTimeAccessEnding(ProSubscription $access): bool
    {
        if (! $access->isOneTimeAccess() || ! $access->isActive() || ! $access->ends_at
            || $access->expiry_reminder_sent_at !== null || ! $access->user) {
            return false;
        }

        // Accès déjà prolongé, ou abonnement pris entre-temps : rien ne s'arrête à cette date.
        $continues = $access->user->proSubscriptions()->activeOrPaidAhead()->whereKeyNot($access->getKey())
            ->where(function ($query) use ($access) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', $access->ends_at);
            })->exists();
        if ($continues) {
            return false;
        }

        $claimed = ProSubscription::whereKey($access->getKey())
            ->whereNull('expiry_reminder_sent_at')
            ->update(['expiry_reminder_sent_at' => now()]);
        if (! $claimed) {
            return false;
        }

        AccountStatusNotification::dispatch($access->user, 'premium_pass_ending', [
            'until' => $access->ends_at->format('d/m/Y'),
            'days_left' => max(1, (int) ceil(now()->diffInDays($access->ends_at, false))),
        ]);

        return true;
    }
}

<?php

namespace App\Services;

use App\Models\ProSubscription;
use App\Models\Transaction;
use App\Models\User;
use App\Support\PlatformFeatures;
use App\Support\ProviderSubscriptionPlans;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\StripeClient;

class ProviderSubscriptionService
{
    public const CHECKOUT_TYPES = [
        'pro_subscription',
        'become_provider_subscription',
        'provider_subscription',
    ];

    /** Session Stripe qui enregistre un nouveau moyen de paiement, sans rien débiter. */
    public const PAYMENT_METHOD_CHECKOUT_TYPE = 'subscription_payment_method';

    /** États Stripe d'un abonnement dont le dernier paiement n'a pas abouti. */
    public const LATE_STRIPE_STATUSES = ['past_due', 'unpaid'];

    /** Plafond d'une prolongation du délai accordée par un administrateur, en jours. */
    public const MAX_GRACE_EXTENSION_DAYS = 30;

    public function createCheckoutSession(
        User $user,
        string $plan,
        string $successUrl,
        string $cancelUrl,
        string $flow = 'pro_subscription'
    ): StripeCheckoutSession {
        if (! PlatformFeatures::proSubscriptionsEnabled()) {
            throw new DomainException(PlatformFeatures::proSubscriptionUnavailableMessage());
        }

        $planConfig = ProviderSubscriptionPlans::get($plan);
        if (! $planConfig || empty($planConfig['enabled'])) {
            throw new DomainException('Cet abonnement n’est pas disponible pour le moment.');
        }

        // Subscription entry points and account deletion share the same member lock.
        return DB::transaction(function () use ($user, $plan, $successUrl, $cancelUrl, $flow) {
            $member = User::query()->lockForUpdate()->findOrFail($user->getKey());

            return $this->prepareCheckout($member, $plan, $successUrl, $cancelUrl, $flow);
        });
    }

    private function prepareCheckout(User $user, string $plan, string $successUrl, string $cancelUrl, string $flow): StripeCheckoutSession
    {
        if ($user->hasActiveProSubscription()) {
            throw new DomainException('Vous disposez déjà d’un abonnement Pro actif.');
        }

        if ($user->proSubscriptions()->billingOpen()->exists()) {
            throw new DomainException('Un abonnement est déjà en cours de facturation. Gérez sa carte ou sa résiliation depuis Mon abonnement avant de souscrire à nouveau.');
        }

        $client = $this->client();
        $customerId = $this->ensureStripeCustomer($client, $user);
        $amount = ProviderSubscriptionPlans::amount($plan);

        if ($amount <= 0) {
            throw new DomainException('Le montant de cet abonnement est invalide.');
        }

        $metadata = [
            'user_id' => (string) $user->id,
            'type' => $flow,
            'plan' => $plan,
            'expected_amount_cents' => (string) ((int) round($amount * 100)),
            'expected_currency' => 'eur',
        ];

        $reusable = null;
        foreach ($this->openProviderCheckouts($client, $customerId, $user) as $session) {
            $existing = $this->metadata($session);
            if (! $reusable && $session->url && $existing['type'] === $flow
                && ($existing['plan'] ?? null) === $plan
                && ($existing['expected_amount_cents'] ?? null) === $metadata['expected_amount_cents']
                && ($existing['expected_currency'] ?? null) === $metadata['expected_currency']) {
                $reusable = $session;
            } else {
                $client->checkout->sessions->expire($session->id);
            }
        }

        // A completed Stripe checkout can precede both the return page and the webhook.
        $subscriptions = $client->subscriptions->all(['customer' => $customerId, 'status' => 'all', 'limit' => 100]);
        foreach ($subscriptions->autoPagingIterator() as $remote) {
            if (in_array($remote->status, ProSubscription::TERMINAL_STRIPE_STATUSES, true)
                || ! $this->managesStripeSubscription($remote)) {
                continue;
            }
            if ($reusable) {
                $client->checkout->sessions->expire($reusable->id);
            }
            throw new DomainException('Stripe a déjà enregistré votre abonnement. Consultez Mon abonnement dans quelques instants pour suivre sa confirmation.');
        }
        if ($reusable) {
            return $reusable;
        }

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
                        'name' => ProviderSubscriptionPlans::stripeLabel($plan),
                        'description' => ProviderSubscriptionPlans::description($plan),
                    ],
                    'unit_amount' => (int) round($amount * 100),
                    'recurring' => [
                        'interval' => $plan === 'annual' ? 'year' : 'month',
                        'interval_count' => 1,
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => $this->publicReturnUrl($successUrl),
            'cancel_url' => $this->publicReturnUrl($cancelUrl),
            'metadata' => $metadata,
            'subscription_data' => [
                'metadata' => $metadata,
            ],
        ]);
    }

    public function completeCheckout(string $sessionId, User $expectedUser, array $attributes = []): ProSubscription
    {
        $session = $this->client()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['subscription.latest_invoice.payment_intent'],
        ]);

        return $this->completeCheckoutSession($session, $expectedUser, $attributes);
    }

    public function completeCheckoutSession(object $session, ?User $expectedUser = null, array $attributes = []): ProSubscription
    {
        $metadata = $this->metadata($session);
        $type = (string) ($metadata['type'] ?? '');
        $userId = (int) ($metadata['user_id'] ?? 0);
        $plan = (string) ($metadata['plan'] ?? '');

        if (! in_array($type, self::CHECKOUT_TYPES, true) || $userId <= 0) {
            throw new DomainException('Cette session Stripe ne correspond pas à un abonnement Pro.');
        }
        if (! in_array($plan, ['monthly', 'annual'], true)) {
            throw new DomainException('Le plan de l’abonnement est invalide.');
        }

        $expectedAmount = (int) ($metadata['expected_amount_cents']
            ?? round(ProviderSubscriptionPlans::amount($plan) * 100));
        $expectedCurrency = strtolower((string) ($metadata['expected_currency'] ?? 'eur'));
        $sessionAmount = $this->value($session, 'amount_total');
        $sessionCurrency = strtolower((string) $this->value($session, 'currency'));
        if (! is_numeric($sessionAmount)
            || (int) $sessionAmount !== $expectedAmount
            || $sessionCurrency !== $expectedCurrency) {
            throw new DomainException('Le montant ou la devise de l’abonnement est invalide.');
        }

        $user = $expectedUser ?? User::find($userId);
        if (! $user || $user->id !== $userId) {
            throw new DomainException('La session de paiement ne correspond pas à cet utilisateur.');
        }

        $subscriptionObject = $this->value($session, 'subscription');
        $subscriptionId = is_object($subscriptionObject)
            ? (string) $this->value($subscriptionObject, 'id')
            : (string) $subscriptionObject;

        if ($subscriptionId === '') {
            throw new DomainException('Stripe n’a retourné aucun abonnement récurrent.');
        }

        if (! is_object($subscriptionObject)) {
            $subscriptionObject = $this->client()->subscriptions->retrieve($subscriptionId, [
                'expand' => ['latest_invoice.payment_intent'],
            ]);
        }

        $stripeStatus = (string) $this->value($subscriptionObject, 'status');
        if (! in_array($stripeStatus, ['active', 'trialing'], true)) {
            throw new DomainException('Le premier paiement de l’abonnement n’est pas encore confirmé.');
        }

        $attributes['stripe_checkout_session_id'] = (string) $this->value($session, 'id');
        $attributes['stripe_payment_intent'] = $this->paymentIntentId($session, $subscriptionObject);

        return $this->syncStripeSubscription($subscriptionObject, $user, $plan, $attributes);
    }

    public function syncStripeSubscription(
        object $stripeSubscription,
        ?User $knownUser = null,
        ?string $knownPlan = null,
        array $attributes = []
    ): ProSubscription {
        $stripeId = (string) $this->value($stripeSubscription, 'id');
        if ($stripeId === '') {
            throw new DomainException('Identifiant d’abonnement Stripe manquant.');
        }

        $existing = ProSubscription::where('stripe_subscription_id', $stripeId)->first();
        $metadata = $this->metadata($stripeSubscription);
        $user = $knownUser
            ?? $existing?->user
            ?? User::find((int) ($metadata['user_id'] ?? 0));

        if (! $user) {
            $customerId = $this->resourceId($this->value($stripeSubscription, 'customer'));
            $user = $customerId !== '' ? User::where('stripe_id', $customerId)->first() : null;
        }

        if (! $user) {
            throw new DomainException('Utilisateur local introuvable pour cet abonnement Stripe.');
        }

        $plan = $knownPlan ?: ($metadata['plan'] ?? null) ?: $existing?->plan;
        if (! in_array($plan, ['monthly', 'annual'], true)) {
            throw new DomainException('Plan local introuvable pour cet abonnement Stripe.');
        }

        $stripeStatus = (string) $this->value($stripeSubscription, 'status');
        $cancelAtPeriodEnd = (bool) $this->value($stripeSubscription, 'cancel_at_period_end');
        $pauseCollection = $this->value($stripeSubscription, 'pause_collection');

        // Paiement en retard sur un abonnement qui était ouvert : le membre garde ses
        // avantages jusqu'à la fin du délai de régularisation, compté depuis le premier échec.
        $failedAt = null;
        $graceEndsAt = null;
        if (in_array($stripeStatus, self::LATE_STRIPE_STATUSES, true) && ! filled($pauseCollection)
            && $existing && ($existing->status === 'active' || $existing->payment_failed_at)) {
            $failedAt = $existing->payment_failed_at ?? now();
            $graceEndsAt = $existing->grace_ends_at
                ?? $failedAt->copy()->addDays(ProSubscription::PAYMENT_GRACE_DAYS);
        }
        $localStatus = $graceEndsAt?->isFuture()
            ? 'active'
            : $this->localStatus($stripeStatus, filled($pauseCollection));
        $items = $this->value($this->value($stripeSubscription, 'items'), 'data');
        $firstItem = is_array($items) ? ($items[0] ?? null) : null;
        $stripePrice = $this->value($firstItem, 'price');
        $stripeUnitAmount = $this->value($stripePrice, 'unit_amount');
        $amount = is_numeric($stripeUnitAmount)
            ? ((int) $stripeUnitAmount) / 100
            : (float) ($existing?->amount ?? ProviderSubscriptionPlans::amount($plan));
        $startsAt = $this->timestamp(
            $this->value($stripeSubscription, 'current_period_start')
                ?? $this->value($firstItem, 'current_period_start')
        );
        $endsAt = $this->timestamp(
            $this->value($stripeSubscription, 'current_period_end')
                ?? $this->value($firstItem, 'current_period_end')
        );
        $cancelledAt = $this->timestamp($this->value($stripeSubscription, 'canceled_at'));

        $payload = array_merge([
            'user_id' => $user->id,
            'plan' => $plan,
            'amount' => $amount,
            'status' => $localStatus,
            'stripe_status' => $stripeStatus,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'cancelled_at' => $cancelledAt,
            'stripe_subscription_id' => $stripeId,
            'auto_renew' => ! $cancelAtPeriodEnd && $localStatus === 'active',
            'notifications_enabled' => $existing?->notifications_enabled ?? true,
            'realtime_notifications' => $existing?->realtime_notifications ?? $user->pro_notifications_realtime ?? true,
            'selected_categories' => $existing?->selected_categories ?? $user->pro_service_categories ?? [],
            'intervention_radius' => $existing?->intervention_radius ?? $user->pro_intervention_radius ?? 30,
        ], array_filter($attributes, fn ($value): bool => $value !== null));

        if ($failedAt === null) {
            // Paiement à jour, abonnement terminé ou suspendu : plus aucun délai en cours.
            $payload['payment_failed_at'] = null;
            $payload['grace_ends_at'] = null;
            $payload['payment_reminders_sent'] = 0;
        } elseif ($existing?->payment_failed_at) {
            $payload['grace_ends_at'] = $graceEndsAt;
        }

        $subscription = DB::transaction(function () use ($stripeId, $payload, $user, $localStatus, $plan) {
            $subscription = ProSubscription::updateOrCreate(
                ['stripe_subscription_id' => $stripeId],
                $payload
            );

            if ($localStatus === 'active') {
                $user->update([
                    'pro_subscription_plan' => $plan,
                    'pro_status' => 'active',
                ]);
            } elseif (! $user->proSubscriptions()->currentlyActive()->whereKeyNot($subscription->id)->exists()) {
                $user->update([
                    'pro_subscription_plan' => null,
                    'pro_status' => 'inactive',
                ]);
            }

            return $subscription;
        });

        // Notification au passage en « actif » ou à la fin de l'accès, une seule fois :
        // Stripe renvoie le même état plusieurs fois (retour de paiement, webhooks).
        $previousStatus = $existing?->status;
        $wasLate = (bool) $existing?->payment_failed_at;
        if ($failedAt !== null && ! $wasLate) {
            // Premier échec : le délai démarre ici, et le membre est prévenu une seule fois.
            $this->startPaymentGrace($subscription, $user, $failedAt, $graceEndsAt);
        } elseif (! $this->notifyStatusChanges) {
            // Action d’un administrateur : c’est elle qui prévient le membre, avec le bon message.
        } elseif ($wasLate && $failedAt === null && $localStatus === 'active') {
            \App\Notifications\AccountStatusNotification::dispatch($user, 'payment_recovered', [
                'until' => $endsAt?->format('d/m/Y'),
            ]);
        } elseif ($localStatus === 'active' && $previousStatus !== 'active') {
            \App\Notifications\AccountStatusNotification::dispatch($user, 'premium_activated', [
                'until' => $endsAt?->format('d/m/Y'),
            ]);
        } elseif ($previousStatus === 'active' && in_array($localStatus, ['cancelled', 'expired'], true)) {
            \App\Notifications\AccountStatusNotification::dispatch($user, $wasLate ? 'premium_unpaid_ended' : 'premium_expired');
        }

        return $subscription->fresh();
    }

    /**
     * Ouvre le délai de régularisation. La mise à jour conditionnelle garantit un seul
     * départ, même si deux événements Stripe annoncent le même échec en même temps.
     */
    private function startPaymentGrace(ProSubscription $subscription, User $user, Carbon $failedAt, Carbon $graceEndsAt): void
    {
        $started = ProSubscription::whereKey($subscription->getKey())
            ->whereNull('payment_failed_at')
            ->update([
                'payment_failed_at' => $failedAt,
                'grace_ends_at' => $graceEndsAt,
                'payment_reminders_sent' => 0,
            ]);

        if ($started) {
            \App\Notifications\AccountStatusNotification::dispatch($user, 'payment_failed', [
                'until' => $graceEndsAt->format('d/m/Y'),
            ]);
        }
    }

    /**
     * Relit l'abonnement chez Stripe avant de l'enregistrer : un événement arrivé en
     * retard ne doit pas rouvrir un incident de paiement déjà réglé.
     */
    public function syncLatest(string $stripeSubscriptionId): ?ProSubscription
    {
        $stripeSubscription = $this->client()->subscriptions->retrieve($stripeSubscriptionId);

        return $this->managesStripeSubscription($stripeSubscription)
            ? $this->syncStripeSubscription($stripeSubscription)
            : null;
    }

    /** Événement d'abonnement reçu de Stripe. */
    public function syncFromWebhook(object $stripeSubscription): ProSubscription
    {
        $stripeId = (string) $this->value($stripeSubscription, 'id');
        if ($stripeId !== '' && in_array($this->value($stripeSubscription, 'status'), self::LATE_STRIPE_STATUSES, true)) {
            return $this->syncLatest($stripeId) ?? $this->syncStripeSubscription($stripeSubscription);
        }

        return $this->syncStripeSubscription($stripeSubscription);
    }

    /** Les actions de l’administration envoient leur propre notification : la synchronisation reste muette. */
    private bool $notifyStatusChanges = true;

    private function withoutStatusNotifications(callable $callback): mixed
    {
        $previous = $this->notifyStatusChanges;
        $this->notifyStatusChanges = false;

        try {
            return $callback();
        } finally {
            $this->notifyStatusChanges = $previous;
        }
    }

    public function managesStripeSubscription(object $stripeSubscription): bool
    {
        $metadata = $this->metadata($stripeSubscription);
        if (in_array($metadata['type'] ?? null, self::CHECKOUT_TYPES, true)) {
            return true;
        }

        $stripeId = (string) $this->value($stripeSubscription, 'id');

        return $stripeId !== '' && ProSubscription::where('stripe_subscription_id', $stripeId)->exists();
    }

    public function syncInvoicePaid(object $invoice): ?ProSubscription
    {
        $subscriptionId = $this->invoiceSubscriptionId($invoice);
        if ($subscriptionId === '') {
            return null;
        }

        $stripeSubscription = $this->client()->subscriptions->retrieve($subscriptionId);
        if (! $this->managesStripeSubscription($stripeSubscription)) {
            return null;
        }

        $subscription = $this->syncStripeSubscription($stripeSubscription, attributes: [
            'last_payment_at' => now(),
        ]);

        $invoiceId = (string) $this->value($invoice, 'id');
        if ($invoiceId !== '') {
            $invoiceNumber = (string) $this->value($invoice, 'number');
            $payment = Transaction::firstOrCreate(
                ['stripe_session_id' => $invoiceId],
                [
                    'user_id' => $subscription->user_id,
                    'amount' => ((int) $this->value($invoice, 'amount_paid')) / 100,
                    'type' => 'SUBSCRIPTION',
                    'description' => 'Paiement '.ProviderSubscriptionPlans::summaryLabel($subscription->plan),
                    'status' => 'completed',
                    'metadata' => [
                        'stripe_subscription_id' => $subscriptionId,
                        'stripe_invoice_id' => $invoiceId,
                        'invoice_number' => $invoiceNumber !== '' ? $invoiceNumber : null,
                        'billing_reason' => $this->value($invoice, 'billing_reason'),
                    ],
                ]
            );
            // Une seule fois par facture : Stripe peut renvoyer le même événement.
            if ($payment->wasRecentlyCreated) {
                \App\Notifications\SubscriptionInvoiceNotification::dispatch(
                    $subscription->user,
                    $payment,
                    $invoiceNumber !== '' ? $invoiceNumber : null,
                    ((string) $this->value($invoice, 'invoice_pdf')) ?: null,
                );
            }
        }

        return $subscription;
    }

    /**
     * Adresse du PDF d'une facture d'abonnement, relue chez Stripe (le lien
     * a une durée de vie limitée) et rendue seulement si la facture appartient
     * bien à ce membre.
     */
    public function invoicePdfUrl(User $user, string $invoiceId): ?string
    {
        if (! $user->stripe_id || ! str_starts_with($invoiceId, 'in_')) {
            return null;
        }

        $invoice = $this->client()->invoices->retrieve($invoiceId);
        if ($this->resourceId($this->value($invoice, 'customer')) !== $user->stripe_id) {
            return null;
        }

        $url = (string) $this->value($invoice, 'invoice_pdf');

        return str_starts_with($url, 'https://') ? $url : null;
    }

    /**
     * Paiement refusé. L'état est relu chez Stripe : un abonnement qui était ouvert
     * entre dans son délai de régularisation, un abonnement jamais payé reste fermé.
     */
    public function markInvoiceFailed(object $invoice): ?ProSubscription
    {
        $subscriptionId = $this->invoiceSubscriptionId($invoice);

        return $subscriptionId === '' ? null : $this->syncLatest($subscriptionId);
    }

    public function cancelAtPeriodEnd(ProSubscription $subscription): ProSubscription
    {
        $this->requireStripeSubscription($subscription);
        $stripeSubscription = $this->client()->subscriptions->update(
            $subscription->stripe_subscription_id,
            ['cancel_at_period_end' => true]
        );

        return $this->syncStripeSubscription($stripeSubscription, $subscription->user, $subscription->plan);
    }

    public function resumeRenewal(ProSubscription $subscription): ProSubscription
    {
        $this->requireStripeSubscription($subscription);
        if ($subscription->status !== 'active' || ($subscription->ends_at && $subscription->ends_at->isPast())) {
            throw new DomainException('Cet abonnement est déjà terminé et ne peut pas être réactivé.');
        }

        $stripeSubscription = $this->client()->subscriptions->update(
            $subscription->stripe_subscription_id,
            ['cancel_at_period_end' => false]
        );

        return $this->syncStripeSubscription($stripeSubscription, $subscription->user, $subscription->plan);
    }

    public function createBillingPortalSession(User $user, string $returnUrl): string
    {
        if (! $user->stripe_id) {
            throw new DomainException('Aucun compte de facturation Stripe n’est associé à ce profil.');
        }

        $portalSession = $this->client()->billingPortal->sessions->create([
            'customer' => $user->stripe_id,
            'return_url' => $this->publicReturnUrl($returnUrl),
        ]);

        return (string) $portalSession->url;
    }

    /** Complete remote cancellations before any account data is erased. */
    public function cancelForAccountDeletion(User $user): void
    {
        $localSubscriptions = $user->proSubscriptions()->whereNotNull('stripe_subscription_id')->get();
        if (! $user->stripe_id && $localSubscriptions->isEmpty()) {
            return;
        }

        $client = $this->client();
        $remoteSubscriptions = [];
        if ($user->stripe_id) {
            foreach ($this->openProviderCheckouts($client, $user->stripe_id, $user) as $session) {
                $client->checkout->sessions->expire($session->id);
            }
            // Include a checkout whose webhook has not reached our database yet.
            foreach ($client->subscriptions->all(['customer' => $user->stripe_id, 'status' => 'all', 'limit' => 100])->autoPagingIterator() as $remote) {
                if ($this->managesStripeSubscription($remote)) {
                    $remoteSubscriptions[$remote->id] = $remote;
                }
            }
        }
        foreach ($localSubscriptions as $local) {
            if (! isset($remoteSubscriptions[$local->stripe_subscription_id])) {
                $remoteSubscriptions[$local->stripe_subscription_id] = $client->subscriptions->retrieve($local->stripe_subscription_id);
            }
        }

        foreach ($remoteSubscriptions as $remote) {
            if (in_array($remote->status, ProSubscription::TERMINAL_STRIPE_STATUSES, true)) {
                continue;
            }
            $cancelled = $client->subscriptions->cancel($remote->id, ['invoice_now' => false, 'prorate' => false]);
            // Persist each success: a partial failure is safe to retry.
            $user->proSubscriptions()->where('stripe_subscription_id', $remote->id)->update([
                'status' => 'cancelled', 'stripe_status' => $cancelled->status,
                'auto_renew' => false, 'cancelled_at' => now(), 'ends_at' => now(),
            ]);
        }
    }

    public function cancelImmediately(ProSubscription $subscription): ProSubscription
    {
        if (! $subscription->stripe_subscription_id) {
            $subscription->update([
                'status' => 'cancelled',
                'auto_renew' => false,
                'cancelled_at' => now(),
                'ends_at' => now(),
            ]);
            $this->deactivateUserWhenNoOtherSubscription($subscription);

            return $subscription->fresh();
        }

        $wasLate = $subscription->isPaymentLate();
        $client = $this->client();
        $stripeSubscription = $client->subscriptions->cancel($subscription->stripe_subscription_id);

        $cancelled = $this->withoutStatusNotifications(
            fn () => $this->syncStripeSubscription($stripeSubscription, $subscription->user, $subscription->plan)
        );

        if ($wasLate) {
            // Même issue que la fin automatique du délai : la facture restée impayée
            // est annulée, et la période qu'elle couvrait n'est pas due.
            $this->voidOpenInvoice($client, $this->value($stripeSubscription, 'latest_invoice'));
            $cancelled->update(['ends_at' => now(), 'auto_renew' => false]);
            Cache::forget(self::paymentMethodCacheKey($subscription));
        }

        return $cancelled->fresh();
    }

    private function voidOpenInvoice(StripeClient $client, mixed $invoice): void
    {
        try {
            if (! is_object($invoice) && $this->resourceId($invoice) !== '') {
                $invoice = $client->invoices->retrieve($this->resourceId($invoice));
            }
            if (is_object($invoice) && $this->value($invoice, 'status') === 'open') {
                $client->invoices->voidInvoice((string) $this->value($invoice, 'id'));
            }
        } catch (\Throwable $exception) {
            // L'abonnement est bien résilié ; une facture restée ouverte se règle depuis Stripe.
            report($exception);
        }
    }

    public function suspend(ProSubscription $subscription): ProSubscription
    {
        if ($subscription->stripe_subscription_id) {
            $stripeSubscription = $this->client()->subscriptions->update(
                $subscription->stripe_subscription_id,
                ['pause_collection' => ['behavior' => 'void']]
            );

            return $this->withoutStatusNotifications(
                fn () => $this->syncStripeSubscription($stripeSubscription, $subscription->user, $subscription->plan)
            );
        }

        $subscription->update(['status' => 'pending', 'auto_renew' => false]);
        $this->deactivateUserWhenNoOtherSubscription($subscription);

        return $subscription->fresh();
    }

    public function resumeSuspended(ProSubscription $subscription): ProSubscription
    {
        if ($subscription->stripe_subscription_id) {
            $stripeSubscription = $this->client()->subscriptions->update(
                $subscription->stripe_subscription_id,
                ['pause_collection' => '']
            );

            return $this->withoutStatusNotifications(
                fn () => $this->syncStripeSubscription($stripeSubscription, $subscription->user, $subscription->plan)
            );
        }

        $subscription->update([
            'status' => 'active',
            'auto_renew' => false,
            'cancelled_at' => null,
        ]);
        $subscription->user?->update([
            'pro_subscription_plan' => $subscription->plan,
            'pro_status' => 'active',
        ]);

        return $subscription->fresh();
    }

    // =========================================
    // MOYEN DE PAIEMENT
    // =========================================

    /** Moyens de paiement proposés : la carte, et le prélèvement SEPA quand il est activé chez Stripe. */
    public function paymentMethodTypes(): array
    {
        return $this->sepaDebitAvailable() ? ['card', 'sepa_debit'] : ['card'];
    }

    /** Lu dans la configuration Stripe du compte : rien n'est promis qui ne soit réellement proposé. */
    public function sepaDebitAvailable(): bool
    {
        if (trim((string) config('services.stripe.secret')) === '') {
            return false;
        }

        return (bool) Cache::remember('stripe:sepa-debit-available', now()->addMinutes(15), function (): bool {
            try {
                foreach ($this->client()->paymentMethodConfigurations->all(['limit' => 20])->data as $configuration) {
                    if ($configuration->is_default && $configuration->active && ! $configuration->parent) {
                        return (bool) ($this->value($this->value($configuration, 'sepa_debit'), 'available') ?? false);
                    }
                }
            } catch (\Throwable $exception) {
                report($exception);
            }

            return false;
        });
    }

    /**
     * Moyen de paiement actuel de l'abonnement, tel que Stripe le connaît.
     *
     * @return array{type: string, label: string}|null
     */
    public function paymentMethodSummary(ProSubscription $subscription): ?array
    {
        if (! $subscription->hasOpenBilling() || trim((string) config('services.stripe.secret')) === '') {
            return null;
        }

        $summary = Cache::remember(self::paymentMethodCacheKey($subscription), now()->addMinutes(15), function () use ($subscription): array {
            try {
                $remote = $this->client()->subscriptions->retrieve(
                    $subscription->stripe_subscription_id,
                    ['expand' => ['default_payment_method']]
                );

                return $this->describePaymentMethod($this->value($remote, 'default_payment_method'));
            } catch (\Throwable $exception) {
                report($exception);

                return [];
            }
        });

        return $summary === [] ? null : $summary;
    }

    private static function paymentMethodCacheKey(ProSubscription $subscription): string
    {
        return 'billing:payment-method:'.$subscription->getKey();
    }

    private function describePaymentMethod(mixed $method): array
    {
        if (! is_object($method)) {
            return [];
        }

        $type = (string) $this->value($method, 'type');
        if ($type === 'card') {
            $card = $this->value($method, 'card');
            $brand = ucfirst((string) $this->value($card, 'brand'));
            $expires = sprintf('%02d/%d', (int) $this->value($card, 'exp_month'), (int) $this->value($card, 'exp_year'));

            return ['type' => 'card', 'label' => trim('Carte '.$brand).' se terminant par '.$this->value($card, 'last4').', valable jusqu’en '.$expires];
        }
        if ($type === 'sepa_debit') {
            return ['type' => 'sepa_debit', 'label' => 'Prélèvement SEPA sur le compte se terminant par '.$this->value($this->value($method, 'sepa_debit'), 'last4')];
        }

        return ['type' => $type, 'label' => 'Moyen de paiement enregistré chez Stripe'];
    }

    /** Page Stripe qui enregistre un nouveau moyen de paiement. Rien n'est débité à cette étape. */
    public function createPaymentMethodSession(ProSubscription $subscription, string $successUrl, string $cancelUrl): StripeCheckoutSession
    {
        $this->requireStripeSubscription($subscription);
        $user = $subscription->user;
        if (! $subscription->hasOpenBilling() || ! $user?->stripe_id) {
            throw new DomainException('Cet abonnement n’est plus prélevé : il n’y a pas de moyen de paiement à modifier.');
        }

        $metadata = [
            'type' => self::PAYMENT_METHOD_CHECKOUT_TYPE,
            'user_id' => (string) $user->id,
            'pro_subscription_id' => (string) $subscription->id,
            'stripe_subscription_id' => (string) $subscription->stripe_subscription_id,
        ];

        return $this->client()->checkout->sessions->create([
            'mode' => 'setup',
            'customer' => $user->stripe_id,
            'client_reference_id' => (string) $user->id,
            'payment_method_types' => $this->paymentMethodTypes(),
            'success_url' => $this->publicReturnUrl($successUrl),
            'cancel_url' => $this->publicReturnUrl($cancelUrl),
            'metadata' => $metadata,
            'setup_intent_data' => ['metadata' => $metadata],
        ]);
    }

    public function completePaymentMethodCheckout(string $sessionId, User $expectedUser): array
    {
        $session = $this->client()->checkout->sessions->retrieve($sessionId, ['expand' => ['setup_intent']]);

        return $this->completePaymentMethodSession($session, $expectedUser);
    }

    /**
     * Le nouveau moyen de paiement devient celui de l'abonnement ; si un paiement
     * était en retard, il est présenté de nouveau tout de suite.
     *
     * Résultat : saved (enregistré), paid (retard réglé), processing (prélèvement
     * en cours), refused (nouveau refus) ou already (session déjà traitée).
     *
     * @return array{subscription: ProSubscription, outcome: string}
     */
    public function completePaymentMethodSession(object $session, ?User $expectedUser = null): array
    {
        $metadata = $this->metadata($session);
        if (($metadata['type'] ?? null) !== self::PAYMENT_METHOD_CHECKOUT_TYPE || $this->value($session, 'mode') !== 'setup') {
            throw new DomainException('Cette session Stripe ne concerne pas un moyen de paiement.');
        }

        $subscription = ProSubscription::find((int) ($metadata['pro_subscription_id'] ?? 0));
        $user = $subscription?->user;
        if (! $subscription || ! $user
            || (string) $user->id !== (string) ($metadata['user_id'] ?? '')
            || ($expectedUser && ! $expectedUser->is($user))
            || ! filled($subscription->stripe_subscription_id)
            || $subscription->stripe_subscription_id !== ($metadata['stripe_subscription_id'] ?? null)
            || ! filled($user->stripe_id)
            || $this->resourceId($this->value($session, 'customer')) !== (string) $user->stripe_id) {
            throw new DomainException('Cette session Stripe ne correspond pas à votre abonnement.');
        }
        if ($this->value($session, 'status') !== 'complete') {
            throw new DomainException('Le nouveau moyen de paiement n’a pas été enregistré.');
        }

        // Le retour du membre et l'événement Stripe arrivent tous les deux : un seul traite la session.
        $sessionId = (string) $this->value($session, 'id');
        $claimed = ProSubscription::whereKey($subscription->getKey())
            ->where(function ($query) use ($sessionId) {
                $query->whereNull('payment_method_session_id')->orWhere('payment_method_session_id', '!=', $sessionId);
            })
            ->update(['payment_method_session_id' => $sessionId]);
        if (! $claimed) {
            return ['subscription' => $subscription->fresh(), 'outcome' => 'already'];
        }

        try {
            $client = $this->client();
            $setupIntent = $this->value($session, 'setup_intent');
            if (! is_object($setupIntent)) {
                $setupIntent = $client->setupIntents->retrieve((string) $setupIntent);
            }
            $paymentMethodId = $this->resourceId($this->value($setupIntent, 'payment_method'));
            if ($this->value($setupIntent, 'status') !== 'succeeded' || $paymentMethodId === '') {
                throw new DomainException('Stripe n’a pas confirmé ce moyen de paiement.');
            }

            $client->customers->update($user->stripe_id, [
                'invoice_settings' => ['default_payment_method' => $paymentMethodId],
            ]);
            $remote = $client->subscriptions->update($subscription->stripe_subscription_id, [
                'default_payment_method' => $paymentMethodId,
                'expand' => ['latest_invoice'],
            ]);
            Cache::forget(self::paymentMethodCacheKey($subscription));

            $outcome = 'saved';
            if (in_array($remote->status, self::LATE_STRIPE_STATUSES, true)) {
                $outcome = $this->retryLatestInvoice($client, $remote, $paymentMethodId);
                if ($outcome === 'paid') {
                    $remote = $client->subscriptions->retrieve($subscription->stripe_subscription_id);
                }
            }

            return [
                'subscription' => $this->syncStripeSubscription($remote, $user, $subscription->plan),
                'outcome' => $outcome,
            ];
        } catch (\Throwable $exception) {
            // La session reste à traiter : le prochain passage (retour ou événement Stripe) recommencera.
            ProSubscription::whereKey($subscription->getKey())
                ->where('payment_method_session_id', $sessionId)
                ->update(['payment_method_session_id' => null]);

            throw $exception;
        }
    }

    /**
     * Relance demandée par un administrateur : la facture en retard est présentée de
     * nouveau au moyen de paiement déjà enregistré par le membre.
     *
     * Résultat : paid (réglé), processing (prélèvement en cours), refused (nouveau refus),
     * saved (rien n'a pu être présenté) ou settled (Stripe ne voit plus de retard).
     */
    public function retryLatePayment(ProSubscription $subscription): string
    {
        $this->requireStripeSubscription($subscription);
        $client = $this->client();
        $remote = $client->subscriptions->retrieve($subscription->stripe_subscription_id, ['expand' => ['latest_invoice']]);

        $outcome = 'settled';
        if (in_array($remote->status, self::LATE_STRIPE_STATUSES, true)) {
            $outcome = $this->retryLatestInvoice($client, $remote);
            if ($outcome === 'paid') {
                $remote = $client->subscriptions->retrieve($subscription->stripe_subscription_id);
            }
        }
        $this->syncStripeSubscription($remote, $subscription->user, $subscription->plan);

        return $outcome;
    }

    /**
     * Délai supplémentaire accordé par un administrateur, compté depuis la fin du
     * délai en cours (ou depuis maintenant s'il est déjà écoulé).
     */
    public function extendPaymentGrace(ProSubscription $subscription, int $days): ProSubscription
    {
        if (! $subscription->isPaymentLate()) {
            throw new DomainException('Cet abonnement n’a aucun paiement en retard : il n’y a pas de délai à prolonger.');
        }

        $days = max(1, min(self::MAX_GRACE_EXTENSION_DAYS, $days));
        $from = $subscription->grace_ends_at?->isFuture() ? $subscription->grace_ends_at->copy() : now();
        $subscription->update(['grace_ends_at' => $from->addDays($days)]);

        if ($subscription->status !== 'active' && $subscription->hasOpenBilling()) {
            // Le délai était écoulé : l'état est relu chez Stripe, qui rouvre les avantages
            // si le paiement est toujours attendu.
            $this->withoutStatusNotifications(fn () => $this->syncLatest($subscription->stripe_subscription_id));
        }

        return $subscription->fresh();
    }

    private function retryLatestInvoice(StripeClient $client, object $remote, ?string $paymentMethodId = null): string
    {
        $invoice = $this->value($remote, 'latest_invoice');
        if (! is_object($invoice) && $this->resourceId($invoice) !== '') {
            $invoice = $client->invoices->retrieve($this->resourceId($invoice));
        }
        if (! is_object($invoice) || $this->value($invoice, 'status') !== 'open') {
            return 'saved';
        }

        try {
            $paid = $client->invoices->pay(
                (string) $this->value($invoice, 'id'),
                $paymentMethodId !== null ? ['payment_method' => $paymentMethodId] : []
            );
        } catch (\Stripe\Exception\CardException) {
            return 'refused';
        } catch (\Stripe\Exception\ApiErrorException $exception) {
            // Par exemple un paiement déjà en cours sur cette facture : Stripe le mènera à son terme.
            report($exception);

            return 'saved';
        }

        return $this->value($paid, 'status') === 'paid' ? 'paid' : 'processing';
    }

    // =========================================
    // DÉLAI DE RÉGULARISATION
    // =========================================

    /** Rappel dû pendant le délai. Un seul message par passage, même si plusieurs échéances sont dépassées. */
    public function remindPaymentDue(ProSubscription $subscription): bool
    {
        if (! $subscription->inPaymentGrace()) {
            return false;
        }

        $sent = (int) $subscription->payment_reminders_sent;
        $due = count(array_filter(
            ProSubscription::PAYMENT_REMINDER_DAYS,
            fn (int $days): bool => $subscription->payment_failed_at->copy()->addDays($days)->isPast()
        ));
        if ($due <= $sent) {
            return false;
        }

        $claimed = ProSubscription::whereKey($subscription->getKey())
            ->where('payment_reminders_sent', $sent)
            ->update(['payment_reminders_sent' => $due]);
        if (! $claimed) {
            return false;
        }

        \App\Notifications\AccountStatusNotification::dispatch($subscription->user, 'payment_reminder', [
            'until' => $subscription->grace_ends_at->format('d/m/Y'),
            'days_left' => $subscription->graceDaysLeft(),
        ]);

        return true;
    }

    /**
     * Abonnement resté impayé : résiliation chez Stripe, facture en attente annulée,
     * retour au compte gratuit. Rien n'est réclamé au membre après coup.
     *
     * Retourne null quand un paiement est encore en cours de traitement (un
     * prélèvement met plusieurs jours) : son résultat décidera.
     */
    public function endForNonPayment(ProSubscription $subscription, bool $notify = true): ?ProSubscription
    {
        $this->requireStripeSubscription($subscription);
        $client = $this->client();
        $remote = $client->subscriptions->retrieve($subscription->stripe_subscription_id, ['expand' => ['latest_invoice']]);

        if (! in_array($remote->status, self::LATE_STRIPE_STATUSES, true)) {
            // Régularisé ou déjà terminé entre-temps : on enregistre l'état réel.
            return $this->syncStripeSubscription($remote, $subscription->user, $subscription->plan);
        }

        $invoice = $this->value($remote, 'latest_invoice');
        $invoiceId = $this->resourceId($invoice);
        $invoiceOpen = is_object($invoice) && $this->value($invoice, 'status') === 'open';
        if ($invoiceOpen && $this->invoicePaymentProcessing($client, $invoiceId)) {
            return null;
        }

        $cancelled = $client->subscriptions->cancel($subscription->stripe_subscription_id, ['invoice_now' => false, 'prorate' => false]);
        if ($invoiceOpen) {
            $this->voidOpenInvoice($client, $invoice);
        }

        $ended = $this->withoutStatusNotifications(
            fn () => $this->syncStripeSubscription($cancelled, $subscription->user, $subscription->plan)
        );
        // La période annoncée par Stripe n'a pas été payée : l'accès s'arrête maintenant.
        $ended->update(['ends_at' => now(), 'auto_renew' => false]);
        Cache::forget(self::paymentMethodCacheKey($subscription));

        if ($notify) {
            \App\Notifications\AccountStatusNotification::dispatch($subscription->user, 'premium_unpaid_ended');
        }

        return $ended->fresh();
    }

    private function invoicePaymentProcessing(StripeClient $client, string $invoiceId): bool
    {
        $payments = $client->invoicePayments->all([
            'invoice' => $invoiceId,
            'limit' => 20,
            'expand' => ['data.payment.payment_intent'],
        ]);

        foreach ($payments->data as $payment) {
            $intent = $this->value($this->value($payment, 'payment'), 'payment_intent');
            if (is_object($intent) && $this->value($intent, 'status') === 'processing') {
                return true;
            }
        }

        return false;
    }

    private function client(): StripeClient
    {
        $secret = trim((string) config('services.stripe.secret'));
        if ($secret === '') {
            throw new DomainException('La clé secrète Stripe n’est pas configurée.');
        }

        return new StripeClient($secret);
    }

    private function openProviderCheckouts(StripeClient $client, string $customerId, User $user): iterable
    {
        $sessions = $client->checkout->sessions->all(['customer' => $customerId, 'status' => 'open', 'limit' => 100]);
        foreach ($sessions->autoPagingIterator() as $session) {
            $metadata = $this->metadata($session);
            if ($session->mode === 'subscription' && in_array($metadata['type'] ?? null, self::CHECKOUT_TYPES, true)
                && ($metadata['user_id'] ?? null) === (string) $user->id) {
                yield $session;
            }
        }
    }

    private function ensureStripeCustomer(StripeClient $client, User $user): string
    {
        if ($user->stripe_id) {
            return $user->stripe_id;
        }

        $customer = $client->customers->create([
            'email' => $user->email,
            'name' => $user->company_name ?: $user->name,
            'metadata' => ['user_id' => (string) $user->id],
        ], ['idempotency_key' => 'prokejem-provider-customer-'.$user->id]);

        $user->update(['stripe_id' => $customer->id]);

        return $customer->id;
    }

    private function publicReturnUrl(string $url): string
    {
        $publicUrl = rtrim((string) PlatformFeatures::proSubscriptionReadiness()['public_url'], '/');
        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $publicUrl.$path.$query;
    }

    private function localStatus(string $stripeStatus, bool $collectionPaused): string
    {
        if ($collectionPaused) {
            return 'pending';
        }

        return match ($stripeStatus) {
            'active', 'trialing' => 'active',
            'canceled' => 'cancelled',
            'incomplete_expired', 'unpaid' => 'expired',
            default => 'pending',
        };
    }

    private function requireStripeSubscription(ProSubscription $subscription): void
    {
        if (! $subscription->stripe_subscription_id) {
            throw new DomainException('Cet accès a été accordé manuellement et n’est pas renouvelé par Stripe.');
        }
    }

    private function deactivateUserWhenNoOtherSubscription(ProSubscription $subscription): void
    {
        $user = $subscription->user;
        if ($user && ! $user->proSubscriptions()->currentlyActive()->whereKeyNot($subscription->id)->exists()) {
            $user->update([
                'pro_subscription_plan' => null,
                'pro_status' => 'inactive',
            ]);
        }
    }

    private function paymentIntentId(object $session, object $subscription): ?string
    {
        $sessionIntent = $this->resourceId($this->value($session, 'payment_intent'));
        if ($sessionIntent !== '') {
            return $sessionIntent;
        }

        $invoice = $this->value($subscription, 'latest_invoice');

        return ($intent = $this->resourceId($this->value($invoice, 'payment_intent'))) !== '' ? $intent : null;
    }

    private function invoiceSubscriptionId(object $invoice): string
    {
        $subscriptionId = $this->resourceId($this->value($invoice, 'subscription'));
        if ($subscriptionId !== '') {
            return $subscriptionId;
        }

        $parent = $this->value($invoice, 'parent');
        $subscriptionDetails = $this->value($parent, 'subscription_details');

        return $this->resourceId($this->value($subscriptionDetails, 'subscription'));
    }

    private function metadata(object $resource): array
    {
        $metadata = $this->value($resource, 'metadata');
        if (is_array($metadata)) {
            return $metadata;
        }

        if (is_object($metadata) && method_exists($metadata, 'toArray')) {
            return $metadata->toArray();
        }

        return is_object($metadata) ? get_object_vars($metadata) : [];
    }

    private function resourceId(mixed $resource): string
    {
        if (is_string($resource)) {
            return $resource;
        }

        return is_object($resource) ? (string) $this->value($resource, 'id') : '';
    }

    private function value(mixed $resource, string $key): mixed
    {
        if (is_array($resource)) {
            return $resource[$key] ?? null;
        }

        return is_object($resource) ? ($resource->{$key} ?? null) : null;
    }

    private function timestamp(mixed $timestamp): ?Carbon
    {
        return is_numeric($timestamp) ? Carbon::createFromTimestampUTC((int) $timestamp) : null;
    }
}

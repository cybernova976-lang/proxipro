<?php

namespace App\Services;

use App\Models\ProSubscription;
use App\Models\Transaction;
use App\Models\User;
use App\Support\PlatformFeatures;
use App\Support\ProviderSubscriptionPlans;
use Carbon\Carbon;
use DomainException;
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

        if ($user->hasActiveProSubscription()) {
            throw new DomainException('Vous disposez déjà d’un abonnement Pro actif.');
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
        ];

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

        if (! in_array($type, self::CHECKOUT_TYPES, true) || $userId <= 0) {
            throw new DomainException('Cette session Stripe ne correspond pas à un abonnement Pro.');
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

        return $this->syncStripeSubscription($subscriptionObject, $user, (string) ($metadata['plan'] ?? ''), $attributes);
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
        $localStatus = $this->localStatus($stripeStatus, filled($pauseCollection));
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

        return $subscription->fresh();
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
            'payment_failed_at' => null,
        ]);

        $invoiceId = (string) $this->value($invoice, 'id');
        if ($invoiceId !== '') {
            Transaction::firstOrCreate(
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
                        'billing_reason' => $this->value($invoice, 'billing_reason'),
                    ],
                ]
            );
        }

        return $subscription;
    }

    public function markInvoiceFailed(object $invoice): ?ProSubscription
    {
        $subscriptionId = $this->invoiceSubscriptionId($invoice);
        if ($subscriptionId === '') {
            return null;
        }

        $subscription = ProSubscription::where('stripe_subscription_id', $subscriptionId)->first();
        if (! $subscription) {
            $stripeSubscription = $this->client()->subscriptions->retrieve($subscriptionId);
            if (! $this->managesStripeSubscription($stripeSubscription)) {
                return null;
            }

            $subscription = $this->syncStripeSubscription($stripeSubscription);
        }

        $subscription->update([
            'status' => 'pending',
            'stripe_status' => 'past_due',
            'payment_failed_at' => now(),
        ]);
        $subscription->user?->update(['pro_status' => 'inactive']);

        return $subscription->fresh();
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

        $stripeSubscription = $this->client()->subscriptions->cancel($subscription->stripe_subscription_id);

        return $this->syncStripeSubscription($stripeSubscription, $subscription->user, $subscription->plan);
    }

    public function suspend(ProSubscription $subscription): ProSubscription
    {
        if ($subscription->stripe_subscription_id) {
            $stripeSubscription = $this->client()->subscriptions->update(
                $subscription->stripe_subscription_id,
                ['pause_collection' => ['behavior' => 'void']]
            );

            return $this->syncStripeSubscription($stripeSubscription, $subscription->user, $subscription->plan);
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

            return $this->syncStripeSubscription($stripeSubscription, $subscription->user, $subscription->plan);
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

    private function client(): StripeClient
    {
        $secret = trim((string) config('services.stripe.secret'));
        if ($secret === '') {
            throw new DomainException('La clé secrète Stripe n’est pas configurée.');
        }

        return new StripeClient($secret);
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
        ]);

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

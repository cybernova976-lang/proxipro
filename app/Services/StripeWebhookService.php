<?php

namespace App\Services;

use App\Models\StripeWebhookEvent;
use App\Support\StripeSessionData;
use DomainException;
use Illuminate\Support\Str;
use Throwable;

class StripeWebhookService
{
    public function __construct(
        protected StripeCheckoutFulfillmentService $checkoutFulfillment,
        protected ProviderSubscriptionService $subscriptions,
    ) {}

    public function process(object $event, bool $force = false): string
    {
        $eventId = (string) ($event->id ?? '');
        $eventType = (string) ($event->type ?? '');
        $object = $event->data->object ?? null;

        if ($eventId === '' || $eventType === '' || ! $object) {
            throw new DomainException('Événement Stripe incomplet.');
        }

        $record = StripeWebhookEvent::firstOrCreate(
            ['event_id' => $eventId],
            [
                'event_type' => $eventType,
                'object_id' => (string) StripeSessionData::value($object, 'id', ''),
                'checkout_session_id' => $eventType === 'checkout.session.completed'
                    ? (string) StripeSessionData::value($object, 'id', '')
                    : null,
                'status' => StripeWebhookEvent::STATUS_PROCESSING,
            ]
        );

        if (! $force && in_array($record->status, [
            StripeWebhookEvent::STATUS_PROCESSED,
            StripeWebhookEvent::STATUS_IGNORED,
        ], true)) {
            return 'duplicate';
        }
        if (! $force
            && $record->status === StripeWebhookEvent::STATUS_PROCESSING
            && ! $record->wasRecentlyCreated
            && $record->updated_at?->isAfter(now()->subMinutes(5))) {
            return 'processing';
        }

        $record->forceFill([
            'event_type' => $eventType,
            'status' => StripeWebhookEvent::STATUS_PROCESSING,
            'attempts' => $record->attempts + 1,
            'error_message' => null,
        ])->save();

        try {
            $result = $this->dispatch($eventType, $object);
            $record->forceFill([
                'status' => $result === 'ignored'
                    ? StripeWebhookEvent::STATUS_IGNORED
                    : StripeWebhookEvent::STATUS_PROCESSED,
                'processed_at' => now(),
            ])->save();

            return $result;
        } catch (Throwable $exception) {
            $record->forceFill([
                'status' => StripeWebhookEvent::STATUS_FAILED,
                'error_message' => Str::limit($exception->getMessage(), 1000, ''),
                'processed_at' => null,
            ])->save();

            throw $exception;
        }
    }

    private function dispatch(string $eventType, object|array $object): string
    {
        if ($eventType === 'checkout.session.completed') {
            return $this->checkoutFulfillment->fulfill($object);
        }

        if (in_array($eventType, [
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'customer.subscription.paused',
            'customer.subscription.resumed',
        ], true)) {
            if ($this->subscriptions->managesStripeSubscription($object)) {
                $this->subscriptions->syncStripeSubscription($object);

                return 'processed';
            }

            return 'ignored';
        }

        if ($eventType === 'invoice.paid') {
            $this->subscriptions->syncInvoicePaid($object);

            return 'processed';
        }

        if ($eventType === 'invoice.payment_failed') {
            $this->subscriptions->markInvoiceFailed($object);

            return 'processed';
        }

        return 'ignored';
    }
}

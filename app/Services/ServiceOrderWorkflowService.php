<?php

namespace App\Services;

use App\Models\ServiceOrder;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\ServiceOrderStatusNotification;
use App\Support\StripeSessionData;
use DomainException;
use Illuminate\Support\Facades\DB;

class ServiceOrderWorkflowService
{
    public function __construct(protected StripeConnectService $stripeConnectService) {}

    public function accept(ServiceOrder $serviceOrder, User $seller): ServiceOrder
    {
        abort_unless($serviceOrder->seller_id === $seller->id, 403);
        abort_unless($serviceOrder->canSellerAccept(), 422);

        $serviceOrder->forceFill([
            'status' => ServiceOrder::STATUS_AWAITING_PAYMENT,
            'payment_status' => ServiceOrder::PAYMENT_AWAITING,
            'accepted_at' => now(),
            'refused_at' => null,
            'refused_reason' => null,
        ])->save();

        $serviceOrder->loadMissing(['buyer', 'seller', 'ad']);
        $serviceOrder->buyer?->notify(new ServiceOrderStatusNotification($serviceOrder, 'accepted'));

        return $serviceOrder;
    }

    public function refuse(ServiceOrder $serviceOrder, User $seller, ?string $reason = null): ServiceOrder
    {
        abort_unless($serviceOrder->seller_id === $seller->id, 403);
        abort_unless($serviceOrder->canSellerRefuse(), 422);

        $serviceOrder->forceFill([
            'status' => ServiceOrder::STATUS_REFUSED,
            'payment_status' => ServiceOrder::PAYMENT_CANCELED,
            'refused_at' => now(),
            'refused_reason' => $reason,
        ])->save();

        $serviceOrder->loadMissing(['buyer', 'seller', 'ad']);
        $serviceOrder->buyer?->notify(new ServiceOrderStatusNotification($serviceOrder, 'refused', $reason));

        return $serviceOrder;
    }

    public function markPaidFromCheckoutSession(object|array $session): ?ServiceOrder
    {
        $metadata = StripeSessionData::metadata($session);
        $serviceOrderId = (int) ($metadata['service_order_id'] ?? 0);
        $sessionId = StripeSessionData::id($session);

        if (! $serviceOrderId) {
            return null;
        }
        if ($sessionId === '' || ! StripeSessionData::isPaid($session)) {
            throw new DomainException('Le paiement de la commande n’est pas confirmé.');
        }

        $serviceOrder = ServiceOrder::find($serviceOrderId);
        if (! $serviceOrder) {
            return null;
        }
        $expectedCurrency = strtolower((string) config('marketplace.currency', 'eur'));
        if ((int) ($metadata['buyer_id'] ?? 0) !== (int) $serviceOrder->buyer_id
            || (int) ($metadata['seller_id'] ?? 0) !== (int) $serviceOrder->seller_id
            || (string) ($metadata['order_number'] ?? '') !== (string) $serviceOrder->order_number
            || StripeSessionData::amountTotal($session) !== (int) round(((float) $serviceOrder->amount) * 100)
            || StripeSessionData::currency($session) !== $expectedCurrency) {
            throw new DomainException('Les références, le montant ou la devise de la commande sont invalides.');
        }

        $wasApplied = DB::transaction(function () use ($serviceOrderId, $session, $sessionId) {
            $serviceOrder = ServiceOrder::query()->lockForUpdate()->findOrFail($serviceOrderId);

            if (in_array($serviceOrder->payment_status, [
                ServiceOrder::PAYMENT_PAID,
                ServiceOrder::PAYMENT_RELEASED,
            ], true)) {
                if ($serviceOrder->stripe_checkout_session_id !== $sessionId) {
                    throw new DomainException('Cette commande a déjà été réglée par une autre session.');
                }

                return false;
            }
            if ($serviceOrder->stripe_checkout_session_id !== $sessionId) {
                throw new DomainException('La session Stripe ne correspond pas à la commande.');
            }
            if (! in_array($serviceOrder->payment_status, [
                ServiceOrder::PAYMENT_AWAITING,
                ServiceOrder::PAYMENT_CHECKOUT_OPEN,
            ], true)) {
                throw new DomainException('Cette commande ne peut plus être payée.');
            }

            $transaction = Transaction::firstOrCreate(
                ['stripe_session_id' => $sessionId],
                [
                    'user_id' => $serviceOrder->buyer_id,
                    'amount' => $serviceOrder->amount,
                    'type' => 'SERVICE_ORDER_PAYMENT',
                    'description' => 'Paiement sécurisé commande '.$serviceOrder->order_number,
                    'status' => 'completed',
                    'metadata' => [
                        'service_order_id' => $serviceOrder->id,
                        'seller_id' => $serviceOrder->seller_id,
                        'order_number' => $serviceOrder->order_number,
                        'payment_intent' => StripeSessionData::value($session, 'payment_intent'),
                    ],
                ]
            );
            if (! $transaction->wasRecentlyCreated) {
                throw new DomainException('Cette session Stripe est déjà rattachée à une autre opération.');
            }

            $serviceOrder->forceFill([
                'status' => ServiceOrder::STATUS_FUNDED,
                'payment_status' => ServiceOrder::PAYMENT_PAID,
                'stripe_checkout_session_id' => $sessionId,
                'stripe_payment_intent_id' => StripeSessionData::value($session, 'payment_intent'),
                'paid_at' => now(),
            ])->save();

            return true;
        });

        $serviceOrder = ServiceOrder::with(['buyer', 'seller', 'ad'])->findOrFail($serviceOrderId);
        if ($wasApplied) {
            $serviceOrder->seller?->notify(new ServiceOrderStatusNotification($serviceOrder, 'funded'));
        }

        return $serviceOrder;
    }

    public function release(ServiceOrder $serviceOrder, User $buyer): ServiceOrder
    {
        abort_unless($serviceOrder->buyer_id === $buyer->id, 403);
        abort_unless($serviceOrder->canBuyerRelease(), 422);

        DB::transaction(function () use ($serviceOrder) {
            $transferId = $this->stripeConnectService->transferToSeller($serviceOrder->loadMissing('seller'));

            $serviceOrder->forceFill([
                'status' => ServiceOrder::STATUS_COMPLETED,
                'payment_status' => ServiceOrder::PAYMENT_RELEASED,
                'released_at' => now(),
                'stripe_transfer_id' => $transferId,
            ])->save();

            Transaction::firstOrCreate(
                [
                    'user_id' => $serviceOrder->seller_id,
                    'type' => 'SERVICE_ORDER_RELEASE',
                    'description' => 'Liberation commande '.$serviceOrder->order_number,
                ],
                [
                    'amount' => $serviceOrder->seller_amount,
                    'status' => 'completed',
                    'metadata' => [
                        'service_order_id' => $serviceOrder->id,
                        'buyer_id' => $serviceOrder->buyer_id,
                        'commission_amount' => $serviceOrder->commission_amount,
                        'stripe_transfer_id' => $transferId,
                    ],
                ]
            );
        });

        $serviceOrder->loadMissing(['buyer', 'seller', 'ad']);
        $serviceOrder->seller?->notify(new ServiceOrderStatusNotification($serviceOrder, 'released'));

        return $serviceOrder;
    }

    public function dispute(ServiceOrder $serviceOrder, User $buyer, string $reason): ServiceOrder
    {
        abort_unless($serviceOrder->buyer_id === $buyer->id, 403);
        abort_unless($serviceOrder->canBuyerDispute(), 422);

        $serviceOrder->forceFill([
            'status' => ServiceOrder::STATUS_DISPUTED,
            'payment_status' => ServiceOrder::PAYMENT_DISPUTED,
            'disputed_at' => now(),
            'dispute_reason' => $reason,
        ])->save();

        $serviceOrder->loadMissing(['buyer', 'seller', 'ad']);
        $serviceOrder->seller?->notify(new ServiceOrderStatusNotification($serviceOrder, 'disputed', $reason));

        return $serviceOrder;
    }

    public function adminReleaseDispute(ServiceOrder $serviceOrder, User $admin, ?string $note = null): ServiceOrder
    {
        abort_unless($admin->isAdmin(), 403);
        abort_unless($serviceOrder->canAdminResolveDispute(), 422);

        DB::transaction(function () use ($serviceOrder, $admin, $note) {
            $transferId = $this->stripeConnectService->transferToSeller($serviceOrder->loadMissing('seller'));

            $serviceOrder->forceFill([
                'status' => ServiceOrder::STATUS_COMPLETED,
                'payment_status' => ServiceOrder::PAYMENT_RELEASED,
                'released_at' => now(),
                'admin_resolution' => 'released',
                'admin_resolution_note' => $note,
                'admin_resolved_at' => now(),
                'admin_resolved_by' => $admin->id,
                'stripe_transfer_id' => $transferId,
            ])->save();

            Transaction::firstOrCreate(
                [
                    'user_id' => $serviceOrder->seller_id,
                    'type' => 'SERVICE_ORDER_RELEASE',
                    'description' => 'Liberation commande '.$serviceOrder->order_number,
                ],
                [
                    'amount' => $serviceOrder->seller_amount,
                    'status' => 'completed',
                    'metadata' => [
                        'service_order_id' => $serviceOrder->id,
                        'buyer_id' => $serviceOrder->buyer_id,
                        'commission_amount' => $serviceOrder->commission_amount,
                        'admin_resolution' => 'released',
                        'stripe_transfer_id' => $transferId,
                    ],
                ]
            );
        });

        $serviceOrder->loadMissing(['buyer', 'seller', 'ad']);
        $serviceOrder->buyer?->notify(new ServiceOrderStatusNotification($serviceOrder, 'released', $note));
        $serviceOrder->seller?->notify(new ServiceOrderStatusNotification($serviceOrder, 'released', $note));

        return $serviceOrder;
    }

    public function adminRefundDispute(ServiceOrder $serviceOrder, User $admin, string $note): ServiceOrder
    {
        abort_unless($admin->isAdmin(), 403);
        abort_unless($serviceOrder->canAdminResolveDispute(), 422);

        DB::transaction(function () use ($serviceOrder, $admin, $note) {
            $refundId = $this->stripeConnectService->refundOrder($serviceOrder);

            $serviceOrder->forceFill([
                'status' => ServiceOrder::STATUS_REFUNDED,
                'payment_status' => ServiceOrder::PAYMENT_REFUNDED,
                'refunded_at' => now(),
                'admin_resolution' => 'refunded',
                'admin_resolution_note' => $note,
                'admin_resolved_at' => now(),
                'admin_resolved_by' => $admin->id,
                'stripe_refund_id' => $refundId,
            ])->save();

            Transaction::create([
                'user_id' => $serviceOrder->buyer_id,
                'amount' => $serviceOrder->amount,
                'type' => 'SERVICE_ORDER_REFUND',
                'description' => 'Remboursement commande '.$serviceOrder->order_number,
                'status' => 'completed',
                'metadata' => [
                    'service_order_id' => $serviceOrder->id,
                    'seller_id' => $serviceOrder->seller_id,
                    'admin_resolution' => 'refunded',
                    'stripe_refund_id' => $refundId,
                ],
            ]);
        });

        $serviceOrder->loadMissing(['buyer', 'seller', 'ad']);
        $serviceOrder->buyer?->notify(new ServiceOrderStatusNotification($serviceOrder, 'refunded', $note));
        $serviceOrder->seller?->notify(new ServiceOrderStatusNotification($serviceOrder, 'refunded', $note));

        return $serviceOrder;
    }
}

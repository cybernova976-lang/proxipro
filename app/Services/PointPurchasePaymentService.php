<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Support\PointPackCatalog;
use App\Support\StripeSessionData;
use DomainException;
use Illuminate\Support\Facades\DB;

class PointPurchasePaymentService
{
    public function __construct(protected ReferralService $referralService) {}

    /**
     * @return array{status: string, user: User, product: array<string, mixed>}
     */
    public function fulfill(object|array $session, ?int $expectedUserId = null): array
    {
        $sessionId = StripeSessionData::id($session);
        $metadata = StripeSessionData::metadata($session);
        $userId = (int) ($metadata['user_id'] ?? 0);
        $productKey = (string) ($metadata['product_key'] ?? '');
        $product = PointPackCatalog::find($productKey);

        if ($sessionId === '' || ! StripeSessionData::isPaid($session)) {
            throw new DomainException('Le paiement Stripe n’est pas confirmé.');
        }
        if ($userId < 1 || ($expectedUserId !== null && $expectedUserId !== $userId)) {
            throw new DomainException('Le bénéficiaire du paiement est invalide.');
        }
        if (! $product) {
            throw new DomainException('Le pack de points est invalide.');
        }
        if (StripeSessionData::amountTotal($session) !== (int) $product['price_cents']
            || StripeSessionData::currency($session) !== 'eur') {
            throw new DomainException('Le montant ou la devise du paiement est invalide.');
        }

        $wasCreated = DB::transaction(function () use ($session, $sessionId, $userId, $product, $productKey) {
            $user = User::query()->lockForUpdate()->find($userId);
            if (! $user) {
                throw new DomainException('Le compte bénéficiaire est introuvable.');
            }

            $transaction = Transaction::firstOrCreate(
                ['stripe_session_id' => $sessionId],
                [
                    'user_id' => $user->id,
                    'amount' => $product['price'],
                    'type' => 'POINTS',
                    'description' => 'Achat de '.$product['points'].' points',
                    'status' => 'completed',
                    'metadata' => [
                        'product_key' => $productKey,
                        'payment_intent' => StripeSessionData::value($session, 'payment_intent'),
                    ],
                ]
            );

            if (! $transaction->wasRecentlyCreated) {
                return false;
            }

            $user->addPoints(
                (int) $product['points'],
                'purchase',
                'Achat de '.$product['points'].' points',
                'stripe'
            );
            $this->referralService->grantFirstPurchaseRewards($user->fresh(), $transaction);

            return true;
        });

        return [
            'status' => $wasCreated ? 'processed' : 'duplicate',
            'user' => User::findOrFail($userId),
            'product' => $product,
        ];
    }
}

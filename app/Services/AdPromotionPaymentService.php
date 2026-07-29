<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\Transaction;
use App\Models\User;
use App\Support\AdPromotionCatalog;
use App\Support\StripeSessionData;
use DomainException;
use Illuminate\Support\Facades\DB;

class AdPromotionPaymentService
{
    /**
     * @return array{status: string, ad: Ad, promotion_type: string}
     */
    public function fulfill(
        object|array $session,
        ?int $expectedUserId = null,
        ?int $expectedAdId = null,
        ?string $expectedType = null,
        ?string $expectedPackage = null,
    ): array {
        $sessionId = StripeSessionData::id($session);
        $metadata = StripeSessionData::metadata($session);
        $userId = (int) ($metadata['user_id'] ?? 0);
        $adId = (int) ($metadata['ad_id'] ?? 0);
        $packageKey = (string) ($metadata['package'] ?? '');
        $type = (string) ($metadata['type'] ?? '');
        if ($type === '' && $packageKey !== '') {
            $type = 'ad_boost'; // Compatibilité avec les sessions créées avant le webhook unifié.
        }

        if ($sessionId === '' || ! StripeSessionData::isPaid($session)) {
            throw new DomainException('Le paiement Stripe n’est pas confirmé.');
        }
        if ($userId < 1 || $adId < 1
            || ($expectedUserId !== null && $expectedUserId !== $userId)
            || ($expectedAdId !== null && $expectedAdId !== $adId)) {
            throw new DomainException('L’annonce ou le bénéficiaire du paiement est invalide.');
        }
        if ($expectedType !== null && $expectedType !== $type) {
            throw new DomainException('Le type de promotion ne correspond pas au paiement.');
        }
        if ($expectedPackage !== null && $expectedPackage !== $packageKey) {
            throw new DomainException('Le pack de boost ne correspond pas au paiement.');
        }

        $user = User::find($userId);
        if (! $user) {
            throw new DomainException('Le compte bénéficiaire est introuvable.');
        }

        $expectedAmount = $this->expectedAmountCents($type, $packageKey, $user, $metadata);
        if (StripeSessionData::amountTotal($session) !== $expectedAmount
            || StripeSessionData::currency($session) !== 'eur') {
            throw new DomainException('Le montant ou la devise de la promotion est invalide.');
        }

        $wasCreated = DB::transaction(function () use ($session, $sessionId, $userId, $adId, $type, $packageKey) {
            $ad = Ad::query()->lockForUpdate()->find($adId);
            if (! $ad || (int) $ad->user_id !== $userId) {
                throw new DomainException('Cette annonce n’appartient pas au bénéficiaire du paiement.');
            }
            if ($type === 'urgent' && $ad->service_type !== 'demande') {
                throw new DomainException('Le mode Urgent est réservé aux demandes de services.');
            }

            $transaction = Transaction::firstOrCreate(
                ['stripe_session_id' => $sessionId],
                [
                    'user_id' => $userId,
                    'amount' => StripeSessionData::amountTotal($session) / 100,
                    'type' => match ($type) {
                        'ad_boost' => 'AD_BOOST',
                        'refresh' => 'AD_REFRESH',
                        'urgent' => 'AD_URGENT',
                    },
                    'description' => 'Promotion de l’annonce #'.$ad->id,
                    'status' => 'completed',
                    'metadata' => [
                        'ad_id' => $ad->id,
                        'package' => $packageKey ?: null,
                        'payment_intent' => StripeSessionData::value($session, 'payment_intent'),
                    ],
                ]
            );

            if (! $transaction->wasRecentlyCreated) {
                return false;
            }

            if ($type === 'ad_boost') {
                $package = AdPromotionCatalog::boosts()[$packageKey];
                $startsAt = $ad->isCurrentlyBoosted() && $ad->boost_end
                    ? $ad->boost_end->copy()
                    : now();
                $ad->forceFill([
                    'is_boosted' => true,
                    'boost_end' => $startsAt->addDays($package['duration_days']),
                    'boost_type' => $packageKey,
                ])->save();
            } elseif ($type === 'refresh') {
                $ad->forceFill([
                    'updated_at' => now(),
                    'is_boosted' => false,
                    'boost_end' => null,
                    'boost_type' => null,
                ])->save();
            } else {
                $config = AdPromotionCatalog::urgent();
                $ad->forceFill([
                    'is_urgent' => true,
                    'urgent_until' => now()->addDays($config['duration_days']),
                    'sidebar_priority' => 1,
                ])->save();
            }

            return true;
        });

        return [
            'status' => $wasCreated ? 'processed' : 'duplicate',
            'ad' => Ad::findOrFail($adId),
            'promotion_type' => $type,
        ];
    }

    /** @param array<string, mixed> $metadata */
    private function expectedAmountCents(string $type, string $packageKey, User $user, array $metadata): int
    {
        $basePrice = match ($type) {
            'ad_boost' => AdPromotionCatalog::boosts()[$packageKey]['price_euros'] ?? null,
            'refresh' => AdPromotionCatalog::refresh()['price_euros'],
            'urgent' => AdPromotionCatalog::urgent()['price_euros'],
            default => null,
        };
        if ($basePrice === null) {
            throw new DomainException('Le produit de promotion est invalide.');
        }

        $fullPrice = (int) round($basePrice * 100);
        $proPrice = AdPromotionCatalog::discountedCents((float) $basePrice, true);
        $metadataAmount = (int) ($metadata['expected_amount_cents'] ?? 0);

        if ($metadataAmount > 0) {
            if (! in_array($metadataAmount, [$fullPrice, $proPrice], true)) {
                throw new DomainException('Le tarif enregistré pour cette promotion est invalide.');
            }

            return $metadataAmount;
        }

        return $user->hasActiveProSubscription() ? $proPrice : $fullPrice;
    }
}

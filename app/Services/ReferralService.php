<?php

namespace App\Services;

use App\Models\ReferralReward;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\ReferralRewardNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralService
{
    public const REFEREE_REWARD_POINTS = 20;
    public const REFERRER_REWARD_POINTS = 50;

    public function normalizeCode(?string $code): ?string
    {
        if (!$code) {
            return null;
        }

        $normalized = strtoupper(trim($code));

        return $normalized !== '' ? $normalized : null;
    }

    public function generateUniqueCode(string $seed = ''): string
    {
        $prefix = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $seed) ?: 'PROXI', 0, 4));
        $prefix = str_pad($prefix, 4, 'X');

        do {
            $candidate = $prefix . Str::upper(Str::random(6));
        } while (User::where('referral_code', $candidate)->exists());

        return $candidate;
    }

    public function findReferrerByCode(?string $code): ?User
    {
        $normalized = $this->normalizeCode($code);

        if (!$normalized) {
            return null;
        }

        return User::where('referral_code', $normalized)->first();
    }

    public function grantFirstPurchaseRewards(User $referee, Transaction $transaction): bool
    {
        if (!$referee->referred_by_user_id || $referee->referral_bonus_granted_at) {
            return false;
        }

        $referrer = $referee->referrer;
        if (!$referrer || $referrer->id === $referee->id) {
            return false;
        }

        $notificationPayload = null;

        $granted = DB::transaction(function () use ($referee, $referrer, $transaction, &$notificationPayload) {
            $lockedReferee = User::whereKey($referee->id)->lockForUpdate()->first();
            if (!$lockedReferee || $lockedReferee->referral_bonus_granted_at) {
                return false;
            }

            $existingReward = ReferralReward::where('referee_user_id', $lockedReferee->id)
                ->where('reward_type', 'first_purchase_referee')
                ->first();

            if ($existingReward) {
                $lockedReferee->forceFill([
                    'referral_bonus_granted_at' => $lockedReferee->referral_bonus_granted_at ?? now(),
                    'first_qualifying_purchase_at' => $lockedReferee->first_qualifying_purchase_at ?? now(),
                ])->save();

                return false;
            }

            $grantedAt = now();

            $refereeReward = ReferralReward::create([
                'referrer_user_id' => $referrer->id,
                'referee_user_id' => $lockedReferee->id,
                'source_transaction_id' => $transaction->id,
                'reward_type' => 'first_purchase_referee',
                'points' => self::REFEREE_REWARD_POINTS,
                'granted_at' => $grantedAt,
            ]);

            $referrerReward = ReferralReward::create([
                'referrer_user_id' => $referrer->id,
                'referee_user_id' => $lockedReferee->id,
                'source_transaction_id' => $transaction->id,
                'reward_type' => 'first_purchase_referrer',
                'points' => self::REFERRER_REWARD_POINTS,
                'granted_at' => $grantedAt,
            ]);

            $lockedReferee->addPoints(
                self::REFEREE_REWARD_POINTS,
                'referral_bonus',
                'Bonus filleul après premier achat',
                'referral'
            );

            $referrer->refresh();
            $referrer->addPoints(
                self::REFERRER_REWARD_POINTS,
                'referral_bonus',
                'Bonus parrainage débloqué par ' . $lockedReferee->name,
                'referral'
            );

            $lockedReferee->forceFill([
                'referral_bonus_granted_at' => $grantedAt,
                'first_qualifying_purchase_at' => $grantedAt,
            ])->save();

            $notificationPayload = [
                'referee' => $lockedReferee->fresh(),
                'referrer' => $referrer->fresh(),
                'referee_reward' => $refereeReward,
                'referrer_reward' => $referrerReward,
            ];

            return true;
        });

        if ($granted && $notificationPayload) {
            try {
                $notificationPayload['referee']->notify(
                    new ReferralRewardNotification(
                        $notificationPayload['referee_reward'],
                        'referee',
                        $notificationPayload['referrer']->name
                    )
                );

                $notificationPayload['referrer']->notify(
                    new ReferralRewardNotification(
                        $notificationPayload['referrer_reward'],
                        'referrer',
                        $notificationPayload['referee']->name
                    )
                );
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $granted;
    }
}
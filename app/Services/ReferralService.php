<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
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
        // Rewards have been retired; preserve existing referral history.
        return false;
    }
}
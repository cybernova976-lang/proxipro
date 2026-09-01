<?php

namespace App\Services;

use App\Models\Ad;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class AdLifecycleService
{
    public const DUPLICATE_WINDOW_HOURS = 24;

    public const FIRST_RESPONSE_ATTENTION_HOURS = 2;

    public function expiresAtFor(string $serviceType): CarbonInterface
    {
        return $serviceType === 'demande'
            ? now()->addDays(30)
            : now()->addDays(90);
    }

    public function hasRecentDuplicate(
        int $userId,
        string $title,
        string $category,
        string $serviceType,
        ?int $ignoredAdId = null
    ): bool {
        $normalizedTitle = $this->normalizeTitle($title);

        return Ad::query()
            ->where('user_id', $userId)
            ->where('category', $category)
            ->where('service_type', $serviceType)
            ->where('created_at', '>=', now()->subHours(self::DUPLICATE_WINDOW_HOURS))
            ->whereIn('status', ['active', 'pending'])
            ->when($ignoredAdId, fn ($query) => $query->where('id', '!=', $ignoredAdId))
            ->pluck('title')
            ->contains(fn ($candidate) => $this->normalizeTitle((string) $candidate) === $normalizedTitle);
    }

    public function needsFirstResponseAttention(Ad $ad, ?int $proposalCount = null): bool
    {
        if ($ad->service_type !== 'demande'
            || $ad->status !== 'active'
            || ($ad->expires_at && $ad->expires_at->isPast())
            || ! $ad->created_at
            || $ad->created_at->isAfter(now()->subHours(self::FIRST_RESPONSE_ATTENTION_HOURS))) {
            return false;
        }

        $proposalCount ??= array_key_exists('service_proposals_count', $ad->getAttributes())
            ? (int) $ad->getAttribute('service_proposals_count')
            : $ad->serviceProposals()->count();

        return $proposalCount === 0;
    }

    public function republish(Ad $ad): void
    {
        $ad->forceFill([
            'status' => 'active',
            'expires_at' => $this->expiresAtFor($ad->service_type),
            'created_at' => now(),
        ])->save();
    }

    private function normalizeTitle(string $title): string
    {
        return Str::of($title)
            ->squish()
            ->lower()
            ->ascii()
            ->toString();
    }
}

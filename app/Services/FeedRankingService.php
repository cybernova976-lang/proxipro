<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Support\Collection;

class FeedRankingService
{
    /**
     * Build a useful feed without allowing paid placements or one publisher
     * to occupy the whole first screen.
     */
    public function rank(Collection $ads, ?User $viewer, int $limit = 18): Collection
    {
        if ($limit < 1 || $ads->isEmpty()) {
            return collect();
        }

        $ranked = $ads
            ->unique('id')
            ->sortByDesc(fn (Ad $ad) => $this->score($ad, $viewer))
            ->values();

        $priority = $ranked->filter(fn (Ad $ad) => $this->isPriority($ad))->values();
        $organic = $ranked->reject(fn (Ad $ad) => $this->isPriority($ad))->values();

        // A paid placement may improve visibility, but it must never erase the
        // organic marketplace. One card out of three is the maximum while
        // organic results are available.
        $priorityQuota = $organic->isEmpty()
            ? min($limit, $priority->count())
            : min($priority->count(), max(1, (int) floor($limit / 3)));

        $selectedPriority = $this->takeDiversified($priority, $priorityQuota);
        $selectedOrganic = $this->takeDiversified($organic, $limit - $selectedPriority->count());

        $result = collect();
        $priorityIndex = 0;
        $organicIndex = 0;

        // The first priority card keeps the purchased benefit. Subsequent paid
        // cards are spaced out so the first screen remains representative.
        while ($result->count() < $limit
            && ($priorityIndex < $selectedPriority->count() || $organicIndex < $selectedOrganic->count())) {
            if ($priorityIndex < $selectedPriority->count()) {
                $result->push($selectedPriority[$priorityIndex++]);
            }

            for ($i = 0; $i < 2 && $organicIndex < $selectedOrganic->count() && $result->count() < $limit; $i++) {
                $result->push($selectedOrganic[$organicIndex++]);
            }
        }

        if ($result->count() < $limit) {
            $remaining = $ranked->whereNotIn('id', $result->pluck('id'))->take($limit - $result->count());
            $result = $result->concat($remaining);
        }

        return $result->take($limit)->values();
    }

    private function takeDiversified(Collection $ads, int $limit): Collection
    {
        $selected = collect();
        $authorCounts = [];

        foreach ($ads as $ad) {
            $authorId = (int) $ad->user_id;
            if (($authorCounts[$authorId] ?? 0) >= 2) {
                continue;
            }

            $selected->push($ad);
            $authorCounts[$authorId] = ($authorCounts[$authorId] ?? 0) + 1;

            if ($selected->count() >= $limit) {
                return $selected;
            }
        }

        // A small marketplace may not have enough distinct publishers yet.
        // Fill the remaining slots instead of displaying an artificially empty feed.
        if ($selected->count() < $limit) {
            $selected = $selected->concat(
                $ads->whereNotIn('id', $selected->pluck('id'))->take($limit - $selected->count())
            );
        }

        return $selected->values();
    }

    private function score(Ad $ad, ?User $viewer): int
    {
        $score = 0;

        if ($this->isUrgent($ad)) {
            $score += 180;
        } elseif ($this->isBoosted($ad)) {
            $score += 140;
        }

        if ($ad->is_pinned && (! $ad->pinned_until || $ad->pinned_until->isFuture())) {
            $score += 30;
        }

        if ($ad->user?->is_verified || $ad->user?->identity_verified) {
            $score += 20;
        }

        $interests = $this->viewerCategories($viewer);
        if ($interests !== [] && in_array($this->normalize($ad->category), $interests, true)) {
            $score += 45;
        }

        if ($viewer) {
            $preferredType = ($viewer->user_type === 'professionnel' || $viewer->is_service_provider)
                ? 'demande'
                : 'offre';
            if ($ad->service_type === $preferredType) {
                $score += 12;
            }
        }

        if ($ad->created_at?->isAfter(now()->subDay())) {
            $score += 24;
        } elseif ($ad->created_at?->isAfter(now()->subDays(7))) {
            $score += 12;
        } elseif ($ad->created_at?->isAfter(now()->subDays(30))) {
            $score += 5;
        }

        // Stable daily rotation gives equally-ranked ads a chance to move without
        // making the page jump at every refresh.
        $rotationKey = ($viewer?->id ?? 'guest').'|'.$ad->id.'|'.now()->toDateString();
        $score += hexdec(substr(hash('sha256', $rotationKey), 0, 2)) % 10;

        return $score;
    }

    private function viewerCategories(?User $viewer): array
    {
        if (! $viewer) {
            return [];
        }

        return collect([
            ...((array) ($viewer->preferred_categories ?? [])),
            ...((array) ($viewer->service_subcategories ?? [])),
            ...((array) ($viewer->pro_service_categories ?? [])),
            $viewer->service_category,
        ])->map(fn ($category) => $this->normalize($category))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalize(?string $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));

        return $value !== '' ? $value : null;
    }

    private function isPriority(Ad $ad): bool
    {
        return $this->isUrgent($ad) || $this->isBoosted($ad);
    }

    private function isUrgent(Ad $ad): bool
    {
        return (bool) $ad->is_urgent && (! $ad->urgent_until || $ad->urgent_until->isFuture());
    }

    private function isBoosted(Ad $ad): bool
    {
        return (bool) $ad->is_boosted && $ad->boost_end?->isFuture();
    }
}

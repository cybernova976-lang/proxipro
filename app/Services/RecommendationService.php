<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Support\Collection;

class RecommendationService
{
    public function getFeedRecommendations(?User $user, array $context = [], int $limit = 6): Collection
    {
        if (!$user) {
            return collect();
        }

        $interestCategories = $this->extractInterestCategories($user);
        $savedAdIds = $user->savedAds()->pluck('ads.id')->all();

        $query = Ad::query()
            ->where('status', 'active')
            ->with('user')
            ->where('user_id', '!=', $user->id)
            ->whereNotIn('id', $savedAdIds)
            ->where(function ($query) use ($user) {
                $query->where('visibility', 'public');

                if ($user->user_type === 'professionnel' || $user->is_service_provider) {
                    $query->orWhere('visibility', 'pro_targeted');
                }
            })
            ->where(function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('is_boosted', true)
                        ->where('boost_end', '>', now());
                })->orWhereHas('user', function ($subQuery) {
                    $subQuery->whereNotNull('plan')
                        ->where('plan', '!=', '')
                        ->where('plan', '!=', 'free')
                        ->where(function ($dateQuery) {
                            $dateQuery->whereNull('subscription_end')
                                ->orWhere('subscription_end', '>', now());
                        });
                });
            })
            ->latest()
            ->take(max($limit * 10, 40));

        $candidates = $query->get();

        return $candidates
            ->map(function (Ad $ad) use ($user, $context, $interestCategories) {
                [$score, $reasons] = $this->scoreAd($ad, $user, $context, $interestCategories);

                $ad->setAttribute('recommendation_score', $score);
                $ad->setAttribute('recommendation_reasons', array_values(array_slice(array_unique($reasons), 0, 3)));

                return $ad;
            })
            ->filter(fn (Ad $ad) => ($ad->recommendation_score ?? 0) > 0)
            ->sortByDesc(function (Ad $ad) {
                return [
                    $ad->recommendation_score ?? 0,
                    optional($ad->created_at)->timestamp ?? 0,
                ];
            })
            ->take($limit)
            ->values();
    }

    private function scoreAd(Ad $ad, User $user, array $context, array $interestCategories): array
    {
        $score = 0;
        $reasons = [];
        $adCategory = $this->normalizeCategory($ad->category);

        if ($adCategory && in_array($adCategory, $interestCategories, true)) {
            $score += 45;
            $reasons[] = 'Dans vos categories';
        }

        $preferredServiceType = ($user->user_type === 'professionnel' || $user->is_service_provider) ? 'demande' : 'offre';
        if ($ad->service_type === $preferredServiceType) {
            $score += 18;
            $reasons[] = 'Adapte a votre profil';
        }

        $distanceKm = $this->resolveDistanceKm($ad, $context);
        if ($distanceKm !== null) {
            $radius = max((int) ($context['radius'] ?? $user->geo_radius ?? 50), 5);

            if ($distanceKm <= 25) {
                $score += 22;
                $reasons[] = 'Pres de vous';
            } elseif ($distanceKm <= $radius) {
                $score += 14;
                $reasons[] = 'Dans votre zone';
            } elseif ($distanceKm <= ($radius * 2)) {
                $score += 6;
            }
        }

        if ($ad->user?->is_verified) {
            $score += 8;
            $reasons[] = 'Profil verifie';
        }

        if ($ad->user?->identity_verified) {
            $score += 6;
        }

        if ($this->authorHasActiveProPlan($ad)) {
            $score += 8;
        }

        if ($ad->is_boosted && $ad->boost_end && $ad->boost_end->isFuture()) {
            $score += 10;
            $reasons[] = 'Annonce mise en avant';
        }

        if ($ad->is_urgent && (!$ad->urgent_until || $ad->urgent_until->isFuture())) {
            $score += 6;
            $reasons[] = 'Besoin urgent';
        }

        if ($ad->created_at) {
            if ($ad->created_at->gt(now()->subDay())) {
                $score += 8;
                $reasons[] = 'Publication recente';
            } elseif ($ad->created_at->gt(now()->subDays(3))) {
                $score += 4;
            }
        }

        return [$score, $reasons];
    }

    private function extractInterestCategories(User $user): array
    {
        $categories = [];

        foreach ((array) ($user->preferred_categories ?? []) as $category) {
            $categories[] = $this->normalizeCategory($category);
        }

        $categories[] = $this->normalizeCategory($user->service_category ?? null);

        foreach ((array) ($user->service_subcategories ?? []) as $category) {
            $categories[] = $this->normalizeCategory($category);
        }

        foreach ($user->savedAds()->pluck('category') as $category) {
            $categories[] = $this->normalizeCategory($category);
        }

        return array_values(array_unique(array_filter($categories)));
    }

    private function resolveDistanceKm(Ad $ad, array $context): ?float
    {
        $userLat = $context['latitude'] ?? null;
        $userLng = $context['longitude'] ?? null;

        if ($userLat === null || $userLng === null || $ad->latitude === null || $ad->longitude === null) {
            return null;
        }

        $earthRadiusKm = 6371;
        $latFrom = deg2rad((float) $userLat);
        $lngFrom = deg2rad((float) $userLng);
        $latTo = deg2rad((float) $ad->latitude);
        $lngTo = deg2rad((float) $ad->longitude);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2)
            + cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)
        ));

        return $earthRadiusKm * $angle;
    }

    private function normalizeCategory(?string $category): ?string
    {
        if (!$category) {
            return null;
        }

        $normalized = mb_strtolower(trim($category));

        return $normalized !== '' ? $normalized : null;
    }

    private function authorHasActiveProPlan(Ad $ad): bool
    {
        $author = $ad->user;
        if (!$author) {
            return false;
        }

        return !empty($author->plan)
            && !in_array($author->plan, ['', 'free'], true)
            && ($author->subscription_end === null || $author->subscription_end->isFuture());
    }
}
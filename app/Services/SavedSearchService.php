<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\SavedSearch;
use App\Models\SavedSearchMatch;
use App\Models\User;
use App\Notifications\SavedSearchMatchNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SavedSearchService
{
    public function buildSnapshot(array $input, ?User $user = null, array $geoContext = []): array
    {
        $type = $input['type'] ?? 'all';
        $category = $input['category'] ?? null;
        $searchTerm = $input['search'] ?? null;

        if (!$category && $searchTerm && empty($input['q'])) {
            $category = $searchTerm;
        }

        $normalized = [
            'name' => $this->buildName($category, $searchTerm, $type, $geoContext),
            'search_term' => $this->cleanString($searchTerm),
            'category' => $this->cleanString($category),
            'service_type' => in_array($type, ['all', 'offres', 'demandes'], true) ? $type : 'all',
            'country' => $this->cleanString($input['country'] ?? ($geoContext['country'] ?? null) ?? $user?->getDisplayCountry()),
            'city' => $this->cleanString($input['city'] ?? ($geoContext['city'] ?? null) ?? $user?->getDisplayCity()),
            'latitude' => $geoContext['latitude'] ?? $user?->latitude,
            'longitude' => $geoContext['longitude'] ?? $user?->longitude,
            'radius_km' => isset($input['radius']) || isset($geoContext['radius'])
                ? (int) ($input['radius'] ?? $geoContext['radius'])
                : ($user?->geo_radius ?? null),
            'filters' => array_filter([
                'sort' => $this->cleanString($input['sort'] ?? 'recent'),
                'category' => $this->cleanString($category),
                'search' => $this->cleanString($searchTerm),
                'type' => in_array($type, ['all', 'offres', 'demandes'], true) ? $type : 'all',
            ], fn ($value) => $value !== null && $value !== ''),
        ];

        return $normalized;
    }

    public function saveFromSnapshot(User $user, array $snapshot): SavedSearch
    {
        $attributes = [
            'user_id' => $user->id,
            'search_term' => $snapshot['search_term'],
            'category' => $snapshot['category'],
            'service_type' => $snapshot['service_type'],
            'country' => $snapshot['country'],
            'city' => $snapshot['city'],
            'radius_km' => $snapshot['radius_km'],
        ];

        $savedSearch = SavedSearch::firstOrNew($attributes);
        $savedSearch->fill([
            'name' => $snapshot['name'],
            'latitude' => $snapshot['latitude'],
            'longitude' => $snapshot['longitude'],
            'filters' => $snapshot['filters'],
            'is_active' => true,
        ]);
        $savedSearch->save();

        return $savedSearch;
    }

    public function findExistingSearch(User $user, array $snapshot): ?SavedSearch
    {
        return SavedSearch::query()
            ->where('user_id', $user->id)
            ->where('search_term', $snapshot['search_term'])
            ->where('category', $snapshot['category'])
            ->where('service_type', $snapshot['service_type'])
            ->where('country', $snapshot['country'])
            ->where('city', $snapshot['city'])
            ->where('radius_km', $snapshot['radius_km'])
            ->first();
    }

    public function processNewAd(Ad $ad): int
    {
        $ad->loadMissing('user');

        $searches = SavedSearch::query()
            ->where('is_active', true)
            ->where('user_id', '!=', $ad->user_id)
            ->with('user')
            ->get();

        $notifiedCount = 0;

        foreach ($searches as $savedSearch) {
            if (!$this->matchesAd($savedSearch, $ad)) {
                continue;
            }

            $match = SavedSearchMatch::firstOrCreate(
                [
                    'saved_search_id' => $savedSearch->id,
                    'ad_id' => $ad->id,
                ],
                [
                    'matched_at' => now(),
                    'notified_at' => now(),
                ]
            );

            if (!$match->wasRecentlyCreated) {
                continue;
            }

            $savedSearch->forceFill(['last_matched_at' => now()])->save();

            try {
                $savedSearch->user?->notify(new SavedSearchMatchNotification($savedSearch, $ad));
                $notifiedCount++;
            } catch (\Throwable $exception) {
                Log::warning('Saved search notification failed for search #' . $savedSearch->id . ' and ad #' . $ad->id, [
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return $notifiedCount;
    }

    public function matchesAd(SavedSearch $savedSearch, Ad $ad): bool
    {
        if (!$savedSearch->is_active) {
            return false;
        }

        if ((int) $savedSearch->user_id === (int) $ad->user_id) {
            return false;
        }

        if (!$this->matchesType($savedSearch, $ad)) {
            return false;
        }

        if (!$this->matchesCategory($savedSearch, $ad)) {
            return false;
        }

        if (!$this->matchesLocation($savedSearch, $ad)) {
            return false;
        }

        return true;
    }

    private function matchesType(SavedSearch $savedSearch, Ad $ad): bool
    {
        return match ($savedSearch->service_type) {
            'offres' => $ad->service_type === 'offre',
            'demandes' => $ad->service_type === 'demande',
            default => true,
        };
    }

    private function matchesCategory(SavedSearch $savedSearch, Ad $ad): bool
    {
        $category = $this->normalize($savedSearch->category);
        $searchTerm = $this->normalize($savedSearch->search_term);
        $haystack = $this->normalize(implode(' ', array_filter([
            $ad->category,
            $ad->title,
            $ad->description,
        ])));

        if ($category && !str_contains($haystack, $category)) {
            return false;
        }

        if ($searchTerm && !str_contains($haystack, $searchTerm)) {
            return false;
        }

        return true;
    }

    private function matchesLocation(SavedSearch $savedSearch, Ad $ad): bool
    {
        $country = $this->normalize($savedSearch->country);
        if ($country && $this->normalize($ad->country) !== $country) {
            return false;
        }

        $city = $this->normalize($savedSearch->city);
        if ($city) {
            $adLocation = $this->normalize($ad->location);
            if (!$adLocation || !str_contains($adLocation, $city)) {
                return false;
            }
        }

        if ($savedSearch->latitude !== null && $savedSearch->longitude !== null && $savedSearch->radius_km && $ad->latitude !== null && $ad->longitude !== null) {
            $distanceKm = $this->distanceKm(
                (float) $savedSearch->latitude,
                (float) $savedSearch->longitude,
                (float) $ad->latitude,
                (float) $ad->longitude
            );

            if ($distanceKm > $savedSearch->radius_km) {
                return false;
            }
        }

        return true;
    }

    private function buildName(?string $category, ?string $searchTerm, string $type, array $geoContext): string
    {
        $subject = $category ?: $searchTerm ?: 'Toutes les annonces';
        $typeLabel = match ($type) {
            'offres' => 'offres',
            'demandes' => 'demandes',
            default => 'annonces',
        };

        $city = $this->cleanString($geoContext['city'] ?? null);

        return trim($subject . ' · ' . $typeLabel . ($city ? ' · ' . $city : ''));
    }

    private function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function normalize(?string $value): ?string
    {
        $value = $this->cleanString($value);

        return $value ? mb_strtolower($value) : null;
    }

    private function distanceKm(float $latA, float $lngA, float $latB, float $lngB): float
    {
        $earthRadiusKm = 6371;

        $latFrom = deg2rad($latA);
        $lngFrom = deg2rad($lngA);
        $latTo = deg2rad($latB);
        $lngTo = deg2rad($lngB);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2)
            + cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)
        ));

        return $earthRadiusKm * $angle;
    }
}
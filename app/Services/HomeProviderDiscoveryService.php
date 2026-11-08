<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\User;
use App\Support\Territory;
use App\Support\TerritoryResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class HomeProviderDiscoveryService
{
    /**
     * Discovery is independent of past requests; broader locations are explicitly labelled.
     * Quand le territoire du lecteur est connu, la sélection s'arrête à ce
     * territoire : aucun élargissement « partout ». Sans profil, la zone est masquée.
     */
    public function discover(User $viewer, ?string $city, ?string $country, ?Territory $territory = null): array
    {
        $city = trim((string) $city) ?: null;
        $country = trim((string) $country) ?: null;
        $base = User::query()->where('is_active', true)->where('profile_public', true)
            ->where('id', '!=', $viewer->id)
            ->where(fn (Builder $query) => $query->where('user_type', 'professionnel')
                ->orWhere('account_type', 'professionnel')->orWhere('is_service_provider', true));

        // Comptes internes ou de test : jamais mis en avant.
        $hiddenIds = Ad::hiddenAuthorIds();
        if ($hiddenIds !== []) {
            $base->whereNotIn('id', $hiddenIds);
        }

        $scopes = [];
        if ($territory) {
            // Lieu déclaré du profil seulement : c'est celui qui est affiché sur la carte.
            [$sql, $bindings] = app(TerritoryResolver::class)->sqlCondition($territory, [
                'postal' => 'users.postal_code', 'country' => 'users.country', 'city' => 'users.city',
            ]);
            $inTerritory = (clone $base)->whereRaw($sql, $bindings);
            if ($city) {
                $scopes['city'] = (clone $inTerritory)->whereRaw('LOWER(TRIM(city)) = ?', [mb_strtolower($city)]);
            }
            $scopes['territory'] = $inTerritory;
        } else {
            if ($city) {
                $local = (clone $base)->whereRaw('LOWER(TRIM(city)) = ?', [mb_strtolower($city)]);
                if ($country) {
                    $local->whereRaw('LOWER(TRIM(country)) = ?', [mb_strtolower($country)]);
                }
                $scopes['city'] = $local;
            }
            if ($country) {
                $scopes['country'] = (clone $base)->whereRaw('LOWER(TRIM(country)) = ?', [mb_strtolower($country)]);
            }
            $scopes['all'] = $base;
        }

        $territoryCountry = $territory?->isOverseas() ? $territory->label : null;

        foreach ($scopes as $scope => $query) {
            $count = (clone $query)->count();
            if ($count === 0) {
                continue;
            }

            return [
                'profiles' => $this->rotatingSelection($query, $count, (int) $viewer->id),
                'scope' => $scope,
                'city' => $scope === 'city' ? $city : null,
                'country' => $territory ? $territoryCountry : ($scope !== 'all' ? $country : null),
                'territory' => $territory,
                'expanded' => ($city && $scope !== 'city') || ($country && $scope === 'all'),
                'requested_location' => implode(' · ', array_filter([$city, $territory ? $territory->label : $country])),
            ];
        }

        return ['profiles' => collect(), 'scope' => 'empty', 'city' => $city,
            'country' => $territory ? $territoryCountry : $country, 'territory' => $territory,
            'expanded' => false, 'requested_location' => implode(' · ', array_filter([$city, $territory ? $territory->label : $country]))];
    }

    private function rotatingSelection(Builder $query, int $count, int $viewerId): Collection
    {
        // Stable within a day, rotating across the entire eligible pool, including newcomers.
        // No paid boost or review-count advantage. Bound memory even for large directories.
        $offset = (intdiv(now('UTC')->timestamp, 86400) + $viewerId) % $count;
        $size = min(40, $count);
        $firstSize = min($size, $count - $offset);
        $pool = (clone $query)->orderBy('id')->offset($offset)->limit($firstSize)->get();
        if ($firstSize < $size) {
            $pool = $pool->concat((clone $query)->orderBy('id')->limit($size - $firstSize)->get());
        }

        $pool->load(['services' => fn ($services) => $services->where('is_active', true)->orderBy('id')->limit(2)]);
        $selected = $pool->unique(fn (User $provider) => mb_strtolower(trim(
            $provider->profession ?: $provider->service_category ?: $provider->services->first()?->subcategory ?: 'autre'
        )))->take(4);
        $selected = $selected->concat($pool->whereNotIn('id', $selected->pluck('id')))->take(4)->values();
        $selected->loadCount(['verifiedReviewsReceived as verified_reviews_count']);
        $selected->loadAvg('verifiedReviewsReceived as verified_reviews_avg', 'rating');

        return $selected;
    }
}

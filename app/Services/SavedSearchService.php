<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\SavedSearch;
use App\Models\SavedSearchMatch;
use App\Models\User;
use App\Notifications\SavedSearchMatchNotification;
use App\Support\Territory;
use App\Support\TerritoryResolver;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SavedSearchService
{
    public function __construct(private TerritoryResolver $territories)
    {
    }

    /**
     * Territoire demandé par le formulaire (« territory » = code 976, 75, 2A…).
     * Null si le champ est absent ou si le code n'est pas reconnu.
     */
    public function territoryFromInput(array $input): ?Territory
    {
        $code = is_scalar($input['territory'] ?? null) ? trim((string) $input['territory']) : '';

        return $code === '' ? null : $this->territories->find($code);
    }

    public function buildSnapshot(array $input, ?User $user = null, array $geoContext = []): array
    {
        // Alerte « nouvelles offres du territoire » (état vide du flux client) :
        // le lieu est le territoire entier, jamais la ville ni le rayon du compte.
        if ($territory = $this->territoryFromInput($input)) {
            return $this->buildTerritorySnapshot($input, $territory);
        }

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
            'territory_code' => null,
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
            'territory_code' => $snapshot['territory_code'] ?? null,
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
            ->where('territory_code', $snapshot['territory_code'] ?? null)
            ->first();
    }

    public function processNewAd(Ad $ad): int
    {
        $ad->loadMissing('user');

        // Les alertes de territoire ne concernent que les annonces de ce
        // territoire : les autres sont écartées dès la requête.
        $adTerritoryCode = $this->territories->forAd($ad)?->code;

        $searches = SavedSearch::query()
            ->where('is_active', true)
            ->where('user_id', '!=', $ad->user_id)
            ->where(function ($scope) use ($adTerritoryCode) {
                $scope->whereNull('territory_code');
                if ($adTerritoryCode !== null) {
                    $scope->orWhere('territory_code', $adTerritoryCode);
                }
            })
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

        // Les annonces des comptes internes ou de test ne sont pas publiques
        // (config feed.hidden_author_ids) : elles ne déclenchent aucune alerte.
        if (in_array((int) $ad->user_id, Ad::hiddenAuthorIds(), true)) {
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
        // Alerte de territoire : mêmes règles que le flux client
        // (TerritoryResolver), à la place du pays, de la ville et du rayon.
        if ($this->cleanString($savedSearch->territory_code) !== null) {
            return $this->matchesTerritory($savedSearch, $ad);
        }

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

    private function matchesTerritory(SavedSearch $savedSearch, Ad $ad): bool
    {
        // Une alerte créée depuis le flux des services ne se déclenche que pour
        // les annonces que ce flux affiche (domaine « service » ou non précisé).
        $domain = is_array($savedSearch->filters) ? ($savedSearch->filters['domain'] ?? null) : null;
        if ($domain !== null && !in_array($ad->publication_domain, [null, '', $domain], true)) {
            return false;
        }

        $adTerritory = $this->territories->forAd($ad);

        return $adTerritory !== null && $adTerritory->code === trim((string) $savedSearch->territory_code);
    }

    /**
     * Alerte portant sur un territoire entier. Le libellé de pays reste
     * renseigné (« Mayotte », « France ») pour l'affichage et pour l'ancien
     * code en cas de retour arrière ; la ville, les coordonnées et le rayon du
     * compte ne sont pas repris, sinon l'alerte serait plus étroite que le
     * territoire annoncé.
     */
    private function buildTerritorySnapshot(array $input, Territory $territory): array
    {
        $type = in_array($input['type'] ?? null, ['all', 'offres', 'demandes'], true) ? $input['type'] : 'offres';
        $category = $this->cleanString(is_string($input['category'] ?? null) ? $input['category'] : null);
        $searchTerm = $this->cleanString(is_string($input['search'] ?? null) ? $input['search'] : null);

        return [
            'name' => $this->buildTerritoryName($category ?: $searchTerm, $type, $territory),
            'search_term' => $searchTerm,
            'category' => $category,
            'service_type' => $type,
            'country' => $territory->isOverseas() ? $territory->label : ($territory->countryLabels[0] ?? 'France'),
            'city' => null,
            'latitude' => null,
            'longitude' => null,
            'radius_km' => null,
            'territory_code' => $territory->code,
            'filters' => array_filter([
                'type' => $type,
                'category' => $category,
                'search' => $searchTerm,
                'territory' => $territory->code,
                'domain' => 'service',
            ], fn ($value) => $value !== null && $value !== ''),
        ];
    }

    /** « Offres à Mayotte », « Plombier · offres dans le département 75 ». */
    private function buildTerritoryName(?string $subject, string $type, Territory $territory): string
    {
        $typeLabel = match ($type) {
            'offres' => 'offres',
            'demandes' => 'demandes',
            default => 'annonces',
        };
        $place = $typeLabel . ' ' . $territory->withPreposition();

        return $subject ? $subject . ' · ' . $place : ucfirst($place);
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
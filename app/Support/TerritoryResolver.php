<?php

namespace App\Support;

use App\Models\Ad;
use App\Models\User;
use App\Services\IpGeolocationService;
use Illuminate\Support\Str;

/**
 * Rattache un membre, une annonce ou une adresse à un territoire.
 *
 * Ordre de lecture, du plus fiable au moins fiable :
 *  1. code postal d'outre-mer (97[1-6]xx configuré) ;
 *  2. libellé de pays d'outre-mer (les DROM sont rangés comme des pays dans
 *     config/locations.php) ;
 *  3. commune d'une liste d'outre-mer, seulement sans code postal et avec un
 *     pays vide ou hexagonal (« France ») ; une commune présente dans
 *     plusieurs listes (Saint-Denis, Sainte-Marie…) n'est jamais retenue ;
 *  4. département de l'Hexagone ou de Corse, d'après le code postal, avec un
 *     pays vide ou hexagonal ;
 *  5. sinon null : le territoire est inconnu, et l'interface le dit.
 *
 * sqlCondition() traduit exactement les mêmes règles en SQL portable
 * (SQLite et PostgreSQL : LOWER, TRIM, COALESCE, LIKE, IN), pour que la
 * sélection en base et le rattachement en PHP ne divergent pas.
 */
final class TerritoryResolver
{
    /** @var array<string, Territory>|null */
    private ?array $overseas = null;

    /** @var array<string, string>|null libellé de pays normalisé => code */
    private ?array $countryIndex = null;

    /** @var array<string, string>|null commune normalisée => code (non ambiguë) */
    private ?array $communeIndex = null;

    /** @var array<string, list<string>>|null code => variantes de communes */
    private ?array $communeVariants = null;

    /**
     * Territoire d'un membre. Le lieu déclaré dans le profil prime ; à défaut,
     * le lieu détecté et enregistré ; à défaut, la position de la requête
     * (middleware « geo »). Une position par défaut (geo_source = default,
     * ou coordonnées par défaut posées avant ce marquage, voir
     * hasDefaultPosition) n'est jamais retenue.
     */
    public function forUser(?User $user, array $requestGeo = []): ?Territory
    {
        if ($user) {
            if ($this->filled($user->postal_code) || $this->filled($user->country) || $this->filled($user->city)) {
                return $this->fromParts($user->postal_code, $user->country, $user->city);
            }

            if ($this->hasDefaultPosition($user)) {
                return null;
            }

            if ($this->filled($user->detected_country) || $this->filled($user->detected_city)) {
                return $this->fromParts(null, $user->detected_country, $user->detected_city);
            }
        }

        // Une position « par défaut » n'est pas une mesure : elle n'est pas retenue.
        if (($requestGeo['source'] ?? null) === 'default') {
            return null;
        }

        if ($this->filled($requestGeo['country'] ?? null) || $this->filled($requestGeo['city'] ?? null)) {
            return $this->fromParts($requestGeo['postal_code'] ?? null, $requestGeo['country'] ?? null, $requestGeo['city'] ?? null);
        }

        return null;
    }

    public function forAd(Ad $ad): ?Territory
    {
        return $this->fromParts($ad->postal_code, $ad->country, $ad->city, $ad->location);
    }

    public function fromParts(?string $postalCode, ?string $country, ?string $city, ?string $location = null): ?Territory
    {
        $postal = trim((string) $postalCode);

        if (preg_match('/^(97[1-6])\d{2}$/', $postal, $match) === 1 && ($territory = $this->overseas()[$match[1]] ?? null)) {
            return $territory;
        }

        $countryKey = $this->normalize($country);
        if ($countryKey !== '' && isset($this->countryIndex()[$countryKey])) {
            return $this->overseas()[$this->countryIndex()[$countryKey]];
        }

        $countryIsMainland = $countryKey === '' || in_array($countryKey, $this->mainlandCountryKeys(), true);
        if (! $countryIsMainland) {
            return null;
        }

        if ($postal === '') {
            foreach ([$city, $location] as $place) {
                $placeKey = $this->normalize($place);
                if ($placeKey !== '' && isset($this->communeIndex()[$placeKey])) {
                    return $this->overseas()[$this->communeIndex()[$placeKey]];
                }
            }

            return null;
        }

        return $this->department($postal);
    }

    /** Département de l'Hexagone ou de Corse d'après un code postal à 5 chiffres. */
    public function department(?string $postalCode): ?Territory
    {
        $postal = trim((string) $postalCode);
        if (preg_match('/^\d{5}$/', $postal) !== 1) {
            return null;
        }

        $prefix = substr($postal, 0, 2);
        if ($prefix === '20') {
            return $this->departmentTerritory((int) $postal < 20200 ? '2A' : '2B');
        }

        $number = (int) $prefix;

        return $number >= 1 && $number <= 95 ? $this->departmentTerritory($prefix) : null;
    }

    public function find(?string $code): ?Territory
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }

        if (isset($this->overseas()[$code])) {
            return $this->overseas()[$code];
        }

        if (in_array($code, ['2A', '2B'], true)
            || (preg_match('/^\d{2}$/', $code) === 1 && $code !== '20' && (int) $code >= 1 && (int) $code <= 95)) {
            return $this->departmentTerritory($code);
        }

        return null;
    }

    public function findBySlug(?string $slug): ?Territory
    {
        $slug = Str::lower(trim((string) $slug));
        foreach ($this->overseas() as $territory) {
            if ($territory->slug === $slug) {
                return $territory;
            }
        }

        if (preg_match('/^departement-([0-9]{2}|2a|2b)$/', $slug, $match) === 1) {
            return $this->find($match[1]);
        }

        return null;
    }

    /** @return list<Territory> territoires d'outre-mer, dans l'ordre de la configuration */
    public function overseasTerritories(): array
    {
        return array_values($this->overseas());
    }

    /**
     * Condition SQL portable « l'enregistrement appartient au territoire ».
     * Elle n'est jamais NULL (tout est encadré par COALESCE) : on peut donc
     * la nier sans perdre de lignes.
     *
     * @param  array{postal?: ?string, country?: ?string, city?: ?string, location?: ?string}  $columns
     * @return array{0: string, 1: list<string>}
     */
    public function sqlCondition(Territory $territory, array $columns): array
    {
        $postal = $this->sqlColumn($columns['postal'] ?? null, false);
        $country = $this->sqlColumn($columns['country'] ?? null, true);
        $mainland = ['', ...$this->mainlandCountryKeys()];

        if ($territory->isDepartment()) {
            [$postalSql, $postalBindings] = $this->likeAny($postal, array_map(
                fn (string $prefix) => $prefix.str_repeat('_', 5 - strlen($prefix)),
                $territory->postalPrefixes()
            ));
            [$mainlandSql, $mainlandBindings] = $this->in($country, $mainland);

            return ['('.$postalSql.' AND '.$mainlandSql.')', [...$postalBindings, ...$mainlandBindings]];
        }

        $sql = [];
        $bindings = [];

        // 1. Code postal du territoire.
        $sql[] = $postal.' LIKE ?';
        $bindings[] = $territory->code.'__';

        // 2. Libellé de pays, sauf si le code postal désigne un autre DROM.
        $otherCodes = array_values(array_diff(array_keys($this->overseas()), [$territory->code]));
        [$countrySql, $countryBindings] = $this->in($country, $this->countryKeysFor($territory->code));
        [$otherPostalSql, $otherPostalBindings] = $this->likeAny($postal, array_map(fn ($code) => $code.'__', $otherCodes));
        $sql[] = '('.$countrySql.' AND NOT '.$otherPostalSql.')';
        array_push($bindings, ...$countryBindings, ...$otherPostalBindings);

        // 3. Commune connue, sans code postal, pays vide ou hexagonal.
        $communes = $this->communeVariants()[$territory->code] ?? [];
        if ($communes !== []) {
            $city = $this->sqlColumn($columns['city'] ?? null, true);
            $location = $this->sqlColumn($columns['location'] ?? null, true);
            [$mainlandSql, $mainlandBindings] = $this->in($country, $mainland);
            [$cityInSql, $cityInBindings] = $this->in($city, $communes);
            [$cityKnownSql, $cityKnownBindings] = $this->in($city, array_keys($this->communeIndex()));
            [$locationInSql, $locationInBindings] = $this->in($location, $communes);
            $sql[] = '('.$postal." = '' AND ".$mainlandSql.' AND ('.$cityInSql
                .' OR (NOT '.$cityKnownSql.' AND '.$locationInSql.')))';
            array_push($bindings, ...$mainlandBindings, ...$cityInBindings, ...$cityKnownBindings, ...$locationInBindings);
        }

        return ['('.implode(' OR ', $sql).')', $bindings];
    }

    /** @return array<string, Territory> */
    private function overseas(): array
    {
        if ($this->overseas !== null) {
            return $this->overseas;
        }

        $this->overseas = [];
        foreach ((array) config('territories.overseas', []) as $code => $definition) {
            $code = (string) $code;
            $label = (string) ($definition['label'] ?? $code);
            $this->overseas[$code] = new Territory(
                code: $code,
                label: $label,
                slug: (string) ($definition['slug'] ?? Str::slug($label)),
                timezone: (string) ($definition['timezone'] ?? config('app.timezone', 'UTC')),
                communes: array_values((array) config('locations.cities.'.($definition['location_country'] ?? $label), [])),
                ftLieux: (string) ($definition['ft_lieux'] ?? $code.'D'),
                kind: Territory::KIND_OVERSEAS,
                countryLabels: array_values(array_unique([$label, (string) ($definition['location_country'] ?? $label), ...((array) ($definition['aliases'] ?? []))])),
                preposition: (string) ($definition['preposition'] ?? 'à'),
            );
        }

        return $this->overseas;
    }

    private function departmentTerritory(string $code): Territory
    {
        return new Territory(
            code: $code,
            label: 'Département '.$code,
            slug: 'departement-'.Str::lower($code),
            timezone: (string) config('territories.mainland.timezone', 'Europe/Paris'),
            communes: [],
            ftLieux: $code.'D',
            kind: Territory::KIND_DEPARTMENT,
            countryLabels: array_values((array) config('territories.mainland.country_labels', ['France'])),
            preposition: 'dans le',
        );
    }

    /** @return array<string, string> */
    private function countryIndex(): array
    {
        if ($this->countryIndex === null) {
            $this->countryIndex = [];
            foreach ($this->overseas() as $code => $territory) {
                foreach ($territory->countryLabels as $label) {
                    foreach ($this->variants($label) as $variant) {
                        $this->countryIndex[$variant] = $code;
                    }
                }
            }
        }

        return $this->countryIndex;
    }

    /** @return list<string> */
    private function countryKeysFor(string $code): array
    {
        return array_keys(array_filter($this->countryIndex(), fn (string $owner) => $owner === $code));
    }

    /** @return list<string> */
    private function mainlandCountryKeys(): array
    {
        $keys = [];
        foreach ((array) config('territories.mainland.country_labels', ['France']) as $label) {
            array_push($keys, ...$this->variants((string) $label));
        }

        return array_values(array_unique($keys));
    }

    /** @return array<string, string> */
    private function communeIndex(): array
    {
        if ($this->communeIndex !== null) {
            return $this->communeIndex;
        }

        // Une commune homonyme d'une ville de l'Hexagone ou d'un autre DROM
        // ne permet pas de conclure : elle est écartée.
        $owners = [];
        foreach ($this->overseas() as $code => $territory) {
            foreach ($territory->communes as $commune) {
                foreach ($this->variants((string) $commune) as $variant) {
                    $owners[$variant][$code] = true;
                }
            }
        }
        foreach ((array) config('locations.cities.France', []) as $city) {
            foreach ($this->variants((string) $city) as $variant) {
                $owners[$variant]['mainland'] = true;
            }
        }

        $this->communeIndex = [];
        $this->communeVariants = [];
        foreach ($owners as $variant => $codes) {
            if (count($codes) !== 1 || isset($codes['mainland'])) {
                continue;
            }
            $code = (string) array_key_first($codes);
            $this->communeIndex[$variant] = $code;
            $this->communeVariants[$code][] = $variant;
        }

        return $this->communeIndex;
    }

    /** @return array<string, list<string>> */
    private function communeVariants(): array
    {
        $this->communeIndex();

        return $this->communeVariants ?? [];
    }

    /**
     * Formes comparables d'un libellé : minuscules Unicode (PostgreSQL),
     * minuscules ASCII seules (LOWER de SQLite), sans accents, et apostrophe
     * droite ou typographique.
     *
     * @return list<string>
     */
    private function variants(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        $forms = [mb_strtolower($value), strtolower($value), Str::lower(Str::ascii($value))];
        foreach ($forms as $form) {
            $forms[] = str_replace("'", '’', $form);
            $forms[] = str_replace('’', "'", $form);
        }

        return array_values(array_unique(array_filter($forms, fn ($form) => $form !== '')));
    }

    private function normalize(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    /**
     * Le membre ne porte que la position par défaut (IpGeolocationService) :
     * marquée geo_source = default, ou posée avant ce marquage (geo_source
     * vide et coordonnées de Mamoudzou). Ce n'est pas une mesure. La
     * géolocalisation du navigateur (geo_source = browser) n'est jamais visée.
     */
    public function hasDefaultPosition(User $user): bool
    {
        if ($user->geo_source === 'default') {
            return true;
        }

        // Position par défaut posée avant le marquage geo_source = default : ce n'est pas une mesure.
        return ! $this->filled($user->geo_source) && $this->isDefaultPosition($user->latitude, $user->longitude);
    }

    private function isDefaultPosition(mixed $latitude, mixed $longitude): bool
    {
        return $latitude !== null && $longitude !== null
            && abs((float) $latitude - IpGeolocationService::DEFAULT_LATITUDE) < 0.00005
            && abs((float) $longitude - IpGeolocationService::DEFAULT_LONGITUDE) < 0.00005;
    }

    private function filled(mixed $value): bool
    {
        return is_string($value) ? trim($value) !== '' : $value !== null;
    }

    private function sqlColumn(?string $column, bool $lower): string
    {
        if ($column === null || $column === '') {
            return "''";
        }

        $expression = 'TRIM(COALESCE('.$column.", ''))";

        return $lower ? 'LOWER('.$expression.')' : $expression;
    }

    /** @return array{0: string, 1: list<string>} */
    private function in(string $expression, array $values): array
    {
        $values = array_values(array_unique(array_map('strval', $values)));
        if ($values === []) {
            return ['1 = 0', []];
        }

        return [$expression.' IN ('.implode(', ', array_fill(0, count($values), '?')).')', $values];
    }

    /** @return array{0: string, 1: list<string>} */
    private function likeAny(string $expression, array $patterns): array
    {
        if ($patterns === []) {
            return ['1 = 0', []];
        }

        return ['('.implode(' OR ', array_fill(0, count($patterns), $expression.' LIKE ?')).')', array_values($patterns)];
    }
}

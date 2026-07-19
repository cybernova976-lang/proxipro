<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MarketplaceCategoryRegistry
{
    public const SETTING_KEY = 'marketplace_category_availability';

    /**
     * Toutes les catégories pilotables depuis l'administration.
     * Les services restent actifs par défaut. Les verticales de petites
     * annonces restent en attente tant qu'elles n'ont pas été explicitement
     * activées et préparées juridiquement.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        $definitions = [];

        foreach (['services' => true, 'marketplace' => false] as $type => $defaultEnabled) {
            foreach (config('categories.'.$type, []) as $name => $category) {
                $id = Str::slug($name);
                $definitions[$id] = [
                    'id' => $id,
                    'name' => $name,
                    'type' => $type,
                    'default_enabled' => $defaultEnabled,
                    'icon' => $category['icon'] ?? '•',
                    'fa_icon' => $category['fa_icon'] ?? 'fas fa-tag',
                    'color' => $category['color'] ?? '#64748b',
                    'description' => $category['description'] ?? '',
                    'subcategories' => $category['subcategories'] ?? [],
                ];
            }
        }

        return $definitions;
    }

    /** @return array<string, bool> */
    public static function states(): array
    {
        $stored = Setting::get(self::SETTING_KEY);
        $stored = is_string($stored) ? json_decode($stored, true) : $stored;
        $stored = is_array($stored) ? $stored : [];
        $states = [];

        foreach (self::definitions() as $id => $definition) {
            $states[$id] = array_key_exists($id, $stored)
                ? filter_var($stored[$id], FILTER_VALIDATE_BOOL)
                : (bool) $definition['default_enabled'];
        }

        return $states;
    }

    /** @param array<int, string> $enabledIds */
    public static function storeEnabledIds(array $enabledIds): void
    {
        $enabledLookup = array_fill_keys($enabledIds, true);
        $states = [];

        foreach (self::definitions() as $id => $definition) {
            $states[$id] = isset($enabledLookup[$id]);
        }

        Setting::set(
            self::SETTING_KEY,
            json_encode($states, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'catalog'
        );

        foreach ([
            'homepage.stats',
            'homepage.category_counts',
            'feed:active-category-counts:v2:all',
            'feed:active-category-counts:v2:offre',
        ] as $cacheKey) {
            Cache::forget($cacheKey);
        }
    }

    public static function isEnabled(?string $mainCategory, ?string $category = null): bool
    {
        $resolved = self::resolveMainCategory($mainCategory, $category);

        if ($resolved === null) {
            return false;
        }

        $definition = collect(self::definitions())->firstWhere('name', $resolved);

        return $definition !== null && (self::states()[$definition['id']] ?? false);
    }

    public static function isVisible(?string $mainCategory, ?string $category = null): bool
    {
        return self::resolveMainCategory($mainCategory, $category) === null
            || self::isEnabled($mainCategory, $category);
    }

    public static function resolveMainCategory(?string $mainCategory, ?string $category = null): ?string
    {
        foreach (self::definitions() as $definition) {
            $name = $definition['name'];
            $subcategories = $definition['subcategories'];

            if ($category && ($category === $name || in_array($category, $subcategories, true))) {
                return $name;
            }

            if ($mainCategory === $name && (! $category || in_array($category, $subcategories, true) || $category === $name)) {
                return $name;
            }
        }

        return null;
    }

    /** @return array<string, array<string, mixed>> */
    public static function enabledServices(): array
    {
        return self::enabledForType('services');
    }

    /** @return array<string, array<string, mixed>> */
    public static function enabledMarketplace(): array
    {
        return self::enabledForType('marketplace');
    }

    /** @return array<string, array<string, mixed>> */
    public static function enabledAll(): array
    {
        return array_merge(self::enabledServices(), self::enabledMarketplace());
    }

    public static function applyEnabledScope(Builder $query): Builder
    {
        $states = self::states();
        $disabledDefinitions = collect(self::definitions())
            ->reject(fn (array $definition): bool => $states[$definition['id']] ?? false);

        if ($disabledDefinitions->isEmpty()) {
            return $query;
        }

        $disabledMainCategories = $disabledDefinitions->pluck('name')->all();
        $disabledCategoryValues = $disabledDefinitions
            ->flatMap(fn (array $definition): array => array_merge([$definition['name']], $definition['subcategories']))
            ->unique()
            ->values()
            ->all();

        return $query
            ->where(function (Builder $visibilityQuery) use ($disabledMainCategories) {
                $visibilityQuery->whereNull('main_category')
                    ->orWhereNotIn('main_category', $disabledMainCategories);
            })
            ->whereNotIn('category', $disabledCategoryValues);
    }

    /** @return array<string, array<string, mixed>> */
    private static function enabledForType(string $type): array
    {
        $states = self::states();
        $configured = config('categories.'.$type, []);
        $enabled = [];

        foreach (self::definitions() as $id => $definition) {
            if ($definition['type'] === $type && ($states[$id] ?? false)) {
                $enabled[$definition['name']] = $configured[$definition['name']];
            }
        }

        return $enabled;
    }
}

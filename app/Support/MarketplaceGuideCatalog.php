<?php

namespace App\Support;

use Illuminate\Support\Collection;

class MarketplaceGuideCatalog
{
    public static function all(): Collection
    {
        return collect(config('marketplace_guides', []))
            ->map(function (array $guide, string $slug): array {
                $guide['slug'] = $slug;

                return $guide;
            })
            ->sortBy('priority')
            ->values();
    }

    public static function forAudience(string $audience): Collection
    {
        return self::all()
            ->filter(fn (array $guide): bool => in_array($audience, $guide['audience'] ?? [], true))
            ->values();
    }

    public static function find(string $slug): ?array
    {
        return self::all()->firstWhere('slug', $slug);
    }
}

<?php

namespace App\Support;

class PointPackCatalog
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return [
            'POINTS_5' => self::pack(5, 400, 'Boost 3 jours', '⚡', 'fas fa-bolt', '#6366f1'),
            'POINTS_10' => self::pack(10, 600, 'Boost 7 jours', '🚀', 'fas fa-rocket', '#f59e0b', 'popular'),
            'POINTS_20' => self::pack(20, 1000, 'Boost 15 jours', '⭐', 'fas fa-star', '#a855f7'),
            'POINTS_30' => self::pack(30, 1500, 'Boost 30 jours', '👑', 'fas fa-crown', '#ef4444'),
            'POINTS_50' => self::pack(50, 2200, 'Boost et vérification', '🛡️', 'fas fa-shield-alt', '#10b981'),
            'POINTS_100' => self::pack(100, 4000, 'Utilisation intensive', '💎', 'fas fa-gem', '#2563eb', 'best'),
        ];
    }

    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /** @return array<string, mixed> */
    private static function pack(
        int $points,
        int $priceCents,
        string $description,
        string $emoji,
        string $icon,
        string $color,
        ?string $badge = null,
    ): array {
        return [
            'points' => $points,
            'price_cents' => $priceCents,
            'price' => $priceCents / 100,
            'name' => $points.' Points',
            'type' => 'points',
            'description' => $description,
            'emoji' => $emoji,
            'icon' => $icon,
            'color' => $color,
            'badge' => $badge,
        ];
    }
}

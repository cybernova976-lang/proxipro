<?php

namespace App\Support;

class AdPromotionCatalog
{
    /** @return array<string, array<string, mixed>> */
    public static function boosts(): array
    {
        return [
            'boost_3' => [
                'name' => 'Boost 3 jours',
                'duration_days' => 3,
                'price_points' => 5,
                'price_euros' => 4.00,
                'description' => 'Votre annonce apparaît dans la section "Professionnels Premium" pendant 3 jours',
                'features' => [
                    'Visibilité accrue pendant 3 jours',
                    'Badge "Boost" sur votre annonce',
                    'Position prioritaire dans les résultats',
                ],
                'icon' => 'fas fa-bolt',
                'color' => '#3b82f6',
            ],
            'boost_7' => [
                'name' => 'Boost 7 jours',
                'duration_days' => 7,
                'price_points' => 10,
                'price_euros' => 6.00,
                'description' => 'Votre annonce apparaît dans la section "Professionnels Premium" pendant 7 jours',
                'features' => [
                    'Visibilité accrue pendant 7 jours',
                    'Badge "Boost" sur votre annonce',
                    'Position prioritaire dans les résultats',
                    'Meilleur rapport qualité/prix',
                ],
                'icon' => 'fas fa-rocket',
                'color' => '#10b981',
            ],
            'boost_15' => [
                'name' => 'Boost 15 jours',
                'duration_days' => 15,
                'price_points' => 20,
                'price_euros' => 10.00,
                'description' => 'Votre annonce apparaît dans la section "Professionnels Premium" pendant 15 jours',
                'features' => [
                    'Visibilité accrue pendant 15 jours',
                    'Badge "Premium" doré sur votre annonce',
                    'Position prioritaire dans les résultats',
                    'Mise en avant dans les notifications',
                ],
                'icon' => 'fas fa-star',
                'color' => '#f59e0b',
            ],
            'boost_30' => [
                'name' => 'Boost 30 jours',
                'duration_days' => 30,
                'price_points' => 30,
                'price_euros' => 15.00,
                'description' => 'Votre annonce apparaît dans la section "Professionnels Premium" pendant 30 jours',
                'features' => [
                    'Visibilité maximale pendant 30 jours',
                    'Badge "VIP" exclusif sur votre annonce',
                    'Première position garantie',
                    'Mise en avant dans les notifications',
                    'Support prioritaire',
                ],
                'icon' => 'fas fa-crown',
                'color' => '#8b5cf6',
            ],
        ];
    }

    /** @return array{price_points: int, price_euros: float} */
    public static function refresh(): array
    {
        return ['price_points' => 10, 'price_euros' => 3.00];
    }

    /** @return array{price_points: int, price_euros: float, duration_days: int} */
    public static function urgent(): array
    {
        return ['price_points' => 15, 'price_euros' => 14.00, 'duration_days' => 7];
    }

    public static function discountedCents(float $priceEuros, bool $isPro): int
    {
        $price = $isPro ? round($priceEuros * 0.8, 2) : $priceEuros;

        return (int) round($price * 100);
    }
}

<?php

namespace App\Support;

class AdPromotionCatalog
{
    /**
     * Message affiché à toute tentative d'achat ou d'activation de la remontée.
     */
    public const REFRESH_SUSPENDED_MESSAGE = 'La remontée n’est plus proposée pour le moment.';

    /**
     * La vente de la remontée payante est suspendue (décision du 8 novembre
     * 2026) : elle annulait le boost en cours sans faire remonter l'annonce,
     * l'accueil des clients étant classé par date de publication.
     */
    public static function refreshAvailable(): bool
    {
        return false;
    }

    /**
     * Les textes ne décrivent que l'effet réellement appliqué par
     * App\Services\ClientFeedOffersService : pendant la durée achetée,
     * l'offre rejoint le groupe des offres sponsorisées de l'accueil des
     * clients, classé avant les autres offres. Aucune position n'est fixe.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function boosts(): array
    {
        return [
            'boost_3' => self::boost(3, 5, 4.00, 'fas fa-bolt', '#3b82f6'),
            'boost_7' => self::boost(7, 10, 6.00, 'fas fa-rocket', '#10b981'),
            'boost_15' => self::boost(15, 20, 10.00, 'fas fa-calendar-alt', '#6366f1'),
            'boost_30' => self::boost(30, 30, 15.00, 'fas fa-calendar-check', '#8b5cf6'),
        ];
    }

    /**
     * Tarif de la remontée : conservé uniquement pour vérifier le montant des
     * paiements Stripe engagés avant la suspension (voir refreshAvailable()).
     *
     * @return array{price_points: int, price_euros: float}
     */
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

    /** @return array<string, mixed> */
    private static function boost(int $days, int $points, float $euros, string $icon, string $color): array
    {
        return [
            'name' => 'Boost '.$days.' jours',
            'duration_days' => $days,
            'price_points' => $points,
            'price_euros' => $euros,
            'description' => 'Pendant '.$days.' jours, votre offre porte la mention « Sponsorisé » et passe avant les offres non sponsorisées sur l’accueil des clients de son territoire.',
            'features' => [
                'Mention « Sponsorisé » sur votre offre pendant '.$days.' jours',
                'Affichée avant les offres non sponsorisées sur l’accueil des clients',
                'Pas de place fixe : les offres sponsorisées sont classées entre elles selon les règles de classement publiées',
            ],
            'icon' => $icon,
            'color' => $color,
        ];
    }
}

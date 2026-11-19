<?php

namespace App\Support;

use App\Services\Feed\RankingDisclosure;

/**
 * Catalogue des produits de mise en avant à l'unité.
 *
 * Deux jeux de règles se succèdent :
 *  - les règles d'origine (boost 3/7/15/30 jours, Urgent 7 jours), en vigueur
 *    tant que la date d'effet des nouvelles règles n'est pas atteinte ;
 *  - les nouvelles règles (« À la une », « Remontée automatique », Urgent pour
 *    toute la durée de la demande), qui changent le classement et sont donc
 *    annoncées au moins 15 jours à l'avance, comme les règles publiées s'y
 *    engagent. La date est lue dans config('feed.sponsored.effective_at').
 *
 * Les textes ne décrivent que des effets réellement appliqués par le code.
 */
class AdPromotionCatalog
{
    /** Message affiché à toute tentative d'achat de l'ancienne remontée. */
    public const REFRESH_SUSPENDED_MESSAGE = 'La remontée n’est plus proposée pour le moment.';

    /** Les nouvelles règles de mise en avant sont-elles en vigueur ? */
    public static function newRulesActive(): bool
    {
        $date = app(RankingDisclosure::class)->sponsoredEffectiveDate();

        return $date !== null && now()->greaterThanOrEqualTo($date);
    }

    /**
     * L'ancienne remontée payante (un seul coup, 3 €) reste suspendue : elle
     * annulait le boost en cours sans faire remonter l'annonce. Elle est
     * remplacée par la remontée automatique des nouvelles règles (bumps()).
     */
    public static function refreshAvailable(): bool
    {
        return false;
    }

    /**
     * Packs « offre en tête » en vente aujourd'hui : boosts d'origine, ou
     * « À la une » une fois les nouvelles règles en vigueur.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function boosts(): array
    {
        return self::newRulesActive() ? self::featured() : self::legacyBoosts();
    }

    /** @return array<string, array<string, mixed>> */
    public static function legacyBoosts(): array
    {
        return [
            'boost_3' => self::boost(3, 5, 4.00, 'fas fa-bolt', '#3b82f6'),
            'boost_7' => self::boost(7, 10, 6.00, 'fas fa-rocket', '#10b981'),
            'boost_15' => self::boost(15, 20, 10.00, 'fas fa-calendar-alt', '#6366f1'),
            'boost_30' => self::boost(30, 30, 15.00, 'fas fa-calendar-check', '#8b5cf6'),
        ];
    }

    /**
     * « À la une » : pendant la durée achetée, l'offre porte la mention
     * « Sponsorisé » et passe avant les offres non sponsorisées sur l'accueil
     * des clients et dans la liste des annonces. Aucune position n'est fixe.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function featured(): array
    {
        return [
            'boost_7' => self::featuredPack(7, 5.90),
            'boost_30' => self::featuredPack(30, 14.90),
        ];
    }

    /**
     * « Remontée automatique » : à chaque échéance, l'offre revient en haut
     * des offres non sponsorisées, comme si elle venait d'être publiée.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function bumps(): array
    {
        return [
            'bump_daily_7' => self::bumpPack(1, 7, 2.90, 'Chaque jour pendant 7 jours'),
            'bump_daily_30' => self::bumpPack(1, 30, 7.90, 'Chaque jour pendant 30 jours'),
            'bump_weekly_8' => self::bumpPack(7, 56, 3.90, 'Chaque semaine pendant 8 semaines'),
        ];
    }

    /** Un pack « en tête », quel que soit le jeu de règles : sert à honorer un paiement engagé avant un changement. */
    public static function boostDefinition(string $key): ?array
    {
        return self::boosts()[$key] ?? self::featured()[$key] ?? self::legacyBoosts()[$key] ?? null;
    }

    /** Tous les tarifs qu'un pack « en tête » a pu avoir : même raison. */
    public static function boostPrices(string $key): array
    {
        return array_values(array_unique(array_filter([
            self::boosts()[$key]['price_euros'] ?? null,
            self::featured()[$key]['price_euros'] ?? null,
            self::legacyBoosts()[$key]['price_euros'] ?? null,
        ], fn ($price) => $price !== null)));
    }

    /**
     * Tarif de l'ancienne remontée : conservé uniquement pour vérifier le
     * montant des paiements Stripe engagés avant la suspension.
     *
     * @return array{price_points: int, price_euros: float}
     */
    public static function refresh(): array
    {
        return ['price_points' => 10, 'price_euros' => 3.00];
    }

    /**
     * Option Urgent d'une demande. Nouvelles règles : un prix unique pour toute
     * la durée de la demande (duration_days à null).
     *
     * @return array{price_points: int, price_euros: float, duration_days: ?int}
     */
    public static function urgent(): array
    {
        return self::newRulesActive() ? self::urgentForWholeDuration() : self::legacyUrgent();
    }

    /** @return array{price_points: int, price_euros: float, duration_days: ?int} */
    public static function legacyUrgent(): array
    {
        return ['price_points' => 15, 'price_euros' => 14.00, 'duration_days' => 7];
    }

    /** @return array{price_points: int, price_euros: float, duration_days: ?int} */
    public static function urgentForWholeDuration(): array
    {
        return ['price_points' => 0, 'price_euros' => 2.50, 'duration_days' => null];
    }

    /** @return list<float> */
    public static function urgentPrices(): array
    {
        return array_values(array_unique([
            self::urgent()['price_euros'], self::urgentForWholeDuration()['price_euros'], self::legacyUrgent()['price_euros'],
        ]));
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

    /** @return array<string, mixed> */
    private static function featuredPack(int $days, float $euros): array
    {
        return [
            'name' => 'À la une · '.$days.' jours',
            'label' => 'Pendant '.$days.' jours',
            'duration_days' => $days,
            'price_points' => 0,
            'price_euros' => $euros,
            'description' => 'Pendant '.$days.' jours, votre offre porte la mention « Sponsorisé » et passe avant les offres non sponsorisées sur l’accueil des clients et dans la liste des annonces.',
            'features' => [
                'Mention « Sponsorisé » sur votre offre pendant '.$days.' jours',
                'Affichée avant les offres non sponsorisées sur l’accueil des clients et dans la liste des annonces',
                'Pas de place fixe : les offres sponsorisées sont classées entre elles selon les règles de classement publiées',
            ],
            'icon' => 'fas fa-thumbtack',
            'color' => '#4338ca',
        ];
    }

    /** @return array<string, mixed> */
    private static function bumpPack(int $everyDays, int $durationDays, float $euros, string $label): array
    {
        return [
            'name' => 'Remontée automatique · '.mb_strtolower($label),
            'label' => $label,
            'every_days' => $everyDays,
            'duration_days' => $durationDays,
            'price_euros' => $euros,
        ];
    }
}

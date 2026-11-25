<?php

namespace App\Support;

/**
 * Comparatif « Gratuit / À la carte / Premium ».
 *
 * Source unique de la page des offres, de la page d'abonnement et du bloc
 * « comparatif » des e-mails : les trois affichent exactement les mêmes lignes.
 *
 * Chaque ligne décrit un effet réellement appliqué par le code, et chaque prix
 * est lu dans le catalogue en vigueur (App\Support\AdPromotionCatalog,
 * App\Support\ProviderSubscriptionPlans). Rien n'est écrit en dur : quand les
 * règles de mise en avant changent, le comparatif change avec elles.
 */
class OfferComparison
{
    public const NOT_INCLUDED = 'Non inclus';

    public const INCLUDED = 'Inclus';

    /** L'abonnement est-il réellement en vente ? */
    public static function premiumOnSale(): bool
    {
        return PlatformFeatures::proSubscriptionsEnabled() && self::monthlyAmount() >= 0.5;
    }

    /** « 9,99 € par mois », ou null si l'abonnement n'est pas en vente. */
    public static function premiumPrice(): ?string
    {
        return self::premiumOnSale() ? self::euros(self::monthlyAmount()).' par mois' : null;
    }

    /**
     * Sections du comparatif.
     *
     * @return list<array{title: string, rows: list<array{label: string, free: string, unit: string, premium: string}>}>
     */
    public static function sections(): array
    {
        $newRules = AdPromotionCatalog::newRulesActive();
        $where = $newRules
            ? 'sur l’accueil des clients et dans la liste des annonces'
            : 'sur l’accueil des clients';

        $visibility = [
            self::row('Photos par offre', '2 photos', '2 photos', '4 photos'),
            self::row(
                'Offre affichée avant les offres non sponsorisées '.$where.', avec la mention « Sponsorisé »',
                self::NOT_INCLUDED,
                self::featuredPrices(),
                'Toutes vos offres, tant que l’abonnement est actif'
            ),
        ];
        if ($newRules) {
            $visibility[] = self::row(
                'Remontée automatique : l’offre revient en haut des offres non sponsorisées',
                self::NOT_INCLUDED,
                self::bumpPrices(),
                'Toutes vos offres, chaque semaine, sans achat'
            );
        }
        $visibility[] = self::row(
            'Demande signalée « Urgent »',
            self::NOT_INCLUDED,
            self::urgentPrice(false),
            self::urgentPrice(true).' (réduction de 20 %)'
        );
        $visibility[] = self::row(
            'Profil proposé à un client après sa demande',
            'Selon la recherche',
            'Selon la recherche',
            'Présenté avant les prestataires non abonnés'
        );
        $visibility[] = self::row('Badge « Premium » sur votre profil public', self::NOT_INCLUDED, self::NOT_INCLUDED, self::INCLUDED);

        return [
            [
                'title' => 'Publier et échanger',
                'rows' => [
                    self::row('Publier vos offres et vos demandes, échanger par la messagerie', self::INCLUDED, self::INCLUDED, self::INCLUDED),
                ],
            ],
            ['title' => 'Être vu', 'rows' => $visibility],
            [
                'title' => 'Gagner du temps sur vos devis et vos factures',
                'rows' => [
                    // Quota réel : App\Services\CommercialDocumentAllowance (décision du 24/11/2026).
                    self::row('Devis et factures de l’espace Pro, devis accepté en ligne par votre client',
                        self::freeDocuments(), self::freeDocuments(), 'Sans limite'),
                    self::row('Modèles de devis et relance des devis sans réponse', self::NOT_INCLUDED, self::NOT_INCLUDED, self::INCLUDED),
                    self::row('Votre logo sur vos devis et vos factures', self::NOT_INCLUDED, self::NOT_INCLUDED, self::INCLUDED),
                    self::row('Rappel automatique de vos factures impayées', self::NOT_INCLUDED, self::NOT_INCLUDED, self::INCLUDED),
                    self::row(
                        'Rapport d’activité mensuel',
                        self::NOT_INCLUDED,
                        self::NOT_INCLUDED,
                        'Chiffres mesurés, comparaison au mois précédent, récapitulatif par e-mail'
                    ),
                ],
            ],
        ];
    }

    /**
     * Le calcul qui justifie l'abonnement, écrit seulement s'il est vrai aux
     * tarifs en vigueur et si l'abonnement est en vente.
     */
    public static function argument(): ?string
    {
        $thirtyDays = (float) (AdPromotionCatalog::boosts()['boost_30']['price_euros'] ?? 0);
        $monthly = self::monthlyAmount();

        if (! self::premiumOnSale() || $thirtyDays <= 0 || $monthly >= $thirtyDays) {
            return null;
        }

        return 'Mettre une seule offre en avant pendant 30 jours coûte '.self::euros($thirtyDays)
            .' à la carte. Premium coûte '.self::euros($monthly)
            .' par mois et met en avant toutes vos offres.';
    }

    /** Limites dites en clair, sous le comparatif. */
    public static function notes(): array
    {
        return array_values(array_filter([
            'Les options à la carte se règlent par offre ou par demande, sans abonnement.',
            'Une mise en avant ne donne pas de place fixe et ne garantit ni demande, ni mission, ni revenu.',
            'Le nombre d’annonces actives dépend du statut du compte : Premium ne rend pas les publications illimitées.',
            'Sans abonnement, chaque membre, particulier ou professionnel, crée '.self::freeDocuments().', sans justificatif ; Premium lève la limite.',
            self::oneTimeNote(),
        ]));
    }

    /**
     * Premium sans abonnement : l'accès à paiement unique, à ses prix en vigueur.
     * Null tant qu'il n'est pas réellement en vente.
     */
    private static function oneTimeNote(): ?string
    {
        $offers = self::premiumOnSale() ? ProviderSubscriptionPlans::oneTimeSentence() : null;

        return $offers === null ? null
            : 'Premium se prend aussi sans abonnement, par un paiement unique par carte : '.$offers
                .'. Mêmes avantages, aucun prélèvement ensuite : l’accès s’arrête seul à la fin de la période.';
    }

    /** « 2 devis et 2 factures offerts », lu dans App\Services\CommercialDocumentAllowance. */
    private static function freeDocuments(): string
    {
        $free = \App\Services\CommercialDocumentAllowance::FREE;

        return $free['quote'].' devis et '.$free['invoice'].' factures offerts';
    }

    private static function row(string $label, string $free, string $unit, string $premium): array
    {
        return ['label' => $label, 'free' => $free, 'unit' => $unit, 'premium' => $premium];
    }

    /** « 5,90 € (7 jours) ou 14,90 € (30 jours), par offre ». */
    private static function featuredPrices(): string
    {
        $packs = collect(AdPromotionCatalog::boosts())
            ->map(fn (array $pack) => self::euros((float) $pack['price_euros']).' ('.$pack['duration_days'].' jours)')
            ->values();

        if ($packs->isEmpty()) {
            return self::NOT_INCLUDED;
        }

        return ($packs->count() > 2
            ? 'De '.$packs->first().' à '.$packs->last()
            : $packs->implode(' ou ')).', par offre';
    }

    private static function bumpPrices(): string
    {
        $cheapest = collect(AdPromotionCatalog::bumps())->min('price_euros');

        return $cheapest === null ? self::NOT_INCLUDED : 'À partir de '.self::euros((float) $cheapest).', par offre';
    }

    private static function urgentPrice(bool $subscriber): string
    {
        $urgent = AdPromotionCatalog::urgent();
        $price = AdPromotionCatalog::discountedCents((float) $urgent['price_euros'], $subscriber) / 100;
        $duration = $urgent['duration_days'] === null
            ? 'pour toute la durée de la demande'
            : 'pour '.$urgent['duration_days'].' jours';

        return self::euros($price).' '.$duration;
    }

    private static function monthlyAmount(): float
    {
        return ProviderSubscriptionPlans::amount('monthly');
    }

    private static function euros(float $amount): string
    {
        return number_format($amount, 2, ',', ' ').' €';
    }
}

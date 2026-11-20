<?php

namespace App\Services\Feed;

use App\Support\Territory;
use Carbon\CarbonImmutable;

/**
 * Ligne de critère de classement du flux client (article D111-6 II du code
 * de la consommation). Elle décrit l'ordre réellement appliqué par
 * App\Services\ClientFeedOffersService, sans rien y ajouter :
 *  1. les offres promues (boost actif ou abonnement Premium actif de
 *     l'auteur) passent en premier ;
 *  2. dans chaque groupe, deux offres au plus par prestataire avant ses
 *     offres suivantes ;
 *  3. les catégories suivies par le lecteur, s'il en a ;
 *  4. puis les plus récentes d'abord.
 */
final class RankingDisclosure
{
    /**
     * @return array{text: string, details: string, link_label: string, url: string}
     */
    public function forClientFeed(?Territory $territory, bool $includesRemote = false, bool $viewerHasCategories = false, ?string $nearbyLabel = null, bool $completedElsewhere = false): array
    {
        // $nearbyLabel : portée « autour de vous » réellement appliquée (?scope=nearby), par exemple « de Mamoudzou et alentours ».
        $scope = $territory ? 'offres '.$territory->withDe() : ($nearbyLabel !== null ? 'offres '.$nearbyLabel : 'offres de toutes les zones');

        $details = ['Deux offres au plus par prestataire passent avant ses offres suivantes.'];
        if ($territory && $includesRemote) {
            $details[] = 'Les offres « À distance » d’autres territoires sont incluses.';
        }
        if (\App\Support\AdPromotionCatalog::newRulesActive()) {
            $details[] = 'Une offre « Remontée » a payé pour être reclassée parmi les plus récentes.';
        }
        // $completedElsewhere : la grille de cette page est réellement complétée par des offres d'ailleurs.
        if ($territory && $completedElsewhere) {
            $details[] = 'La fin de la liste est complétée par des offres d’autres territoires, signalées « Autre territoire ».';
        }

        return [
            // L'ordre est énoncé dans l'ordre réel du tri : sponsorisées, puis catégories suivies, puis date.
            'text' => 'Classement : '.$scope.'. Les offres sponsorisées (payantes) passent en premier, puis '
                .($viewerHasCategories ? 'celles des catégories que vous suivez, puis ' : '')
                .'les plus récentes.',
            'details' => implode(' ', $details),
            'link_label' => 'En savoir plus',
            'url' => route('legal.platform-rules').'#classement',
        ];
    }

    /**
     * Date d'effet annoncée des nouvelles règles de mise en avant (« À la une »,
     * « Remontée automatique », Urgent pour toute la durée de la demande),
     * lue dans config('feed.sponsored.effective_at') (FEED_SPONSORED_EFFECTIVE_AT).
     * Formats acceptés : AAAA-MM-JJ ou JJ/MM/AAAA. Vide ou illisible : aucune
     * date n'est inventée.
     */
    public function sponsoredEffectiveDate(): ?CarbonImmutable
    {
        $value = config('feed.sponsored.effective_at');
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        // Lecture stricte, avec contrôle aller-retour : 2026-02-30, « tomorrow » ou une
        // date ambiguë sont rejetés ; 05/12/2026 se lit 5 décembre (format français).
        $value = trim($value);
        $timezone = new \DateTimeZone((string) config('app.timezone', 'UTC'));
        foreach (['!Y-m-d', '!d/m/Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value, $timezone);
            if ($date && $date->format(ltrim($format, '!')) === $value) {
                return CarbonImmutable::instance($date)->startOfDay();
            }
        }

        return null;
    }

    /** « à compter du 25/11/2026 », ou la formule d'attente si la date n'est pas fixée. */
    public function sponsoredEffectiveLabel(): string
    {
        $date = $this->sponsoredEffectiveDate();

        return $date
            ? 'à compter du '.$date->format('d/m/Y')
            : 'à une date annoncée au moins 15 jours à l’avance';
    }
}

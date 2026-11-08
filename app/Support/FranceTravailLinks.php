<?php

namespace App\Support;

/**
 * Liens vers le site France Travail, propres à un territoire.
 *
 * France Travail n'est pas un partenaire de Prokejem : les liens mènent à son
 * site public, la recherche et la candidature s'y poursuivent. Aucune offre
 * n'est reprise ni comptée ici.
 */
final class FranceTravailLinks
{
    public const SEARCH_URL = 'https://candidat.francetravail.fr/offres/recherche';

    /** Recherche d'offres limitée au territoire (« lieux=976D »), ou générale si le territoire est inconnu. */
    public static function searchUrl(?Territory $territory): string
    {
        if ($territory === null || $territory->ftLieux === '') {
            return self::SEARCH_URL;
        }

        return self::SEARCH_URL.'?'.http_build_query(['lieux' => $territory->ftLieux]);
    }

    /** « Offres d'emploi à Mayotte sur France Travail », ou la formule générale. */
    public static function searchLabel(?Territory $territory): string
    {
        return $territory === null
            ? 'Offres d’emploi sur France Travail'
            : 'Offres d’emploi '.$territory->withPreposition().' sur France Travail';
    }
}

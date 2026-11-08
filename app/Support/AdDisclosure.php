<?php

namespace App\Support;

use App\Models\Ad;
use App\Models\User;

/**
 * Mentions affichées sur les cartes du feed (articles L111-7 et D111-7 du
 * code de la consommation) : une seule définition, partagée par
 * feed/partials/ad-card et feed/partials/ad-row.
 *
 * Rien n'est estimé : une mention n'apparaît que si la donnée existe.
 */
final class AdDisclosure
{
    public const SPONSORED_LABEL = 'Sponsorisé';

    public const SPONSORED_SCREEN_READER = 'le prestataire a payé pour être affiché ici';

    public const PAID_URGENT_LABEL = 'Urgent · option payante';

    public const FREE_URGENT_LABEL = 'Urgent';

    /**
     * Offre mise en avant contre paiement : boost acheté actif sur l'annonce,
     * ou abonnement Premium actif de l'auteur (colonne calculée
     * feed_is_promoted de App\Services\ClientFeedOffersService). Un boost
     * accordé gratuitement par l'administration (boost_source = admin) n'est
     * pas présenté comme payé.
     */
    public static function isSponsored(Ad $ad): bool
    {
        return (bool) ($ad->feed_is_promoted ?? false)
            || ($ad->isCurrentlyBoosted() && $ad->boost_source !== 'admin');
    }

    /**
     * Libellé « Urgent ». L'option payante renseigne urgent_until et devient
     * « Urgent · option payante ». L'urgence accordée gratuitement par
     * l'administration (urgent_source = admin) et l'urgence choisie au dépôt,
     * sans échéance, restent « Urgent ».
     */
    public static function urgentLabel(Ad $ad): ?string
    {
        if (! $ad->isCurrentlyUrgent()) {
            return null;
        }

        return ($ad->urgent_until !== null && $ad->urgent_source !== 'admin') ? self::PAID_URGENT_LABEL : self::FREE_URGENT_LABEL;
    }

    /**
     * Statut déclaré de l'auteur : « Professionnel » ou « Particulier ».
     * Lu dans users.account_type (choisi à l'inscription ou dans l'espace
     * pro), avec users.user_type pour les comptes professionnels plus anciens.
     * Statut absent ou inconnu : aucune mention.
     */
    public static function advertiserStatus(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $accountType = mb_strtolower(trim((string) $user->account_type));
        $userType = mb_strtolower(trim((string) $user->user_type));

        if ($accountType === 'professionnel' || $userType === 'professionnel') {
            return 'Professionnel';
        }

        if ($accountType === 'particulier') {
            return 'Particulier';
        }

        return null;
    }
}

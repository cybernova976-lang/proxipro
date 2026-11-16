<?php

namespace App\Support;

/**
 * Libellés français des états enregistrés en base (en anglais) :
 * l'interface ne doit jamais afficher « Active », « Cancelled » ou « Past_due ».
 * Une valeur inconnue reste lisible plutôt que de disparaître.
 */
final class StatusLabels
{
    private const AD = [
        'active' => 'Active',
        'pending' => 'En attente',
        'expired' => 'Expirée',
        'rejected' => 'Refusée',
        'inactive' => 'Désactivée',
        'draft' => 'Brouillon',
        'paused' => 'En pause',
        'archived' => 'Archivée',
    ];

    private const PROVIDER_SUBSCRIPTION = [
        'active' => 'Actif',
        'pending' => 'Suspendu ou en attente',
        'cancelled' => 'Annulé',
        'expired' => 'Expiré',
    ];

    /** États renvoyés par Stripe pour un abonnement. */
    private const STRIPE_SUBSCRIPTION = [
        'active' => 'Actif chez Stripe',
        'trialing' => 'Période d’essai',
        'past_due' => 'Paiement en retard',
        'unpaid' => 'Impayé',
        'canceled' => 'Annulé chez Stripe',
        'incomplete' => 'Paiement incomplet',
        'incomplete_expired' => 'Paiement abandonné',
        'paused' => 'En pause chez Stripe',
    ];

    public static function ad(?string $status): string
    {
        return self::label(self::AD, $status);
    }

    public static function providerSubscription(?string $status): string
    {
        return self::label(self::PROVIDER_SUBSCRIPTION, $status);
    }

    public static function stripeSubscription(?string $status): string
    {
        return self::label(self::STRIPE_SUBSCRIPTION, $status);
    }

    private static function label(array $labels, ?string $status): string
    {
        if ($status === null || $status === '') {
            return '—';
        }

        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
}

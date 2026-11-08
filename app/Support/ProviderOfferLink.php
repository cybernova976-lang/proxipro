<?php

namespace App\Support;

use App\Models\User;

/**
 * Destination du lien « Proposer mes services », selon le visiteur :
 *  - visiteur : inscription en tant que professionnel ;
 *  - prestataire : publication d'une offre (/ads/create) ;
 *  - autre membre : activation du profil prestataire.
 */
final class ProviderOfferLink
{
    public static function url(?User $user): string
    {
        if ($user === null) {
            return route('register', ['account_type' => 'professionnel']);
        }

        if ($user->isServiceProvider() || $user->isProfessionnel()) {
            return route('ads.create');
        }

        return route('service-provider.start');
    }
}

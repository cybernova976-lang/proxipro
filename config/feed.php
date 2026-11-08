<?php

/*
|--------------------------------------------------------------------------
| Accueil connecté (/feed)
|--------------------------------------------------------------------------
|
| Réglages du flux. Aucun chiffre affiché n'est tiré d'ici : ce fichier ne
| fixe que des règles (portée, comptes masqués, date d'effet annoncée).
|
*/

$parseIds = static fn ($value): array => array_values(array_unique(array_filter(
    array_map('intval', explode(',', (string) $value)),
    static fn (int $id) => $id > 0
)));

return [
    'client' => [
        // Nombre d'offres par page dans le flux client.
        'page_size' => 6,

        // Portée par défaut du flux client : le territoire du lecteur.
        // « ?scope=all » affiche toutes les zones.
        'default_scope' => 'territory',

        // Les offres d'autres territoires déclarées « À distance »
        // (ad_details.intervention_mode = remote) restent visibles.
        'include_remote_offers' => true,
    ],

    // Comptes internes ou de test dont les annonces sont retirées des pages
    // publiques (flux client, vitrine de l'accueil public, liste /ads, rail
    // des prestataires). Identifiants séparés par des virgules, vide par défaut.
    'hidden_author_ids' => $parseIds(env('FEED_HIDDEN_AUTHOR_IDS', '')),

    'sponsored' => [
        // Mécanisme de mise en avant en vigueur : « legacy » = ordre actuel
        // (toutes les offres promues d'abord).
        'mechanism' => 'legacy',

        // Date d'effet annoncée du futur mécanisme de places sponsorisées
        // (au moins 15 jours après l'annonce). Format AAAA-MM-JJ ou
        // JJ/MM/AAAA ; toute autre saisie est ignorée. Vide = date non fixée.
        'effective_at' => env('FEED_SPONSORED_EFFECTIVE_AT') ?: null,
    ],
];

<?php

return [
    'commission_percent' => (float) env('MARKETPLACE_COMMISSION_PERCENT', 10),
    'currency' => env('MARKETPLACE_CURRENCY', 'eur'),

    /*
    | Purge nocturne des comptes supprimés (cleanup:orphaned-data --force).
    | Désactivée par défaut : elle efface définitivement, sans délai de grâce,
    | chaque compte supprimé ET ses commandes (clé étrangère en cascade), donc
    | aussi l'historique de l'autre partie et des pièces comptables. À
    | n'activer qu'une fois la politique de conservation décidée.
    */
    'purge_deleted_accounts' => (bool) env('ACCOUNT_PURGE_ENABLED', false),
];

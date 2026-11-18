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

    /*
    | Rappels au client qui a payé mais n'a pas confirmé la fin de la
    | prestation : premier rappel N jours après la date souhaitée (ou le
    | paiement), puis tous les M jours, K fois au plus. Aucun versement
    | automatique : seul le client confirme.
    */
    'release_reminders' => [
        'first_after_days' => (int) env('RELEASE_REMINDER_FIRST_AFTER_DAYS', 3),
        'every_days' => (int) env('RELEASE_REMINDER_EVERY_DAYS', 3),
        'max' => (int) env('RELEASE_REMINDER_MAX', 3),
    ],
];

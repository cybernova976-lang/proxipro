<?php

/*
 * Réglages du parcours de demande.
 *
 * assist_whatsapp : bouton « Publier avec de l’aide » du parcours guidé (décision G8).
 * Désactivé par défaut. Le numéro n'a AUCUNE valeur par défaut : sans numéro
 * valide (8 à 15 chiffres, indicatif compris, sans « + ») ou sans horaires,
 * rien n'est affiché, même si le drapeau est actif.
 */
return [
    'assist_whatsapp' => [
        'enabled' => (bool) env('DEMAND_ASSIST_WHATSAPP_ENABLED', false),
        'number' => env('DEMAND_ASSIST_WHATSAPP_NUMBER'),
        // Exemple : « du lundi au vendredi, 8 h – 17 h ».
        'hours' => env('DEMAND_ASSIST_WHATSAPP_HOURS'),
    ],
];

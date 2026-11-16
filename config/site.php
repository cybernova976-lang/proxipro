<?php

return [
    'support_email' => env('SUPPORT_EMAIL') ?: 'contact@prokejem.fr',
    // Aucun numéro par défaut : un téléphone n'est affiché au public que si
    // CONTACT_PHONE est défini, avec un numéro professionnel (jamais personnel).
    'contact_phone' => env('CONTACT_PHONE') ?: null,
    'contact_location' => env('CONTACT_LOCATION') ?: 'Mamoudzou, Mayotte',
    'response_time' => env('SUPPORT_RESPONSE_TIME', '24 à 48 heures ouvrées'),
    'social' => [
        'facebook' => env('SOCIAL_FACEBOOK_URL'),
        'instagram' => env('SOCIAL_INSTAGRAM_URL'),
        'linkedin' => env('SOCIAL_LINKEDIN_URL'),
        'youtube' => env('SOCIAL_YOUTUBE_URL'),
    ],
];

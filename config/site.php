<?php

return [
    'support_email' => env('SUPPORT_EMAIL') ?: 'contact@prokejem.fr',
    'contact_phone' => env('CONTACT_PHONE') ?: '+262 639 71 94 22',
    'contact_location' => env('CONTACT_LOCATION') ?: 'Mamoudzou, Mayotte',
    'response_time' => env('SUPPORT_RESPONSE_TIME', '24 à 48 heures ouvrées'),
    'social' => [
        'facebook' => env('SOCIAL_FACEBOOK_URL'),
        'instagram' => env('SOCIAL_INSTAGRAM_URL'),
        'linkedin' => env('SOCIAL_LINKEDIN_URL'),
        'youtube' => env('SOCIAL_YOUTUBE_URL'),
    ],
];

<?php

return [
    // Identité issue de l'attestation RNE du 23 octobre 2026.
    'entity_name' => env('LEGAL_ENTITY_NAME') ?: 'PROKEJEM',
    'entity_form' => env('LEGAL_ENTITY_FORM') ?: 'SASU — Société par actions simplifiée unipersonnelle',
    'registration_number' => env('LEGAL_REGISTRATION_NUMBER') ?: '131 775 942',
    'siret' => env('LEGAL_SIRET') ?: '131 775 942 00017',
    'share_capital' => env('LEGAL_SHARE_CAPITAL') ?: '1 EUR',
    'registration_date' => env('LEGAL_REGISTRATION_DATE') ?: '23/10/2026',
    'ape_code' => env('LEGAL_APE_CODE') ?: '6312Z — Portails Internet',
    'vat_number' => env('LEGAL_VAT_NUMBER'),
    'address' => env('LEGAL_ADDRESS') ?: '12 impasse Mroni Manga, Passamainti, 97600 Mamoudzou, Mayotte, France',
    'publication_director' => env('LEGAL_PUBLICATION_DIRECTOR') ?: 'Hardali SAID, président',
    // Coordonnées publiées par Railway : https://railway.com/legal/terms
    'host_name' => env('LEGAL_HOST_NAME') ?: 'Railway Corporation',
    'host_address' => env('LEGAL_HOST_ADDRESS') ?: '548 Market St Suite 68956, San Francisco, California 94104, États-Unis',
    'host_phone' => env('LEGAL_HOST_PHONE') ?: '+1 415 707 7675',
    'privacy_contact' => env('PRIVACY_CONTACT_EMAIL') ?: (env('SUPPORT_EMAIL') ?: 'contact@prokejem.fr'),
    'last_updated' => env('LEGAL_LAST_UPDATED') ?: '23/10/2026',
    'pro_terms_version' => env('PRO_TERMS_VERSION', '2026-07-19'),
];

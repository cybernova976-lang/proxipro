<?php

return [
    'entity_name' => env('LEGAL_ENTITY_NAME'),
    'entity_form' => env('LEGAL_ENTITY_FORM'),
    'registration_number' => env('LEGAL_REGISTRATION_NUMBER'),
    'vat_number' => env('LEGAL_VAT_NUMBER'),
    'address' => env('LEGAL_ADDRESS'),
    'publication_director' => env('LEGAL_PUBLICATION_DIRECTOR'),
    'host_name' => env('LEGAL_HOST_NAME'),
    'host_address' => env('LEGAL_HOST_ADDRESS'),
    'privacy_contact' => env('PRIVACY_CONTACT_EMAIL', env('SUPPORT_EMAIL')),
    'last_updated' => env('LEGAL_LAST_UPDATED'),
    'pro_terms_version' => env('PRO_TERMS_VERSION', '2026-07-17'),
];

<?php

/**
 * Configuration de l'administration
 * 
 * Ce fichier définit les paramètres de l'administration Lunamars
 * y compris l'administrateur principal avec tous les privilèges.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Administrateur Principal
    |--------------------------------------------------------------------------
    |
    | L'administrateur principal possède tous les droits et ne peut pas être
    | modifié, désactivé ou supprimé par d'autres administrateurs.
    | Seul lui peut nommer d'autres administrateurs et gérer leurs privilèges.
    |
    */
    'principal_admin' => [
        'email' => env('PRINCIPAL_ADMIN_EMAIL', 'hardali.soudj@gmail.com'),
        'name' => 'Hardali SAÏD',
    ],

    /*
    |--------------------------------------------------------------------------
    | Privilèges Administrateurs
    |--------------------------------------------------------------------------
    |
    | Liste des privilèges qui peuvent être attribués aux administrateurs
    | secondaires par l'administrateur principal.
    |
    */
    'privileges' => [
        'manage_users' => [
            'label' => 'Gérer les utilisateurs',
            'description' => 'Voir, modifier, activer/désactiver les utilisateurs',
        ],
        'manage_ads' => [
            'label' => 'Gérer les annonces',
            'description' => 'Modérer, approuver, rejeter ou supprimer les annonces',
        ],
        'manage_subscriptions' => [
            'label' => 'Gérer les abonnements',
            'description' => 'Voir et modifier les abonnements utilisateurs',
        ],
        'grant_premium' => [
            'label' => 'Accorder le Premium',
            'description' => 'Donner gratuitement le statut premium aux utilisateurs',
        ],
        'view_stats' => [
            'label' => 'Voir les statistiques',
            'description' => 'Accéder aux statistiques et rapports de la plateforme',
        ],
        'manage_settings' => [
            'label' => 'Gérer les paramètres',
            'description' => 'Modifier les paramètres globaux de la plateforme',
        ],
        'view_deleted' => [
            'label' => 'Voir les comptes supprimés',
            'description' => 'Accéder aux logs des comptes supprimés',
        ],
        'restore_accounts' => [
            'label' => 'Restaurer les comptes',
            'description' => 'Restaurer les comptes utilisateurs supprimés',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Types d'utilisateurs
    |--------------------------------------------------------------------------
    |
    | Distinction entre les types de comptes utilisateurs.
    |
    */
    'user_types' => [
        'particulier' => [
            'label' => 'Particulier',
            'color' => 'info',
            'icon' => 'fa-user',
        ],
        'professionnel' => [
            'label' => 'Professionnel',
            'color' => 'success',
            'icon' => 'fa-briefcase',
        ],
        'entreprise' => [
            'label' => 'Entreprise',
            'color' => 'primary',
            'icon' => 'fa-building',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plans d'abonnement
    |--------------------------------------------------------------------------
    */
    'plans' => [
        'FREE' => [
            'label' => 'Gratuit',
            'color' => 'secondary',
            'icon' => 'fa-user',
            'price' => 0,
        ],
        'STARTER' => [
            'label' => 'Starter',
            'color' => 'info',
            'icon' => 'fa-rocket',
            'price' => 9.99,
        ],
        'PRO' => [
            'label' => 'Pro',
            'color' => 'success',
            'icon' => 'fa-star',
            'price' => 19.99,
        ],
        'BUSINESS' => [
            'label' => 'Business',
            'color' => 'primary',
            'icon' => 'fa-building',
            'price' => 49.99,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tarification
    |--------------------------------------------------------------------------
    |
    | Configuration des tarifs et points.
    | Les abonnements ont été supprimés. Tout fonctionne avec des points.
    |
    */
    'pricing' => [
        'signup_points' => 5,
        'share_points' => 5,
        'boost_3_days' => ['price_eur' => 400, 'price_points' => 5],
        'boost_7_days' => ['price_eur' => 600, 'price_points' => 10],
        'boost_15_days' => ['price_eur' => 1000, 'price_points' => 20],
        'boost_30_days' => ['price_eur' => 1500, 'price_points' => 30],
        'refresh_ad' => ['price_points' => 10],
        'profile_verification' => ['price_eur' => 1000, 'price_points' => 20],
        'ad_publishing' => 'free',
    ],
];

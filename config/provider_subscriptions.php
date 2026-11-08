<?php

/*
| Avantages affichés à la vente : uniquement des effets réellement appliqués
| par le code (badge du profil public, mention « Sponsorisé » des offres sur
| l'accueil des clients, ordre des prestataires suggérés après une demande,
| 4 photos par annonce, réduction sur les boosts et l'option Urgent).
| Un texte enregistré depuis l'administration (réglage
| provider_subscription_plans) remplace ces valeurs par défaut.
*/

return [
    'plans' => [
        'monthly' => [
            'enabled' => true,
            'recommended' => false,
            'label' => 'Mensuel',
            'price' => '9,99€',
            'amount' => 9.99,
            'period' => '/mois',
            'original_price' => '',
            'badge' => '',
            'subtitle' => '',
            'description' => 'Accès complet pendant 1 mois, renouvelable.',
            'features' => [
                'Badge « Premium » sur votre profil public',
                'Vos offres signalées « Sponsorisé » et affichées avant les offres non sponsorisées sur l’accueil des clients, sans place fixe',
                'Profil présenté avant celui des prestataires non abonnés dans la liste proposée après une demande',
                'Gestion de clientèle et Espace Pro',
                'Historique de facturation Stripe',
                'Jusqu\'à 4 photos par annonce',
                'Réduction de 20 % sur les boosts et l’option Urgent',
                'Alertes dans la plateforme',
            ],
        ],
        'annual' => [
            'enabled' => true,
            'recommended' => true,
            'label' => 'Annuel',
            'price' => '85€',
            'amount' => 85.00,
            'period' => '/an',
            'original_price' => '119,88€',
            'badge' => 'RECOMMANDÉ · -30%',
            'subtitle' => 'soit 7,08€/mois',
            'description' => 'Accès complet pendant 1 an.',
            'features' => [
                'Tout le plan mensuel inclus',
                'Mention « Sponsorisé » sur vos offres pendant les 12 mois de l’abonnement',
                'Jusqu\'à 4 photos par annonce',
                'Assistance via formulaire de contact',
            ],
        ],
    ],
];

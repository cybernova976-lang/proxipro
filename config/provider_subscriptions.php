<?php

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
                'Badge Premium visible',
                'Priorité dans les suggestions de prestataires',
                'Gestion de clientèle et Espace Pro',
                'Historique de facturation Stripe',
                'Jusqu\'à 4 photos par annonce',
                'Alertes dans la plateforme',
                'Support prioritaire',
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
                'Priorité renforcée dans les résultats',
                'Mise en avant pendant 12 mois',
                'Badge « Premium annuel »',
                'Jusqu\'à 4 photos par annonce',
                'Export comptable',
                'Assistance via formulaire de contact',
            ],
        ],
    ],
];

<?php

/*
| Avantages affichés à la vente : uniquement des effets réellement appliqués
| par le code (badge du profil public, mention « Sponsorisé » des offres sur
| l'accueil des clients, ordre des prestataires suggérés après une demande,
| 4 photos par annonce, réduction sur les boosts et l'option Urgent, rapport
| d'activité mensuel, modèles de devis et relance automatique des devis, logo
| sur les devis et factures, rappel unique des factures impayées).
| Ce qui est ouvert à tout prestataire sans abonnement (Espace Pro, carnet de
| clients, alertes) n'y figure pas : ce ne sont pas des avantages payants.
| Une seule formule à prélèvement, mensuelle : l'annuelle n'apportait aucun
| avantage réel et a été retirée le 19/11/2026.
| Depuis le 25/11/2026, Premium se prend aussi sans abonnement : un accès à
| paiement unique (billing = one_time) de 3 ou 6 mois, réglé une fois par
| carte, sans prélèvement ni reconduction. Il ouvre exactement les avantages
| de la formule mensuelle : il n'a donc pas de liste d'avantages à lui.
| Les clés « billing » et « months » décrivent la nature de la formule : elles
| ne se modifient pas depuis l'administration, qui règle le prix et
| l'activation (réglage provider_subscription_plans).
*/

return [
    // Rappel envoyé au membre avant la fin d'un accès à paiement unique, en jours.
    'one_time_reminder_days' => 7,

    'plans' => [
        'monthly' => [
            'billing' => 'recurring',
            'months' => 1,
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
                'Jusqu\'à 4 photos par annonce',
                'Réduction de 20 % sur les boosts et l’option Urgent',
                'Rapport d’activité mensuel : vues de profil, contacts, missions et montants mesurés, avec récapitulatif par e-mail',
                'Modèles de devis et relance automatique des devis sans réponse (comptes professionnels)',
                'Votre logo sur vos devis et vos factures (comptes professionnels)',
                'Rappel automatique de vos factures impayées (comptes professionnels)',
            ],
        ],

        // Prix confirmés par le fondateur le 25/11/2026 (27 € pour 3 mois, 49 € pour 6 mois).
        // Ils se changent ici (valeur par défaut) ou dans l'administration, page
        // Abonnements, bloc « Accès à paiement unique » (le réglage enregistré l'emporte).
        'pass_3m' => [
            'billing' => 'one_time',
            'months' => 3,
            'enabled' => true,
            'recommended' => false,
            'label' => '3 mois',
            'price' => '27,00€',
            'amount' => 27.00,
            'period' => 'paiement unique',
            'original_price' => '',
            'badge' => '',
            'subtitle' => '',
            'description' => 'Accès Premium pendant 3 mois. Paiement unique par carte, sans abonnement ni prélèvement.',
            'features' => [],
        ],
        'pass_6m' => [
            'billing' => 'one_time',
            'months' => 6,
            'enabled' => true,
            'recommended' => false,
            'label' => '6 mois',
            'price' => '49,00€',
            'amount' => 49.00,
            'period' => 'paiement unique',
            'original_price' => '',
            'badge' => '',
            'subtitle' => '',
            'description' => 'Accès Premium pendant 6 mois. Paiement unique par carte, sans abonnement ni prélèvement.',
            'features' => [],
        ],
    ],
];

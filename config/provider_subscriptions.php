<?php

/*
| Avantages affichés à la vente : uniquement des effets réellement appliqués
| par le code (badge du profil public, mention « Sponsorisé » des offres sur
| l'accueil des clients, ordre des prestataires suggérés après une demande,
| 4 photos par annonce, réduction sur les boosts et l'option Urgent, rapport
| d'activité mensuel, modèles de devis et relance automatique des devis).
| Ce qui est ouvert à tout prestataire sans abonnement (Espace Pro, carnet de
| clients, alertes) n'y figure pas : ce ne sont pas des avantages payants.
| Une seule formule, mensuelle : l'annuelle n'apportait aucun avantage réel
| et a été retirée le 19/11/2026.
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
                'Jusqu\'à 4 photos par annonce',
                'Réduction de 20 % sur les boosts et l’option Urgent',
                'Rapport d’activité mensuel : vues de profil, contacts, missions et montants mesurés, avec récapitulatif par e-mail',
                'Modèles de devis et relance automatique des devis sans réponse (comptes professionnels)',
            ],
        ],
    ],
];

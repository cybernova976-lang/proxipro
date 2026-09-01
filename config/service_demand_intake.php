<?php

return [
    'category_profiles' => [
        'Bricolage & Travaux' => 'property_work',
        'Jardinage & Extérieur' => 'outdoor_work',
        'Nettoyage & Entretien' => 'recurring_help',
        'Aide à domicile' => 'recurring_help',
        'Cours & Formation' => 'learning',
        'Beauté & Bien-être' => 'appointment',
        'Événements & Spectacles' => 'event',
        'Transport & Déménagement' => 'transport',
        'Informatique & Tech' => 'tech',
        'Artisanat & Création' => 'custom_project',
        'Santé & Social' => 'appointment',
        'Automobile & Mécanique' => 'automotive',
        'Immobilier & Architecture' => 'property_work',
        'Services juridiques & Administratifs' => 'professional_advice',
        'Agriculture & Élevage' => 'outdoor_work',
        'Restauration & Alimentation' => 'event',
        'Sports & Fitness' => 'learning',
        'Commerce & Marchandises' => 'commerce',
    ],

    'profiles' => [
        'property_work' => [
            'introduction' => 'Le type d’intervention et le lieu permettent aux artisans de juger rapidement si la mission leur correspond.',
            'fields' => [
                'work_scope' => [
                    'label' => 'Quel résultat attendez-vous ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir le type d’intervention',
                    'options' => [
                        'diagnosis' => 'Diagnostic ou conseil',
                        'repair' => 'Réparation ou dépannage',
                        'installation' => 'Installation neuve',
                        'renovation' => 'Rénovation ou transformation',
                    ],
                ],
                'site_type' => [
                    'label' => 'Où se situe l’intervention ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir le type de lieu',
                    'options' => [
                        'apartment' => 'Appartement',
                        'house' => 'Maison',
                        'business' => 'Local professionnel',
                        'outdoor' => 'Terrain ou extérieur',
                    ],
                ],
            ],
        ],
        'outdoor_work' => [
            'introduction' => 'La surface et les moyens déjà disponibles aident le prestataire à prévoir la durée et le matériel.',
            'fields' => [
                'area_band' => [
                    'label' => 'Quelle est la surface approximative ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir une surface',
                    'options' => [
                        'small' => 'Moins de 100 m²',
                        'medium' => '100 à 500 m²',
                        'large' => 'Plus de 500 m²',
                        'unknown' => 'Je ne sais pas encore',
                    ],
                ],
                'resources_available' => [
                    'label' => 'Le matériel principal est-il disponible sur place ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir une réponse',
                    'options' => [
                        'yes' => 'Oui',
                        'partial' => 'En partie',
                        'no' => 'Non, le prestataire doit venir équipé',
                        'unknown' => 'À déterminer avec le prestataire',
                    ],
                ],
            ],
        ],
        'recurring_help' => [
            'introduction' => 'Indiquez le rythme envisagé pour recevoir des réponses adaptées à une intervention ponctuelle ou régulière.',
            'fields' => [
                'frequency' => [
                    'label' => 'À quelle fréquence avez-vous besoin d’aide ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir une fréquence',
                    'options' => [
                        'once' => 'Une seule fois',
                        'weekly' => 'Une fois par semaine',
                        'several_week' => 'Plusieurs fois par semaine',
                        'monthly' => 'Quelques fois par mois',
                        'continuous' => 'Besoin régulier à définir',
                    ],
                ],
                'duration_band' => [
                    'label' => 'Quelle durée prévoyez-vous par intervention ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir une durée',
                    'options' => [
                        'short' => 'Moins de 2 heures',
                        'half_day' => 'Une demi-journée',
                        'day' => 'Une journée',
                        'several_days' => 'Plusieurs jours',
                        'unknown' => 'À définir ensemble',
                    ],
                ],
            ],
        ],
        'learning' => [
            'introduction' => 'Le format et le rythme souhaités permettent de proposer un accompagnement réellement compatible.',
            'fields' => [
                'session_mode' => [
                    'label' => 'Quel format préférez-vous ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir un format',
                    'options' => [
                        'customer_location' => 'À mon domicile',
                        'provider_location' => 'Chez le prestataire',
                        'remote' => 'À distance',
                        'outdoor' => 'En extérieur',
                        'flexible' => 'Je suis flexible',
                    ],
                ],
                'frequency' => [
                    'label' => 'Quel rythme recherchez-vous ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir un rythme',
                    'options' => [
                        'once' => 'Une séance',
                        'weekly' => 'Une fois par semaine',
                        'several_week' => 'Plusieurs fois par semaine',
                        'intensive' => 'Stage ou programme intensif',
                        'flexible' => 'À définir ensemble',
                    ],
                ],
            ],
        ],
        'appointment' => [
            'introduction' => 'Précisez le bénéficiaire et le lieu souhaité afin d’éviter les prises de contact incompatibles.',
            'fields' => [
                'beneficiary' => [
                    'label' => 'Pour qui est cette prestation ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir le bénéficiaire',
                    'options' => [
                        'self' => 'Pour moi',
                        'child' => 'Pour un enfant',
                        'adult' => 'Pour un autre adulte',
                        'family' => 'Pour plusieurs personnes',
                    ],
                ],
                'service_location' => [
                    'label' => 'Où souhaitez-vous la prestation ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir un lieu',
                    'options' => [
                        'customer_location' => 'À domicile',
                        'provider_location' => 'Chez le prestataire',
                        'facility' => 'Dans un établissement',
                        'remote' => 'À distance',
                        'flexible' => 'Je suis flexible',
                    ],
                ],
            ],
        ],
        'event' => [
            'introduction' => 'Le type d’événement et le nombre de personnes permettent d’estimer les moyens nécessaires.',
            'fields' => [
                'event_type' => [
                    'label' => 'Quel type d’événement préparez-vous ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir un événement',
                    'options' => [
                        'private' => 'Événement privé',
                        'ceremony' => 'Mariage ou cérémonie',
                        'professional' => 'Événement professionnel',
                        'cultural' => 'Événement culturel ou associatif',
                        'other' => 'Autre',
                    ],
                ],
                'guest_band' => [
                    'label' => 'Combien de personnes sont prévues ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir une estimation',
                    'options' => [
                        'small' => '1 à 20 personnes',
                        'medium' => '21 à 60 personnes',
                        'large' => '61 à 150 personnes',
                        'very_large' => 'Plus de 150 personnes',
                        'unknown' => 'Pas encore défini',
                    ],
                ],
            ],
        ],
        'transport' => [
            'introduction' => 'Le volume et les contraintes d’accès sont essentiels pour proposer le bon véhicule et la bonne équipe.',
            'fields' => [
                'load_size' => [
                    'label' => 'Quel volume faut-il transporter ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir un volume',
                    'options' => [
                        'few_items' => 'Quelques objets ou colis',
                        'car_load' => 'L’équivalent d’une voiture',
                        'utility' => 'Un utilitaire',
                        'large_move' => 'Un logement complet',
                        'passenger' => 'Transport de personnes',
                    ],
                ],
                'access' => [
                    'label' => 'Quelle est la principale contrainte d’accès ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir une situation',
                    'options' => [
                        'easy' => 'Accès facile ou rez-de-chaussée',
                        'elevator' => 'Étage avec ascenseur',
                        'stairs' => 'Étage sans ascenseur',
                        'distance' => 'Stationnement éloigné',
                        'unknown' => 'À vérifier',
                    ],
                ],
            ],
        ],
        'tech' => [
            'introduction' => 'Le type d’équipement et le mode d’assistance orientent votre demande vers le bon spécialiste.',
            'fields' => [
                'device_type' => [
                    'label' => 'Quel équipement est concerné ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir un équipement',
                    'options' => [
                        'computer' => 'Ordinateur',
                        'phone' => 'Téléphone',
                        'tablet' => 'Tablette',
                        'network' => 'Internet ou réseau',
                        'software' => 'Logiciel ou site web',
                        'other' => 'Autre équipement',
                    ],
                ],
                'support_mode' => [
                    'label' => 'Comment souhaitez-vous être aidé ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir un mode',
                    'options' => [
                        'onsite' => 'Intervention sur place',
                        'remote' => 'Assistance à distance',
                        'dropoff' => 'Dépôt de l’équipement',
                        'flexible' => 'Selon le diagnostic',
                    ],
                ],
            ],
        ],
        'custom_project' => [
            'introduction' => 'L’état d’avancement et la quantité permettent à l’artisan de comprendre le niveau de préparation du projet.',
            'fields' => [
                'project_stage' => [
                    'label' => 'Où en est votre projet ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir une étape',
                    'options' => [
                        'idea' => 'J’ai une idée à préciser',
                        'measurements' => 'J’ai les dimensions ou un cahier des charges',
                        'model' => 'J’ai un modèle ou un visuel',
                        'repair' => 'Je souhaite réparer un objet',
                        'ready' => 'Le projet est prêt à fabriquer',
                    ],
                ],
                'quantity_band' => [
                    'label' => 'Quelle quantité envisagez-vous ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir une quantité',
                    'options' => [
                        'one' => 'Une pièce',
                        'small' => '2 à 10 pièces',
                        'medium' => '11 à 50 pièces',
                        'series' => 'Petite série de plus de 50 pièces',
                        'unknown' => 'À définir',
                    ],
                ],
            ],
        ],
        'professional_advice' => [
            'introduction' => 'La nature de l’accompagnement et le délai permettent de trouver un professionnel réellement disponible.',
            'fields' => [
                'request_type' => [
                    'label' => 'De quel accompagnement avez-vous besoin ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir un besoin',
                    'options' => [
                        'information' => 'Information ou conseil',
                        'draft' => 'Rédaction d’un document',
                        'review' => 'Vérification d’un dossier',
                        'procedure' => 'Démarche administrative',
                        'representation' => 'Accompagnement ou représentation',
                    ],
                ],
                'deadline_level' => [
                    'label' => 'Quel est votre délai ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir un délai',
                    'options' => [
                        'flexible' => 'Je suis flexible',
                        'month' => 'Dans le mois',
                        'week' => 'Dans la semaine',
                        'urgent' => 'Sous 48 heures',
                    ],
                ],
            ],
        ],
        'automotive' => [
            'introduction' => 'Le véhicule et la nature de l’intervention évitent les réponses de professionnels non équipés.',
            'fields' => [
                'vehicle_type' => [
                    'label' => 'Quel véhicule est concerné ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir un véhicule',
                    'options' => [
                        'car' => 'Voiture',
                        'utility' => 'Utilitaire',
                        'motorcycle' => 'Moto',
                        'scooter' => 'Scooter',
                        'heavy' => 'Poids lourd ou engin',
                        'other' => 'Autre',
                    ],
                ],
                'vehicle_need' => [
                    'label' => 'Quelle intervention recherchez-vous ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir une intervention',
                    'options' => [
                        'diagnosis' => 'Diagnostic',
                        'maintenance' => 'Entretien',
                        'repair' => 'Réparation',
                        'tires' => 'Pneus ou roues',
                        'bodywork' => 'Carrosserie',
                        'other' => 'Autre',
                    ],
                ],
            ],
        ],
        'commerce' => [
            'introduction' => 'La nature et le volume du besoin permettent de distinguer une recherche ponctuelle d’une mission commerciale.',
            'fields' => [
                'commerce_need' => [
                    'label' => 'Quel est votre besoin principal ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir un besoin',
                    'options' => [
                        'sourcing' => 'Recherche de produits ou fournisseurs',
                        'delivery' => 'Livraison ou approvisionnement',
                        'stock' => 'Gestion ou rangement de stock',
                        'merchandising' => 'Vente ou mise en rayon',
                        'other' => 'Autre mission commerciale',
                    ],
                ],
                'quantity_band' => [
                    'label' => 'Quel volume est concerné ?',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Choisir un volume',
                    'options' => [
                        'small' => 'Petit volume',
                        'medium' => 'Volume moyen',
                        'large' => 'Volume important',
                        'recurring' => 'Besoin récurrent',
                        'unknown' => 'À définir',
                    ],
                ],
            ],
        ],
    ],
];

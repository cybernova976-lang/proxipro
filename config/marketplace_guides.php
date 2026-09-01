<?php

return [
    'publier-une-demande-utile' => [
        'audience' => ['client'],
        'priority' => 10,
        'icon' => 'fas fa-pen-to-square',
        'kicker' => 'Avant de publier',
        'title' => 'Publier une demande qui reçoit des réponses utiles',
        'summary' => 'Les informations qui permettent à un prestataire de comprendre, chiffrer et planifier votre besoin.',
        'reading_time' => 4,
        'sections' => [
            [
                'title' => 'Décrivez le résultat attendu',
                'text' => 'Expliquez ce qui doit être fait et le résultat que vous souhaitez obtenir. Un titre précis aide davantage que quelques mots génériques.',
                'bullets' => [
                    'Indiquez la pièce, l’équipement ou la zone concernée.',
                    'Précisez ce qui fonctionne encore et ce qui ne fonctionne plus.',
                    'Signalez toute contrainte d’accès ou de stationnement utile.',
                ],
            ],
            [
                'title' => 'Ajoutez uniquement des photos utiles',
                'text' => 'Une vue d’ensemble et un ou deux détails suffisent souvent. Retirez les documents, visages ou informations personnelles sans rapport avec la mission.',
                'bullets' => [
                    'Photographiez avec suffisamment de lumière.',
                    'Montrez l’échelle ou les dimensions quand elles comptent.',
                    'N’affichez jamais de pièce d’identité dans une annonce publique.',
                ],
            ],
            [
                'title' => 'Donnez un créneau et un budget réalistes',
                'text' => 'Un prestataire peut répondre plus clairement quand il connaît votre degré d’urgence, vos disponibilités et la façon dont le prix doit être calculé.',
                'bullets' => [
                    'Choisissez « à négocier » si le diagnostic doit être fait sur place.',
                    'Précisez si le matériel doit être inclus.',
                    'Gardez plusieurs créneaux possibles lorsque vous le pouvez.',
                ],
            ],
        ],
        'checklist' => ['Besoin précis', 'Photos sans données sensibles', 'Lieu et créneau', 'Budget ou mode de prix'],
        'cta_route' => 'demand.create',
        'cta_label' => 'Publier ma demande',
    ],

    'choisir-un-prestataire' => [
        'audience' => ['client'],
        'priority' => 20,
        'icon' => 'fas fa-user-check',
        'kicker' => 'Avant de choisir',
        'title' => 'Comparer les prestataires au-delà du prix',
        'summary' => 'Une méthode simple pour vérifier que le profil, le périmètre et le calendrier correspondent à votre besoin.',
        'reading_time' => 4,
        'sections' => [
            [
                'title' => 'Vérifiez les preuves pertinentes',
                'text' => 'Regardez le métier déclaré, la description des services et les avis issus de missions réellement terminées. Le badge de vérification d’identité est un repère, pas une garantie de résultat.',
                'bullets' => [
                    'Lisez plusieurs avis, pas seulement la note moyenne.',
                    'Vérifiez que le service demandé figure bien sur le profil.',
                    'Demandez une précision si le périmètre reste ambigu.',
                ],
            ],
            [
                'title' => 'Comparez ce qui est réellement inclus',
                'text' => 'Deux propositions au même prix peuvent couvrir des prestations différentes. Faites préciser la main-d’œuvre, le matériel, le déplacement et le délai.',
                'bullets' => [
                    'Conservez les échanges importants dans la messagerie.',
                    'Ne choisissez pas automatiquement l’offre la moins chère.',
                    'Validez le créneau avant de payer.',
                ],
            ],
            [
                'title' => 'Gardez un cadre clair',
                'text' => 'Utilisez la commande et le paiement prévus par la plateforme lorsque la prestation y est éligible. Signalez un contenu ou un comportement inquiétant.',
                'bullets' => [
                    'Ne communiquez jamais vos codes ou mots de passe.',
                    'Refusez les demandes de paiement inhabituelles.',
                    'Rencontrez-vous dans un cadre adapté à la prestation.',
                ],
            ],
        ],
        'checklist' => ['Métier cohérent', 'Avis vérifiés', 'Prix détaillé', 'Créneau confirmé'],
        'cta_route' => 'ads.index',
        'cta_route_params' => ['type' => 'offres'],
        'cta_label' => 'Explorer les services',
    ],

    'profil-prestataire-rassurant' => [
        'audience' => ['provider'],
        'priority' => 10,
        'icon' => 'fas fa-address-card',
        'kicker' => 'Gagner en visibilité',
        'title' => 'Construire un profil prestataire rassurant',
        'summary' => 'Présentez des preuves compréhensibles et les informations dont un client a réellement besoin pour vous contacter.',
        'reading_time' => 5,
        'sections' => [
            [
                'title' => 'Annoncez clairement votre métier',
                'text' => 'Sélectionnez seulement les catégories dans lesquelles vous pouvez réellement intervenir. Une spécialisation précise est plus utile qu’une longue liste générique.',
                'bullets' => [
                    'Utilisez une photo nette et professionnelle.',
                    'Décrivez les prestations que vous acceptez.',
                    'Indiquez votre zone et vos disponibilités habituelles.',
                ],
            ],
            [
                'title' => 'Expliquez votre façon de travailler',
                'text' => 'Quelques phrases concrètes sur le diagnostic, le devis, le matériel et le suivi rassurent davantage que des slogans.',
                'bullets' => [
                    'Mentionnez les qualifications réellement détenues.',
                    'N’utilisez pas de promesse impossible à vérifier.',
                    'Gardez vos tarifs et vos services à jour.',
                ],
            ],
            [
                'title' => 'Faites vérifier votre identité',
                'text' => 'La vérification permet d’afficher un repère factuel sur votre profil. Elle ne remplace ni les avis de missions terminées ni les obligations professionnelles applicables.',
                'bullets' => [
                    'Envoyez des documents lisibles dans l’espace sécurisé.',
                    'Ne publiez aucun document d’identité dans une annonce.',
                    'Répondez aux demandes de correction depuis votre compte.',
                ],
            ],
        ],
        'checklist' => ['Photo nette', 'Métiers précis', 'Zone à jour', 'Preuves factuelles'],
        'cta_route' => 'pro.profile.edit',
        'cta_label' => 'Améliorer mon profil',
    ],

    'envoyer-une-proposition-claire' => [
        'audience' => ['provider'],
        'priority' => 20,
        'icon' => 'fas fa-paper-plane',
        'kicker' => 'Répondre à un client',
        'title' => 'Envoyer une proposition claire et comparable',
        'summary' => 'Un prix, un périmètre et un délai compréhensibles réduisent les échanges inutiles et les malentendus.',
        'reading_time' => 4,
        'sections' => [
            [
                'title' => 'Montrez que vous avez lu la demande',
                'text' => 'Reprenez le besoin principal et expliquez brièvement votre approche. Une réponse personnalisée inspire davantage confiance qu’un message identique envoyé partout.',
                'bullets' => [
                    'Posez une question si une information manque.',
                    'Précisez si un diagnostic sur place est nécessaire.',
                    'N’annoncez pas un délai que vous ne pouvez pas tenir.',
                ],
            ],
            [
                'title' => 'Détaillez le prix proposé',
                'text' => 'Le client doit comprendre ce qui est inclus dans votre montant et ce qui pourrait le faire évoluer.',
                'bullets' => [
                    'Séparez la main-d’œuvre du matériel si nécessaire.',
                    'Mentionnez les frais de déplacement éventuels.',
                    'Indiquez la durée de validité de votre proposition.',
                ],
            ],
            [
                'title' => 'Fixez la prochaine étape',
                'text' => 'Terminez par une action simple : échange de photos, appel, visite technique ou confirmation d’un créneau.',
                'bullets' => [
                    'Centralisez les informations importantes dans la messagerie.',
                    'Mettez à jour la proposition si le périmètre change.',
                    'Retirez-la si vous n’êtes plus disponible.',
                ],
            ],
        ],
        'checklist' => ['Réponse personnalisée', 'Prix expliqué', 'Délai réaliste', 'Prochaine étape'],
        'cta_route' => 'pro.opportunities',
        'cta_label' => 'Voir mes opportunités',
    ],

    'organiser-une-mission' => [
        'audience' => ['provider'],
        'priority' => 30,
        'icon' => 'fas fa-list-check',
        'kicker' => 'Après acceptation',
        'title' => 'Organiser la mission jusqu’au paiement',
        'summary' => 'Préparez l’intervention, gardez une trace des décisions et vérifiez chaque étape de la commande.',
        'reading_time' => 4,
        'sections' => [
            [
                'title' => 'Confirmez le périmètre avant de commencer',
                'text' => 'Validez le lieu, le créneau, le résultat attendu et les éléments compris dans le prix. Toute modification importante doit être acceptée avant l’intervention.',
                'bullets' => [
                    'Prévenez rapidement en cas de retard.',
                    'Demandez l’accord avant tout coût supplémentaire.',
                    'Conservez les échanges utiles dans la plateforme.',
                ],
            ],
            [
                'title' => 'Suivez le statut de la commande',
                'text' => 'Vérifiez que le paiement est bien confirmé dans Prokejem. Un message ou une capture externe ne prouve pas qu’une transaction a été validée.',
                'bullets' => [
                    'Ne partagez jamais vos codes d’authentification.',
                    'Consultez le statut affiché dans « Mes commandes ».',
                    'Utilisez la procédure de litige en cas de désaccord.',
                ],
            ],
            [
                'title' => 'Clôturez proprement la mission',
                'text' => 'Expliquez au client ce qui a été réalisé et ce qui reste éventuellement à surveiller. La libération des fonds intervient selon le statut réel de la commande.',
                'bullets' => [
                    'Remettez les documents utiles lorsque cela s’applique.',
                    'Invitez le client à valider seulement après la prestation.',
                    'Les avis doivent porter sur une expérience réellement vécue.',
                ],
            ],
        ],
        'checklist' => ['Périmètre confirmé', 'Paiement vérifié', 'Échanges conservés', 'Mission clôturée'],
        'cta_route' => 'service-orders.index',
        'cta_label' => 'Suivre mes commandes',
    ],

    'securite-et-bonnes-pratiques' => [
        'audience' => ['client', 'provider'],
        'priority' => 40,
        'icon' => 'fas fa-shield-halved',
        'kicker' => 'Pour tous les membres',
        'title' => 'Les bons réflexes de la communauté',
        'summary' => 'Protégez vos informations, repérez les demandes inhabituelles et utilisez les outils de signalement.',
        'reading_time' => 3,
        'sections' => [
            [
                'title' => 'Protégez vos accès et vos documents',
                'text' => 'Prokejem ne vous demandera pas de communiquer votre mot de passe, votre code de connexion ou les informations complètes de votre carte bancaire dans un message.',
                'bullets' => [
                    'Gardez vos documents d’identité dans l’espace prévu.',
                    'N’envoyez pas de code reçu par SMS ou e-mail.',
                    'Vérifiez toujours l’adresse du site avant de vous connecter.',
                ],
            ],
            [
                'title' => 'Restez attentif aux demandes inhabituelles',
                'text' => 'Un paiement urgent hors plateforme, un prix incohérent ou une demande de données sans rapport avec la prestation doivent vous alerter.',
                'bullets' => [
                    'Prenez le temps de vérifier le profil et le contexte.',
                    'N’avancez pas de frais sans raison clairement établie.',
                    'Interrompez l’échange si vous ne vous sentez pas en confiance.',
                ],
            ],
            [
                'title' => 'Signalez pour aider la modération',
                'text' => 'Le bouton de signalement d’une annonce permet de transmettre le contenu concerné. Pour un problème de compte ou de paiement, utilisez le formulaire de contact.',
                'bullets' => [
                    'Décrivez des faits précis, sans publier de données sensibles.',
                    'Conservez les références de la commande concernée.',
                    'En cas de danger immédiat, contactez les services compétents.',
                ],
            ],
        ],
        'checklist' => ['Accès protégés', 'Paiement vérifié', 'Échanges prudents', 'Signalement factuel'],
        'cta_route' => 'legal.platform-rules',
        'cta_label' => 'Lire les règles de la marketplace',
    ],
];

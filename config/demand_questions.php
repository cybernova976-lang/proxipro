<?php

/*
 * Banque de questions du parcours guidé : une question par écran, des réponses
 * à toucher, et toujours « Je ne sais pas » (qui ne produit aucune phrase).
 *
 * - label : la question, en mots simples ;
 * - maps_to : champ de service_details (config/service_demand_intake.php) renseigné
 *   par la réponse, ou null. Pour un maps_to, la clé de l'option EST la valeur du champ ;
 * - unknown_value : valeur enregistrée pour « Je ne sais pas » (obligatoire si
 *   maps_to vise un champ requis ; elle doit exister dans les options du champ) ;
 * - options : libellé et pictogramme, plus ce que l'option produit dans le texte :
 *   title_part (ajouté au titre), fragment (remplace {fragment} de la phrase
 *   d'ouverture), sentence (phrase ajoutée, 12 mots au plus) et service_details
 *   (surcharge des précisions fixées par la situation) ;
 * - audio : réservé à de futures consignes enregistrées (non utilisé).
 *
 * Typographie : apostrophe typographique ’ partout.
 */
return [
    'version' => 1,

    'unknown' => ['label' => 'Je ne sais pas', 'picto' => 'je-ne-sais-pas'],

    'questions' => [
        'fuite_lieu' => [
            'label' => 'Où est la fuite ?',
            'maps_to' => null,
            'unknown_value' => null,
            'options' => [
                'cuisine' => ['label' => 'Cuisine', 'picto' => 'cuisine', 'title_part' => ' dans la cuisine', 'fragment' => ' dans la cuisine'],
                'salle_de_bain' => ['label' => 'Salle de bain', 'picto' => 'salle-de-bain', 'title_part' => ' dans la salle de bain', 'fragment' => ' dans la salle de bain'],
                'wc' => ['label' => 'WC', 'picto' => 'wc', 'title_part' => ' dans les WC', 'fragment' => ' dans les WC'],
                'dehors' => ['label' => 'Dehors', 'picto' => 'dehors', 'title_part' => ' dehors', 'fragment' => ' dehors'],
            ],
            'audio' => null,
        ],
        'fuite_force' => [
            'label' => 'L’eau coule comment ?',
            'maps_to' => null,
            'unknown_value' => null,
            'options' => [
                'goutte' => ['label' => 'Goutte à goutte', 'picto' => 'goutte', 'sentence' => 'L’eau coule goutte à goutte.'],
                'beaucoup' => ['label' => 'Beaucoup', 'picto' => 'coule-fort', 'sentence' => 'L’eau coule beaucoup.'],
                'coupee' => ['label' => 'J’ai coupé l’eau', 'picto' => 'robinet-ferme', 'sentence' => 'J’ai coupé l’eau.'],
            ],
            'audio' => null,
        ],
        'logement' => [
            'label' => 'Chez vous, c’est…',
            'maps_to' => 'site_type',
            'unknown_value' => 'unknown',
            'options' => [
                'house' => ['label' => 'Une maison', 'picto' => 'maison', 'sentence' => 'C’est dans une maison.'],
                'apartment' => ['label' => 'Un appartement', 'picto' => 'appartement', 'sentence' => 'C’est dans un appartement.'],
                'business' => ['label' => 'Un commerce ou un bureau', 'picto' => 'commerce', 'sentence' => 'C’est dans un local professionnel.'],
            ],
            'audio' => null,
        ],
        'wc_etat' => [
            'label' => 'Que fait l’eau ?',
            'maps_to' => null,
            'unknown_value' => null,
            'options' => [
                'monte' => ['label' => 'Elle monte', 'picto' => 'eau-monte', 'sentence' => 'L’eau monte dans la cuvette.'],
                'lent' => ['label' => 'Elle descend lentement', 'picto' => 'eau-lente', 'sentence' => 'L’eau descend lentement.'],
                'deborde' => ['label' => 'Ça déborde', 'picto' => 'deborde', 'sentence' => 'Les WC débordent.'],
            ],
            'audio' => null,
        ],
        'panne_quoi' => [
            'label' => 'Qu’est-ce qui ne marche pas ?',
            'maps_to' => null,
            'unknown_value' => null,
            'options' => [
                'tout' => ['label' => 'Plus de courant', 'picto' => 'plus-de-courant', 'sentence' => 'Je n’ai plus de courant du tout.'],
                'prise' => ['label' => 'Une prise', 'picto' => 'prise', 'sentence' => 'Une prise ne marche plus.'],
                'lumiere' => ['label' => 'Une lumière', 'picto' => 'lumiere', 'sentence' => 'Une lumière ne marche plus.'],
                'disjoncteur' => ['label' => 'Le disjoncteur saute', 'picto' => 'disjoncteur', 'sentence' => 'Le disjoncteur saute.'],
            ],
            'audio' => null,
        ],
        'clim_probleme' => [
            'label' => 'Que se passe-t-il ?',
            'maps_to' => null,
            'unknown_value' => null,
            'options' => [
                'froid' => ['label' => 'Plus de froid', 'picto' => 'clim-froid', 'title_part' => ' en panne', 'sentence' => 'La clim ne fait plus froid.'],
                'coule' => ['label' => 'Elle coule', 'picto' => 'clim-coule', 'title_part' => ' qui coule', 'sentence' => 'La clim coule.'],
                'bruit' => ['label' => 'Elle fait du bruit', 'picto' => 'clim-bruit', 'title_part' => ' bruyante', 'sentence' => 'La clim fait du bruit.'],
                'installer' => ['label' => 'Installer une clim', 'picto' => 'clim-installer', 'title_part' => ' à installer', 'sentence' => 'Je veux installer une clim.', 'service_details' => ['work_scope' => 'installation']],
            ],
            'audio' => null,
        ],
        'menage_frequence' => [
            'label' => 'Combien de fois ?',
            'maps_to' => 'frequency',
            'unknown_value' => 'unknown',
            'options' => [
                'once' => ['label' => 'Une fois', 'picto' => 'calendrier-une-fois', 'sentence' => 'C’est pour une seule fois.'],
                'weekly' => ['label' => 'Chaque semaine', 'picto' => 'calendrier-semaine', 'sentence' => 'C’est pour chaque semaine.'],
                'several_week' => ['label' => 'Plusieurs fois par semaine', 'picto' => 'calendrier-plusieurs', 'sentence' => 'C’est plusieurs fois par semaine.'],
                'monthly' => ['label' => 'Quelques fois par mois', 'picto' => 'calendrier-mois', 'sentence' => 'C’est quelques fois par mois.'],
            ],
            'audio' => null,
        ],
        'menage_duree' => [
            'label' => 'Combien de temps ?',
            'maps_to' => 'duration_band',
            'unknown_value' => 'unknown',
            'options' => [
                'short' => ['label' => 'Moins de 2 heures', 'picto' => 'sablier-court', 'sentence' => 'Il faut moins de 2 heures.'],
                'half_day' => ['label' => 'Une demi-journée', 'picto' => 'sablier-demi', 'sentence' => 'Il faut une demi-journée.'],
                'day' => ['label' => 'Une journée', 'picto' => 'sablier-jour', 'sentence' => 'Il faut une journée.'],
            ],
            'audio' => null,
        ],
        'jardin_travail' => [
            'label' => 'Quel travail ?',
            'maps_to' => null,
            'unknown_value' => null,
            'options' => [
                'herbe' => ['label' => 'Couper l’herbe', 'picto' => 'herbe', 'title_part' => ' : couper l’herbe', 'sentence' => 'Il faut couper l’herbe.'],
                'tailler' => ['label' => 'Tailler', 'picto' => 'secateur', 'title_part' => ' : taille', 'sentence' => 'Il faut tailler des arbres ou des haies.'],
                'nettoyer' => ['label' => 'Nettoyer le terrain', 'picto' => 'rateau', 'title_part' => ' : nettoyage', 'sentence' => 'Il faut nettoyer le terrain.'],
            ],
            'audio' => null,
        ],
        'jardin_taille' => [
            'label' => 'C’est grand ?',
            'maps_to' => 'area_band',
            // Option existante « Je ne sais pas encore ».
            'unknown_value' => 'unknown',
            'options' => [
                'small' => ['label' => 'Petit', 'picto' => 'jardin-petit', 'sentence' => 'Le jardin est petit.'],
                'medium' => ['label' => 'Moyen', 'picto' => 'jardin-moyen', 'sentence' => 'Le jardin est moyen.'],
                'large' => ['label' => 'Grand', 'picto' => 'jardin-grand', 'sentence' => 'Le terrain est grand.'],
            ],
            'audio' => null,
        ],
        'dem_volume' => [
            'label' => 'Combien de choses ?',
            'maps_to' => 'load_size',
            'unknown_value' => 'unknown',
            'options' => [
                'few_items' => ['label' => 'Quelques objets', 'picto' => 'objets', 'sentence' => 'J’ai quelques objets à déplacer.'],
                'car_load' => ['label' => 'Une voiture pleine', 'picto' => 'voiture', 'sentence' => 'J’ai une voiture pleine à déplacer.'],
                'utility' => ['label' => 'Un camion', 'picto' => 'camion', 'sentence' => 'Il faut un camion.'],
                'large_move' => ['label' => 'Toute la maison', 'picto' => 'maison-entiere', 'sentence' => 'Je déménage toute la maison.'],
            ],
            'audio' => null,
        ],
        'dem_acces' => [
            'label' => 'Il y a des escaliers ?',
            'maps_to' => 'access',
            // Option existante « À vérifier ».
            'unknown_value' => 'unknown',
            'options' => [
                'easy' => ['label' => 'Non', 'picto' => 'plain-pied', 'sentence' => 'L’accès est facile.'],
                'elevator' => ['label' => 'Il y a un ascenseur', 'picto' => 'ascenseur', 'sentence' => 'Il y a un ascenseur.'],
                'stairs' => ['label' => 'Oui, sans ascenseur', 'picto' => 'escalier', 'sentence' => 'Il y a des escaliers, sans ascenseur.'],
            ],
            'audio' => null,
        ],
        'tel_panne' => [
            'label' => 'Qu’est-ce qui est cassé ?',
            'maps_to' => null,
            'unknown_value' => null,
            'options' => [
                'ecran' => ['label' => 'L’écran', 'picto' => 'ecran-casse', 'sentence' => 'L’écran est cassé.'],
                'batterie' => ['label' => 'La batterie', 'picto' => 'batterie', 'sentence' => 'La batterie ne tient plus.'],
                'charge' => ['label' => 'Il ne charge plus', 'picto' => 'prise-charge', 'sentence' => 'Il ne charge plus.'],
                'eau' => ['label' => 'Tombé dans l’eau', 'picto' => 'goutte-telephone', 'sentence' => 'Il est tombé dans l’eau.'],
            ],
            'audio' => null,
        ],
        'tel_mode' => [
            'label' => 'Où le réparer ?',
            'maps_to' => 'support_mode',
            // Option existante « Selon le diagnostic ».
            'unknown_value' => 'flexible',
            'options' => [
                'dropoff' => ['label' => 'Je le dépose', 'picto' => 'boutique', 'sentence' => 'Je peux déposer le téléphone.'],
                'onsite' => ['label' => 'On vient chez moi', 'picto' => 'domicile', 'sentence' => 'Je préfère une réparation chez moi.'],
            ],
            'audio' => null,
        ],
    ],
];

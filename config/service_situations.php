<?php

/*
 * Situations de départ du parcours guidé « Publier sans écrire » (lot 2).
 *
 * Chaque situation est rattachée à un métier existant de config/categories.php.
 * Elle disparaît si l'univers du métier est désactivé dans l'administration,
 * ou si le métier n'existe plus (App\Support\ServiceSituationCatalog).
 *
 * Typographie : apostrophe typographique ’ partout (le texte composé la reprend).
 *
 * - label : 3 mots au plus, affiché sous le pictogramme ;
 * - picto : nom du fichier public/img/pk-situations/{picto}.svg ;
 * - tile : false = accessible par la recherche et l'URL, sans tuile ;
 * - synonyms : en minuscules et sans accents (la recherche normalise aussi) ;
 * - questions : clés de config/demand_questions.php, dans l'ordre des écrans ;
 * - title_question : question qui fournit title_part (titre) et {fragment} (phrase d'ouverture) ;
 * - service_details : précisions obligatoires fixées par la situation ;
 * - territories : null = tous les territoires, sinon liste de codes (['976', '973']).
 */
return [
    'version' => 1,

    // Ordre éditorial des tuiles (il n'y a pas encore de mesure : la colonne situation_key est neuve).
    'situations' => [
        'fuite-eau' => [
            'label' => 'Fuite d’eau',
            'picto' => 'fuite-eau',
            'main_category' => 'Bricolage & Travaux',
            'subcategory' => 'Plombier',
            'tile' => true,
            'synonyms' => ['fuite', 'fuit', 'robinet', 'tuyau', 'eau qui coule', 'degat des eaux', 'plomberie', 'plombier', 'evier', 'chauffe-eau'],
            'questions' => ['fuite_lieu', 'fuite_force', 'logement'],
            'title' => 'Fuite d’eau',
            'title_question' => 'fuite_lieu',
            'opening' => 'J’ai une fuite d’eau{fragment}.',
            'service_details' => ['work_scope' => 'repair'],
            'territories' => null,
        ],
        'wc-bouche' => [
            'label' => 'WC bouché',
            'picto' => 'wc-bouche',
            'main_category' => 'Bricolage & Travaux',
            'subcategory' => 'Plombier',
            'tile' => true,
            'synonyms' => ['wc', 'toilettes', 'toilette', 'cuvette', 'chasse d\'eau', 'debouchage', 'canalisation', 'bouche'],
            'questions' => ['wc_etat', 'logement'],
            'title' => 'WC bouchés',
            'title_question' => null,
            'opening' => 'Mes WC sont bouchés.',
            'service_details' => ['work_scope' => 'repair'],
            'territories' => null,
        ],
        'panne-electrique' => [
            'label' => 'Panne électrique',
            'picto' => 'panne-electrique',
            'main_category' => 'Bricolage & Travaux',
            'subcategory' => 'Électricien',
            'tile' => true,
            'synonyms' => ['electricite', 'courant', 'plus de courant', 'coupure', 'disjoncteur', 'prise', 'lumiere', 'electricien'],
            'questions' => ['panne_quoi', 'logement'],
            'title' => 'Panne électrique',
            'title_question' => null,
            'opening' => 'J’ai une panne électrique.',
            'service_details' => ['work_scope' => 'repair'],
            'territories' => null,
        ],
        'clim' => [
            'label' => 'Clim',
            'picto' => 'clim',
            'main_category' => 'Bricolage & Travaux',
            'subcategory' => 'Climaticien',
            'tile' => true,
            'synonyms' => ['climatisation', 'climatiseur', 'air conditionne', 'split', 'froid', 'climaticien'],
            'questions' => ['clim_probleme', 'logement'],
            'title' => 'Clim',
            'title_question' => 'clim_probleme',
            'opening' => 'J’ai besoin d’un climaticien.',
            // L'option « Installer une clim » remplace work_scope par installation.
            'service_details' => ['work_scope' => 'repair'],
            'territories' => null,
        ],
        'menage' => [
            'label' => 'Ménage',
            'picto' => 'menage',
            'main_category' => 'Nettoyage & Entretien',
            'subcategory' => 'Femme/Homme de ménage',
            'tile' => true,
            'synonyms' => ['nettoyage', 'femme de menage', 'aide menagere', 'repassage', 'proprete'],
            'questions' => ['menage_frequence', 'menage_duree'],
            'title' => 'Ménage',
            'title_question' => null,
            'opening' => 'Je cherche une aide pour le ménage.',
            'service_details' => [],
            'territories' => null,
        ],
        'jardin' => [
            'label' => 'Jardin',
            'picto' => 'jardin',
            'main_category' => 'Jardinage & Extérieur',
            'subcategory' => 'Jardinier',
            'tile' => true,
            'synonyms' => ['jardinage', 'herbe', 'tondre', 'tonte', 'haie', 'debroussaillage', 'arbre', 'jardinier'],
            'questions' => ['jardin_travail', 'jardin_taille'],
            'title' => 'Entretien du jardin',
            'title_question' => 'jardin_travail',
            'opening' => 'J’ai besoin d’un jardinier.',
            // Option existante « À déterminer avec le prestataire ».
            'service_details' => ['resources_available' => 'unknown'],
            'territories' => null,
        ],
        'demenager' => [
            'label' => 'Déménager',
            'picto' => 'demenager',
            'main_category' => 'Transport & Déménagement',
            'subcategory' => 'Déménageur',
            'tile' => true,
            'synonyms' => ['demenagement', 'demenageur', 'cartons', 'transporter', 'meubles', 'camion'],
            'questions' => ['dem_volume', 'dem_acces'],
            'title' => 'Déménagement',
            'title_question' => null,
            'opening' => 'Je dois déménager.',
            'service_details' => [],
            'territories' => null,
        ],
        'telephone-casse' => [
            'label' => 'Téléphone cassé',
            'picto' => 'telephone-casse',
            'main_category' => 'Informatique & Tech',
            'subcategory' => 'Réparateur smartphone / tablette',
            // Pas de tuile : accessible par la recherche et par l'URL.
            'tile' => false,
            'synonyms' => ['telephone', 'portable', 'smartphone', 'ecran casse', 'batterie', 'iphone', 'samsung', 'tablette'],
            'questions' => ['tel_panne', 'tel_mode'],
            'title' => 'Téléphone cassé',
            'title_question' => null,
            'opening' => 'Mon téléphone est cassé.',
            'service_details' => ['device_type' => 'phone'],
            'territories' => null,
        ],
    ],
];

<?php

/**
 * Catégories et sous-catégories unifiées pour toute l'application.
 *
 * Source de vérité unique : toutes les pages (feed, publication d'annonce,
 * inscription pro, onboarding, profil) DOIVENT utiliser cette liste.
 *
 * Structure :
 *   - 'services'    → catégories de services professionnels (onboarding, profil pro, prestations)
 *   - 'marketplace' → catégories propres aux annonces (vente, emploi, location, etc.)
 *   - 'all'         → services + marketplace fusionnés (feed, publication d'annonce)
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Catégories de services professionnels
    |--------------------------------------------------------------------------
    | Utilisées pour : onboarding pro, profil prestataire, inscription Google,
    | devenir prestataire, feed "Trouver un Pro", publication d'annonce
    */
    'services' => [
        'Bricolage & Travaux' => [
            'icon' => '🔧',
            'fa_icon' => 'fas fa-tools',
            'color' => '#eab308',
            'description' => 'Plomberie, électricité, peinture...',
            'subcategories' => [
                'Plombier', 'Électricien', 'Peintre en bâtiment', 'Menuisier', 'Carreleur',
                'Maçon', 'Serrurier', 'Climaticien', 'Chauffagiste',
                'Installateur panneaux solaires', 'Spécialiste rénovation', 'Plaquiste',
                'Façadier', 'Couvreur / Zingueur', 'Charpentier',
                'Poseur de fenêtres / Vitrier', 'Installateur domotique',
                'Poseur de cuisine', 'Poseur de parquet', 'Terrassier',
                'Ferronnier', 'Étanchéiste',
            ],
        ],
        'Jardinage & Extérieur' => [
            'icon' => '🌿',
            'fa_icon' => 'fas fa-leaf',
            'color' => '#22c55e',
            'description' => 'Tonte, élagage, paysagiste...',
            'subcategories' => [
                'Jardinier', 'Paysagiste', 'Élagueur', 'Pisciniste',
                'Spécialiste arrosage', 'Entretien espaces verts',
                'Tonte de pelouse', 'Clôturiste', 'Pépiniériste',
                'Créateur de terrasses', 'Spécialiste engazonnement', 'Tailleur de haies',
            ],
        ],
        'Nettoyage & Entretien' => [
            'icon' => '🧹',
            'fa_icon' => 'fas fa-broom',
            'color' => '#06b6d4',
            'description' => 'Ménage, repassage, nettoyage...',
            'subcategories' => [
                'Agent de nettoyage', 'Femme/Homme de ménage', 'Nettoyeur fin de chantier',
                'Repasseur/Repasseuse', 'Laveur de vitres', 'Nettoyeur haute pression',
                'Nettoyeur de toiture', 'Désinjecteur / Dératiseur',
                'Nettoyeur de moquettes', 'Autolaveuse professionnel',
                'Entretien copropriétés', 'Nettoyage après sinistre',
            ],
        ],
        'Aide à domicile' => [
            'icon' => '🤝',
            'fa_icon' => 'fas fa-hands-helping',
            'color' => '#ef4444',
            'description' => 'Baby-sitting, aide personnes âgées...',
            'subcategories' => [
                'Baby-sitter', 'Aide-soignant(e)', 'Nounou / Assistante maternelle',
                'Accompagnateur scolaire', 'Livreur de courses', 'Cuisinier à domicile',
                'Assistant administratif', 'Aide aux personnes âgées', 'Garde de nuit',
                'Dame de compagnie', 'Auxiliaire de vie',
                'Pet-sitter / Garde d\'animaux', 'Promeneur de chiens',
            ],
        ],
        'Cours & Formation' => [
            'icon' => '📚',
            'fa_icon' => 'fas fa-graduation-cap',
            'color' => '#3b82f6',
            'description' => 'Langues, soutien scolaire, musique...',
            'subcategories' => [
                'Professeur particulier', 'Coach sportif', 'Professeur de musique',
                'Professeur de langues', 'Formateur informatique',
                'Coach de vie / Développement personnel', 'Soutien scolaire',
                'Préparation concours', 'Professeur arts plastiques',
                'Professeur de danse', 'Moniteur de conduite',
                'Formateur professionnel', 'Professeur de yoga / méditation',
            ],
        ],
        'Beauté & Bien-être' => [
            'icon' => '💆',
            'fa_icon' => 'fas fa-spa',
            'color' => '#ec4899',
            'description' => 'Coiffure, massage, esthétique...',
            'subcategories' => [
                'Coiffeur/Coiffeuse', 'Esthéticien(ne)', 'Masseur/Masseuse',
                'Maquilleur/Maquilleuse', 'Prothésiste ongulaire', 'Coach bien-être',
                'Barbier', 'Tatoueur', 'Diététicien(ne)', 'Naturopathe',
                'Sophrologue', 'Ostéopathe', 'Réflexologue',
                'Praticien shiatsu', 'Conseiller en image',
            ],
        ],
        'Événements & Spectacles' => [
            'icon' => '🎉',
            'fa_icon' => 'fas fa-calendar-star',
            'color' => '#a855f7',
            'description' => 'DJ, photographe, traiteur...',
            'subcategories' => [
                'DJ', 'Photographe', 'Vidéaste', 'Traiteur',
                'Décorateur événementiel', 'Animateur', 'Wedding planner',
                'Fleuriste', 'Maître de cérémonie', 'Organisateur d\'événements',
                'Régisseur', 'Sonorisateur / Éclairagiste', 'Musicien / Groupe',
                'Magicien', 'Location de matériel événementiel',
            ],
        ],
        'Transport & Déménagement' => [
            'icon' => '🚚',
            'fa_icon' => 'fas fa-truck-moving',
            'color' => '#f97316',
            'description' => 'Déménagement, livraison, transport...',
            'subcategories' => [
                'Déménageur', 'Livreur', 'Chauffeur privé / VTC', 'Coursier',
                'Transporteur d\'animaux', 'Transport de marchandises',
                'Chauffeur poids lourd', 'Taxi', 'Convoyeur de véhicules',
                'Garde-meubles / Stockage', 'Monte-meubles',
            ],
        ],
        'Informatique & Tech' => [
            'icon' => '💻',
            'fa_icon' => 'fas fa-laptop-code',
            'color' => '#6366f1',
            'description' => 'Dépannage, développement, réparation...',
            'subcategories' => [
                'Développeur web', 'Développeur mobile', 'Technicien informatique',
                'Réparateur smartphone / tablette', 'Installateur réseau / fibre',
                'Graphiste / Designer', 'Community manager', 'Rédacteur web / SEO',
                'Administrateur systèmes', 'Data analyst',
                'Spécialiste cybersécurité', 'Installateur vidéosurveillance',
                'Consultant IT', 'Webmaster',
                'Photocopie, préparation de documents, et autres activités spécialisées de soutien de bureau',
            ],
        ],
        'Artisanat & Création' => [
            'icon' => '🎨',
            'fa_icon' => 'fas fa-palette',
            'color' => '#14b8a6',
            'description' => 'Couture, bijouterie, restauration...',
            'subcategories' => [
                'Couturier sur mesure', 'Retoucheur / Retoucheuse',
                'Bijoutier / Joaillier', 'Potier / Céramiste', 'Encadreur',
                'Restaurateur de meubles', 'Tapissier', 'Ébéniste',
                'Sellier / Maroquinier', 'Graveur', 'Vitrailliste',
                'Doreur', 'Luthier', 'Relieur',
            ],
        ],
        'Santé & Social' => [
            'icon' => '🏥',
            'fa_icon' => 'fas fa-heartbeat',
            'color' => '#dc2626',
            'description' => 'Infirmier, kiné, psychologue...',
            'subcategories' => [
                'Infirmier(ère) libéral(e)', 'Kinésithérapeute', 'Psychologue',
                'Orthophoniste', 'Sage-femme', 'Ergothérapeute', 'Podologue',
                'Dentiste', 'Opticien', 'Audioprothésiste',
                'Pharmacien', 'Ambulancier', 'Aide médico-psychologique',
            ],
        ],
        'Automobile & Mécanique' => [
            'icon' => '🚗',
            'fa_icon' => 'fas fa-car',
            'color' => '#475569',
            'description' => 'Mécanicien, carrossier, contrôle technique...',
            'subcategories' => [
                'Mécanicien auto', 'Carrossier / Peintre auto', 'Électricien auto',
                'Contrôleur technique', 'Laveur de véhicules', 'Mécanicien moto',
                'Pneumaticien', 'Débosseleur', 'Vitrier auto',
                'Réparateur de camping-cars', 'Diagnosticien auto', 'Mécanicien nautique',
            ],
        ],
        'Immobilier & Architecture' => [
            'icon' => '🏠',
            'fa_icon' => 'fas fa-building',
            'color' => '#0891b2',
            'description' => 'Agent immobilier, architecte, diagnostic...',
            'subcategories' => [
                'Agent immobilier', 'Architecte', 'Architecte d\'intérieur',
                'Décorateur d\'intérieur', 'Diagnostiqueur immobilier',
                'Géomètre / Topographe', 'Métreur / Économiste de la construction',
                'Home stager', 'Courtier immobilier',
                'Gestionnaire de patrimoine', 'Expert en bâtiment',
            ],
        ],
        'Services juridiques & Administratifs' => [
            'icon' => '⚖️',
            'fa_icon' => 'fas fa-balance-scale',
            'color' => '#7c3aed',
            'description' => 'Avocat, notaire, comptable...',
            'subcategories' => [
                'Avocat', 'Notaire', 'Huissier de justice', 'Expert-comptable',
                'Comptable', 'Secrétaire indépendant(e)', 'Traducteur / Interprète',
                'Écrivain public', 'Consultant fiscal', 'Médiateur',
                'Conseiller juridique', 'Prestataire de paie',
            ],
        ],
        'Agriculture & Élevage' => [
            'icon' => '🌾',
            'fa_icon' => 'fas fa-tractor',
            'color' => '#a16207',
            'description' => 'Agriculteur, éleveur, vétérinaire...',
            'subcategories' => [
                'Agriculteur', 'Éleveur', 'Maraîcher', 'Apiculteur',
                'Vétérinaire', 'Toiletteur d\'animaux', 'Maréchal-ferrant',
                'Paysagiste rural', 'Conseiller agricole', 'Arboriste',
                'Viticulteur', 'Ostéopathe animalier',
            ],
        ],
        'Restauration & Alimentation' => [
            'icon' => '🍳',
            'fa_icon' => 'fas fa-utensils',
            'color' => '#ea580c',
            'description' => 'Chef cuisinier, pâtissier, traiteur...',
            'subcategories' => [
                'Chef cuisinier', 'Pâtissier', 'Boulanger', 'Boucher',
                'Poissonnier', 'Sommelier', 'Barman / Barmaid', 'Food truck',
                'Traiteur à domicile', 'Préparateur de repas',
                'Chocolatier', 'Glacier',
            ],
        ],
        'Sports & Fitness' => [
            'icon' => '🏋️',
            'fa_icon' => 'fas fa-dumbbell',
            'color' => '#059669',
            'description' => 'Coach, yoga, arts martiaux...',
            'subcategories' => [
                'Coach personnel', 'Professeur de yoga', 'Professeur de pilates',
                'Moniteur de natation', 'Moniteur de tennis', 'Moniteur de boxe',
                'Instructeur arts martiaux', 'Préparateur physique',
                'Moniteur d\'escalade', 'Moniteur de ski',
                'Moniteur d\'équitation', 'Coach de running',
            ],
        ],
        'Commerce & Marchandises' => [
            'icon' => '🏪',
            'fa_icon' => 'fas fa-store',
            'color' => '#b45309',
            'description' => 'Magasin, vente au détail, grossiste...',
            'subcategories' => [
                'Magasin alimentation générale', 'Magasin prêt-à-porter',
                'Magasin chaussures', 'Quincaillerie', 'Marchandises générales',
                'Épicerie / Supérette', 'Boucherie / Charcuterie', 'Poissonnerie',
                'Boulangerie / Pâtisserie', 'Magasin électronique / High-tech',
                'Magasin électroménager', 'Magasin cosmétiques / Parfumerie',
                'Magasin meubles / Décoration', 'Magasin jouets / Loisirs',
                'Librairie / Papeterie', 'Pharmacie / Parapharmacie',
                'Magasin matériaux de construction', 'Grossiste / Demi-grossiste',
                'Magasin de tissus / Mercerie', 'Magasin de téléphonie',
                'Boutique cadeaux / Souvenirs', 'Magasin de sport',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Catégories marketplace (annonces uniquement)
    |--------------------------------------------------------------------------
    | Utilisées uniquement pour le feed et la publication d'annonce.
    | Ne sont PAS proposées lors de l'inscription/onboarding pro.
    */
    'marketplace' => [
        'Covoiturage' => [
            'icon' => '🚗',
            'fa_icon' => 'fas fa-car-side',
            'color' => '#10B981',
            'description' => 'Trajet quotidien, longue distance...',
            'subcategories' => [
                'Trajet quotidien', 'Longue distance', 'Aéroport', 'Événements',
            ],
        ],
        'Vente' => [
            'icon' => '🛒',
            'fa_icon' => 'fas fa-shopping-cart',
            'color' => '#F59E0B',
            'description' => 'Meubles, high-tech, véhicules...',
            'subcategories' => [
                'Électroménager', 'Meubles', 'Vêtements', 'High-tech',
                'Véhicules', 'Immobilier',
            ],
        ],
        'Emploi' => [
            'icon' => '💼',
            'fa_icon' => 'fas fa-id-card',
            'color' => '#3B82F6',
            'description' => 'CDI, CDD, freelance...',
            'subcategories' => [
                'CDI', 'CDD', 'Intérim', 'Stage', 'Freelance', 'Temps partiel',
            ],
        ],
        'Location' => [
            'icon' => '🔑',
            'fa_icon' => 'fas fa-key',
            'color' => '#8B5CF6',
            'description' => 'Appartement, voiture, matériel...',
            'subcategories' => [
                'Appartements', 'Maisons', 'Voitures', 'Utilitaires',
                'Vélos & Trottinettes', 'Matériel de bricolage',
                'Matériel de jardinage', 'Équipement photo/vidéo',
                'Matériel de sono', 'Matériel événementiel',
                'Mobilier', 'Équipement sportif', 'Électroménager',
                'Vêtements & Costumes', 'Jeux & Consoles',
            ],
        ],
        'Perdu/disparu' => [
            'icon' => '🔍',
            'fa_icon' => 'fas fa-search-location',
            'color' => '#F97316',
            'description' => 'Objets perdus, animaux, personnes...',
            'subcategories' => [
                'Téléphones & Tablettes', 'Portefeuilles & Papiers', 'Clés',
                'Bijoux & Montres', 'Sacs & Bagages', 'Animaux perdus',
                'Personnes disparues', 'Véhicules volés', 'Électronique',
                'Vêtements & Accessoires', 'Lunettes', 'Autres objets',
            ],
        ],
    ],

];

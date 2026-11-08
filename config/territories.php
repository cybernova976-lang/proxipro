<?php

/*
|--------------------------------------------------------------------------
| Territoires reconnus par Prokejem
|--------------------------------------------------------------------------
|
| Les départements et régions d'outre-mer (DROM) sont rangés comme des pays
| dans config/locations.php. Leurs communes y sont lues au moment de
| l'exécution, grâce à la clé « location_country » : il n'existe donc
| qu'une seule liste de communes à entretenir.
|
| Pour l'Hexagone et la Corse, le territoire est le département, déduit du
| code postal (voir App\Support\TerritoryResolver).
|
| « preposition » sert à écrire « à Mayotte », « en Guyane », etc.
| « ft_lieux » est le code de lieu attendu par la recherche France Travail.
|
*/

return [
    'overseas' => [
        '976' => [
            'label' => 'Mayotte',
            'slug' => 'mayotte',
            'timezone' => 'Indian/Mayotte',
            'location_country' => 'Mayotte',
            'ft_lieux' => '976D',
            'preposition' => 'à',
            'aliases' => [],
        ],
        '973' => [
            'label' => 'Guyane',
            'slug' => 'guyane',
            'timezone' => 'America/Cayenne',
            'location_country' => 'Guyane',
            'ft_lieux' => '973D',
            'preposition' => 'en',
            'aliases' => ['Guyane française'],
        ],
        '974' => [
            'label' => 'La Réunion',
            'slug' => 'la-reunion',
            'timezone' => 'Indian/Reunion',
            'location_country' => 'La Réunion',
            'ft_lieux' => '974D',
            'preposition' => 'à',
            'aliases' => ['Réunion', 'Île de la Réunion'],
        ],
        '971' => [
            'label' => 'Guadeloupe',
            'slug' => 'guadeloupe',
            'timezone' => 'America/Guadeloupe',
            'location_country' => 'Guadeloupe',
            'ft_lieux' => '971D',
            'preposition' => 'en',
            'aliases' => [],
        ],
        '972' => [
            'label' => 'Martinique',
            'slug' => 'martinique',
            'timezone' => 'America/Martinique',
            'location_country' => 'Martinique',
            'ft_lieux' => '972D',
            'preposition' => 'en',
            'aliases' => [],
        ],
    ],

    // Libellés de pays qui désignent l'Hexagone : une commune d'outre-mer
    // saisie avec l'un d'eux (ou sans pays) reste rattachée à son DROM.
    'mainland' => [
        'country_labels' => ['France', 'France métropolitaine', 'Métropole', 'Hexagone'],
        'timezone' => 'Europe/Paris',
    ],
];

# Accueil public : recherche et mise en relation

Révision du 26 octobre 2026. Inspirations de parcours consultées :
[Yoojo](https://yoojo.fr/) et [AlloVoisins](https://www.allovoisins.com/).
L’identité, les images et les contenus de ces plateformes ne sont pas repris.

## Parcours

Le titre conserve ses deux lignes. Deux choix courts donnent accès à la
recherche ou à la création d’un profil prestataire. L’illustration panoramique
est un arrière-plan hors du flux sur toute la largeur du haut de page. Sur
téléphone, le bouton du formulaire reste dans le premier écran aux largeurs
360, 375 et 390 pixels. Le lieu reste facultatif et aucune couverture locale
universelle n’est annoncée. L’annuaire reste accessible sans compte.

Les catégories incluent les cours, le transport et l’informatique. Les étapes,
les cartes et le pied de page sont compacts sur mobile. Le bandeau mobile à
deux choix apparaît après le haut de page et se masque près du pied de page,
du menu et des champs en cours de saisie.

## Contenus mis en avant

Les critères sont appliqués uniquement à l’accueil ; ils ne modifient aucun
nom, avatar, texte, type, statut ou visibilité enregistrés par les membres.

- Au maximum trois profils publics actifs, avec métier, ville, avatar ou logo,
  présentation d’au moins 40 caractères et offre publique active ou réalisation.
- Au maximum deux annonces actives publiques, âgées de 30 jours au plus,
  avec lieu, titre et description suffisamment renseignés.
- Quelques contradictions explicites entre titre et type écartent une annonce
  de cette sélection. Ce filtre conservateur n’est pas une modération générale.
  Il examine au plus 40 candidats puis retient deux annonces admissibles.
- Les aperçus des annonces utilisent des icônes neutres, sans présenter une
  photo générique comme une réalisation du membre. Les photos originales
  restent sur les annonces.
- Les sections sans contenu admissible sont absentes. Aucun profil, avis,
  compteur ou signe d’activité n’est inventé pour les remplir.

Les critères sont dans `App\Support\PublicHomepageSelection` et couverts par
les tests de l’accueil. Ils ne certifient ni la qualification ni la qualité du
prestataire. Les badges et avis conservent leurs conditions réelles.

## Transparence des offres

`/offres-prestataires` est publique et ne déclenche aucun achat. Elle distingue
le compte et les demandes gratuits, le contrôle d’identité nécessaire au
parcours prestataire, les packs de points, les éventuels abonnements ouverts
et la commission des commandes réglées sur la plateforme. Les montants sont
lus dans les sources de tarification existantes, sans nouvelle configuration.

Les CTA prestataires conservent le choix `continue=provider` jusqu’à
l’authentification. La destination est un parcours interne fixe ; aucune URL
de retour arbitraire et aucune activation automatique de compte ne sont admises.

## Validation

Les relevés de rendu et captures sont produits dans `tmp/` et ne sont pas
déployés. Les contrôles couvrent largeur, deux lignes du titre, formulaire
avant l’image sur mobile, hauteur totale, menu, onglets au clavier, bandeau
à deux choix, recherche avec lieu, tarifs publics et fichiers déployés.

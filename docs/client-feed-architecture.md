# Accueil particulier — 7 novembre 2026

## Organisation livrée

L’accueil connecté sert à agir, retrouver son suivi et explorer des services.

1. Salutation courte, recherche limitée à 620 px et zone géographique.
2. Repères vers Services, Décrire mon besoin, Emploi et Mon suivi.
3. Prochaine action personnelle lorsqu’une proposition ou une mission le nécessite.
4. Quatre situations concrètes ouvrant le parcours de demande avec le métier présélectionné.
5. Six offres de services par page, avec navigation explicite et badges promotionnels.
6. Bloc emploi distinct avec accès à la recherche officielle France Travail.

Sur ordinateur, le rail contient le suivi, les messages, les favoris et les profils.
Sur téléphone, les offres utilisent une colonne et le rail passe après le contenu principal.
Les annonces ne défilent pas automatiquement. La pagination évite un carrousel où les
dernières cartes restent peu visibles et une accumulation d’annonces dans la sidebar.

## Classement des offres

Le périmètre reprend les annonces actives, les catégories autorisées, la visibilité et
la zone du feed. Les annonces de l’utilisateur sont exclues. Les emplois et autres
domaines ne sont pas intégrés aux cartes de prestations.

- Un boost actif ou un abonnement Premium actif donne la priorité.
- Deux offres par auteur passent d’abord dans chaque groupe promotionnel, puis les
  autres offres de cet auteur peuvent compléter les places.
- L’affinité de catégorie, puis la récence et l’identifiant départagent les offres.
- Les offres ordinaires complètent les places restantes après les offres promues.
- Six cartes par page ; toutes les offres éligibles restent accessibles, sans limite
  préalable de 120 candidates. Le classement est stable tant que les données ne changent pas.

La priorité ne garantit pas une présence permanente en première page lorsque beaucoup
d’offres sont promues. Une mesure future des impressions permettrait d’évaluer une
rotation équitable sans changer l’ordre pendant la navigation d’un utilisateur.

## Publication guidée

À l’étape Détails, 27 situations réparties sur 11 métiers proposent un titre et un
texte modifiables. Les autres métiers ont deux amorces générales. L’insertion est
explicite, conserve le texte existant, empêche les doublons et respecte la limite de
description. Les validations et le consentement à la publication restent obligatoires.

## Emploi : intégration future

Le bloc actuel est un accès au site officiel, pas un flux importé ni une affirmation
de partenariat. La fiche officielle indique que l’API Offres d’emploi est actuellement
restreinte pendant le réexamen de ses conditions de réutilisation :
https://www.data.gouv.fr/dataservices/api-offres-demploi
Demande d’accès : https://francetravail.io/contact

Après obtention d’un accès et validation des conditions :

- créer une source dédiée, distincte des annonces de services et de leur paiement ;
- stocker source + identifiant externe unique, intitulé, employeur, lieu, contrat,
  URL de candidature, date de publication, expiration et dernière synchronisation ;
- importer de façon idempotente, filtrer les offres expirées et désactiver les liens retirés ;
- afficher deux ou trois cartes compactes dans ce bloc, avec source et candidature
  externe clairement indiquées, puis une page emploi filtrable et paginée ;
- bloquer le parcours commande/paiement pour le domaine emploi côté serveur et interface
  avant toute activation d’offres internes de ce type.

Références observées : https://yoojo.fr/ (choix de services concrets),
https://www.allovoisins.com/ (demandes et profils immédiatement consultables).
Les espaces connectés des concurrents n’ont pas pu être inspectés : le contrôle Chrome
renvoyait des délais d’attente. Les constats ci-dessus portent sur leurs pages publiques.

# Prokejem — audit concurrentiel et plan d’amélioration

Date : 5 septembre 2026. Observation dans Google Chrome, avec les sessions disponibles de l’utilisateur.

## Conclusion

Prokejem n’a pas besoin d’une nouvelle accumulation de fonctions. Il faut d’abord rendre cohérents ses parcours et ses données, puis augmenter les chances qu’une demande aboutisse à une prestation.

La direction recommandée : **la simplicité de recherche de Yoojo, la proximité d’AlloVoisins et la clarté du suivi d’Airtasker, avec une identité visuelle propre à Prokejem**. Thumbtack est une bonne référence pour aider à comparer les professionnels. Jiji et Kaidee apportent surtout des idées de classement et de présentation des annonces ; leur modèle généraliste n’est pas à reproduire intégralement.

La promesse produit proposée : « Décrivez votre besoin, trouvez la bonne personne près de vous et suivez votre prestation au même endroit. » Ce texte est une orientation, pas une promesse de résultat garanti.

## Périmètre et limites

- Sept plateformes examinées : Yoojo, AlloVoisins, TaskRabbit, Airtasker, Thumbtack, Jiji et Kaidee.
- Écrans représentatifs : accueil, recherche/catalogue, profils, fiches d’annonces et premiers écrans de publication/réservation selon les sites.
- Prokejem : feed client connecté, annuaire supposé derrière « Voir les profils », profil public et vérification d’identité, complétés par une inspection ciblée du code local.
- Aucune annonce publiée, aucun devis envoyé, aucune réservation ni aucun paiement effectué. Aucun document d’identité téléversé. Certaines navigations concurrentes génèrent leurs propres identifiants de brouillon/recherche ; ce ne sont pas des prestations commandées.
- Les pages Airtasker consultées affichaient une navigation publique avec connexion proposée. Ne pas considérer son espace connecté comme audité.
- Les interfaces d’administration internes des concurrents ne sont pas accessibles. Les recommandations de gestion sont donc des propositions pour Prokejem, pas des affirmations sur leurs outils internes.
- Pas de validation sur téléphone physique ni de mesure comparative de vitesse dans cet audit. Les vues ont été observées sur Chrome ordinateur. Certaines pages étaient traduites automatiquement : les formulations traduites peuvent être imparfaites.
- Les chiffres de fréquentation, évaluations et garanties affichés par les concurrents sont leurs déclarations, non des résultats contrôlés indépendamment.
- Une fonction trouvée dans le code local n’est pas déclarée opérationnelle en production sans essai correspondant. Aucune suite de tests ni aucun déploiement n’a été lancé pour cet audit.

## 1. Ce qu’il faut retenir des sept plateformes

| Plateforme et pages observées | Ce qui fonctionne dans la présentation | Application recommandée pour Prokejem | Limite à ne pas copier |
|---|---|---|---|
| [Yoojo](https://yoojo.fr/) : accueil, profil, choix d’un service, quantité, durée et calendrier | Le besoin est le point d’entrée. Les cartes combinent photo, métier, tarif, avis et engagements. Le profil approfondit équipements, expérience et photos associées aux évaluations. | Rechercher une prestation concrète ; proposer des questions adaptées ; conserver le prestataire choisi lorsqu’on lance une demande depuis son profil. | Un long enchaînement de micro-écrans peut aussi ralentir. Ne pas copier les badges ou les engagements sans preuves. |
| [AlloVoisins](https://www.allovoisins.com/accueil) : fil connecté, publication intégrée, fiche prestataire | Auteur en haut, distance et réponses visibles ; publication à portée de main. Profil organisé en Présentation, Photos, Avis, Activité. Une restriction de compte est accompagnée d’une explication et d’une action corrective. | Renforcer la proximité, distinguer les états des annonces et rendre la prochaine action évidente. Regrouper les informations du profil en sections faciles à parcourir. | Le mélange annonces, contenus communautaires, promotions et thématiques charge le fil. La contrainte observée sur le compte professionnel limite l’audit de ses actions de réponse. |
| [TaskRabbit](https://www.taskrabbit.fr/dashboard/explore) : compte, catalogue connecté, début de réservation | Navigation courte ; tâches séparées du compte. Le parcours annonce ses étapes : besoin, prestataires/tarifs, date, confirmation. | Expliquer dès le départ ce qui va se passer et rendre cohérents les accès « Demander », « Publier » et « Suivre ». | La suite de la réservation n’a pas été exécutée après la demande d’adresse. Pas de conclusion sur le paiement ou le choix final. |
| [Airtasker](https://www.airtasker.com/tasks/) : formulaire initial, accueil public, liste et détail d’une mission | Budget et échéance se repèrent immédiatement. États explicites : ouverte, attribuée, terminée. Offres et questions sont séparées ; les disponibilités figurent dans des propositions. | Transformer le suivi client en un parcours lisible ; structurer la proposition avec prix, périmètre et créneau. | Les fils de propositions publics peuvent devenir longs. Ne pas rendre publiques les coordonnées ou les échanges qui doivent rester privés. |
| [Thumbtack](https://www.thumbtack.com/instant-results/?category_pk=109125193401647362&zip_code=10001) : accueil, recherche localisée, profil | Filtres liés au métier ; délai de réponse affiché ; travaux similaires et avis contextualisés. Profil avec navigation interne, réalisations, FAQ et panneau de demande. | Expliquer pourquoi un prestataire correspond au besoin ; comparer sur autre chose que le prix ; afficher les délais uniquement s’ils sont mesurés. | Beaucoup de critères peuvent surcharger un petit catalogue. La recherche sans code postal a d’abord affiché une erreur, puis fonctionné avec un code postal : les concurrents ne sont pas exempts de friction. |
| [Jiji](https://jiji.ng/services) : accueil, services, fiche de nettoyage | Classement par catégories, localisation, prix, vendeur vérifié. Galerie avec compteur ; champs métier structurés ; conseils de sécurité près du contact. | Reprendre les attributs utiles et la galerie ; distinguer clairement les annonces promues. | Le catalogue mélange de nombreux univers et des offres très diverses. Ne pas transformer le feed de services en catalogue de petites annonces généralistes. |
| [Kaidee](https://www.kaidee.com/product-371544928) : accueil et fiche produit | Une grande photo, miniatures, compteur, prix et caractéristiques distinctes. Les verticales auto/immobilier sont séparées. Le contact et les avertissements sont repérables. | Présenter proprement plusieurs photos et les informations essentielles ; séparer les univers si Prokejem s’élargit plus tard. | Les boutons de contact rapide doivent annoncer leur effet. Les secteurs marchandises, auto et immobilier ne doivent pas encombrer l’accueil de services. |

Sources complémentaires : [fonctionnement d’Airtasker](https://www.airtasker.com/uk/how-it-works/) pour le cycle demande–offres–paiement ; [Yoojo Cover](https://yoojo.fr/yoojo-cover) pour la façon dont une couverture est décrite avec conditions et limites. Cela ne signifie pas que Prokejem possède une couverture équivalente.

## 2. Problèmes constatés sur Prokejem en ligne

### A. Des demandes apparaissent parmi les offres de services — priorité forte

Dans le [feed](https://www.prokejem.fr/feed) puis dans la [liste des offres](https://www.prokejem.fr/ads?type=offres), des annonces intitulées « Cherche aide pour déménagement » et « Cherche un développeur web » sont présentées avec les services proposés. Leur formulation et leur description expriment un besoin, pas une prestation vendue.

Conséquence : le client pense consulter des personnes qui proposent un service, mais rencontre d’autres personnes qui le recherchent.

À faire : diagnostiquer la classification des annonces concernées et les anciens formulaires ; corriger les règles de création, de filtrage et, après validation, les données historiques. Ne pas reclasser ou supprimer en masse uniquement sur le mot « cherche ».

### B. « Voir les profils » n’ouvre pas un annuaire — priorité forte

Le lien conduit à `/ads?type=offres`, dont la page affiche des annonces et « annonces trouvées ». Il ne conduit pas à une liste de fiches prestataires.

À faire : un véritable annuaire centré sur les personnes et leurs services. Un même professionnel doit y apparaître une seule fois par recherche, avec accès à ses spécialités. Vérifier d’abord les composants de recherche de prestataires déjà présents avant de créer un second système.

### C. Absence d’avis présentée comme une mauvaise note — priorité forte

Le [profil public examiné](https://www.prokejem.fr/user/1) affiche « 0.0 (0 avis vérifiés) » ainsi que « 0.0/5 » dans ses statistiques.

À faire : afficher « Nouveau sur Prokejem » ou « Aucun avis pour le moment », sans note numérique. Conserver les vraies mauvaises notes lorsqu’il existe effectivement des avis. Cette distinction est essentielle pour le lancement.

### D. L’information utile est répétée, les preuves arrivent plus bas

Sur le profil : tarif, localisation, ancienneté, vérification et statistiques occupent plusieurs blocs. La galerie est présente, mais précédée d’une grande zone de chiffres et de réassurance. Sur le feed, le conseil concernant la demande sans réponse est répété dans la colonne latérale.

À faire : une identité compacte, une ligne de preuves puis les réalisations et les services. Garder les détails de vérification consultables, sans les répéter partout. Une seule recommandation prioritaire par écran.

### E. Des accès proches ont des destinations différentes

Le bouton du header « Demander un service » pointe vers `/ads/create`, la recherche de besoin vers `/demande`, et les cartes/profils proposent encore d’autres entrées de contact.

À faire : un vocabulaire et un parcours principal cohérents. Depuis un profil : « Décrire mon besoin à ce prestataire », récapitulatif puis confirmation explicite. Depuis l’accueil : demande ouverte aux prestataires compatibles. Ne pas confondre commencer un formulaire, envoyer un message et publier une annonce.

### F. Pertinence et fraîcheur à renforcer

L’échantillon du feed comporte des annonces âgées de plusieurs mois ; un profil situé à Paris apparaît aussi dans la sélection consultée depuis un contexte local à Mayotte. Cela ne prouve pas à lui seul un défaut technique : des données historiques, une zone déclarée ou une sélection de remplacement peuvent l’expliquer.

À faire : privilégier métier, zone d’intervention et activité récente ; expliquer tout élargissement hors zone. Distinguer « encore disponible » et « ancienneté de publication ». Prévoir une confirmation de disponibilité et un archivage maîtrisé, avec possibilité de réactivation, plutôt qu’une suppression silencieuse.

### G. Vérification d’identité payante à réexaminer

La [page de vérification](https://www.prokejem.fr/verification) annonce un paiement de 5 € avant transmission des documents à l’administration. Le prix est visible ; la question est ici commerciale et produit, pas un défaut d’affichage.

Recommandation : étudier une vérification de base prise en charge pendant le lancement, au moins pour les prestataires actifs recrutés dans les premières zones. La confiance contribue au fonctionnement du marché ; elle ne devrait pas devenir principalement une vente de badge. Décision à prendre après chiffrage des coûts et de la capacité de contrôle, sans changer la tarification automatiquement.

## 3. Ce qui existe déjà : améliorer plutôt que reconstruire

| Fonction | Niveau de constat dans cet audit | Suite utile |
|---|---|---|
| Feed différent selon client/prestataire, demande client sans réponse | Feed client observé ; logique des deux rôles lue dans le code | Mieux gérer la pertinence, les doublons et la prochaine action |
| Réalisations professionnelles | Trois photos vues sur un profil ; limite de six et gestion d’ajout présentes dans le code | Ajouter une courte légende, métier et contexte ; vérifier séparément l’ajout multiple sur téléphone |
| Avis liés aux prestations terminées/payées | Explication visible ; contrôleur et vue examinés | Corriger l’état sans avis ; rendre les avis plus utiles à la comparaison |
| Formulaire guidé, brouillon et questions métier | Implémentation locale examinée ; parcours invité complet non rejoué ici | Consolider tous les points d’entrée et la validation ; tests de bout en bout indispensables |
| Comparateur de propositions | Vue et contrôleur présents dans le code | Ajouter si nécessaire périmètre inclus/exclus et frais ; faire vérifier le parcours avec propositions réelles de test |
| Suivi prestataire : nouvelles demandes, propositions, missions, terminées | Vue et test de fonctionnalité présents, non exécutés ici | Le rendre central dans l’espace prestataire ; ajouter une prochaine action par dossier |
| Paiements, litiges, devis et factures | Routes, vues et code présents ; aucun paiement vérifié ici | Tester le cycle réel en environnement adapté et aligner les explications commerciales |
| Recherches sauvegardées, notifications et statistiques | Composants présents dans le code | Les connecter aux besoins sans réponse et aux actions réellement utiles, sans multiplier les alertes |

L’analyse ne conclut donc pas que « tout manque ». Elle montre surtout une fragmentation entre des briques déjà développées.

## 4. Organisation recommandée

### Accueil client

1. **Mon besoin en cours** : état, nouvelles réponses et un bouton principal. Sans demande active, placer la recherche de besoin en premier.
2. **Prestataires pertinents** : petite sélection expliquée par le métier et la zone ; accès à un véritable annuaire.
3. **Services à découvrir** : quelques cartes, sans mélanger offres et demandes. Les besoins des autres clients ne doivent pas dominer cette page.
4. **Un conseil contextuel**, seulement s’il aide à avancer. Les guides complets restent accessibles ailleurs.

La demande sans réponse ne doit pas uniquement demander au client de mieux écrire. Il faut distinguer : manque de détails, absence de prestataires compatibles, prestataires inactifs ou absence de réponse après contact. Proposer une action adaptée : préciser, élargir la zone avec accord, modifier le créneau ou solliciter une aide.

### Accueil prestataire

1. À traiter maintenant : message reçu, proposition à compléter, intervention proche.
2. Demandes correspondant au métier et à la zone, avec date, budget et état des réponses.
3. Mes dossiers : à répondre, proposition envoyée, intervention prévue, terminée.
4. Une seule action pour améliorer son profil, choisie selon ce qui manque réellement.

Un prestataire peut aussi avoir besoin d’un service. Prévoir un accès explicite au parcours client, sans lui faire changer son statut professionnel ni créer un second compte.

### Profil public

Ordre proposé : identité et métier → tarif/zone → preuves réelles → six réalisations → services détaillés → avis → informations complémentaires.

- Photo portrait ou logo professionnel choisi par le titulaire ; proposer des conseils de qualité, sans modifier arbitrairement ses images.
- Carte compacte avec nom lisible, métier pertinent pour la recherche, ville/zone, tarif avec unité et avis lorsqu’ils existent.
- Signe vert pour l’identité vérifiée, avec explication accessible. Ne pas confondre identité, statut professionnel, qualifications, assurance et abonnement.
- Réalisations : conserver six photos, sélection multiple, prévisualisation et légendes. Ne pas présenter une image comme preuve d’une mission réalisée via Prokejem si elle est simplement déclarée par le prestataire.
- Disponibilité déclarée ou délai moyen de réponse mesuré. Aucun « répond en 10 minutes » inventé pour remplir une carte.
- Une action principale, cohérente sur le feed, l’annuaire et le profil.

### Publication et suivi

Conserver un parcours court, mais adapter les questions au service. Exemple : une réparation de fuite n’a pas besoin des mêmes renseignements qu’un déménagement.

Le socle actuel peut être conservé : besoin → lieu/créneau → détails/photos → budget → récapitulatif. Pour l’invité, l’action finale mène à la connexion, puis restaure le brouillon et le récapitulatif. La publication doit rester une confirmation explicite.

Une erreur doit indiquer le champ concerné, expliquer la correction, amener le focus au bon endroit et conserver les autres réponses. Aucun bouton apparemment inactif.

Après publication, afficher un suivi simple : publiée → propositions reçues → prestataire choisi → intervention → terminée. Relier les pages existantes de propositions, messagerie, commande et avis au même dossier.

## 5. Direction esthétique et mobile

Conserver le bleu de Prokejem, des surfaces blanches et un fond neutre très clair. Réserver le vert à la validation, l’ambre à l’attention et le rouge aux erreurs. Réduire les dégradés, ombres et grands blocs de statistiques lorsque plusieurs se concurrencent.

- Même famille de boutons, hauteurs, espacements et rayons de bordure sur feed, annonces, profils et formulaires.
- Cartes d’annonces séparées par un vrai espace ; auteur en haut, état court, une seule action principale.
- Mobile : une photo représentative avec compteur ; ouverture sur la galerie complète. Images agrandies contenues dans l’écran, sans déformation ; accès au fichier original si pertinent.
- Pas de nom tronqué arbitrairement à une longueur fixe lorsque deux lignes restent possibles.
- Profil : onglets ou ancres courts « Services », « Réalisations », « Avis », « À propos ». Les informations légitimes restent accessibles sans allonger l’écran d’accueil.
- Navigation basse cohérente : Accueil, Explorer, Publier, Messages, Profil. Si « Mes demandes » devient indispensable, la placer clairement dans l’accueil et le compte plutôt que multiplier les icônes.
- Réaction visuelle immédiate au toucher, état de chargement, prévention des doubles envois et restauration de la position au retour. Ne pas précharger les pages privées ou mettre les données sensibles en cache sans règle explicite.

Ce sont des spécifications à tester, pas une affirmation que la vitesse mobile a été corrigée. Vérification à prévoir à 360, 390, 430, 768 et 1440 px, puis sur au moins un téléphone Android et un iPhone disponibles.

## 6. Politique de fonctionnement et outils de gestion

### Priorité au marché local, pas au volume du catalogue

Commencer par quelques couples métier/zone réellement pourvus. Une catégorie visible ne constitue pas une offre disponible. Si personne ne couvre un besoin, le dire et proposer une solution de repli honnête. Ne pas remplir artificiellement le feed de vieilles annonces pour donner une impression d’activité.

### Confiance et commercialisation

- Critères de vérification compréhensibles ; état du dossier, motif de refus et possibilité de complément.
- Prix total, frais et conditions de paiement avant engagement ; même vocabulaire dans les pages, devis, commande et aide.
- Séparer une priorité payante d’une recommandation de qualité ; marquer les emplacements sponsorisés.
- Ne pas promettre assurance, remboursement garanti, avantage fiscal ou qualification professionnelle sans dispositif vérifié et conditions adaptées. La page Yoojo Cover montre justement qu’une protection s’accompagne de plafonds, d’exclusions et de procédures ; ce n’est pas un simple élément de design.
- Garder les abonnements au second plan tant que les prestataires n’obtiennent pas suffisamment de demandes utiles. Monétiser ensuite un bénéfice concret, pas la promesse de travail non démontrée.

Les règles contractuelles, les justificatifs professionnels requis et la conservation des documents doivent être validés séparément avec les compétences appropriées. Cet audit n’est pas une consultation juridique.

### Administration orientée vers les problèmes à résoudre

À intégrer aux outils existants : une file de demandes sans réponse, les dossiers de vérification en attente, les signalements, les litiges et les incidents de paiement. Pour chaque dossier : état, responsable, prochaine action, historique et échéance interne. Ne pas modifier ni relancer une demande au nom d’un client sans cadre et accord adaptés.

### Mesure

Le tableau d’usage local calcule déjà des volumes de publication, un taux de demandes avec proposition et un délai médian de première proposition. Le service d’événements examiné se limite actuellement aux vues de page, sessions, installation PWA et activation des notifications : il faut compléter la mesure du formulaire étape par étape.

Indicateurs à suivre :

1. Passage de chaque étape du formulaire à la suivante, erreurs et abandon, séparés entre invité/connecté et mobile/ordinateur.
2. Part des demandes obtenant une proposition utile, avec une fenêtre d’observation commune.
3. Délai médian et proportion de demandes sans réponse, en incluant explicitement les dossiers toujours en attente.
4. Proposition acceptée → intervention terminée ; motifs d’annulation.
5. Réutilisation de la plateforme par clients et prestataires.

Mesurer avec des événements limités et des agrégats, sans enregistrer les textes de demandes, documents d’identité ou données de paiement dans les outils analytiques. Ne pas présenter un simple ratio visites/publications comme un suivi exact d’utilisateurs uniques.

## 7. Ordre de réalisation recommandé

| Lot | Travail | Critère de livraison |
|---|---|---|
| 1 — Cohérence et fiabilité | Classification offres/demandes ; lien annuaire ; état sans avis ; points d’entrée de publication ; validation et reprise invité | Parcours réellement rejoués avec deux rôles et un invité, sans envoi involontaire ; tests de régression ciblés ; preuve de l’URL et de l’état final |
| 2 — Annuaire et profils | Annuaire métier/zone ; cartes cohérentes ; profil compact ; galerie contextualisée ; suppression des répétitions | Recherche, profil et demande ciblée raccordés ; pas de faux avis, de faux badges ou de faux délais ; contrôle mobile |
| 3 — Faire aboutir les demandes | Suivi client unifié ; traitement des sans-réponse ; disponibilité ; intégration du suivi prestataire et du comparateur existants | Un scénario complet demande → proposition → choix → intervention fonctionne dans un environnement de test adapté ; états d’échec et d’annulation inclus |
| 4 — Exploitation et croissance | File administrative ; mesure du parcours ; politique de lancement ; reprise de contact utile et services récurrents | Données de référence disponibles ; règles de relance et consentement définis ; décision de monétisation fondée sur l’usage |

Ne pas lancer immédiatement : messagerie vidéo, suivi GPS en direct, application native complète, classement opaque par IA, dizaines de nouvelles catégories ou garantie commerciale sans organisation derrière.

Chaque lot doit être livré dans un changement limité, validé avant puis après déploiement. Le passage au lot suivant dépend du résultat constaté, pas d’un nombre de fichiers modifiés.

## 8. Repères techniques pour la reprise

Fichiers inspectés, à revérifier dans la branche de travail avant modification :

- `C:/Users/PC/Desktop/PROKEJEM/app/Http/Controllers/FeedController.php`
- `C:/Users/PC/Desktop/PROKEJEM/resources/views/feed/index.blade.php`
- `C:/Users/PC/Desktop/PROKEJEM/resources/views/feed/partials/providers.blade.php`
- `C:/Users/PC/Desktop/PROKEJEM/resources/views/feed/partials/state-card.blade.php`
- `C:/Users/PC/Desktop/PROKEJEM/resources/views/profile/public.blade.php`
- `C:/Users/PC/Desktop/PROKEJEM/app/Http/Controllers/ProfileController.php`
- `C:/Users/PC/Desktop/PROKEJEM/app/Models/ProfessionalRealization.php`
- `C:/Users/PC/Desktop/PROKEJEM/resources/views/demands/create.blade.php`
- `C:/Users/PC/Desktop/PROKEJEM/app/Support/ServiceDemandIntakeSchema.php`
- `C:/Users/PC/Desktop/PROKEJEM/resources/views/proposals/compare.blade.php`
- `C:/Users/PC/Desktop/PROKEJEM/resources/views/pro/opportunities.blade.php`
- `C:/Users/PC/Desktop/PROKEJEM/app/Http/Controllers/Admin/UsageDashboardController.php`
- `C:/Users/PC/Desktop/PROKEJEM/app/Services/UsageAnalytics.php`
- `C:/Users/PC/Desktop/PROKEJEM/tests/Feature/CommercialPolicyConsistencyFeatureTest.php`

Livraison de cet audit : ce rapport uniquement. Aucun code applicatif, compte concurrent, tarif ou donnée de production n’a été modifié. Aucun commit ni déploiement effectué.

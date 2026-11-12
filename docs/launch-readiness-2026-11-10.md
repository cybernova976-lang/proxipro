# PROKEJEM — confiance, offre Premium et préparation du lancement

Audit du 10 au 12 novembre 2026 sur la branche `codex/prokejem-pwa-mobile` du dépôt `cybernova976-lang/proxipro`.

## Décision recommandée

Préparer un lancement pilote concentré sur un territoire et quelques services, avant une campagne de grande ampleur. La plateforme possède déjà les fonctions d'une marketplace. Son avantage doit venir de la facilité à obtenir une réponse pertinente, de la confiance et du suivi de la prestation. Ajouter des fonctions ne compense pas un manque de prestataires actifs ou un parcours de paiement ambigu.

Conserver Laravel et les parcours existants qui fonctionnent. Ne pas lancer une réécriture ni des microservices pour anticiper une fréquentation encore inconnue. Consolider les frontières entre publications, commandes, facturation, avantages Premium et communications.

## Corrections de ce lot

| Parcours | Constat dans le code initial | Changement |
| --- | --- | --- |
| Suppression du compte | Effacement des abonnements locaux sans arrêt de leur facturation Stripe | Annulation des abonnements distants avant tout effacement. Recherche également des abonnements du client Stripe dont le webhook n'a pas encore créé la ligne locale. Si Stripe échoue, le compte est conservé. |
| Abonnement impayé | Seule la relation des abonnements actifs alimentait le panneau ; la facturation pouvait rester ouverte sans outil visible | Affichage de la facturation à régulariser, accès au portail et résiliation. Gestion disponible même après un changement de statut professionnel. |
| Souscriptions répétées | Plusieurs onglets ou un retour tardif de Stripe pouvaient ouvrir plusieurs paiements | Les trois parcours de souscription prennent le même verrou par compte. Une seconde demande reprend le paiement ouvert pour la même formule et le même prix. Un changement de formule ferme l’ancien lien. Le serveur vérifie les abonnements sur Stripe avant de créer un paiement, même si le webhook est encore en route. |
| Montants et historique | Prix fixes dans la vue et libellé calculé avec le tarif actuel, même pour un ancien contrat | Montant enregistré sur chaque abonnement. Dates manquantes tolérées. Factures récurrentes accessibles par le portail Stripe. |
| Résiliation | Comportement disponible, information insuffisamment visible avant achat | Explication publique et dans l'espace Premium : renouvellement, paiement annuel en une fois, arrêt à tout moment, maintien de l'accès payé, absence de remboursement automatique. |
| Paramètres | Deux interfaces distinctes à maintenir | Un composant commun, des accès directs et des rubriques accessibles sans dépendre d'onglets JavaScript. |
| Emails d'activité | Plusieurs notifications ignoraient l'option générale ; les alertes métier ne la consultaient pas | Respect de l'option et vérification supplémentaire à l'exécution des notifications mises en file. Les réglages navigateur restent propres à chaque appareil. |
| Newsletters | Consentement à l'inscription et composition administrateur existants, mais réglage absent du centre de compte | Choix explicite dans le compte ; lien signé de désinscription sans connexion. GET affiche une confirmation ; POST effectue la désinscription, afin qu'un robot de prévisualisation ne désinscrive pas un membre. |
| Rappels de demandes | Rappel unique préparé après 24 h, puis validation administrateur. La pertinence du message n’était plus vérifiée à l’envoi. | Les préférences et le brouillon sont vérifiés avant l’envoi. L’administration voit aussi les brouillons arrivés à échéance et peut préparer jusqu’à 50 rappels à relire, même si la tâche planifiée a été interrompue. |
| Gestion des publications | Liste sans classement clair des brouillons, et tableau de bord non paginé | Écran commun avec brouillon de demande, publications actives, en validation et terminées ; reprise, modification, archivage et republication ; pagination et quota visible. |
| Plafond gratuit existant | Le formulaire d'annonce appliquait le plafond du compte, le formulaire de demande le contournait | Même contrôle lors d'une nouvelle demande, sans perdre le brouillon. Aucun nouveau tarif ni nouveau plafond commercial dans ce lot. |

Le brouillon conservé sur le serveur concerne le parcours de demande existant, avec un brouillon par compte. La sauvegarde multi-brouillons des offres n'est pas ajoutée dans ce lot. Les photos des demandes inachevées restent propres à l'appareil, ce qui est expliqué dans la nouvelle liste.

## Ce qui mérite d'être conservé

- Le parcours guidé de demande, la validation explicite avant publication et le contrôle des doublons.
- La séparation entre statut professionnel, identité vérifiée et abonnement Premium.
- Les suggestions territoriales et la priorité des offres sponsorisées, complétées par des offres ordinaires quand nécessaire.
- Les paiements récurrents Stripe, les webhooks et la résiliation à l'échéance déjà implémentés.
- Les commandes, propositions, messagerie, avis liés aux prestations et outils professionnels existants.
- La préparation des newsletters et des relances dans une file de validation administrateur. Aucun envoi de campagne n'a été réalisé pendant cette intervention.
- Les recherches enregistrées, les contrôles de notifications sur chaque appareil et les statistiques de profil.

## Gratuit / Premium : positionnement conseillé

Le gratuit doit permettre de découvrir, publier un besoin, échanger et obtenir une première expérience utile. Les réglages, la confidentialité, le départ et l'accès à ses factures ne doivent pas servir de leviers de conversion.

Les tarifs par défaut constatés dans le code sont de 9,99 € par mois et 85 € par an. Les paramètres administrateur peuvent les remplacer. Cette intervention ne change pas les tarifs et n'active pas la commercialisation.

Contrôle en production pendant l'audit : `proSubscriptionsRequested()` retourne `true` et aucun contrôle de la checklist configurée n'est manquant. L'offre est donc déjà activée. Cette checklist ne prouve pas un cycle bancaire complet ni la réussite d'une vraie notification chez son destinataire.

La limite actuelle dépend surtout du statut : généralement 5 annonces pour un particulier, 10 ou 15 pour un auto-entrepreneur, 20 ou 1 000 pour une entreprise selon sa vérification, avec un champ de quota propre au compte pour les autres cas. Ce système distingue mal activité déclarée et besoin d'outillage payant. Le compteur affiché utilise les règles du serveur, pas une limite inventée dans la vue.

Le CRM et les statistiques sont déjà accessibles aux prestataires sans abonnement ; il serait trompeur de les présenter comme exclusivement Premium. La comparaison livrée les indique dans les deux colonnes. Les avantages actuellement appliqués sont notamment 4 photos par offre au lieu de 2, la visibilité sponsorisée, l'ordre des suggestions éligibles et la réduction de 20 % sur les boosts et l'option Urgent. Le parcours guidé de demande reste limité à 2 photos.

### Hypothèse commerciale à expérimenter, non appliquée

| Usage | Gratuit durable | Premium enrichi à construire | Raison de payer |
| --- | --- | --- | --- |
| Offres de services simultanées | 3 offres distinctes | 10 offres distinctes | Présenter plusieurs prestations, sans permettre les doublons |
| Recherche et contact | Consultation et échanges conservés | Filtres sauvegardés plus précis et synthèse d'opportunités | Économiser du temps de recherche |
| Gestion de clientèle | Conserver les fonctions déjà acquises | Modèles personnels, classement et relances préparées | Réduire le travail administratif |
| Présentation | Profil et portfolio de base | Portfolio enrichi et présentation guidée des réalisations | Mieux démontrer son travail |
| Mesure de la valeur | Compteurs simples | Bilan d'activité et parcours de conversion | Comprendre ce que l'abonnement apporte |
| Assistance | Support normal | Priorité avec délai annoncé seulement si l'équipe peut le tenir | Réduire les interruptions de travail |

Les nombres 3 et 10 sont des hypothèses de test, pas des droits actuels ni une décision déployée. Avant tout changement : estimer les comptes concernés, prévenir les membres et préserver leurs contenus. Ne pas confondre identité vérifiée et quota payant. Mesurer conversion, rétention et baisse éventuelle des publications avant généralisation.

## Services Premium à prioriser

1. **Bilan d'activité utile.** Réunir vues, contacts, propositions et missions conclues, avec une période et une définition précises. Montrer zéro lorsque zéro est mesuré. Instrumenter les contacts attribuables à la plateforme avant de promettre une mesure du retour sur abonnement ; les vues actuelles ne prouvent pas cet effet.
2. **Assistant de réponse.** Préparer une réponse avec disponibilité, questions manquantes et périmètre du service. Le prestataire valide le texte et le prix. Ne jamais inventer ses qualifications ou envoyer une proposition en son nom sans action explicite.
3. **Modèles et suivi des devis.** Modèles personnels, suivi des devis en attente et préparation de rappels. Réutiliser les documents existants, conserver les contrôles de statut professionnel et distinguer création, émission et paiement.
4. **Disponibilités et demandes compatibles.** Permettre au prestataire d'indiquer ses créneaux et sa zone réelle ; réduire les sollicitations impossibles à honorer. Mesurer la qualité des correspondances avant d'en faire une promesse commerciale.
5. **Services récurrents.** Proposer au client, après une prestation réalisée, un rappel d'entretien choisi par lui. Les rappels existants sont une base. Une nouvelle commande ou un nouveau paiement doit toujours être clairement confirmé.
6. **Portfolio et preuve du travail.** Aider à décrire les réalisations avec photos, contexte et avis authentiques. Le paiement Premium ne doit jamais acheter une certification ni un avis favorable.

La priorité économique est le temps gagné et le travail obtenu, puis la visibilité. Éviter de vendre une promesse de volume de clients tant qu'elle n'est pas démontrable.

## Fidélisation sans saturation

| Déclencheur | Communication utile | Limite proposée |
| --- | --- | --- |
| Demande inachevée | Reprendre précisément le brouillon | Au maximum une relance après 24 h ; option de désactivation livrée |
| Nouvelle proposition ou message | Action à traiter, lien direct | Événement réel ; option générale d'email respectée |
| Recherche enregistrée | Nouvelle annonce correspondant aux critères | Ajouter ensuite un récapitulatif plutôt que multiplier les messages |
| Fin de mission | Avis, réutilisation d'un prestataire, rappel choisi | Éviter les relances après litige ou désactivation |
| Abonnement | Échéance, paiement échoué, récapitulatif de valeur | Distinguer la facturation nécessaire de la promotion facultative |
| Newsletter | Nouveautés concrètes et conseils du territoire | Consentement et désinscription simple ; fréquence à annoncer et respecter |

Le mécanisme historique de récompense de parrainage est désactivé : `ReferralService::grantFirstPurchaseRewards()` retourne `false`. Ne pas annoncer de cadeau en points. Pour une future version, envisager un avantage plafonné après une première prestation réellement payée et terminée, avec contrôle des auto-parrainages et des remboursements. Chiffrer la marge avant d'offrir un mois d'abonnement ou un crédit ; rien de cela n'est activé dans ce lot.

## Architecture et exploitation avant montée en charge

| Sujet | Évidence de l'audit | Suite recommandée |
| --- | --- | --- |
| Serveur web | Railway utilise actuellement `php artisan migrate --force && php artisan serve ...` | Préparer un serveur adapté à la production et un test de charge ; ne pas déduire une capacité internationale du seul HTTP 200 |
| Communications | Configuration en production : queue `sync`, SMTP | Déporter les envois dans une file supervisée, après vérification de la reprise, des échecs, du consentement au moment d'envoyer et de l'idempotence |
| Tâches périodiques | Le 11 novembre, le conteneur web n’exécutait pas le planificateur. Le démarrage Railway lance désormais le planificateur avec le site. Les rappels dus sont préparés toutes les 15 minutes. | Suivre l’heure du dernier passage et les échecs dans la durée. L’administration peut aussi préparer les rappels depuis « E-mails à valider ». La purge définitive des comptes supprimés est désactivée par défaut. |
| Cache | Configuration en production : cache fichier, sessions en base | Évaluer un cache partagé avant plusieurs instances et mesurer la contention |
| Facturation | Stripe assure les cycles ; la base locale porte l'état d'accès | Tester en mode Stripe test les webhooks réels, une échéance, un impayé, une reprise, une résiliation et des checkouts simultanés |
| Données de compte | La suppression historique efface des relations de paiement et conserve un journal nominatif | Clarifier et tester conservation comptable, anonymisation, fichiers, commandes en cours et suppression ; ce lot ne certifie pas toute la politique de rétention |
| Sauvegardes | Aucun test de restauration effectué dans cet audit | Réaliser et chronométrer une restauration avant promotion importante |
| International | Interface française et facturation de ces offres en euros ; environnement serveur UTC | Avancer pays par pays : langue, devise, fiscalité, moyens de paiement, fuseaux, support et disponibilité des prestataires |

Le découpage conseillé reste un monolithe modulaire. Extraire progressivement les règles d'accès commercial dans un service unique afin que la page tarifaire, les formulaires, les quotas et les actions serveur lisent les mêmes droits. Éviter un nouveau booléen Premium indépendant des abonnements réellement valides.

## Périmètre de validation

Les tests fonctionnels couvrent la propriété des publications, la pagination, le quota de demande, le respect des préférences, la désinscription signée, les rappels devenus obsolètes, la suppression avec ou sans disponibilité Stripe et le maintien des avantages jusqu'au terme payé. Les appels Stripe sont simulés, sans prélèvement réel.

Sur la version du 12 novembre, **653 tests et 6 980 assertions ont réussi**. Les vues Blade ont été compilées. Le rapport JUnit local est conservé dans `tmp/account-review/phpunit-scheduler-release.xml`.

L’inventaire statique a repéré 123 vues Blade de page, dont 26 d’administration, en excluant les composants, gabarits partagés, emails et modèles PDF. Les 123 vues déclarent la largeur d’écran mobile ou en héritent de leur gabarit. L’inventaire inclut aussi des écrans historiques. Une déclaration de largeur ne suffit pas à valider la mise en page.

Le navigateur a contrôlé 34 pages et états à 1 440, 768, 390 et 360 pixels, soit 136 rendus locaux. Aucun débordement horizontal de la page ni exception JavaScript n’a été relevé. Les contrôles couvrent notamment l’accueil, les offres, la recherche, la fiche et l’édition d’annonce, la demande guidée, les commandes, les favoris, les paramètres, la messagerie, les clients, les devis, les factures, les statistiques, la désinscription aux newsletters, l’administration des rappels et quatre états d’abonnement. Les rendus proviennent des routes Laravel avec des comptes fictifs et une base en mémoire. Les résultats et captures sont conservés dans `tmp/account-review/`.

Un second passage a bloqué le téléchargement de Chart.js sur les quatre largeurs. Le chiffre d’affaires de douze mois, les nouveaux clients de six mois et les nombres de devis acceptés et autres restent lisibles. Les tables servent également de description aux lecteurs d’écran lorsque les graphiques apparaissent.

Au premier contrôle de la page publique sur téléphone, les colonnes du comparatif coupaient certains mots. Le comparatif présente désormais le nom de chaque fonctionnalité sur sa propre ligne, puis les deux formules côte à côte. Les parcours de souscription décrivent les avantages Premium effectivement disponibles et indiquent comment continuer si les paiements sont suspendus. Au dernier passage local, la feuille de police Inter de Google Fonts n’a pas répondu. Les mesures de mise en page sont restées valides avec la police de secours.

Les pages d'administration, chaque formulaire spécialisé, les PDF de facturation, tous les états vides et tous les cas d'accessibilité ne font pas l'objet d'une validation visuelle exhaustive dans ce lot. Le rapport de livraison distingue tests, contrôles locaux et contrôles publics en production. Ni la réception dans une vraie boîte mail, ni une transaction bancaire réelle ne sont prouvées par ces vérifications.

## Déroulement de lancement conseillé

1. **Fiabilité.** Terminer les validations de facturation, d'ordonnancement, de restauration et de délivrabilité. Vérifier les accès essentiels sur un vrai téléphone. Finaliser les informations d'exploitant nécessaires à l'offre.
2. **Pilote local.** Recruter un groupe limité de clients et de prestataires réellement disponibles dans quelques catégories. Observer le délai de première réponse, les demandes sans proposition, les missions terminées, les incidents et les sollicitations du support.
3. **Valeur Premium.** Tester le bilan d'activité et un outil de gain de temps avant d'ajouter de nouvelles restrictions. Suivre activation, renouvellement, résiliation et motifs d'insatisfaction.
4. **Expansion progressive.** Étendre un territoire à la fois lorsqu'il possède assez d'offre et que l'équipe maîtrise son support. Construire l'international à partir d'un fonctionnement local reproductible.

Exemples de seuils internes à fixer pour le pilote, non résultats acquis : au moins 70 % des demandes recevables avec une réponse pertinente en 24 h ; une dizaine de missions réellement terminées ; aucune anomalie de double paiement non résolue ; un délai de support compatible avec les engagements affichés. Ces seuils sont une proposition de pilotage, pas une prédiction de succès.

## Références consultées

- [Stripe — annulation des abonnements](https://docs.stripe.com/billing/subscriptions/cancel) : arrêt immédiat ou à l'échéance, prorata et facturation finale.
- [Stripe — portail client](https://docs.stripe.com/customer-management) : gestion des paiements, abonnements et factures.
- Stripe documente les opérations utilisées pour identifier les paiements ouverts et fermer un lien avant un changement de formule. [Liste des sessions Checkout](https://docs.stripe.com/api/checkout/sessions/list) et [expiration d’une session](https://docs.stripe.com/api/checkout/sessions/expire).
- [CNIL — communications électroniques](https://www.cnil.fr/fr/communication-electronique-quelles-regles) : distinction des communications et possibilité de désinscription. Les textes locaux doivent être vérifiés pour chaque pays d'expansion.

Ces références expliquent les choix de conception. Elles ne remplacent pas la validation des conditions commerciales et obligations propres à l'exploitant.

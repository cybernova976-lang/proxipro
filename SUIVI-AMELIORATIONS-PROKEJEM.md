# Suivi des améliorations de Prokejem

## 6 septembre 2026 — Lot 1 : cohérence et fiabilité

Référence : [audit concurrentiel du 5 septembre](AUDIT-CONCURRENTIEL-PROKEJEM-2026-09-05.md).

État à la fin de la validation locale, avant livraison : corrections du lot 1 enregistrées et validées. Les données historiques et la production n'ont pas été modifiées pendant ces travaux. Le commit et le déploiement ont ensuite été autorisés par l'utilisateur le 6 septembre 2026 ; leur résultat doit être confirmé séparément par les références Git et Railway.

### Modifications enregistrées

- Les liens « Voir les profils » et « Consulter les prestataires » du feed ouvrent l'annuaire, pas la liste d'annonces.
- L'annuaire pleine page inclut les professionnels et particuliers prestataires actifs dont le profil est public, même sans abonnement. Pagination de 12 profils, ordre alphabétique stable, filtre par catégorie. Une demande personnelle ne sert pas de preuve de compétence pour ce filtre. Le widget promotionnel AJAX existant conserve son fonctionnement.
- Les notes de l'annuaire utilisent les avis liés à une prestation terminée et payée. Sans avis, l'annuaire et le profil public n'affichent plus de fausse note « 0/5 ».
- « Demander un service » dans le header ouvre le parcours guidé pour les deux rôles. La publication d'une offre par la navigation prestataire reste explicite (`type=offre`). Le formulaire générique ne déduit plus l'intention « offre » du seul statut professionnel ; les anciens liens `type=service` restent compatibles. Le nettoyage des paramètres de préremplissage conserve désormais le type : recharger une offre ne la transforme plus en demande.
- Le visiteur arrivé à l'étape 5 passe par la connexion, puis revient à son récapitulatif. La continuation n'accepte aucune URL arbitraire. Les échecs de connexion et la vérification e-mail conservent ce contexte. L'inscription et le retour OAuth utilisent la même destination autorisée.
- Le brouillon est revalidé au retour : une date passée ou un champ manquant renvoie à l'étape concernée. Il n'y a aucune publication automatique ; la case de confirmation reste décochée. Les photos ne sont pas conservées dans le brouillon local.
- L'ancien menu latéral sans destinations utiles est retiré de l'annuaire et du parcours de demande. Les fenêtres de configuration du prestataire ne recouvrent plus le parcours de demande ni les formulaires de création/modification d'annonce.
- Le bouton d'installation PWA est masqué dans les formulaires de demande/publication et de connexion/inscription. Ailleurs sur mobile, il est placé au-dessus de la navigation basse.

### Contrôles

- Avant la dernière interruption : suite complète à 258 tests réussis, puis derniers ajustements d'affichage des fenêtres et de l'installation PWA.
- Résultat final du 6 septembre, après les derniers correctifs : **258 tests réussis, 1 738 assertions**, durée 65,19 s. Rapport persistant dans `storage/logs/lot1-tests-2026-09-06.xml`.
- Contrôle préalable à la livraison demandée : 24 tests ciblés réussis (175 assertions), puis 258 tests réussis (1 738 assertions), compilation Blade complète et `git diff --check` réussis. Rapport dans `storage/logs/lot1-deployment-tests-2026-09-06.xml`.
- Le contrôle de reprise a détecté une erreur de compilation Blade dans le dernier ajustement PWA : `@json` ne doit pas recevoir cette expression contenant plusieurs virgules. L'expression est désormais calculée dans une variable PHP avant sérialisation. Le même usage fragile est corrigé dans le lien de connexion du formulaire. La suite a été interrompue puis relancée après correction ; aucun déploiement de la version erronée.
- Deux tests ont été adaptés sans supprimer leur contrôle : rechercher la balise de navigation, pas son nom cité dans une règle CSS ; vérifier le rendu réel de la messagerie pour l'interdiction de l'invite PWA, pas l'ancienne orthographe de la condition PHP.
- `git diff --check` et contrôle de style PHP des huit fichiers ciblés : réussis.
- Build Vite : réussi le 5 septembre ; avertissements Sass de dépréciation existants, sans échec de compilation. Les modifications suivantes concernent du PHP/Blade, sans modification du bundle Vite.
- Chrome, base SQLite locale isolée : champs obligatoires signalés, récapitulatif rempli, étape 5 vers connexion, puis retour après connexion à `/demande?resume=1` avec les mêmes réponses et confirmation décochée. Scénario rejoué à une largeur mobile demandée de 390 px ; largeur utile constatée de 382 px, sans débordement horizontal sur le récapitulatif.
- Chrome : lien annuaire, filtre de catégorie et accès à un profil sans avis vérifiés. Annuaire inspecté sur ordinateur et à 390 px.
- Reprise du 6 septembre dans le navigateur intégré (Chrome déconnecté) : connexion avec le compte de démonstration vers `/demande?resume=1`, aucune fenêtre de configuration sur la demande ; clic sur « Publier » du menu bas vers `/ads/create?type=offre`, choix Offre effectivement coché, conservé après rechargement, aucune fenêtre de configuration sur ce formulaire et bouton d'installation masqué. Aucune annonce envoyée.
- Taille mobile demandée de 390 × 844 dans le navigateur intégré, mais largeur utile mesurée de 425 px ; `scrollWidth` = `clientWidth` = 425. Cette vérification ne vaut donc pas un test exact à 390 px ni un test sur téléphone réel. Taille du navigateur rétablie ensuite.
- La position de l'invite PWA hors formulaire est corrigée dans le CSS ; l'apparition de la véritable invite d'installation reste à revérifier sur un navigateur/téléphone compatible.

### Limites et suite

- Les annonces historiques potentiellement mal classées ne sont pas requalifiées automatiquement. Une vérification individuelle reste nécessaire avant toute correction de production.
- Les tests OAuth sont simulés : ils ne prouvent pas la configuration Google/Facebook réelle en production.
- Un test sur un vrai téléphone Android/iPhone et les contrôles après déploiement restent nécessaires ; l'émulation Chrome ne les remplace pas.
- Le lot 2 est documenté ci-dessous. Les lots 3 (suivi des demandes) et 4 (exploitation/mesure) ne sont pas annoncés comme réalisés.

## 7 septembre 2026 — Lot 2 : annuaire et profils

État avant livraison : implémentation et validation locale terminées. Le commit, l'envoi GitHub et le déploiement Railway doivent encore être confirmés séparément.

### Modifications locales

- L'annuaire recherche les prestataires publics actifs par nom, métier, service actif, ville déclarée et pays/territoire. Les caractères `%` et `_` sont traités comme du texte, pas comme des jokers SQL. La recherche ne s'élargit pas automatiquement lorsqu'elle ne trouve rien.
- La ville est un filtre exact sur la ville déclarée du profil. Ce n'est pas encore une recherche par rayon, une preuve de disponibilité ni une promesse de déplacement ; cette limite est indiquée à l'utilisateur.
- Les cartes d'annuaire affichent le nom complet, le métier, le lieu, le tarif public déclaré et les avis vérifiés lorsqu'ils existent. Le signe vert signifie uniquement « identité vérifiée ». Deux actions restent visibles : voir le profil et décrire le besoin.
- Les cartes du feed et celles de l'annuaire conduisent désormais vers la même fenêtre de contact du profil. Le texte est saisi et validé par le client ; aucun message prérempli n'est envoyé par un clic sur la carte.
- Le profil public est raccourci et structuré par ancres : Réalisations, Services, Avis et À propos. Les informations secondaires, repères de confiance et annonces sont repliables ; les indicateurs répétés ont été retirés.
- La galerie publique affiche le contexte déclaré de chaque photo, une légende facultative, un compteur, les commandes précédente/suivante et l'accès au fichier original. Elle ne prétend pas qu'une photo prouve une mission Prokejem.
- L'éditeur accepte toujours six photos maximum, y compris une sélection multiple ou plusieurs sélections successives. Les légendes restent associées à leur fichier après ajout ou retrait d'une photo. Un utilisateur ne peut modifier que les légendes de ses propres réalisations.

### Contrôles locaux

- 39 tests ciblés réussis, 353 assertions.
- Suite complète finale : **267 tests réussis, 1 807 assertions**. Rapport dans `storage/logs/lot2-final-tests.xml`.
- Trois tests JavaScript dédiés à la sélection multiple : accumulation, conservation des légendes après retrait, doublons/formats/limite de six. Tous réussis.
- Compilation Vite finale réussie en 38,89 s ; avertissements Sass de dépréciation déjà connus, sans échec.
- Compilation Blade, format PHP et `git diff --check` réussis.
- Chrome local : annuaire filtré sur `plomb` + `Mamoudzou` + `Mayotte`, un résultat attendu ; nom complet et deux actions visibles ; largeur utile mobile constatée de 382 px pour 390 px demandés, sans débordement horizontal.
- Chrome local : après remplacement de la ville par `Ville sans profil` et clic réel sur « Rechercher », l'URL est mise à jour, le compteur passe à zéro et l'état vide attendu s'affiche. Les filtres actifs restent visibles.
- Chrome local : fenêtre de contact ouverte pour le prestataire ciblé et annulée sans envoi. Le formulaire indique explicitement qu'aucun message ne part avant validation.
- Chrome local : galerie ouverte, photo suivante et précédente fonctionnelles, compteur `1 / 2` puis `2 / 2`, images chargées et contenues dans l'écran. En largeur utile 382 px, `scrollWidth` = 382 px.
- Le sélecteur HTML est bien multiple. L'insertion automatisée de fichiers dans Chrome a été bloquée par l'autorisation « Allow access to file URLs » de l'extension ; la logique d'accumulation a donc été contrôlée par tests JavaScript et le stockage de six fichiers par tests Laravel, pas par un enregistrement manuel dans Chrome.

### Limites avant livraison

- Exécuter la nouvelle migration `caption` est obligatoire au déploiement.
- Un test manuel sur un vrai téléphone reste nécessaire, notamment le sélecteur photo natif Android/iPhone.
- Le statut final du déploiement Railway du lot 1 doit toujours être revérifié ; cela ne change pas l'état local du lot 2.

## 7 septembre 2026 — Lot 3 : suivi unifié des demandes

État actuel : implémentation, validation locale et livraison terminées. Commit `035e2c20` envoyé sur `codex/prokejem-pwa-mobile` ; déploiement Railway `ede64b6b-6b09-44b5-b6ba-9a6b77d824ad` réussi le 7 septembre 2026. Le service `web`, `/up` et la feuille de style dédiée répondaient correctement après déploiement.

### Modifications locales

- Une page client dédiée `/mes-demandes` réunit les demandes publiées et leur progression : publiée, propositions, prestataire choisi, mission en cours et terminée.
- Chaque demande affiche un état compréhensible, une explication et une seule action principale adaptée : améliorer la demande, comparer les propositions, finaliser le paiement ou suivre la mission.
- Les demandes anciennes sans réponse sont mises en évidence sans fausse promesse de mise en relation.
- Le résumé distingue les demandes en recherche, celles avec réponses à examiner et les missions actives.
- Le feed mène directement vers la demande suivie et limite son bloc principal à deux actions. Le menu mobile client remplace « Annonces » par « Suivi » ; le menu professionnel conserve « Annonces ».
- Les commandes possèdent une ancre stable afin qu'une action du suivi ouvre directement la mission concernée.

### Contrôles locaux

- 25 tests fonctionnels ciblés réussis, 182 assertions.
- Suite complète finale : **270 tests réussis, 1 838 assertions**.
- Format PHP, compilation Blade et `git diff --check` réussis.
- Compilation Vite réussie ; avertissements Sass de dépréciation déjà connus, sans échec.
- Navigateur local : état vide puis demande sans proposition contrôlés avec une base SQLite isolée. Le statut, l'action corrective et les cinq étapes sont visibles.
- Navigation mobile : clic réel sur « Suivi » depuis le feed, arrivée sur `/mes-demandes`, titre attendu et onglet « Suivi » actif. La largeur utile constatée par l'outil était de 433 px malgré une consigne de 390 px ; aucun débordement horizontal n'a été mesuré (`scrollWidth` 426 px pour `innerWidth` 433 px).

### Limites avant livraison

- Le test responsive automatisé ne remplace pas un contrôle sur un téléphone Android ou iPhone réel.
- Les données utilisées pour le contrôle visuel sont uniquement locales et ne doivent pas être confondues avec les données de production.
- La route authentifiée a été contrôlée visuellement en local. En production, le contrôle sans session a confirmé la redirection attendue vers `/login`, pas le contenu privé d'un compte réel.

## 7 septembre 2026 — Lot 4 : exploitation et mesure

État actuel : implémentation et validation locale terminées. Aucun commit, envoi GitHub ou déploiement Railway n'a encore été effectué pour ce lot.

### Modifications locales

- Le parcours de demande mesure maintenant l’affichage de chacune des cinq étapes, les erreurs de validation, le passage d’un invité vers la connexion et la reprise du brouillon après authentification.
- Les compteurs distinguent invité/connecté, mobile/tablette/ordinateur et navigateur/PWA. Ils restent journaliers et agrégés : aucun identifiant de compte, texte saisi, document, adresse IP ou paramètre d’URL n’est enregistré.
- Le tableau « Utilisation réelle » affiche les cinq étapes, les erreurs et le passage vers l’étape suivante. L’interface précise qu’un écart entre deux étapes n’est pas une mesure exacte d’utilisateurs uniques ayant abandonné.
- La politique de mesure indique désormais explicitement les dimensions du formulaire et l’absence de conservation des réponses saisies.
- Une page administrateur « À traiter » réunit les demandes anciennes sans proposition, vérifications d’identité en attente, signalements, litiges et webhooks Stripe en échec.
- Chaque dossier expose sa priorité, l’équipe responsable, l’échéance interne recommandée, la prochaine action et un lien vers l’écran source. La file ne modifie, ne relance et ne clôture rien automatiquement au nom d’un utilisateur.
- Les lignes des paiements et litiges possèdent une ancre stable pour ouvrir directement le dossier ciblé.
- La politique interne `POLITIQUE-EXPLOITATION-LANCEMENT-PROKEJEM.md` définit la séparation service/marketing, les limites de relance, le consentement pour les besoins récurrents et des seuils minimaux avant d’intensifier la monétisation.
- Après une commande terminée, le client peut programmer volontairement un rappel ponctuel, mensuel, trimestriel, semestriel ou annuel. Il choisit la prochaine date et peut demander un e-mail en complément de la notification interne.
- Le rappel peut être modifié, mis en pause, réactivé ou supprimé. L’interface affiche la prochaine échéance, le dernier envoi et le nombre d’envois. La commande planifiée recale directement dans le futur un rappel récurrent ancien afin d’éviter plusieurs envois rapprochés.
- Le traitement n’ajoute aucune annonce ou commande et ne sélectionne aucun prestataire. L’e-mail exige à la fois le choix explicite du rappel et l’autorisation générale des notifications e-mail du compte.
- La notification interne et son compteur sont enregistrés dans la même transaction : un échec de stockage laisse le rappel à traiter. Le résultat de l’e-mail est suivi séparément ; un échec ou une interruption n’est pas affiché comme un envoi confirmé et ne déclenche pas de renvoi automatique potentiellement en double.
- Réactiver un rappel dont la date est passée nécessite de choisir une date future. Une commande devenue inéligible ou un compte supprimé ne reçoit plus de rappel.

### Contrôles locaux

- 13 tests ciblés réussis, 90 assertions.
- Suite complète finale : **286 tests réussis, 1 930 assertions**. Les rappels disposent de **10 tests, 48 assertions**, incluant les erreurs de stockage/e-mail, la réactivation et les commandes inéligibles. Exécution PHPUnit directe avec environnement SQLite isolé : 2 min 30 s.
- Compilation Blade, format PHP, build Vite et `git diff --check` réussis. Le build conserve les avertissements Sass de dépréciation déjà connus, sans échec.
- Navigateur local : passage réel de l’étape 1 à l’étape 2 ; le compteur agrégé `demand.step.2.guest` a été créé une seule fois.
- Navigateur local administrateur : les cinq lignes du tunnel, les erreurs et le taux de passage sont visibles. La file « À traiter » affiche la demande locale sans réponse et ses actions attendues.
- Contrôle responsive de la file à une largeur utile de 433 px malgré une consigne de 390 px : aucune largeur de page excédentaire mesurée (`scrollWidth` 416 px pour `innerWidth` 433 px). Ce contrôle ne remplace pas un téléphone physique.
- Navigateur local, base SQLite isolée : ouverture du formulaire, choix mensuel et e-mail, clic réel sur « Programmer le rappel », puis état « Actif » et valeurs conservées. Largeur mobile utile mesurée de 433 px, `scrollWidth` de 425 px. Les derniers ajustements de messages d’erreur sont également contrôlés par rendu Laravel.

### Limites avant livraison

- Les compteurs commenceront à être utiles après une période réelle d’observation ; les données historiques ne peuvent pas reconstituer les étapes passées.
- Les échéances affichées sont des objectifs internes, pas des garanties contractuelles communiquées aux utilisateurs.
- Les relances des demandes sans réponse et les campagnes marketing restent désactivées. Seul le rappel de service expressément programmé par le client est automatisé ; il ne republie rien et ne déclenche aucune transaction.
- Le lot n’est pas encore commité ni envoyé vers GitHub. Railway est reporté à la demande de l’utilisateur. Au déploiement, exécuter la migration `2026_09_07_000001_create_service_reminders_table.php` et vérifier le fonctionnement effectif du planificateur (déjà prévu dans `nixpacks.toml`).
- Aucun e-mail réel n’a été envoyé pour ces tests. La confirmation de remise en boîte et le contrôle sur téléphone physique restent à effectuer après déploiement.

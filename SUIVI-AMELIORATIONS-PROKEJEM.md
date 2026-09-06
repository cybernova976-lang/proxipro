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
- Le lot 2 (annuaire métier/zone et refonte compacte des profils), le lot 3 (suivi des demandes) et le lot 4 (exploitation/mesure) ne sont pas annoncés comme réalisés.

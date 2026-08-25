# Prokejem — consignes de travail

Marketplace de services biface (Laravel), en ligne sur https://www.prokejem.fr
Hébergement Railway · domaine et e-mails OVHcloud · Cloudflare devant.

Ce fichier fixe les règles de ce dépôt. Suis-les sans qu'on ait à te les rappeler.

---

## 1. Ne jamais déployer sans le contrôle préalable

Avant tout déploiement, exécute :

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\preflight.ps1
```

Ce script enchaîne : syntaxe PHP → **compilation Blade réelle (`view:cache`)** →
résolution des routes → `npm run build` → tests ciblés → suite complète.

**Tant qu'il n'est pas VERT, la version n'est pas déployable.** Ne propose pas
de déployer, ne dis pas « c'est prêt ».

L'étape 2 est la plus importante : une vérification statique (équilibre des
directives, `php -l`) **ne remplace pas** la compilation Blade. Une erreur de
parsing Blade a déjà provoqué une erreur 500 en production sur `/feed`.

### Pièges Blade rencontrés sur ce projet

- `@json([...])` **sur plusieurs lignes** casse la compilation. Construis le
  tableau dans un bloc `@php`, puis affiche-le avec
  `{!! Illuminate\Support\Js::encode($tableau) !!}`.
- **Aucune directive Blade dans un commentaire `{{-- --}}`.** Blade compile les
  directives *avant* de retirer les commentaires : écrire `@php` ou `@json()`
  dans un commentaire casse la vue.

---

## 2. Déploiement

Le dépôt n'est pas déployé via GitHub. La mise en ligne se fait à la main,
depuis le dossier local, avec la CLI Railway :

```powershell
railway up
```

Railway envoie le **dossier de travail**, pas un commit. Donc :

1. le contrôle préalable doit être vert ;
2. **commite avant de déployer**, sinon la version mise en ligne n'est ni
   identifiable ni annulable proprement ;
3. ne déploie jamais sans confirmation explicite de l'utilisateur.

Au démarrage du conteneur, Railway exécute tout seul `composer install`,
`npm run build`, `php artisan migrate --force`, `config:cache`, `route:cache`,
`view:cache`, puis `queue:work` et `schedule:work`. Aucune commande artisan
n'est à lancer à la main en production.

### Après déploiement, vérifie et rapporte

- statut Railway et identifiant du commit déployé ;
- `https://www.prokejem.fr/up` doit répondre HTTP 200 ;
- `/feed` **avec une session connectée** — c'est le contrôle qui compte, une
  redirection ou un asset en 200 ne prouve rien sur le rendu de la page ;
- erreurs serveur dans les journaux Railway, et console du navigateur ;
- rappelle le plan de retour arrière.

### Retour arrière

```powershell
git checkout <commit-precedent> -- <fichiers concernés>
railway up
```

---

## 3. Comment rapporter une vérification

Distingue toujours ces cinq catégories, et écris **« non exécuté »** noir sur
blanc là où c'est le cas. Ne présente jamais un contrôle statique comme une
validation fonctionnelle.

1. contrôles statiques réellement exécutés ;
2. compilation Laravel ;
3. tests fonctionnels ;
4. rendu avec les vraies données ;
5. vérifications après déploiement.

---

## 4. Conventions de la page d'accueil (`/feed`)

Refonte d'août 2026. Le feed est **un poste de pilotage, pas un catalogue** :
il répond à « où en suis-je et que dois-je faire maintenant ? ».

- `resources/views/feed/index.blade.php` est un orchestrateur court (~130 lignes)
  qui inclut un partial par zone. **Aucun fichier Blade au-delà de 200 lignes.**
- Le CSS et le JS vivent dans `public/css/feed.css` et `public/js/feed.js`.
  **Ne réintroduis jamais de CSS ou de JS dans la vue** : la page était à
  12 482 lignes avant, dont 7 216 de CSS en ligne.
- Tout est préfixé `pk-` et porté par `.pk-feed`, pour qu'aucun style ne
  se mélange avec `layouts/app.blade.php` (10 198 lignes).
- Une seule rampe de couleur : l'indigo de `welcome.blade.php`
  (`#eef2ff` → `#312e81`), plus le violet `#7c3aed`. Le bleu `#3a86ff` de
  l'en-tête et l'orange `#E76F51` sont des accidents à ne pas propager.
- Trois points de rupture : **640 / 1024 / 1180**. Pas d'autres.
- Six zones, dans cet ordre : intention · carte d'état · progression ·
  flux (6 annonces max) · prestataires · réassurance.
- **Un seul bouton d'action dominant par écran.** La publication passe
  uniquement par `/demande` (clients) et `/ads/create` (prestataires) :
  pas de modale de publication dans le feed.
- La recherche, les filtres et la carte géographique vivent sur `/annonces`.

---

## 5. Règle de fond : aucun chiffre inventé

Tout nombre affiché à un utilisateur doit être mesuré. Si la donnée n'existe
pas, **n'affiche pas l'élément** — ne l'estime pas, ne le simule pas.

Exemple en place : les vues de profil sont comptées dans la table
`profile_views`, dédoublonnées par visiteur et par jour, robots et
propriétaire du profil exclus (`App\Services\ProfileViewCounter`).
La colonne `users.profile_views` était auparavant incrémentée à chaque
rechargement de page : ses valeurs historiques sont gonflées.

---

## 6. Langue

Interface, messages d'erreur, commentaires de code et réponses à
l'utilisateur : **en français**.

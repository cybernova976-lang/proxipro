# ProxiPro

Plateforme communautaire de services de proximité — trouvez et proposez des services locaux facilement.

**Stack technique :** Laravel 12 · PHP 8.2+ · Vite · Tailwind CSS · Bootstrap 5 · SQLite/MySQL

## Prérequis

- PHP 8.2 ou supérieur
- Composer
- Node.js 20+ et npm
- (Optionnel) Docker & Docker Compose

## Installation locale

```bash
# Cloner le dépôt
git clone https://github.com/cybernova976-lang/proxipro.git
cd proxipro

# Installation rapide (dépendances, .env, clé, migrations, assets)
composer setup

# Lancer le serveur de développement
composer dev
```

L'application sera disponible sur `http://localhost:8000`.

## Déploiement

### Option 1 — Docker (recommandé)

Construire et lancer l'image Docker localement :

```bash
# Générer une clé d'application (nécessite PHP et Laravel installés localement)
APP_KEY=$(php artisan key:generate --show)

# Lancer avec Docker Compose
APP_KEY=$APP_KEY docker compose up --build -d
```

L'application sera disponible sur `http://localhost:8080`.

### Option 2 — Render (déploiement gratuit)

1. Créer un compte sur [render.com](https://render.com)
2. Cliquer sur **New > Web Service**
3. Connecter votre dépôt GitHub `cybernova976-lang/proxipro`
4. Render détectera automatiquement le fichier `render.yaml`
5. Cliquer sur **Apply** pour lancer le déploiement

### Option 3 — Railway

1. Créer un compte sur [railway.app](https://railway.app)
2. Cliquer sur **New Project > Deploy from GitHub repo**
3. Sélectionner le dépôt `proxipro`
4. Railway détectera le `Dockerfile` automatiquement
5. Ajouter les variables d'environnement :
   - `APP_KEY` — Générer avec `php artisan key:generate --show`
   - `APP_ENV` — `production`
   - `APP_DEBUG` — `false`

### Option 4 — Heroku

```bash
# Installer le CLI Heroku, puis :
heroku create proxipro-test
heroku buildpacks:set heroku/php
heroku buildpacks:add heroku/nodejs

git push heroku main

heroku run php artisan key:generate --show
heroku config:set APP_KEY=<clé_générée>
heroku run php artisan migrate --force
```

## Variables d'environnement

| Variable | Description | Défaut |
|----------|-------------|--------|
| `APP_KEY` | Clé de chiffrement (obligatoire) | — |
| `APP_ENV` | Environnement (`local`, `production`) | `local` |
| `APP_DEBUG` | Mode debug | `true` |
| `APP_URL` | URL publique de l'application | `http://localhost` |
| `DB_CONNECTION` | Driver de base de données | `sqlite` |
| `APP_LOCALE` | Langue de l'application | `fr` |

Voir `.env.example` pour la liste complète des variables.

## Tests

```bash
composer test
```

## Licence

Ce projet utilise le framework Laravel sous [licence MIT](https://opensource.org/licenses/MIT).

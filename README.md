# ProxiPro

Plateforme de services de proximité et marketplace locale, construite avec Laravel 12, Vite, Tailwind CSS et Bootstrap 5.

## Fonctionnalités

- **Marketplace / Petites annonces** : Publiez et parcourez des annonces de services et d'articles
- **Annuaire de prestataires** : Les professionnels peuvent s'inscrire et proposer leurs services
- **Fil d'actualité géolocalisé** : Découvrez les services à proximité
- **Espace Pro** : Tableau de bord pour la gestion d'entreprise (devis, factures, clients)
- **Messagerie** : Communication directe entre utilisateurs
- **Système de points** : Gamification avec économie de crédits
- **Paiement Stripe** : Abonnements et achats de points
- **Outils** : Convertisseur PDF, compresseur d'images, générateur QR
- **Objets perdus / trouvés** : Section dédiée
- **Avis et notes** : Système d'évaluation des prestataires
- **Vérification d'identité** : Système KYC pour les utilisateurs

## Prérequis

- PHP 8.2+
- Composer
- Node.js 20+
- SQLite (par défaut) ou MySQL/PostgreSQL

## Installation locale

```bash
# Cloner le dépôt
git clone https://github.com/cybernova976-lang/proxipro.git
cd proxipro

# Installer les dépendances
composer install
npm install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate

# Créer la base de données SQLite et exécuter les migrations
touch database/database.sqlite
php artisan migrate

# Créer le lien de stockage
php artisan storage:link

# Compiler les assets frontend
npm run build

# Lancer le serveur de développement
composer run dev
```

L'application sera accessible sur `http://localhost:8000`.

## Déploiement

### Option 1 : Docker (recommandé pour les tests)

La façon la plus simple de tester le projet en ligne. Il suffit d'avoir [Docker](https://www.docker.com/) installé.

```bash
# Construire et lancer
docker compose up --build

# L'application est disponible sur http://localhost:8080
```

Pour personnaliser le port :

```bash
APP_PORT=3000 docker compose up --build
```

### Option 2 : Render (déploiement gratuit en ligne)

1. Créez un compte sur [Render](https://render.com)
2. Cliquez sur **New > Web Service**
3. Connectez votre dépôt GitHub `cybernova976-lang/proxipro`
4. Render détectera automatiquement le fichier `render.yaml`
5. Cliquez sur **Deploy**

Le projet sera en ligne en quelques minutes avec un URL public.

### Option 3 : Railway

1. Créez un compte sur [Railway](https://railway.app)
2. Cliquez sur **New Project > Deploy from GitHub repo**
3. Sélectionnez le dépôt `proxipro`
4. Railway détectera le `Dockerfile` et déploiera automatiquement

### Option 4 : Hébergement classique (Apache/Nginx)

1. Uploadez les fichiers sur votre serveur
2. Pointez le document root vers le dossier `public/`
3. Configurez le fichier `.env` avec vos paramètres de production
4. Exécutez :

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Variables d'environnement

| Variable | Description | Défaut |
|---|---|---|
| `APP_ENV` | Environnement (`local`, `production`) | `local` |
| `APP_DEBUG` | Mode debug | `true` |
| `APP_URL` | URL publique de l'application | `http://localhost` |
| `DB_CONNECTION` | Driver de base de données | `sqlite` |
| `STRIPE_KEY` | Clé publique Stripe | - |
| `STRIPE_SECRET` | Clé secrète Stripe | - |
| `RECAPTCHA_SITE_KEY` | Clé reCAPTCHA v3 | - |
| `RECAPTCHA_SECRET_KEY` | Secret reCAPTCHA v3 | - |

## Tests

```bash
php artisan test
```

## Licence

Ce projet est sous licence [MIT](https://opensource.org/licenses/MIT).

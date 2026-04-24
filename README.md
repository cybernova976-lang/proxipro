# ProxiPro — Site Web Professionnel

**ProxiPro** est une plateforme française de mise en relation entre particuliers et prestataires de services (MASSIWANI-EMERGENT project).

## 🌐 Accéder au site en ligne

Le site est déployé sur **Railway**. Pour trouver l'URL en ligne :

1. Connectez-vous à votre tableau de bord Railway : [railway.app](https://railway.app)
2. Ouvrez le projet **MASSIWANI-EMERGENT**
3. Dans l'onglet **Settings** de votre service web, copiez le domaine généré (format : `https://proxipro-production-<hash>.up.railway.app`)

> Si vous avez configuré un domaine personnalisé, il sera affiché dans l'onglet **Settings → Domains** de votre service Railway.

---

## 📋 Analyse du projet

### Description
ProxiPro est une marketplace de services entre particuliers et professionnels, entièrement en français. Elle permet aux utilisateurs de trouver des prestataires compétents près de chez eux pour tous types de services du quotidien.

### Catégories de services
| Catégorie | Description |
|-----------|-------------|
| 🔨 Bricolage | Réparations, installations, travaux |
| 🌿 Jardinage | Entretien de jardins, taille, tonte |
| ✨ Ménage | Nettoyage, repassage, entretien |
| 🤝 Aide à domicile | Assistance, garde, accompagnement |
| 📚 Cours | Soutien scolaire, langues, musique |
| 💻 Informatique | Dépannage, formation, développement |

### Fonctionnalités principales

#### Pour les particuliers
- 🔍 Recherche d'annonces par mot-clé et localisation
- 📢 Publication d'annonces gratuites
- 💌 Messagerie intégrée avec les prestataires
- ⭐ Système d'avis et de notation
- 🔖 Sauvegarde d'annonces favorites
- 🔎 Signalement d'objets perdus

#### Pour les professionnels (abonnement Pro)
- 📊 Tableau de bord Pro dédié
- 📄 Gestion de devis et factures PDF
- 🚀 Mise en avant des annonces (Boost / Urgent)
- ✅ Vérification d'identité (badge de confiance)
- 👤 Profil prestataire complet
- 📈 Statistiques de performance

#### Monétisation
- 💳 Paiements en ligne via **Stripe**
- 🎯 Système de points (ProxiPoints)
- 📦 Plans d'abonnement Pro
- 🚀 Promotions payantes (Boost / Urgent)

#### Administration
- 🛡️ Panel administrateur complet
- 👥 Gestion des utilisateurs
- 🚨 Modération des signalements
- ⚙️ Paramètres globaux de l'application

---

## 🏗️ Stack technique

| Composant | Technologie |
|-----------|-------------|
| Backend | PHP 8.2 + Laravel 12 |
| Frontend | Blade + Tailwind CSS + Vite |
| Base de données | PostgreSQL (prod) / SQLite (dev) |
| Paiements | Stripe (Laravel Cashier) |
| Authentification | Email + Google OAuth + Facebook OAuth |
| Emails | Brevo (SMTP) |
| Stockage fichiers | Cloudflare R2 / AWS S3 |
| Déploiement | Railway (Railpack) |
| Langue | Français 🇫🇷 |

---

## 🚀 Déploiement Railway

Le projet se déploie automatiquement sur Railway à chaque push sur la branche `main`.

### Configuration requise sur Railway

```
APP_NAME=ProxiPro
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<votre-domaine>.up.railway.app
SESSION_DRIVER=cookie
CACHE_STORE=file

# Base de données PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_DATABASE=${{Postgres.PGDATABASE}}
DB_USERNAME=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}
```

Pour le guide complet de déploiement, consultez [RAILWAY_SETUP.md](RAILWAY_SETUP.md).

---

## 💻 Développement local

```bash
# Cloner le projet
git clone https://github.com/cybernova976-lang/proxipro.git
cd proxipro

# Installer les dépendances
composer install
npm install

# Configurer l'environnement
cp .env.example .env
# Éditer .env : DB_CONNECTION=sqlite
php artisan key:generate

# Migrations et seeds
php artisan migrate
php artisan db:seed --class=AdminSeeder

# Lancer le serveur
npm run dev &
php artisan serve
```

L'application sera accessible sur **http://localhost:8000**.

---

## License

[MIT](https://opensource.org/licenses/MIT)

# ProxiPro

**ProxiPro** is a local services marketplace and professional networking platform built with Laravel 12. It connects users with local service providers, supports peer-to-peer services, and includes a suite of professional tools for invoicing and quote management.

## Features

- **Marketplace** – Browse and post service advertisements with geo-location based filtering and radius search.
- **Professional Profiles** – Service providers can register, get verified, and manage their public profile.
- **Messaging** – Direct conversations between users and service providers.
- **Points Economy** – Gamified engagement with daily rewards, task completion bonuses, and a badge system.
- **Quote & Invoice Generator** – Free tool for professionals to create, download (PDF), and send business documents.
- **Lost & Found** – Post and search for lost or found items with a category and reward system.
- **Payments & Subscriptions** – Stripe integration for ad boosts, identity verification, and pro subscriptions.
- **Social Login** – OAuth via Google and Facebook (Laravel Socialite).
- **Admin Dashboard** – Full platform management: user moderation, ad review, verification workflows, reports, and settings.
- **Notifications** – Database-backed notifications with read/unread tracking.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | Laravel 12, PHP ^8.2 |
| **Database** | SQLite (default), MySQL compatible |
| **Authentication** | Laravel Sanctum + Laravel Socialite (Google, Facebook) |
| **Payments** | Stripe via Laravel Cashier v16 |
| **PDF Generation** | barryvdh/laravel-dompdf |
| **Frontend Build** | Vite 7 |
| **CSS** | Tailwind CSS 4, Bootstrap 5 |
| **Testing** | PHPUnit 11 |

## Requirements

- PHP >= 8.2
- Composer
- Node.js >= 20 & npm
- SQLite (default) or MySQL

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/cybernova976-lang/proxipro.git
cd proxipro

# 2. Install PHP dependencies
composer install

# 3. Install JavaScript dependencies
npm install

# 4. Configure the environment
cp .env.example .env
php artisan key:generate

# 5. Create the database and run migrations
touch database/database.sqlite
php artisan migrate

# 6. (Optional) Seed the database
php artisan db:seed

# 7. Build frontend assets
npm run build

# 8. Start the development server
php artisan serve
```

The application will be available at `http://localhost:8000`.

## Environment Variables

Key variables to configure in `.env`:

| Variable | Description |
|----------|-------------|
| `APP_NAME` | Application name (default: `ProxiPro`) |
| `APP_URL` | Base URL of the application |
| `DB_CONNECTION` | Database driver (`sqlite` or `mysql`) |
| `MAIL_MAILER` | Mail driver (e.g. `smtp`, `log`) |
| `STRIPE_KEY` | Stripe publishable key |
| `STRIPE_SECRET` | Stripe secret key |
| `GOOGLE_CLIENT_ID` | Google OAuth client ID |
| `GOOGLE_CLIENT_SECRET` | Google OAuth client secret |
| `FACEBOOK_CLIENT_ID` | Facebook OAuth app ID |
| `FACEBOOK_CLIENT_SECRET` | Facebook OAuth app secret |
| `RECAPTCHA_SITE_KEY` | Google reCAPTCHA v3 site key |
| `RECAPTCHA_SECRET_KEY` | Google reCAPTCHA v3 secret key |

## Running Tests

```bash
php artisan test
```

## Project Structure

```
app/
├── Http/Controllers/   # Route controllers
├── Models/             # Eloquent models
├── Services/           # Business logic services
├── Rules/              # Custom validation rules
├── Mail/               # Mailable classes
└── Notifications/      # Notification classes
database/
├── migrations/         # Database schema migrations
└── seeders/            # Database seeders
resources/
├── views/              # Blade templates
├── css/                # Stylesheets
└── js/                 # JavaScript entry points
routes/
└── web.php             # Application routes
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

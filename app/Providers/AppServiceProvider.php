<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->afterResolving('mail.manager', function ($manager) {
            $manager->extend('brevo', function (array $config = []) {
                return new BrevoApiTransport($config['key'] ?? '');
            });
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production (Railway reverse proxy)
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verification de votre adresse e-mail')
                ->greeting('Bonjour')
                ->line('Merci pour votre inscription sur ProxiPro.')
                ->line('Cliquez sur le bouton ci-dessous pour verifier votre adresse e-mail.')
                ->action('Verifier mon adresse e-mail', $url)
                ->line('Si vous n\'avez pas cree de compte, ignorez cet email.');
        });

        // Configurer Carbon en français pour les dates
        Carbon::setLocale('fr');
        setlocale(LC_TIME, 'fr_FR.UTF-8', 'fra_fra', 'fra');

        // storage_url() helper — autoloaded via composer.json "files" (app/helpers.php).
        // Generates public URLs for files stored on Cloudflare R2 (S3) or local disk.
        // Uses FILESYSTEM_PUBLIC_DRIVER env var to select the correct URL strategy:
        //   - 's3'   → AWS_URL + file path  (Cloudflare R2 public bucket URL)
        //   - other  → asset('storage/' . path)  (local symlinked storage)

        // Enregistrer les fonctions mathématiques manquantes pour SQLite (Haversine).
        // Check the configured driver first (no live connection needed) to avoid
        // attempting a DB connection during build-time commands like config:cache.
        if (config('database.default') === 'sqlite') {
            try {
                $pdo = DB::connection()->getPdo();
                $pdo->sqliteCreateFunction('acos', 'acos', 1);
                $pdo->sqliteCreateFunction('cos', 'cos', 1);
                $pdo->sqliteCreateFunction('sin', 'sin', 1);
                $pdo->sqliteCreateFunction('radians', 'deg2rad', 1);
            } catch (\Exception $e) {
                // Silently ignore if the database is not available (e.g. during build).
            }
        }
    }
}

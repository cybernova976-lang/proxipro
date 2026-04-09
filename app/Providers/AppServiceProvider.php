<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        $publicUrl = $this->resolvePublicUrl();

        // Force HTTPS in production (Railway reverse proxy)
        if (app()->environment('production') && $publicUrl !== null) {
            URL::forceRootUrl($publicUrl);
            URL::forceScheme('https');
        }

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = $this->makeAbsoluteRoute('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return (new MailMessage)
                ->subject('Réinitialisation de votre mot de passe')
                ->greeting('Bonjour ' . ($notifiable->name ?? ''))
                ->line('Vous recevez cet e-mail car une demande de réinitialisation du mot de passe a été effectuée pour votre compte ProxiPro.')
                ->action('Réinitialiser mon mot de passe', $url)
                ->line('Ce lien de réinitialisation expirera dans 60 minutes.')
                ->line('Si vous n\'êtes pas à l\'origine de cette demande, aucune action supplémentaire n\'est requise.');
        });

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

    protected function makeAbsoluteRoute(string $name, array $parameters = []): string
    {
        $path = route($name, $parameters, false);
        $publicUrl = $this->resolvePublicUrl();

        if ($publicUrl !== null) {
            return $publicUrl . $path;
        }

        return url($path);
    }

    protected function resolvePublicUrl(): ?string
    {
        $candidates = [];

        if (! $this->app->runningInConsole() && $this->app->bound('request')) {
            $candidates[] = request()->getSchemeAndHttpHost();
        }

        $candidates[] = config('app.url');

        $railwayPublicDomain = env('RAILWAY_PUBLIC_DOMAIN');
        if (is_string($railwayPublicDomain) && trim($railwayPublicDomain) !== '') {
            $candidates[] = 'https://' . ltrim(trim($railwayPublicDomain), '/');
        }

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizePublicUrl($candidate);

            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    protected function normalizePublicUrl(mixed $candidate): ?string
    {
        if (! is_string($candidate)) {
            return null;
        }

        $candidate = trim($candidate);
        if ($candidate === '') {
            return null;
        }

        if (! Str::startsWith($candidate, ['http://', 'https://'])) {
            $candidate = 'https://' . ltrim($candidate, '/');
        }

        if (filter_var($candidate, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $host = (string) parse_url($candidate, PHP_URL_HOST);
        if ($host === '') {
            return null;
        }

        $isIpAddress = filter_var($host, FILTER_VALIDATE_IP) !== false;
        $isLocalhost = $host === 'localhost';
        $looksLikeHash = preg_match('/^[a-f0-9]{32,}$/i', $host) === 1;

        if ($looksLikeHash || (! $isIpAddress && ! $isLocalhost && ! str_contains($host, '.'))) {
            return null;
        }

        $scheme = (string) (parse_url($candidate, PHP_URL_SCHEME) ?: 'https');
        $port = parse_url($candidate, PHP_URL_PORT);
        $path = trim((string) parse_url($candidate, PHP_URL_PATH));

        $normalized = $scheme . '://' . $host;
        if ($port !== false && $port !== null) {
            $normalized .= ':' . $port;
        }

        if ($path !== '' && $path !== '/') {
            $normalized .= '/' . trim($path, '/');
        }

        return rtrim($normalized, '/');
    }
}

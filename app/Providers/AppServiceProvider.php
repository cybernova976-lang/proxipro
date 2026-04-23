<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use App\Models\Setting;
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
        $this->applyDynamicMailSettings();

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

            $supportEmail = config('mail.reply_to.address')
                ?: config('mail.admin_email')
                ?: config('mail.from.address');

            return (new MailMessage)
                ->subject('Réinitialisation de votre mot de passe ProxiPro')
                ->view('emails.auth.reset-password', [
                    'resetUrl' => $url,
                    'userName' => $notifiable->name ?? null,
                    'supportEmail' => $supportEmail,
                    'appName' => config('app.name', 'ProxiPro'),
                ]);
        });

        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            $supportEmail = config('mail.reply_to.address')
                ?: config('mail.admin_email')
                ?: config('mail.from.address');

            return (new MailMessage)
                ->subject('Vérification de votre adresse e-mail ProxiPro')
                ->view('emails.auth.verify-email', [
                    'verificationUrl' => $url,
                    'userName' => $notifiable->name ?? null,
                    'supportEmail' => $supportEmail,
                    'appName' => config('app.name', 'ProxiPro'),
                ]);
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

    protected function applyDynamicMailSettings(): void
    {
        $mailDriver = Setting::get('mail_driver', config('mail.default'));
        $fromAddress = Setting::get('mail_from_address', config('mail.from.address'));
        $fromName = Setting::get('mail_from_name', config('mail.from.name'));
        $adminEmail = Setting::get(
            'mail_admin_address',
            Setting::get('contact_email', config('mail.admin_email'))
        );
        $replyToAddress = Setting::get('mail_reply_to_address', config('mail.reply_to.address')) ?: $adminEmail;
        $replyToName = Setting::get('mail_reply_to_name', config('mail.reply_to.name')) ?: $fromName;

        config([
            'mail.default' => $mailDriver,
            'mail.from.address' => $fromAddress,
            'mail.from.name' => $fromName,
            'mail.reply_to.address' => $replyToAddress,
            'mail.reply_to.name' => $replyToName,
            'mail.admin_email' => $adminEmail,
        ]);
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

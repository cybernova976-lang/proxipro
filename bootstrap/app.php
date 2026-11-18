<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // Stripe signe lui-meme ses webhooks. Ils ne peuvent pas fournir de
        // jeton CSRF de session, mais restent verifies par leur signature.
        // Idem pour la désinscription en un clic (RFC 8058) : la messagerie
        // envoie un POST sans session ; le lien signé fait office de preuve.
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'communications/*',
        ]);

        // Détection appareil (mobile/tablet/desktop) sur toutes les requêtes web
        $middleware->web(append: [
            \App\Http\Middleware\RedirectToCanonicalHost::class,
            \App\Http\Middleware\DetectDevice::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\EnforceSessionInactivity::class,
            // Après la session : fait accepter la dernière version des conditions.
            \App\Http\Middleware\EnsureTermsAccepted::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'geo' => \App\Http\Middleware\DetectUserGeolocation::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e) {
            if ($response->getStatusCode() === 419 && request()->isMethod('post')) {
                $email = (string) request()->input('email', '');

                if (request()->routeIs('login.attempt') || request()->is('login')) {
                    return redirect()->route('login')
                        ->withInput(request()->only('email', 'remember'))
                        ->with('error', 'Votre session a été renouvelée. Veuillez valider à nouveau la connexion.');
                }

                if (request()->routeIs('verification.code.verify') && $email !== '') {
                    return redirect()->route('verification.code.show', ['email' => $email])
                        ->with('error', 'Votre session a expiré pendant la vérification. Saisissez à nouveau le code pour continuer.');
                }

                if (request()->routeIs('verification.code.resend') && $email !== '') {
                    return redirect()->route('verification.code.show', ['email' => $email])
                        ->with('error', 'Votre session a expiré. La page a été rechargée, vous pouvez renvoyer un nouveau code.');
                }
            }

            // Limite de fréquence atteinte : Laravel répond « Too Many Attempts. » en anglais.
            // Les appels en arrière-plan (JSON) reçoivent le message en français ;
            // les pages classiques affichent errors/429.blade.php.
            if ($response->getStatusCode() === 429 && request()->expectsJson()) {
                return response()->json(
                    ['message' => 'Trop de tentatives. Patientez une minute avant de réessayer.'],
                    429,
                    array_filter([
                        'Retry-After' => $response->headers->get('Retry-After'),
                        'X-RateLimit-Limit' => $response->headers->get('X-RateLimit-Limit'),
                        'X-RateLimit-Remaining' => $response->headers->get('X-RateLimit-Remaining'),
                    ], fn ($value) => $value !== null)
                );
            }

            if ($response->getStatusCode() >= 500) {
                \Illuminate\Support\Facades\Log::error('Server error: '.$e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => request()->fullUrl(),
                ]);
            }

            return $response;
        });
    })->create();

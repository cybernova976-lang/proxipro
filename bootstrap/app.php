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
        
        // Détection appareil (mobile/tablet/desktop) sur toutes les requêtes web
        $middleware->web(append: [
            \App\Http\Middleware\DetectDevice::class,
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

                if (request()->routeIs('verification.code.verify') && $email !== '') {
                    return redirect()->route('verification.code.show', ['email' => $email])
                        ->with('error', 'Votre session a expiré pendant la vérification. Saisissez à nouveau le code pour continuer.');
                }

                if (request()->routeIs('verification.code.resend') && $email !== '') {
                    return redirect()->route('verification.code.show', ['email' => $email])
                        ->with('error', 'Votre session a expiré. La page a été rechargée, vous pouvez renvoyer un nouveau code.');
                }
            }

            if ($response->getStatusCode() >= 500) {
                \Illuminate\Support\Facades\Log::error('Server error: ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => request()->fullUrl(),
                ]);
            }
            return $response;
        });
    })->create();

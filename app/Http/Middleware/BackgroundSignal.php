<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marque une requête comme signal d'arrière-plan (présence, activité de
 * session, mesure d'usage) : envoyée par la page sans action du membre, elle
 * ne doit pas réécrire la session.
 *
 * Voir App\Session\SignalAwareDatabaseSessionHandler.
 */
class BackgroundSignal
{
    public const ATTRIBUTE = 'pk.background_signal';

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::ATTRIBUTE, true);

        return $next($request);
    }
}

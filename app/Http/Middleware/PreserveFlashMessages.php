<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Les envois d'arrière-plan ne consomment pas les messages de retour.
 *
 * Un message de retour (« Alerte enregistrée… ») vit le temps d'une requête.
 * Chaque page envoie en arrière-plan des signaux de présence, d'activité de
 * session et de mesure d'usage : s'ils arrivaient entre l'action et le
 * rechargement de la page, le message disparaissait avant d'être affiché.
 *
 * Seule une vraie page (réponse HTML) ou une redirection le consomme
 * désormais ; une réponse JSON ou vide le garde pour la page suivante.
 */
class PreserveFlashMessages
{
    public function handle(Request $request, Closure $next): Response
    {
        // Ce qui attendait d'être affiché avant cette requête.
        $pending = $request->hasSession() ? (array) $request->session()->get('_flash.old', []) : [];

        $response = $next($request);

        if ($pending !== [] && $request->hasSession() && ! $this->showsAPage($response)) {
            $request->session()->keep($pending);
        }

        return $response;
    }

    private function showsAPage(Response $response): bool
    {
        if ($response instanceof RedirectResponse || $response->isRedirection()) {
            return true;
        }

        $type = (string) $response->headers->get('Content-Type', '');

        return $type === '' ? $response->getContent() !== '' && $response->getContent() !== false : str_contains($type, 'text/html');
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\TermsVersion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fait accepter la dernière version des conditions à tout membre connecté,
 * administrateurs compris. Avant la date d'entrée en vigueur, l'acceptation est
 * proposée une fois par session ; à partir de cette date, elle est obligatoire
 * pour continuer. Sans version publiée, ou si l'administrateur a suspendu la
 * demande, rien n'est demandé.
 */
class EnsureTermsAccepted
{
    /** Pages toujours accessibles : accepter, lire les textes, se déconnecter, écrire au support. */
    private const OPEN_ROUTES = ['terms.accept.*', 'logout', 'legal.*', 'contact.*'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $request->routeIs(...self::OPEN_ROUTES)) {
            return $next($request);
        }

        $version = TermsVersion::current();
        if (! $version || (int) $user->accepted_terms_version_id === (int) $version->id || TermsVersion::isPaused()) {
            return $next($request);
        }

        $background = $request->expectsJson() || $request->ajax();

        if (! $version->isMandatory()) {
            // Simple proposition : une seule fois par session, jamais sur un appel en arrière-plan
            // ni sur l'envoi d'un formulaire (on ne fait pas perdre une saisie).
            $seenKey = 'terms_prompt_seen_'.$version->id;
            if ($background || ! $request->isMethod('get') || $request->session()->get($seenKey)) {
                return $next($request);
            }
            $request->session()->put($seenKey, true);
        } elseif ($background) {
            return response()->json([
                'message' => 'Les conditions d’utilisation ont changé. Rechargez la page pour les accepter.',
                'reason' => 'terms_update',
                'accept_url' => route('terms.accept.show'),
            ], 409)->header('Cache-Control', 'no-store');
        }

        if ($request->isMethod('get')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->route('terms.accept.show');
    }
}

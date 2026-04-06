<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\IpGeolocationService;

class DetectUserGeolocation
{
    protected IpGeolocationService $geoService;

    public function __construct(IpGeolocationService $geoService)
    {
        $this->geoService = $geoService;
    }

    /**
     * Détecte automatiquement la position de l'utilisateur connecté
     * et la stocke en session + en base de données.
     * 
     * Fonctionne selon cette priorité :
     * 1. Coordonnées déjà enregistrées dans le profil
     * 2. Ville/Pays du profil → géocodage Nominatim
     * 3. Adresse IP → ip-api.com / ipinfo.io
     * 4. Défaut → Mamoudzou, Mayotte
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            if (Auth::check()) {
                $user = Auth::user();

                // Détecter la localisation (le service gère le cache session)
                $geoData = $this->geoService->detectUserLocation($user, $request);

                // Stocker dans la requête pour que les contrôleurs y accèdent facilement
                if ($geoData) {
                    $request->attributes->set('user_geo', $geoData);
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Geolocation detection failed: ' . $e->getMessage());
        }

        return $next($request);
    }
}

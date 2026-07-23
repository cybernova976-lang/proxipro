<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;

class IpGeolocationService
{
    /**
     * Détecte la localisation de l'utilisateur par plusieurs méthodes :
     * 1. Données du profil utilisateur (ville/pays → géocodage)
     * 2. Adresse IP → API géolocalisation gratuite
     * 3. Session (cache navigateur)
     * 
     * Retourne un tableau avec latitude, longitude, city, country, source
     */
    public function detectUserLocation($user = null, ?Request $request = null): ?array
    {
        // 1. Vérifier si on a déjà la position en session (pour éviter des appels répétés)
        $sessionGeo = Session::get('user_geolocation');
        if ($sessionGeo && isset($sessionGeo['latitude']) && $this->isRecentEnough($sessionGeo)) {
            return $sessionGeo;
        }

        // 2. Essayer depuis le profil utilisateur (ville + pays)
        if ($user) {
            $fromProfile = $this->detectFromUserProfile($user);
            if ($fromProfile) {
                $this->storeInSession($fromProfile);
                return $fromProfile;
            }
        }

        // 3. Essayer depuis l'adresse IP
        if ($request) {
            $fromIp = $this->detectFromIp($request->ip());
            if ($fromIp) {
                $this->storeInSession($fromIp);
                // Mettre à jour le profil utilisateur si les coordonnées sont vides
                if ($user && !$user->latitude) {
                    $this->updateUserGeoData($user, $fromIp);
                }
                return $fromIp;
            }
        }

        return null;
    }

    /**
     * Détecte la localisation depuis les infos du profil (ville, pays, adresse)
     */
    private function detectFromUserProfile($user): ?array
    {
        // Si l'utilisateur a déjà des coordonnées
        if ($user->latitude && $user->longitude) {
            return [
                'latitude' => (float) $user->latitude,
                'longitude' => (float) $user->longitude,
                'city' => $user->detected_city ?? $user->city,
                'country' => $user->detected_country ?? $user->country,
                'source' => 'profile_coordinates',
                'detected_at' => now()->toISOString(),
            ];
        }

        // Construire une adresse à géocoder depuis les infos du profil
        $addressParts = array_filter([
            $user->address,
            $user->city,
            $user->country,
        ]);

        if (empty($addressParts)) {
            // Essayer avec location_preference
            if ($user->location_preference) {
                $addressParts = [$user->location_preference];
            } else {
                return null;
            }
        }

        $address = implode(', ', $addressParts);
        $geocodingService = app(GeocodingService::class);
        $result = $geocodingService->geocode($address);

        if ($result && isset($result['latitude'])) {
            return [
                'latitude' => $result['latitude'],
                'longitude' => $result['longitude'],
                'city' => $result['city'] ?? $user->city,
                'country' => $result['country'] ?? $user->country ?? 'France',
                'source' => 'profile_geocoded',
                'detected_at' => now()->toISOString(),
            ];
        }

        return null;
    }

    /**
     * Détecte la localisation depuis l'adresse IP
     * Utilise ip-api.com (gratuit, 45 req/min)
     */
    private function detectFromIp(string $ip): ?array
    {
        // En local/dev, l'IP sera 127.0.0.1 → pas géolocalisable
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return $this->getDefaultLocation();
        }

        $cacheKey = 'ip_geo_' . md5($ip);

        return Cache::remember($cacheKey, 3600 * 24, function () use ($ip) {
            try {
                // ip-api.com - Gratuit, pas de clé API nécessaire
                $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'status,country,city,lat,lon,regionName,query',
                    'lang' => 'fr'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if ($data['status'] === 'success') {
                        return [
                            'latitude' => (float) $data['lat'],
                            'longitude' => (float) $data['lon'],
                            'city' => $data['city'] ?? null,
                            'region' => $data['regionName'] ?? null,
                            'country' => $data['country'] ?? null,
                            'source' => 'ip_detection',
                            'detected_at' => now()->toISOString(),
                        ];
                    }
                }

                // Fallback : ipinfo.io (gratuit, 50k req/mois)
                $response2 = Http::timeout(5)->get("https://ipinfo.io/{$ip}/json");
                
                if ($response2->successful()) {
                    $data2 = $response2->json();
                    $loc = explode(',', $data2['loc'] ?? '0,0');
                    
                    if (count($loc) === 2 && $loc[0] != 0) {
                        return [
                            'latitude' => (float) $loc[0],
                            'longitude' => (float) $loc[1],
                            'city' => $data2['city'] ?? null,
                            'region' => $data2['region'] ?? null,
                            'country' => $data2['country'] ?? null,
                            'source' => 'ip_detection_fallback',
                            'detected_at' => now()->toISOString(),
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('IP Geolocation error: ' . $e->getMessage());
            }

            return $this->getDefaultLocation();
        });
    }

    /**
     * Localisation par défaut (Mayotte - Lunamars)
     */
    private function getDefaultLocation(): array
    {
        return [
            'latitude' => -12.8275,
            'longitude' => 45.1662,
            'city' => 'Mamoudzou',
            'country' => 'Mayotte',
            'source' => 'default',
            'detected_at' => now()->toISOString(),
        ];
    }

    /**
     * Met à jour les coordonnées géo de l'utilisateur en base
     */
    private function updateUserGeoData($user, array $geoData): void
    {
        try {
            $updateData = [
                'latitude' => $geoData['latitude'],
                'longitude' => $geoData['longitude'],
            ];

            if (!$user->detected_city && isset($geoData['city'])) {
                $updateData['detected_city'] = $geoData['city'];
            }
            if (!$user->detected_country && isset($geoData['country'])) {
                $updateData['detected_country'] = $geoData['country'];
            }

            $user->update($updateData);
        } catch (\Exception $e) {
            Log::warning('Failed to update user geo data: ' . $e->getMessage());
        }
    }

    /**
     * Stocke la géolocalisation en session
     */
    private function storeInSession(array $geoData): void
    {
        Session::put('user_geolocation', $geoData);
    }

    /**
     * Vérifie si les données géo en session sont assez récentes (< 24h)
     */
    private function isRecentEnough(?array $geoData): bool
    {
        if (!$geoData || !isset($geoData['detected_at'])) {
            return false;
        }

        try {
            $detectedAt = \Carbon\Carbon::parse($geoData['detected_at']);
            return $detectedAt->diffInHours(now()) < 24;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Calcule le rayon recommandé en fonction de la densité d'annonces
     * Plus il y a d'annonces proches, plus le rayon peut être petit
     */
    public function getRecommendedRadius(float $lat, float $lng): int
    {
        $radii = [15, 30, 50, 100, 200];
        
        foreach ($radii as $radius) {
            $count = \App\Models\Ad::where('status', 'active')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->withinRadius($lat, $lng, $radius)
                ->count();
            
            if ($count >= 5) {
                return $radius;
            }
        }

        return 200; // Rayon max par défaut
    }

    /**
     * Obtient la géolocalisation depuis le navigateur (stockée en session via JS)
     */
    public function getFromBrowserGeolocation(): ?array
    {
        $browserGeo = Session::get('browser_geolocation');
        if ($browserGeo && isset($browserGeo['latitude'])) {
            return [
                'latitude' => (float) $browserGeo['latitude'],
                'longitude' => (float) $browserGeo['longitude'],
                'city' => $browserGeo['city'] ?? null,
                'country' => $browserGeo['country'] ?? null,
                'source' => 'browser',
                'detected_at' => $browserGeo['detected_at'] ?? now()->toISOString(),
            ];
        }
        return null;
    }
}

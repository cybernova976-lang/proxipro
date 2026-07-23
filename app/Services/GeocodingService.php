<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    private $service;

    public function __construct()
    {
        // Utilise Nominatim (OpenStreetMap - gratuit)
        $this->service = 'nominatim';
    }

    /**
     * Géocode une adresse en coordonnées GPS
     */
    public function geocode(string $address): ?array
    {
        // Mise en cache pour éviter les appels répétitifs
        $cacheKey = 'geocode_'.md5($address);

        return Cache::remember($cacheKey, 3600 * 24 * 30, function () use ($address) {
            if ($this->service === 'nominatim') {
                return $this->geocodeWithNominatim($address);
            }

            return null;
        });
    }

    /**
     * Utilise Nominatim (OpenStreetMap)
     */
    private function geocodeWithNominatim(string $address): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent(),
            ])->timeout(10)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
                'addressdetails' => 1,
            ]);

            if ($response->successful() && ! empty($response->json())) {
                $data = $response->json()[0];

                return [
                    'latitude' => (float) $data['lat'],
                    'longitude' => (float) $data['lon'],
                    'address' => $data['display_name'],
                    'postal_code' => $data['address']['postcode'] ?? null,
                    'country' => $data['address']['country'] ?? 'France',
                    'city' => $data['address']['city'] ?? $data['address']['town'] ?? $data['address']['village'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Geocoding error: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Géocode inverse : coordonnées → adresse
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array
    {
        $cacheKey = 'reverse_geocode_'.md5($latitude.','.$longitude);

        return Cache::remember($cacheKey, 3600 * 24 * 30, function () use ($latitude, $longitude) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => $this->userAgent(),
                ])->timeout(10)->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'json',
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'address' => $data['display_name'] ?? null,
                        'postal_code' => $data['address']['postcode'] ?? null,
                        'city' => $data['address']['city'] ?? $data['address']['town'] ?? $data['address']['village'] ?? null,
                        'country' => $data['address']['country'] ?? 'France',
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Reverse geocoding error: '.$e->getMessage());
            }

            return null;
        });
    }

    /**
     * Calcule la distance entre deux points (en km)
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Rayon de la Terre en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function userAgent(): string
    {
        $appName = preg_replace('/[^A-Za-z0-9._-]/', '', (string) config('app.name', 'Lunamars')) ?: 'Lunamars';
        $contact = config('site.support_email') ?: config('app.url');

        return $appName.'/1.0 (+'.$contact.')';
    }
}

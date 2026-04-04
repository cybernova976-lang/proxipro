<?php

use Illuminate\Support\Facades\Storage;

if (!function_exists('storage_url')) {
    /**
     * Génère l'URL publique d'un fichier stocké sur le disque "public".
     * Fonctionne avec le disque local (dev) ET Cloudflare R2/S3 (production).
     */
    function storage_url(?string $path): string
    {
        if (empty($path)) {
            return asset('images/default-avatar.svg');
        }

        // Si le chemin est déjà une URL complète (ancien avatar Google, etc.)
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Nettoyer le préfixe storage/ si présent (restes de l'ancien stockage local)
        $path = preg_replace('#^/?storage/#', '', $path);

        return Storage::disk('public')->url($path);
    }
}

<?php

if (!function_exists('storage_url')) {
    /**
     * Génère l'URL publique d'un fichier stocké sur le disque "public".
     * Fonctionne avec le disque local (dev) ET Cloudflare R2/S3 (production).
     *
     * - Si le disque public est configuré en s3 (R2/S3) : construit l'URL
     *   depuis la clé `url` du disque (config filesystems) ou AWS_URL en fallback.
     * - Sinon : utilise asset('storage/' . chemin) pour le disque local.
     */
    function storage_url(?string $path): string
    {
        if (empty($path)) {
            return '';
        }

        // Si le chemin est déjà une URL complète (avatar Google OAuth, etc.)
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Nettoyer le préfixe storage/ si présent (restes de l'ancien stockage local)
        $path = ltrim(preg_replace('#^/?storage/#', '', $path), '/');

        if (config('filesystems.disks.public.driver') === 's3' || env('FILESYSTEM_PUBLIC_DRIVER') === 's3') {
            // Cloudflare R2 / S3 : assembler l'URL publique depuis AWS_URL
            $baseUrl = rtrim(config('filesystems.disks.public.url', env('AWS_URL', '')), '/');

            if (empty($baseUrl)) {
                return asset('storage/' . $path);
            }

            return $baseUrl . '/' . ltrim($path, '/');
        }

        // Disque local : servir via le lien symbolique public/storage
        return asset('storage/' . $path);
    }
}

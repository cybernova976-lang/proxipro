<?php

if (!function_exists('storage_url')) {
    /**
     * Génère l'URL publique d'un fichier stocké sur le disque par défaut.
     * Fonctionne avec le disque local (dev) ET Cloudflare R2/S3 (production).
     *
     * Stratégie de résolution :
     * 1. Si le chemin est déjà une URL complète (OAuth, etc.) → retourner tel quel.
     * 2. Si FILESYSTEM_DISK=s3 ET AWS_URL est configuré → URL R2/S3.
     * 3. Sinon → asset('storage/…') via le lien symbolique public/storage.
     *
     * N'utilise jamais env() directement — toujours config() — pour être compatible
     * avec php artisan config:cache.
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

        // Determine whether the active default disk is S3-based.
        $defaultDisk   = config('filesystems.default', 'public');
        $defaultDriver = config('filesystems.disks.' . $defaultDisk . '.driver', 'local');

        if ($defaultDriver === 's3') {
            // Cloudflare R2 / S3 : assembler l'URL publique depuis AWS_URL.
            // If AWS_URL is not set, fall back to local storage so images are
            // never broken when R2 credentials are missing or misconfigured.
            $baseUrl = rtrim(
                config('filesystems.disks.' . $defaultDisk . '.url', config('filesystems.disks.s3.url', '')),
                '/'
            );

            if (!empty($baseUrl)) {
                return $baseUrl . '/' . ltrim($path, '/');
            }

            // AWS_URL not configured — fall through to local asset URL below.
        }

        // Disque local (ou S3 sans AWS_URL) : servir via le lien symbolique public/storage.
        return asset('storage/' . $path);
    }
}


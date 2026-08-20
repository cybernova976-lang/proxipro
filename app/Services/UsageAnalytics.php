<?php

namespace App\Services;

use App\Models\UsageDailyMetric;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class UsageAnalytics
{
    public const EVENTS = [
        'page_view',
        'session_start',
        'pwa_install',
        'push_enabled',
    ];

    public const DEVICE_TYPES = ['mobile', 'tablet', 'desktop'];

    public const APP_MODES = ['browser', 'pwa'];

    /**
     * Enregistre uniquement un compteur agrégé. Aucun identifiant de compte,
     * adresse IP, user-agent, contenu ou paramètre d'URL n'est conservé.
     */
    public function record(
        string $eventName,
        ?string $routeName = null,
        string $deviceType = 'desktop',
        string $appMode = 'browser',
        ?CarbonInterface $date = null
    ): void {
        if (! in_array($eventName, self::EVENTS, true)) {
            return;
        }

        $metricDate = ($date ?? now())->toDateString();
        $routeName = $this->normalizeRouteName($routeName);
        $deviceType = in_array($deviceType, self::DEVICE_TYPES, true) ? $deviceType : 'desktop';
        $appMode = in_array($appMode, self::APP_MODES, true) ? $appMode : 'browser';
        $now = now();

        $dimensions = [
            'metric_date' => $metricDate,
            'event_name' => $eventName,
            'route_name' => $routeName,
            'device_type' => $deviceType,
            'app_mode' => $appMode,
        ];

        UsageDailyMetric::query()->insertOrIgnore([
            ...$dimensions,
            'count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        UsageDailyMetric::query()
            ->where($dimensions)
            ->increment('count', 1, ['updated_at' => $now]);
    }

    public function pruneBefore(CarbonInterface $cutoff): int
    {
        return UsageDailyMetric::query()
            ->where('metric_date', '<', $cutoff->toDateString())
            ->delete();
    }

    public function routeLabel(string $routeName): string
    {
        return [
            'homepage' => 'Accueil public',
            'feed' => 'Fil des annonces',
            'ads.index' => 'Liste des annonces',
            'ads.show' => 'Détail d’une annonce',
            'ads.create' => 'Publication d’une annonce',
            'messages.index' => 'Messagerie',
            'messages.show' => 'Conversation',
            'profile.show' => 'Profil personnel',
            'profile.public' => 'Profil public',
            'settings.index' => 'Paramètres',
            'login' => 'Connexion',
            'register' => 'Inscription',
            'pricing.index' => 'Tarifs et points',
            'pro.dashboard' => 'Espace professionnel',
            'pro.analytics' => 'Statistiques professionnelles',
            'other' => 'Autres pages',
        ][$routeName] ?? Str::headline(str_replace(['.', '-'], ' ', $routeName));
    }

    private function normalizeRouteName(?string $routeName): string
    {
        $routeName = trim((string) $routeName);

        if ($routeName === '' || ! preg_match('/^[a-zA-Z0-9_.-]+$/', $routeName)) {
            return 'other';
        }

        return Str::limit($routeName, 100, '');
    }
}

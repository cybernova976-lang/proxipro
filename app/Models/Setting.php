<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    /**
     * Récupérer une valeur de paramètre
     */
    public static function get(string $key, $default = null)
    {
        // Mémoire de la requête en cours : un même réglage lu 40 fois ne coûte
        // qu'une lecture. Un réglage absent est mémorisé aussi (« found » faux),
        // sinon Cache::remember le relirait en base à chaque appel.
        $memo = self::memo();
        if ($memo->offsetExists($key)) {
            return $memo[$key]['found'] ? $memo[$key]['value'] : $default;
        }

        try {
            $entry = Cache::remember("setting_v2_{$key}", 3600, function () use ($key) {
                $setting = self::where('key', $key)->first();

                return ['found' => $setting !== null, 'value' => $setting?->value];
            });
            $memo[$key] = $entry;

            return $entry['found'] ? $entry['value'] : $default;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Setting::get('{$key}') failed: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Mémoire liée au conteneur en mode « scoped » (AppServiceProvider) :
     * Laravel la remet à zéro à chaque requête et à chaque tâche de file
     * d'attente, donc un réglage modifié n'est jamais servi périmé à un worker.
     */
    private static function memo(): \ArrayObject
    {
        return app('settings.memo');
    }

    private static function forgetMemo(string $key): void
    {
        $memo = self::memo();
        if ($memo->offsetExists($key)) {
            $memo->offsetUnset($key);
        }
    }

    protected static function booted(): void
    {
        // Toute écriture directe du modèle invalide les deux niveaux de cache.
        $forget = function (self $setting): void {
            self::forgetMemo($setting->key);
            Cache::forget("setting_{$setting->key}");
            Cache::forget("setting_v2_{$setting->key}");
        };
        static::saved($forget);
        static::deleted($forget);
    }

    /**
     * Définir une valeur de paramètre
     */
    public static function set(string $key, $value, string $group = 'general'): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        self::forgetMemo($key);
        Cache::forget("setting_{$key}");
        Cache::forget("setting_v2_{$key}");
    }

    /**
     * Récupérer tous les paramètres d'un groupe
     */
    public static function getGroup(string $group): array
    {
        $settings = self::where('group', $group)->get();
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->value;
        }
        
        return $result;
    }

    /**
     * Mettre à jour plusieurs paramètres d'un groupe
     */
    public static function setGroup(string $group, array $values): void
    {
        foreach ($values as $key => $value) {
            self::set($key, $value, $group);
        }
    }
}

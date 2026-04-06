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
        try {
            $setting = Cache::remember("setting_{$key}", 3600, function () use ($key) {
                return self::where('key', $key)->first();
            });

            return $setting ? $setting->value : $default;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Setting::get('{$key}') failed: " . $e->getMessage());
            return $default;
        }
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

        Cache::forget("setting_{$key}");
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

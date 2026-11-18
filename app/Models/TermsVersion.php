<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Une version publiée des conditions générales d'utilisation. Le texte vit
 * dans la vue legal.terms ; cette ligne porte le résumé des changements, la
 * date d'entrée en vigueur et sert de référence aux acceptations.
 */
class TermsVersion extends Model
{
    private const CACHE_KEY = 'terms_version_current_id';

    private const PAUSE_KEY = 'terms_acceptance_paused';

    protected $fillable = ['version', 'summary', 'effective_at', 'published_by'];

    protected function casts(): array
    {
        return ['effective_at' => 'datetime'];
    }

    /** Dernière version publiée, ou null si l'administrateur n'en a publié aucune. */
    public static function current(): ?self
    {
        // « 0 » = aucune version : mémorisé aussi, pour ne pas interroger la base à chaque page.
        try {
            $id = (int) Cache::remember(self::CACHE_KEY, 600, fn () => (int) static::query()->max('id'));

            return $id > 0 ? static::find($id) : null;
        } catch (\Throwable $error) {
            // Une table absente ou un cache en panne ne doit jamais bloquer tout le site.
            report($error);

            return null;
        }
    }

    public static function forgetCurrent(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Interrupteur de l'administrateur : suspendue, la demande d'acceptation
     * n'est plus présentée à personne. Les acceptations déjà données restent
     * enregistrées, et tout reprend là où cela s'était arrêté à la réactivation.
     */
    public static function isPaused(): bool
    {
        return (string) Setting::get(self::PAUSE_KEY, '0') === '1';
    }

    public static function setPaused(bool $paused): void
    {
        Setting::set(self::PAUSE_KEY, $paused ? '1' : '0', 'legal');
    }

    /** À partir de cette date, un membre ne peut plus continuer sans avoir accepté. */
    public function isMandatory(): bool
    {
        return $this->effective_at->lessThanOrEqualTo(now());
    }

    public function acceptances()
    {
        return $this->hasMany(TermsAcceptance::class);
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by')->withTrashed();
    }
}

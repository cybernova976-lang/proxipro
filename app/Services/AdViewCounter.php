<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Comptage des vues d'annonce, avec les mêmes règles que les vues de profil
 * (App\Services\ProfileViewCounter) :
 *   1. l'auteur qui regarde sa propre annonce ne compte pas ;
 *   2. un robot d'indexation ne compte pas ;
 *   3. un même visiteur ne compte qu'une fois par annonce et par jour.
 *
 * Un chiffre montré à un prestataire doit être un chiffre qu'il peut croire :
 * c'est ce qui permet de constater l'effet d'une mise en avant sans rien
 * promettre.
 */
class AdViewCounter
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python-requests|headless|lighthouse|pingdom|semrush|ahrefs/i';

    /** @return bool true si la vue a réellement été comptée */
    public function record(Ad $ad, ?User $viewer, Request $request): bool
    {
        if ($viewer && (int) $viewer->id === (int) $ad->user_id) {
            return false;
        }

        $userAgent = (string) $request->userAgent();
        if ($userAgent === '' || preg_match(self::BOT_PATTERN, $userAgent) === 1) {
            return false;
        }

        // L'index unique fait le dédoublonnage : une seule requête, pas de course.
        $inserted = DB::table('ad_views')->insertOrIgnore([
            'ad_id' => $ad->id,
            'viewer_user_id' => $viewer?->id,
            'viewer_key' => $this->viewerKey($viewer, $request),
            'viewed_on' => Carbon::today()->toDateString(),
            'created_at' => now(),
        ]);

        if ($inserted < 1) {
            return false;
        }

        // Total depuis le début du comptage, lisible sans jointure.
        Ad::whereKey($ad->id)->increment('views');

        return true;
    }

    /** Nombre de vues d'une annonce depuis une date donnée (incluse). */
    public function countSince(Ad $ad, CarbonInterface $since): int
    {
        return DB::table('ad_views')->where('ad_id', $ad->id)
            ->where('viewed_on', '>=', $since->toDateString())->count();
    }

    /**
     * Date à partir de laquelle les vues de cette annonce sont comptées : sa
     * publication, ou le début du comptage si elle est plus ancienne. Null tant
     * que le comptage n'a pas commencé : on n'affiche alors aucun chiffre.
     */
    public function countedSince(Ad $ad): ?CarbonInterface
    {
        $started = Setting::get('ad_views_counted_since');
        if (! is_string($started) || trim($started) === '') {
            return null;
        }

        try {
            $started = Carbon::parse($started)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        return $ad->created_at && $ad->created_at->greaterThan($started) ? $ad->created_at->copy()->startOfDay() : $started;
    }

    /** Supprime le détail des vues antérieures à la date donnée ; le total de l'annonce reste. */
    public function pruneBefore(CarbonInterface $date): int
    {
        return DB::table('ad_views')->where('viewed_on', '<', $date->toDateString())->delete();
    }

    private function viewerKey(?User $viewer, Request $request): string
    {
        if ($viewer) {
            return 'u:'.$viewer->id;
        }

        return 'a:'.substr(hash_hmac('sha256', $request->ip().'|'.$request->userAgent(), (string) config('app.key')), 0, 40);
    }
}

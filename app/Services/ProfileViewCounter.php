<?php

namespace App\Services;

use App\Models\ProfileView;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Comptage des vues de profil.
 *
 * Regles, dans l'ordre ou elles s'appliquent :
 *   1. on ne compte jamais un utilisateur qui regarde son propre profil ;
 *   2. on ne compte jamais un robot d'indexation ;
 *   3. un meme visiteur ne compte qu'une fois par profil et par jour.
 *
 * L'objectif est qu'un chiffre affiche a un prestataire soit un chiffre
 * qu'il puisse croire : recharger la page ne le fait pas monter.
 */
class ProfileViewCounter
{
    /**
     * Robots les plus courants. La liste n'a pas besoin d'etre exhaustive :
     * elle sert a eviter que l'indexation gonfle les compteurs.
     */
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python-requests|headless|lighthouse|pingdom|semrush|ahrefs/i';

    /**
     * Enregistre une vue si elle est legitime et nouvelle pour la journee.
     *
     * @return bool true si la vue a reellement ete comptee
     */
    public function record(User $profile, ?User $viewer, Request $request): bool
    {
        // 1. Son propre profil ne compte pas.
        if ($viewer && (int) $viewer->id === (int) $profile->id) {
            return false;
        }

        // 2. Les robots ne comptent pas.
        $userAgent = (string) $request->userAgent();
        if ($userAgent === '' || preg_match(self::BOT_PATTERN, $userAgent) === 1) {
            return false;
        }

        $today = Carbon::today();

        // 3. Une vue par visiteur, par profil et par jour.
        //    insertOrIgnore s'appuie sur l'index unique : pas de course possible
        //    entre deux requetes simultanees, et une seule requete SQL.
        $inserted = DB::table('profile_views')->insertOrIgnore([
            'profile_user_id' => $profile->id,
            'viewer_user_id' => $viewer?->id,
            'viewer_key' => $this->viewerKey($viewer, $request),
            'viewed_on' => $today->toDateString(),
            'created_at' => now(),
        ]);

        if ($inserted < 1) {
            return false;
        }

        // Le total historique reste disponible sur la fiche utilisateur.
        $profile->increment('profile_views');

        return true;
    }

    /** Nombre de vues depuis une date donnee (incluse). */
    public function countSince(User $profile, CarbonInterface $since): int
    {
        return ProfileView::where('profile_user_id', $profile->id)
            ->where('viewed_on', '>=', $since->toDateString())
            ->count();
    }

    /** Nombre de vues depuis le premier jour du mois en cours. */
    public function countThisMonth(User $profile): int
    {
        return $this->countSince($profile, Carbon::today()->startOfMonth());
    }

    /** Nombre de vues sur les trente derniers jours. */
    public function countLastThirtyDays(User $profile): int
    {
        return $this->countSince($profile, Carbon::today()->subDays(29));
    }

    /** Supprime les vues anterieures a la date donnee. Retourne le nombre de lignes. */
    public function pruneBefore(CarbonInterface $date): int
    {
        return ProfileView::where('viewed_on', '<', $date->toDateString())->delete();
    }

    /**
     * Identifiant du visiteur.
     *
     * Membre connecte  : « u:<id> ».
     * Visiteur anonyme : « a:<empreinte> », ou l'empreinte est un HMAC de
     * l'adresse IP et du navigateur signe avec la cle applicative. L'adresse
     * IP elle-meme n'est jamais enregistree et l'empreinte n'est pas reversible.
     */
    private function viewerKey(?User $viewer, Request $request): string
    {
        if ($viewer) {
            return 'u:'.$viewer->id;
        }

        $fingerprint = hash_hmac(
            'sha256',
            $request->ip().'|'.$request->userAgent(),
            (string) config('app.key')
        );

        return 'a:'.substr($fingerprint, 0, 40);
    }
}

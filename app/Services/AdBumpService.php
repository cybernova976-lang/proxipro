<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\ProSubscription;
use App\Support\AdPromotionCatalog;

/**
 * Remontée automatique des offres.
 *
 * Une offre « remontée » revient en haut des offres non sponsorisées : le
 * classement « plus récentes d'abord » lit ads.bumped_at quand il existe
 * (App\Models\Ad::freshnessSql). La date de publication affichée ne change pas.
 *
 *  - Remontée achetée : tous les 1 ou 7 jours jusqu'à ads.bump_until.
 *  - Abonnement Premium : chaque offre active de l'abonné remonte toutes les
 *    semaines, sans achat.
 *
 * Rien ne se passe tant que les nouvelles règles de mise en avant ne sont pas
 * en vigueur (AdPromotionCatalog::newRulesActive).
 */
class AdBumpService
{
    /** Intervalle de la remontée incluse dans l'abonnement. */
    public const SUBSCRIBER_EVERY_DAYS = 7;

    /** @return array{purchased: int, subscribers: int, ended: int} */
    public function run(): array
    {
        if (! AdPromotionCatalog::newRulesActive()) {
            return ['purchased' => 0, 'subscribers' => 0, 'ended' => 0];
        }

        $now = now();

        // Remontées achetées dont l'échéance est arrivée.
        $purchased = 0;
        Ad::marketplaceActive()->where('service_type', 'offre')
            ->whereNotNull('bump_every_days')->where('bump_until', '>', $now)
            ->orderBy('id')
            ->each(function (Ad $ad) use (&$purchased, $now) {
                $due = $ad->bumped_at === null
                    || $ad->bumped_at->copy()->addDays((int) $ad->bump_every_days)->lessThanOrEqualTo($now);
                if ($due) {
                    $ad->forceFill(['bumped_at' => $now])->saveQuietly();
                    $purchased++;
                }
            });

        // Remontée hebdomadaire incluse dans l'abonnement Premium.
        $subscriberIds = ProSubscription::query()->currentlyActive()
            ->where(fn ($started) => $started->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->pluck('user_id')->unique()->all();
        $subscribers = 0;
        if ($subscriberIds !== []) {
            $subscribers = Ad::marketplaceActive()->where('service_type', 'offre')
                ->whereIn('user_id', $subscriberIds)
                ->whereRaw(Ad::freshnessSql().' <= ?', [$now->copy()->subDays(self::SUBSCRIBER_EVERY_DAYS)])
                ->update(['bumped_at' => $now]);
        }

        // Remontées achetées arrivées à leur terme : plus rien de programmé.
        $ended = Ad::whereNotNull('bump_until')->where('bump_until', '<=', $now)
            ->update(['bump_until' => null, 'bump_every_days' => null]);

        return ['purchased' => $purchased, 'subscribers' => (int) $subscribers, 'ended' => (int) $ended];
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Ad;
use App\Notifications\BoostExpiringNotification;
use Illuminate\Console\Command;

class SendBoostExpiringAlerts extends Command
{
    protected $signature = 'boosts:send-expiring-alerts';
    protected $description = 'Envoie des notifications aux utilisateurs dont les boosts/urgents expirent dans moins de 48h';

    public function handle(): int
    {
        $notified = 0;

        // Boosts expirant dans 48h (mais pas encore expirés)
        $expiringBoosts = Ad::where('is_boosted', true)
            ->where('boost_end', '>', now())
            ->where('boost_end', '<=', now()->addHours(48))
            ->with('user')
            ->get();

        foreach ($expiringBoosts as $ad) {
            if (!$ad->user) continue;

            // Éviter les doublons : vérifier si une notification similaire a déjà été envoyée dans les dernières 24h
            $alreadyNotified = $this->alreadyNotified($ad, 'boost');

            if (!$alreadyNotified) {
                $hoursLeft = (int) now()->diffInHours($ad->boost_end, false);
                $ad->user->notify(new BoostExpiringNotification($ad, 'boost', max(1, $hoursLeft)));
                $notified++;
            }
        }

        // Urgents expirant dans 48h
        $expiringUrgents = Ad::where('is_urgent', true)
            ->whereNotNull('urgent_until')
            ->where('urgent_until', '>', now())
            ->where('urgent_until', '<=', now()->addHours(48))
            ->with('user')
            ->get();

        foreach ($expiringUrgents as $ad) {
            if (!$ad->user) continue;

            $alreadyNotified = $this->alreadyNotified($ad, 'urgent');

            if (!$alreadyNotified) {
                $hoursLeft = (int) now()->diffInHours($ad->urgent_until, false);
                $ad->user->notify(new BoostExpiringNotification($ad, 'urgent', max(1, $hoursLeft)));
                $notified++;
            }
        }

        $this->info("{$notified} notification(s) d'expiration envoyée(s).");

        if ($notified > 0) {
            \Log::info("Boost expiring alerts: {$notified} notification(s) envoyée(s).");
        }

        return self::SUCCESS;
    }

    /**
     * Une alerte du même type a-t-elle déjà été envoyée pour cette annonce
     * depuis 24 heures ? Le contenu est comparé en PHP : la colonne
     * notifications.data est du texte, et un opérateur JSON SQL (accepté par
     * SQLite) fait échouer la requête sous PostgreSQL, la base de production.
     */
    private function alreadyNotified(Ad $ad, string $kind): bool
    {
        return $ad->user->notifications()
            ->where('type', BoostExpiringNotification::class)
            ->where('created_at', '>=', now()->subHours(24))
            ->get(['id', 'data'])
            ->contains(fn ($notification) => (int) ($notification->data['ad_id'] ?? 0) === (int) $ad->id
                && ($notification->data['type'] ?? null) === $kind);
    }
}

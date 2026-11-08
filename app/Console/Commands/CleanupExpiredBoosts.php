<?php

namespace App\Console\Commands;

use App\Models\Ad;
use Illuminate\Console\Command;

class CleanupExpiredBoosts extends Command
{
    protected $signature = 'boosts:cleanup';
    protected $description = 'Désactive les boosts et modes urgents expirés';

    public function handle(): int
    {
        // Désactiver les boosts expirés
        $expiredBoosts = Ad::where('is_boosted', true)
            ->whereNotNull('boost_end')
            ->where('boost_end', '<=', now())
            ->update([
                'is_boosted' => false,
                'boost_end' => null,
                'boost_type' => null,
                'boost_source' => null,
            ]);

        // Désactiver les urgents expirés
        $expiredUrgents = Ad::where('is_urgent', true)
            ->whereNotNull('urgent_until')
            ->where('urgent_until', '<=', now())
            ->update([
                'is_urgent' => false,
                'urgent_until' => null,
                'urgent_source' => null,
                'sidebar_priority' => 0,
            ]);

        $this->info("Nettoyage terminé : {$expiredBoosts} boost(s) et {$expiredUrgents} urgent(s) désactivés.");

        if ($expiredBoosts > 0 || $expiredUrgents > 0) {
            \Log::info("Boosts cleanup: {$expiredBoosts} boost(s) expirés, {$expiredUrgents} urgent(s) expirés désactivés.");
        }

        return self::SUCCESS;
    }
}

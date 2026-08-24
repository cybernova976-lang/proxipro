<?php

namespace App\Console\Commands;

use App\Services\ProfileViewCounter;
use Illuminate\Console\Command;

/**
 * Les vues de profil ne sont conservees que le temps d'alimenter les
 * statistiques affichees. Au-dela, seules restent les valeurs agregees.
 */
class PruneProfileViews extends Command
{
    protected $signature = 'profile-views:prune {--months=13 : Nombre de mois de vues detaillees a conserver}';

    protected $description = 'Supprime les vues de profil arrivees a expiration';

    public function handle(ProfileViewCounter $counter): int
    {
        $months = max(1, (int) $this->option('months'));
        $deleted = $counter->pruneBefore(today()->subMonthsNoOverflow($months));

        $this->info("{$deleted} vue(s) de profil supprimee(s).");

        return self::SUCCESS;
    }
}

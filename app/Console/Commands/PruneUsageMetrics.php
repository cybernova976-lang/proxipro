<?php

namespace App\Console\Commands;

use App\Services\UsageAnalytics;
use Illuminate\Console\Command;

class PruneUsageMetrics extends Command
{
    protected $signature = 'usage:prune {--months=25 : Nombre de mois de statistiques agrégées à conserver}';

    protected $description = 'Supprime les statistiques agrégées d’utilisation arrivées à expiration';

    public function handle(UsageAnalytics $analytics): int
    {
        $months = max(1, (int) $this->option('months'));
        $deleted = $analytics->pruneBefore(today()->subMonthsNoOverflow($months));

        $this->info("{$deleted} ligne(s) de statistiques supprimée(s).");

        return self::SUCCESS;
    }
}

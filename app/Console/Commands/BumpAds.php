<?php

namespace App\Console\Commands;

use App\Services\AdBumpService;
use Illuminate\Console\Command;

/**
 * Remontée automatique des offres : remontées achetées arrivées à échéance et
 * remontée hebdomadaire incluse dans l'abonnement Premium.
 */
class BumpAds extends Command
{
    protected $signature = 'ads:bump';

    protected $description = 'Fait remonter les offres dont la remontée automatique est arrivée à échéance';

    public function handle(AdBumpService $bumps): int
    {
        $result = $bumps->run();

        $this->info("Remontées : {$result['purchased']} achetée(s), {$result['subscribers']} d'abonnés, {$result['ended']} terminée(s).");

        return self::SUCCESS;
    }
}

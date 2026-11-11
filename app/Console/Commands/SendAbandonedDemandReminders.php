<?php

namespace App\Console\Commands;

use App\Services\DemandDraftReminderService;
use Illuminate\Console\Command;

class SendAbandonedDemandReminders extends Command
{
    protected $signature = 'demand-drafts:send-reminders {--minutes=1440}';

    protected $description = 'Prépare pour validation un rappel par demande restée inachevée';

    public function handle(DemandDraftReminderService $reminders): int
    {
        $count = $reminders->prepare((int) $this->option('minutes'));
        $this->info("{$count} relance(s) placée(s) en attente de validation.");

        return self::SUCCESS;
    }
}

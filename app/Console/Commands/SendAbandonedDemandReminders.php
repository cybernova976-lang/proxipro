<?php

namespace App\Console\Commands;

use App\Services\DemandDraftReminderService;
use Illuminate\Console\Command;

class SendAbandonedDemandReminders extends Command
{
    protected $signature = 'demand-drafts:send-reminders {--minutes=1440}';

    protected $description = 'Envoie un rappel par demande restée inachevée (au plus un par brouillon)';

    public function handle(DemandDraftReminderService $reminders): int
    {
        $result = $reminders->run((int) $this->option('minutes'));
        $this->info("{$result['sent']} rappel(s) envoyé(s), {$result['cancelled']} annulé(s), {$result['failed']} échec(s).");

        return self::SUCCESS;
    }
}

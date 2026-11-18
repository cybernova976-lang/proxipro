<?php

namespace App\Console\Commands;

use App\Services\ReleaseReminderService;
use Illuminate\Console\Command;

class SendReleaseReminders extends Command
{
    protected $signature = 'service-orders:remind-release';

    protected $description = 'Rappelle aux clients de confirmer la fin des prestations payées, pour que le prestataire soit versé';

    public function handle(ReleaseReminderService $reminders): int
    {
        $result = $reminders->run();
        $this->info("{$result['sent']} rappel(s) de confirmation envoyé(s), {$result['failed']} échec(s).");

        return self::SUCCESS;
    }
}

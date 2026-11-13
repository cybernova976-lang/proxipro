<?php

namespace App\Console\Commands;

use App\Services\QuoteReminderService;
use Illuminate\Console\Command;

class SendQuoteReminders extends Command
{
    protected $signature = 'pro-quotes:send-reminders';

    protected $description = 'Relance une seule fois les devis Premium restés sans réponse depuis 7 jours';

    public function handle(QuoteReminderService $reminders): int
    {
        $result = $reminders->run();
        $this->info("{$result['sent']} relance(s) de devis envoyée(s), {$result['failed']} échec(s).");

        return self::SUCCESS;
    }
}

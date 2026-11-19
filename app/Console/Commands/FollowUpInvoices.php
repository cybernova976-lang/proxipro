<?php

namespace App\Console\Commands;

use App\Services\InvoiceFollowUpService;
use Illuminate\Console\Command;

class FollowUpInvoices extends Command
{
    protected $signature = 'pro-invoices:follow-up';

    protected $description = 'Passe « En retard » les factures échues et envoie le rappel unique des factures Premium impayées';

    public function handle(InvoiceFollowUpService $followUp): int
    {
        $result = $followUp->run();
        $this->info("{$result['overdue']} facture(s) passée(s) en retard, {$result['sent']} rappel(s) envoyé(s), {$result['failed']} échec(s).");

        return self::SUCCESS;
    }
}

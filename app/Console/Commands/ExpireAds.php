<?php

namespace App\Console\Commands;

use App\Models\Ad;
use Illuminate\Console\Command;

class ExpireAds extends Command
{
    protected $signature = 'ads:expire';

    protected $description = 'Marque comme expirées les annonces arrivées au terme de leur publication';

    public function handle(): int
    {
        $expired = Ad::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $this->info("{$expired} annonce(s) expirée(s).");

        return self::SUCCESS;
    }
}

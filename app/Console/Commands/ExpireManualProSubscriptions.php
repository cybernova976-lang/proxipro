<?php

namespace App\Console\Commands;

use App\Models\ProSubscription;
use Illuminate\Console\Command;

class ExpireManualProSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire-pro';

    protected $description = 'Expire les accès Pro manuels arrivés à échéance';

    public function handle(): int
    {
        $expiredCount = 0;

        ProSubscription::query()
            ->whereNull('stripe_subscription_id')
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->with('user')
            ->chunkById(100, function ($subscriptions) use (&$expiredCount) {
                foreach ($subscriptions as $subscription) {
                    $subscription->update([
                        'status' => 'expired',
                        'auto_renew' => false,
                    ]);
                    $expiredCount++;

                    $user = $subscription->user;
                    if ($user && ! $user->proSubscriptions()->currentlyActive()->exists()) {
                        $user->update([
                            'pro_subscription_plan' => null,
                            'pro_status' => 'inactive',
                        ]);
                    }
                }
            });

        $this->info("{$expiredCount} accès Pro manuel(s) expiré(s).");

        return self::SUCCESS;
    }
}

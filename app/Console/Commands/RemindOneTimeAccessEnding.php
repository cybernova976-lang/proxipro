<?php

namespace App\Console\Commands;

use App\Models\ProSubscription;
use App\Services\ProviderSubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Accès Premium à paiement unique : rien ne se renouvelle, le membre est donc
 * prévenu quelques jours avant la fin, avec un lien pour reprendre un accès.
 * Un seul rappel par accès (colonne expiry_reminder_sent_at).
 */
class RemindOneTimeAccessEnding extends Command
{
    protected $signature = 'subscriptions:remind-one-time-ending';

    protected $description = 'Prévient les membres dont l’accès Premium à paiement unique se termine bientôt';

    public function handle(ProviderSubscriptionService $subscriptions): int
    {
        $days = max(1, (int) config('provider_subscriptions.one_time_reminder_days', 7));
        $sent = 0;
        $errors = 0;

        ProSubscription::query()
            ->oneTimeAccess()
            ->whereNull('stripe_subscription_id')
            ->where('status', 'active')
            ->whereNull('expiry_reminder_sent_at')
            ->where('ends_at', '>', now())
            ->where('ends_at', '<=', now()->addDays($days))
            ->with('user')
            ->chunkById(100, function ($ending) use ($subscriptions, &$sent, &$errors) {
                foreach ($ending as $access) {
                    try {
                        $sent += (int) $subscriptions->remindOneTimeAccessEnding($access);
                    } catch (\Throwable $exception) {
                        // Un accès en erreur n'empêche pas de prévenir les suivants ; il sera repris au prochain passage.
                        $errors++;
                        Log::error('Rappel de fin d’accès Premium impossible', [
                            'pro_subscription_id' => $access->id,
                            'exception' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("{$sent} rappel(s) de fin d’accès envoyé(s) · {$errors} erreur(s).");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}

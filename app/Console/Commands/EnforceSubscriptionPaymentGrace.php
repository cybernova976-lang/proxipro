<?php

namespace App\Console\Commands;

use App\Models\ProSubscription;
use App\Services\ProviderSubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Suit les abonnements dont un paiement a été refusé : rappels pendant le délai
 * de régularisation, puis résiliation et retour au compte gratuit.
 */
class EnforceSubscriptionPaymentGrace extends Command
{
    protected $signature = 'subscriptions:enforce-payment-grace';

    protected $description = 'Rappelle les paiements d’abonnement en retard et résilie ceux restés impayés après le délai';

    public function handle(ProviderSubscriptionService $subscriptions): int
    {
        $counts = ['rappels' => 0, 'résiliations' => 0, 'en attente' => 0, 'resynchronisés' => 0, 'erreurs' => 0];

        ProSubscription::query()
            ->whereNotNull('stripe_subscription_id')
            ->whereNotNull('payment_failed_at')
            ->whereIn('status', ['active', 'pending'])
            ->with('user')
            ->chunkById(100, function ($late) use ($subscriptions, &$counts) {
                foreach ($late as $subscription) {
                    try {
                        if ($subscription->inPaymentGrace()) {
                            $counts['rappels'] += (int) $subscriptions->remindPaymentDue($subscription);

                            continue;
                        }
                        if (! $subscription->hasOpenBilling()) {
                            continue;
                        }

                        $ended = $subscriptions->endForNonPayment($subscription);
                        if ($ended === null) {
                            $counts['en attente']++;
                        } elseif (in_array($ended->status, ['cancelled', 'expired'], true)) {
                            $counts['résiliations']++;
                        } else {
                            $counts['resynchronisés']++;
                        }
                    } catch (\Throwable $exception) {
                        // Un abonnement en erreur n'empêche pas de traiter les suivants ; il sera repris au prochain passage.
                        $counts['erreurs']++;
                        Log::error('Suivi du délai de paiement impossible pour un abonnement', [
                            'pro_subscription_id' => $subscription->id,
                            'exception' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        // Filet de sécurité : un abonnement dont l'échéance est passée sans nouvelle de
        // Stripe (événement non reçu) est relu à la source plutôt que laissé dans le doute.
        ProSubscription::query()
            ->whereNotNull('stripe_subscription_id')
            ->where('status', 'active')
            ->whereNull('payment_failed_at')
            ->where('auto_renew', true)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now()->subHours(6))
            ->where('ends_at', '>', now()->subDays(ProSubscription::PAYMENT_GRACE_DAYS))
            ->chunkById(100, function ($stale) use ($subscriptions, &$counts) {
                foreach ($stale as $subscription) {
                    try {
                        $subscriptions->syncLatest($subscription->stripe_subscription_id);
                        $counts['resynchronisés']++;
                    } catch (\Throwable $exception) {
                        $counts['erreurs']++;
                        Log::error('Relecture Stripe impossible pour un abonnement échu', [
                            'pro_subscription_id' => $subscription->id,
                            'exception' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        $this->info(collect($counts)->map(fn (int $count, string $label) => "{$count} {$label}")->implode(' · '));

        return $counts['erreurs'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}

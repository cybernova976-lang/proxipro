<?php

namespace App\Console\Commands;

use App\Models\ProSubscription;
use Illuminate\Console\Command;

class ExpireManualProSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire-pro';

    protected $description = 'Expire les accès Premium sans abonnement Stripe arrivés à échéance (accès offerts et accès à paiement unique), et ouvre les accès payés d’avance arrivés à leur date';

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
                    // Un accès à paiement unique déjà prolongé : Premium continue, rien n'est annoncé.
                    if ($user && $user->proSubscriptions()->currentlyActive()->exists()) {
                        continue;
                    }

                    $user?->update([
                        'pro_subscription_plan' => null,
                        'pro_status' => 'inactive',
                    ]);

                    // Un accès déjà payé commencera plus tard (accès en cours annulé entre-temps) :
                    // Premium reprendra à sa date, la fin n'est donc pas annoncée.
                    if ($user && $user->proSubscriptions()->paidAhead()->exists()) {
                        continue;
                    }

                    // Le membre apprend la fin de son accès au moment où elle a lieu.
                    \App\Notifications\AccountStatusNotification::dispatch($user, 'premium_expired');
                }
            });

        // Accès à paiement unique payé d'avance arrivé à sa date de début : Premium s'ouvre
        // de lui-même (isPremium lit les accès) ; le compte est remis en cohérence ici,
        // notamment quand l'accès précédent avait été annulé ou suspendu.
        $startedCount = 0;
        ProSubscription::query()
            ->oneTimeAccess()
            ->whereNull('stripe_subscription_id')
            ->currentlyActive()
            ->whereNotNull('starts_at')
            // Seulement un compte remis à « inactive » par la fin d'un accès précédent : pro_status
            // peut aussi porter le statut juridique choisi par le membre, qu'on ne réécrit jamais.
            ->whereHas('user', fn ($user) => $user->where(fn ($user) => $user->whereNull('pro_status')->orWhere('pro_status', 'inactive')))
            ->with('user')
            ->chunkById(100, function ($accesses) use (&$startedCount) {
                foreach ($accesses as $access) {
                    $access->user?->update([
                        'pro_subscription_plan' => $access->plan,
                        'pro_status' => 'active',
                    ]);
                    $startedCount++;
                }
            });

        $this->info("{$expiredCount} accès Premium sans abonnement expiré(s) · {$startedCount} accès payé(s) d’avance ouvert(s).");

        return self::SUCCESS;
    }
}

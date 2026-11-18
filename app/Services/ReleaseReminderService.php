<?php

namespace App\Services;

use App\Models\ServiceOrder;
use App\Models\User;
use App\Notifications\ServiceOrderStatusNotification;
use App\Support\SafeNotifier;
use Illuminate\Support\Carbon;

/**
 * Rappelle au client de confirmer la fin d'une prestation qu'il a payée.
 *
 * Tant qu'il ne confirme pas, le prestataire n'est pas payé. Le premier rappel
 * part quelques jours après la date souhaitée (ou le paiement, à défaut), puis
 * à intervalle régulier, un nombre limité de fois. Le rappel ne verse jamais
 * rien : seul le client confirme, seul un litige bloque.
 */
class ReleaseReminderService
{
    /** @return array{sent: int, failed: int} */
    public function run(?Carbon $now = null): array
    {
        $now ??= now();
        $result = ['sent' => 0, 'failed' => 0];

        ServiceOrder::query()
            ->with(['buyer', 'seller', 'ad'])
            ->where('status', ServiceOrder::STATUS_FUNDED)
            ->where('payment_status', ServiceOrder::PAYMENT_PAID)
            ->whereNotNull('paid_at')
            ->chunkById(100, function ($orders) use ($now, &$result) {
                foreach ($orders as $order) {
                    $this->alertAdminsOnce($order);

                    if (! $this->isDue($order, $now)) {
                        continue;
                    }

                    if (! SafeNotifier::send($order->buyer, new ServiceOrderStatusNotification($order, 'release_reminder'))) {
                        $result['failed']++;

                        continue;
                    }

                    $order->forceFill(['metadata' => array_merge($order->metadata ?? [], [
                        'release_reminders_sent' => $order->release_reminders_sent + 1,
                        'release_reminder_last_at' => $now->toIso8601String(),
                    ])])->save();
                    $result['sent']++;
                }
            });

        return $result;
    }

    /**
     * Tous les rappels sont partis et le client n'a pas répondu : les
     * administrateurs sont prévenus une seule fois, pour décider eux-mêmes.
     */
    private function alertAdminsOnce(ServiceOrder $order): void
    {
        if (! $order->isAwaitingAdminDecision() || ! empty($order->metadata['admin_alerted_at'])) {
            return;
        }

        $sent = false;
        foreach (User::where('role', 'admin')->get() as $admin) {
            $sent = SafeNotifier::send($admin, new ServiceOrderStatusNotification($order, 'admin_decision_needed')) || $sent;
        }

        if ($sent) {
            $order->forceFill(['metadata' => array_merge($order->metadata ?? [], [
                'admin_alerted_at' => now()->toIso8601String(),
            ])])->save();
        }
    }

    public function isDue(ServiceOrder $order, ?Carbon $now = null): bool
    {
        $now ??= now();
        $first = max(1, (int) config('marketplace.release_reminders.first_after_days', 3));
        $every = max(1, (int) config('marketplace.release_reminders.every_days', 3));
        $max = max(0, (int) config('marketplace.release_reminders.max', 3));

        // Compte client supprimé, commande faite à l'envers ou rappels épuisés : on n'écrit pas.
        if (! $order->buyer || $order->buyer->trashed() || $order->isReservationOnDemand()
            || ! $order->canBuyerRelease() || $order->release_reminders_sent >= $max) {
            return false;
        }

        $last = $order->release_reminder_last_at;
        if ($last) {
            return $last->copy()->addDays($every)->lessThanOrEqualTo($now);
        }

        // Le travail ne peut pas être fini avant la date souhaitée par le client.
        $reference = $order->scheduled_for && $order->scheduled_for->greaterThan($order->paid_at)
            ? $order->scheduled_for
            : $order->paid_at;

        return $reference->copy()->addDays($first)->lessThanOrEqualTo($now);
    }
}

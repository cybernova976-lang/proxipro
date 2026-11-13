<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\MonthlyActivityReportNotification;
use App\Services\ProviderActivityReport;
use Illuminate\Console\Command;

class SendMonthlyActivityReports extends Command
{
    protected $signature = 'pro:send-activity-reports';

    protected $description = 'Envoie aux abonnés Premium le rapport d’activité du mois écoulé (une fois par mois)';

    public function handle(ProviderActivityReport $reports): int
    {
        $month = $reports->lastCompletedMonth();
        $sent = 0;

        User::query()
            ->where('is_active', true)
            ->whereHas('proSubscriptions', fn ($query) => $query->currentlyActive())
            ->lazyById(100)
            ->each(function (User $user) use ($reports, $month, &$sent): void {
                if (! $user->isProfessionnel() && ! $user->isServiceProvider()) {
                    return;
                }

                // Le rapport du mois M est envoyé pendant le mois M+1 : une notification de ce
                // type créée depuis le 1er du mois en cours signifie qu'il est déjà parti.
                // Aucun opérateur JSON SQL (la colonne data est du texte sous PostgreSQL).
                $already = $user->notifications()
                    ->where('type', MonthlyActivityReportNotification::class)
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->exists();
                if ($already) {
                    return;
                }

                $report = $reports->forMonth($user, $month);
                // Un mois sans aucune activité mesurée ne donne lieu à aucun envoi :
                // le rapport reste consultable dans l'Espace Pro.
                if (! $report['has_activity']) {
                    return;
                }

                $user->notify(new MonthlyActivityReportNotification($report));
                $sent++;
            });

        $this->info("{$sent} rapport(s) d’activité envoyé(s) pour ".$reports->label($month).'.');

        return self::SUCCESS;
    }
}

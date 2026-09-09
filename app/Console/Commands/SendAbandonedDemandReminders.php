<?php

namespace App\Console\Commands;

use App\Models\DemandDraft;
use App\Models\ManagedEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendAbandonedDemandReminders extends Command
{
    protected $signature = 'demand-drafts:send-reminders {--minutes=1440}';

    protected $description = 'Programme une relance unique pour les demandes authentifiées restées inachevées';

    public function handle(): int
    {
        $minutes = max(1440, (int) $this->option('minutes'));
        $count = 0;

        DemandDraft::query()
            ->whereNull('review_requested_at')
            ->where('last_activity_at', '<=', now()->subMinutes($minutes))
            ->whereHas('user', fn ($query) => $query
                ->whereNotNull('email_verified_at')
                ->where('email_notifications', true))
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $id) use (&$count): void {
                $draft = DB::transaction(function () use ($id) {
                    $draft = DemandDraft::with('user')->lockForUpdate()->find($id);
                    if (! $draft || $draft->review_requested_at || ! $draft->user?->email_notifications) {
                        return null;
                    }

                    $payload = $draft->payload;
                    ManagedEmail::create([
                        'user_id' => $draft->user_id,
                        'source_key' => 'abandoned-demand:'.$draft->id,
                        'type' => 'abandoned_demand',
                        'recipient_email' => $draft->user->email,
                        'recipient_name' => $draft->user->name,
                        'subject' => 'Votre demande Prokejem est presque terminée',
                        'eyebrow' => ($payload['category'] ?? $payload['main_category'] ?? 'Service').' · Demande en cours',
                        'headline' => 'Vous y étiez presque, '.$draft->user->name,
                        'body' => 'Votre demande'.(! empty($payload['title']) ? ' « '.$payload['title'].' »' : '')." n’est pas encore publiée. Reprenez-la à l’étape {$draft->current_step} sans ressaisir les informations déjà enregistrées.",
                        'cta_label' => 'Terminer ma demande',
                        'cta_url' => route('demand.create', ['resume' => 1]),
                        'metadata' => ['demand_draft_id' => $draft->id],
                        'status' => ManagedEmail::STATUS_PENDING,
                        'scheduled_for' => now(),
                    ]);
                    $draft->forceFill(['review_requested_at' => now()])->save();

                    return $draft;
                });

                if (! $draft) {
                    return;
                }

                try {
                    $count++;
                } catch (\Throwable $exception) {
                    $draft->forceFill(['review_requested_at' => null])->save();
                    report($exception);
                }
            });

        $this->info("{$count} relance(s) placée(s) en attente de validation.");

        return self::SUCCESS;
    }
}

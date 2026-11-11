<?php

namespace App\Services;

use App\Models\DemandDraft;
use App\Models\ManagedEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DemandDraftReminderService
{
    public function due(int $minutes = 1440): Builder
    {
        return DemandDraft::query()
            ->whereNull('review_requested_at')
            ->where('last_activity_at', '<=', now()->subMinutes(max(1440, $minutes)))
            ->whereHas('user', fn ($query) => $query
                ->whereNotNull('email_verified_at')->where('is_active', true)
                ->where('draft_reminders_enabled', true)->where('email_notifications', true));
    }

    public function prepare(int $minutes = 1440, ?int $limit = null): int
    {
        $candidates = $this->due($minutes)->select('id')->lazyById(100);
        if ($limit !== null) {
            $candidates = $candidates->take($limit);
        }

        $count = 0;
        foreach ($candidates as $candidate) {
            $count += DB::transaction(function () use ($candidate, $minutes): int {
                $draft = $this->due($minutes)->with('user')->lockForUpdate()->find($candidate->id);
                if (! $draft) {
                    return 0;
                }

                $payload = $draft->payload;
                $title = filled($payload['title'] ?? null) ? ' « '.$payload['title'].' »' : '';
                ManagedEmail::create([
                    'user_id' => $draft->user_id,
                    'source_key' => 'abandoned-demand:'.$draft->id,
                    'type' => 'abandoned_demand',
                    'recipient_email' => $draft->user->email,
                    'recipient_name' => $draft->user->name,
                    'subject' => 'Votre demande Prokejem est presque terminée',
                    'eyebrow' => ($payload['category'] ?? $payload['main_category'] ?? 'Service').' · Demande en cours',
                    'headline' => 'Vous y étiez presque, '.$draft->user->name,
                    'body' => "Votre demande{$title} n’est pas encore publiée. Reprenez-la à l’étape {$draft->current_step} sans ressaisir les informations déjà enregistrées.",
                    'cta_label' => 'Terminer ma demande',
                    'cta_url' => route('demand.create', ['resume' => 1]),
                    'metadata' => ['demand_draft_id' => $draft->id],
                    'status' => ManagedEmail::STATUS_PENDING,
                    'scheduled_for' => now(),
                ]);
                $draft->update(['review_requested_at' => now()]);

                return 1;
            });
        }

        return $count;
    }
}

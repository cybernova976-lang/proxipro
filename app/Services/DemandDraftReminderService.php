<?php

namespace App\Services;

use App\Mail\ManagedEmailMail;
use App\Models\DemandDraft;
use App\Models\ManagedEmail;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Rappels de demande inachevée, envoyés automatiquement par le planificateur.
 *
 * Règles :
 * - un seul rappel par brouillon (la colonne `review_requested_at` marque le
 *   brouillon déjà traité ; elle n'est jamais remise à zéro) ;
 * - au plus tôt 24 heures après la dernière modification, au plus tard
 *   30 jours après : au-delà, un rappel n'a plus de sens ;
 * - uniquement aux comptes actifs et vérifiés qui acceptent les e-mails et
 *   ces rappels ; chaque message porte un lien de désinscription sans connexion ;
 * - tout est revérifié juste avant l'envoi : un brouillon repris, publié ou
 *   supprimé entre-temps annule le rappel.
 */
class DemandDraftReminderService
{
    public const TYPE = 'abandoned_demand';

    public const MIN_IDLE_MINUTES = 1440;

    public const MAX_IDLE_DAYS = 30;

    /** Nombre maximal d'envois tentés pour un même rappel en cas d'échec du serveur d'e-mails. */
    public const MAX_ATTEMPTS = 3;

    /** Délai avant de retenter un envoi échoué. */
    public const RETRY_AFTER_MINUTES = 60;

    public function due(int $minutes = self::MIN_IDLE_MINUTES): Builder
    {
        return DemandDraft::query()
            ->whereNull('review_requested_at')
            ->where('last_activity_at', '<=', now()->subMinutes(max(self::MIN_IDLE_MINUTES, $minutes)))
            ->where('last_activity_at', '>=', now()->subDays(self::MAX_IDLE_DAYS))
            ->whereHas('user', fn ($query) => $query
                ->whereNotNull('email_verified_at')->where('is_active', true)
                ->where('draft_reminders_enabled', true)->where('email_notifications', true));
    }

    /**
     * Prépare les rappels dus puis envoie ceux qui sont en attente.
     *
     * @return array{sent: int, failed: int, cancelled: int}
     */
    public function run(int $minutes = self::MIN_IDLE_MINUTES, ?int $limit = null): array
    {
        $this->prepare($minutes, $limit);

        return $this->deliverPending($limit);
    }

    /** Crée au plus un rappel par brouillon dû et marque le brouillon comme traité. */
    public function prepare(int $minutes = self::MIN_IDLE_MINUTES, ?int $limit = null): int
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
                    'type' => self::TYPE,
                    'recipient_email' => $draft->user->email,
                    'recipient_name' => $draft->user->name,
                    'subject' => 'Votre demande Prokejem est presque terminée',
                    'eyebrow' => ($payload['category'] ?? $payload['main_category'] ?? 'Service').' · Demande en cours',
                    'headline' => 'Vous y étiez presque, '.$draft->user->name,
                    'body' => "Votre demande{$title} n’est pas encore publiée. Reprenez-la à l’étape {$draft->current_step} sans ressaisir les informations déjà enregistrées.",
                    'cta_label' => 'Terminer ma demande',
                    'cta_url' => route('demand.create', ['resume' => 1]),
                    'metadata' => ['demand_draft_id' => $draft->id, 'automatic' => true],
                    'status' => ManagedEmail::STATUS_PENDING,
                    'scheduled_for' => now(),
                ]);
                $draft->update(['review_requested_at' => now()]);

                return 1;
            });
        }

        return $count;
    }

    /**
     * Envoie les rappels en attente et retente, après une heure, ceux dont
     * l'envoi a échoué (trois tentatives au plus).
     *
     * @return array{sent: int, failed: int, cancelled: int}
     */
    public function deliverPending(?int $limit = null): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'cancelled' => 0];

        $candidates = ManagedEmail::query()
            ->where('type', self::TYPE)
            ->where(fn ($query) => $query->where('status', ManagedEmail::STATUS_PENDING)
                ->orWhere(fn ($failed) => $failed->where('status', ManagedEmail::STATUS_FAILED)
                    ->where('updated_at', '<=', now()->subMinutes(self::RETRY_AFTER_MINUTES))))
            ->select(['id', 'metadata'])
            ->lazyById(100)
            // Le compteur de tentatives vit dans `metadata` (texte JSON) : on le
            // lit en PHP, aucun opérateur JSON SQL (PostgreSQL le refuserait).
            ->filter(fn (ManagedEmail $email) => $this->attempts($email) < self::MAX_ATTEMPTS);
        if ($limit !== null) {
            $candidates = $candidates->take($limit);
        }

        foreach ($candidates as $candidate) {
            $outcome = $this->deliver($candidate->id);
            if ($outcome !== null) {
                $result[$outcome]++;
            }
        }

        return $result;
    }

    /** Le rappel peut-il encore partir ? Utilisé par l'envoi automatique et par la validation manuelle. */
    public function stillRelevant(ManagedEmail $email): bool
    {
        $recipient = $email->user;

        return $recipient instanceof User
            && $recipient->email === $email->recipient_email
            && $recipient->email_notifications && $recipient->draft_reminders_enabled
            && $recipient->email_verified_at && $recipient->is_active
            && DemandDraft::whereKey($email->metadata['demand_draft_id'] ?? 0)
                ->where('user_id', $recipient->id)
                ->where('last_activity_at', '<=', now()->subMinutes(self::MIN_IDLE_MINUTES))
                ->where('last_activity_at', '>=', now()->subDays(self::MAX_IDLE_DAYS))
                ->where('last_activity_at', '<=', $email->created_at)
                ->exists();
    }

    /** @return 'sent'|'failed'|'cancelled'|null */
    private function deliver(int $id): ?string
    {
        $email = DB::transaction(function () use ($id) {
            $email = ManagedEmail::lockForUpdate()->find($id);
            if (! $email || $email->type !== self::TYPE
                || ! in_array($email->status, [ManagedEmail::STATUS_PENDING, ManagedEmail::STATUS_FAILED], true)) {
                return null;
            }

            if (! $this->stillRelevant($email)) {
                $email->update([
                    'status' => ManagedEmail::STATUS_CANCELLED,
                    'metadata' => [...($email->metadata ?? []), 'cancelled_reason' => 'Brouillon repris, publié ou supprimé, ou rappels désactivés par le destinataire.'],
                ]);

                return false;
            }

            $email->update([
                'status' => ManagedEmail::STATUS_SENDING,
                'failure_message' => null,
                'metadata' => [...($email->metadata ?? []), 'automatic' => true, 'attempts' => $this->attempts($email) + 1],
            ]);

            return $email;
        });

        if ($email === null) {
            return null;
        }
        if ($email === false) {
            return 'cancelled';
        }

        try {
            Mail::to($email->recipient_email, $email->recipient_name)->send(new ManagedEmailMail($email));
            $email->update(['status' => ManagedEmail::STATUS_SENT, 'sent_at' => now()]);

            return 'sent';
        } catch (\Throwable $exception) {
            $email->update([
                'status' => ManagedEmail::STATUS_FAILED,
                'failure_message' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
            report($exception);

            return 'failed';
        }
    }

    private function attempts(ManagedEmail $email): int
    {
        return (int) ($email->metadata['attempts'] ?? 0);
    }
}

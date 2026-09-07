<?php

namespace App\Console\Commands;

use App\Models\ServiceOrder;
use App\Models\ServiceReminder;
use App\Notifications\ServiceReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendServiceReminders extends Command
{
    protected $signature = 'service-reminders:send';

    protected $description = 'Envoie les rappels de service explicitement programmés par les clients';

    public function handle(): int
    {
        $sent = 0;
        $failed = 0;

        ServiceReminder::query()
            ->where('is_active', true)
            ->where('next_reminder_at', '<=', now())
            ->lazyById(100)
            ->each(function (ServiceReminder $candidate) use (&$sent, &$failed): void {
                $id = $candidate->id;
                try {
                    $reminder = DB::transaction(function () use ($id) {
                        $reminder = ServiceReminder::with(['user', 'serviceOrder.ad'])
                            ->lockForUpdate()
                            ->find($id);

                        if (! $reminder || ! $reminder->is_active || $reminder->next_reminder_at->isFuture()) {
                            return null;
                        }

                        if (! $reminder->user || ! $reminder->serviceOrder?->ad
                            || $reminder->serviceOrder->status !== ServiceOrder::STATUS_COMPLETED
                            || $reminder->serviceOrder->buyer_id !== $reminder->user_id) {
                            $reminder->update(['is_active' => false]);

                            return null;
                        }

                        // Commit the in-app notification and its history atomically.
                        $reminder->user->notifyNow(new ServiceReminderNotification($reminder), ['database']);

                        $nextOccurrence = $reminder->nextOccurrence();
                        $reminder->forceFill([
                            'last_sent_at' => now(),
                            'last_email_status' => $reminder->send_email
                                ? ($reminder->user->email_notifications ? 'pending' : 'disabled')
                                : 'not_requested',
                            'reminders_sent_count' => $reminder->reminders_sent_count + 1,
                            'next_reminder_at' => $nextOccurrence ?? $reminder->next_reminder_at,
                            'is_active' => $nextOccurrence !== null,
                        ])->save();

                        return $reminder;
                    });

                    if (! $reminder) {
                        return;
                    }

                    $sent++;
                    if ($reminder->last_email_status === 'pending') {
                        try {
                            $reminder->user->notifyNow(new ServiceReminderNotification($reminder), ['mail']);
                            $reminder->update(['last_email_status' => 'sent']);
                        } catch (\Throwable $exception) {
                            // SMTP acceptance may be uncertain: no automatic retry
                            // that would duplicate the notification or email.
                            $reminder->update(['last_email_status' => 'failed']);
                            throw $exception;
                        }
                    }
                } catch (\Throwable $exception) {
                    $failed++;
                    Log::error('Échec de l’envoi d’un rappel de service.', [
                        'service_reminder_id' => $id,
                        'exception' => get_class($exception),
                    ]);
                }
            });

        $this->info("{$sent} notification(s) interne(s) créée(s), {$failed} échec(s).");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}

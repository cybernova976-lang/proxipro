<?php

namespace App\Jobs;

use App\Mail\ManagedEmailMail;
use App\Models\ManagedEmail;
use App\Support\ManagedEmailAudience;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Envoi d'un message d'un lot validé depuis le studio e-mail.
 *
 * Le lot est validé d'un seul geste, puis chaque message part à son tour, en
 * arrière-plan. Les préférences du destinataire sont relues au moment de
 * l'envoi : une désinscription faite entre-temps est respectée.
 */
class SendManagedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Un seul essai : un message ne doit jamais partir deux fois. */
    public int $tries = 1;

    public function __construct(public int $managedEmailId) {}

    public function handle(): void
    {
        $email = ManagedEmail::find($this->managedEmailId);
        if (! $email || $email->status !== ManagedEmail::STATUS_SENDING) {
            return;
        }

        if (! ManagedEmailAudience::stillReachable($email)) {
            $email->update([
                'status' => ManagedEmail::STATUS_CANCELLED,
                'failure_message' => 'Destinataire désinscrit ou compte inactif au moment de l’envoi : rien n’a été transmis.',
            ]);

            return;
        }

        try {
            Mail::to($email->recipient_email, $email->recipient_name)->send(new ManagedEmailMail($email));
            $email->update(['status' => ManagedEmail::STATUS_SENT, 'sent_at' => now()]);
        } catch (\Throwable $exception) {
            $email->update([
                'status' => ManagedEmail::STATUS_FAILED,
                'failure_message' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
            report($exception);
        }
    }
}

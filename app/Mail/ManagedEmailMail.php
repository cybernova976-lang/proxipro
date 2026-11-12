<?php

namespace App\Mail;

use App\Models\ManagedEmail;
use App\Services\DemandDraftReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ManagedEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ManagedEmail $managedEmail) {}

    public function build(): self
    {
        $unsubscribe = $this->unsubscribe();

        return $this->subject($this->managedEmail->subject)
            ->view('emails.managed.standard', [
                'email' => $this->managedEmail,
                'unsubscribeUrl' => $unsubscribe['url'] ?? null,
                'unsubscribeLabel' => $unsubscribe['label'] ?? null,
                'supportEmail' => config('mail.reply_to.address')
                    ?: config('mail.admin_email')
                    ?: config('mail.from.address'),
            ]);
    }

    /**
     * Désinscription en un clic (RFC 8058) : les messageries proposent alors
     * leur propre bouton « Se désabonner », qui envoie un POST vers ce lien signé.
     */
    public function headers(): Headers
    {
        $unsubscribe = $this->unsubscribe();

        return new Headers(text: $unsubscribe === null ? [] : [
            'List-Unsubscribe' => '<'.$unsubscribe['url'].'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    /** @return array{url: string, label: string}|null */
    private function unsubscribe(): ?array
    {
        $userId = $this->managedEmail->user_id;
        if (! $userId) {
            return null;
        }

        return match ($this->managedEmail->type) {
            'newsletter' => [
                'url' => URL::signedRoute('newsletter.unsubscribe', ['user' => $userId]),
                'label' => 'Me désinscrire des newsletters sans me connecter',
            ],
            DemandDraftReminderService::TYPE => [
                'url' => URL::signedRoute('draft-reminders.unsubscribe', ['user' => $userId]),
                'label' => 'Ne plus recevoir de rappels de demande inachevée',
            ],
            default => null,
        };
    }
}

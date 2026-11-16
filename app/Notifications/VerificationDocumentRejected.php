<?php

namespace App\Notifications;

use App\Models\IdentityVerification;
use App\Notifications\Concerns\RespectsEmailPreferences;
use App\Notifications\Concerns\SendsWebPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationDocumentRejected extends Notification
{
    use Queueable, RespectsEmailPreferences, SendsWebPushNotifications;

    protected $verification;
    protected $rejectedDocs;
    protected $adminMessage;

    public function __construct(IdentityVerification $verification, array $rejectedDocs, ?string $adminMessage = null)
    {
        $this->verification = $verification;
        $this->rejectedDocs = $rejectedDocs;
        $this->adminMessage = $adminMessage;
    }

    /** Réponse de l'administration : dans le compte, par e-mail et sur les appareils activés. */
    public function via($notifiable): array
    {
        return array_merge(['database'], $this->emailChannelsFor($notifiable), $this->webPushChannelsFor($notifiable));
    }

    /** L'e-mail invite à se connecter : le détail des documents reste dans le compte. */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre demande de vérification demande une correction — '.config('app.name', 'Prokejem'))
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Notre équipe a examiné votre demande de vérification : un ou plusieurs documents doivent être corrigés.')
            ->line('Connectez-vous à votre compte Prokejem pour voir les documents concernés et le message de notre équipe.')
            ->action('Corriger mes documents', route('verification.index'));
    }

    public function toArray($notifiable): array
    {
        $docLabels = collect($this->rejectedDocs)->pluck('label')->implode(', ');

        return [
            'type' => 'verification_rejected',
            'verification_id' => $this->verification->id,
            'title' => 'Document(s) de vérification rejeté(s)',
            'message' => 'Votre demande de vérification nécessite des corrections. Document(s) concerné(s) : ' . $docLabels,
            'admin_message' => $this->adminMessage,
            'rejected_documents' => $this->rejectedDocs,
            'icon' => 'fas fa-exclamation-triangle',
            'color' => '#ef4444',
            'action_url' => route('verification.index'),
            'action_label' => 'Corriger mes documents',
        ];
    }
}

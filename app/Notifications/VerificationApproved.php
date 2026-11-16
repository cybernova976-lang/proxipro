<?php

namespace App\Notifications;

use App\Models\IdentityVerification;
use App\Notifications\Concerns\RespectsEmailPreferences;
use App\Notifications\Concerns\SendsWebPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationApproved extends Notification
{
    use Queueable, RespectsEmailPreferences, SendsWebPushNotifications;

    protected $verification;

    public function __construct(IdentityVerification $verification)
    {
        $this->verification = $verification;
    }

    /** Réponse de l'administration : dans le compte, par e-mail et sur les appareils activés. */
    public function via($notifiable): array
    {
        return array_merge(['database'], $this->emailChannelsFor($notifiable), $this->webPushChannelsFor($notifiable));
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre profil est vérifié — '.config('app.name', 'Prokejem'))
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Votre demande de vérification a été acceptée : le badge « Profil vérifié » est maintenant affiché sur votre profil.')
            ->action('Voir mon profil', route('profile.show'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'verification_approved',
            'verification_id' => $this->verification->id,
            'title' => 'Profil vérifié avec succès !',
            'message' => 'Félicitations ! Votre profil a été vérifié. Le badge « Vérifié » est maintenant visible sur votre profil.',
            'icon' => 'fas fa-check-circle',
            'color' => '#10b981',
            'action_url' => route('profile.show'),
            'action_label' => 'Voir mon profil',
        ];
    }
}

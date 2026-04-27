<?php

namespace App\Notifications;

use App\Models\ReferralReward;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralRewardNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected ReferralReward $reward,
        protected string $recipientRole,
        protected string $counterpartName
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isReferrer = $this->recipientRole === 'referrer';
        $subject = $isReferrer
            ? 'Votre bonus de parrainage ProxiPro est disponible'
            : 'Votre bonus filleul ProxiPro est disponible';

        $message = $isReferrer
            ? $this->counterpartName . ' a finalisé son premier achat. Vous recevez ' . $this->reward->points . ' points de parrainage.'
            : 'Votre premier achat a été validé. Vous recevez ' . $this->reward->points . ' points grâce au parrainage de ' . $this->counterpartName . '.';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line($message)
            ->action('Voir mes points', route('points.dashboard'))
            ->line('Merci d\'utiliser ProxiPro.');
    }

    public function toArray(object $notifiable): array
    {
        $isReferrer = $this->recipientRole === 'referrer';

        return [
            'type' => 'referral_reward',
            'icon' => 'fas fa-user-friends',
            'color' => '#0ea5e9',
            'title' => $isReferrer ? 'Bonus de parrainage débloqué' : 'Bonus filleul débloqué',
            'message' => $isReferrer
                ? $this->counterpartName . ' a effectué son premier achat. +' . $this->reward->points . ' points.'
                : 'Votre premier achat a été validé grâce au code de ' . $this->counterpartName . '. +' . $this->reward->points . ' points.',
            'action_url' => route('points.dashboard'),
            'referral_reward_id' => $this->reward->id,
        ];
    }
}
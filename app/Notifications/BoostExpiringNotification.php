<?php

namespace App\Notifications;

use App\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BoostExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Ad $ad,
        protected string $type, // 'boost' or 'urgent'
        protected int $hoursLeft
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->type === 'urgent' ? 'mode Urgent' : 'boost';
        $icon = $this->type === 'urgent' ? '🔥' : '🚀';
        $timeText = $this->hoursLeft <= 24
            ? "moins de 24 heures"
            : "{$this->hoursLeft} heures";
        $supportEmail = config('mail.reply_to.address')
            ?: config('mail.admin_email')
            ?: config('mail.from.address')
            ?: 'support@proxipro.fr';

        return (new MailMessage)
            ->subject("{$icon} Votre {$label} expire bientôt — {$this->ad->title}")
            ->view('emails.notifications.boost-expiring', [
                'appName' => config('app.name', 'ProxiPro'),
                'supportEmail' => $supportEmail,
                'recipientName' => $notifiable->name,
                'label' => $label,
                'icon' => $icon,
                'timeText' => $timeText,
                'adTitle' => $this->ad->title,
                'renewUrl' => url(route('boost.show', $this->ad)),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $label = $this->type === 'urgent' ? 'mode Urgent' : 'boost';

        return [
            'title' => $this->type === 'urgent'
                ? "🔥 Mode Urgent expirant"
                : "🚀 Boost expirant",
            'message' => $this->hoursLeft <= 24
                ? "Le {$label} de « {$this->ad->title} » expire dans moins de 24h !"
                : "Le {$label} de « {$this->ad->title} » expire dans {$this->hoursLeft}h.",
            'icon' => $this->type === 'urgent' ? 'fas fa-fire' : 'fas fa-rocket',
            'color' => $this->type === 'urgent' ? '#ef4444' : '#f59e0b',
            'action_url' => route('boost.show', $this->ad),
            'ad_id' => $this->ad->id,
            'type' => $this->type,
            'hours_left' => $this->hoursLeft,
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdCandidatureNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Ad $ad,
        protected User $candidate,
        protected ?string $message = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $supportEmail = config('mail.reply_to.address')
            ?: config('mail.admin_email')
            ?: config('mail.from.address')
            ?: 'support@proxipro.fr';

        return (new MailMessage)
            ->subject("📩 Nouvelle candidature pour votre annonce : {$this->ad->title}")
            ->view('emails.notifications.ad-candidature', [
                'appName' => config('app.name', 'ProxiPro'),
                'supportEmail' => $supportEmail,
                'recipientName' => $notifiable->name,
                'candidateName' => $this->candidate->name,
                'candidateMessage' => $this->message,
                'adTitle' => $this->ad->title,
                'adUrl' => url(route('ads.show', $this->ad)),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ad_candidature',
            'title' => '📩 Nouvelle candidature',
            'message' => "{$this->candidate->name} est intéressé(e) par votre annonce « {$this->ad->title} »",
            'candidate_message' => $this->message,
            'icon' => 'fas fa-hand-paper',
            'color' => '#3b82f6',
            'action_url' => route('ads.show', $this->ad),
            'ad_id' => $this->ad->id,
            'candidate_id' => $this->candidate->id,
            'candidate_name' => $this->candidate->name,
            'candidate_avatar' => $this->candidate->avatar,
        ];
    }
}

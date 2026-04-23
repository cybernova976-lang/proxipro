<?php

namespace App\Notifications;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NewAdMatchingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    protected Ad $ad;
    protected User $publisher;

    public function __construct(Ad $ad, User $publisher)
    {
        $this->ad = $ad;
        $this->publisher = $publisher;
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->pro_notifications_email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $serviceType = $this->ad->service_type === 'demande' ? 'demande de service' : 'offre de service';
        $adUrl = url('/ads/' . $this->ad->id);
        $supportEmail = config('mail.reply_to.address')
            ?: config('mail.admin_email')
            ?: config('mail.from.address')
            ?: 'support@proxipro.fr';
        $budget = $this->ad->price
            ? number_format($this->ad->price, 0, ',', ' ') . ' EUR'
            : null;

        return (new MailMessage)
            ->subject('📌 Nouvelle ' . $serviceType . ' dans votre domaine — ' . $this->ad->category)
            ->view('emails.notifications.new-ad-matching', [
                'appName' => config('app.name', 'ProxiPro'),
                'supportEmail' => $supportEmail,
                'recipientName' => $notifiable->name,
                'serviceTypeLabel' => $serviceType,
                'adTitle' => $this->ad->title,
                'category' => $this->ad->category,
                'location' => $this->ad->location ?? 'Non précisé',
                'budget' => $budget,
                'publisherName' => $this->publisher->name,
                'adUrl' => $adUrl,
            ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('NewAdMatchingNotification permanently failed for ad #' . $this->ad->id, [
            'exception' => $exception->getMessage(),
            'publisher' => $this->publisher->id,
        ]);
    }

    public function toArray(object $notifiable): array
    {
        $serviceType = $this->ad->service_type === 'demande' ? 'demande' : 'offre';

        return [
            'type' => 'new_ad_matching',
            'icon' => 'fas fa-bullhorn',
            'color' => '#3a86ff',
            'title' => 'Nouvelle ' . $serviceType . ' : ' . $this->ad->category,
            'message' => $this->publisher->name . ' a publié « ' . \Illuminate\Support\Str::limit($this->ad->title, 60) . ' » à ' . ($this->ad->location ?? 'lieu non précisé'),
            'action_url' => '/ads/' . $this->ad->id,
            'ad_id' => $this->ad->id,
            'publisher_id' => $this->publisher->id,
        ];
    }
}

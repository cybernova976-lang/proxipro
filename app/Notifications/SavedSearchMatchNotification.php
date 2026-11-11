<?php

namespace App\Notifications;

use App\Notifications\Concerns\RespectsEmailPreferences;

use App\Models\Ad;
use App\Models\SavedSearch;
use App\Notifications\Concerns\SendsWebPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SavedSearchMatchNotification extends Notification
{
    use Queueable, SendsWebPushNotifications, RespectsEmailPreferences;

    public function __construct(
        protected SavedSearch $savedSearch,
        protected Ad $ad
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = array_merge(['database'], $this->emailChannelsFor($notifiable, (bool) $notifiable->pro_notifications_email));

        // Notification sur l'appareil, seulement si le serveur est configuré
        // et que le membre a enregistré au moins un appareil.
        return array_merge($channels, $this->webPushChannelsFor($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle annonce pour votre alerte Prokejem')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Une nouvelle annonce correspond a votre alerte "' . $this->savedSearch->name . '".')
            ->line('Categorie: ' . ($this->ad->category ?? 'Non precisee'))
            ->line('Lieu: ' . ($this->ad->location ?? 'Non precise'))
            ->action('Voir l\'annonce', route('ads.show', $this->ad->id))
            ->line('Vous pouvez gerer vos alertes depuis votre espace Prokejem.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'saved_search_match',
            'icon' => 'fas fa-bell',
            'color' => '#4f46e5',
            'title' => 'Nouvelle annonce pour votre alerte',
            'message' => '"' . $this->savedSearch->name . '" : ' . Str::limit($this->ad->title, 70),
            'action_url' => route('ads.show', $this->ad->id),
            'saved_search_id' => $this->savedSearch->id,
            'ad_id' => $this->ad->id,
        ];
    }
}

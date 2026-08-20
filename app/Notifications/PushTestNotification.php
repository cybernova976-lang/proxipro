<?php

namespace App\Notifications;

use App\Notifications\Concerns\SendsWebPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PushTestNotification extends Notification
{
    use Queueable, SendsWebPushNotifications;

    public function via(object $notifiable): array
    {
        return $this->webPushChannelsFor($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'push_test',
            'title' => 'Notifications Prokejem activées',
            'message' => 'Vous recevrez ici les nouveaux messages, propositions et demandes importantes.',
            'action_url' => route('settings.index').'#notifications',
        ];
    }
}

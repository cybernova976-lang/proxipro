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
            'title' => 'Test des notifications Prokejem',
            'message' => 'Le test a réussi. Les notifications de Prokejem arrivent bien sur cet appareil.',
            'action_url' => route('settings.index').'#notifications',
        ];
    }
}

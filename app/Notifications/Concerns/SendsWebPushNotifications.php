<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

trait SendsWebPushNotifications
{
    /**
     * Ajoute le canal Web Push uniquement lorsque le serveur et l'utilisateur
     * sont correctement configurés.
     *
     * @return array<int, class-string>
     */
    protected function webPushChannelsFor(object $notifiable): array
    {
        if (blank(config('webpush.vapid.public_key')) || blank(config('webpush.vapid.private_key'))) {
            return [];
        }

        if (! method_exists($notifiable, 'pushSubscriptions')) {
            return [];
        }

        return $notifiable->pushSubscriptions()->exists()
            ? [WebPushChannel::class]
            : [];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $content = $this->toArray($notifiable);
        $target = (string) ($content['action_url'] ?? '/');
        $targetUrl = Str::startsWith($target, ['http://', 'https://']) ? $target : url($target);

        $tagId = $content['conversation_id']
            ?? $content['proposal_id']
            ?? $content['service_order_id']
            ?? $content['ad_id']
            ?? $notification->id;

        return (new WebPushMessage)
            ->title((string) ($content['title'] ?? config('app.name', 'Prokejem')))
            ->body(Str::limit(strip_tags((string) ($content['message'] ?? 'Vous avez une nouvelle notification.')), 180))
            ->icon('/pwa/icon-192.png')
            ->badge('/pwa/icon-192.png')
            ->lang('fr')
            ->tag((string) ($content['type'] ?? 'prokejem').'-'.$tagId)
            ->vibrate([200, 100, 200])
            ->data([
                'url' => $targetUrl,
                'type' => $content['type'] ?? 'notification',
                'notification_id' => $notification->id,
            ])
            ->options([
                'TTL' => 3600,
                'urgency' => 'high',
            ]);
    }
}

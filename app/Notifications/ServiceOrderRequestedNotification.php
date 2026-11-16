<?php

namespace App\Notifications;

use App\Notifications\Concerns\RespectsEmailPreferences;

use App\Models\ServiceOrder;
use App\Notifications\Concerns\SendsWebPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceOrderRequestedNotification extends Notification
{
    use Queueable, SendsWebPushNotifications, RespectsEmailPreferences;

    public function __construct(protected ServiceOrder $serviceOrder) {}

    public function via(object $notifiable): array
    {
        return array_merge(['database'], $this->emailChannelsFor($notifiable), $this->webPushChannelsFor($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle commande sécurisée Prokejem')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($this->serviceOrder->buyer->name.' souhaite lancer une commande sécurisée pour votre annonce "'.$this->serviceOrder->ad->title.'".')
            ->line('Montant proposé : '.number_format((float) $this->serviceOrder->amount, 2, ',', ' ').' €')
            ->action('Voir mes commandes', route('service-orders.index'))
            ->line('Vous pourrez accepter ou refuser cette commande depuis votre espace.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'service_order_requested',
            'icon' => 'fas fa-shield-alt',
            'color' => '#0f766e',
            'title' => 'Nouvelle commande sécurisée',
            'message' => $this->serviceOrder->buyer->name.' a proposé une commande pour "'.$this->serviceOrder->ad->title.'".',
            'action_url' => route('service-orders.index'),
            'service_order_id' => $this->serviceOrder->id,
            'ad_id' => $this->serviceOrder->ad_id,
            'buyer_id' => $this->serviceOrder->buyer_id,
        ];
    }
}

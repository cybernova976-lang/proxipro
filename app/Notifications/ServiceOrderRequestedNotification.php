<?php

namespace App\Notifications;

use App\Models\ServiceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceOrderRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(protected ServiceOrder $serviceOrder)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle commande securisee ProxiPro')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line($this->serviceOrder->buyer->name . ' souhaite lancer une commande securisee pour votre annonce "' . $this->serviceOrder->ad->title . '".')
            ->line('Montant propose : ' . number_format((float) $this->serviceOrder->amount, 2, ',', ' ') . ' EUR')
            ->action('Voir mes commandes', route('service-orders.index'))
            ->line('Vous pourrez accepter ou refuser cette commande depuis votre espace.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'service_order_requested',
            'icon' => 'fas fa-shield-alt',
            'color' => '#0f766e',
            'title' => 'Nouvelle commande securisee',
            'message' => $this->serviceOrder->buyer->name . ' a propose une commande pour "' . $this->serviceOrder->ad->title . '".',
            'action_url' => route('service-orders.index'),
            'service_order_id' => $this->serviceOrder->id,
            'ad_id' => $this->serviceOrder->ad_id,
            'buyer_id' => $this->serviceOrder->buyer_id,
        ];
    }
}
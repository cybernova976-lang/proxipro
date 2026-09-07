<?php

namespace App\Notifications;

use App\Models\ServiceReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceReminderNotification extends Notification
{
    use Queueable;

    public function __construct(protected ServiceReminder $serviceReminder) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->serviceReminder->send_email && $notifiable->email_notifications) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Rappel de service Prokejem')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Vous aviez demandé un rappel pour le service « '.$this->serviceReminder->serviceOrder->ad->title.' ».')
            ->line('Ce rappel ne publie aucune annonce et ne déclenche aucun paiement.')
            ->action('Voir mes commandes', route('service-orders.index').'#order-'.$this->serviceReminder->service_order_id)
            ->line('Vous pouvez modifier, mettre en pause ou supprimer ce rappel depuis votre espace.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'service_reminder',
            'icon' => 'fas fa-calendar-check',
            'color' => '#2563eb',
            'title' => 'Rappel de service',
            'message' => 'Il est peut-être temps de renouveler le service « '.$this->serviceReminder->serviceOrder->ad->title.' ».',
            'action_url' => route('service-orders.index').'#order-'.$this->serviceReminder->service_order_id,
            'service_reminder_id' => $this->serviceReminder->id,
            'service_order_id' => $this->serviceReminder->service_order_id,
            'ad_id' => $this->serviceReminder->serviceOrder->ad_id,
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\ServiceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceOrderStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected ServiceOrder $serviceOrder,
        protected string $event,
        protected ?string $note = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $content = $this->content();

        $mail = (new MailMessage)
            ->subject($content['subject'])
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line($content['line'])
            ->action('Voir la commande', route('service-orders.index'));

        if ($this->note) {
            $mail->line('Motif / note: ' . $this->note);
        }

        return $mail->line('Commande: ' . $this->serviceOrder->order_number);
    }

    public function toArray(object $notifiable): array
    {
        $content = $this->content();

        return [
            'type' => 'service_order_' . $this->event,
            'icon' => $content['icon'],
            'color' => $content['color'],
            'title' => $content['title'],
            'message' => $content['line'],
            'note' => $this->note,
            'action_url' => route('service-orders.index'),
            'service_order_id' => $this->serviceOrder->id,
            'order_number' => $this->serviceOrder->order_number,
            'ad_id' => $this->serviceOrder->ad_id,
        ];
    }

    protected function content(): array
    {
        return match ($this->event) {
            'accepted' => [
                'subject' => 'Commande securisee acceptee',
                'title' => 'Commande acceptee',
                'line' => 'Votre commande pour "' . $this->serviceOrder->ad->title . '" a ete acceptee. Vous pouvez maintenant regler le paiement securise.',
                'icon' => 'fas fa-check-circle',
                'color' => '#15803d',
            ],
            'refused' => [
                'subject' => 'Commande securisee refusee',
                'title' => 'Commande refusee',
                'line' => 'Votre commande pour "' . $this->serviceOrder->ad->title . '" a ete refusee par le vendeur.',
                'icon' => 'fas fa-times-circle',
                'color' => '#b91c1c',
            ],
            'funded' => [
                'subject' => 'Paiement securise recu',
                'title' => 'Paiement securise recu',
                'line' => 'Le paiement Stripe de la commande "' . $this->serviceOrder->ad->title . '" a ete confirme et les fonds sont bloques en attente de liberation.',
                'icon' => 'fas fa-lock',
                'color' => '#1d4ed8',
            ],
            'released' => [
                'subject' => 'Fonds liberes',
                'title' => 'Fonds liberes',
                'line' => 'Les fonds de la commande "' . $this->serviceOrder->ad->title . '" ont ete liberes.',
                'icon' => 'fas fa-hand-holding-usd',
                'color' => '#0f766e',
            ],
            'refunded' => [
                'subject' => 'Commande remboursee',
                'title' => 'Commande remboursee',
                'line' => 'La commande "' . $this->serviceOrder->ad->title . '" a ete remboursee.',
                'icon' => 'fas fa-undo-alt',
                'color' => '#1d4ed8',
            ],
            'disputed' => [
                'subject' => 'Litige ouvert',
                'title' => 'Litige ouvert',
                'line' => 'Un litige a ete ouvert sur la commande "' . $this->serviceOrder->ad->title . '". Les fonds restent bloques.',
                'icon' => 'fas fa-exclamation-triangle',
                'color' => '#c2410c',
            ],
            default => [
                'subject' => 'Mise a jour de commande securisee',
                'title' => 'Commande mise a jour',
                'line' => 'La commande securisee "' . $this->serviceOrder->ad->title . '" a ete mise a jour.',
                'icon' => 'fas fa-shield-alt',
                'color' => '#0f766e',
            ],
        };
    }
}
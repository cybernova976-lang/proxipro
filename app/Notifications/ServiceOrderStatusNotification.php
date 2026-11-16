<?php

namespace App\Notifications;

use App\Notifications\Concerns\RespectsEmailPreferences;

use App\Models\ServiceOrder;
use App\Notifications\Concerns\SendsWebPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceOrderStatusNotification extends Notification
{
    use Queueable, SendsWebPushNotifications, RespectsEmailPreferences;

    public function __construct(
        protected ServiceOrder $serviceOrder,
        protected string $event,
        protected ?string $note = null,
    ) {}

    public function via(object $notifiable): array
    {
        return array_merge(['database'], $this->emailChannelsFor($notifiable), $this->webPushChannelsFor($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $content = $this->content();

        $mail = (new MailMessage)
            ->subject($content['subject'])
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($content['line'])
            ->action('Voir la commande', route('service-orders.index'));

        if ($this->note) {
            $mail->line('Motif / note: '.$this->note);
        }

        return $mail->line('Commande: '.$this->serviceOrder->order_number);
    }

    public function toArray(object $notifiable): array
    {
        $content = $this->content();

        return [
            'type' => 'service_order_'.$this->event,
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
                'subject' => 'Commande sécurisée acceptée',
                'title' => 'Commande acceptée',
                'line' => 'Votre commande pour "'.$this->serviceOrder->ad->title.'" a été acceptée. Vous pouvez maintenant régler le paiement sécurisé.',
                'icon' => 'fas fa-check-circle',
                'color' => '#15803d',
            ],
            'refused' => [
                'subject' => 'Commande sécurisée refusée',
                'title' => 'Commande refusée',
                'line' => 'Votre commande pour "'.$this->serviceOrder->ad->title.'" a été refusée par le vendeur.',
                'icon' => 'fas fa-times-circle',
                'color' => '#b91c1c',
            ],
            'funded' => [
                'subject' => 'Paiement sécurisé reçu',
                'title' => 'Paiement sécurisé reçu',
                'line' => 'Le paiement Stripe de la commande "'.$this->serviceOrder->ad->title.'" a été confirmé et les fonds sont bloqués en attente de libération.',
                'icon' => 'fas fa-lock',
                'color' => '#1d4ed8',
            ],
            'released' => [
                'subject' => 'Fonds libérés',
                'title' => 'Fonds libérés',
                'line' => 'Les fonds de la commande "'.$this->serviceOrder->ad->title.'" ont été libérés.',
                'icon' => 'fas fa-hand-holding-usd',
                'color' => '#0f766e',
            ],
            'refunded' => [
                'subject' => 'Commande remboursée',
                'title' => 'Commande remboursée',
                'line' => 'La commande "'.$this->serviceOrder->ad->title.'" a été remboursée.',
                'icon' => 'fas fa-undo-alt',
                'color' => '#1d4ed8',
            ],
            'disputed' => [
                'subject' => 'Litige ouvert',
                'title' => 'Litige ouvert',
                'line' => 'Un litige a été ouvert sur la commande "'.$this->serviceOrder->ad->title.'". Les fonds restent bloqués.',
                'icon' => 'fas fa-exclamation-triangle',
                'color' => '#c2410c',
            ],
            default => [
                'subject' => 'Mise à jour de commande sécurisée',
                'title' => 'Commande mise à jour',
                'line' => 'La commande sécurisée "'.$this->serviceOrder->ad->title.'" a été mise à jour.',
                'icon' => 'fas fa-shield-alt',
                'color' => '#0f766e',
            ],
        };
    }
}

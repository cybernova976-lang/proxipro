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
            ->action('Voir la commande', $content['url'] ?? route('service-orders.index'));

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
            'action_url' => $content['url'] ?? route('service-orders.index'),
            'service_order_id' => $this->serviceOrder->id,
            'order_number' => $this->serviceOrder->order_number,
            'ad_id' => $this->serviceOrder->ad_id,
        ];
    }

    protected function content(): array
    {
        return match ($this->event) {
            'accepted' => [
                'subject' => 'Commande acceptée',
                'title' => 'Commande acceptée',
                'line' => 'Votre commande pour "'.$this->serviceOrder->ad->title.'" a été acceptée. Vous pouvez maintenant payer en ligne.',
                'icon' => 'fas fa-check-circle',
                'color' => '#15803d',
            ],
            'refused' => [
                'subject' => 'Commande refusée',
                'title' => 'Commande refusée',
                'line' => 'Votre commande pour "'.$this->serviceOrder->ad->title.'" a été refusée par le prestataire.',
                'icon' => 'fas fa-times-circle',
                'color' => '#b91c1c',
            ],
            'funded' => [
                'subject' => 'Paiement sécurisé reçu',
                'title' => 'Paiement sécurisé reçu',
                'line' => 'Le client a payé la commande "'.$this->serviceOrder->ad->title.'". L’argent est conservé en sécurité : il vous sera versé quand le client aura confirmé la fin de la prestation.',
                'icon' => 'fas fa-lock',
                'color' => '#1d4ed8',
            ],
            'released' => [
                'subject' => 'Paiement versé au prestataire',
                'title' => 'Paiement versé au prestataire',
                'line' => 'La prestation "'.$this->serviceOrder->ad->title.'" est terminée : le paiement est versé au prestataire.',
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
                'subject' => 'Problème signalé sur une commande',
                'title' => 'Problème signalé',
                'line' => 'Le client a signalé un problème sur la commande "'.$this->serviceOrder->ad->title.'". Le paiement reste conservé en sécurité pendant que notre équipe examine la situation.',
                'icon' => 'fas fa-exclamation-triangle',
                'color' => '#c2410c',
            ],
            'canceled_by_buyer' => [
                'subject' => 'Commande annulée par le client',
                'title' => 'Commande annulée',
                'line' => 'Le client a annulé la commande "'.$this->serviceOrder->ad->title.'" avant de la payer. Aucun paiement n’a été encaissé.',
                'icon' => 'fas fa-ban',
                'color' => '#b91c1c',
            ],
            'canceled_by_seller' => [
                'subject' => 'Commande annulée par le prestataire',
                'title' => 'Commande annulée',
                'line' => 'Le prestataire a annulé la commande "'.$this->serviceOrder->ad->title.'". Vous n’avez rien payé : aucun montant ne vous sera prélevé.',
                'icon' => 'fas fa-ban',
                'color' => '#b91c1c',
            ],
            'release_reminder' => [
                'subject' => 'Votre prestation est-elle terminée ?',
                'title' => 'Votre prestation est-elle terminée ?',
                'line' => 'Vous avez payé la commande "'.$this->serviceOrder->ad->title.'". Si le travail est terminé, confirmez-le depuis vos commandes : '.($this->serviceOrder->seller?->name ?? 'le prestataire').' ne sera payé qu’à ce moment-là. En cas de problème, signalez-le au même endroit.',
                'icon' => 'fas fa-clipboard-check',
                'color' => '#4f46e5',
            ],
            'admin_decision_needed' => [
                'subject' => 'Commande payée sans confirmation du client : à vous de décider',
                'title' => 'Commande à trancher',
                'line' => 'Le client de la commande "'.$this->serviceOrder->ad->title.'" ('.$this->serviceOrder->order_number.') n’a ni confirmé la fin de la prestation ni signalé de problème, malgré tous les rappels. Le prestataire n’est pas payé. Décidez depuis l’administration : verser au prestataire ou rembourser le client.',
                'icon' => 'fas fa-gavel',
                'color' => '#c2410c',
                'url' => route('admin.service-orders.index', ['status' => 'unconfirmed']),
            ],
            'payout_setup_required' => [
                'subject' => 'Activez la réception de vos paiements',
                'title' => 'Un client attend pour vous payer',
                'line' => 'Un client veut régler la commande "'.$this->serviceOrder->ad->title.'", mais vous n’avez pas encore activé la réception de vos paiements. Activez-la depuis vos commandes : le client pourra payer aussitôt.',
                'icon' => 'fas fa-university',
                'color' => '#c2410c',
            ],
            default => [
                'subject' => 'Commande mise à jour',
                'title' => 'Commande mise à jour',
                'line' => 'La commande "'.$this->serviceOrder->ad->title.'" a été mise à jour.',
                'icon' => 'fas fa-shield-alt',
                'color' => '#0f766e',
            ],
        };
    }
}

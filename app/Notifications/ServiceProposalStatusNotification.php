<?php

namespace App\Notifications;

use App\Notifications\Concerns\RespectsEmailPreferences;

use App\Models\ServiceProposal;
use App\Notifications\Concerns\SendsWebPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceProposalStatusNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsWebPushNotifications, RespectsEmailPreferences;

    public function __construct(protected ServiceProposal $proposal)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (($notifiable->email_notifications ?? true) && filled($notifiable->email)) {
            $channels[] = 'mail';
        }

        return array_merge($channels, $this->webPushChannelsFor($notifiable));
    }

    public function viaConnections(): array
    {
        return [
            'database' => 'sync',
            'mail' => config('queue.default', 'database'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $accepted = $this->proposal->status === ServiceProposal::STATUS_ACCEPTED;

        return (new MailMessage)
            ->subject($accepted ? 'Votre proposition a été acceptée' : 'Mise à jour de votre proposition')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($accepted
                ? 'Votre proposition pour « '.$this->proposal->ad->title.' » a été acceptée. La commande sécurisée est maintenant en attente de paiement du client.'
                : 'Votre proposition pour « '.$this->proposal->ad->title.' » n’a pas été retenue.')
            ->action('Voir mes propositions', route('proposals.index'));
    }

    public function toArray(object $notifiable): array
    {
        $accepted = $this->proposal->status === ServiceProposal::STATUS_ACCEPTED;

        return [
            'type' => 'service_proposal_'.$this->proposal->status,
            'icon' => $accepted ? 'fas fa-check-circle' : 'fas fa-times-circle',
            'color' => $accepted ? '#15803d' : '#b91c1c',
            'title' => $accepted ? 'Proposition acceptée' : 'Proposition refusée',
            'message' => $accepted
                ? 'Votre proposition pour « '.$this->proposal->ad->title.' » a été acceptée.'
                : 'Votre proposition pour « '.$this->proposal->ad->title.' » n’a pas été retenue.',
            'action_url' => route('proposals.index'),
            'proposal_id' => $this->proposal->id,
            'service_order_id' => $this->proposal->service_order_id,
        ];
    }
}

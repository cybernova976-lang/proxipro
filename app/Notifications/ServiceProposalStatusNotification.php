<?php

namespace App\Notifications;

use App\Models\ServiceProposal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceProposalStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

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

        return $channels;
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
            ->subject($accepted ? 'Votre proposition a ete acceptee' : 'Mise a jour de votre proposition')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($accepted
                ? 'Votre proposition pour « '.$this->proposal->ad->title.' » a ete acceptee. La commande securisee est maintenant en attente de paiement du client.'
                : 'Votre proposition pour « '.$this->proposal->ad->title.' » n a pas ete retenue.')
            ->action('Voir mes propositions', route('proposals.index'));
    }

    public function toArray(object $notifiable): array
    {
        $accepted = $this->proposal->status === ServiceProposal::STATUS_ACCEPTED;

        return [
            'type' => 'service_proposal_'.$this->proposal->status,
            'icon' => $accepted ? 'fas fa-check-circle' : 'fas fa-times-circle',
            'color' => $accepted ? '#15803d' : '#b91c1c',
            'title' => $accepted ? 'Proposition acceptee' : 'Proposition refusee',
            'message' => $accepted
                ? 'Votre proposition pour « '.$this->proposal->ad->title.' » a ete acceptee.'
                : 'Votre proposition pour « '.$this->proposal->ad->title.' » n a pas ete retenue.',
            'action_url' => route('proposals.index'),
            'proposal_id' => $this->proposal->id,
            'service_order_id' => $this->proposal->service_order_id,
        ];
    }
}

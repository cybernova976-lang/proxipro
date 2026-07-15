<?php

namespace App\Notifications;

use App\Models\ServiceProposal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceProposalReceivedNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Nouvelle proposition pour votre demande')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($this->proposal->provider->name.' vous propose '.number_format((float) $this->proposal->amount, 2, ',', ' ').' EUR pour « '.$this->proposal->ad->title.' ».')
            ->action('Comparer mes propositions', route('proposals.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'service_proposal_received',
            'icon' => 'fas fa-file-signature',
            'color' => '#2563eb',
            'title' => 'Nouvelle proposition',
            'message' => $this->proposal->provider->name.' a envoye une proposition pour « '.$this->proposal->ad->title.' ».',
            'action_url' => route('proposals.index'),
            'proposal_id' => $this->proposal->id,
            'ad_id' => $this->proposal->ad_id,
        ];
    }
}

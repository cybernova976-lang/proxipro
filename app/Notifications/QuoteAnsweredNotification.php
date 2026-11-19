<?php

namespace App\Notifications;

use App\Models\ProQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Prévient le prestataire que son client a répondu en ligne à un devis. */
class QuoteAnsweredNotification extends Notification
{
    use Queueable;

    public function __construct(protected ProQuote $quote) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $accepted = $this->quote->status === 'accepted';
        $when = ($accepted ? $this->quote->accepted_at : $this->quote->refused_at)?->format('d/m/Y à H:i');

        $mail = (new MailMessage)
            ->subject('Devis '.$this->quote->quote_number.' '.($accepted ? 'accepté' : 'refusé').' par votre client')
            ->greeting('Bonjour,')
            ->line($this->quote->response_name.' a '.($accepted ? 'accepté' : 'refusé').' en ligne le devis '
                .$this->quote->quote_number.' (« '.$this->quote->subject.' »)'.($when ? ', le '.$when : '').'.')
            ->action('Voir le devis', route('pro.quotes.show', $this->quote->id));

        return $accepted
            ? $mail->line('Vous pouvez maintenant créer la facture depuis ce devis.')
            : $mail;
    }

    public function toArray(object $notifiable): array
    {
        $accepted = $this->quote->status === 'accepted';

        return [
            'type' => 'quote_answered',
            'icon' => $accepted ? 'fas fa-check-circle' : 'fas fa-times-circle',
            'color' => $accepted ? '#0b6e52' : '#b3321f',
            'title' => $accepted ? 'Devis accepté par votre client' : 'Devis refusé par votre client',
            'message' => $this->quote->response_name.' a '.($accepted ? 'accepté' : 'refusé').' en ligne le devis '
                .$this->quote->quote_number.' (« '.$this->quote->subject.' »).',
            'action_url' => route('pro.quotes.show', $this->quote->id),
            'quote_id' => $this->quote->id,
        ];
    }
}

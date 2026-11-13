<?php

namespace App\Notifications;

use App\Models\ProQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Informe le prestataire, dans la plateforme, que la relance de son devis est partie. */
class QuoteReminderSentNotification extends Notification
{
    use Queueable;

    public function __construct(protected ProQuote $quote) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'quote_reminder_sent',
            'icon' => 'fas fa-paper-plane',
            'color' => '#4f46e5',
            'title' => 'Relance de devis envoyée',
            'message' => 'Votre client a reçu un rappel pour le devis '.$this->quote->quote_number.' (« '.$this->quote->subject.' »).',
            'action_url' => route('pro.quotes.show', $this->quote->id),
            'quote_id' => $this->quote->id,
        ];
    }
}

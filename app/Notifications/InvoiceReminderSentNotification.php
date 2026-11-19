<?php

namespace App\Notifications;

use App\Models\ProInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Informe le prestataire, dans la plateforme, que le rappel de sa facture impayée est parti. */
class InvoiceReminderSentNotification extends Notification
{
    use Queueable;

    public function __construct(protected ProInvoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invoice_reminder_sent',
            'icon' => 'fas fa-paper-plane',
            'color' => '#4f46e5',
            'title' => 'Rappel de facture envoyé',
            'message' => 'Votre client a reçu un rappel pour la facture '.$this->invoice->invoice_number.' (« '.$this->invoice->subject.' »).',
            'action_url' => route('pro.invoices.show', $this->invoice->id),
            'invoice_id' => $this->invoice->id,
        ];
    }
}

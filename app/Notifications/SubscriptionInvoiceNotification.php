<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Facture d'un paiement d'abonnement, envoyée au membre dès que le paiement
 * est reçu. La facture est celle émise par Stripe : elle est jointe en PDF
 * quand elle a pu être récupérée, et reste téléchargeable depuis la page
 * « Facturation ». C'est un document de facturation, pas une notification
 * de confort : l'e-mail part même si le membre a coupé les autres e-mails.
 */
class SubscriptionInvoiceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Une facture d'abonnement pèse quelques dizaines de Ko : au-delà, on ne joint pas. */
    private const MAX_PDF_BYTES = 5 * 1024 * 1024;

    public function __construct(
        public readonly int $transactionId,
        public readonly ?string $invoiceNumber = null,
        public readonly ?string $pdfUrl = null,
    ) {}

    /** Envoi qui ne doit jamais faire échouer l'enregistrement d'un paiement. */
    public static function dispatch(?object $user, Transaction $transaction, ?string $invoiceNumber, ?string $pdfUrl): void
    {
        if (! $user || (float) $transaction->amount <= 0) {
            return;
        }

        try {
            $user->notify(new self($transaction->id, $invoiceNumber, $pdfUrl));
        } catch (\Throwable $error) {
            Log::error('Facture d’abonnement non envoyée', [
                'user_id' => $user->id ?? null, 'transaction_id' => $transaction->id, 'exception' => $error->getMessage(),
            ]);
        }
    }

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $transaction = Transaction::find($this->transactionId);
        $amount = number_format((float) ($transaction?->amount ?? 0), 2, ',', ' ').' €';
        $date = ($transaction?->created_at ?? now())->format('d/m/Y');
        $label = $this->invoiceNumber ? 'Votre facture n° '.$this->invoiceNumber : 'Votre facture';
        $pdf = $this->downloadPdf();

        $mail = (new MailMessage)
            ->subject('Votre facture — '.config('app.name', 'Prokejem'))
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Nous avons bien reçu votre paiement de '.$amount.' le '.$date.' pour votre abonnement Premium.')
            ->line($pdf !== null
                ? $label.' est jointe à cet e-mail.'
                : $label.' est disponible dans votre compte.')
            ->action('Télécharger ma facture', route('billing.invoice', $this->transactionId))
            ->line('Vous retrouvez toutes vos factures à tout moment dans votre compte, page « Facturation ».');

        if ($pdf !== null) {
            $mail->attachData($pdf, 'Facture-Prokejem-'.($this->invoiceNumber ?: $this->transactionId).'.pdf', ['mime' => 'application/pdf']);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_invoice',
            'title' => 'Votre facture est disponible',
            'message' => 'Votre paiement d’abonnement est reçu. Vous pouvez télécharger votre facture.',
            'icon' => 'fas fa-file-invoice',
            'color' => '#4f46e5',
            'action_url' => route('billing.index'),
        ];
    }

    /** Le PDF de Stripe, ou null : l'e-mail part alors avec le seul lien de téléchargement. */
    private function downloadPdf(): ?string
    {
        if (! is_string($this->pdfUrl) || ! str_starts_with($this->pdfUrl, 'https://')) {
            return null;
        }

        try {
            $response = Http::timeout(15)->get($this->pdfUrl);
            $body = $response->successful() ? $response->body() : '';

            return str_starts_with($body, '%PDF') && strlen($body) <= self::MAX_PDF_BYTES ? $body : null;
        } catch (\Throwable $error) {
            Log::warning('PDF de facture non récupéré', ['transaction_id' => $this->transactionId, 'exception' => $error->getMessage()]);

            return null;
        }
    }
}

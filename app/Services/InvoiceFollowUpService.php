<?php

namespace App\Services;

use App\Models\ProInvoice;
use App\Models\User;
use App\Notifications\InvoiceReminderSentNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Suivi des factures impayées.
 *
 * 1. Pour tous les comptes : une facture envoyée dont l'échéance est passée
 *    devient « En retard » toute seule.
 * 2. Avantage Premium, à la demande du prestataire pour cette facture : un
 *    rappel unique part au client 3 jours après l'échéance, à l'adresse où la
 *    facture a été envoyée.
 *
 * Jamais de rappel pour une facture payée ou annulée. Si le serveur d'e-mails
 * échoue, l'envoi est retenté aux passages suivants, mais plus après 30 jours
 * de retard : un rappel aussi tardif relève d'un échange direct.
 */
class InvoiceFollowUpService
{
    public const DELAY_DAYS = 3;

    public const GIVE_UP_AFTER_DAYS = 30;

    /** Factures envoyées dont l'échéance est dépassée : elles passent « En retard ». */
    public function markOverdue(): int
    {
        return ProInvoice::query()
            ->where('status', 'sent')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);
    }

    public function due(): Builder
    {
        return ProInvoice::query()
            ->whereIn('status', ['sent', 'overdue'])
            ->where('reminder_enabled', true)
            ->whereNull('reminder_sent_at')
            ->whereNotNull('sent_to_email')
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now()->subDays(self::DELAY_DAYS)->toDateString())
            ->where('due_date', '>=', now()->subDays(self::GIVE_UP_AFTER_DAYS)->toDateString());
    }

    /** Date prévue du rappel, ou null si aucun rappel ne partira. */
    public function plannedFor(ProInvoice $invoice): ?Carbon
    {
        if (! in_array($invoice->status, ['sent', 'overdue'], true) || ! $invoice->reminder_enabled
            || $invoice->reminder_sent_at || ! $invoice->sent_to_email || ! $invoice->due_date) {
            return null;
        }

        return $invoice->due_date->lessThan(now()->subDays(self::GIVE_UP_AFTER_DAYS)->startOfDay())
            ? null
            : Carbon::parse($invoice->due_date)->addDays(self::DELAY_DAYS);
    }

    /** Le rappel peut-il encore être demandé pour cette facture ? */
    public function canPlan(ProInvoice $invoice): bool
    {
        return in_array($invoice->status, ['sent', 'overdue'], true) && $invoice->sent_to_email
            && $invoice->due_date && ! $invoice->reminder_sent_at
            && ! $invoice->due_date->lessThan(now()->subDays(self::GIVE_UP_AFTER_DAYS)->startOfDay());
    }

    /** @return array{overdue: int, sent: int, failed: int} */
    public function run(): array
    {
        $result = ['overdue' => $this->markOverdue(), 'sent' => 0, 'failed' => 0];

        foreach ($this->due()->with('user')->lazyById(50) as $invoice) {
            $owner = $invoice->user;
            if (! $owner instanceof User || ! $owner->is_active || ! $owner->hasActiveProSubscription()) {
                continue;
            }

            try {
                $this->send($invoice, $owner);
                $invoice->update(['reminder_sent_at' => now()]);
                $owner->notify(new InvoiceReminderSentNotification($invoice));
                $result['sent']++;
            } catch (\Throwable $exception) {
                report($exception);
                $result['failed']++;
            }
        }

        return $result;
    }

    private function send(ProInvoice $invoice, User $owner): void
    {
        $sender = $owner->company_name ?: $owner->name;
        $message = 'Rappel : la facture '.$invoice->invoice_number.', d’un montant de '
            .number_format((float) $invoice->total, 2, ',', ' ').' € TTC, était à régler pour le '
            .$invoice->due_date->format('d/m/Y').'. Ceci est un rappel unique, envoyé automatiquement à la demande de '
            .$sender.'. Si votre règlement est déjà parti, vous pouvez ignorer ce message.';

        $pdf = Pdf::loadView('pro.invoice-pdf', ['user' => $owner, 'invoice' => $invoice]);
        $pdf->setPaper('a4');

        Mail::send('emails.invoice', ['invoice' => $invoice, 'user' => $owner, 'customMessage' => $message],
            function ($mail) use ($invoice, $owner, $sender, $pdf) {
                $mail->to($invoice->sent_to_email)
                    ->replyTo([$owner->email => $sender]) // tableau : remplace l’adresse de réponse globale au lieu de s’y ajouter
                    ->subject('Rappel — Facture '.$invoice->invoice_number.' - '.$sender)
                    ->attachData($pdf->output(), 'Facture_'.$invoice->invoice_number.'.pdf', ['mime' => 'application/pdf']);
            });
    }
}

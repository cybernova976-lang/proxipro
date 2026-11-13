<?php

namespace App\Services;

use App\Models\ProQuote;
use App\Models\User;
use App\Notifications\QuoteReminderSentNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

/**
 * Relance automatique d'un devis resté sans réponse (avantage Premium).
 *
 * Règles :
 * - le prestataire l'a demandée pour ce devis, et son abonnement est actif ;
 * - une seule relance par devis, 7 jours après l'envoi, à l'adresse où le
 *   devis a été envoyé ;
 * - jamais sur un devis accepté, refusé, expiré ou dont la validité est passée ;
 * - si le serveur d'e-mails échoue, la relance est retentée aux passages
 *   suivants, mais plus après 21 jours : une relance tardive n'a plus de sens.
 */
class QuoteReminderService
{
    public const DELAY_DAYS = 7;

    public const GIVE_UP_AFTER_DAYS = 21;

    public function due(): Builder
    {
        return ProQuote::query()
            ->where('status', 'sent')
            ->where('reminder_enabled', true)
            ->whereNull('reminder_sent_at')
            ->whereNotNull('sent_to_email')
            ->where('sent_at', '<=', now()->subDays(self::DELAY_DAYS))
            ->where('sent_at', '>=', now()->subDays(self::GIVE_UP_AFTER_DAYS))
            ->where(fn ($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>=', now()->toDateString()));
    }

    /** Date prévue de la relance, ou `null` si aucune relance ne partira. */
    public function plannedFor(ProQuote $quote): ?\Illuminate\Support\Carbon
    {
        if ($quote->status !== 'sent' || ! $quote->reminder_enabled || $quote->reminder_sent_at
            || ! $quote->sent_to_email || ! $quote->sent_at) {
            return null;
        }

        $planned = $quote->sent_at->copy()->addDays(self::DELAY_DAYS);
        $expired = $quote->valid_until && $quote->valid_until->copy()->endOfDay()->lessThan($planned);

        return $expired || $quote->sent_at->lessThan(now()->subDays(self::GIVE_UP_AFTER_DAYS)) ? null : $planned;
    }

    /** @return array{sent: int, failed: int} */
    public function run(): array
    {
        $result = ['sent' => 0, 'failed' => 0];

        foreach ($this->due()->with('user')->lazyById(50) as $quote) {
            $owner = $quote->user;
            if (! $owner instanceof User || ! $owner->is_active || ! $owner->hasActiveProSubscription()) {
                continue;
            }

            try {
                $this->send($quote, $owner);
                $quote->update(['reminder_sent_at' => now()]);
                $owner->notify(new QuoteReminderSentNotification($quote));
                $result['sent']++;
            } catch (\Throwable $exception) {
                report($exception);
                $result['failed']++;
            }
        }

        return $result;
    }

    private function send(ProQuote $quote, User $owner): void
    {
        $sender = $owner->company_name ?: $owner->name;
        $message = 'Rappel : ce devis vous a été envoyé le '.$quote->sent_at->format('d/m/Y').' et attend toujours votre réponse.'
            .($quote->valid_until ? ' Il reste valable jusqu’au '.$quote->valid_until->format('d/m/Y').'.' : '')
            .' Ceci est un rappel unique, envoyé automatiquement à la demande de '.$sender
            .'. Si vous avez déjà répondu, vous pouvez ignorer ce message.';

        $pdf = Pdf::loadView('pro.quote-pdf', ['user' => $owner, 'quote' => $quote]);
        $pdf->setPaper('a4');

        Mail::send('emails.quote', ['quote' => $quote, 'user' => $owner, 'customMessage' => $message],
            function ($mail) use ($quote, $owner, $sender, $pdf) {
                $mail->to($quote->sent_to_email)
                    ->replyTo([$owner->email => $sender]) // tableau : remplace l’adresse de réponse globale au lieu de s’y ajouter
                    ->subject('Rappel — Devis '.$quote->quote_number.' - '.$sender)
                    ->attachData($pdf->output(), 'Devis_'.$quote->quote_number.'.pdf', ['mime' => 'application/pdf']);
            });
    }
}

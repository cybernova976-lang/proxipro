<?php

namespace App\Services;

use App\Models\ProInvoice;
use App\Models\ProQuote;
use App\Models\User;
use App\Support\ProDocumentNumber;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Avoirs et factures d'acompte de l'espace Pro.
 *
 *  - Une facture émise ne se modifie ni ne se supprime : elle se corrige par un
 *    avoir, total ou partiel, qui porte son propre numéro et cite la facture.
 *  - Un acompte prévu au devis se facture à part ; la facture du devis déduit
 *    ensuite les acomptes déjà facturés et n'affiche que le reste à payer.
 *
 * Les refus métier sont des DomainException dont le message est affichable.
 */
class InvoiceAdjustments
{
    /**
     * Émet un avoir sur une facture. Sans montant, l'avoir annule tout ce qui
     * reste de la facture ; avec un montant HT, il n'en annule qu'une partie.
     */
    public function issueCreditNote(User $user, ProInvoice $invoice, string $reason, ?float $amountHt = null): ProInvoice
    {
        return DB::transaction(function () use ($user, $invoice, $reason, $amountHt) {
            /** @var ProInvoice $invoice */
            $invoice = ProInvoice::whereKey($invoice->id)->where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if (! $invoice->canBeCredited()) {
                throw new DomainException('Un avoir ne peut être émis que sur une facture émise, non annulée, dont il reste un montant à annuler.');
            }
            if ($balance = $this->balanceInvoiceDeducting($invoice)) {
                throw new DomainException('Cet acompte est déjà déduit de la facture '.$balance->invoice_number.' : émettez l’avoir sur cette facture.');
            }

            $rate = (float) $invoice->tax_rate;
            $remaining = $invoice->creditableTotal();

            if ($amountHt === null) {
                // Avoir total. Sans avoir antérieur, il reprend les lignes de la facture.
                $untouched = (float) $invoice->credited_total === 0.0;
                $total = $remaining;
                $subtotal = $untouched ? (float) $invoice->subtotal : round($remaining / (1 + $rate / 100), 2);
                $items = $untouched ? $invoice->items : [$this->line('Solde de la facture '.$invoice->invoice_number, $subtotal)];
            } else {
                $subtotal = round($amountHt, 2);
                $total = round($subtotal + round($subtotal * $rate / 100, 2), 2);
                if ($subtotal <= 0 || $total > $remaining + 0.001) {
                    throw new DomainException('Le montant de l’avoir dépasse ce qui reste à annuler sur cette facture ('
                        .number_format(round($remaining / (1 + $rate / 100), 2), 2, ',', ' ').' € HT).');
                }
                $items = [$this->line('Avoir partiel sur la facture '.$invoice->invoice_number, $subtotal)];
            }

            $creditNote = ProInvoice::create([
                'user_id' => $user->id,
                'pro_client_id' => $invoice->pro_client_id,
                'kind' => ProInvoice::KIND_CREDIT_NOTE,
                'parent_invoice_id' => $invoice->id,
                'invoice_number' => ProDocumentNumber::next($user->id, 'credit_note'),
                'client_name' => $invoice->client_name,
                'client_email' => $invoice->client_email,
                'client_phone' => $invoice->client_phone,
                'client_address' => $invoice->client_address,
                'client_company' => $invoice->client_company,
                'client_registration_number' => $invoice->client_registration_number,
                'client_vat_number' => $invoice->client_vat_number,
                'client_type' => $invoice->client_type,
                'subject' => Str::limit('Avoir sur la facture '.$invoice->invoice_number.' — '.$invoice->subject, 250, '…'),
                'credit_reason' => $reason,
                'operation_type' => $invoice->operation_type,
                'service_date' => $invoice->service_date,
                'currency' => $invoice->currency ?: 'EUR',
                'items' => $items,
                'subtotal' => $subtotal,
                'tax_rate' => $rate,
                'tax_amount' => round($total - $subtotal, 2),
                'total' => $total,
                'vat_exemption_reason' => $invoice->vat_exemption_reason,
                'status' => ProInvoice::STATUS_ISSUED,
                'seller_snapshot' => $user->commercialIdentitySnapshot(),
                'finalized_at' => now(),
            ]);

            $credited = round((float) $invoice->credited_total + $total, 2);
            $fullyCredited = $credited >= round((float) $invoice->total, 2);
            $wasPaid = $invoice->status === 'paid';
            $invoice->update([
                'credited_total' => $credited,
                // Une facture impayée entièrement annulée n'est plus due : plus de rappel.
                'status' => $fullyCredited && ! $wasPaid ? 'cancelled' : $invoice->status,
                'reminder_enabled' => $fullyCredited ? false : $invoice->reminder_enabled,
            ]);

            // Le montant encaissé auprès de ce client diminue de l'avoir.
            if ($wasPaid && $invoice->pro_client_id && ($client = $user->proClients()->find($invoice->pro_client_id))) {
                $client->update(['total_revenue' => max(0, round((float) $client->total_revenue - $total, 2))]);
            }

            return $creditNote;
        });
    }

    /**
     * Prépare le brouillon de la facture d'acompte d'un devis accepté. Le
     * prestataire le relit sur l'écran de modification avant de l'émettre.
     */
    public function draftDepositInvoice(User $user, ProQuote $quote): ProInvoice
    {
        $percentage = (float) $quote->deposit_percentage;

        if ($quote->status !== 'accepted') {
            throw new DomainException('La facture d’acompte se crée à partir d’un devis accepté.');
        }
        if ($percentage <= 0 || $percentage >= 100) {
            throw new DomainException('Ce devis ne prévoit pas d’acompte : indiquez un pourcentage d’acompte sur le devis avant son envoi.');
        }
        if ($quote->depositInvoices()->exists()) {
            throw new DomainException('Une facture d’acompte existe déjà pour ce devis.');
        }
        if ($quote->invoice()->exists()) {
            throw new DomainException('La facture de ce devis existe déjà : l’acompte ne peut plus être facturé à part.');
        }

        $subtotal = round((float) $quote->subtotal * $percentage / 100, 2);
        $rate = (float) $quote->tax_rate;
        $tax = round($subtotal * $rate / 100, 2);
        $business = filled($quote->client_company);

        return ProInvoice::create([
            'user_id' => $user->id,
            'pro_client_id' => $quote->pro_client_id,
            'quote_id' => $quote->id,
            'kind' => ProInvoice::KIND_DEPOSIT,
            'deposit_percentage' => $percentage,
            'invoice_number' => 'BROUILLON-FAC-'.strtoupper(Str::random(10)),
            'client_name' => $quote->client_name,
            'client_email' => $quote->client_email,
            'client_phone' => $quote->client_phone,
            'client_address' => $quote->client_address,
            'client_company' => $quote->client_company,
            'client_registration_number' => $quote->client_registration_number,
            'client_vat_number' => $quote->client_vat_number,
            'client_type' => $business ? 'business' : 'individual',
            'subject' => Str::limit('Acompte — '.$quote->subject, 250, '…'),
            'operation_type' => $quote->operation_type ?: 'services',
            'service_date' => now(),
            'currency' => 'EUR',
            'items' => [$this->line('Acompte de '.$this->percent($percentage).' % sur le devis '.$quote->quote_number
                .($quote->issued_at ? ' du '.$quote->issued_at->format('d/m/Y') : ''), $subtotal)],
            'subtotal' => $subtotal,
            'tax_rate' => $rate,
            'tax_amount' => $tax,
            'total' => round($subtotal + $tax, 2),
            'status' => 'draft',
            // Mêmes valeurs de départ que l'écran de création : elles se relisent avant l'émission.
            'payment_terms' => 'Acompte payable à la commande.',
            'early_payment_discount' => 'Néant',
            'late_penalty_rate' => $business ? 12 : null,
            'vat_exemption_reason' => $rate === 0.0 ? 'TVA non applicable, art. 293 B du CGI' : null,
        ]);
    }

    /**
     * Acomptes émis à déduire de la facture d'un devis : factures d'acompte du
     * même devis, émises et non annulées, pour leur montant net d'avoir.
     *
     * @return Collection<int, ProInvoice>
     */
    public function depositsFor(ProInvoice $invoice): Collection
    {
        if ($invoice->kind !== ProInvoice::KIND_INVOICE || ! $invoice->quote_id) {
            return collect();
        }

        return ProInvoice::query()->where('user_id', $invoice->user_id)->where('quote_id', $invoice->quote_id)
            ->where('kind', ProInvoice::KIND_DEPOSIT)->whereIn('status', ['sent', 'overdue', 'paid'])
            ->orderBy('finalized_at')->get();
    }

    /** Total TTC des acomptes à déduire, plafonné au total de la facture. */
    public function deductibleDeposits(ProInvoice $invoice): float
    {
        $deposits = $this->depositsFor($invoice)->sum(fn (ProInvoice $deposit) => $deposit->creditableTotal());

        return round(min((float) $invoice->total, (float) $deposits), 2);
    }

    /** Facture émise qui a déjà déduit cet acompte, s'il y en a une. */
    public function balanceInvoiceDeducting(ProInvoice $deposit): ?ProInvoice
    {
        if (! $deposit->isDeposit() || ! $deposit->quote_id) {
            return null;
        }

        return ProInvoice::query()->where('user_id', $deposit->user_id)->where('quote_id', $deposit->quote_id)
            ->where('kind', ProInvoice::KIND_INVOICE)->whereNotNull('finalized_at')
            ->where('status', '!=', 'cancelled')->where('deposit_deducted', '>', 0)->first();
    }

    /**
     * Lignes à afficher sous le « Total TTC » d'une facture : acomptes déjà
     * facturés, avoirs émis, puis le net. Null s'il n'y a rien à déduire.
     *
     * @return array{lines: list<array{label: string, amount: float}>, net: float, net_label: string}|null
     */
    public function deductionLines(ProInvoice $invoice): ?array
    {
        if ($invoice->isCreditNote()) {
            return null;
        }

        $lines = [];
        // Brouillon : acomptes qui seront déduits à l'émission. Facture émise : montant figé.
        $deposits = $invoice->isIssued() ? (float) $invoice->deposit_deducted : $this->deductibleDeposits($invoice);
        if ($deposits > 0) {
            $numbers = $this->depositsFor($invoice)
                ->map(fn (ProInvoice $deposit) => $deposit->invoice_number.' du '.$deposit->finalized_at->format('d/m/Y'))->implode(', ');
            $lines[] = ['label' => 'Acompte déjà facturé'.($numbers !== '' ? ' ('.$numbers.')' : ''), 'amount' => -$deposits];
        }
        foreach ($invoice->creditNotes()->orderBy('finalized_at')->get() as $creditNote) {
            $lines[] = [
                'label' => 'Avoir '.$creditNote->invoice_number.' du '.$creditNote->finalized_at->format('d/m/Y'),
                'amount' => -(float) $creditNote->total,
            ];
        }
        if ($lines === []) {
            return null;
        }

        return [
            'lines' => $lines,
            'net' => round((float) $invoice->total - (float) $invoice->credited_total - $deposits, 2),
            'net_label' => $invoice->status === 'paid' ? 'Net réglé' : 'Net à payer',
        ];
    }

    private function line(string $description, float $amount): array
    {
        return ['description' => $description, 'quantity' => 1, 'unit_price' => $amount, 'total' => $amount];
    }

    private function percent(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ''), '0'), ',');
    }
}

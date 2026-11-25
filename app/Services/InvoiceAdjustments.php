<?php

namespace App\Services;

use App\Models\ProInvoice;
use App\Models\ProQuote;
use App\Models\User;
use App\Support\DocumentTotals;
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
            // Ce qui reste à annuler, hors taxes, pour chaque taux de TVA de la facture.
            $bases = $this->remainingBases($invoice);
            $remainingHt = round(array_sum($bases), 2);

            if ($amountHt === null) {
                // Avoir total. Sans avoir antérieur, il reprend les lignes de la facture.
                $untouched = (float) $invoice->credited_total === 0.0;
                $total = $remaining;
                $items = $untouched ? $invoice->items
                    : $this->linesByRate('Solde de la facture '.$invoice->invoice_number, $bases, $rate);
                $subtotal = $untouched ? (float) $invoice->subtotal : round(array_sum(array_column($items, 'total')), 2);
            } else {
                $subtotal = round($amountHt, 2);
                if ($subtotal <= 0 || $subtotal > $remainingHt + 0.001) {
                    throw new DomainException('Le montant de l’avoir dépasse ce qui reste à annuler sur cette facture ('
                        .number_format($remainingHt, 2, ',', ' ').' € HT).');
                }
                // Avoir partiel : réparti entre les taux au prorata de ce qui reste sur chacun.
                $items = $this->linesByRate('Avoir partiel sur la facture '.$invoice->invoice_number,
                    $this->share($bases, $subtotal), $rate);
                $totals = DocumentTotals::compute($items, $rate);
                $items = $totals['items'];
                // Un avoir ne dépasse jamais le reste de la facture, même d'un centime d'arrondi.
                $total = min($totals['total'], $remaining);
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
                // Un avoir suit la règle du filigrane « version gratuite » : émis sous Premium, il reste propre.
            ] + app(CommercialDocumentAllowance::class)->issueStamp($user));

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

        // Un acompte par taux de TVA du devis, pour que la facture d'acompte détaille la TVA comme lui.
        $rate = (float) $quote->tax_rate;
        $label = 'Acompte de '.$this->percent($percentage).' % sur le devis '.$quote->quote_number
            .($quote->issued_at ? ' du '.$quote->issued_at->format('d/m/Y') : '');
        $bases = [];
        foreach (DocumentTotals::breakdown($quote->items, $rate) as $group) {
            $bases[number_format($group['rate'], 2, '.', '')] = round($group['base'] * $percentage / 100, 2);
        }
        $totals = DocumentTotals::compute($this->linesByRate($label, $bases, $rate), $rate);
        $subtotal = $totals['subtotal'];
        $tax = $totals['tax_amount'];
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
            'items' => $totals['items'],
            'subtotal' => $subtotal,
            'tax_rate' => $rate,
            'tax_amount' => $tax,
            'total' => round($subtotal + $tax, 2),
            'status' => 'draft',
            // Mêmes valeurs de départ que l'écran de création : elles se relisent avant l'émission.
            'payment_terms' => 'Acompte payable à la commande.',
            'early_payment_discount' => 'Néant',
            'late_penalty_rate' => $business ? 12 : null,
            'vat_exemption_reason' => DocumentTotals::hasZeroRate($totals['items'], $rate) ? 'TVA non applicable, art. 293 B du CGI' : null,
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

    /**
     * Montant HT restant à annuler sur une facture, par taux de TVA : ses
     * bases, moins celles des avoirs déjà émis.
     *
     * @return array<string, float> taux (« 20.00 ») => montant HT
     */
    private function remainingBases(ProInvoice $invoice): array
    {
        $rate = (float) $invoice->tax_rate;
        $bases = [];
        foreach (DocumentTotals::breakdown($invoice->items, $rate) as $group) {
            $bases[number_format($group['rate'], 2, '.', '')] = $group['base'];
        }
        foreach ($invoice->creditNotes()->get() as $creditNote) {
            foreach (DocumentTotals::breakdown($creditNote->items, (float) $creditNote->tax_rate) as $group) {
                $key = number_format($group['rate'], 2, '.', '');
                $bases[$key] = round(($bases[$key] ?? 0) - $group['base'], 2);
            }
        }

        return array_filter($bases, fn (float $base) => $base > 0);
    }

    /**
     * Répartit un montant HT entre les taux, au prorata des bases ; le dernier
     * taux porte l'arrondi, pour que la somme soit exactement le montant.
     *
     * @param  array<string, float>  $bases
     * @return array<string, float>
     */
    private function share(array $bases, float $amount): array
    {
        $whole = array_sum($bases);
        $shares = [];
        $given = 0.0;
        $last = array_key_last($bases);
        foreach ($bases as $rate => $base) {
            $part = $rate === $last ? round($amount - $given, 2) : round($amount * $base / $whole, 2);
            $given += $part;
            if ($part > 0) {
                $shares[$rate] = $part;
            }
        }

        return $shares;
    }

    /**
     * Une ligne par taux de TVA. Avec un seul taux, la ligne porte le libellé
     * seul ; avec plusieurs, chaque ligne précise son taux.
     *
     * @param  array<string, float>  $bases
     * @return list<array<string, mixed>>
     */
    private function linesByRate(string $label, array $bases, float $defaultRate): array
    {
        $lines = [];
        foreach ($bases as $rate => $base) {
            $line = $this->line($label.(count($bases) > 1 ? ' — part à TVA '.DocumentTotals::formatRate((float) $rate).' %' : ''), $base);
            if (round((float) $rate, 2) !== round($defaultRate, 2)) {
                $line['tax_rate'] = (float) $rate;
            }
            $lines[] = $line;
        }

        return $lines;
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

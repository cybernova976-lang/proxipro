@php
    // Avoirs et acomptes liés à ce document, et émission d'un avoir.
    $pkCredits = $invoice->isCreditNote() ? collect() : $invoice->creditNotes()->orderBy('finalized_at')->get();
    $pkDeposits = app(\App\Services\InvoiceAdjustments::class)->depositsFor($invoice);
    $pkParent = $invoice->isCreditNote() ? $invoice->parentInvoice : null;
    $pkCanCredit = $invoice->canBeCredited();
    $pkRemaining = $invoice->creditableTotal();
@endphp
@if($pkParent || $pkCredits->isNotEmpty() || $pkDeposits->isNotEmpty() || $pkCanCredit)
<link rel="stylesheet" href="{{ asset('css/pro-premium.css') }}?v={{ @filemtime(public_path('css/pro-premium.css')) }}">
<section class="pk-adjust mt-3" aria-labelledby="pkAdjustTitle" data-invoice-adjustments>
    <h2 id="pkAdjustTitle" class="pk-adjust__title">Documents liés</h2>

    @if($pkParent)
        <p class="pk-adjust__line">Cet avoir corrige la facture
            <a href="{{ route('pro.invoices.show', $pkParent->id) }}">{{ $pkParent->invoice_number }}</a>
            du {{ ($pkParent->finalized_at ?? $pkParent->created_at)->format('d/m/Y') }}.
            @if($invoice->credit_reason) Motif : {{ $invoice->credit_reason }}@endif
        </p>
    @endif

    @foreach($pkDeposits as $pkDeposit)
        <p class="pk-adjust__line">Acompte déjà facturé :
            <a href="{{ route('pro.invoices.show', $pkDeposit->id) }}">{{ $pkDeposit->invoice_number }}</a>
            — {{ number_format($pkDeposit->creditableTotal(), 2, ',', ' ') }} € TTC ({{ mb_strtolower($pkDeposit->getStatusLabel()) }}).
        </p>
    @endforeach

    @foreach($pkCredits as $pkCredit)
        <p class="pk-adjust__line">Avoir
            <a href="{{ route('pro.invoices.show', $pkCredit->id) }}">{{ $pkCredit->invoice_number }}</a>
            du {{ $pkCredit->finalized_at->format('d/m/Y') }} — {{ number_format($pkCredit->total, 2, ',', ' ') }} € TTC.
            @if($pkCredit->credit_reason) Motif : {{ $pkCredit->credit_reason }}@endif
        </p>
    @endforeach

    @if($pkCanCredit)
        <details class="pk-adjust__credit" @if($errors->hasAny(['reason', 'scope', 'amount_ht', 'confirm']) || session('error')) open @endif>
            <summary>Corriger ou annuler cette facture par un avoir</summary>
            <p class="pk-adjust__hint">Une facture émise ne se modifie pas : elle se corrige par un avoir, qui porte son propre numéro. Il reste {{ number_format($pkRemaining, 2, ',', ' ') }} € TTC à pouvoir annuler sur cette facture.</p>
            @if($errors->hasAny(['reason', 'scope', 'amount_ht', 'confirm']))
                <ul class="pk-adjust__errors" role="alert">
                    @foreach(['reason', 'scope', 'amount_ht', 'confirm'] as $pkField)
                        @error($pkField)<li>{{ $message }}</li>@enderror
                    @endforeach
                </ul>
            @endif
            <form method="POST" action="{{ route('pro.invoices.credit-note', $invoice->id) }}" class="pk-adjust__form">
                @csrf
                <label class="form-label fw-semibold" for="pkCreditReason">Motif de l’avoir *</label>
                <input type="text" name="reason" id="pkCreditReason" class="form-control pk-doc-input" maxlength="255" required value="{{ old('reason') }}" placeholder="Ex. : prestation annulée, erreur de prix, remise accordée">

                <div class="form-check mt-3">
                    <input class="form-check-input" type="radio" name="scope" id="pkCreditTotal" value="total" @checked(old('scope', 'total') === 'total')>
                    <label class="form-check-label" for="pkCreditTotal">Annuler tout ce qui reste : {{ number_format($pkRemaining, 2, ',', ' ') }} € TTC</label>
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="radio" name="scope" id="pkCreditPartial" value="partial" @checked(old('scope') === 'partial')>
                    <label class="form-check-label" for="pkCreditPartial">Annuler une partie seulement</label>
                </div>
                <label class="form-label mt-2" for="pkCreditAmount">Montant HT de l’avoir partiel ({{ \App\Support\DocumentTotals::hasSeveralRates($invoice->items, (float) $invoice->tax_rate) ? 'réparti entre les taux de TVA de la facture' : 'TVA de '.number_format($invoice->tax_rate, 2, ',', ' ').' % ajoutée' }})</label>
                <input type="number" name="amount_ht" id="pkCreditAmount" class="form-control pk-doc-input pk-adjust__amount" min="0.01" step="0.01" value="{{ old('amount_ht') }}">

                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="confirm" id="pkCreditConfirm" value="1" required>
                    <label class="form-check-label" for="pkCreditConfirm">Je confirme l’émission de cet avoir : il recevra un numéro définitif et ne pourra plus être modifié.</label>
                </div>
                @include('pro.partials.free-watermark-notice', ['documentLabel' => 'cet avoir', 'pkLine' => true])
                <button type="submit" class="btn btn-pro-outline mt-3">Émettre l’avoir</button>
            </form>
        </details>
    @endif
</section>
@endif

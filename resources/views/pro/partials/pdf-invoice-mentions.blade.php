@php
    // Mentions de bas de facture en PDF : paiement reçu, puis conditions de
    // règlement (ou, pour un avoir, son motif et sa portée). Toutes les mentions
    // viennent de la facture ; « autre » n'est pas un mode de paiement à afficher.
    // Paramètres : $invoice, $pkFirst (vrai quand le bloc ouvre la colonne à gauche
    // des totaux : il est alors court et ne se coupe pas entre deux pages).
    $pkFirst = $pkFirst ?? false;
    $pkExemption = \App\Support\DocumentTotals::hasZeroRate($invoice->items, (float) $invoice->tax_rate) ? $invoice->vat_exemption_reason : null;
    $pkMethods = ['virement' => 'Virement bancaire', 'cheque' => 'Chèque', 'especes' => 'Espèces', 'carte' => 'Carte bancaire', 'other' => null];
    $pkMethod = array_key_exists((string) $invoice->payment_method, $pkMethods)
        ? $pkMethods[(string) $invoice->payment_method]
        : ($invoice->payment_method ? ucfirst($invoice->payment_method) : null);
    // Une seule ponctuation finale, même si la saisie se termine déjà par un point.
    $pkTerms = rtrim((string) ($invoice->payment_terms ?: 'À convenir'), " .\t\n\r");
    $pkDiscount = rtrim((string) ($invoice->early_payment_discount ?: 'Néant'), " .\t\n\r");
@endphp
@if($invoice->paid_at)
<div class="pk-paid {{ $pkFirst ? '' : 'pk-paid-below' }}">
    <div class="pk-label">Paiement reçu</div>
    <div class="pk-paid-text">Payée le {{ $invoice->paid_at->format('d/m/Y') }}</div>
    @if($pkMethod)
    <div class="pk-paid-detail">Mode : {{ $pkMethod }}</div>
    @endif
</div>
@endif
<div class="pk-section {{ $pkFirst ? 'pk-keep' : '' }} {{ $pkFirst && ! $invoice->paid_at ? 'pk-section-first' : '' }}">
    @if($invoice->isCreditNote())
    <div class="pk-section-title">Motif et portée de l’avoir</div>
    <div class="pk-section-text">
        @if($pkExemption) <strong>{{ $pkExemption }}</strong><br> @endif
        <strong>Motif de l’avoir :</strong> {{ $invoice->credit_reason ?: 'Non précisé' }}<br>
        Le montant de cet avoir vient en déduction de la facture citée ci-dessus. S’il a déjà été réglé, il est à rembourser au client ou à déduire d’une prochaine facture.
    </div>
    @else
    <div class="pk-section-title">Conditions de règlement</div>
    <div class="pk-section-text">
        @if($pkExemption) <strong>{{ $pkExemption }}</strong><br> @endif
        <strong>Conditions de paiement :</strong> {{ $pkTerms }}. @if($invoice->due_date) Échéance au {{ $invoice->due_date->format('d/m/Y') }}. @endif
        @if($pkMethod && ! $invoice->paid_at)
        <br><strong>Mode de paiement :</strong> {{ $pkMethod }}.
        @endif
        <br><strong>Escompte pour paiement anticipé :</strong> {{ $pkDiscount }}.
        @if($invoice->client_type === 'business')
        <br><strong>Retard :</strong> pénalités au taux annuel de {{ number_format($invoice->late_penalty_rate ?? 0, 2, ',', ' ') }} % et indemnité forfaitaire de 40 € pour frais de recouvrement.
        @endif
    </div>
    @endif
</div>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->documentLabel() }} {{ $invoice->invoice_number }}</title>
    <style>
@include('pro.partials.pdf-styles')
    </style>
</head>
<body>
@php
    // Identité figée à l'émission ; à défaut (brouillon), celle du compte.
    $seller = $invoice->seller_snapshot ?: $user->commercialIdentitySnapshot();
    $pkDefaultRate = (float) ($invoice->tax_rate ?? 0);
    $pkSeveralRates = \App\Support\DocumentTotals::hasSeveralRates($invoice->items, $pkDefaultRate);
    $pkVatLines = \App\Support\DocumentTotals::vatLines($invoice->items, $pkDefaultRate, (float) $invoice->tax_amount);
    $pkDeductions = app(\App\Services\InvoiceAdjustments::class)->deductionLines($invoice);
    $pkTitle = mb_strtoupper($invoice->documentLabel());
    $pkNature = ['services' => 'Prestations de services', 'goods' => 'Vente de biens', 'mixed' => 'Biens et services'][$invoice->operation_type] ?? 'Prestation';
    $pkClientContact = implode(' · ', array_filter([$invoice->client_email, $invoice->client_phone]));
    // Mentions courtes : à gauche des totaux, pour tenir sur une page. Longues :
    // en pleine largeur sous les totaux, où elles peuvent changer de page.
    $pkMentions = $invoice->payment_terms.$invoice->early_payment_discount.$invoice->vat_exemption_reason.$invoice->credit_reason;
    $pkCompact = mb_strlen($pkMentions) <= 260;
    // Une note courte se range alors sous les totaux ; une note longue garde toute la largeur.
    $pkNotes = trim((string) $invoice->notes);
    $pkNotesAside = $pkCompact && $pkNotes !== '' && mb_strlen($pkNotes) <= 220 && preg_match_all('/\R/u', $pkNotes) <= 3;
@endphp
@include('pro.partials.pdf-furniture', ['pkOwner' => $user, 'pkDocument' => $invoice, 'pkSeller' => $seller, 'pkDraftNote' => 'Brouillon sans valeur de facture définitive'])

<table class="pk-head">
    <tr>
        <td class="pk-head-seller">
            @include('pro.partials.pdf-seller', ['pkSeller' => $seller, 'pkLogoOwner' => $user])
        </td>
        <td class="pk-head-doc">
            <div class="pk-doc-title {{ mb_strlen($pkTitle) > 8 ? 'pk-doc-title-long' : '' }}">{{ $pkTitle }}</div>
            <div class="pk-doc-number">N° {{ $invoice->invoice_number }}</div>
            <span class="pk-badge {{ $invoice->status === 'paid' ? 'pk-badge-strong' : '' }}">{{ $statusLabel ?? $invoice->getStatusLabel() }}</span>
        </td>
    </tr>
</table>
<div class="pk-rule"></div>

<table class="pk-band">
    <tr>
        <td class="pk-band-meta">
            <table class="pk-kv">
                <tr><td class="k">Date d’émission :</td><td class="v">{{ ($invoice->finalized_at ?? $invoice->created_at)->format('d/m/Y') }}</td></tr>
                @if($invoice->service_date)
                <tr><td class="k">Date de l’opération :</td><td class="v">{{ $invoice->service_date->format('d/m/Y') }}</td></tr>
                @endif
                @if($invoice->due_date)
                <tr><td class="k">Échéance :</td><td class="v">{{ $invoice->due_date->format('d/m/Y') }}</td></tr>
                @endif
                @if($invoice->quote)
                <tr><td class="k">Réf. devis :</td><td class="v">{{ $invoice->quote->quote_number }}</td></tr>
                @endif
                @if($invoice->isCreditNote() && $invoice->parentInvoice)
                <tr><td class="k">Avoir sur la facture :</td><td class="v">{{ $invoice->parentInvoice->invoice_number }} du {{ ($invoice->parentInvoice->finalized_at ?? $invoice->parentInvoice->created_at)->format('d/m/Y') }}</td></tr>
                @endif
                @if($invoice->purchase_order_number)
                <tr><td class="k">Bon de commande :</td><td class="v">{{ $invoice->purchase_order_number }}</td></tr>
                @endif
                <tr><td class="k">Nature :</td><td class="v">{{ $pkNature }}</td></tr>
            </table>
        </td>
        <td class="pk-band-client">
            <div class="pk-client">
                <div class="pk-label">Client</div>
                <div class="pk-client-name">{{ $invoice->client_name }}</div>
                <div class="pk-client-info">
                    @if($invoice->client_company) <strong>{{ $invoice->client_company }}</strong><br> @endif
                    @if($invoice->client_address) {{ $invoice->client_address }}<br> @endif
                    @if($invoice->delivery_address && $invoice->delivery_address !== $invoice->client_address) Adresse de livraison : {{ $invoice->delivery_address }}<br> @endif
                    @if($invoice->client_registration_number) Immatriculation : {{ $invoice->client_registration_number }}<br> @endif
                    @if($invoice->client_vat_number) TVA : {{ $invoice->client_vat_number }}<br> @endif
                    {{ $pkClientContact }}
                </div>
            </div>
        </td>
    </tr>
</table>

<div class="pk-subject">
    @if($invoice->subject && $invoice->subject !== 'Facture')
    <div class="pk-label">Objet</div>
    <div class="pk-subject-text">{{ $invoice->subject }}</div>
    @endif
</div>

@include('pro.partials.pdf-lines', ['pkItems' => $invoice->items, 'pkFirstColumn' => 'Désignation'])

<table class="pk-summary">
    <tr>
        <td class="pk-summary-left">
            @if($pkCompact)
            @include('pro.partials.pdf-invoice-mentions', ['pkFirst' => true])
            @endif
        </td>
        <td class="pk-summary-right">
            @include('pro.partials.pdf-totals', ['pkSubtotal' => $invoice->subtotal, 'pkTotalLabel' => $invoice->isCreditNote() ? 'Total TTC de l’avoir' : 'Total TTC', 'pkTotal' => $invoice->total])
            @if($pkNotesAside)
            <div class="pk-section">
                <div class="pk-section-title">Notes</div>
                <div class="pk-section-text pk-pre">{{ $pkNotes }}</div>
            </div>
            @endif
        </td>
    </tr>
</table>

@if(! $pkCompact)
@include('pro.partials.pdf-invoice-mentions', ['pkFirst' => false])
@endif

@if($pkNotes !== '' && ! $pkNotesAside)
<div class="pk-section {{ mb_strlen($pkNotes) <= 600 ? 'pk-keep' : '' }}">
    <div class="pk-section-title">Notes</div>
    <div class="pk-section-text pk-pre">{{ $pkNotes }}</div>
</div>
@endif
</body>
</html>

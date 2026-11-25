{{-- Corps du devis, commun au PDF (quote-pdf) et à l'aperçu de la fiche (quote-show, styles public/css/pro-document-sheet.css). --}}
@php
    // Identité figée à l'émission ; à défaut (brouillon), celle du compte.
    $seller = $quote->seller_snapshot ?: $user->commercialIdentitySnapshot();
    $pkDefaultRate = (float) ($quote->tax_rate ?? 0);
    $pkSeveralRates = \App\Support\DocumentTotals::hasSeveralRates($quote->items, $pkDefaultRate);
    $pkVatLines = \App\Support\DocumentTotals::vatLines($quote->items, $pkDefaultRate, (float) $quote->tax_amount);
    $pkNature = ['services' => 'Prestation de services', 'goods' => 'Vente de biens', 'mixed' => 'Biens et services'][$quote->operation_type] ?? 'Prestation de services';
    // Notes et conditions courtes : à gauche des totaux, pour tenir sur une page.
    // Longues : en pleine largeur sous les totaux, où elles peuvent changer de page.
    $pkTexts = trim((string) $quote->notes).PHP_EOL.trim((string) $quote->conditions);
    $pkCompact = mb_strlen($pkTexts) <= 420 && preg_match_all('/\R/u', $pkTexts) <= 6;
@endphp
@include('pro.partials.pdf-furniture', ['pkOwner' => $user, 'pkDocument' => $quote, 'pkSeller' => $seller, 'pkDraftNote' => 'Brouillon non contractuel'])

<table class="pk-head">
    <tr>
        <td class="pk-head-seller">
            @include('pro.partials.pdf-seller', ['pkSeller' => $seller, 'pkLogoOwner' => $user])
        </td>
        <td class="pk-head-doc">
            <div class="pk-doc-title">DEVIS</div>
            <div class="pk-doc-number">N° {{ $quote->quote_number }}</div>
            <span class="pk-badge {{ $quote->status === 'accepted' ? 'pk-badge-strong' : '' }}">{{ $quote->getStatusLabel() }}</span>
        </td>
    </tr>
</table>
<div class="pk-rule"></div>

<table class="pk-band">
    <tr>
        <td class="pk-band-meta">
            <table class="pk-kv">
                <tr><td class="k">Date :</td><td class="v">{{ $quote->created_at->format('d/m/Y') }}</td></tr>
                @if($quote->valid_until)
                <tr><td class="k">Valable jusqu’au :</td><td class="v">{{ $quote->valid_until->format('d/m/Y') }}</td></tr>
                @endif
                <tr><td class="k">Nature :</td><td class="v">{{ $pkNature }}</td></tr>
                @if($quote->execution_location)
                <tr><td class="k">Lieu :</td><td class="v">{{ $quote->execution_location }}</td></tr>
                @endif
                <tr><td class="k">Établissement du devis :</td><td class="v">{{ $quote->is_free ? 'gratuit' : 'payant' }}</td></tr>
                @if($quote->deposit_percentage !== null)
                <tr><td class="k">Acompte :</td><td class="v">{{ \App\Support\DocumentTotals::formatRate((float) $quote->deposit_percentage) }} %</td></tr>
                @endif
            </table>
        </td>
        <td class="pk-band-client">
            <div class="pk-client">
                <div class="pk-label">Destinataire</div>
                <div class="pk-client-name">{{ $quote->client_name }}</div>
                <div class="pk-client-info">
                    @if($quote->client_company) <strong>{{ $quote->client_company }}</strong><br> @endif
                    @if($quote->client_address) {{ $quote->client_address }}<br> @endif
                    @if($quote->client_registration_number) Immatriculation : {{ $quote->client_registration_number }}<br> @endif
                    @if($quote->client_vat_number) TVA : {{ $quote->client_vat_number }}<br> @endif
                    {{ implode(' · ', array_filter([$quote->client_email, $quote->client_phone])) }}
                </div>
            </div>
        </td>
    </tr>
</table>

<div class="pk-subject">
    <div class="pk-label">Objet</div>
    <div class="pk-subject-text">{{ $quote->subject }}</div>
</div>

@include('pro.partials.pdf-lines', ['pkItems' => $quote->items, 'pkFirstColumn' => 'Description'])

<table class="pk-summary">
    <tr>
        <td class="pk-summary-left">
            @if($pkCompact)
            @include('pro.partials.pdf-quote-texts', ['pkNotes' => $quote->notes, 'pkConditions' => $quote->conditions, 'pkFirst' => true])
            @endif
        </td>
        <td class="pk-summary-right">
            @include('pro.partials.pdf-totals', ['pkSubtotal' => $quote->subtotal, 'pkTotalLabel' => 'Total TTC', 'pkTotal' => $quote->total, 'pkDeductions' => null])
            @if($pkCompact)
            @include('pro.partials.pdf-quote-accord')
            @endif
        </td>
    </tr>
</table>

@if(! $pkCompact)
@include('pro.partials.pdf-quote-texts', ['pkNotes' => $quote->notes, 'pkConditions' => $quote->conditions, 'pkFirst' => false])
<div class="pk-half-right pk-keep">
    @include('pro.partials.pdf-quote-accord')
</div>
@endif

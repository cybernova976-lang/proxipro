<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Devis {{ $document->quote_number }}</title>
    <style>
@include('pro.partials.pdf-styles')
    </style>
</head>
<body>
@php
    // Devis créé avec l'ancien outil : l'identité est celle saisie à l'époque
    // ($emitter), présentée comme sur les devis de l'espace Pro.
    $seller = [
        'company_name' => ($emitter['company'] ?? null) ?: null,
        'name' => ($emitter['name'] ?? null) ?: $user->name,
        'address' => $emitter['address'] ?? null,
        'email' => $emitter['email'] ?? null,
        'phone' => $emitter['phone'] ?? null,
        'siret' => $emitter['siret'] ?? null,
    ];
    $pkDefaultRate = (float) ($document->tax_rate ?? 0);
    $pkSeveralRates = \App\Support\DocumentTotals::hasSeveralRates($document->items, $pkDefaultRate);
    $pkVatLines = \App\Support\DocumentTotals::vatLines($document->items, $pkDefaultRate, (float) $document->tax_amount);
    // Notes et conditions courtes : à gauche des totaux. Longues : en pleine largeur dessous.
    $pkTexts = trim((string) $document->notes).PHP_EOL.trim((string) $document->conditions);
    $pkCompact = mb_strlen($pkTexts) <= 420 && preg_match_all('/\R/u', $pkTexts) <= 6;
@endphp
@include('pro.partials.pdf-furniture', ['pkOwner' => $user, 'pkDocument' => $document, 'pkSeller' => $seller, 'pkDraftNote' => 'Brouillon non contractuel'])

<table class="pk-head">
    <tr>
        <td class="pk-head-seller">
            @include('pro.partials.pdf-seller', ['pkSeller' => $seller, 'pkLogoOwner' => $user])
        </td>
        <td class="pk-head-doc">
            <div class="pk-doc-title">DEVIS</div>
            <div class="pk-doc-number">N° {{ $document->quote_number }}</div>
            <span class="pk-badge {{ $document->status === 'accepted' ? 'pk-badge-strong' : '' }}">{{ method_exists($document, 'getStatusLabel') ? $document->getStatusLabel() : ucfirst($document->status) }}</span>
        </td>
    </tr>
</table>
<div class="pk-rule"></div>

<table class="pk-band">
    <tr>
        <td class="pk-band-meta">
            <table class="pk-kv">
                <tr><td class="k">Date :</td><td class="v">{{ $document->created_at->format('d/m/Y') }}</td></tr>
                @if($document->valid_until)
                <tr><td class="k">Valable jusqu’au :</td><td class="v">{{ \Carbon\Carbon::parse($document->valid_until)->format('d/m/Y') }}</td></tr>
                @endif
            </table>
        </td>
        <td class="pk-band-client">
            <div class="pk-client">
                <div class="pk-label">Destinataire</div>
                <div class="pk-client-name">{{ $document->client_name }}</div>
                <div class="pk-client-info">
                    @if($document->client_address) {{ $document->client_address }}<br> @endif
                    {{ implode(' · ', array_filter([$document->client_email, $document->client_phone])) }}
                </div>
            </div>
        </td>
    </tr>
</table>

@if($document->subject)
<div class="pk-subject">
    <div class="pk-label">Objet</div>
    <div class="pk-subject-text">{{ $document->subject }}</div>
</div>
@else
<div class="pk-subject"></div>
@endif

@include('pro.partials.pdf-lines', ['pkItems' => $document->items, 'pkFirstColumn' => 'Description'])

<table class="pk-summary">
    <tr>
        <td class="pk-summary-left">
            @if($pkCompact)
            @include('pro.partials.pdf-quote-texts', ['pkNotes' => $document->notes, 'pkConditions' => $document->conditions, 'pkFirst' => true])
            @endif
        </td>
        <td class="pk-summary-right">
            @include('pro.partials.pdf-totals', ['pkSubtotal' => $document->subtotal, 'pkTotalLabel' => 'Total TTC', 'pkTotal' => $document->total, 'pkDeductions' => null])
        </td>
    </tr>
</table>

@if(! $pkCompact)
@include('pro.partials.pdf-quote-texts', ['pkNotes' => $document->notes, 'pkConditions' => $document->conditions, 'pkFirst' => false])
@endif
</body>
</html>

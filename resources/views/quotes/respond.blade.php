<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Devis {{ $quote->quote_number }} - {{ $seller['name'] }}</title>
    <link rel="stylesheet" href="{{ asset('css/quote-response.css') }}?v={{ @filemtime(public_path('css/quote-response.css')) ?: 1 }}">
</head>
<body class="pk-quote-body">
@php
    $pkAwaiting = $quote->isAwaitingResponse();
    $pkAnswered = $quote->onlineResponseLabel();
    $pkExpired = $quote->status === 'expired' || ($quote->status === 'sent' && ! $pkAwaiting);
@endphp
<main class="pk-quote">
    @if(session('success'))<p class="pk-quote__flash pk-quote__flash--ok" role="status">{{ session('success') }}</p>@endif
    @if(session('error'))<p class="pk-quote__flash pk-quote__flash--error" role="alert">{{ session('error') }}</p>@endif

    <section class="pk-quote__card">
        <p class="pk-quote__seller">{{ $seller['name'] }}</p>
        <h1>Devis {{ $quote->quote_number }}</h1>
        <p class="pk-quote__meta">{{ $quote->subject }}</p>
        <p class="pk-quote__meta">Émis le {{ ($quote->issued_at ?? $quote->created_at)->format('d/m/Y') }}@if($quote->valid_until) · valable jusqu’au {{ $quote->valid_until->format('d/m/Y') }}@endif · pour {{ $quote->client_name }}</p>
    </section>

    <section class="pk-quote__card" aria-labelledby="pkQuoteLinesTitle">
        <h2 id="pkQuoteLinesTitle">Détail du devis</h2>
        <table class="pk-quote__lines">
            <thead>
                <tr><th scope="col">Description</th><th scope="col" class="pk-num">Quantité</th><th scope="col" class="pk-num">Prix unitaire HT</th><th scope="col" class="pk-num">Total HT</th></tr>
            </thead>
            <tbody>
@php
    $pkDefaultRate = (float) ($quote->tax_rate ?? 0);
    $pkSeveralRates = \App\Support\DocumentTotals::hasSeveralRates($quote->items, $pkDefaultRate);
    $pkVatLines = \App\Support\DocumentTotals::vatLines($quote->items, $pkDefaultRate, (float) $quote->tax_amount);
@endphp
                @foreach((array) $quote->items as $item)
                    <tr>
                        <td>{{ $item['description'] ?? '' }}@include('pro.partials.document-line-note')</td>
                        <td class="pk-num">{{ $item['quantity'] ?? '' }}</td>
                        <td class="pk-num">{{ number_format((float) ($item['unit_price'] ?? 0), 2, ',', ' ') }} €</td>
                        <td class="pk-num">{{ number_format((float) ($item['total'] ?? ((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0))), 2, ',', ' ') }} €</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="pk-quote__totals">
            <div><span>Sous-total HT</span><span>{{ number_format((float) $quote->subtotal, 2, ',', ' ') }} €</span></div>
            @foreach($pkVatLines as $pkVat)
            <div><span>TVA ({{ \App\Support\DocumentTotals::formatRate($pkVat['rate']) }} %)@if($pkSeveralRates) sur {{ number_format($pkVat['base'], 2, ',', ' ') }} €@endif</span><span>{{ number_format($pkVat['tax'], 2, ',', ' ') }} €</span></div>
            @endforeach
            <div class="pk-quote__total"><span>Total TTC</span><span>{{ number_format((float) $quote->total, 2, ',', ' ') }} €</span></div>
            @if($quote->deposit_percentage !== null)
                <div><span>Acompte demandé</span><span>{{ rtrim(rtrim(number_format((float) $quote->deposit_percentage, 2, ',', ' '), '0'), ',') }} %</span></div>
            @endif
        </div>
        @if($quote->conditions)<p class="pk-quote__text"><strong>Conditions :</strong> {{ $quote->conditions }}</p>@endif
        @if($quote->notes)<p class="pk-quote__text"><strong>Notes :</strong> {{ $quote->notes }}</p>@endif
        <p class="pk-quote__text"><a href="{{ route('quotes.respond.pdf', $quote->public_token) }}">Télécharger le devis complet (PDF)</a></p>
    </section>

    <section class="pk-quote__card" aria-labelledby="pkQuoteAnswerTitle">
        <h2 id="pkQuoteAnswerTitle">Votre réponse</h2>
        @if($pkAwaiting)
            <form method="POST" action="{{ route('quotes.respond.store', $quote->public_token) }}">
                @csrf
                <label class="pk-quote__field">
                    <span>Votre nom et votre prénom</span>
                    <input type="text" name="name" value="{{ old('name') }}" maxlength="150" autocomplete="name" required>
                </label>
                @error('name')<p class="pk-quote__error" role="alert">{{ $message }}</p>@enderror
                <label class="pk-quote__agree">
                    <input type="checkbox" name="agree" value="1" @checked(old('agree'))>
                    <span>Bon pour accord : j’accepte le devis {{ $quote->quote_number }} d’un montant de {{ number_format((float) $quote->total, 2, ',', ' ') }} € TTC, aux conditions indiquées.</span>
                </label>
                @error('agree')<p class="pk-quote__error" role="alert">{{ $message }}</p>@enderror
                <button type="submit" name="decision" value="accept" class="pk-quote__accept">Accepter le devis</button>
                <button type="submit" name="decision" value="refuse" class="pk-quote__refuse">Refuser ce devis</button>
                <p class="pk-quote__legal">Votre réponse est enregistrée avec votre nom, la date et l’heure, puis transmise à {{ $seller['name'] }}. Elle figure ensuite sur le devis.</p>
            </form>
        @elseif($pkAnswered)
            <p>{{ $pkAnswered }}.</p>
        @elseif($quote->status === 'accepted')
            <p>Ce devis a été accepté le {{ $quote->accepted_at?->format('d/m/Y') }}.</p>
        @elseif(in_array($quote->status, ['refused', 'rejected'], true))
            <p>Ce devis a été refusé{{ $quote->refused_at ? ' le '.$quote->refused_at->format('d/m/Y') : '' }}.</p>
        @elseif($pkExpired)
            <p>La validité de ce devis est dépassée. Contactez {{ $seller['name'] }} pour en recevoir un nouveau.</p>
        @endif
    </section>

    <p class="pk-quote__foot">Devis transmis par {{ $seller['name'] }} avec {{ config('app.name', 'Prokejem') }}. Pour toute question, contactez directement {{ $seller['name'] }}@if(!empty($seller['email'])) : {{ $seller['email'] }}@endif.</p>
</main>
</body>
</html>

{{--
    Bouton de création d'un devis ou d'une facture, avec le compteur réel des
    documents offerts (App\Services\CommercialDocumentAllowance : 2 devis et
    2 factures sans abonnement ni justificatif). Une fois les offerts utilisés,
    le bouton mène à l'abonnement Premium, qui lève la limite, seulement si
    l'abonnement est ouvert à la souscription ; sinon un message neutre, sans bouton.
    Paramètres : $allowance, $createUrl, $createLabel.
--}}
@php
    $pkIsQuote = $allowance['type'] === \App\Services\CommercialDocumentAllowance::QUOTE;
    $pkRemaining = $allowance['remaining'];
    $pkFreeLabel = $pkIsQuote
        ? ($pkRemaining > 1 ? 'devis gratuits' : 'devis gratuit')
        : ($pkRemaining > 1 ? 'factures gratuites' : 'facture gratuite');
    $pkDocsLabel = $pkIsQuote ? 'devis' : 'factures';
    $pkSubscriptionsOpen = $proSubscriptionsEnabled ?? \App\Support\PlatformFeatures::proSubscriptionsEnabled();
@endphp
<div class="pk-doc-allowance text-md-end" data-doc-allowance="{{ $allowance['type'] }}">
    @if($allowance['can_create'])
        <a href="{{ $createUrl }}" class="btn btn-pro-primary">
            <i class="fas fa-plus me-1" aria-hidden="true"></i> {{ $createLabel }}
        </a>
        @if($allowance['unlimited'])
            <p class="small text-muted mb-0 mt-1">Premium actif : {{ $pkDocsLabel }} sans limite.</p>
        @else
            <p class="small text-muted mb-0 mt-1" data-doc-allowance-count>Il vous reste {{ $pkRemaining }} {{ $pkFreeLabel }} sur {{ $allowance['limit'] }}.</p>
        @endif
    @elseif($pkSubscriptionsOpen)
        <a href="{{ route('pro.subscription') }}" class="btn btn-pro-primary" data-doc-allowance-premium>
            <i class="fas fa-crown me-1" aria-hidden="true"></i> Passer à Premium
        </a>
        <p class="small text-muted mb-0 mt-1" data-doc-allowance-count>
            Vos {{ $allowance['limit'] }} {{ $pkIsQuote ? 'devis gratuits sont utilisés' : 'factures gratuites sont utilisées' }} : Premium lève la limite.
        </p>
    @else
        <p class="small text-muted mb-0" data-doc-allowance-count data-doc-allowance-closed>
            Vous avez utilisé vos {{ $allowance['limit'] }} {{ $pkIsQuote ? 'devis gratuits' : 'factures gratuites' }}. {{ \App\Services\CommercialDocumentAllowance::SUBSCRIPTIONS_CLOSED }}
        </p>
    @endif
</div>

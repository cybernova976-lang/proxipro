{{-- Quatre chiffres mesurés. Pas de note affichée sans avis reçu. --}}
@php
    $pkFigures = [
        ['key' => 'opportunities', 'value' => $stats['new_opportunities'], 'label' => 'Demandes compatibles', 'url' => route('pro.opportunities')],
        ['key' => 'proposals', 'value' => $stats['pending_proposals'], 'label' => 'Propositions sans réponse', 'url' => route('pro.opportunities')],
        ['key' => 'missions', 'value' => $stats['active_missions'], 'label' => 'Missions en cours', 'url' => route('service-orders.index')],
    ];
    if ($isProfessional) {
        $pkFigures[] = ['key' => 'revenue', 'value' => \App\Support\Money::eur($stats['monthly_revenue'], 0),
            'label' => 'Encaissé ce mois-ci', 'url' => route('pro.invoices')];
    } else {
        $pkFigures[] = ['key' => 'rating',
            'value' => $stats['average_rating'] === null ? 'Aucun avis' : number_format($stats['average_rating'], 1, ',', ' ').' / 5',
            'label' => $stats['reviews_count'] > 0 ? $stats['reviews_count'].' avis reçu'.($stats['reviews_count'] > 1 ? 's' : '') : 'Note moyenne',
            'url' => route('pro.profile')];
    }
@endphp
<section class="pk-dash-figures" aria-labelledby="pkFiguresTitle">
    <h2 id="pkFiguresTitle" class="pk-dash__title">Vos chiffres</h2>
    <ul class="pk-dash-figures__grid">
        @foreach($pkFigures as $figure)
            <li data-figure="{{ $figure['key'] }}">
                <a href="{{ $figure['url'] }}">
                    <span class="pk-dash-figures__value">{{ $figure['value'] }}</span>
                    <span class="pk-dash-figures__label">{{ $figure['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</section>

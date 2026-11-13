{{-- Derniers devis et factures, dans une seule liste. --}}
<section class="pk-dash-docs" aria-labelledby="pkDocsTitle">
    <div class="pk-dash-docs__head">
        <h2 id="pkDocsTitle" class="pk-dash__title">Derniers devis et factures</h2>
        <span class="pk-dash-docs__links">
            <a href="{{ route('pro.quotes') }}">Tous les devis</a>
            <a href="{{ route('pro.invoices') }}">Toutes les factures</a>
        </span>
    </div>

    @if($recentDocuments->isEmpty())
        <p class="pk-dash-docs__empty">
            Aucun devis ni facture pour l’instant.
            <a href="{{ route('pro.quotes.create') }}">Préparer un premier devis</a>
        </p>
    @else
        <ul class="pk-dash-docs__list">
            @foreach($recentDocuments as $document)
                <li>
                    <a href="{{ $document['url'] }}">
                        <span class="pk-dash-docs__type">{{ $document['type'] }}</span>
                        <span class="pk-dash-docs__main">
                            <span class="pk-dash-docs__number">{{ $document['number'] ?: 'Brouillon' }}</span>
                            <span class="pk-dash-docs__client">{{ Str::limit($document['client'], 32) }}</span>
                        </span>
                        <span class="pk-dash-docs__total">{{ number_format($document['total'], 2, ',', ' ') }} €</span>
                        <span class="pro-status pro-status-{{ $document['tone'] }}">{{ $document['status'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</section>

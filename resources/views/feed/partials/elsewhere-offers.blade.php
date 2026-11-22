{{-- Territoire sans offre : les offres publiées ailleurs, annoncées comme telles et jamais mêlées au flux local. --}}
<div class="pk-elsewhere" id="pkElsewhere">
    <div class="pk-elsewhere__head">
        <div>
            <h3 id="pkElsewhereTitle">Offres publiées dans d’autres territoires</h3>
            <p>En attendant une première offre {{ $pkTerritory->withPreposition() }}. Vérifiez le lieu indiqué sur chaque offre.</p>
        </div>
        <a href="{{ $pkTerritoryLinks['all'] }}" class="pk-sechead__more">Tout voir <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
    @include('feed.partials.ranking-disclosure', ['pkRankingDisclosure' => $pkEmptyLocal['elsewhere_disclosure']])
    @if($pkEmptyLocal['elsewhere']->count() <= 3)
        <ul class="pk-feed-rows" aria-labelledby="pkElsewhereTitle">
            @foreach($pkEmptyLocal['elsewhere'] as $ad)
                <li>@include('feed.partials.ad-row', ['ad' => $ad, 'pkSaved' => $pkSavedAdIds])</li>
            @endforeach
        </ul>
    @else
        <div class="pk-feed-list" aria-labelledby="pkElsewhereTitle">
            @foreach($pkEmptyLocal['elsewhere'] as $ad)
                @include('feed.partials.ad-card', ['ad' => $ad, 'pkSaved' => $pkSavedAdIds])
            @endforeach
            {{-- Quatre ou cinq offres : la place libre de la grille propose de décrire son besoin, au lieu de rester vide. --}}
            @php $pkElsewhereCount = $pkEmptyLocal['elsewhere']->count(); @endphp
            @if($pkElsewhereCount < 6)
                @include('feed.partials.feed-filler', ['pkFillerWide' => $pkElsewhereCount === 4])
            @endif
        </div>
    @endif
</div>

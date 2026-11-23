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
    <div class="pk-feed-list" aria-labelledby="pkElsewhereTitle">
        @foreach($pkEmptyLocal['elsewhere'] as $ad)
            @include('feed.partials.ad-card', ['ad' => $ad, 'pkSaved' => $pkSavedAdIds])
        @endforeach
        @php $pkElsewhereCount = $pkEmptyLocal['elsewhere']->count(); @endphp
        @if(in_array($pkElsewhereCount, [4, 5], true))
            @include('feed.partials.feed-filler', ['pkFillerWide' => $pkElsewhereCount === 4])
        @endif
    </div>
</div>

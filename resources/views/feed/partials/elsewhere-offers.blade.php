{{-- Territoire sans offre : les offres publiées ailleurs, annoncées comme telles et jamais mêlées au flux local. --}}
<div class="pk-elsewhere" id="pkElsewhere">
    <div class="pk-elsewhere__head">
        <div>
            <h3 id="pkElsewhereTitle">Annonces publiées dans d’autres territoires</h3>
            <p>En attendant une première annonce {{ $pkTerritory->withPreposition() }}. Vérifiez le lieu indiqué sur chaque annonce.</p>
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
        </div>
    @endif
</div>

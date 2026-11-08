{{--
    Zone 2 · « Quel est votre problème ? » (mode client).
    Une pilule de recherche courte et des tuiles illustrées qui ouvrent le
    parcours guidé /demande?situation=… Aucun nombre dans la zone.
    Le texte tapé n'a pas d'attribut name : il ne part jamais dans une URL.
--}}
@php
    $pkTiles = $pkSituationTiles ?? [];
    $pkTileFallback = [];
    if (count($pkTiles) < 4) {
        foreach (array_slice(array_keys(\App\Support\MarketplaceCategoryRegistry::enabledServices()), 0, 7) as $pkUniverse) {
            $pkTileFallback[] = ['label' => $pkUniverse, 'url' => route('demand.create', ['category' => $pkUniverse])];
        }
    }
    $pkPublishFilled = ($pkPrimaryAction['state'] ?? 'publish') === 'publish';
    $pkJobUrl = ($pkTerritory ?? null)
        ? route('employment.territory', ['territoire' => $pkTerritory->slug])
        : route('employment.index');
@endphp
<section class="pk-problem" id="pkProblemPicker" aria-labelledby="pkProblemTitle">
    <h2 id="pkProblemTitle">Quel est votre problème ?</h2>
    <p class="pk-problem__sub">Touchez une image : quelques questions simples, et votre demande s’écrit toute seule.</p>

    <form class="pk-search" id="pkSearchForm" role="search" action="{{ route('demand.create') }}" method="GET">
        <i class="fas fa-search pk-search__icon" aria-hidden="true"></i>
        <input class="pk-search__field" id="pkSearchField" type="search"
               aria-labelledby="pkProblemTitle" placeholder="Ex. : fuite, ménage, clim"
               autocomplete="off" maxlength="80"
               role="combobox" aria-expanded="false" aria-controls="pkSearchSuggest" aria-autocomplete="list">
        <button type="submit" class="pk-search__go" aria-label="Chercher"><i class="fas fa-arrow-right" aria-hidden="true"></i></button>
        <ul class="pk-search__suggest" id="pkSearchSuggest" role="listbox" hidden></ul>
    </form>

    <ul class="pk-tiles">
        @foreach($pkTiles as $pkTile)
            <li>
                <a class="pk-tile" href="{{ $pkTile['url'] }}" data-pk-situation="{{ $pkTile['key'] }}">
                    <img src="{{ $pkTile['picto_url'] }}" alt="" width="40" height="40">
                    <span>{{ $pkTile['label'] }}</span>
                </a>
            </li>
        @endforeach
        @foreach($pkTileFallback as $pkUniverse)
            <li>
                <a class="pk-tile pk-tile--universe" href="{{ $pkUniverse['url'] }}">
                    <img src="{{ asset('img/pk-situations/autre-besoin.svg') }}" alt="" width="40" height="40">
                    <span>{{ $pkUniverse['label'] }}</span>
                </a>
            </li>
        @endforeach
        <li>
            <a class="pk-tile pk-tile--other" href="{{ route('demand.create') }}">
                <img src="{{ asset('img/pk-situations/autre-besoin.svg') }}" alt="" width="40" height="40">
                <span>Autre besoin</span>
            </a>
        </li>
    </ul>

    <div class="pk-problem__actions">
        <a class="{{ $pkPublishFilled ? 'pk-btn' : 'pk-btn-outline' }}" href="{{ route('demand.create') }}">
            <i class="fas fa-pen" aria-hidden="true"></i> Décrire mon besoin
        </a>
        <a class="pk-problem__job" href="{{ $pkJobUrl }}">Vous cherchez un emploi ? <i class="fas fa-chevron-right" aria-hidden="true"></i></a>
    </div>

    <script type="application/json" id="pkSearchConfig">{!! Illuminate\Support\Js::encode($pkSearchConfig ?? []) !!}</script>
</section>

{{-- Portee du flux client : le territoire affiche est toujours dit en clair, jamais elargi en silence. --}}
<span class="pk-scope" id="pkFeedScope">
    @if($pkTerritoryScoped)
        Offres {{ $pkTerritory->withDe() }}{{ config('feed.client.include_remote_offers', true) ? ' et offres « À distance »' : '' }}
        · <a href="{{ $pkTerritoryLinks['all'] }}">Voir les autres territoires</a>
    @elseif($useNearbyScope && ! $geoFallbackUsed && $geoCity)
        {{ $geoCity }} et alentours
    @elseif($pkTerritory)
        Toutes les zones · <a href="{{ $pkTerritoryLinks['territory'] }}">Revenir aux offres {{ $pkTerritory->withDe() }}</a>
    @else
        Vous voyez toutes les zones. <a href="{{ $pkTerritoryLinks['location'] }}">Indiquez votre commune</a>
    @endif
</span>

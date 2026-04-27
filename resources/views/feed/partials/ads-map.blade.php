@if(isset($adsMapData) && $adsMapData->count() > 0)
<section class="ads-map-section">
    <div class="ads-map-header">
        <div>
            <h3 class="ads-map-title"><i class="fas fa-map-marked-alt me-2"></i>Carte des annonces</h3>
            <p class="ads-map-copy">
                Visualisez les annonces de cette page sur la carte
                @if($geoEnabled && $geoCity)
                    autour de {{ $geoCity }}
                @endif
                .
            </p>
        </div>
    </div>

    <div id="adsFeedMap" class="ads-map-canvas" data-markers='@json($adsMapData)'></div>

    <div class="ads-map-legend">
        <span class="ads-map-legend-item"><span class="ads-map-dot standard"></span>Annonce standard</span>
        <span class="ads-map-legend-item"><span class="ads-map-dot boosted"></span>Annonce boostee</span>
        <span class="ads-map-legend-item"><span class="ads-map-dot urgent"></span>Annonce urgente</span>
    </div>
</section>
@endif
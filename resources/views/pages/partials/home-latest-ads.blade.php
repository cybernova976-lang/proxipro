@if($latestAds->isNotEmpty())
<section class="section">
    <div class="container">
        <div class="section-head reveal">
            <span class="section-pill">Annonces</span>
            <h2 class="section-title">Les dernières annonces publiées</h2>
            <p class="section-desc">Découvrez les besoins et services publiés sur la plateforme.</p>
        </div>
        <div class="row g-4">
            @foreach($latestAds as $ad)
            <div class="col-md-6 col-lg-4">
                <a href="{{ route('ads.show', $ad) }}" class="ad-card reveal">
                    <div class="ad-card-img">
                        @if(!empty($ad->photos[0]))
                            <img src="{{ storage_url($ad->photos[0]) }}" alt="{{ $ad->title }}" loading="lazy">
                        @else
                            <i class="fas fa-{{ $ad->service_type === 'demande' ? 'clipboard-list' : 'briefcase' }}"></i>
                        @endif
                        <span class="ad-card-badge">{{ $ad->service_type === 'demande' ? 'Demande' : 'Offre' }}</span>
                    </div>
                    <div class="ad-card-body">
                        <h4>{{ Str::limit($ad->title, 65) }}</h4>
                        <div class="ad-card-location">
                            <i class="fas fa-map-marker-alt me-1"></i>{{ $ad->city ?: ($ad->location ?: 'Localisation à préciser') }}
                        </div>
                        <div class="ad-card-bottom">
                            <span class="ad-card-price">{{ $ad->formatted_price }}</span>
                            <span class="ad-card-time">{{ $ad->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
        <div class="text-center">
            <a href="{{ route('ads.index') }}" class="btn-see-all">Voir toutes les annonces <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</section>
@endif

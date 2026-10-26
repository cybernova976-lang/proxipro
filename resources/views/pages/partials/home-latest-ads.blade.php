@if($latestAds->isNotEmpty())
<section class="section home-latest-ads">
    <div class="container">
        <div class="section-head reveal">
            <span class="section-pill">Sur la plateforme</span>
            <h2 class="section-title">Besoins et services récents</h2>
            <p class="section-desc">Les publications des 30 derniers jours.</p>
        </div>
        <div class="home-recent-grid">
            @foreach($latestAds as $ad)
            <a href="{{ route('ads.show', $ad) }}" class="home-recent-card">
                <span class="home-recent-icon" aria-hidden="true"><i class="fas {{ $ad->service_type === 'demande' ? 'fa-clipboard-list' : 'fa-briefcase' }}"></i></span>
                <div class="home-recent-content">
                    <div class="home-recent-meta"><span>{{ $ad->service_type === 'demande' ? 'Besoin publié' : 'Service proposé' }}</span><time datetime="{{ $ad->created_at->toDateString() }}">{{ $ad->created_at->diffForHumans() }}</time></div>
                    <h3>{{ Str::limit($ad->title, 65) }}</h3>
                    <p>{{ $ad->city ?: $ad->location }} <span aria-hidden="true">·</span> {{ $ad->category }}</p>
                    <span class="home-recent-open">{{ $ad->service_type === 'demande' ? 'Voir le besoin' : 'Voir le service' }} <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                </div>
            </a>
            @endforeach
        </div>
        <div class="text-center">
            <a href="{{ route('ads.index') }}" class="btn-see-all">Voir toutes les annonces <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</section>
@endif

@if($featuredPros->isNotEmpty())
<section class="section home-providers" id="prestataires">
    <div class="container">
        <div class="section-head">
            <span class="section-pill">Des savoir-faire à découvrir</span>
            <h2 class="section-title">Des prestataires à découvrir</h2>
            <p class="section-desc">Consultez leurs services et leurs réalisations.</p>
        </div>
        <div class="home-provider-grid" aria-label="Prestataires à découvrir">
            @foreach($featuredPros as $pro)
            <a href="{{ route('profile.public', $pro->id) }}" class="home-provider-item">
                <img src="{{ storage_url($pro->avatar) }}" alt="" width="64" height="64" loading="lazy">
                <div class="home-provider-info">
                    <h3>{{ $pro->company_name ?: $pro->name }} @if($pro->identity_verified)<i class="fas fa-check-circle" aria-label="Identité vérifiée"></i>@endif</h3>
                    <p class="home-provider-profession">{{ $pro->profession }}</p>
                    <p class="home-provider-location">{{ $pro->city }}{{ $pro->country ? ', '.$pro->country : '' }}</p>
                    @if($pro->verified_reviews_count > 0)<p class="home-provider-rating"><i class="fas fa-star" aria-hidden="true"></i> {{ number_format((float) $pro->verified_reviews_avg, 1, ',', ' ') }} · {{ $pro->verified_reviews_count }} avis de mission</p>@endif
                    <p class="home-provider-bio">{{ Str::limit(strip_tags($pro->bio), 100) }}</p>
                    <span class="home-provider-open">Voir le profil <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                </div>
            </a>
            @endforeach
        </div>
        <div class="text-center"><a href="{{ route('feed.professionals') }}" class="btn-see-all">Consulter l’annuaire <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
    </div>
</section>
@endif

@if($latestAds->isNotEmpty())
<section class="section home-latest-ads" id="publications" aria-labelledby="home-publications-title">
    <div class="container">
        <div class="section-head">
            <span class="section-pill">La communauté</span>
            <h2 class="section-title" id="home-publications-title">Découvrez les annonces des membres</h2>
            <p class="section-desc">Des services proposés et des besoins à concrétiser. Une publication vous intéresse ? Échangez avec son auteur.</p>
        </div>
        <div class="home-recent-grid">
            @foreach($latestAds as $ad)
            @php
                $publicationPhotos = $ad->photos ?? [];
                if (is_string($publicationPhotos)) {
                    $publicationPhotos = json_decode($publicationPhotos, true);
                }
                $publicationPhotos = array_values(array_filter(is_array($publicationPhotos) ? $publicationPhotos : [], fn ($photo) => is_string($photo) && trim($photo) !== ''));
            @endphp
            <article class="home-recent-card home-publication-{{ $ad->service_type }}" data-publication-id="{{ $ad->id }}" data-publication-type="{{ $ad->service_type }}">
                <a href="{{ route('ads.show', $ad) }}" class="home-publication-media" aria-label="Consulter l’annonce : {{ $ad->title }}">
                    @if($publicationPhotos !== [])
                        <img src="{{ storage_url(trim($publicationPhotos[0])) }}" alt="Photo publiée pour : {{ $ad->title }}" width="640" height="360" loading="lazy" decoding="async" data-publication-image>
                        <span class="home-publication-photo-placeholder" data-publication-image-fallback hidden><i class="far fa-image" aria-hidden="true"></i><span>Photo indisponible</span></span>
                        @if(count($publicationPhotos) > 1)
                            <span class="home-publication-photo-count"><i class="far fa-images" aria-hidden="true"></i> {{ count($publicationPhotos) }} photos</span>
                        @endif
                    @else
                        <span class="home-publication-photo-placeholder"><i class="far fa-image" aria-hidden="true"></i><span>Photo non fournie</span></span>
                    @endif
                </a>
                <div class="home-recent-meta"><span class="home-publication-type"><i class="fas {{ $ad->service_type === 'demande' ? 'fa-clipboard-list' : 'fa-toolbox' }}" aria-hidden="true"></i> {{ $ad->service_type === 'demande' ? 'Demande de service' : 'Service proposé' }}</span><time datetime="{{ $ad->created_at->toDateString() }}">{{ $ad->created_at->diffForHumans() }}</time></div>
                <p class="home-publication-category">{{ $ad->category }}</p>
                <h3><a href="{{ route('ads.show', $ad) }}">{{ Str::limit($ad->title, 100) }}</a></h3>
                <p class="home-publication-excerpt">{{ Str::limit(trim(strip_tags($ad->description)), 180) }}</p>
                <div class="home-publication-details"><span><i class="fas fa-map-marker-alt" aria-hidden="true"></i> {{ $ad->city ?: $ad->location }}</span><strong>{{ $ad->formatted_price }}</strong></div>
                <div class="home-publication-footer"><span class="home-publication-author">{{ Str::limit($ad->user->name, 40) }}</span><a href="{{ route('login', ['continue' => 'ad', 'ad' => $ad->id]) }}" class="home-publication-contact" aria-label="Se connecter pour contacter l’auteur de : {{ $ad->title }}"><i class="far fa-comment-dots" aria-hidden="true"></i> {{ $ad->service_type === 'demande' ? 'Proposer mon aide' : 'Contacter' }}</a></div>
            </article>
            @endforeach
        </div>
        <p class="home-publication-note"><i class="fas fa-comments" aria-hidden="true"></i> Connectez-vous ou créez un compte gratuitement pour contacter les auteurs dans la messagerie.</p>
    </div>
</section>
@endif

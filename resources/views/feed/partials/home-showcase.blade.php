@php
    $personalRequestAds = collect($homePersonalRequests ?? [])->values();
    $professionalOfferAds = collect($homeProfessionalOffers ?? [])->values();
    $professionalProfiles = collect($homeProfessionalProfiles ?? [])->values();

    $adSections = collect([
        [
            'kind' => 'personal-requests',
            'dataKind' => 'personal-request',
            'title' => 'Offres des particuliers',
            'copy' => 'Des particuliers recherchent un professionnel disponible pour leurs travaux et services.',
            'badge' => 'Demande',
            'icon' => 'fas fa-search',
            'ads' => $personalRequestAds,
        ],
        [
            'kind' => 'professional-offers',
            'dataKind' => 'professional-offer',
            'title' => 'Offres de professionnels',
            'copy' => 'Services, locations de matériel, recrutements et promotions publiés par des pros.',
            'badge' => 'Offre pro',
            'icon' => 'fas fa-briefcase',
            'ads' => $professionalOfferAds,
        ],
    ])->filter(fn($section) => $section['ads']->isNotEmpty())->values();
@endphp

@if($adSections->isNotEmpty() || $professionalProfiles->isNotEmpty())
<section class="home-showcase-section" aria-labelledby="homeShowcaseTitle">
    <div class="home-showcase-header">
        <div>
            <h2 id="homeShowcaseTitle">Le meilleur de Massiwani</h2>
            <p>Des annonces triées par intention, puis les profils professionnels mis en avant.</p>
        </div>
        <a href="#adsFeedMap" class="home-showcase-link">Voir la carte <i class="fas fa-arrow-down"></i></a>
    </div>

    @foreach($adSections as $section)
    @php
        $isScrollableSection = $section['ads']->count() > 6;
    @endphp
    <div class="home-showcase-block home-showcase-block--{{ $section['kind'] }}" data-showcase-block="{{ $section['kind'] }}">
        <div class="home-showcase-block-header">
            <div>
                <span class="home-showcase-kicker"><i class="{{ $section['icon'] }}"></i> {{ $section['badge'] }}</span>
                <h3>{{ $section['title'] }}</h3>
                <p>{{ $section['copy'] }}</p>
            </div>
        </div>

        <div class="home-showcase-carousel{{ $isScrollableSection ? ' is-scrollable' : '' }}" data-showcase-carousel>
            @if($isScrollableSection)
                <button type="button" class="home-showcase-carousel-arrow home-showcase-carousel-arrow--prev" data-showcase-scroll-dir="-1" aria-label="Annonces précédentes">
                    <i class="fas fa-chevron-left"></i>
                </button>
            @endif

            <div class="home-showcase-scroll" data-showcase-scroll>
                <div class="home-showcase-ads-grid{{ $isScrollableSection ? ' home-showcase-scroll-grid' : '' }}" data-showcase-ads-count="{{ $section['ads']->count() }}">
            @foreach($section['ads'] as $ad)
            @php
                $photos = $ad->photos ?? [];
                if (is_string($photos)) {
                    $decoded = json_decode($photos, true);
                    $photos = json_last_error() === JSON_ERROR_NONE && is_array($decoded)
                        ? $decoded
                        : (trim($photos) !== '' ? [$photos] : []);
                } elseif (!is_array($photos)) {
                    $photos = (array) $photos;
                }
                $photos = array_values(array_filter($photos));
                $firstPhoto = $photos[0] ?? null;
                $visualUrl = null;
                if ($firstPhoto) {
                    $cleanPhoto = ltrim(trim($firstPhoto), '/');
                    if (str_starts_with($cleanPhoto, 'http://') || str_starts_with($cleanPhoto, 'https://')) {
                        $visualUrl = $cleanPhoto;
                    } elseif (str_starts_with($cleanPhoto, 'storage/')) {
                        $visualUrl = asset($cleanPhoto);
                    } elseif (str_starts_with($cleanPhoto, 'public/')) {
                        $visualUrl = storage_url(str_replace('public/', '', $cleanPhoto));
                    } else {
                        $visualUrl = storage_url($cleanPhoto);
                    }
                }
                $isBoosted = $ad->is_boosted && $ad->boost_end && $ad->boost_end > now();
                $isUrgent = $ad->is_urgent && (!$ad->urgent_until || $ad->urgent_until > now());
                $author = $ad->user;
                $authorName = $author?->name ?? 'Utilisateur';
                $authorInitial = strtoupper(substr($authorName, 0, 1));
                $isProfessionalOffer = $section['dataKind'] === 'professional-offer';
            @endphp
            <a href="{{ route('ads.show', $ad) }}"
               class="home-showcase-ad-card{{ $isUrgent ? ' is-urgent' : '' }}{{ $isBoosted ? ' is-boosted' : '' }}"
               data-showcase-kind="{{ $section['dataKind'] }}"
               data-showcase-ad-id="{{ $ad->id }}">
                <div class="home-showcase-ad-media">
                    @if($visualUrl)
                        <img src="{{ $visualUrl }}" alt="{{ $ad->title }}" loading="lazy">
                    @else
                        <div class="home-showcase-ad-placeholder">
                            <i class="{{ $section['icon'] }}"></i>
                        </div>
                    @endif

                    <div class="home-showcase-ad-flags">
                        @if($isUrgent)
                            <span class="home-showcase-flag flag-urgent"><i class="fas fa-fire"></i> Urgent</span>
                        @endif
                        @if($isBoosted)
                            <span class="home-showcase-flag flag-boost"><i class="fas fa-rocket"></i> Boosté</span>
                        @endif
                    </div>

                    <span class="home-showcase-price">
                        {{ $ad->price ? number_format($ad->price, 0, ',', ' ') . ' €' : 'Sur devis' }}
                    </span>
                </div>

                <div class="home-showcase-ad-body">
                    <div class="home-showcase-ad-meta">
                        <span class="home-showcase-type-badge">{{ $section['badge'] }}</span>
                        <span>{{ Str::limit($ad->category ?? 'Service', 24) }}</span>
                    </div>
                    <h4>{{ Str::limit($ad->title, 54) }}</h4>
                    <p>{{ Str::limit($ad->description, 82) }}</p>
                    <div class="home-showcase-ad-footer">
                        <span class="home-showcase-author">
                            @if($author?->avatar)
                                <img src="{{ storage_url($author->avatar) }}" alt="{{ $authorName }}">
                            @else
                                <span>{{ $authorInitial }}</span>
                            @endif
                            {{ Str::limit($authorName, 16) }}
                            @if($isProfessionalOffer)
                                <span class="home-showcase-author-pro">PRO</span>
                            @endif
                        </span>
                        <span class="home-showcase-location"><i class="fas fa-map-marker-alt"></i> {{ Str::limit($ad->location ?? $ad->city ?? 'Local', 18) }}</span>
                    </div>
                </div>
            </a>
            @endforeach
                </div>
            </div>

            @if($isScrollableSection)
                <button type="button" class="home-showcase-carousel-arrow home-showcase-carousel-arrow--next" data-showcase-scroll-dir="1" aria-label="Annonces suivantes">
                    <i class="fas fa-chevron-right"></i>
                </button>
            @endif
        </div>
    </div>
    @endforeach

    @if($professionalProfiles->isNotEmpty())
    @php
        $isProfilesScrollable = $professionalProfiles->count() > 6;
    @endphp
    <div class="home-showcase-block home-showcase-block--professional-profiles" data-showcase-block="professional-profiles">
        <div class="home-showcase-block-header">
            <div>
                <span class="home-showcase-kicker"><i class="fas fa-user-tie"></i> Profils</span>
                <h3>Profils de professionnels</h3>
                <p>Des prestataires locaux, avec les profils abonnés ou boostés affichés en priorité.</p>
            </div>
            <a href="javascript:void(0)" onclick="setViewMode('providers'); window.scrollTo({top: 0, behavior: 'smooth'});">Voir les profils <i class="fas fa-arrow-right"></i></a>
        </div>

        <div class="home-showcase-carousel{{ $isProfilesScrollable ? ' is-scrollable' : '' }}" data-showcase-carousel>
            @if($isProfilesScrollable)
                <button type="button" class="home-showcase-carousel-arrow home-showcase-carousel-arrow--prev" data-showcase-scroll-dir="-1" aria-label="Profils précédents">
                    <i class="fas fa-chevron-left"></i>
                </button>
            @endif

            <div class="home-showcase-scroll" data-showcase-scroll>
                <div class="home-showcase-pro-grid{{ $isProfilesScrollable ? ' home-showcase-scroll-grid' : '' }}" data-showcase-pros-count="{{ $professionalProfiles->count() }}">
            @foreach($professionalProfiles as $pro)
            @php
                $ratingRaw = $pro->verified_reviews_avg ?? $pro->reviews_avg_rating ?? null;
                $rating = $ratingRaw ? rtrim(rtrim(number_format((float) $ratingRaw, 1, ',', ''), '0'), ',') : null;
                $reviewsCount = (int) ($pro->verified_reviews_count ?? $pro->reviews_count ?? 0);
                $primaryService = $pro->relationLoaded('services') ? $pro->services->first() : null;
                $profession = $pro->profession ?? $pro->service_category ?? $primaryService?->subcategory ?? $primaryService?->main_category ?? 'Prestataire de services';
                $bio = $pro->bio ?: 'Prestataire professionnel disponible pour vos demandes locales.';
                $isTopProvider = (bool) ($pro->is_top_provider ?? false);
            @endphp
            <a href="{{ route('profile.public', $pro->id) }}"
               class="home-showcase-pro-card"
               data-showcase-kind="professional-profile"
               data-showcase-pro-id="{{ $pro->id }}">
                <div class="home-showcase-pro-avatar">
                    @if($pro->avatar)
                        <img src="{{ storage_url($pro->avatar) }}" alt="{{ $pro->name }}" loading="lazy">
                    @else
                        <span class="home-showcase-pro-initial">{{ strtoupper(substr($pro->name, 0, 1)) }}</span>
                    @endif
                    @if($isTopProvider)
                        <span class="home-showcase-top-provider"><i class="fas fa-star"></i> Top prestataire</span>
                    @endif
                </div>
                <div class="home-showcase-pro-content">
                    <div class="home-showcase-pro-name-row">
                        <div class="home-showcase-pro-title">
                            <strong>{{ Str::limit($pro->name, 20) }}</strong>
                            <span class="home-showcase-pro-badge">PRO</span>
                        </div>
                    </div>
                    <p class="home-showcase-pro-profession">{{ Str::limit($profession, 44) }}</p>
                    <div class="home-showcase-pro-rating">
                        <i class="fas fa-star"></i>
                        @if($reviewsCount > 0 && $rating)
                            <strong>{{ $rating }}</strong>
                            <span>{{ $reviewsCount }} avis</span>
                        @else
                            <span>0 avis</span>
                        @endif
                    </div>
                    <p class="home-showcase-pro-bio">{{ Str::limit($bio, 86) }}</p>
                </div>
            </a>
            @endforeach
                </div>
            </div>

            @if($isProfilesScrollable)
                <button type="button" class="home-showcase-carousel-arrow home-showcase-carousel-arrow--next" data-showcase-scroll-dir="1" aria-label="Profils suivants">
                    <i class="fas fa-chevron-right"></i>
                </button>
            @endif
        </div>
    </div>
    @endif
</section>
@endif

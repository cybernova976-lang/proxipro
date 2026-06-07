@php
    $personalRequestAds = collect($homePersonalRequests ?? [])->values();
    $professionalOfferAds = collect($homeProfessionalOffers ?? [])->values();
    $professionalProfiles = collect($homeProfessionalProfiles ?? [])->values();

    $adSections = collect([
        [
            'kind' => 'personal-requests',
            'dataKind' => 'personal-request',
            'title' => 'Offres des particuliers',
            'copy' => 'Des utilisateurs recherchent un professionnel disponible pour leurs travaux et services.',
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
            <h2 id="homeShowcaseTitle">Pour vous</h2>
            <p>Sélectionnés selon vos intérêts</p>
        </div>
        <a href="#adsFeedMap" class="home-showcase-link">Voir la carte <i class="fas fa-arrow-down"></i></a>
    </div>

    @foreach($adSections as $section)
    @php
        $isPersonalRequestSection = $section['kind'] === 'personal-requests';
        $fixedPersonalAds = $isPersonalRequestSection ? $section['ads']->take(3)->values() : collect();
        $scrollPersonalAds = $isPersonalRequestSection ? $section['ads']->slice(3)->values() : collect();
        $isPersonalSecondRowScrollable = $scrollPersonalAds->count() > 3;
        $isScrollableSection = !$isPersonalRequestSection && $section['ads']->count() > 6;
    @endphp
    <div class="home-showcase-block home-showcase-block--{{ $section['kind'] }}" data-showcase-block="{{ $section['kind'] }}">
        <div class="home-showcase-block-header">
            <div>
                <span class="home-showcase-kicker"><i class="{{ $section['icon'] }}"></i> {{ $section['badge'] }}</span>
                <h3>{{ $section['title'] }}</h3>
                <p>{{ $section['copy'] }}</p>
            </div>
        </div>

        @if($isPersonalRequestSection)
            <div class="home-showcase-personal-stack">
                <div class="home-showcase-ads-grid home-showcase-fixed-row" data-showcase-ads-count="{{ $fixedPersonalAds->count() }}">
                    @foreach($fixedPersonalAds as $ad)
                        @include('feed.partials.home-showcase-ad-card', ['ad' => $ad, 'section' => $section])
                    @endforeach
                </div>

                @if($scrollPersonalAds->isNotEmpty())
                    <div class="home-showcase-carousel{{ $isPersonalSecondRowScrollable ? ' is-scrollable' : '' }} home-showcase-row2-carousel" data-showcase-carousel>
                        @if($isPersonalSecondRowScrollable)
                            <button type="button" class="home-showcase-carousel-arrow home-showcase-carousel-arrow--prev" data-showcase-scroll-dir="-1" aria-label="Demandes précédentes">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                        @endif

                        <div class="home-showcase-scroll" data-showcase-scroll>
                            <div class="home-showcase-ads-grid home-showcase-row2-grid{{ $isPersonalSecondRowScrollable ? ' home-showcase-scroll-row-grid' : '' }}" data-showcase-ads-count="{{ $scrollPersonalAds->count() }}">
                                @foreach($scrollPersonalAds as $ad)
                                    @include('feed.partials.home-showcase-ad-card', ['ad' => $ad, 'section' => $section])
                                @endforeach
                            </div>
                        </div>

                        @if($isPersonalSecondRowScrollable)
                            <button type="button" class="home-showcase-carousel-arrow home-showcase-carousel-arrow--next" data-showcase-scroll-dir="1" aria-label="Demandes suivantes">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        @else
            <div class="home-showcase-carousel{{ $isScrollableSection ? ' is-scrollable' : '' }} home-showcase-professional-offers-carousel" data-showcase-carousel data-mobile-navigation="swipe">
                @if($isScrollableSection)
                    <button type="button" class="home-showcase-carousel-arrow home-showcase-carousel-arrow--prev home-showcase-professional-offers-arrow" data-showcase-scroll-dir="-1" aria-label="Annonces précédentes">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                @endif

                <div class="home-showcase-scroll" data-showcase-scroll>
                    <div class="home-showcase-ads-grid{{ $isScrollableSection ? ' home-showcase-scroll-grid' : '' }}" data-showcase-ads-count="{{ $section['ads']->count() }}">
                        @foreach($section['ads'] as $ad)
                            @include('feed.partials.home-showcase-ad-card', ['ad' => $ad, 'section' => $section])
                        @endforeach
                    </div>
                </div>

                @if($isScrollableSection)
                    <button type="button" class="home-showcase-carousel-arrow home-showcase-carousel-arrow--next home-showcase-professional-offers-arrow" data-showcase-scroll-dir="1" aria-label="Annonces suivantes">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                @endif
            </div>
        @endif
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

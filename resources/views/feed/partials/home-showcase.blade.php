@php
    $showcaseAds = collect($homeShowcaseAds ?? [])->take(8)->values();
    $showcasePros = collect($homeShowcasePros ?? [])->take(4)->values();
@endphp

@if($showcaseAds->isNotEmpty() || $showcasePros->isNotEmpty())
<section class="home-showcase-section" aria-labelledby="homeShowcaseTitle">
    <div class="home-showcase-header">
        <div>
            <h2 id="homeShowcaseTitle">À la une près de chez vous</h2>
        </div>
        <a href="#missionsGrid" class="home-showcase-link">Tout voir <i class="fas fa-arrow-down"></i></a>
    </div>

    @if($showcaseAds->isNotEmpty())
    <div class="home-showcase-ads-grid" data-showcase-ads-count="{{ $showcaseAds->count() }}">
        @foreach($showcaseAds as $ad)
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
        @endphp
        <a href="{{ route('ads.show', $ad) }}" class="home-showcase-ad-card{{ $isUrgent ? ' is-urgent' : '' }}{{ $isBoosted ? ' is-boosted' : '' }}" data-showcase-ad-id="{{ $ad->id }}">
            <div class="home-showcase-ad-media">
                @if($visualUrl)
                    <img src="{{ $visualUrl }}" alt="{{ $ad->title }}" loading="lazy">
                @else
                    <div class="home-showcase-ad-placeholder">
                        <i class="fas fa-briefcase"></i>
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
                    <span>{{ Str::limit($ad->category ?? 'Service', 24) }}</span>
                    <span>{{ $ad->created_at?->diffForHumans() }}</span>
                </div>
                <h3>{{ Str::limit($ad->title, 54) }}</h3>
                <p>{{ Str::limit($ad->description, 82) }}</p>
                <div class="home-showcase-ad-footer">
                    <span class="home-showcase-author">
                        @if($author?->avatar)
                            <img src="{{ storage_url($author->avatar) }}" alt="{{ $authorName }}">
                        @else
                            <span>{{ $authorInitial }}</span>
                        @endif
                        {{ Str::limit($authorName, 16) }}
                    </span>
                    <span class="home-showcase-location"><i class="fas fa-map-marker-alt"></i> {{ Str::limit($ad->location ?? $ad->city ?? 'France', 18) }}</span>
                </div>
            </div>
        </a>
        @endforeach
    </div>
    @endif

    @if($showcasePros->isNotEmpty())
    <div class="home-showcase-pro-row">
        <div class="home-showcase-row-title">
            <h3>Professionnels mis en avant</h3>
            <a href="javascript:void(0)" onclick="setViewMode('providers'); window.scrollTo({top: 0, behavior: 'smooth'});">Voir les profils <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="home-showcase-pro-grid" data-showcase-pros-count="{{ $showcasePros->count() }}">
            @foreach($showcasePros as $pro)
            @php
                $isPremiumProfile = (bool) ($pro->is_featured_premium ?? false);
                $isProAccount = $pro->user_type === 'professionnel' || $pro->hasActiveProSubscription() || $pro->hasCompletedProOnboarding();
                $rating = $pro->verified_reviews_avg ? number_format((float) $pro->verified_reviews_avg, 1, ',', '') : null;
                $reviewsCount = (int) ($pro->verified_reviews_count ?? 0);
                $profession = $pro->profession ?? $pro->service_category ?? $pro->bio ?? 'Prestataire de services';
            @endphp
            <a href="{{ route('profile.public', $pro->id) }}" class="home-showcase-pro-card{{ $isPremiumProfile ? ' is-premium' : '' }}" data-showcase-pro-id="{{ $pro->id }}">
                <div class="home-showcase-pro-avatar">
                    @if($pro->avatar)
                        <img src="{{ storage_url($pro->avatar) }}" alt="{{ $pro->name }}" loading="lazy">
                    @else
                        <span>{{ strtoupper(substr($pro->name, 0, 1)) }}</span>
                    @endif
                </div>
                <div class="home-showcase-pro-content">
                    <div class="home-showcase-pro-name">
                        <strong>{{ Str::limit($pro->name, 18) }}</strong>
                        <span class="{{ $isPremiumProfile ? 'badge-premium' : 'badge-provider' }}">
                            {{ $isPremiumProfile ? 'Mis en avant' : ($isProAccount ? 'Pro' : 'Prestataire') }}
                        </span>
                    </div>
                    <p>{{ Str::limit($profession, 38) }}</p>
                    <div class="home-showcase-pro-foot">
                        <span><i class="fas fa-star"></i> {{ $rating ?: 'Nouveau' }}{{ $reviewsCount > 0 ? ' (' . $reviewsCount . ')' : '' }}</span>
                        <span><i class="fas fa-map-marker-alt"></i> {{ Str::limit($pro->city ?? $pro->country ?? 'Local', 16) }}</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</section>
@endif

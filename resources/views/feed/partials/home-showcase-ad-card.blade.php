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
    $publishedDate = $ad->created_at?->format('d/m/Y');
    $priceLabel = $ad->formatted_price;
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
            {{ $priceLabel }}
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
            @if($publishedDate)
                <span class="home-showcase-published-at"><i class="far fa-calendar-alt"></i> {{ $publishedDate }}</span>
            @endif
            <span class="home-showcase-location"><i class="fas fa-map-marker-alt"></i> {{ Str::limit($ad->location ?? $ad->city ?? 'Local', 18) }}</span>
        </div>
    </div>
</a>

@if(isset($recommendedAds) && $recommendedAds->count() > 0)
<section class="recommendations-strip">
    <div class="recommendations-strip-header">
        <div>
            <h3 class="recommendations-strip-title">
                <i class="fas fa-sparkles"></i>
                Pour vous
            </h3>
            <p class="recommendations-strip-copy">
                Une selection personnalisee selon vos categories, votre activite et votre zone.
            </p>
        </div>
        <a href="{{ route('feed', ['sort' => 'recommended']) }}" style="font-size: 0.82rem; font-weight: 700; color: #c2410c; text-decoration: none; white-space: nowrap;">
            Voir plus <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="recommendations-grid">
        @foreach($recommendedAds as $ad)
        @php
            $recommendationUser = $ad->user;
            $isServiceProvider = $recommendationUser && ($recommendationUser->user_type === 'professionnel' || $recommendationUser->is_service_provider);
            $isProfessionalPromotion = $isServiceProvider && (($ad->service_type ?? null) !== 'demande');
            $displayName = $recommendationUser?->name ? trim(explode(' ', $recommendationUser->name)[0]) : 'Utilisateur';
            $isProAccount = $recommendationUser && ($recommendationUser->user_type === 'professionnel' || $recommendationUser->hasActiveProSubscription());
            $qualities = collect($recommendationUser?->specialties ?? $recommendationUser?->service_subcategories ?? [])
                ->filter()
                ->reject(fn ($quality) => $quality === ($recommendationUser?->profession ?? null))
                ->take(3)
                ->values();

            $photos = $ad->photos ?? [];
            if (is_string($photos)) {
                $decodedPhotos = json_decode($photos, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedPhotos)) {
                    $photos = $decodedPhotos;
                } elseif (trim($photos) !== '') {
                    $photos = [$photos];
                } else {
                    $photos = [];
                }
            } elseif (!is_array($photos)) {
                $photos = (array) $photos;
            }
            $photos = array_values(array_filter($photos));
            $photoCount = count($photos);
            $firstPhoto = $photos[0] ?? null;
            $visualUrl = null;
            if ($isProfessionalPromotion && $recommendationUser?->avatar) {
                $visualUrl = storage_url($recommendationUser->avatar);
            } elseif ($firstPhoto) {
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

            $recommendationUrl = $isProfessionalPromotion && $recommendationUser
                ? route('profile.public', $recommendationUser->id)
                : route('ads.show', $ad->id);
        @endphp
        <a href="{{ $recommendationUrl }}" class="recommendation-card{{ $isProfessionalPromotion ? ' recommendation-card--profile' : '' }}">
            <div class="recommendation-card-media">
                @if($visualUrl)
                    <img src="{{ $visualUrl }}" alt="{{ $isProfessionalPromotion ? $displayName : $ad->title }}" class="recommendation-card-image">
                @else
                    <div class="recommendation-card-image recommendation-card-image-placeholder">
                        <span>{{ strtoupper(substr($displayName, 0, 1)) }}</span>
                    </div>
                @endif

                @if($isProfessionalPromotion)
                    <span class="recommendation-card-media-badge">Profil mis en avant</span>
                @elseif($photoCount > 1)
                    <span class="recommendation-card-media-badge">1 / {{ $photoCount }}</span>
                @endif
            </div>

            <div class="recommendation-card-body">
                <div class="recommendation-card-meta">
                    <span>
                        <i class="fas {{ $isProfessionalPromotion ? 'fa-user-tie' : 'fa-folder-open' }} me-1"></i>
                        {{ $isProfessionalPromotion ? 'Professionnel recommande' : $ad->category }}
                    </span>
                    <span>{{ $ad->created_at?->diffForHumans() }}</span>
                </div>

                @if($isProfessionalPromotion)
                    <div class="recommendation-card-name-row">
                        <h4 class="recommendation-card-title">{{ $displayName }}</h4>
                        <span class="recommendation-card-account-badge{{ $isProAccount ? ' is-pro' : '' }}">
                            {{ $isProAccount ? 'Pro' : 'Prestataire' }}
                        </span>
                    </div>
                    <p class="recommendation-card-profession">{{ Str::limit($recommendationUser->profession ?? $recommendationUser->service_category ?? 'Prestataire de services', 52) }}</p>
                    <p class="recommendation-card-text">{{ Str::limit($recommendationUser->bio ?: $ad->description, 118) }}</p>

                    @if($qualities->isNotEmpty())
                    <div class="recommendation-reasons recommendation-reasons--qualities">
                        @foreach($qualities as $quality)
                        <span class="recommendation-reason recommendation-reason--quality">{{ Str::limit($quality, 28) }}</span>
                        @endforeach
                    </div>
                    @endif

                    <div class="recommendation-card-footer">
                        <span>
                            <i class="fas fa-map-marker-alt me-1"></i>{{ Str::limit($recommendationUser->city ?? $ad->location ?? 'France', 28) }}
                        </span>
                        <span class="recommendation-card-price">
                            @if($recommendationUser->hourly_rate && ($recommendationUser->show_hourly_rate ?? true))
                                {{ number_format((float) $recommendationUser->hourly_rate, 0, ',', ' ') }} €/h
                            @else
                                Profil
                            @endif
                        </span>
                    </div>
                @else
                    <h4 class="recommendation-card-title">{{ Str::limit($ad->title, 72) }}</h4>
                    <p class="recommendation-card-text">{{ Str::limit($ad->description, 120) }}</p>

                    @if(!empty($ad->recommendation_reasons))
                    <div class="recommendation-reasons">
                        @foreach(collect($ad->recommendation_reasons)->take(2) as $reason)
                        <span class="recommendation-reason">
                            <i class="fas fa-check-circle"></i>{{ $reason }}
                        </span>
                        @endforeach
                    </div>
                    @endif

                    <div class="recommendation-card-footer">
                        <span>
                            <i class="fas fa-map-marker-alt me-1"></i>{{ Str::limit($ad->location ?? 'France', 28) }}
                        </span>
                        <span class="recommendation-card-price">
                            {{ $ad->price ? number_format($ad->price, 0, ',', ' ') . ' €' : 'Sur devis' }}
                        </span>
                    </div>
                @endif
            </div>
        </a>
        @endforeach
    </div>
</section>
@endif
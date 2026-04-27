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
        <a href="{{ route('ads.show', $ad->id) }}" class="recommendation-card">
            <div class="recommendation-card-meta">
                <span>
                    <i class="fas fa-folder-open me-1"></i>{{ $ad->category }}
                </span>
                <span>{{ $ad->created_at?->diffForHumans() }}</span>
            </div>

            <h4 class="recommendation-card-title">{{ Str::limit($ad->title, 72) }}</h4>
            <p class="recommendation-card-text">{{ Str::limit($ad->description, 120) }}</p>

            @if(!empty($ad->recommendation_reasons))
            <div class="recommendation-reasons">
                @foreach($ad->recommendation_reasons as $reason)
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
        </a>
        @endforeach
    </div>
</section>
@endif
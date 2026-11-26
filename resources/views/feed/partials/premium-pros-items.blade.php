{{-- Premium Professionals Items - AJAX Partial --}}
@if(isset($premiumPros) && $premiumPros->count() > 0)
    @foreach($premiumPros as $pro)
    <a href="{{ route('profile.public', $pro->id) }}" class="pro-card-large">
        <div class="pro-photo-container">
            @if($pro->avatar)
                <img src="{{ storage_url($pro->avatar) }}" alt="{{ $pro->name }}" class="pro-photo">
            @else
                <div class="pro-photo-placeholder">
                    <i class="fas fa-user"></i>
                </div>
            @endif
            @if($pro->hasActiveProSubscription())
            <div class="pro-badge-premium">
                <i class="fas fa-crown"></i> Premium
            </div>
            @endif
        </div>
        <div class="pro-card-info">
            <div class="pro-name-large">{{ Str::limit($pro->name, 18) }}</div>
            <div class="pro-meta">
                @if($pro->bio)
                    <span class="pro-specialty-text">{{ Str::limit($pro->bio, 30) }}</span>
                @endif
            </div>
            <div class="pro-rating-row">
                <div class="pro-stars">
                    <i class="fas fa-star"></i>
                    <span>{{ $pro->reviews_avg_rating ? number_format($pro->reviews_avg_rating, 1) : 'Nouveau' }}</span>
                </div>
                @if($pro->location_preference)
                <div class="pro-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>{{ Str::limit($pro->location_preference, 15) }}</span>
                </div>
                @endif
            </div>
        </div>
    </a>
    @endforeach
@else
    <div class="no-pros-message">
        <i class="fas fa-users-slash"></i>
        <p>Aucun professionnel disponible pour {{ $subcategory ?? $category ?? 'cette catégorie' }}</p>
    </div>
@endif

@extends('layouts.app')

@section('title', 'Tous les professionnels — Lunamars')

@push('styles')
<style>
    .pros-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    .pro-card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
    }
    .pro-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        color: inherit;
        text-decoration: none;
    }
    .pro-card-top {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 20px 20px 12px;
    }
    .pro-card-avatar {
        width: 64px;
        height: 64px;
        border-radius: 12px;
        object-fit: cover;
        flex-shrink: 0;
    }
    .pro-card-avatar-placeholder {
        width: 64px;
        height: 64px;
        border-radius: 12px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    .pro-card-identity {
        min-width: 0;
        flex: 1;
    }
    .pro-card-name {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pro-card-profession {
        font-size: 0.85rem;
        color: #6366f1;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pro-card-location {
        font-size: 0.78rem;
        color: #94a3b8;
        margin-top: 2px;
    }
    .pro-card-body {
        padding: 0 20px 16px;
        flex: 1;
    }
    .pro-card-bio {
        font-size: 0.84rem;
        color: #64748b;
        line-height: 1.45;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .pro-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 20px;
        border-top: 1px solid #f1f5f9;
        background: #fafbfc;
    }
    .pro-card-rating {
        color: #f59e0b;
        font-weight: 600;
        font-size: 0.88rem;
    }
    .pro-card-rating i { margin-right: 3px; }
    .pro-card-badge {
        font-size: 0.7rem;
        padding: 3px 10px;
        border-radius: 20px;
        font-weight: 600;
        color: white;
    }
    .pro-card-badge.premium { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
    .pro-card-badge.pro { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .pro-card-badge.provider { background: linear-gradient(135deg, #10b981, #059669); }
    .pro-card-stats {
        font-size: 0.78rem;
        color: #94a3b8;
    }
    .pros-filter-bar {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 24px;
    }
    .pros-filter-bar select {
        padding: 8px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.88rem;
        background: white;
        color: #334155;
        cursor: pointer;
    }
    .pros-empty {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }
    .pros-empty i {
        font-size: 3rem;
        margin-bottom: 16px;
        display: block;
        opacity: 0.4;
    }
    @media (max-width: 640px) {
        .pros-grid { grid-template-columns: 1fr; }
        .pros-page-header { padding: 24px 0 20px; }
        .pros-page-header h1 { font-size: 1.4rem; }
    }
</style>
@endpush

@section('content')
<div class="container" style="padding-top: 24px; padding-bottom: 60px;">
    <h1 style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 20px;">
        <i class="fas fa-users me-2" style="color: #6366f1;"></i>Tous les professionnels
    </h1>

    {{-- Barre de filtres --}}
    <div class="pros-filter-bar">
        <select id="proCategoryFilter" onchange="filterPros()">
            <option value="">Toutes les catégories</option>
            @foreach($categories as $catName => $catData)
                <option value="{{ $catName }}" {{ request('category') == $catName ? 'selected' : '' }}>{{ $catName }}</option>
            @endforeach
        </select>
    </div>

    {{-- Grille de pros --}}
    <div class="pros-grid" id="prosGrid">
        @forelse($premiumPros as $pro)
            <a href="{{ route('profile.public', $pro->id) }}" class="pro-card">
                <div class="pro-card-top">
                    @if($pro->avatar)
                        <img src="{{ storage_url($pro->avatar) }}" alt="{{ $pro->name }}" class="pro-card-avatar">
                    @else
                        <div class="pro-card-avatar-placeholder">{{ strtoupper(substr($pro->name, 0, 1)) }}</div>
                    @endif
                    <div class="pro-card-identity">
                        <div class="pro-card-name">{{ $pro->name }}</div>
                        @if($pro->profession)
                            <div class="pro-card-profession">{{ $pro->profession }}</div>
                        @elseif($pro->service_category)
                            <div class="pro-card-profession">{{ Str::limit($pro->service_category, 40) }}</div>
                        @endif
                        @if($pro->location_preference ?? ($pro->city ?? null))
                            <div class="pro-card-location"><i class="fas fa-map-marker-alt me-1"></i>{{ Str::limit($pro->location_preference ?? $pro->city, 30) }}</div>
                        @endif
                    </div>
                </div>
                <div class="pro-card-body">
                    @if($pro->bio)
                        <div class="pro-card-bio">{{ $pro->bio }}</div>
                    @endif
                </div>
                <div class="pro-card-footer">
                    <div class="pro-card-rating">
                        <i class="fas fa-star"></i>
                        {{ $pro->reviews_avg_rating ? number_format($pro->reviews_avg_rating, 1) : 'Nouveau' }}
                    </div>
                    <div>
                        @if($pro->hasActiveProSubscription())
                            <span class="pro-card-badge premium"><i class="fas fa-crown me-1"></i>Premium</span>
                        @elseif($pro->user_type === 'professionnel' || $pro->hasCompletedProOnboarding())
                            <span class="pro-card-badge pro"><i class="fas fa-briefcase me-1"></i>Pro</span>
                        @elseif($pro->is_service_provider)
                            <span class="pro-card-badge provider"><i class="fas fa-user-check me-1"></i>Prestataire</span>
                        @endif
                    </div>
                    <div class="pro-card-stats">{{ $pro->ads_count ?? 0 }} annonces</div>
                </div>
            </a>
        @empty
            <div class="pros-empty" style="grid-column: 1 / -1;">
                <i class="fas fa-users-slash"></i>
                <p>Aucun professionnel disponible pour le moment.</p>
                <a href="{{ route('feed') }}" class="btn btn-primary mt-3"><i class="fas fa-arrow-left me-2"></i>Retour au feed</a>
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
function filterPros() {
    const cat = document.getElementById('proCategoryFilter').value;
    const params = new URLSearchParams();
    if (cat) params.append('category', cat);
    window.location.href = '{{ route("feed.professionals") }}' + (params.toString() ? '?' + params.toString() : '');
}
</script>
@endpush

@extends('layouts.app')

@section('title', 'Annonces - ProxiPro')

@push('styles')
<style>
    * { font-family: 'Poppins', sans-serif; }
    
    .search-hero { background: #f8f9fa; padding: 20px 0; border-bottom: 1px solid #e9ecef; }
    .search-box { background: white; border-radius: 12px; padding: 15px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    .form-control-search { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 15px; font-size: 0.95rem; color: #1e293b; }
    .form-control-search:focus { background: white; box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1); border-color: #7c3aed; }
    .btn-search { background: #7c3aed; color: white; border-radius: 8px; padding: 10px 20px; font-weight: 500; font-size: 0.95rem; }
    .btn-search:hover { background: #6d28d9; color: white; }
    
    .content-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
    
    /* Removed sidebar styles */
    .filter-title { color: #2d3748; font-weight: 600; margin-bottom: 20px; }
    .filter-group { margin-bottom: 20px; }
    .filter-label { color: #718096; font-size: 0.9rem; margin-bottom: 8px; }
    .form-control-filter, .form-select-filter { background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 10px; color: #2d3748; padding: 10px 15px; }
    .form-control-filter:focus, .form-select-filter:focus { background: white; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124, 58, 237,0.15); color: #2d3748; }
    .form-select-filter option { background: white; color: #2d3748; }
    
    .category-chip { display: inline-block; padding: 8px 16px; background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 25px; color: #4a5568; font-size: 0.85rem; margin: 3px; cursor: pointer; transition: all 0.3s; text-decoration: none; }
    .category-chip:hover, .category-chip.active { background: #7c3aed; border-color: #7c3aed; color: white; }
    
    .results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
    .results-count { color: #2d3748; font-size: 1.1rem; }
    .results-count strong { color: #7c3aed; }
    
    .ad-card { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 18px; border: 1px solid rgba(0,0,0,0.05); overflow: hidden; transition: all 0.3s; height: 100%; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
    .ad-card:hover { transform: translateY(-5px); border-color: rgba(124, 58, 237,0.3); box-shadow: 0 15px 40px rgba(124, 58, 237,0.15); }
    .ad-card-image { height: 160px; background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
    .ad-card-image i { font-size: 50px; color: rgba(255,255,255,0.3); }
    .ad-card-image img { width: 100%; height: 100%; object-fit: cover; }
    .ad-badge { position: absolute; top: 12px; left: 12px; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .ad-badge-offre { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
    .ad-badge-demande { background: linear-gradient(135deg, #17a2b8, #6f42c1); color: white; }
    .ad-badge-boosted { position: absolute; top: 12px; right: 12px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .ad-badge-urgent { position: absolute; top: 12px; right: 12px; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; animation: urgentPulse 2s ease-in-out infinite; }
    @keyframes urgentPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
    .ad-card-body { padding: 20px; }
    .ad-card-category { display: inline-block; background: rgba(124, 58, 237,0.1); color: #7c3aed; padding: 4px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 500; margin-bottom: 10px; }
    .ad-card-title { color: #2d3748; font-weight: 600; font-size: 1rem; margin-bottom: 8px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .ad-card-location { color: #718096; font-size: 0.85rem; margin-bottom: 10px; }
    .ad-card-price { color: #28a745; font-weight: 700; font-size: 1.1rem; }
    .ad-card-footer { padding: 15px 20px; border-top: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
    .ad-card-user {
        color: #a0aec0;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .ad-card-user-avatar {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        flex-shrink: 0;
    }
    .ad-card-user-fallback {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #7c3aed, #9333ea);
        color: #fff;
        font-size: 0.72rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    .ad-card-user-name {
        line-height: 1.2;
    }
    .btn-view { background: linear-gradient(135deg, #7c3aed, #9333ea); color: white; border: none; border-radius: 10px; padding: 8px 16px; font-size: 0.85rem; font-weight: 500; }
    .btn-view:hover { color: white; box-shadow: 0 5px 15px rgba(124, 58, 237,0.4); }
    
    .empty-state { text-align: center; padding: 60px 20px; }
    .empty-state i { font-size: 80px; color: #cbd5e0; margin-bottom: 20px; }
    .empty-state h4 { color: #2d3748; margin-bottom: 10px; }
    .empty-state p { color: #718096; }
    
    .pagination { gap: 5px; }
    .page-link { background: #f7fafc; border: 1px solid #e2e8f0; color: #4a5568; border-radius: 10px !important; padding: 10px 15px; }
    .page-link:hover { background: white; color: #7c3aed; }
    .page-item.active .page-link { background: #7c3aed; border-color: #7c3aed; color: white; }
    
    @media (max-width: 992px) {
        .filters-sidebar { position: static; margin-bottom: 25px; }
    }

    @media (max-width: 768px) {
        .search-hero { padding: 14px 0; }
        .search-box { padding: 12px; border-radius: 10px; }
        .form-control-search { padding: 9px 12px; font-size: 0.88rem; }
        .btn-search { padding: 9px 16px; font-size: 0.88rem; }
        .content-container { padding: 16px 12px; }
        .results-header { margin-bottom: 18px; gap: 10px; }
        .results-count { font-size: 1rem; }
        .ad-card { border-radius: 14px; }
        .ad-card-image { height: 140px; }
        .ad-card-body { padding: 14px; }
        .ad-card-title { font-size: 0.92rem; }
        .ad-card-footer { padding: 12px 14px; }
        .category-chip { padding: 6px 12px; font-size: 0.8rem; }
        .empty-state { padding: 40px 16px; }
        .empty-state i { font-size: 60px; }
        .page-link { padding: 8px 12px; font-size: 0.88rem; }
    }

    @media (max-width: 576px) {
        .search-hero { padding: 10px 0; }
        .search-box { padding: 10px; }
        .form-control-search { padding: 8px 10px; font-size: 0.82rem; border-radius: 6px; }
        .btn-search { padding: 8px 14px; font-size: 0.82rem; border-radius: 6px; }
        .content-container { padding: 12px 8px; }
        .results-header { flex-direction: column; align-items: flex-start; gap: 8px; }
        .results-count { font-size: 0.92rem; }
        .ad-card { border-radius: 12px; }
        .ad-card-image { height: 130px; }
        .ad-card-image i { font-size: 36px; }
        .ad-badge { top: 8px; left: 8px; padding: 4px 10px; font-size: 0.7rem; }
        .ad-badge-boosted, .ad-badge-urgent { top: 8px; right: 8px; padding: 4px 10px; font-size: 0.7rem; }
        .ad-card-body { padding: 12px; }
        .ad-card-category { font-size: 0.7rem; padding: 3px 8px; }
        .ad-card-title { font-size: 0.88rem; }
        .ad-card-location { font-size: 0.8rem; }
        .ad-card-price { font-size: 1rem; }
        .ad-card-footer { padding: 10px 12px; }
        .ad-card-user { font-size: 0.75rem; gap: 6px; }
        .ad-card-user-avatar,
        .ad-card-user-fallback { width: 24px; height: 24px; }
        .btn-view { padding: 6px 12px; font-size: 0.8rem; border-radius: 8px; }
        .category-chip { padding: 5px 10px; font-size: 0.75rem; margin: 2px; }
        .empty-state { padding: 30px 12px; }
        .empty-state i { font-size: 50px; }
        .empty-state h4 { font-size: 1.05rem; }
        .empty-state p { font-size: 0.85rem; }
        .page-link { padding: 7px 10px; font-size: 0.82rem; }
    }

    @media (max-width: 420px) {
        .content-container { padding: 10px 6px; }
        .ad-card-image { height: 120px; }
        .ad-card-body { padding: 10px; }
        .ad-card-title { font-size: 0.85rem; }
        .ad-card-price { font-size: 0.95rem; }
        .ad-card-footer { padding: 8px 10px; flex-direction: column; gap: 8px; align-items: flex-start; }
    }
</style>
@endpush

@section('content')
    <!-- Search Section -->
    <div class="search-hero">
        <div class="container">
            <div class="search-box mx-auto" style="max-width: 1000px;">
                <form method="GET" action="{{ route('ads.index') }}">
                    <div class="row g-2 align-items-center">
                        <div class="col-lg-5">
                            <input type="text" class="form-control form-control-search" name="q" value="{{ request('q') }}" placeholder="🔍 Rechercher un service...">
                        </div>
                        <div class="col-lg-4">
                            <div class="input-group">
                                <input type="text" class="form-control form-control-search" name="location" value="{{ request('location') }}" placeholder="📍 Ville ou code postal">
                                <button type="button" class="btn btn-outline-secondary bg-white border-start-0" id="detectLocation" title="Ma position" style="border-top-right-radius: 8px; border-bottom-right-radius: 8px; border: 1px solid #e2e8f0;">
                                    <i class="fas fa-location-arrow text-secondary"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <button type="submit" class="btn btn-search w-100"><i class="fas fa-search me-2"></i>Rechercher</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="content-container">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-12 mb-4 d-none d-lg-block">
                 <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="{{ request()->fullUrlWithQuery(['category' => null]) }}" class="category-chip {{ !request('category') ? 'active' : '' }}">Tout</a>
                    @php
                        $filterCategories = array_merge(array_keys(config('categories.services')), array_keys(config('categories.marketplace')));
                    @endphp
                    @foreach($filterCategories as $cat)
                        <a href="{{ request()->fullUrlWithQuery(['category' => $cat]) }}" class="category-chip {{ request('category') == $cat ? 'active' : '' }}">{{ $cat }}</a>
                    @endforeach
                 </div>
            </div>

            <div class="col-lg-12">

                <div class="results-header">
                    <div class="results-count">
                        <strong>{{ $ads->total() }}</strong> annonces trouvées
                        @if(request('location'))
                            près de <strong>{{ request('location') }}</strong>
                        @endif
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-sort me-1"></i>Trier
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'newest']) }}">Plus récentes</a></li>
                            <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'price_low']) }}">Prix croissant</a></li>
                            <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'price_high']) }}">Prix décroissant</a></li>
                        </ul>
                    </div>
                </div>
                
                @if($ads->isEmpty())
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h4>Aucune annonce trouvée</h4>
                        <p>Essayez de modifier vos critères de recherche</p>
                        @auth
                            <a href="{{ route('ads.create') }}" class="btn btn-primary mt-3">
                                <i class="fas fa-plus me-2"></i>Publier une annonce
                            </a>
                        @endauth
                    </div>
                @else
                    <div class="row g-4">
                        @foreach($ads as $ad)
                            <div class="col-md-6 col-xl-4">
                                <div class="ad-card">
                                    <div class="ad-card-image">
                                        <span class="ad-badge {{ $ad->service_type == 'offre' ? 'ad-badge-offre' : 'ad-badge-demande' }}">
                                            {{ $ad->service_type == 'offre' ? 'Offre' : 'Demande' }}
                                        </span>
                                        @if($ad->is_urgent && $ad->urgent_until && $ad->urgent_until->isFuture())
                                            <span class="ad-badge-urgent">
                                                <i class="fas fa-fire me-1"></i>Urgent · {{ now()->diffInDays($ad->urgent_until, false) }}j
                                            </span>
                                        @elseif($ad->is_boosted && $ad->boost_end && $ad->boost_end->isFuture())
                                            <span class="ad-badge-boosted">
                                                <i class="fas fa-rocket me-1"></i>Boosté · {{ now()->diffInDays($ad->boost_end, false) }}j
                                            </span>
                                        @endif
                                        @if(!empty($ad->photos) && isset($ad->photos[0]))
                                            <img src="{{ storage_url($ad->photos[0]) }}" alt="Photo">
                                        @else
                                            <i class="fas fa-image"></i>
                                        @endif
                                    </div>
                                    <div class="ad-card-body">
                                        <span class="ad-card-category">{{ $ad->category }}</span>
                                        <h5 class="ad-card-title">{{ $ad->title }}</h5>
                                        <p class="ad-card-location"><i class="fas fa-map-marker-alt me-1"></i>{{ Str::limit($ad->location, 25) }}</p>
                                        <div class="ad-card-price">
                                            @if($ad->price)
                                                {{ number_format($ad->price, 2, ',', ' ') }} €
                                            @else
                                                Prix à discuter
                                            @endif
                                        </div>
                                    </div>
                                    <div class="ad-card-footer">
                                        <a href="{{ route('profile.public', $ad->user_id) }}" class="ad-card-user text-decoration-none">
                                            @if($ad->user && $ad->user->avatar)
                                                <img src="{{ storage_url($ad->user->avatar) }}" alt="{{ $ad->user->name ?? 'Utilisateur' }}" class="ad-card-user-avatar">
                                            @else
                                                <span class="ad-card-user-fallback">{{ strtoupper(substr($ad->user->name ?? 'U', 0, 1)) }}</span>
                                            @endif
                                            <span class="ad-card-user-name">{{ $ad->user->name ?? 'Anonyme' }}</span>
                                        </a>
                                        <div class="d-flex gap-2 align-items-center">
                                            <a href="{{ route('ads.show', $ad) }}" class="btn btn-view">Voir <i class="fas fa-arrow-right ms-1"></i></a>
                                            @auth
                                                @if(Auth::id() === $ad->user_id)
                                                    <a href="{{ route('ads.edit', $ad) }}" class="btn btn-outline-secondary btn-sm" title="Modifier"><i class="fas fa-edit"></i></a>
                                                    @if(!($ad->is_boosted && $ad->boost_end && $ad->boost_end->isFuture()) && !($ad->is_urgent && $ad->urgent_until && $ad->urgent_until->isFuture()))
                                                        <a href="{{ route('boost.show', $ad) }}" class="btn btn-warning btn-sm" title="Booster" style="color: white;"><i class="fas fa-rocket"></i></a>
                                                    @endif
                                                @endif
                                            @endauth
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    @if($ads->hasPages())
                        <div class="d-flex justify-content-center mt-5">
                            {{ $ads->withQueryString()->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.getElementById('detectLocation')?.addEventListener('click', function() {
        const btn = this;
        const input = document.querySelector('input[name="location"]');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(async (pos) => {
                try {
                    const res = await fetch(`/api/reverse-geocode?lat=${pos.coords.latitude}&lng=${pos.coords.longitude}`);
                    const data = await res.json();
                    input.value = data.city || data.address?.split(',')[0] || `${pos.coords.latitude.toFixed(4)}, ${pos.coords.longitude.toFixed(4)}`;
                } catch(e) {
                    input.value = `${pos.coords.latitude.toFixed(4)}, ${pos.coords.longitude.toFixed(4)}`;
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-location-arrow"></i>';
            }, () => {
                alert('Impossible de détecter votre position');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-location-arrow"></i>';
            });
        }
    });
</script>
@endsection

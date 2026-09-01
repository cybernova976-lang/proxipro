@extends('layouts.app')

@section('title', 'Annonces - Prokejem')

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
    
    /* Delimitation : un filet net plutot qu'une ombre diffuse. Une ombre large
       et pale ne dessine pas de bord — les cartes se fondaient les unes dans
       les autres, surtout quand deux annonces portent la meme photo. */
    .ad-card { background: #fff; border-radius: 18px; border: 1px solid #dde4ef; overflow: hidden; transition: border-color .18s, box-shadow .18s, transform .18s; height: 100%; display: flex; flex-direction: column; box-shadow: 0 1px 2px rgba(15,23,42,.05), 0 10px 24px -18px rgba(15,23,42,.45); }
    .ad-card:hover { transform: translateY(-3px); border-color: #93c5fd; box-shadow: 0 2px 6px rgba(15,23,42,.07), 0 18px 34px -20px rgba(37,99,235,.45); }

    /* En-tete : auteur a gauche, anciennete a droite. */
    .ad-card-head { display: flex; align-items: center; gap: 10px; padding: 11px 14px; border-bottom: 1px solid #eef2f7; background: #fff; }
    .ad-card-date { margin-left: auto; flex: none; color: #94a3b8; font-size: 0.75rem; white-space: nowrap; }
    .ad-card-image { height: 176px; background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
    .ad-card-image i { font-size: 50px; color: rgba(255,255,255,0.3); }
    .ad-card-image img { width: 100%; height: 100%; object-fit: cover; }
    .ad-badge { position: absolute; top: 12px; left: 12px; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .ad-badge-offre { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
    .ad-badge-demande { background: linear-gradient(135deg, #17a2b8, #6f42c1); color: white; }
    .ad-badge-boosted { position: absolute; top: 12px; right: 12px; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .ad-badge-urgent { position: absolute; top: 12px; right: 12px; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; animation: urgentPulse 2s ease-in-out infinite; }
    @keyframes urgentPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
    .ad-card-body { padding: 18px 20px 16px; flex: 0 0 auto; }
    .ad-card-category { display: inline-block; background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 600; margin-bottom: 10px; }
    .ad-card-title { color: #2d3748; font-weight: 600; font-size: 1rem; margin-bottom: 8px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .ad-card-meta { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px 14px; }
    .ad-card-location { color: #64748b; font-size: 0.85rem; margin: 0; min-width: 0; }
    .ad-card-price { color: #1d4ed8; font-weight: 750; font-size: 1.05rem; white-space: nowrap; }
    .ad-card-footer { margin-top: auto; padding: 12px 14px; border-top: 1px solid #eef2f7; display: flex; align-items: center; background: #fbfcfe; }
    .ad-card-user {
        color: #334155;
        font-size: 0.82rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
        flex: 1 1 auto;
    }
    .ad-card-user:hover .ad-card-user-name { color: #1d4ed8; }
    .ad-card-user-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        flex-shrink: 0;
    }
    .ad-card-user-fallback {
        width: 34px;
        height: 34px;
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
        min-width: 0;
        overflow-wrap: anywhere;
    }
    .ad-card-actions { display: flex; align-items: center; gap: 8px; width: 100%; }
    .ad-card-actions .btn-view { flex: 1; text-align: center; }
    .btn-view { background: #2563eb; color: white; border: none; border-radius: 10px; padding: 9px 14px; font-size: 0.82rem; font-weight: 700; white-space: nowrap; }
    .btn-view:hover { background: #1d4ed8; color: white; box-shadow: 0 5px 15px rgba(37,99,235,0.28); }
    
    .empty-state { text-align: center; padding: 60px 20px; }
    .empty-state i { font-size: 80px; color: #cbd5e0; margin-bottom: 20px; }
    .empty-state h4 { color: #2d3748; margin-bottom: 10px; }
    .empty-state p { color: #718096; }
    
    .ads-pagination-shell { margin-top: 32px; display: flex; flex-direction: column; align-items: center; gap: 12px; }
    .ads-pagination-summary { margin: 0; color: #64748b; font-size: 0.84rem; }
    .ads-pagination { display: flex; align-items: center; justify-content: center; gap: 7px; flex-wrap: wrap; }
    .ads-pagination__pages { display: flex; align-items: center; gap: 6px; }
    .ads-pagination__link,
    .ads-pagination__current,
    .ads-pagination__disabled { min-width: 40px; height: 40px; padding: 0 12px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #dbe3ef; text-decoration: none; font-size: 0.84rem; font-weight: 650; }
    .ads-pagination__link { background: #fff; color: #334155; }
    .ads-pagination__link:hover { border-color: #2563eb; color: #1d4ed8; background: #eff6ff; }
    .ads-pagination__current { background: #2563eb; border-color: #2563eb; color: #fff; }
    .ads-pagination__disabled { background: #f8fafc; color: #94a3b8; }
    .ads-pagination__ellipsis { color: #94a3b8; padding: 0 2px; }
    .ads-pagination__label { margin: 0 5px; }
    .ads-pagination__mobile-page { display: none; }
    
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
        .ads-pagination-shell { margin-top: 26px; }
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
        .ad-card { height: auto; }
        .ad-card-image { height: 178px; }
        .ad-card-image i { font-size: 36px; }
        .ad-badge { top: 8px; left: 8px; padding: 4px 10px; font-size: 0.7rem; }
        .ad-badge-boosted, .ad-badge-urgent { top: 8px; right: 8px; padding: 4px 10px; font-size: 0.7rem; }
        .ad-card-body { padding: 14px 15px 13px; }
        .ad-card-category { font-size: 0.7rem; padding: 3px 8px; }
        .ad-card-title { font-size: 0.88rem; }
        .ad-card-meta { align-items: baseline; }
        .ad-card-location { font-size: 0.8rem; }
        .ad-card-price { font-size: 1rem; }
        .ad-card-footer { padding: 11px 12px; gap: 9px; }
        .ad-card-user { font-size: 0.76rem; gap: 7px; }
        .ad-card-user-avatar,
        .ad-card-user-fallback { width: 34px; height: 34px; }
        .btn-view { padding: 8px 11px; font-size: 0.76rem; border-radius: 8px; }
        .category-chip { padding: 5px 10px; font-size: 0.75rem; margin: 2px; }
        .empty-state { padding: 30px 12px; }
        .empty-state i { font-size: 50px; }
        .empty-state h4 { font-size: 1.05rem; }
        .empty-state p { font-size: 0.85rem; }
        .ads-pagination-shell { margin-top: 22px; gap: 9px; }
        .ads-pagination-summary { font-size: 0.78rem; }
        .ads-pagination { width: 100%; display: grid; grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); }
        .ads-pagination__pages { display: none; }
        .ads-pagination__link,
        .ads-pagination__current,
        .ads-pagination__disabled { min-width: 0; height: 42px; padding: 0 10px; }
        .ads-pagination > :last-child { justify-self: stretch; }
        .ads-pagination > :first-child { justify-self: stretch; }
        .ads-pagination__mobile-page { display: inline; color: #64748b; font-size: 0.78rem; font-weight: 650; white-space: nowrap; }
        .ads-pagination > .ads-pagination__link,
        .ads-pagination > .ads-pagination__disabled { width: 100%; }
    }

    @media (max-width: 420px) {
        .content-container { padding: 10px 6px; }
        .ad-card-image { height: 164px; }
        .ad-card-body { padding: 12px; }
        .ad-card-title { font-size: 0.85rem; }
        .ad-card-price { font-size: 0.95rem; }
        .ad-card-footer { padding: 10px; }
        .ad-card-user-name { font-size: 0.73rem; }
        .btn-view { padding-inline: 10px; }
    }

    /* Le layout commun contient aussi d'anciens styles `.ad-card`.
       Ce périmètre garantit que la liste d'annonces garde son propre rendu. */
    .ads-index-page .ad-card {
        background: #fff;
        border: 1px solid #dde4ef;
        border-radius: 18px;
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15,23,42,.05), 0 10px 24px -18px rgba(15,23,42,.45);
    }
    .ads-index-page .ad-card-head { display: flex; align-items: center; gap: 10px; padding: 11px 14px; border-bottom: 1px solid #eef2f7; }
    .ads-index-page .ad-card-image {
        height: 176px;
        margin: 0;
        border-radius: 0;
        background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
    }
    .ads-index-page .ad-card-body { padding: 18px 20px 16px; }
    .ads-index-page .ad-card-category { color: #2563eb; font-weight: 600; margin-bottom: 10px; }
    .ads-index-page .ad-card-title { font-size: 1rem; margin-bottom: 8px; }
    .ads-index-page .ad-card-price { color: #1d4ed8; font-weight: 750; }
    .ads-index-page .ad-card-footer {
        display: flex;
        align-items: center;
        margin-top: auto;
        padding: 12px 14px;
        background: #fbfcfe;
        border-top: 1px solid #eef2f7;
    }

    @media (max-width: 576px) {
        .ads-index-page .ad-card { height: auto; border-radius: 12px; }
        .ads-index-page .ad-card-image { height: 178px; }
        .ads-index-page .ad-card-body { padding: 14px 15px 13px; }
        .ads-index-page .ad-card-title { font-size: 0.88rem; }
        .ads-index-page .ad-card-footer { gap: 9px; padding: 11px 12px; }
    }

    @media (max-width: 420px) {
        .ads-index-page .ad-card-image { height: 164px; }
        .ads-index-page .ad-card-body { padding: 12px; }
        .ads-index-page .ad-card-footer { padding: 10px; }
    }

    /* Sur telephone, les cartes s'enchainent en une seule colonne : on ecarte
       davantage pour que le fond respire visiblement entre deux annonces. */
    @media (max-width: 576px) {
        .ads-index-page .content-container > .row,
        .ads-index-page .content-container .row.g-4 { margin-left: 0; margin-right: 0; }
        .ads-index-page .content-container > .row > *,
        .ads-index-page .content-container .row.g-4 > * { padding-left: 0; padding-right: 0; }
        .ads-index-page .row.g-4 { --bs-gutter-y: 1.75rem; }
        .ads-index-page .ad-card-head { padding: 10px 12px; gap: 8px; }
        .ads-index-page .ad-card-date { font-size: 0.72rem; }
    }
</style>
@endpush

@section('content')
<div class="ads-index-page">
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
                        $filterCategories = array_keys(\App\Support\MarketplaceCategoryRegistry::enabledAll());
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
                                <article class="ad-card">
                                    {{-- L'auteur en tete : c'est ce qui distingue une carte
                                         de la suivante quand les photos se ressemblent. --}}
                                    <div class="ad-card-head">
                                        <a href="{{ route('profile.public', $ad->user_id) }}" class="ad-card-user text-decoration-none" title="Voir le profil de {{ $ad->user->name ?? 'cet utilisateur' }}">
                                            @if($ad->user && $ad->user->avatar)
                                                <img src="{{ storage_url($ad->user->avatar) }}" alt="" class="ad-card-user-avatar" loading="lazy">
                                            @else
                                                <span class="ad-card-user-fallback">{{ strtoupper(substr($ad->user->name ?? 'U', 0, 1)) }}</span>
                                            @endif
                                            <span class="ad-card-user-name">{{ $ad->user->name ?? 'Anonyme' }}</span>
                                        </a>
                                        @if($ad->created_at)
                                            <span class="ad-card-date">{{ $ad->created_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('ads.show', $ad) }}" class="ad-card-image" aria-label="Voir l’annonce : {{ $ad->title }}">
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
                                            <img src="{{ storage_url($ad->photos[0]) }}" alt="Photo de l’annonce {{ $ad->title }}">
                                        @else
                                            <i class="fas fa-image"></i>
                                        @endif
                                    </a>
                                    <div class="ad-card-body">
                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                            <span class="ad-card-category mb-0">{{ $ad->category }}</span>
                                            @if($isMyAds)
                                                @php
                                                    $publicationExpired = $ad->status === 'expired'
                                                        || ($ad->expires_at && $ad->expires_at->isPast());
                                                    $publicationStatus = $publicationExpired
                                                        ? ['label' => 'Expirée', 'class' => 'bg-secondary']
                                                        : ($ad->status === 'active'
                                                            ? ['label' => 'Active', 'class' => 'bg-success']
                                                            : ['label' => 'Archivée', 'class' => 'bg-light text-dark border']);
                                                @endphp
                                                <span class="badge {{ $publicationStatus['class'] }}">{{ $publicationStatus['label'] }}</span>
                                            @endif
                                        </div>
                                        <h5 class="ad-card-title">{{ $ad->title }}</h5>
                                        <div class="ad-card-meta">
                                            <p class="ad-card-location"><i class="fas fa-map-marker-alt me-1"></i>{{ Str::limit($ad->location, 25) }}</p>
                                            <div class="ad-card-price">{{ $ad->formatted_price }}</div>
                                        </div>
                                    </div>
                                    <div class="ad-card-footer">
                                        <div class="ad-card-actions">
                                            <a href="{{ route('ads.show', $ad) }}" class="btn btn-view">Voir l’annonce <i class="fas fa-arrow-right ms-1"></i></a>
                                            @auth
                                                @if(Auth::id() === $ad->user_id)
                                                    <a href="{{ route('ads.edit', $ad) }}" class="btn btn-outline-secondary btn-sm" title="Modifier"><i class="fas fa-edit"></i></a>
                                                    @if($isMyAds && (($publicationExpired ?? false) || $ad->status !== 'active'))
                                                        <form action="{{ route('ads.republish', $ad) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-success btn-sm" title="Republier"><i class="fas fa-redo"></i></button>
                                                        </form>
                                                    @elseif($isMyAds)
                                                        <form action="{{ route('ads.archive', $ad) }}" method="POST" class="d-inline" onsubmit="return confirm('Archiver cette annonce ?');">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Archiver"><i class="fas fa-box-archive"></i></button>
                                                        </form>
                                                    @elseif(!($ad->is_boosted && $ad->boost_end && $ad->boost_end->isFuture()) && !($ad->is_urgent && $ad->urgent_until && $ad->urgent_until->isFuture()))
                                                        <a href="{{ route('boost.show', $ad) }}" class="btn btn-warning btn-sm" title="Booster" style="color: white;"><i class="fas fa-rocket"></i></a>
                                                    @endif
                                                @endif
                                            @endauth
                                        </div>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>
                    
                    @if($ads->hasPages())
                        @include('ads.partials.pagination', ['paginator' => $ads->withQueryString()])
                    @endif
                @endif
            </div>
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

@extends('layouts.app')

@section('title', 'Accueil - Lunamars')

@push('styles')
<style>
    /* =========================================
       PAGE TEST - ARCHITECTURE MODERNE
       ========================================= */
    :root {
        /* 🎨 Palette ServicesPro */
        --primary: #3a86ff;
        --primary-dark: #2667cc;
        --primary-hover: #2667cc;
        --primary-light: rgba(58, 134, 255, 0.1);
        --secondary: #8338ec;
        --accent: #ffbe0b;
        
        /* Funds */
        --bg-body: #f8fafc;
        --text-dark: #1e293b;
        --text-light: #64748b;
        --white: #ffffff;
        --border: #e2e8f0;
    }

    body {
        background-color: var(--bg-body);
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }

    /* =========================================
       HEADER STYLES RESTORATION (Pour "Ne pas toucher au header")
       ========================================= */
    .header-modern {
        background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%) !important;
        box-shadow: 0 2px 20px -4px rgba(58, 134, 255, 0.15), 0 4px 12px -2px rgba(0, 0, 0, 0.08) !important;
        border-bottom: 1px solid rgba(58, 134, 255, 0.1) !important;
        /* height: 90px !important; REMOVED to match standard nav */
        z-index: 1001 !important;
    }
    .header-modern .container-fluid {
        max-width: 1600px !important;
        margin: 0 auto;
        padding: 0 32px !important;
    }
    .header-modern .navbar-brand-modern .brand-logo {
        width: 42px; height: 42px; font-size: 1.2rem;
        box-shadow: 0 4px 15px rgba(58, 134, 255, 0.35);
    }
    .header-modern .navbar-brand-modern .brand-text { font-size: 1.4rem; }
    
    /* Search bar styles removed */
    
    .nav-link-modern {
        font-weight: 500; color: #6c757d; padding: 8px 14px; border-radius: 10px;
        display: flex; align-items: center; gap: 6px; text-decoration: none;
    }
    .nav-link-modern:hover { color: var(--text-dark); background: rgba(58, 134, 255, 0.05); }
    .nav-link-modern.active { color: var(--primary); background: rgba(58, 134, 255, 0.1); font-weight: 600; }
    .nav-link-lost { color: #ea580c !important; border: 1px solid #fed7aa; background: #fff7ed; }
    .lost-badge { background: #ea580c; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.65rem; margin-left: 4px; }
    
    /* =========================================
       NEW HERO & CONTENT STYLES
       ========================================= */
    
    /* HERO SECTION */
    .hero-section-v2 {
        background-color: #312e81; /* Indigo 900 */
        padding-top: 1rem !important; /* Reduced top padding */
        padding-bottom: 7rem !important; /* Adjusted bottom for overlap */
        position: relative;
        overflow: hidden;
    }

    /* Background Shapes */
    .hero-shape-1 {
        position: absolute; top: 0; right: 0; width: 24rem; height: 24rem;
        border-radius: 9999px; background-color: #3730a3; opacity: 0.5;
        filter: blur(64px); transform: translate(5rem, -5rem);
    }
    .hero-shape-2 {
        position: absolute; bottom: 0; left: 0; width: 20rem; height: 20rem;
        border-radius: 9999px; background-color: #701a75; opacity: 0.3;
        filter: blur(64px); transform: translate(-5rem, 5rem);
    }
    
    /* Hero Search Bar (Larger & More Prominent) */
    .hero-search-wrapper {
        background: white; border-radius: 16px; padding: 8px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        max-width: 800px; margin: 0 auto;
        display: flex; gap: 10px; flex-wrap: wrap;
    }
    @media (min-width: 768px) { .hero-search-wrapper { flex-wrap: nowrap; } }
    
    .hero-input-group {
        flex: 1; position: relative; min-width: 200px;
    }
    .hero-input-group input {
        width: 100%; height: 56px; border: none; background: transparent;
        padding-left: 48px; font-size: 1.05rem; outline: none; border-radius: 12px;
    }
    .hero-input-group input:focus { background: #f8fafc; }
    .hero-input-group i {
        position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
        font-size: 1.2rem; color: #94a3b8;
    }
    
    .hero-search-btn {
        background: var(--primary); color: white; font-weight: 700;
        border: none; border-radius: 12px; padding: 0 32px; height: 56px;
        font-size: 1.1rem; cursor: pointer; transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
    }
    .hero-search-btn:hover { background: var(--primary-dark); transform: translateY(-2px); }

    /* Trust Badges */
    .trust-badges {
        margin-top: 2rem; display: flex; justify-content: center; gap: 2rem;
        color: #e0e7ff; font-weight: 500; font-size: 0.95rem;
    }
    .trust-badges i { color: #34d399; margin-right: 8px; }

    /* CATEGORIES FLOATING GRID */
    .categories-floating-v2 {
        margin-top: -60px !important; /* Pull closer to header */
        position: relative; z-index: 10;
        margin-bottom: 2rem;
    }
    .cat-card-modern {
        background: white; border-radius: 16px; padding: 24px;
        text-align: center; border: 1px solid #f1f5f9;
        box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        transition: all 0.3s; height: 100%;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        text-decoration: none; color: var(--text-dark);
        position: relative; overflow: hidden;
    }
    .cat-card-modern:hover, .cat-card-modern.active {
        transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        border-color: var(--primary);
    }
    .cat-card-modern.active { background-color: #f0f9ff; border: 2px solid var(--primary); }
    .cat-card-modern i { font-size: 2.5rem; margin-bottom: 12px; transition: transform 0.3s; }
    .cat-card-modern:hover i { transform: scale(1.1); }
    .cat-card-modern span { font-weight: 700; font-size: 1.05rem; }
    
    .category-count-badge {
        position: absolute; top: 10px; left: 10px;
        font-size: 0.75rem; font-weight: 800;
        color: var(--text-light); background: #f1f5f9;
        padding: 4px 10px; border-radius: 20px;
        transition: all 0.3s;
    }
    .cat-card-modern:hover .category-count-badge,
    .cat-card-modern.active .category-count-badge {
        background: var(--primary); color: white;
    }

    /* SUBCATEGORIES BAR */
    .subcat-bar {
        background: #ffffff; 
        border-bottom: 1px solid #e2e8f0; 
        padding: 20px 0;
        margin-bottom: 40px; 
        white-space: nowrap; 
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        position: relative;
        z-index: 20; /* Ensure visual priority */
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); /* Slight shadow to prove visibility */
        display: block !important; /* Force display if condition met */
    }
    .subcat-pill {
        display: inline-block; padding: 8px 16px; border-radius: 50px;
        background: #f1f5f9; color: var(--text-dark); font-weight: 500;
        margin-right: 10px; text-decoration: none; transition: all 0.2s;
        border: 1px solid transparent;
    }
    .subcat-pill:hover, .subcat-pill.active {
        background: var(--primary); color: white;
        box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
    }

    /* ADVERTISEMENT SPACES */
    .ad-space-banner {
        width: 100%; height: 120px;
        background: linear-gradient(45deg, #f3f4f6 25%, #e5e7eb 25%, #e5e7eb 50%, #f3f4f6 50%, #f3f4f6 75%, #e5e7eb 75%, #e5e7eb 100%);
        background-size: 20px 20px;
        border-radius: 12px; display: flex; align-items: center; justify-content: center;
        color: #9ca3af; font-weight: 600; letter-spacing: 1px;
        border: 2px dashed #d1d5db; margin: 2rem 0;
        position: relative; overflow: hidden;
    }
    .ad-label {
        position: absolute; top: 5px; right: 8px; font-size: 0.65rem;
        background: rgba(0,0,0,0.1); padding: 2px 6px; border-radius: 4px;
    }

    /* LISTINGS HORIZONTAL CAROUSEL */
    .listings-carousel-section {
        position: relative;
        margin-bottom: 2rem;
    }
    .listings-carousel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    .listings-carousel-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .listings-carousel-container {
        flex: 1;
        overflow: hidden;
        border-radius: 16px;
    }
    .listings-carousel-track {
        display: flex;
        gap: 20px;
        transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        will-change: transform;
    }
    .listings-nav-btn {
        flex-shrink: 0;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        border: 2px solid #e2e8f0;
        background: white;
        color: #475569;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        z-index: 10;
    }
    .listings-nav-btn:hover {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
        transform: scale(1.1);
        box-shadow: 0 8px 25px rgba(58, 134, 255, 0.35);
    }
    .listings-nav-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        transform: none;
    }

    /* LISTING CARD - Horizontal version */
    .listing-card {
        background: white; border-radius: 16px; overflow: hidden;
        border: 1px solid #f1f5f9; transition: all 0.3s;
        min-width: 280px;
        max-width: 280px;
        flex-shrink: 0;
        display: flex; flex-direction: column;
    }
    .listing-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.08);
    }
    .listing-img-box {
        height: 160px; background: #e2e8f0; position: relative;
        overflow: hidden;
    }
    .listing-img-box img { width: 100%; height: 100%; object-fit: cover; }
    .listing-badge {
        position: absolute; top: 12px; left: 12px;
        background: rgba(255,255,255,0.9); padding: 4px 10px;
        border-radius: 8px; font-size: 0.75rem; font-weight: 700;
        color: var(--primary); box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .listing-body { padding: 16px; flex: 1; display: flex; flex-direction: column; }
    .listing-title { font-size: 1rem; font-weight: 700; margin-bottom: 6px; color: var(--text-dark); line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .listing-meta { font-size: 0.8rem; color: var(--text-light); margin-bottom: 10px; display: flex; gap: 8px; align-items: center; }
    .listing-price { font-size: 1.1rem; font-weight: 800; color: var(--primary); margin-top: auto; }
    .listing-footer {
        padding: 10px 16px; border-top: 1px solid #f1f5f9; background: #f8fafc;
        display: flex; justify-content: space-between; align-items: center;
    }
    .user-mini { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 600; color: #475569; }
    .user-avatar-sm { width: 26px; height: 26px; border-radius: 50%; background: #cbd5e1; }

    /* PROFESSIONALS SECTION - Dynamic */
    .pros-section {
        background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        border-radius: 20px;
        padding: 2rem;
        margin-top: 1.5rem;
        border: 1px solid #e0e7ff;
    }
    .pros-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .pros-section-title {
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--text-dark);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .pros-section-title i {
        color: var(--primary);
        font-size: 1.2rem;
    }
    .pros-carousel-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .pros-carousel-container {
        flex: 1;
        overflow: hidden;
    }
    .pros-carousel-track {
        display: flex;
        gap: 16px;
        transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    .pro-card-compact {
        flex-shrink: 0;
        width: 160px;
        background: white;
        border-radius: 16px;
        padding: 20px 16px;
        text-align: center;
        border: 2px solid transparent;
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
        display: block;
    }
    .pro-card-compact:hover {
        border-color: var(--primary);
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(58, 134, 255, 0.15);
    }
    .pro-avatar-large {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin: 0 auto 12px;
        overflow: hidden;
        border: 3px solid #e0e7ff;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        position: relative;
    }
    .pro-avatar-large img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .pro-avatar-large .placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: 700;
    }
    .pro-card-compact .pro-name {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pro-card-compact .pro-rating {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        font-size: 0.8rem;
        color: #f59e0b;
        margin-bottom: 8px;
    }
    .pro-card-compact .pro-cta {
        font-size: 0.75rem;
        color: var(--primary);
        font-weight: 600;
    }

    /* SECTION TITLES */
    .modern-title-block { text-align: center; margin-bottom: 3rem; }
    .modern-subtitle {
        color: var(--primary); font-weight: 700; text-transform: uppercase;
        letter-spacing: 1px; font-size: 0.85rem; display: block; margin-bottom: 8px;
    }
    .modern-title { font-size: 2rem; font-weight: 800; color: var(--text-dark); margin: 0; }

    /* STEPS */
    .step-box {
        text-align: center; padding: 20px;
    }
    .step-icon-circle {
        width: 80px; height: 80px; border-radius: 24px;
        display: flex; align-items: center; justify-content: center;
        font-size: 32px; margin: 0 auto 20px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }

    /* CTA BLOCK */
    .pro-cta-block {
        background: #1e293b; border-radius: 24px; overflow: hidden;
        position: relative; color: white;
    }
    .pro-cta-content { padding: 48px; z-index: 2; position: relative; }
    .pro-list li { margin-bottom: 12px; display: flex; align-items: center; gap: 10px; color: #cbd5e1; }
    .pro-list i { color: #34d399; }
</style>
@endpush

@section('content')

    <!-- HERO SECTION (SOUS HEADER) -->
    <div class="hero-section-v2">
        <div class="hero-shape-1"></div>
        <div class="hero-shape-2"></div>

        <div class="container position-relative" style="z-index: 2; text-align: center;">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Personalized Greeting -->
                    <h1 class="text-white fw-bold mb-1 display-6 text-nowrap">
                        Bonjour <span style="color: #60a5fa;">{{ explode(' ', Auth::user()->name ?? 'Vous')[0] }}</span>, que recherchez-vous ?
                    </h1>
                    
                    <p class="text-indigo-200 mb-0 fs-6" style="color: #c7d2fe;">
                        Explorez les services et offres disponibles autour de vous
                    </p>
                </div>
            </div>
        </div>
    </div>

    @php
        $defaultCategory = array_key_first($missionCategories);
        $defaultSubcategories = $defaultCategory ? ($missionCategories[$defaultCategory]['subs'] ?? []) : [];
    @endphp

    <!-- MAIN CONTAINER - CATEGORIES -->
    <div class="container categories-floating-v2">
        <div class="row g-4">
            @foreach($missionCategories as $catName => $catData)
            @php 
                $isActive = request('category') == $catName;
            @endphp
            <div class="col-6 col-md-4 col-lg-2">
                <a href="javascript:void(0)" onclick="filterByCategory('{{ $catName }}')" class="cat-card-modern {{ $isActive ? 'active' : '' }}" data-category="{{ $catName }}">
                    <span class="category-count-badge">{{ $catData['total'] ?? 0 }}</span>
                    <i class="{{ $catData['icon'] }}" style="color: {{ $catData['color'] }};"></i>
                    <span>{{ $catName }}</span>
                </a>
            </div>
            @endforeach
        </div>
    </div>


    <div class="container pb-5">
        
        <!-- SUBCATEGORIES SECTION - Dynamic via AJAX -->
        <div id="subcategories-container" class="mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
                <div class="card-body bg-white p-4">
                    <h5 class="fw-bold mb-3 text-secondary">
                        <i class="fas fa-filter me-2"></i>Affiner votre recherche : 
                        <span id="current-category-name">{{ $defaultCategory ?? 'Toutes les catégories' }}</span>
                    </h5>
                    <div id="subcategories-list" class="d-flex flex-wrap gap-2">
                        <button onclick="filterBySubcategory('')" class="btn btn-light rounded-pill border subcat-pill bg-primary text-white border-primary" data-subcategory="all">
                            Tout voir
                        </button>
                        @forelse($defaultSubcategories as $sub)
                            <button onclick="filterBySubcategory('{{ $sub['name'] }}')" class="btn btn-light rounded-pill border subcat-pill" data-subcategory="{{ $sub['name'] }}">
                                <i class="{{ $sub['icon'] }} me-1"></i> {{ $sub['name'] }}
                                @if(!empty($sub['count']))
                                    <span class="badge bg-secondary ms-1">{{ $sub['count'] }}</span>
                                @endif
                            </button>
                        @empty
                            <span class="text-muted small">Sélectionnez une catégorie pour découvrir les sous-catégories disponibles.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- LISTINGS SECTION - Horizontal Carousel -->
        <div class="listings-carousel-section" id="listings-section">
            <div class="listings-carousel-header">
                <div>
                   <h2 class="fw-bold mb-1" id="section-title">Les dernières pépites</h2>
                   <p class="text-muted mb-0" id="section-subtitle">Découvrez les meilleures opportunités du moment</p>
                </div>
            </div>

            <!-- Loading indicator -->
            <div id="ads-loading" style="display: none;" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-3 text-muted">Chargement des annonces...</p>
            </div>

            <!-- Ads Horizontal Carousel -->
            <div class="listings-carousel-wrapper">
                <button class="listings-nav-btn" onclick="scrollListings(-1)" id="listings-prev-btn">
                    <i class="fas fa-chevron-left"></i>
                </button>
                
                <div class="listings-carousel-container">
                    <div class="listings-carousel-track" id="ads-carousel-track">
                        @forelse($ads as $ad)
                        <div class="listing-card">
                            <div class="listing-img-box">
                                @if($ad->photos && is_array($ad->photos) && count($ad->photos) > 0)
                                    <img src="{{ storage_url($ad->photos[0]) }}" alt="{{ $ad->title }}">
                                @elseif($ad->photos && is_string($ad->photos))
                                    <img src="{{ storage_url(json_decode($ad->photos)[0] ?? $ad->photos) }}" alt="{{ $ad->title }}">
                                @else
                                    <div class="d-flex align-items-center justify-content-center h-100 bg-light text-muted">
                                        <i class="fas fa-image fa-2x"></i>
                                    </div>
                                @endif
                                <span class="listing-badge">{{ $ad->category }}</span>
                            </div>
                            <div class="listing-body">
                                <h3 class="listing-title" title="{{ $ad->title }}">{{ $ad->title }}</h3>
                                <div class="listing-meta">
                                    <span><i class="fas fa-map-marker-alt text-primary"></i> {{ $ad->location ?? 'France' }}</span>
                                </div>
                                <div class="listing-price">
                                    {{ $ad->price ? number_format($ad->price, 0, ',', ' ') . ' €' : 'Sur devis' }}
                                </div>
                            </div>
                            <div class="listing-footer">
                                <div class="user-mini">
                                    <div class="user-avatar-sm" style="background-image: url('{{ $ad->user?->avatar ? storage_url($ad->user->avatar) : '' }}'); background-size: cover;"></div>
                                    <span>{{ Str::limit($ad->user?->name ?? 'Utilisateur', 12) }}</span>
                                </div>
                                <a href="{{ route('ads.show', $ad->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">Voir</a>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-5 w-100">
                            <i class="fas fa-inbox fa-3x text-muted mb-3 opacity-25"></i>
                            <h5 class="fw-bold">Aucune annonce disponible</h5>
                            <p class="text-muted">Essayez une autre catégorie.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
                
                <button class="listings-nav-btn" onclick="scrollListings(1)" id="listings-next-btn">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- PROFESSIONALS SECTION -->
        @include('feed.partials.premium-pros-carousel')

        <!-- AD SPACE 2 -->
        <div class="row mb-5">
            <div class="col-md-6">
                <div class="ad-space-banner" style="height: 250px; background: #e0e7ff; color: #4338ca; border-color: #a5b4fc;">
                    <span class="ad-label">Sponsorisé</span>
                    <div class="text-center">
                        <i class="fas fa-star fa-2x mb-2"></i><br>
                        VOTRE PUB ICI
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="ad-space-banner" style="height: 250px; background: #fae8ff; color: #86198f; border-color: #f0abfc;">
                    <span class="ad-label">Partenaire</span>
                     <div class="text-center">
                        <i class="fas fa-heart fa-2x mb-2"></i><br>
                        OFFRE SPECIALE
                    </div>
                </div>
            </div>
        </div>
        
        <!-- HOW IT WORKS -->
        <div class="mb-5 py-5">
            <div class="modern-title-block">
                <span class="modern-subtitle">SIMPLICITÉ & EFFICACITÉ</span>
                <h2 class="modern-title">Comment ça marche ?</h2>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="step-box">
                        <div class="step-icon-circle" style="background: #eff6ff; color: #3b82f6;">
                            <i class="fas fa-pen-fancy"></i>
                        </div>
                        <h3 class="h4 fw-bold">1. Publiez</h3>
                        <p class="text-muted">Décrivez votre besoin en quelques clics. C'est simple et gratuit.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-box">
                        <div class="step-icon-circle" style="background: #fdf4ff; color: #d946ef;">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 class="h4 fw-bold">2. Discutez</h3>
                        <p class="text-muted">Recevez des offres et échangez avec les prestataires disponibles.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-box">
                        <div class="step-icon-circle" style="background: #ecfdf5; color: #10b981;">
                            <i class="fas fa-check"></i>
                        </div>
                        <h3 class="h4 fw-bold">3. Réservez</h3>
                        <p class="text-muted">Choisissez le meilleur profil et validez la mission en toute sécurité.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- PROMO BANNER -->
        <div class="pro-cta-block">
            <div class="row g-0 align-items-center">
                <div class="col-lg-6">
                    <div class="pro-cta-content">
                        <span class="badge bg-indigo-500 bg-opacity-25 text-indigo-200 border border-indigo-500 mb-3">Professionnels</span>
                        <h2 class="fw-bold mb-4">Boostez votre activité</h2>
                        <ul class="list-unstyled pro-list">
                            <li><i class="fas fa-check-circle"></i> Accès à des milliers de clients</li>
                            <li><i class="fas fa-check-circle"></i> Gestion simplifiée de vos devis</li>
                            <li><i class="fas fa-check-circle"></i> Paiement garanti sous 48h</li>
                        </ul>
                        <button class="btn btn-light fw-bold px-4 py-3 mt-3 shadow">Devenir prestataire</button>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <div style="height: 100%; min-height: 300px; background: linear-gradient(45deg, #1e293b, #334155); display: flex; align-items: center; justify-content: center;">
                         <i class="fas fa-briefcase" style="font-size: 150px; opacity: 0.1; color: white;"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
    // Current state
    let currentCategory = '{{ request("category", "all") }}';
    let currentSubcategory = '{{ request("search", "") }}';
    const defaultCategoryData = @json([
        'name' => $defaultCategory,
        'subcategories' => $defaultSubcategories
    ]);

    // Carousel positions
    let listingsScrollPos = 0;
    let prosScrollPos = 0;

    // Expose filterAds globally for empty state button
    window.filterAds = filterAds;

    /**
     * Scroll listings carousel
     */
    function scrollListings(direction) {
        const track = document.getElementById('ads-carousel-track');
        if (!track) return;
        
        const cardWidth = 300; // card width + gap
        const containerWidth = track.parentElement.offsetWidth;
        const maxScroll = Math.max(0, track.scrollWidth - containerWidth);
        
        listingsScrollPos += direction * cardWidth * 2;
        listingsScrollPos = Math.max(0, Math.min(listingsScrollPos, maxScroll));
        
        track.style.transform = `translateX(-${listingsScrollPos}px)`;
        
        // Update button states
        document.getElementById('listings-prev-btn').disabled = listingsScrollPos <= 0;
        document.getElementById('listings-next-btn').disabled = listingsScrollPos >= maxScroll;
    }

    /**
     * Scroll professionals carousel
     */
    function scrollPros(direction) {
        const track = document.getElementById('pros-carousel-track');
        if (!track) return;
        
        const cardWidth = 176; // card width + gap
        const containerWidth = track.parentElement.offsetWidth;
        const maxScroll = Math.max(0, track.scrollWidth - containerWidth);
        
        prosScrollPos += direction * cardWidth * 3;
        prosScrollPos = Math.max(0, Math.min(prosScrollPos, maxScroll));
        
        track.style.transform = `translateX(-${prosScrollPos}px)`;
        
        // Update button states
        document.getElementById('pros-prev-btn').disabled = prosScrollPos <= 0;
        document.getElementById('pros-next-btn').disabled = prosScrollPos >= maxScroll;
    }

    /**
     * Update professionals section title based on subcategory AND load filtered professionals
     */
    function updateProsTitle(subcategory) {
        // Update title via the global function from the carousel component
        if (typeof window.updateProsSectionTitle === 'function') {
            if (subcategory) {
                window.updateProsSectionTitle(subcategory);
            } else if (currentCategory && currentCategory !== 'all') {
                window.updateProsSectionTitle(currentCategory);
            } else {
                window.updateProsSectionTitle('');
            }
        }
        
        // Load professionals filtered by category/subcategory
        if (typeof window.loadProfessionalsByCategory === 'function') {
            const cat = (currentCategory && currentCategory !== 'all') ? currentCategory : '';
            const sub = subcategory || '';
            window.loadProfessionalsByCategory(cat, sub);
        }
    }

    /**
     * Filter ads by category (main categories)
     */
    function filterByCategory(category) {
        currentCategory = category;
        currentSubcategory = '';
        
        // Update active state on category cards
        document.querySelectorAll('.cat-card-modern').forEach(card => {
            card.classList.remove('active');
            if (card.dataset.category === category) {
                card.classList.add('active');
            }
        });

        // Load subcategories if not "all"
        if (category !== 'all') {
            loadSubcategories(category);
            document.getElementById('section-title').textContent = category;
            document.getElementById('section-subtitle').textContent = 'Annonces dans la catégorie ' + category;
            updateProsTitle(category);
        } else {
            resetSubcategoriesToDefault();
            document.getElementById('section-title').textContent = 'Les dernières pépites';
            document.getElementById('section-subtitle').textContent = 'Découvrez les meilleures opportunités du moment';
            // Use default category name for pros title
            if (defaultCategoryData && defaultCategoryData.name) {
                updateProsTitle(defaultCategoryData.name);
            } else {
                updateProsTitle('');
            }
        }

        // Reset carousel positions
        listingsScrollPos = 0;
        prosScrollPos = 0;

        // Load filtered ads
        filterAds(category, '');
        
        // Smooth scroll to listings section
        document.getElementById('listings-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /**
     * Filter ads by subcategory
     */
    function filterBySubcategory(subcategory) {
        currentSubcategory = subcategory;
        
        // Update active state on subcategory pills
        document.querySelectorAll('#subcategories-list .subcat-pill').forEach(pill => {
            pill.classList.remove('bg-primary', 'text-white', 'border-primary');
            if (pill.dataset.subcategory === subcategory || (subcategory === '' && pill.dataset.subcategory === 'all')) {
                pill.classList.add('bg-primary', 'text-white', 'border-primary');
            }
        });

        // Update title
        if (subcategory) {
            document.getElementById('section-title').textContent = subcategory;
            document.getElementById('section-subtitle').textContent = 'Résultats pour ' + subcategory;
            updateProsTitle(subcategory);
        } else {
            document.getElementById('section-title').textContent = currentCategory;
            document.getElementById('section-subtitle').textContent = 'Annonces dans la catégorie ' + currentCategory;
            updateProsTitle(currentCategory);
        }

        // Reset carousel positions
        listingsScrollPos = 0;

        // Load filtered ads
        filterAds(currentCategory, subcategory);
    }

    /**
     * Load subcategories for a category via AJAX
     */
    function loadSubcategories(category) {
        fetch(`{{ route('feed.subcategories') }}?category=${encodeURIComponent(category)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.subcategories.length > 0) {
                    renderSubcategories(data.subcategories, category);
                } else {
                    resetSubcategoriesToDefault();
                }
            })
            .catch(err => {
                console.error('Error loading subcategories:', err);
                resetSubcategoriesToDefault();
            });
    }

    function renderSubcategories(subcategories = [], categoryLabel = '') {
        const container = document.getElementById('subcategories-container');
        const list = document.getElementById('subcategories-list');
        const currentLabelEl = document.getElementById('current-category-name');
        const fallbackName = (defaultCategoryData && defaultCategoryData.name) ? defaultCategoryData.name : 'Toutes les catégories';
        const label = categoryLabel || fallbackName;
        currentLabelEl.textContent = label;

        let html = `<button onclick="filterBySubcategory('')" class="btn btn-light rounded-pill border subcat-pill bg-primary text-white border-primary" data-subcategory="all">
            Tout voir
        </button>`;

        if (subcategories.length) {
            subcategories.forEach(sub => {
                const badge = sub.count ? `<span class="badge bg-secondary ms-1">${sub.count}</span>` : '';
                html += `<button onclick="filterBySubcategory('${sub.name}')" class="btn btn-light rounded-pill border subcat-pill" data-subcategory="${sub.name}">
                    <i class="${sub.icon} me-1"></i> ${sub.name}
                    ${badge}
                </button>`;
            });
        } else {
            html += `<span class="text-muted small ms-2">Sélectionnez une catégorie pour découvrir les sous-catégories disponibles.</span>`;
        }

        list.innerHTML = html;
        container.style.opacity = '0';
        container.style.transform = 'translateY(-10px)';
        requestAnimationFrame(() => {
            container.style.transition = 'all 0.3s ease';
            container.style.opacity = '1';
            container.style.transform = 'translateY(0)';
        });
    }

    function resetSubcategoriesToDefault() {
        if (defaultCategoryData && Array.isArray(defaultCategoryData.subcategories)) {
            renderSubcategories(defaultCategoryData.subcategories, defaultCategoryData.name);
        } else {
            renderSubcategories([], 'Toutes les catégories');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        resetSubcategoriesToDefault();
        // Initialize pros section with default category (title + load professionals)
        if (defaultCategoryData && defaultCategoryData.name) {
            currentCategory = defaultCategoryData.name;
            setTimeout(() => {
                updateProsTitle(defaultCategoryData.name);
            }, 100);
        }
    });

    /**
     * Filter ads via AJAX - returns carousel format
     */
    function filterAds(category, subcategory) {
        const carouselTrack = document.getElementById('ads-carousel-track');
        const loadingIndicator = document.getElementById('ads-loading');
        
        if (!carouselTrack) return;
        
        // Show loading
        loadingIndicator.style.display = 'block';
        carouselTrack.style.opacity = '0.5';
        
        // Build URL
        let url = `{{ route('feed.filter-ads') }}?format=carousel&`;
        if (category && category !== 'all') {
            url += `category=${encodeURIComponent(category)}&`;
        }
        if (subcategory) {
            url += `subcategory=${encodeURIComponent(subcategory)}&`;
        }
        
        fetch(url)
            .then(response => response.text())
            .then(html => {
                // Hide loading
                loadingIndicator.style.display = 'none';
                carouselTrack.style.opacity = '1';
                
                // Update content
                carouselTrack.innerHTML = html;
                
                // Reset scroll position
                listingsScrollPos = 0;
                carouselTrack.style.transform = 'translateX(0)';
                
                // Animate cards in
                const cards = carouselTrack.querySelectorAll('.listing-card');
                cards.forEach((card, index) => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        card.style.transition = 'all 0.3s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, index * 50);
                });
                
                // Update button states
                const containerWidth = carouselTrack.parentElement.offsetWidth;
                const maxScroll = Math.max(0, carouselTrack.scrollWidth - containerWidth);
                document.getElementById('listings-prev-btn').disabled = true;
                document.getElementById('listings-next-btn').disabled = maxScroll <= 0;
            })
            .catch(err => {
                console.error('Error filtering ads:', err);
                loadingIndicator.style.display = 'none';
                carouselTrack.style.opacity = '1';
            });
    }

    // Initialize based on URL params
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const category = urlParams.get('category');
        
        if (category) {
            filterByCategory(category);
        }
        
        // Initialize carousel button states
        setTimeout(() => {
            const track = document.getElementById('ads-carousel-track');
            if (track) {
                const containerWidth = track.parentElement.offsetWidth;
                const maxScroll = Math.max(0, track.scrollWidth - containerWidth);
                document.getElementById('listings-prev-btn').disabled = true;
                document.getElementById('listings-next-btn').disabled = maxScroll <= 0;
            }
            
            const prosTrack = document.getElementById('pros-carousel-track');
            if (prosTrack) {
                const containerWidth = prosTrack.parentElement.offsetWidth;
                const maxScroll = Math.max(0, prosTrack.scrollWidth - containerWidth);
                document.getElementById('pros-prev-btn').disabled = true;
                document.getElementById('pros-next-btn').disabled = maxScroll <= 0;
            }
        }, 100);
    });
</script>
@endpush

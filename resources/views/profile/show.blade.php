@extends('layouts.app')

@section('title', 'Mon Profil - Lunamars')

@section('content')
@php
    $profileVerified = $user->hasVerifiedProfileBadge();
    $profileCompleteForVerification = $user->hasCompleteVerificationProfile();
    $isParticularAccount = $user->user_type !== 'professionnel' && $user->account_type !== 'professionnel';
    $primaryService = $user->relationLoaded('services') ? $user->services->first() : null;
    $displayProfession = $user->profession
        ?: ($primaryService?->subcategory ?? $primaryService?->category ?? null);
    $serviceCategory = trim((string) ($user->service_category ?? ''));
    $normalizedProfession = mb_strtolower(trim((string) $displayProfession));
    $normalizedCategory = mb_strtolower($serviceCategory);
    $showServiceCategory = $serviceCategory !== '' && $normalizedCategory !== $normalizedProfession;
    $filteredSubcategories = collect($user->service_subcategories ?? [])
        ->filter(function ($subcat) use ($normalizedProfession, $normalizedCategory) {
            $normalized = mb_strtolower(trim((string) $subcat));
            return $normalized !== ''
                && $normalized !== $normalizedProfession
                && $normalized !== $normalizedCategory;
        })
        ->unique()
        ->values();
    $providerAction = null;
    if ($isParticularAccount && !$user->is_service_provider && !$profileCompleteForVerification) {
        $providerAction = [
            'href' => route('profile.edit'),
            'icon' => 'fas fa-user-edit',
            'label' => 'Compléter mon profil',
            'class' => 'btn-warning',
        ];
    } elseif ($isParticularAccount && !$user->is_service_provider && !$profileVerified) {
        $providerAction = [
            'href' => route('verification.index'),
            'icon' => 'fas fa-shield-alt',
            'label' => 'Vérifier mon profil',
            'class' => 'btn-outline-success',
        ];
    } elseif ($isParticularAccount && !$user->is_service_provider) {
        $providerAction = [
            'target' => '#becomeProviderModal',
            'icon' => 'fas fa-user-plus',
            'label' => 'Devenir prestataire',
            'class' => 'btn-success',
        ];
    } elseif ($user->isParticulierPrestataire()) {
        $providerAction = [
            'target' => '#becomeProviderModal',
            'icon' => 'fas fa-check-circle',
            'label' => 'Gérer mes services',
            'class' => 'btn-outline-success',
        ];
    }
@endphp
<div class="container py-4 own-profile-page">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4 align-items-start">
        <!-- Profile Card -->
        <div class="col-lg-4 own-profile-sidebar">
            <div class="card border-0 shadow-sm own-profile-identity-card">
                <div class="card-body text-center own-profile-identity-body">
                    <!-- Avatar -->
                    <div class="position-relative own-profile-photo-shell mb-4">
                    @if($user->avatar)
                            <img src="{{ storage_url($user->avatar) }}" alt="Ma photo de profil" id="profileAvatarImg"
                                class="own-profile-portrait">
                    @else
                            <div class="bg-primary text-white d-flex align-items-center justify-content-center own-profile-portrait own-profile-placeholder" id="profileAvatarPlaceholder">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                        <a href="{{ route('profile.edit') }}#profile-photo-section" class="own-profile-photo-edit position-absolute bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                               title="Changer et recadrer ma photo" aria-label="Changer et recadrer ma photo de profil">
                            <i class="fas fa-camera"></i>
                        </a>
                    </div>

                    @if($user->isParticulierPrestataire())
                        <div class="mb-3">
                            <span class="badge rounded-pill px-3 py-2" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; white-space: normal;">
                                <i class="fas fa-user-cog me-1"></i>Particulier prestataire non professionnel
                            </span>
                        </div>
                    @endif

                    <h4 class="fw-bold mb-1">{{ $user->name }}</h4>

                    {{-- Profession/Métier --}}
                    @if($displayProfession)
                        <p class="text-primary fw-semibold mb-1">
                            <i class="fas fa-briefcase me-1"></i>{{ $displayProfession }}
                        </p>
                    @endif
                    
                    {{-- Catégorie de service --}}
                    @if($showServiceCategory)
                        <p class="text-muted small mb-1">
                            <i class="fas fa-th-large me-1"></i>{{ $serviceCategory }}
                        </p>
                    @endif
                    
                    {{-- Métiers / Sous-catégories --}}
                    @if($filteredSubcategories->isNotEmpty())
                        <div class="d-flex flex-wrap justify-content-center gap-1 mb-2">
                            @foreach($filteredSubcategories as $subcat)
                                <span class="badge bg-light text-primary border px-2 py-1" style="font-size: 0.75rem;">
                                    {{ $subcat }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                    
                    {{-- Localisation --}}
                    @if($user->city && $user->country)
                        <p class="text-muted small mb-2">
                            <i class="fas fa-map-marker-alt me-1"></i>{{ $user->city }}, {{ $user->country }}
                        </p>
                    @endif
                    
                    <p class="text-muted mb-3">{{ $user->email }}</p>

                    <div class="mb-4">
                        @if($profileVerified)
                            <span class="badge bg-success px-3 py-2">
                                <i class="fas fa-check-circle me-1"></i>Profil vérifié
                            </span>
                        @else
                            <a href="{{ route('verification.index') }}" class="btn btn-sm btn-outline-success rounded-pill px-3 py-2">
                                <i class="fas fa-shield-alt me-1"></i><span translate="no">Vérifier mon profil</span>
                            </a>
                        @endif
                    </div>
                    
                    @if($user->hasActiveProSubscription() || $user->user_type === 'professionnel' || $user->isProfessionnel() || ($user->is_service_provider && $user->service_provider_verified))
                    <!-- Badges -->
                    <div class="mb-4">
                        @if($user->hasActiveProSubscription())
                            <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;">
                                <i class="fas fa-crown me-1"></i>Premium
                            </span>
                        @elseif($user->user_type === 'professionnel' || $user->isProfessionnel())
                            <span class="badge bg-primary px-3 py-2">
                                <i class="fas fa-briefcase me-1"></i>Entrepreneur
                            </span>
                        @endif
                        
                        {{-- Badge de vérification avec évolution --}}
                        @if($user->is_service_provider && $user->service_provider_verified)
                            {{-- Prestataire particulier vérifié (niveau max) --}}
                            <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                                <i class="fas fa-user-check me-1"></i>Prestataire particulier vérifié
                            </span>
                        @endif
                    </div>
                    @endif
                    
                    <!-- Points -->
                    <div class="bg-light rounded-3 p-3 mb-4">
                        <div class="d-flex justify-content-center align-items-center gap-2">
                            <i class="fas fa-coins text-warning fa-lg"></i>
                            <span class="fs-4 fw-bold">{{ number_format($user->available_points ?? 0, 0, ',', ' ') }}</span>
                            <span class="text-muted">points</span>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="d-grid gap-2">
                        @if($providerAction)
                            @if(isset($providerAction['href']))
                                <a href="{{ $providerAction['href'] }}" class="btn {{ $providerAction['class'] }}">
                                    <i class="{{ $providerAction['icon'] }} me-2"></i><span translate="no">{{ $providerAction['label'] }}</span>
                                </a>
                            @else
                                <button type="button" class="btn {{ $providerAction['class'] }}" data-bs-toggle="modal" data-bs-target="{{ $providerAction['target'] }}">
                                    <i class="{{ $providerAction['icon'] }} me-2"></i><span translate="no">{{ $providerAction['label'] }}</span>
                                </button>
                            @endif
                        @endif
                        <a href="{{ route('profile.edit') }}" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i><span translate="no">Modifier mon profil</span>
                        </a>
                        @if($user->profile_public)
                            <button type="button" class="btn btn-outline-primary" id="ownProfileShareBtn">
                                <i class="fas fa-share-nodes me-2"></i>Partager mon profil
                            </button>
                        @else
                            <a href="{{ route('settings.index') }}" class="btn btn-outline-warning" title="Votre profil doit être public pour être partagé">
                                <i class="fas fa-lock me-2"></i>Rendre mon profil partageable
                            </a>
                        @endif
                        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-cog me-2"></i>Paramètres
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Compétences Prestataire -->
            @if($user->is_service_provider && $user->services && $user->services->count() > 0)
            <div class="card border-0 shadow-sm mt-4 own-profile-detail-card">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="fas fa-tools me-2 text-success"></i>Mes autres compétences</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($user->services as $service)
                            <span class="competence-badge">
                                {{ $service->subcategory }}
                            </span>
                        @endforeach
                    </div>
                    @if($user->services->first() && $user->services->first()->description)
                    <div class="mt-3 p-3 bg-light rounded">
                        <small class="text-muted d-block mb-1"><i class="fas fa-quote-left me-1"></i>Description</small>
                        <p class="mb-0">{{ $user->services->first()->description }}</p>
                    </div>
                    @endif
                    @if($user->service_provider_since)
                    <div class="mt-3 text-muted small">
                        <i class="fas fa-calendar-check me-1"></i>
                        Prestataire depuis {{ $user->service_provider_since->format('F Y') }}
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Info Card -->
            <div class="card border-0 shadow-sm mt-4 own-profile-detail-card">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @if($user->phone)
                        <li class="mb-3 d-flex align-items-center">
                            <i class="fas fa-phone text-muted me-3" style="width: 20px;"></i>
                            {{ $user->phone }}
                        </li>
                        @endif
                        @if($user->location)
                        <li class="mb-3 d-flex align-items-center">
                            <i class="fas fa-map-marker-alt text-muted me-3" style="width: 20px;"></i>
                            {{ $user->location }}
                        </li>
                        @endif
                        <li class="mb-3 d-flex align-items-center">
                            <i class="fas fa-calendar-alt text-muted me-3" style="width: 20px;"></i>
                            Membre depuis {{ $user->created_at->format('M Y') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-8 own-profile-main">
            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 own-profile-stat-card">
                        <div class="card-body d-flex align-items-center gap-3 py-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: rgba(58,134,255,0.1);">
                                <i class="fas fa-bullhorn text-primary"></i>
                            </div>
                            <div>
                                <div class="fs-4 fw-bold text-primary lh-1 mb-1">{{ $stats['total_ads'] }}</div>
                                <div class="text-muted small">Annonces publiées</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 own-profile-stat-card">
                        <div class="card-body d-flex align-items-center gap-3 py-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: rgba(16,185,129,0.1);">
                                <i class="fas fa-check-circle text-success"></i>
                            </div>
                            <div>
                                <div class="fs-4 fw-bold text-success lh-1 mb-1">{{ $stats['active_ads'] }}</div>
                                <div class="text-muted small">Annonces actives</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 own-profile-stat-card">
                        <div class="card-body d-flex align-items-center gap-3 py-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: rgba(14,165,233,0.1);">
                                <i class="fas fa-eye text-info"></i>
                            </div>
                            <div>
                                <div class="fs-4 fw-bold text-info lh-1 mb-1">{{ number_format($stats['total_views'], 0, ',', ' ') }}</div>
                                <div class="text-muted small">Vues totales</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4 own-profile-section-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="own-profile-section-icon"><i class="fas fa-shield-alt"></i></span>
                        <div>
                            <h2 class="h6 fw-bold mb-1">État de mon profil</h2>
                            <p class="text-muted small mb-0">Les trois éléments qui renforcent la confiance des autres utilisateurs.</p>
                        </div>
                    </div>
                    <div class="own-profile-readiness-grid">
                        <a href="{{ $profileVerified ? route('profile.public', $user) : route('verification.index') }}" class="own-profile-readiness-item {{ $profileVerified ? 'is-ready' : 'is-action' }}">
                            <i class="fas {{ $profileVerified ? 'fa-check-circle' : 'fa-clock' }}"></i>
                            <span><strong>{{ $profileVerified ? 'Profil vérifié' : 'Profil à vérifier' }}</strong><small>{{ $profileVerified ? 'Votre identité a été validée' : 'Finalisez la vérification d’identité' }}</small></span>
                        </a>
                        <a href="{{ route('settings.index') }}" class="own-profile-readiness-item {{ $user->profile_public ? 'is-ready' : 'is-action' }}">
                            <i class="fas {{ $user->profile_public ? 'fa-globe' : 'fa-lock' }}"></i>
                            <span><strong>{{ $user->profile_public ? 'Profil public' : 'Profil privé' }}</strong><small>{{ $user->profile_public ? 'Visible et partageable' : 'Activez sa visibilité publique' }}</small></span>
                        </a>
                        <a href="{{ route('profile.edit') }}" class="own-profile-readiness-item {{ $profileCompleteForVerification ? 'is-ready' : 'is-action' }}">
                            <i class="fas {{ $profileCompleteForVerification ? 'fa-list-check' : 'fa-user-pen' }}"></i>
                            <span><strong>{{ $profileCompleteForVerification ? 'Informations complètes' : 'Profil à compléter' }}</strong><small>{{ $profileCompleteForVerification ? 'Les informations essentielles sont présentes' : 'Ajoutez les informations manquantes' }}</small></span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Bio -->
            @if($user->bio)
            <div class="card border-0 shadow-sm mb-4 own-profile-section-card">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="fas fa-quote-left me-2"></i>À propos</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $user->bio }}</p>
                </div>
            </div>
            @endif
            
            <!-- Recent Ads -->
            <div class="card border-0 shadow-sm own-profile-section-card">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-bullhorn me-2"></i>Mes dernières annonces</h6>
                    <a href="{{ route('ads.index') }}?user={{ $user->id }}" class="btn btn-sm btn-outline-primary">
                        Voir tout
                    </a>
                </div>
                <div class="card-body">
                    @if($ads->count() > 0)
                        <div class="row g-3">
                            @foreach($ads as $ad)
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light rounded-3 overflow-hidden h-100" 
                                         style="cursor:pointer;" 
                                         onclick="showAdDetail({{ json_encode(['id' => $ad->id, 'title' => $ad->title, 'description' => $ad->description, 'price' => $ad->price, 'location' => $ad->location, 'category' => $ad->category, 'status' => $ad->status, 'created_at' => $ad->created_at->format('d/m/Y'), 'photo' => ($ad->photos && count($ad->photos) > 0) ? storage_url($ad->photos[0]) : null, 'url' => route('ads.show', $ad)]) }})">
                                        @if($ad->photos && count($ad->photos) > 0)
                                            <img src="{{ storage_url($ad->photos[0]) }}" alt="" 
                                                 class="card-img-top" style="height: 160px; object-fit: cover;">
                                        @else
                                            <div class="bg-secondary d-flex align-items-center justify-content-center" 
                                                 style="height: 160px;">
                                                <i class="fas fa-image text-white fa-2x"></i>
                                            </div>
                                        @endif
                                        <div class="card-body p-3">
                                            <h6 class="mb-1 text-truncate">{{ $ad->title }}</h6>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <small class="text-muted">{{ $ad->created_at->diffForHumans() }}</small>
                                                <span class="badge {{ $ad->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $ad->status == 'active' ? 'Actif' : 'Inactif' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-bullhorn fa-3x mb-3 opacity-50"></i>
                            <p class="mb-3">Vous n'avez pas encore publié d'annonce</p>
                            <a href="{{ route('ads.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Publier une annonce
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .own-profile-page {
        max-width: 1180px;
    }
    .own-profile-sidebar {
        position: sticky;
        top: 96px;
    }
    .own-profile-identity-card,
    .own-profile-detail-card,
    .own-profile-stat-card,
    .own-profile-section-card {
        border: 1px solid #e6ebf2 !important;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 12px 34px rgba(15, 23, 42, .07) !important;
    }
    .own-profile-identity-body {
        padding: 1.15rem 1.15rem 1.35rem;
    }
    .own-profile-photo-shell {
        width: 100%;
        aspect-ratio: 4 / 4.25;
    }
    .own-profile-portrait {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
        object-position: center;
        border: 1px solid #dfe6ef;
        border-radius: 18px;
        box-shadow: 0 16px 30px rgba(15, 23, 42, .13);
    }
    .own-profile-placeholder {
        font-size: clamp(4rem, 10vw, 7rem);
        background: linear-gradient(145deg, #2563eb, #6857f5) !important;
    }
    .own-profile-photo-edit {
        width: 44px;
        height: 44px;
        right: .8rem;
        bottom: -.65rem;
        border: 3px solid #fff;
        box-shadow: 0 5px 14px rgba(15, 23, 42, .2);
        text-decoration: none;
    }
    .own-profile-detail-card .card-header,
    .own-profile-section-card .card-header {
        padding: 1rem 1.15rem .7rem;
        border-bottom-color: #edf0f5;
    }
    .own-profile-stat-card {
        min-height: 105px;
    }
    .own-profile-section-card .card-body {
        padding: 1.25rem;
    }
    .own-profile-section-icon {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        color: #087a58;
        background: #e6faf2;
    }
    .own-profile-readiness-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem;
    }
    .own-profile-readiness-item {
        min-height: 105px;
        padding: .9rem;
        display: flex;
        flex-direction: column;
        gap: .55rem;
        border: 1px solid #e5eaf1;
        border-radius: 14px;
        color: #334155;
        background: #fbfcfe;
        text-decoration: none;
        transition: transform .15s ease, border-color .15s ease;
    }
    .own-profile-readiness-item:hover {
        transform: translateY(-2px);
        border-color: #b9cdfd;
    }
    .own-profile-readiness-item > i {
        color: #bd7208;
        font-size: 1.05rem;
    }
    .own-profile-readiness-item.is-ready > i {
        color: #059669;
    }
    .own-profile-readiness-item strong,
    .own-profile-readiness-item small {
        display: block;
    }
    .own-profile-readiness-item strong {
        font-size: .84rem;
    }
    .own-profile-readiness-item small {
        margin-top: .25rem;
        color: #7b8799;
        font-size: .71rem;
        line-height: 1.35;
    }
    @media (max-width: 991.98px) {
        .own-profile-sidebar {
            position: static;
        }
        .own-profile-photo-shell {
            max-width: 420px;
            margin-inline: auto;
            aspect-ratio: 4 / 3.7;
        }
    }
    @media (max-width: 767.98px) {
        .own-profile-readiness-grid {
            grid-template-columns: 1fr;
        }
        .own-profile-readiness-item {
            min-height: 0;
            flex-direction: row;
            align-items: flex-start;
        }
    }
    @media (max-width: 575.98px) {
        .own-profile-page {
            width: 100%;
            max-width: 100%;
            padding-top: .8rem !important;
            padding-right: 12px !important;
            padding-left: 12px !important;
            overflow-x: hidden;
        }
        .own-profile-page > .row {
            margin-right: 0;
            margin-left: 0;
        }
        .own-profile-page > .row > [class*="col-"] {
            min-width: 0;
            padding-right: 0;
            padding-left: 0;
        }
        .own-profile-identity-card,
        .own-profile-detail-card,
        .own-profile-stat-card,
        .own-profile-section-card {
            border-radius: 16px;
        }
        .own-profile-photo-shell {
            aspect-ratio: 1 / 1.02;
        }
    }
</style>
@endpush

@if($user->profile_public)
    @include('profile.partials.share-modal', [
        'triggerId' => 'ownProfileShareBtn',
        'modalId' => 'ownProfileShareModal',
    ])
@endif

{{-- Ad Detail Modal --}}
<div class="modal fade" id="adDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div id="adDetailPhoto" style="height:200px; background:#f1f5f9; display:none;">
                <img id="adDetailImg" src="" alt="" style="width:100%; height:100%; object-fit:cover;">
            </div>
            <div class="modal-body p-4">
                <h5 class="fw-bold mb-2" id="adDetailTitle"></h5>
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <span class="badge bg-primary" id="adDetailCategory"></span>
                    <span class="badge" id="adDetailStatus"></span>
                    <span class="text-muted small" id="adDetailDate"></span>
                </div>
                <div class="mb-3" id="adDetailPriceRow">
                    <strong class="text-success" id="adDetailPrice"></strong>
                </div>
                <div class="mb-3" id="adDetailLocationRow">
                    <i class="fas fa-map-marker-alt text-muted me-1"></i>
                    <span id="adDetailLocation"></span>
                </div>
                <p class="text-muted" id="adDetailDesc" style="white-space: pre-line;"></p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fermer</button>
                <a href="#" class="btn btn-primary" id="adDetailLink"><i class="fas fa-eye me-1"></i>Voir l'annonce</a>
            </div>
        </div>
    </div>
</div>

<script>
function showAdDetail(ad) {
    document.getElementById('adDetailTitle').textContent = ad.title;
    document.getElementById('adDetailCategory').textContent = ad.category || '';
    document.getElementById('adDetailDate').textContent = ad.created_at;
    document.getElementById('adDetailLink').href = ad.url;

    const statusEl = document.getElementById('adDetailStatus');
    statusEl.textContent = ad.status === 'active' ? 'Actif' : 'Inactif';
    statusEl.className = 'badge ' + (ad.status === 'active' ? 'bg-success' : 'bg-secondary');

    const priceRow = document.getElementById('adDetailPriceRow');
    if (ad.price) {
        document.getElementById('adDetailPrice').textContent = new Intl.NumberFormat('fr-FR').format(ad.price) + ' €/h';
        priceRow.style.display = '';
    } else {
        priceRow.style.display = 'none';
    }

    const locRow = document.getElementById('adDetailLocationRow');
    if (ad.location) {
        document.getElementById('adDetailLocation').textContent = ad.location;
        locRow.style.display = '';
    } else {
        locRow.style.display = 'none';
    }

    document.getElementById('adDetailDesc').textContent = ad.description || '';

    const photoDiv = document.getElementById('adDetailPhoto');
    if (ad.photo) {
        document.getElementById('adDetailImg').src = ad.photo;
        photoDiv.style.display = '';
    } else {
        photoDiv.style.display = 'none';
    }

    new bootstrap.Modal(document.getElementById('adDetailModal')).show();
}
</script>
@endsection

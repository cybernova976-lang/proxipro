@extends('layouts.app')

@section('title', 'Profil de ' . $user->name . ' - Lunamars')
@section('meta_description', $user->bio ? Str::limit($user->bio, 160) : (($user->profession ? $user->profession . ' sur Lunamars. ' : '') . ($user->city ? 'Basé à ' . $user->city . '. ' : '') . 'Découvrez ce profil sur Lunamars.'))
@section('og_type', 'profile')
@section('og_title', ($user->company_name ?: $user->name) . ($user->profession ? ' — ' . $user->profession : '') . ' | Lunamars')
@section('og_description', $user->bio ? Str::limit($user->bio, 160) : 'Découvrez le profil de ' . ($user->company_name ?: $user->name) . ' sur Lunamars.')
@section('og_image', $user->avatar ? storage_url($user->avatar) : asset('images/social-card.png'))
@section('og_url', route('profile.public', $user->id))

@section('content')
@php
    $isOwnProfile = auth()->check() && auth()->id() === $user->id;
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
    $profileLocation = collect([$user->city, $user->country])->filter()->implode(', ')
        ?: ($user->location ?: null);
    $accountLabel = $user->isParticulierPrestataire()
        ? 'Particulier prestataire non professionnel'
        : (($user->user_type === 'professionnel' || $user->hasCompletedProOnboarding()) ? 'Professionnel' : 'Particulier');
    $providerAction = null;
    if ($isOwnProfile) {
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
    }
@endphp
<div class="container py-4 public-profile-page">
    <div class="row g-4 align-items-start">
        <!-- Profile Card - Sidebar gauche -->
        <div class="col-lg-4 profile-sidebar-column">
            <div class="card border-0 shadow-sm profile-identity-card">
                <div class="card-body text-center profile-identity-body">
                    <!-- Avatar en format carte -->
                    <div class="position-relative profile-photo-shell mb-4">
                        @if($user->avatar)
                            <img src="{{ storage_url($user->avatar) }}" alt="Photo de profil de {{ $user->name }}" id="profileAvatarImg"
                                class="profile-portrait">
                        @else
                            <div class="bg-primary text-white d-flex align-items-center justify-content-center profile-portrait profile-portrait-placeholder" id="profileAvatarPlaceholder">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                        @auth
                            @if(auth()->id() === $user->id)
                                <a href="{{ route('profile.edit') }}#profile-photo-section" class="profile-photo-edit position-absolute bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow"
                                       title="Changer et recadrer la photo" aria-label="Changer et recadrer la photo de profil">
                                    <i class="fas fa-camera"></i>
                                </a>
                            @endif
                        @endauth
                    </div>

                    @if($user->isParticulierPrestataire())
                        <div class="mb-3">
                            <span class="badge rounded-pill px-3 py-2" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; white-space: normal;">
                                <i class="fas fa-user-cog me-1"></i>Particulier prestataire non professionnel
                            </span>
                        </div>
                    @endif

                    <!-- Name + Pro Badge -->
                    <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 mb-1">
                        <h4 class="fw-bold mb-0">{{ $user->name }}</h4>
                        @if($user->hasActiveProSubscription())
                            <span class="badge" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                                <i class="fas fa-crown me-1"></i>Premium
                            </span>
                        @elseif($user->user_type === 'professionnel' || $user->isProfessionnel())
                            <span class="badge" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                <i class="fas fa-briefcase me-1"></i>Professionnel
                            </span>
                        @elseif($user->is_service_provider)
                            <span class="badge" style="background: linear-gradient(135deg, #10b981, #059669);">
                                <i class="fas fa-user-check me-1"></i>Prestataire
                            </span>
                        @endif
                    </div>
                    <div class="mb-2">
                        @if($profileVerified)
                            <span class="badge bg-success px-3 py-2">
                                <i class="fas fa-check-circle me-1"></i>Profil vérifié
                            </span>
                        @elseif($isOwnProfile)
                            <a href="{{ route('verification.index') }}" class="btn btn-sm btn-outline-success rounded-pill px-3 py-2">
                                <i class="fas fa-shield-alt me-1"></i><span translate="no">Vérifier mon profil</span>
                            </a>
                        @else
                            <span class="badge bg-secondary px-3 py-2" style="opacity: 0.85;">
                                <i class="fas fa-user-times me-1"></i>Profil non vérifié
                            </span>
                        @endif
                    </div>

                    <!-- Job Title / Profession -->
                    <p class="text-primary fw-semibold mb-1" style="word-break: break-word; overflow-wrap: break-word;">
                        @if($displayProfession)
                            <i class="fas fa-briefcase me-1"></i>{{ Str::limit($displayProfession, 80) }}
                        @endif
                    </p>
                    
                    {{-- Catégorie de service --}}
                    @if($showServiceCategory)
                        <p class="text-muted small mb-1" style="word-break: break-word; overflow-wrap: break-word;">
                            <i class="fas fa-th-large me-1"></i>{{ Str::limit($serviceCategory, 120) }}
                        </p>
                    @endif

                    {{-- Tarif horaire --}}
                    @if($user->hourly_rate && ($user->show_hourly_rate ?? true))
                        <div class="mb-2">
                            <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); color: #059669; font-size: 0.95rem; font-weight: 700; border: 1px solid #a7f3d0;">
                                <i class="fas fa-euro-sign me-1"></i>{{ number_format((float)$user->hourly_rate, 0, ',', ' ') }} €/h
                            </span>
                        </div>
                    @endif
                    
                    {{-- Métiers / Sous-catégories (exclure celles identiques à la profession) --}}
                    @if($filteredSubcategories->isNotEmpty())
                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-3 profile-skill-list">
                            @foreach($filteredSubcategories as $subcat)
                                <span class="profile-skill-chip">
                                    {{ Str::limit($subcat, 50) }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                    
                    <!-- Localisation -->
                    @if($user->city && $user->country)
                        <p class="text-muted small mb-2">
                            <i class="fas fa-map-marker-alt me-1"></i>{{ $user->city }}, {{ $user->country }}
                        </p>
                    @endif
                    
                    <!-- Bio courte -->
                    @if($user->bio)
                        <p class="text-muted small mb-3 profile-short-bio">{{ Str::limit($user->bio, 120) }}</p>
                    @endif
                    
                    <!-- Rating -->
                    <div class="mb-3">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star {{ $i <= round($ratingAverage ?? 0) ? 'text-warning' : 'text-muted' }}"></i>
                        @endfor
                        <span class="ms-2 fw-bold">{{ number_format($ratingAverage ?? 0, 1) }}</span>
                        <span class="text-muted">({{ $ratingCount ?? 0 }} avis)</span>
                    </div>
                    
                    @if($user->hasActiveProSubscription() || $user->user_type === 'professionnel' || $user->hasCompletedProOnboarding() || ($user->is_service_provider && $user->service_provider_verified))
                    <!-- Badges -->
                    <div class="mb-4">
                        @if($user->hasActiveProSubscription())
                            <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;">
                                <i class="fas fa-briefcase me-1"></i>Professionnel
                            </span>
                        @elseif($user->user_type === 'professionnel' || $user->hasCompletedProOnboarding())
                            <span class="badge bg-primary px-3 py-2">
                                <i class="fas fa-briefcase me-1"></i>Professionnel
                            </span>
                        @endif
                        @if($user->is_service_provider && $user->service_provider_verified)
                            <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                                <i class="fas fa-user-check me-1"></i>Prestataire vérifié
                            </span>
                        @endif
                    </div>
                    @endif

                    <!-- Contact Button -->
                    <div class="d-grid gap-2">
                        @auth
                            @if(!$isOwnProfile)
                                <form action="{{ route('messages.create.conversation') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="recipient_id" value="{{ $user->id }}">
                                    <input type="hidden" name="message" value="Bonjour, je souhaite vous contacter.">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-envelope me-2"></i>Contacter
                                    </button>
                                </form>
                            @else
                                @if($providerAction)
                                    @if(isset($providerAction['href']))
                                        <a href="{{ $providerAction['href'] }}" class="btn {{ $providerAction['class'] }} w-100 mb-2">
                                            <i class="{{ $providerAction['icon'] }} me-2"></i><span translate="no">{{ $providerAction['label'] }}</span>
                                        </a>
                                    @else
                                        <button type="button" class="btn {{ $providerAction['class'] }} w-100 mb-2" data-bs-toggle="modal" data-bs-target="{{ $providerAction['target'] }}">
                                            <i class="{{ $providerAction['icon'] }} me-2"></i><span translate="no">{{ $providerAction['label'] }}</span>
                                        </button>
                                    @endif
                                @endif
                                <button type="button" class="btn btn-outline-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                    <i class="fas fa-pen me-2"></i><span translate="no">Modifier mon profil</span>
                                </button>
                                <button type="button" class="btn btn-outline-success w-100" data-bs-toggle="modal" data-bs-target="#editCategoriesModal">
                                    <i class="fas fa-th-large me-2"></i>Gérer mes catégories
                                </button>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary">
                                <i class="fas fa-envelope me-2"></i>Se connecter pour contacter
                            </a>
                        @endauth
                    </div>

                    <div class="mt-3 pt-3 border-top">
                        @if($user->profile_public)
                            <button type="button" class="btn btn-outline-primary w-100" id="sharePublicProfileBtn">
                                <i class="fas fa-share-nodes me-2"></i>Partager ce profil
                            </button>
                        @elseif($isOwnProfile)
                            <div class="alert alert-light border small mb-0 text-start">
                                <i class="fas fa-lock me-1 text-warning"></i>
                                Rendez votre profil public dans les paramètres avant de le partager.
                            </div>
                        @endif
                    </div>
                    @if($user->profile_public)
                        @include('profile.partials.share-modal', [
                            'triggerId' => 'sharePublicProfileBtn',
                            'modalId' => 'publicProfileShareModal',
                        ])
                    @endif
                </div>
            </div>
            
            <!-- Info Card -->
            <div class="card border-0 shadow-sm mt-4 profile-detail-card">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @if($profileLocation)
                        <li class="mb-3 d-flex align-items-center">
                            <i class="fas fa-map-marker-alt text-muted me-3" style="width: 20px;"></i>
                            {{ $profileLocation }}
                        </li>
                        @endif
                        @if($user->business_type)
                        <li class="mb-3 d-flex align-items-center">
                            <i class="fas fa-building text-muted me-3" style="width: 20px;"></i>
                            {{ $user->business_type === 'entreprise' ? 'Entreprise' : 'Auto-entrepreneur' }}
                        </li>
                        @endif
                        @if($user->hourly_rate && ($user->show_hourly_rate ?? true))
                        <li class="mb-3 d-flex align-items-center">
                            <i class="fas fa-euro-sign text-success me-3" style="width: 20px;"></i>
                            <span class="fw-semibold">{{ number_format((float)$user->hourly_rate, 0, ',', ' ') }} €/h</span>
                        </li>
                        @endif
                        @if($user->years_experience)
                        <li class="mb-3 d-flex align-items-center">
                            <i class="fas fa-award text-muted me-3" style="width: 20px;"></i>
                            {{ $user->years_experience }} an{{ $user->years_experience > 1 ? 's' : '' }} d'expérience
                        </li>
                        @endif
                        <li class="mb-3 d-flex align-items-center">
                            <i class="fas fa-calendar-alt text-muted me-3" style="width: 20px;"></i>
                            Membre depuis {{ $user->created_at->translatedFormat('F Y') }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Compétences Prestataire -->
            @if($user->is_service_provider && $user->services && $user->services->count() > 0)
            <div class="card border-0 shadow-sm mt-4 profile-detail-card">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="fas fa-tools me-2 text-success"></i>Compétences</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($user->services as $service)
                            <span class="profile-skill-chip">
                                {{ $service->subcategory }}
                            </span>
                        @endforeach
                    </div>

                    @if($user->services->first() && $user->services->first()->experience_years)
                    <div class="mt-3 text-muted">
                        <i class="fas fa-history me-1"></i>
                        {{ $user->services->first()->experience_years }} années d'expérience
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-8 profile-main-column">
            <!-- Stats -->
            <div class="profile-metrics mb-4" aria-label="Indicateurs du profil">
                <div class="profile-metric">
                    <span class="profile-metric-value text-primary">{{ $stats['total_ads'] ?? 0 }}</span>
                    <span class="profile-metric-label">Annonces actives</span>
                </div>
                <div class="profile-metric">
                    <span class="profile-metric-value text-warning">{{ number_format($ratingAverage ?? 0, 1) }}<small>/5</small></span>
                    <span class="profile-metric-label">Note moyenne</span>
                    <span class="profile-stars" aria-label="Note de {{ number_format($ratingAverage ?? 0, 1) }} sur 5">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star {{ $i <= round($ratingAverage ?? 0) ? 'text-warning' : 'text-muted' }}"></i>
                        @endfor
                    </span>
                </div>
                <div class="profile-metric">
                    <span class="profile-metric-value text-success">{{ $ratingCount ?? 0 }}</span>
                    <span class="profile-metric-label">Avis vérifiés</span>
                </div>
            </div>

            <section class="card border-0 shadow-sm mb-4 profile-section" aria-labelledby="trust-title">
                <div class="card-body">
                    <div class="profile-section-heading">
                        <span class="profile-section-icon profile-section-icon-success"><i class="fas fa-shield-alt"></i></span>
                        <div>
                            <h2 id="trust-title">Confiance et repères</h2>
                            <p>Les informations utiles avant de prendre contact.</p>
                        </div>
                    </div>
                    <div class="profile-trust-grid">
                        <div class="profile-trust-item {{ $profileVerified ? 'is-confirmed' : 'is-pending' }}">
                            <i class="fas {{ $profileVerified ? 'fa-check-circle' : 'fa-clock' }}"></i>
                            <div><strong>{{ $profileVerified ? 'Identité vérifiée' : 'Identité non vérifiée' }}</strong><span>{{ $profileVerified ? 'Documents validés par la plateforme' : 'Vérification pas encore obtenue' }}</span></div>
                        </div>
                        <div class="profile-trust-item {{ $user->email_verified_at ? 'is-confirmed' : 'is-pending' }}">
                            <i class="fas {{ $user->email_verified_at ? 'fa-envelope-circle-check' : 'fa-envelope' }}"></i>
                            <div><strong>{{ $user->email_verified_at ? 'E-mail confirmé' : 'E-mail non confirmé' }}</strong><span>Compte {{ $user->email_verified_at ? 'authentifié' : 'en attente de confirmation' }}</span></div>
                        </div>
                        <div class="profile-trust-item is-neutral">
                            <i class="fas fa-calendar-check"></i>
                            <div><strong>Membre depuis {{ $user->created_at->translatedFormat('F Y') }}</strong><span>{{ $accountLabel }}</span></div>
                        </div>
                        @if($user->pro_intervention_radius)
                        <div class="profile-trust-item is-neutral">
                            <i class="fas fa-location-dot"></i>
                            <div><strong>Zone de {{ $user->pro_intervention_radius }} km</strong><span>{{ $profileLocation ?: 'Zone définie par le prestataire' }}</span></div>
                        </div>
                        @elseif($user->years_experience)
                        <div class="profile-trust-item is-neutral">
                            <i class="fas fa-award"></i>
                            <div><strong>{{ $user->years_experience }} an{{ $user->years_experience > 1 ? 's' : '' }} d’expérience</strong><span>Expérience déclarée par l’utilisateur</span></div>
                        </div>
                        @endif
                    </div>
                </div>
            </section>
            
            <!-- Bio -->
            @if($user->bio)
            <section class="card border-0 shadow-sm mb-4 profile-section">
                <div class="card-body">
                    <div class="profile-section-heading">
                        <span class="profile-section-icon"><i class="fas fa-user"></i></span>
                        <div><h2>À propos</h2><p>Présentation et manière de travailler.</p></div>
                    </div>
                    <p class="mb-0 profile-bio">{{ $user->bio }}</p>
                </div>
            </section>
            @endif

            <!-- Leave Review Form -->
            @auth
                @if(auth()->id() !== $user->id && $reviewableOrder)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-transparent">
                            <h6 class="mb-0"><i class="fas fa-star me-2 text-warning"></i>Laisser un avis</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('reviews.store', $user->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="service_order_id" value="{{ $reviewableOrder->id }}">
                                <div class="mb-3">
                                    <label class="form-label">Note</label>
                                    <div class="d-flex gap-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            <label class="d-flex align-items-center gap-1">
                                                <input type="radio" name="rating" value="{{ $i }}" required>
                                                <span>{{ $i }}</span>
                                            </label>
                                        @endfor
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Commentaire (optionnel)</label>
                                    <textarea name="comment" class="form-control" rows="3" maxlength="1000" placeholder="Votre expérience..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Publier l'avis
                                </button>
                            </form>
                        </div>
                    </div>
                @elseif(auth()->id() !== $user->id)
                    <div class="alert alert-light border mb-4">
                        <i class="fas fa-shield-alt text-success me-2"></i>
                        Les avis sont réservés aux utilisateurs ayant terminé une prestation payée ensemble.
                    </div>
                @endif
            @else
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <a href="{{ route('login') }}" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Connectez-vous pour laisser un avis
                        </a>
                    </div>
                </div>
            @endauth
            
            <!-- Ads List -->
            <section class="card border-0 shadow-sm mb-4 profile-section">
                <div class="card-body">
                    <div class="profile-section-heading">
                        <span class="profile-section-icon"><i class="fas fa-images"></i></span>
                        <div><h2>Annonces de {{ $user->name }}</h2><p>Services, demandes et réalisations actuellement visibles.</p></div>
                    </div>
                    @if($ads->count() > 0)
                        <div class="row g-3">
                            @foreach($ads as $ad)
                                <div class="col-md-6">
                                    <a href="{{ route('ads.show', $ad) }}" class="profile-ad-card h-100 text-decoration-none">
                                        @if($ad->photos && count($ad->photos) > 0)
                                            <img src="{{ storage_url($ad->photos[0]) }}" alt="Illustration de {{ $ad->title }}" loading="lazy">
                                        @else
                                            <div class="profile-ad-placeholder">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        @endif
                                        <div class="profile-ad-content">
                                            <h3>{{ $ad->title }}</h3>
                                            <div class="profile-ad-meta">
                                                <span>{{ $ad->created_at->diffForHumans() }}</span>
                                                @if($ad->price !== null)
                                                    <strong>{{ number_format($ad->price, 0, ',', ' ') }} €</strong>
                                                @endif
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Pagination -->
                        <div class="mt-4">
                            {{ $ads->links() }}
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-bullhorn fa-3x mb-3 opacity-50"></i>
                            <p class="mb-0">Aucune annonce active pour le moment.</p>
                        </div>
                    @endif
                </div>
            </section>

            <!-- Reviews -->
            <section class="card border-0 shadow-sm profile-section">
                <div class="card-body">
                    <div class="profile-section-heading">
                        <span class="profile-section-icon profile-section-icon-warning"><i class="fas fa-star"></i></span>
                        <div><h2>Avis sur {{ $user->name }}</h2><p>Uniquement après une prestation terminée et payée sur la plateforme.</p></div>
                    </div>
                    @if(isset($reviews) && $reviews->count() > 0)
                        <div class="d-flex flex-column gap-3">
                            @foreach($reviews as $review)
                                <article class="profile-review-card">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="fw-semibold d-flex align-items-center gap-2">
                                            <span class="profile-review-avatar">{{ strtoupper(substr($review->reviewer?->name ?? 'U', 0, 1)) }}</span>
                                            <a href="{{ $review->reviewer ? route('profile.public', $review->reviewer_id) : '#' }}" class="text-decoration-none">
                                                {{ $review->reviewer?->name ?? 'Utilisateur' }}
                                            </a>
                                        </div>
                                        <small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                                    </div>
                                    <div class="mb-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star {{ $i <= $review->rating ? 'text-warning' : 'text-muted' }}"></i>
                                        @endfor
                                    </div>
                                    @if($review->comment)
                                        <div>{{ $review->comment }}</div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-star-half-alt fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0">Aucun avis pour le moment.</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>

{{-- Modals pour l'édition du profil (seulement si c'est le propriétaire) --}}
@auth
@if(auth()->id() === $user->id)

{{-- Modal Modifier le profil --}}
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel"><i class="fas fa-user-edit me-2"></i>Modifier le profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editName" class="form-label">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editName" name="name" value="{{ $user->name }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editEmail" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="editEmail" name="email" value="{{ $user->email }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editPhone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="editPhone" name="phone" value="{{ $user->phone }}" placeholder="06 XX XX XX XX">
                        </div>
                        <div class="col-md-6">
                            <label for="editLocation" class="form-label">Localisation</label>
                            <input type="text" class="form-control" id="editLocation" name="location" value="{{ $user->location }}" placeholder="Mamoudzou, Mayotte">
                        </div>
                        <div class="col-md-6">
                            <label for="editProfession" class="form-label">Profession</label>
                            <input type="text" class="form-control" id="editProfession" name="profession" value="{{ $user->profession }}" placeholder="Ex: Plombier, Électricien...">
                        </div>
                        <div class="col-md-6">
                            <label for="editHourlyRate" class="form-label">Tarif horaire (€/h)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="editHourlyRate" name="hourly_rate" value="{{ $user->hourly_rate }}" placeholder="25" min="0" max="999" step="0.50">
                                <span class="input-group-text">€/h</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="editBio" class="form-label">À propos de moi</label>
                            <textarea class="form-control" id="editBio" name="bio" rows="4" maxlength="500" placeholder="Décrivez-vous en quelques mots...">{{ $user->bio }}</textarea>
                            <div class="form-text"><span id="editBioCount">{{ strlen($user->bio ?? '') }}</span>/500 caractères</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Gérer les catégories --}}
<div class="modal fade" id="editCategoriesModal" tabindex="-1" aria-labelledby="editCategoriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCategoriesModalLabel"><i class="fas fa-th-large me-2"></i>Gérer mes catégories & métiers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div id="categorySaveAlert" class="alert alert-success d-none"><i class="fas fa-check-circle me-2"></i><span></span></div>

                {{-- Catégories actuelles --}}
                @if($user->service_category || ($user->service_subcategories && count($user->service_subcategories) > 0))
                <div class="mb-4">
                    <h6 class="fw-bold mb-2"><i class="fas fa-tags me-2 text-primary"></i>Catégories actuelles</h6>
                    @if($user->service_category)
                        <p class="text-muted small mb-2">Domaine : <strong>{{ $user->service_category }}</strong></p>
                    @endif
                    @if($user->service_subcategories && count($user->service_subcategories) > 0)
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($user->service_subcategories as $subcat)
                                <span class="badge bg-primary px-2 py-1">{{ $subcat }}</span>
                            @endforeach
                        </div>
                    @endif
                    <hr>
                </div>
                @endif

                {{-- Ajouter une catégorie --}}
                <h6 class="fw-bold mb-3"><i class="fas fa-plus-circle me-2 text-success"></i>Ajouter des catégories / métiers</h6>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Choisir un domaine d'activité</label>
                    <select class="form-select" id="categorySelect">
                        <option value="">-- Sélectionner un domaine --</option>
                        @foreach(\App\Support\MarketplaceCategoryRegistry::enabledServices() as $catName => $catData)
                            <option value="{{ $catName }}">{{ $catData['icon'] ?? '' }} {{ $catName }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="subcategoriesContainer" class="d-none">
                    <label class="form-label fw-semibold">Sélectionner vos métiers / compétences</label>
                    <div id="subcategoriesList" class="d-flex flex-wrap gap-2 mb-3" style="max-height: 250px; overflow-y: auto;"></div>
                </div>

                <div id="selectedSubcats" class="mb-3 d-none">
                    <label class="form-label fw-semibold text-success"><i class="fas fa-check me-1"></i>Métiers sélectionnés</label>
                    <div id="selectedSubcatsList" class="d-flex flex-wrap gap-1"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-success" id="saveCategoriesBtn" disabled onclick="saveCategories()">
                    <i class="fas fa-save me-2"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

@endif
@endauth

@push('styles')
<style>
    .public-profile-page {
        max-width: 1180px;
    }
    .profile-sidebar-column {
        position: sticky;
        top: 96px;
    }
    .profile-identity-card,
    .profile-detail-card,
    .profile-section,
    .profile-metrics {
        border: 1px solid #e6ebf2 !important;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 12px 35px rgba(15, 23, 42, 0.07) !important;
    }
    .profile-identity-body {
        padding: 1.15rem 1.15rem 1.35rem;
    }
    .profile-photo-shell {
        width: 100%;
        aspect-ratio: 4 / 4.35;
        overflow: visible;
    }
    .profile-portrait {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
        object-position: center;
        border-radius: 18px;
        border: 1px solid #dfe6ef;
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.13);
    }
    .profile-portrait-placeholder {
        font-size: clamp(4rem, 10vw, 7rem);
        background: linear-gradient(145deg, #2563eb, #6857f5) !important;
    }
    .profile-photo-edit {
        right: .8rem;
        bottom: -.65rem;
        width: 44px;
        height: 44px;
        border: 3px solid #fff;
        text-decoration: none;
    }
    .profile-short-bio {
        line-height: 1.55;
    }
    .profile-skill-chip {
        display: inline-flex;
        align-items: center;
        padding: .48rem .7rem;
        border: 1px solid #dbe5ff;
        border-radius: 999px;
        background: #f5f8ff;
        color: #3159b7;
        font-size: .78rem;
        font-weight: 600;
        white-space: normal;
        line-height: 1.2;
    }
    .profile-detail-card .card-header {
        padding: 1rem 1.15rem .6rem;
        border: 0;
    }
    .profile-detail-card .card-body {
        padding: .75rem 1.15rem 1rem;
    }
    .profile-detail-card li:last-child {
        margin-bottom: 0 !important;
    }
    .profile-metrics {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        background: #fff;
    }
    .profile-metric {
        min-height: 126px;
        padding: 1.2rem .85rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    .profile-metric + .profile-metric {
        border-left: 1px solid #e9edf4;
    }
    .profile-metric-value {
        font-size: 1.85rem;
        line-height: 1;
        font-weight: 800;
    }
    .profile-metric-value small {
        font-size: .8rem;
        color: #8490a5;
    }
    .profile-metric-label {
        margin-top: .45rem;
        color: #68758a;
        font-size: .82rem;
    }
    .profile-stars {
        margin-top: .35rem;
        font-size: .68rem;
        letter-spacing: 1px;
    }
    .profile-section .card-body {
        padding: 1.45rem;
    }
    .profile-section-heading {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        margin-bottom: 1.2rem;
    }
    .profile-section-heading h2 {
        margin: 0;
        color: #13213a;
        font-size: 1.12rem;
        font-weight: 800;
    }
    .profile-section-heading p {
        margin: .2rem 0 0;
        color: #778399;
        font-size: .82rem;
    }
    .profile-section-icon {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        color: #3267e8;
        background: #edf3ff;
    }
    .profile-section-icon-success {
        color: #07855c;
        background: #e8faf3;
    }
    .profile-section-icon-warning {
        color: #b66a00;
        background: #fff5dd;
    }
    .profile-trust-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .8rem;
    }
    .profile-trust-item {
        display: flex;
        align-items: center;
        gap: .75rem;
        min-height: 74px;
        padding: .85rem;
        border: 1px solid #e6ebf2;
        border-radius: 14px;
        background: #fbfcfe;
    }
    .profile-trust-item > i {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: #edf1f7;
        color: #65738a;
    }
    .profile-trust-item.is-confirmed > i {
        color: #047857;
        background: #dff8ed;
    }
    .profile-trust-item.is-pending > i {
        color: #a76208;
        background: #fff3d6;
    }
    .profile-trust-item strong,
    .profile-trust-item span {
        display: block;
    }
    .profile-trust-item > div {
        min-width: 0;
    }
    .profile-trust-item strong {
        color: #26344d;
        font-size: .86rem;
    }
    .profile-trust-item span {
        margin-top: .16rem;
        color: #7c8799;
        font-size: .72rem;
        line-height: 1.3;
    }
    .profile-bio {
        color: #4d5a70;
        line-height: 1.75;
        white-space: pre-line;
    }
    .profile-ad-card {
        display: block;
        overflow: hidden;
        border: 1px solid #e6ebf2;
        border-radius: 15px;
        background: #fff;
        color: inherit;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .profile-ad-card:hover {
        transform: translateY(-2px);
        border-color: #b9cdfd;
        box-shadow: 0 12px 24px rgba(37, 99, 235, .1);
    }
    .profile-ad-card > img,
    .profile-ad-placeholder {
        width: 100%;
        height: 150px;
        object-fit: cover;
    }
    .profile-ad-placeholder {
        display: grid;
        place-items: center;
        color: #8290a5;
        background: linear-gradient(145deg, #edf1f7, #e2e8f0);
        font-size: 1.65rem;
    }
    .profile-ad-content {
        padding: .9rem;
    }
    .profile-ad-content h3 {
        margin: 0;
        overflow: hidden;
        color: #18243a;
        font-size: .92rem;
        font-weight: 750;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .profile-ad-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .65rem;
        margin-top: .6rem;
        color: #7b8799;
        font-size: .74rem;
    }
    .profile-ad-meta strong {
        color: #2563eb;
        font-size: .86rem;
    }
    .profile-review-card {
        padding: 1rem;
        border: 1px solid #e8ecf2;
        border-radius: 15px;
        background: #fbfcfe;
    }
    .profile-review-avatar {
        width: 34px;
        height: 34px;
        display: inline-grid;
        place-items: center;
        border-radius: 50%;
        color: #fff;
        background: linear-gradient(135deg, #4776ef, #7c4dff);
        font-size: .78rem;
    }
    @media (max-width: 991.98px) {
        .profile-sidebar-column {
            position: static;
        }
        .profile-photo-shell {
            max-width: 420px;
            margin-inline: auto;
            aspect-ratio: 4 / 3.7;
        }
    }
    @media (max-width: 575.98px) {
        .public-profile-page {
            width: 100%;
            max-width: 100%;
            padding-top: .8rem !important;
            padding-right: 12px !important;
            padding-left: 12px !important;
            overflow-x: hidden;
        }
        .public-profile-page > .row {
            margin-right: 0;
            margin-left: 0;
        }
        .public-profile-page > .row > [class*="col-"] {
            min-width: 0;
            padding-right: 0;
            padding-left: 0;
        }
        .profile-identity-card,
        .profile-detail-card,
        .profile-section,
        .profile-metrics {
            border-radius: 16px;
        }
        .profile-photo-shell {
            aspect-ratio: 1 / 1.02;
        }
        .profile-metric {
            min-height: 102px;
            padding: .85rem .35rem;
        }
        .profile-metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .profile-metric:nth-child(3) {
            grid-column: 1 / -1;
            border-top: 1px solid #e9edf4;
            border-left: 0;
        }
        .profile-metric-value {
            font-size: 1.35rem;
        }
        .profile-metric-label {
            font-size: .7rem;
        }
        .profile-stars {
            display: none;
        }
        .profile-section .card-body {
            padding: 1rem;
        }
        .profile-trust-grid {
            grid-template-columns: 1fr;
        }
        .profile-trust-item strong,
        .profile-trust-item span {
            overflow-wrap: anywhere;
        }
        .profile-section-heading p {
            line-height: 1.35;
        }
    }
</style>
@endpush

@push('scripts')
@auth
@if(auth()->id() === $user->id)
<script>
// --- Bio character counter ---
document.getElementById('editBio')?.addEventListener('input', function() {
    document.getElementById('editBioCount').textContent = this.value.length;
});

// --- Categories management ---
const categoriesConfig = @json(\App\Support\MarketplaceCategoryRegistry::enabledServices());
let selectedCategory = '';
let selectedSubcategories = [];

document.getElementById('categorySelect')?.addEventListener('change', function() {
    selectedCategory = this.value;
    const container = document.getElementById('subcategoriesContainer');
    const list = document.getElementById('subcategoriesList');
    list.innerHTML = '';

    if (!selectedCategory || !categoriesConfig[selectedCategory]) {
        container.classList.add('d-none');
        return;
    }

    const subcats = categoriesConfig[selectedCategory].subcategories || [];
    const existingSubcats = @json($user->service_subcategories ?? []);

    subcats.forEach(sub => {
        const isExisting = existingSubcats.includes(sub);
        const isSelected = selectedSubcategories.includes(sub);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm ' + (isExisting ? 'btn-secondary disabled' : (isSelected ? 'btn-primary' : 'btn-outline-primary'));
        btn.textContent = sub;
        if (isExisting) {
            btn.title = 'Déjà ajouté';
        } else {
            btn.onclick = function() {
                toggleSubcategory(sub, btn);
            };
        }
        list.appendChild(btn);
    });
    container.classList.remove('d-none');
});

function toggleSubcategory(sub, btn) {
    const idx = selectedSubcategories.indexOf(sub);
    if (idx > -1) {
        selectedSubcategories.splice(idx, 1);
        btn.className = 'btn btn-sm btn-outline-primary';
    } else {
        selectedSubcategories.push(sub);
        btn.className = 'btn btn-sm btn-primary';
    }
    updateSelectedDisplay();
}

function updateSelectedDisplay() {
    const container = document.getElementById('selectedSubcats');
    const list = document.getElementById('selectedSubcatsList');
    const saveBtn = document.getElementById('saveCategoriesBtn');

    if (selectedSubcategories.length > 0) {
        container.classList.remove('d-none');
        list.innerHTML = selectedSubcategories.map(s =>
            `<span class="badge bg-success px-2 py-1">${s} <i class="fas fa-times ms-1" style="cursor:pointer;" onclick="removeSubcat('${s.replace(/'/g, "\\'")}')"></i></span>`
        ).join('');
        saveBtn.disabled = false;
    } else {
        container.classList.add('d-none');
        list.innerHTML = '';
        saveBtn.disabled = true;
    }
}

function removeSubcat(sub) {
    selectedSubcategories = selectedSubcategories.filter(s => s !== sub);
    updateSelectedDisplay();
    // Update button state
    document.querySelectorAll('#subcategoriesList button').forEach(btn => {
        if (btn.textContent === sub) btn.className = 'btn btn-sm btn-outline-primary';
    });
}

function saveCategories() {
    if (!selectedCategory || selectedSubcategories.length === 0) return;
    const btn = document.getElementById('saveCategoriesBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enregistrement...';

    fetch('{{ route("profile.save-categories") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            service_category: selectedCategory,
            service_subcategories: selectedSubcategories
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const alert = document.getElementById('categorySaveAlert');
            alert.querySelector('span').textContent = data.message || 'Catégories enregistrées avec succès !';
            alert.classList.remove('d-none');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            alert('Erreur : ' + (data.message || 'Veuillez réessayer.'));
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-2"></i>Enregistrer';
        }
    })
    .catch(() => {
        alert('Erreur réseau. Veuillez réessayer.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-2"></i>Enregistrer';
    });
}
</script>
@endif
@endauth
@endpush
@endsection

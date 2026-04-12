@extends('layouts.app')

@section('title', 'Profil de ' . $user->name . ' - ProxiPro')

@push('meta')
    {{-- Open Graph for social sharing --}}
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $user->name }}{{ $user->profession ? ' — ' . $user->profession : '' }} | ProxiPro">
    <meta property="og:description" content="{{ $user->bio ? Str::limit($user->bio, 160) : ($user->profession ? $user->profession . ' sur ProxiPro. ' : '') . ($user->city ? 'Basé à ' . $user->city . '. ' : '') . 'Retrouvez ce professionnel sur ProxiPro.' }}">
    @if($user->avatar)
        <meta property="og:image" content="{{ storage_url($user->avatar) }}">
    @else
        <meta property="og:image" content="{{ asset('favicon.ico') }}">
    @endif
    <meta property="og:url" content="{{ route('profile.public', $user->id) }}">
    <meta property="og:site_name" content="ProxiPro">
    <meta property="og:locale" content="fr_FR">
    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $user->name }}{{ $user->profession ? ' — ' . $user->profession : '' }} | ProxiPro">
    <meta name="twitter:description" content="{{ $user->bio ? Str::limit($user->bio, 160) : 'Profil professionnel sur ProxiPro.' }}">
    @if($user->avatar)
        <meta name="twitter:image" content="{{ storage_url($user->avatar) }}">
    @endif
@endpush

@section('content')
<div class="container py-4">
    <div class="row">
        <!-- Profile Card - Sidebar gauche -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <!-- Avatar en format carte -->
                    <div class="position-relative d-inline-block mb-4">
                        @if($user->avatar)
                            <img src="{{ storage_url($user->avatar) }}" alt="Avatar" id="profileAvatarImg"
                                class="shadow" style="width: 140px; height: 140px; object-fit: cover; border-radius: 50%; border: 4px solid #e2e8f0;">
                        @else
                            <div class="bg-primary text-white d-inline-flex align-items-center justify-content-center shadow" id="profileAvatarPlaceholder"
                                style="width: 140px; height: 140px; font-size: 56px; border-radius: 50%; border: 4px solid #e2e8f0;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <img src="" alt="Avatar" id="profileAvatarImg" class="shadow d-none"
                                style="width: 140px; height: 140px; object-fit: cover; border-radius: 50%; border: 4px solid #e2e8f0;">
                        @endif
                        @auth
                            @if(auth()->id() === $user->id)
                                <label for="avatarUploadInput" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow"
                                       style="cursor: pointer; width: 40px; height: 40px; font-size: 0.9rem; border: 2px solid white;" title="Changer la photo">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <form id="avatarUploadForm" action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="d-none">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="name" value="{{ $user->name }}">
                                    <input type="hidden" name="email" value="{{ $user->email }}">
                                    <input type="file" id="avatarUploadInput" name="avatar" accept="image/jpeg,image/png,image/jpg,image/gif">
                                </form>
                            @endif
                        @endauth
                    </div>
                    
                    <!-- Name + Pro Badge -->
                    <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 mb-1">
                        <h4 class="fw-bold mb-0">{{ $user->name }}</h4>
                        @if($user->hasActiveProSubscription())
                            <span class="badge" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                                <i class="fas fa-briefcase me-1"></i>Pro
                            </span>
                        @elseif($user->user_type === 'professionnel' || $user->hasCompletedProOnboarding())
                            <span class="badge" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                <i class="fas fa-crown me-1"></i>Pro
                            </span>
                        @elseif($user->is_service_provider)
                            <span class="badge" style="background: linear-gradient(135deg, #10b981, #059669);">
                                <i class="fas fa-user-check me-1"></i>Prestataire
                            </span>
                        @endif
                    </div>
                    
                    <!-- Job Title / Profession -->
                    <p class="text-primary fw-semibold mb-1" style="word-break: break-word; overflow-wrap: break-word;">
                        @if($user->profession)
                            <i class="fas fa-briefcase me-1"></i>{{ Str::limit($user->profession, 80) }}
                        @elseif($user->is_service_provider && $user->services && $user->services->count() > 0)
                            <i class="fas fa-briefcase me-1"></i>{{ Str::limit($user->services->first()->subcategory ?? $user->services->first()->category ?? 'Prestataire de services', 80) }}
                        @endif
                    </p>
                    
                    {{-- Catégorie de service --}}
                    @if($user->service_category)
                        <p class="text-muted small mb-1" style="word-break: break-word; overflow-wrap: break-word;">
                            <i class="fas fa-th-large me-1"></i>{{ Str::limit($user->service_category, 120) }}
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
                    @if($user->service_subcategories && count($user->service_subcategories) > 0)
                        @php
                            $filteredSubcats = collect($user->service_subcategories)->filter(fn($s) => $s !== $user->profession);
                        @endphp
                        @if($filteredSubcats->isNotEmpty())
                        <div class="d-flex flex-wrap justify-content-center gap-1 mb-2">
                            @foreach($filteredSubcats as $subcat)
                                <span class="badge bg-light text-primary border px-2 py-1" style="font-size: 0.75rem; max-width: 100%; white-space: normal; word-break: break-word; text-align: center;">
                                    {{ Str::limit($subcat, 50) }}
                                </span>
                            @endforeach
                        </div>
                        @endif
                    @endif
                    
                    <!-- Localisation -->
                    @if($user->city && $user->country)
                        <p class="text-muted small mb-2">
                            <i class="fas fa-map-marker-alt me-1"></i>{{ $user->city }}, {{ $user->country }}
                        </p>
                    @endif
                    
                    <!-- Bio courte -->
                    @if($user->bio)
                        <p class="text-muted small mb-3">{{ Str::limit($user->bio, 80) }}</p>
                    @endif
                    
                    <!-- Rating -->
                    <div class="mb-3">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star {{ $i <= round($ratingAverage ?? 0) ? 'text-warning' : 'text-muted' }}"></i>
                        @endfor
                        <span class="ms-2 fw-bold">{{ number_format($ratingAverage ?? 0, 1) }}</span>
                        <span class="text-muted">({{ $ratingCount ?? 0 }} avis)</span>
                    </div>
                    
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
                        @elseif($user->is_verified)
                            <span class="badge bg-success px-3 py-2">
                                <i class="fas fa-check-circle me-1"></i>Profil vérifié
                            </span>
                        @else
                            <span class="badge bg-secondary px-3 py-2" style="opacity: 0.85;">
                                <i class="fas fa-user-times me-1"></i>Profil non vérifié
                            </span>
                        @endif
                    </div>

                    <!-- Contact Button -->
                    <div class="d-grid gap-2">
                        @auth
                            @if(auth()->id() !== $user->id)
                                <form action="{{ route('messages.create.conversation') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="recipient_id" value="{{ $user->id }}">
                                    <input type="hidden" name="message" value="Bonjour, je souhaite vous contacter.">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-envelope me-2"></i>Contacter
                                    </button>
                                </form>
                            @else
                                <button type="button" class="btn btn-outline-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                    <i class="fas fa-pen me-2"></i>Modifier le profil
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

                    <!-- Social Share Buttons -->
                    @php
                        $shareUrl = urlencode(route('profile.public', $user->id));
                        $shareTitle = urlencode($user->name . ($user->profession ? ' — ' . $user->profession : '') . ' | ProxiPro');
                        $shareDesc = urlencode($user->bio ? Str::limit($user->bio, 120) : 'Découvrez ce professionnel sur ProxiPro.');
                    @endphp
                    <div class="mt-3 pt-3 border-top">
                        <p class="text-muted small mb-2 text-center"><i class="fas fa-share-alt me-1"></i>Partager ce profil</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener"
                               class="btn btn-sm" style="background: #1877f2; color: white; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;"
                               title="Partager sur Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}" target="_blank" rel="noopener"
                               class="btn btn-sm" style="background: #1da1f2; color: white; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;"
                               title="Partager sur Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ $shareUrl }}&title={{ $shareTitle }}&summary={{ $shareDesc }}" target="_blank" rel="noopener"
                               class="btn btn-sm" style="background: #0a66c2; color: white; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;"
                               title="Partager sur LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="https://wa.me/?text={{ $shareTitle }}%20{{ $shareUrl }}" target="_blank" rel="noopener"
                               class="btn btn-sm" style="background: #25d366; color: white; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;"
                               title="Partager sur WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <button onclick="copyProfileLink()" class="btn btn-sm" style="background: #64748b; color: white; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;" title="Copier le lien" id="copyLinkBtn">
                                <i class="fas fa-link"></i>
                            </button>
                        </div>
                    </div>
                    <script>
                    function copyProfileLink() {
                        navigator.clipboard.writeText('{{ route('profile.public', $user->id) }}').then(() => {
                            const btn = document.getElementById('copyLinkBtn');
                            btn.innerHTML = '<i class="fas fa-check"></i>';
                            btn.style.background = '#16a34a';
                            setTimeout(() => { btn.innerHTML = '<i class="fas fa-link"></i>'; btn.style.background = '#64748b'; }, 2000);
                        });
                    }
                    </script>
                </div>
            </div>
            
            <!-- Info Card -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @if($user->city && $user->country)
                        <li class="mb-3 d-flex align-items-center">
                            <i class="fas fa-map-marker-alt text-muted me-3" style="width: 20px;"></i>
                            {{ $user->city }}, {{ $user->country }}
                        </li>
                        @elseif($user->location)
                        <li class="mb-3 d-flex align-items-center">
                            <i class="fas fa-map-marker-alt text-muted me-3" style="width: 20px;"></i>
                            {{ $user->location }}
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
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="fas fa-tools me-2 text-success"></i>Compétences</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($user->services as $service)
                            <span class="badge bg-light text-primary px-3 py-2">
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
        <div class="col-lg-8">
            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="display-6 fw-bold text-primary">{{ $stats['total_ads'] ?? 0 }}</div>
                            <div class="text-muted">Annonces actives</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="display-6 fw-bold text-warning">{{ number_format($ratingAverage ?? 0, 1) }}</div>
                            <div class="text-muted">Note moyenne</div>
                            <div class="mt-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= round($ratingAverage ?? 0) ? 'text-warning' : 'text-muted' }}"></i>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="display-6 fw-bold text-success">{{ $ratingCount ?? 0 }}</div>
                            <div class="text-muted">Avis reçus</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bio -->
            @if($user->bio)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="fas fa-quote-left me-2"></i>À propos</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $user->bio }}</p>
                </div>
            </div>
            @endif

            <!-- Leave Review Form -->
            @auth
                @if(auth()->id() !== $user->id)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-transparent">
                            <h6 class="mb-0"><i class="fas fa-star me-2 text-warning"></i>Laisser un avis</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('reviews.store', $user->id) }}" method="POST">
                                @csrf
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
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="fas fa-bullhorn me-2"></i>Annonces de {{ $user->name }}</h6>
                </div>
                <div class="card-body">
                    @if($ads->count() > 0)
                        <div class="row g-3">
                            @foreach($ads as $ad)
                                <div class="col-md-6">
                                    <a href="{{ route('ads.show', $ad) }}" class="d-flex align-items-center p-3 bg-light rounded-3 h-100 text-decoration-none" style="transition: background 0.15s;">
                                        @if($ad->photos && count($ad->photos) > 0)
                                            <img src="{{ storage_url($ad->photos[0]) }}" alt="" 
                                                 class="rounded me-3 flex-shrink-0" style="width: 60px; height: 60px; object-fit: cover;">
                                        @else
                                            <div class="bg-secondary rounded me-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                                                 style="width: 60px; height: 60px;">
                                                <i class="fas fa-image text-white"></i>
                                            </div>
                                        @endif
                                        <div class="flex-grow-1 min-width-0">
                                            <h6 class="mb-1 text-truncate text-dark">
                                                {{ $ad->title }}
                                            </h6>
                                            <div class="small text-muted">
                                                {{ $ad->created_at->diffForHumans() }}
                                            </div>
                                            <div class="small fw-bold text-primary mt-1">
                                                {{ number_format($ad->price, 0, ',', ' ') }} €
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
            </div>

            <!-- Reviews -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0"><i class="fas fa-comments me-2"></i>Avis sur {{ $user->name }}</h6>
                </div>
                <div class="card-body">
                    @if(isset($reviews) && $reviews->count() > 0)
                        <div class="d-flex flex-column gap-3">
                            @foreach($reviews as $review)
                                <div class="p-3 bg-light rounded-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="fw-semibold">
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
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-star-half-alt fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0">Aucun avis pour le moment.</p>
                        </div>
                    @endif
                </div>
            </div>
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
                        @foreach(config('categories.services', []) as $catName => $catData)
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
    .col-md-6 a.d-flex:hover, a.d-flex.bg-light:hover {
        background: #e2e8f0 !important;
    }
</style>
@endpush

@push('scripts')
@auth
@if(auth()->id() === $user->id)
<script>
// --- Avatar upload inline ---
document.getElementById('avatarUploadInput')?.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        alert('La photo ne doit pas dépasser 2 Mo.');
        return;
    }
    // Preview
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = document.getElementById('profileAvatarImg');
        img.src = e.target.result;
        img.classList.remove('d-none');
        const placeholder = document.getElementById('profileAvatarPlaceholder');
        if (placeholder) placeholder.classList.add('d-none');
    };
    reader.readAsDataURL(file);

    // Submit form via AJAX
    const form = document.getElementById('avatarUploadForm');
    const formData = new FormData(form);
    fetch('{{ route("profile.update") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: formData
    }).then(r => {
        if (r.ok || r.redirected) {
            // Success - reload to show updated avatar everywhere
            window.location.reload();
        } else {
            alert('Erreur lors du téléchargement de la photo.');
        }
    }).catch(() => alert('Erreur réseau. Veuillez réessayer.'));
});

// --- Bio character counter ---
document.getElementById('editBio')?.addEventListener('input', function() {
    document.getElementById('editBioCount').textContent = this.value.length;
});

// --- Categories management ---
const categoriesConfig = @json(config('categories.services', []));
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

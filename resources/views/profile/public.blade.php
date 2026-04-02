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
                    @if($user->avatar)
                        <img src="{{ storage_url($user->avatar) }}" alt="Avatar" 
                            class="mb-4 shadow" style="width: 140px; height: 140px; object-fit: cover; border-radius: 50%; border: 4px solid #e2e8f0;">
                    @else
                        <div class="bg-primary text-white d-inline-flex align-items-center justify-content-center mb-4 shadow" 
                            style="width: 140px; height: 140px; font-size: 56px; border-radius: 50%; border: 4px solid #e2e8f0;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                    
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
                                <a href="{{ route('profile.show') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-user me-2"></i>Voir mon tableau de bord
                                </a>
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
                                    <div class="d-flex align-items-center p-3 bg-light rounded-3 h-100">
                                        @if($ad->photos && count($ad->photos) > 0)
                                            <img src="{{ storage_url($ad->photos[0]) }}" alt="" 
                                                 class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                        @else
                                            <div class="bg-secondary rounded me-3 d-flex align-items-center justify-content-center" 
                                                 style="width: 60px; height: 60px;">
                                                <i class="fas fa-image text-white"></i>
                                            </div>
                                        @endif
                                        <div class="flex-grow-1 min-width-0">
                                            <h6 class="mb-1 text-truncate">
                                                <a href="{{ route('ads.show', $ad) }}" class="text-decoration-none text-dark">
                                                    {{ $ad->title }}
                                                </a>
                                            </h6>
                                            <div class="small text-muted">
                                                {{ $ad->created_at->diffForHumans() }}
                                            </div>
                                            <div class="small fw-bold text-primary mt-1">
                                                {{ number_format($ad->price, 0, ',', ' ') }} €
                                            </div>
                                        </div>
                                    </div>
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
@endsection

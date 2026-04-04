{{-- Profile Partial --}}
<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Profile Card -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    @if($user->avatar)
                        <img src="{{ storage_url($user->avatar) }}" alt="Avatar" 
                            class="mb-4" style="width: 180px; height: 180px; object-fit: cover; border-radius: 12px;">
                    @else
                        <div class="bg-primary text-white d-inline-flex align-items-center justify-content-center mb-4" 
                            style="width: 180px; height: 180px; font-size: 64px; border-radius: 12px;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                    
                    <h4 class="fw-bold mb-1">{{ $user->name }}</h4>
                    
                    @if($user->profession)
                        <p class="text-primary fw-semibold mb-1">
                            <i class="fas fa-briefcase me-1"></i>{{ $user->profession }}
                        </p>
                    @endif
                    
                    @if($user->service_category)
                        <p class="text-muted small mb-1">
                            <i class="fas fa-th-large me-1"></i>{{ $user->service_category }}
                        </p>
                    @endif
                    
                    @if($user->service_subcategories && count($user->service_subcategories) > 0)
                        <div class="d-flex flex-wrap justify-content-center gap-1 mb-2">
                            @foreach($user->service_subcategories as $subcat)
                                <span class="badge bg-light text-primary border px-2 py-1" style="font-size: 0.75rem;">
                                    {{ $subcat }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                    
                    @if($user->city && $user->country)
                        <p class="text-muted small mb-2">
                            <i class="fas fa-map-marker-alt me-1"></i>{{ $user->city }}, {{ $user->country }}
                        </p>
                    @endif
                    
                    <p class="text-muted mb-3">{{ $user->email }}</p>
                    
                    <!-- Badges -->
                    <div class="mb-4">
                        @if($user->hasActiveProSubscription())
                            <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;">
                                <i class="fas fa-briefcase me-1"></i>Professionnel
                            </span>
                        @elseif($user->user_type === 'professionnel' || $user->isProfessionnel())
                            <span class="badge bg-primary px-3 py-2">
                                <i class="fas fa-briefcase me-1"></i>Entrepreneur
                            </span>
                        @endif
                        
                        @if($user->is_service_provider && $user->service_provider_verified)
                            <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                                <i class="fas fa-user-check me-1"></i>Prestataire particulier vérifié
                            </span>
                        @elseif($user->is_verified)
                            <span class="badge bg-success px-3 py-2">
                                <i class="fas fa-check-circle me-1"></i>Profil vérifié
                            </span>
                        @else
                            <a href="{{ route('verification.index') }}" class="badge bg-secondary px-3 py-2 text-decoration-none">
                                <i class="fas fa-shield-alt me-1"></i>Vérifier mon profil
                            </a>
                        @endif
                    </div>
                    
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
                        <a href="#" onclick="dashboardNav('profile-edit'); return false;" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Modifier le profil
                        </a>
                        <a href="#" onclick="dashboardNav('settings'); return false;" class="btn btn-outline-secondary">
                            <i class="fas fa-cog me-2"></i>Paramètres
                        </a>
                    </div>
                </div>
            </div>
            
            @if($user->is_service_provider && $user->services && $user->services->count() > 0)
            <div class="card border-0 shadow-sm mt-4">
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

            <div class="card border-0 shadow-sm mt-4">
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
        <div class="col-lg-8">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="display-6 fw-bold text-primary">{{ $stats['total_ads'] }}</div>
                            <div class="text-muted">Annonces publiées</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="display-6 fw-bold text-success">{{ $stats['active_ads'] }}</div>
                            <div class="text-muted">Annonces actives</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="display-6 fw-bold text-info">{{ number_format($stats['total_views'], 0, ',', ' ') }}</div>
                            <div class="text-muted">Vues totales</div>
                        </div>
                    </div>
                </div>
            </div>
            
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
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-bullhorn me-2"></i>Mes dernières annonces</h6>
                    <a href="#" onclick="dashboardNav('my-ads'); return false;" class="btn btn-sm btn-outline-primary">
                        Voir tout
                    </a>
                </div>
                <div class="card-body">
                    @if($ads->count() > 0)
                        <div class="row g-3">
                            @foreach($ads as $ad)
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light rounded-3 overflow-hidden h-100">
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
                                            <h6 class="mb-1 text-truncate">
                                                <a href="{{ route('ads.show', $ad) }}" class="text-decoration-none text-dark">
                                                    {{ $ad->title }}
                                                </a>
                                            </h6>
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

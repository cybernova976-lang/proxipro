@extends('layouts.app')

@section('title', 'Mon Profil - ProxiPro')

@section('content')
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
                    <!-- Avatar -->
                    <div class="position-relative d-inline-block">
                    @if($user->avatar)
                            <img src="{{ storage_url($user->avatar) }}" alt="Avatar" id="profileAvatarImg"
                                class="mb-4 shadow rounded-3" style="width: 200px; height: 200px; object-fit: cover; border: 4px solid #e2e8f0;">
                    @else
                            <div class="bg-primary text-white d-inline-flex align-items-center justify-content-center mb-4 shadow rounded-3" id="profileAvatarPlaceholder"
                                style="width: 200px; height: 200px; font-size: 72px; border: 4px solid #e2e8f0;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <img src="" alt="Avatar" id="profileAvatarImg" class="mb-4 shadow rounded-3 d-none" style="width: 200px; height: 200px; object-fit: cover; border: 4px solid #e2e8f0;">
                    @endif
                        <label for="avatarUploadInput" class="position-absolute bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                               style="cursor: pointer; width: 44px; height: 44px; bottom: 20px; right: -5px; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="avatarUploadInput" class="d-none" accept="image/jpeg,image/png,image/jpg,image/gif">
                    </div>
                    
                    <h4 class="fw-bold mb-1">{{ $user->name }}</h4>
                    
                    {{-- Profession/Métier --}}
                    @if($user->profession)
                        <p class="text-primary fw-semibold mb-1">
                            <i class="fas fa-briefcase me-1"></i>{{ $user->profession }}
                        </p>
                    @endif
                    
                    {{-- Catégorie de service --}}
                    @if($user->service_category)
                        <p class="text-muted small mb-1">
                            <i class="fas fa-th-large me-1"></i>{{ $user->service_category }}
                        </p>
                    @endif
                    
                    {{-- Métiers / Sous-catégories --}}
                    @if($user->service_subcategories && count($user->service_subcategories) > 0)
                        <div class="d-flex flex-wrap justify-content-center gap-1 mb-2">
                            @foreach($user->service_subcategories as $subcat)
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
                        
                        {{-- Badge de vérification avec évolution --}}
                        @if($user->is_service_provider && $user->service_provider_verified)
                            {{-- Prestataire particulier vérifié (niveau max) --}}
                            <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                                <i class="fas fa-user-check me-1"></i>Prestataire particulier vérifié
                            </span>
                        @elseif($user->is_verified)
                            {{-- Profil vérifié uniquement --}}
                            <span class="badge bg-success px-3 py-2">
                                <i class="fas fa-check-circle me-1"></i>Profil vérifié
                            </span>
                        @else
                            {{-- Profil non vérifié - bouton pour vérifier --}}
                            <button type="button" class="badge bg-secondary px-3 py-2 border-0" data-bs-toggle="modal" data-bs-target="#verifyProfileModal" style="cursor: pointer;">
                                <i class="fas fa-shield-alt me-1"></i>Vérifier mon profil
                            </button>
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
                        <a href="{{ route('profile.edit') }}" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Modifier le profil
                        </a>
                        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-cog me-2"></i>Paramètres
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Compétences Prestataire -->
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

            <!-- Info Card -->
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
            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
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
                    <div class="card border-0 shadow-sm h-100">
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
                    <div class="card border-0 shadow-sm h-100">
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
            
            <!-- Recent Ads -->
            <div class="card border-0 shadow-sm">
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

{{-- Avatar upload script --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('avatarUploadInput');
    if (!avatarInput) return;
    
    avatarInput.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        
        if (file.size > 2 * 1024 * 1024) {
            alert('L\'image ne doit pas dépasser 2 Mo.');
            return;
        }

        const formData = new FormData();
        formData.append('avatar', file);
        formData.append('_method', 'PUT');
        formData.append('name', '{{ $user->name }}');
        formData.append('email', '{{ $user->email }}');

        fetch('{{ route("profile.update") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData
        }).then(response => {
            if (response.ok || response.status === 302) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('profileAvatarImg');
                    const placeholder = document.getElementById('profileAvatarPlaceholder');
                    if (img) {
                        img.src = e.target.result;
                        img.classList.remove('d-none');
                    }
                    if (placeholder) placeholder.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                alert('Erreur lors du téléchargement. Veuillez réessayer.');
            }
        }).catch(() => {
            alert('Erreur réseau. Veuillez réessayer.');
        });
    });
});

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

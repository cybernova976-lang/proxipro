@extends('admin.layouts.app')

@section('title', 'Détail Annonce - ' . $ad->title)

@section('content')
<div class="row mb-4">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.ads') }}">Annonces</a></li>
                <li class="breadcrumb-item active">{{ Str::limit($ad->title, 30) }}</li>
            </ol>
        </nav>
        <h2 class="h4 fw-bold">Détail de l'annonce</h2>
    </div>
    <div class="col-auto">
        <a href="{{ route('admin.ads') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Retour
        </a>
    </div>
</div>

<div class="row">
    <!-- Détail de l'annonce -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $ad->title }}</h5>
                    @php
                        $statusColors = [
                            'active' => 'success',
                            'pending' => 'warning',
                            'rejected' => 'danger',
                            'expired' => 'secondary'
                        ];
                    @endphp
                    <span class="badge bg-{{ $statusColors[$ad->status] ?? 'secondary' }} px-3 py-2">
                        {{ \App\Support\StatusLabels::ad($ad->status) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-2">
                            <i class="fas fa-folder text-muted me-2"></i>
                            <strong>Catégorie:</strong> {{ $ad->category }}
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-tag text-muted me-2"></i>
                            <strong>Type:</strong> 
                            <span class="badge bg-{{ $ad->service_type == 'offer' ? 'info' : 'primary' }}">
                                {{ $ad->service_type == 'offer' ? 'Offre de service' : 'Demande de service' }}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2">
                            <i class="fas fa-map-marker-alt text-muted me-2"></i>
                            <strong>Localisation:</strong> {{ $ad->location ?? 'Non spécifié' }}
                        </p>
                        <p class="mb-2">
                            <i class="fas fa-money-bill text-muted me-2"></i>
                            <strong>Prix:</strong> 
                            @if($ad->price)
                                <span class="text-success fw-bold">{{ number_format($ad->price, 0, ',', ' ') }} €</span>
                            @else
                                <span class="text-muted">À négocier</span>
                            @endif
                        </p>
                    </div>
                </div>
                
                <hr>
                
                <h6 class="text-muted mb-3">Description</h6>
                <div class="bg-light rounded p-3">
                    {!! nl2br(e($ad->description)) !!}
                </div>
                
                <hr>
                
                <div class="row text-muted small">
                    <div class="col-md-6">
                        <i class="fas fa-calendar me-1"></i>
                        Créée le {{ $ad->created_at->format('d/m/Y à H:i') }}
                    </div>
                    <div class="col-md-6 text-md-end">
                        <i class="fas fa-clock me-1"></i>
                        Mise à jour le {{ $ad->updated_at->format('d/m/Y à H:i') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Auteur -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0"><i class="fas fa-user me-2"></i>Auteur</h6>
            </div>
            <div class="card-body">
                @if($ad->user)
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-circle bg-primary text-white me-3">
                            {{ strtoupper(substr($ad->user->name, 0, 1)) }}
                        </div>
                        <div>
                            <strong>{{ $ad->user->name }}</strong>
                            <br>
                            <small class="text-muted">{{ $ad->user->email }}</small>
                        </div>
                    </div>
                    @if($ad->user->phone)
                        <p class="mb-2">
                            <i class="fas fa-phone text-muted me-2"></i>{{ $ad->user->phone }}
                        </p>
                    @endif
                    <a href="{{ route('admin.users.show', $ad->user->id) }}" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-eye me-2"></i>Voir le profil
                    </a>
                @else
                    <p class="text-muted mb-0">Auteur inconnu</p>
                @endif
            </div>
        </div>
        
        <!-- Modération -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0"><i class="fas fa-gavel me-2"></i>Modération</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.ads.update', $ad->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label class="form-label">Changer le statut</label>
                        <select name="status" class="form-select">
                            <option value="pending" {{ $ad->status == 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="active" {{ $ad->status == 'active' ? 'selected' : '' }}>Actif (approuvé)</option>
                            <option value="rejected" {{ $ad->status == 'rejected' ? 'selected' : '' }}>Rejeté</option>
                            <option value="expired" {{ $ad->status == 'expired' ? 'selected' : '' }}>Expiré</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>Mettre à jour
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Actions rapides -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Actions rapides</h6>
            </div>
            <div class="card-body">
                @if($ad->status == 'pending')
                    <form action="{{ route('admin.ads.update', $ad->id) }}" method="POST" class="mb-2">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="active">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-check me-2"></i>Approuver l'annonce
                        </button>
                    </form>
                    <form action="{{ route('admin.ads.update', $ad->id) }}" method="POST" class="mb-2">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-times me-2"></i>Rejeter l'annonce
                        </button>
                    </form>
                @elseif($ad->status == 'active')
                    <form action="{{ route('admin.ads.update', $ad->id) }}" method="POST" class="mb-2">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="pending">
                        <button type="submit" class="btn btn-secondary w-100">
                            <i class="fas fa-pause me-2"></i>Suspendre l'annonce
                        </button>
                    </form>
                @elseif($ad->status == 'rejected')
                    <form action="{{ route('admin.ads.update', $ad->id) }}" method="POST" class="mb-2">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="active">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-redo me-2"></i>Réactiver l'annonce
                        </button>
                    </form>
                @endif
                
                <form action="{{ route('admin.ads.delete', $ad->id) }}" 
                      method="POST"
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement cette annonce ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="fas fa-trash me-2"></i>Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>

        <!-- Boost / Urgent Management -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0"><i class="fas fa-rocket me-2"></i>Boost & Urgent</h6>
            </div>
            <div class="card-body">
                {{-- Current status --}}
                @php
                    $isBoostedActive = $ad->is_boosted && $ad->boost_end && $ad->boost_end->isFuture();
                    $isUrgentActive = $ad->is_urgent && $ad->urgent_until && $ad->urgent_until->isFuture();
                @endphp

                <div class="mb-3">
                    <small class="text-muted d-block mb-2">Statut actuel :</small>
                    @if($isBoostedActive)
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-warning text-dark px-3 py-2"><i class="fas fa-rocket me-1"></i>Boosté</span>
                            <small class="text-muted">→ {{ $ad->boost_end->format('d/m/Y H:i') }}</small>
                        </div>
                    @endif
                    @if($isUrgentActive)
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-danger px-3 py-2"><i class="fas fa-fire me-1"></i>Urgent</span>
                            <small class="text-muted">→ {{ $ad->urgent_until->format('d/m/Y H:i') }}</small>
                        </div>
                    @endif
                    @if(!$isBoostedActive && !$isUrgentActive)
                        <span class="badge bg-secondary px-3 py-2">Aucune visibilité spéciale</span>
                    @endif
                </div>

                <hr>

                {{-- Grant Boost --}}
                <form action="{{ route('admin.ads.grant-boost', $ad->id) }}" method="POST" class="mb-3">
                    @csrf
                    <label class="form-label small fw-semibold"><i class="fas fa-rocket text-warning me-1"></i>Accorder un boost</label>
                    <div class="d-flex gap-2">
                        <select name="boost_type" class="form-select form-select-sm">
                            <option value="boost_3">3 jours</option>
                            <option value="boost_7" selected>7 jours</option>
                            <option value="boost_15">15 jours</option>
                            <option value="boost_30">30 jours</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-warning text-white" style="white-space:nowrap">
                            <i class="fas fa-plus me-1"></i>Activer
                        </button>
                    </div>
                    @if($isBoostedActive)
                        <small class="text-muted mt-1 d-block">Le boost sera étendu à partir de la date d'expiration actuelle.</small>
                    @endif
                </form>

                {{-- Grant Urgent --}}
                <form action="{{ route('admin.ads.grant-urgent', $ad->id) }}" method="POST" class="mb-3">
                    @csrf
                    <label class="form-label small fw-semibold"><i class="fas fa-fire text-danger me-1"></i>Activer mode urgent</label>
                    <div class="d-flex gap-2">
                        <div class="input-group input-group-sm">
                            <input type="number" name="duration_days" class="form-control" value="7" min="1" max="90">
                            <span class="input-group-text">jours</span>
                        </div>
                        <button type="submit" class="btn btn-sm btn-danger" style="white-space:nowrap">
                            <i class="fas fa-plus me-1"></i>Activer
                        </button>
                    </div>
                    @if($isUrgentActive)
                        <small class="text-muted mt-1 d-block">Le mode urgent sera étendu à partir de la date d'expiration actuelle.</small>
                    @endif
                </form>

                {{-- Revoke buttons --}}
                @if($isBoostedActive)
                    <form action="{{ route('admin.ads.revoke-boost', $ad->id) }}" method="POST" class="mb-2" onsubmit="return confirm('Désactiver le boost ?')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning w-100">
                            <i class="fas fa-ban me-2"></i>Désactiver le boost
                        </button>
                    </form>
                @endif
                @if($isUrgentActive)
                    <form action="{{ route('admin.ads.revoke-urgent', $ad->id) }}" method="POST" onsubmit="return confirm('Désactiver le mode urgent ?')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                            <i class="fas fa-ban me-2"></i>Désactiver le mode urgent
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-circle {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: bold;
    }
</style>
@endsection

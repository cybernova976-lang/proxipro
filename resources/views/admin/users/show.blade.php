@extends('admin.layouts.app')

@section('title', 'Profil Utilisateur - ' . $user->name)

@section('content')
@php
    $isPrincipalAdmin = $user->isPrincipalAdmin();
@endphp
<div class="row mb-4">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users') }}">Utilisateurs</a></li>
                <li class="breadcrumb-item active">{{ $user->name }}</li>
            </ol>
        </nav>
        <h2 class="h4 fw-bold">Profil de {{ $user->name }}</h2>
    </div>
    <div class="col-auto">
        <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Retour
        </a>
    </div>
</div>

<div class="row">
    <!-- Informations utilisateur -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center py-4">
                <div class="avatar-large bg-primary text-white mx-auto mb-3">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
                <p class="text-muted mb-3">{{ $user->email }}</p>
                
                <div class="d-flex justify-content-center flex-wrap gap-2 mb-2">
                    <span class="badge bg-{{ ($user->role ?? 'user') == 'admin' ? 'danger' : 'primary' }} px-3 py-2">
                        {{ ucfirst($user->role ?? 'user') }}
                    </span>
                    @if($user->is_verified ?? false)
                        <span class="badge bg-success px-3 py-2">
                            <i class="fas fa-check-circle me-1"></i>Vérifié
                        </span>
                    @else
                        <span class="badge bg-warning text-dark px-3 py-2">Non vérifié</span>
                    @endif
                    @if($user->is_active ?? true)
                        <span class="badge bg-info px-3 py-2">Actif</span>
                    @else
                        <span class="badge bg-secondary px-3 py-2">Inactif</span>
                    @endif
                </div>
                <div class="d-flex justify-content-center flex-wrap gap-2 mb-3">
                    <span class="badge bg-light text-dark border px-3 py-2">{{ $user->getAccountTypeLabel() }}</span>
                    @if($user->isPremium())
                        <span class="badge bg-warning text-dark px-3 py-2"><i class="fas fa-crown me-1"></i>Premium</span>
                    @endif
                </div>
                
                <hr>
                
                <div class="text-start">
                    <p class="mb-2">
                        <i class="fas fa-calendar text-muted me-2"></i>
                        Inscrit le {{ $user->created_at->format('d/m/Y à H:i') }}
                    </p>
                    @if($user->phone)
                    <p class="mb-2">
                        <i class="fas fa-phone text-muted me-2"></i>
                        {{ $user->phone }}
                    </p>
                    @endif
                    @if($user->address)
                    <p class="mb-2">
                        <i class="fas fa-map-marker-alt text-muted me-2"></i>
                        {{ $user->address }}
                    </p>
                    @endif
                </div>
                
                @if($user->bio)
                <hr>
                <div class="text-start">
                    <h6 class="text-muted mb-2">Bio</h6>
                    <p class="mb-0">{{ $user->bio }}</p>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Actions rapides -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <h6 class="mb-0">Actions rapides</h6>
            </div>
            <div class="card-body">
                @if($isPrincipalAdmin)
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Administrateur Principal</strong><br>
                        Ce compte est protégé et ne peut pas être modifié ou supprimé.
                    </div>
                @else
                <form action="{{ route('admin.users.update', $user->id) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="name" value="{{ $user->name }}">
                    <input type="hidden" name="email" value="{{ $user->email }}">
                    <input type="hidden" name="is_verified" value="{{ $user->is_verified ? '0' : '1' }}">
                    <input type="hidden" name="is_active" value="{{ $user->is_active ?? true ? '1' : '0' }}">
                    <input type="hidden" name="role" value="{{ $user->role ?? 'user' }}">
                    <button type="submit" class="btn btn-{{ $user->is_verified ? 'warning' : 'success' }} w-100">
                        @if($user->is_verified)
                            <i class="fas fa-times me-2"></i>Retirer la vérification
                        @else
                            <i class="fas fa-check me-2"></i>Marquer comme vérifié
                        @endif
                    </button>
                </form>
                
                <form action="{{ route('admin.users.update', $user->id) }}" method="POST" class="mb-2">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="name" value="{{ $user->name }}">
                    <input type="hidden" name="email" value="{{ $user->email }}">
                    <input type="hidden" name="is_verified" value="{{ $user->is_verified ? '1' : '0' }}">
                    <input type="hidden" name="is_active" value="{{ ($user->is_active ?? true) ? '0' : '1' }}">
                    <input type="hidden" name="role" value="{{ $user->role ?? 'user' }}">
                    <button type="submit" class="btn btn-{{ ($user->is_active ?? true) ? 'secondary' : 'info' }} w-100">
                        @if($user->is_active ?? true)
                            <i class="fas fa-ban me-2"></i>Désactiver le compte
                        @else
                            <i class="fas fa-check me-2"></i>Réactiver le compte
                        @endif
                    </button>
                </form>
                
                @if(Auth::id() !== $user->id)
                <form action="{{ route('admin.users.delete', $user->id) }}" method="POST"
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="fas fa-trash me-2"></i>Supprimer le compte
                    </button>
                </form>
                @endif
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        @if(! $isPrincipalAdmin)
        <!-- Statut du compte : Premium et rôle administrateur -->
        <div class="row g-4 mb-4">
            <div class="col-xl-6">
                @include('admin.users.partials.premium')
            </div>
            <div class="col-xl-6">
                @include('admin.users.partials.admin-role')
            </div>
        </div>
        @endif

        <!-- Abonnements, paiements et commandes du compte -->
        @include('admin.users.partials.purchases')

        <!-- Annonces de l'utilisateur -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-bullhorn me-2 text-primary"></i>
                    Annonces de {{ $user->name }}
                    <span class="badge bg-primary ms-2">{{ $user->ads->count() }}</span>
                </h5>
            </div>
            <div class="card-body p-0">
                @if($user->ads->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 ps-4">Titre</th>
                                <th class="border-0">Catégorie</th>
                                <th class="border-0">Prix</th>
                                <th class="border-0">Statut</th>
                                <th class="border-0">Date</th>
                                <th class="border-0 pe-4 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($user->ads as $ad)
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('admin.ads.show', $ad->id) }}" class="text-decoration-none">
                                        {{ Str::limit($ad->title, 30) }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ $ad->category }}</span>
                                </td>
                                <td>
                                    @if($ad->price)
                                        {{ number_format($ad->price, 0, ',', ' ') }} €
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $ad->status == 'active' ? 'success' : ($ad->status == 'pending' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($ad->status) }}
                                    </span>
                                </td>
                                <td>{{ $ad->created_at->format('d/m/Y') }}</td>
                                <td class="pe-4 text-end">
                                    <a href="{{ route('admin.ads.show', $ad->id) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form action="{{ route('admin.ads.delete', $ad->id) }}" 
                                          method="POST" 
                                          class="d-inline"
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Cet utilisateur n'a pas encore publié d'annonces</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-large {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        font-weight: bold;
    }
</style>
@endsection

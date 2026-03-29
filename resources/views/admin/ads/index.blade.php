@extends('admin.layouts.app')

@section('title', 'Gestion des Annonces')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h2 class="h4 fw-bold">Gestion des Annonces</h2>
        <p class="text-muted mb-0">Modération et gestion des annonces de la plateforme</p>
    </div>
</div>

<!-- Filtres et recherche -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.ads') }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0" 
                               placeholder="Rechercher..." 
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select">
                        <option value="">Toutes les catégories</option>
                        @foreach(array_merge(array_keys(config('categories.services')), array_keys(config('categories.marketplace'))) as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejeté</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expiré</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="service_type" class="form-select">
                        <option value="">Tous les types</option>
                        <option value="offer" {{ request('service_type') == 'offer' ? 'selected' : '' }}>Offre</option>
                        <option value="request" {{ request('service_type') == 'request' ? 'selected' : '' }}>Demande</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.ads') }}" class="btn btn-outline-secondary w-100">
                        Réinitialiser
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Statistiques rapides -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 bg-light">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-bullhorn text-primary me-3 fa-2x"></i>
                    <div>
                        <h4 class="mb-0">{{ $stats['total'] ?? $ads->total() }}</h4>
                        <small class="text-muted">Total</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-light">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle text-success me-3 fa-2x"></i>
                    <div>
                        <h4 class="mb-0">{{ $stats['active'] ?? 0 }}</h4>
                        <small class="text-muted">Actives</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-light">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-clock text-warning me-3 fa-2x"></i>
                    <div>
                        <h4 class="mb-0">{{ $stats['pending'] ?? 0 }}</h4>
                        <small class="text-muted">En attente</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-light">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-times-circle text-danger me-3 fa-2x"></i>
                    <div>
                        <h4 class="mb-0">{{ $stats['rejected'] ?? 0 }}</h4>
                        <small class="text-muted">Rejetées</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Liste des annonces -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0 py-3 ps-4">ID</th>
                        <th class="border-0 py-3">Annonce</th>
                        <th class="border-0 py-3">Auteur</th>
                        <th class="border-0 py-3">Catégorie</th>
                        <th class="border-0 py-3">Type</th>
                        <th class="border-0 py-3">Prix</th>
                        <th class="border-0 py-3">Statut</th>
                        <th class="border-0 py-3">Date</th>
                        <th class="border-0 py-3 pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ads as $ad)
                    <tr>
                        <td class="ps-4">{{ $ad->id }}</td>
                        <td>
                            <div>
                                <a href="{{ route('admin.ads.show', $ad->id) }}" class="text-decoration-none fw-bold">
                                    {{ Str::limit($ad->title, 35) }}
                                </a>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i>{{ $ad->location ?? 'Non spécifié' }}
                                </small>
                            </div>
                        </td>
                        <td>
                            @if($ad->user)
                                <a href="{{ route('admin.users.show', $ad->user->id) }}" class="text-decoration-none">
                                    {{ $ad->user->name }}
                                </a>
                            @else
                                <span class="text-muted">Anonyme</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">{{ $ad->category }}</span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $ad->service_type == 'offer' ? 'info' : 'secondary' }}">
                                {{ $ad->service_type == 'offer' ? 'Offre' : 'Demande' }}
                            </span>
                        </td>
                        <td>
                            @if($ad->price)
                                {{ number_format($ad->price, 0, ',', ' ') }} FCFA
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $statusColors = [
                                    'active' => 'success',
                                    'pending' => 'warning',
                                    'rejected' => 'danger',
                                    'expired' => 'secondary'
                                ];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$ad->status] ?? 'secondary' }}">
                                {{ ucfirst($ad->status) }}
                            </span>
                        </td>
                        <td>{{ $ad->created_at->format('d/m/Y') }}</td>
                        <td class="pe-4 text-end">
                            <a href="{{ route('admin.ads.show', $ad->id) }}" 
                               class="btn btn-sm btn-outline-primary" title="Voir">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($ad->status == 'pending')
                                <form action="{{ route('admin.ads.update', $ad->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="active">
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Approuver">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.ads.update', $ad->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Rejeter">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('admin.ads.delete', $ad->id) }}" 
                                  method="POST" 
                                  class="d-inline"
                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aucune annonce trouvée</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($ads->hasPages())
    <div class="card-footer bg-white border-0 py-3">
        {{ $ads->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection

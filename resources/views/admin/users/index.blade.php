@extends('admin.layouts.app')

@section('title', 'Gestion des Utilisateurs')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h2 class="h4 fw-bold">Gestion des Utilisateurs</h2>
        <p class="text-muted mb-0">Particuliers et professionnels inscrits sur la plateforme</p>
    </div>
</div>

@php
    $listes = [
        'particuliers' => ['label' => 'Particuliers', 'icon' => 'fa-user'],
        'professionnels' => ['label' => 'Professionnels', 'icon' => 'fa-briefcase'],
    ];
    $filtres = array_filter(request()->only(['search', 'role', 'status']), fn ($valeur) => filled($valeur));
@endphp

<!-- Les deux listes, avec le nombre d'inscrits de chacune -->
<nav class="row g-3 mb-4" aria-label="Listes d’utilisateurs">
    @foreach($listes as $cle => $info)
        @php
            $inscrits = $registeredCounts[$cle];
            $correspondances = $matchingCounts[$cle] ?? null;
        @endphp
        <div class="col-md-6">
            <a href="{{ route('admin.users', $filtres + ['liste' => $cle]) }}"
               class="card shadow-sm h-100 text-decoration-none border border-2 {{ $liste === $cle ? 'border-primary' : 'border-light' }}"
               data-liste="{{ $cle }}" aria-current="{{ $liste === $cle ? 'page' : 'false' }}">
                <div class="card-body d-flex align-items-center justify-content-between gap-3">
                    <div>
                        <div class="fw-bold {{ $liste === $cle ? 'text-primary' : 'text-dark' }}">
                            <i class="fas {{ $info['icon'] }} me-2"></i>{{ $info['label'] }}
                        </div>
                        @if($correspondances !== null)
                            <small class="text-muted">
                                @if($correspondances === 0)
                                    Aucun ne correspond aux filtres
                                @elseif($correspondances === 1)
                                    1 correspond aux filtres
                                @else
                                    {{ $correspondances }} correspondent aux filtres
                                @endif
                            </small>
                        @endif
                    </div>
                    <div class="text-end">
                        <div class="h3 fw-bold text-dark mb-0" data-compteur="{{ $cle }}">{{ $inscrits }}</div>
                        <small class="text-muted">{{ $inscrits > 1 ? 'inscrits' : 'inscrit' }}</small>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</nav>

<!-- Filtres et recherche -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.users') }}">
            <input type="hidden" name="liste" value="{{ $liste }}">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0" 
                               placeholder="Rechercher par nom ou email..." 
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="role" class="form-select">
                        <option value="">Tous les rôles</option>
                        <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>Utilisateur</option>
                        <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Vérifié</option>
                        <option value="unverified" {{ request('status') == 'unverified' ? 'selected' : '' }}>Non vérifié</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactif</option>
                    </select>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Filtrer
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.users', ['liste' => $liste]) }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-1"></i>Réinitialiser
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Liste des utilisateurs -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0 py-3 ps-4">ID</th>
                        <th class="border-0 py-3">Nom</th>
                        <th class="border-0 py-3">Email</th>
                        <th class="border-0 py-3">Rôle</th>
                        <th class="border-0 py-3">Type</th>
                        <th class="border-0 py-3">Annonces</th>
                        <th class="border-0 py-3">Statut</th>
                        <th class="border-0 py-3">Inscrit le</th>
                        <th class="border-0 py-3 pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    @php
                        $isPrincipalAdmin = $user->email === config('admin.principal_admin.email');
                        if ($user->isRegisteredAsProfessional()) {
                            $userType = match (true) {
                                $user->business_type === 'entreprise', $user->user_type === 'entreprise' => ['label' => 'Entreprise', 'color' => 'primary', 'icon' => 'fa-building'],
                                $user->business_type === 'auto_entrepreneur' => ['label' => 'Auto-entrepreneur', 'color' => 'success', 'icon' => 'fa-briefcase'],
                                default => ['label' => 'Professionnel', 'color' => 'success', 'icon' => 'fa-briefcase'],
                            };
                        } else {
                            // Un particulier peut proposer des services sans être un professionnel.
                            $userType = $user->is_service_provider
                                ? ['label' => 'Particulier prestataire', 'color' => 'info', 'icon' => 'fa-user-tie']
                                : ['label' => 'Particulier', 'color' => 'info', 'icon' => 'fa-user'];
                        }
                    @endphp
                    <tr>
                        <td class="ps-4">{{ $user->id }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle bg-primary text-white me-2">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <strong>{{ $user->name }}</strong>
                                    @if($isPrincipalAdmin)
                                        <i class="fas fa-shield-alt text-warning ms-1" title="Admin Principal"></i>
                                    @endif
                                    @if($user->phone)
                                        <br><small class="text-muted">{{ $user->phone }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge bg-{{ $user->role == 'admin' ? 'danger' : 'secondary' }}">
                                {{ ucfirst($user->role ?? 'user') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $userType['color'] }}" title="{{ $userType['label'] }}">
                                <i class="fas {{ $userType['icon'] }} me-1"></i>{{ $userType['label'] }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $user->ads_count ?? $user->ads->count() }}</span>
                        </td>
                        <td>
                            @if($user->is_verified ?? false)
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i>Vérifié
                                </span>
                            @else
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-clock me-1"></i>Non vérifié
                                </span>
                            @endif
                            @if(!($user->is_active ?? true))
                                <span class="badge bg-danger">Inactif</span>
                            @endif
                        </td>
                        <td>{{ $user->created_at->format('d/m/Y') }}</td>
                        <td class="pe-4 text-end">
                            <a href="{{ route('admin.users.show', $user->id) }}" 
                               class="btn btn-sm btn-outline-primary" title="Voir">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-warning" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editModal{{ $user->id }}" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            @if(Auth::id() !== $user->id)
                            <form action="{{ route('admin.users.delete', $user->id) }}" 
                                  method="POST" 
                                  class="d-inline"
                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>

                    <!-- Modal de modification -->
                    <div class="modal fade" id="editModal{{ $user->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Modifier {{ $user->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Nom</label>
                                            <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Téléphone</label>
                                            <input type="text" name="phone" class="form-control" value="{{ $user->phone ?? '' }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Rôle</label>
                                            <select name="role" class="form-select">
                                                <option value="user" {{ ($user->role ?? 'user') == 'user' ? 'selected' : '' }}>Utilisateur</option>
                                                <option value="admin" {{ ($user->role ?? 'user') == 'admin' ? 'selected' : '' }}>Administrateur</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_verified" value="1"
                                                       {{ $user->is_verified ?? false ? 'checked' : '' }}>
                                                <label class="form-check-label">Vérifié</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                                       {{ $user->is_active ?? true ? 'checked' : '' }}>
                                                <label class="form-check-label">Actif</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ ['particuliers' => 'Aucun particulier trouvé', 'professionnels' => 'Aucun professionnel trouvé'][$liste] ?? 'Aucun utilisateur trouvé' }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
    <div class="card-footer bg-white border-0 py-3">
        {{ $users->links('admin.partials.pagination') }}
    </div>
    @endif
</div>

<style>
    .avatar-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: bold;
    }
</style>
@endsection

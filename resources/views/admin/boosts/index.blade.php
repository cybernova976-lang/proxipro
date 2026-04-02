@extends('admin.layouts.app')

@section('title', 'Gestion Boosts & Urgents')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h2 class="h4 fw-bold"><i class="fas fa-rocket me-2"></i>Gestion Boosts & Urgents</h2>
        <p class="text-muted mb-0">Gérez les annonces boostées et urgentes de la plateforme</p>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                    <i class="fas fa-rocket text-primary fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small">Boosts actifs</div>
                    <div class="h4 mb-0 fw-bold">{{ $stats['active_boosts'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3">
                    <i class="fas fa-fire text-danger fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small">Urgents actifs</div>
                    <div class="h4 mb-0 fw-bold">{{ $stats['active_urgents'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                    <i class="fas fa-hourglass-end text-warning fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small">Boosts expirés</div>
                    <div class="h4 mb-0 fw-bold">{{ $stats['expired_boosts'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-secondary bg-opacity-10 p-3 me-3">
                    <i class="fas fa-clock text-secondary fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small">Urgents expirés</div>
                    <div class="h4 mb-0 fw-bold">{{ $stats['expired_urgents'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.boosts') }}">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Rechercher une annonce..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="visibility_type" class="form-select">
                        <option value="">Tous</option>
                        <option value="boosted" {{ request('visibility_type') == 'boosted' ? 'selected' : '' }}>Boostés actifs</option>
                        <option value="urgent" {{ request('visibility_type') == 'urgent' ? 'selected' : '' }}>Urgents actifs</option>
                        <option value="expired" {{ request('visibility_type') == 'expired' ? 'selected' : '' }}>Expirés</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Filtrer</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.boosts') }}" class="btn btn-outline-secondary w-100">Réinitialiser</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tableau -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Annonce</th>
                        <th>Auteur</th>
                        <th>Statut visibilité</th>
                        <th>Type boost</th>
                        <th>Expire le</th>
                        <th>Jours restants</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ads as $ad)
                        @php
                            $isBoostedActive = $ad->is_boosted && $ad->boost_end && $ad->boost_end->isFuture();
                            $isUrgentActive = $ad->is_urgent && $ad->urgent_until && $ad->urgent_until->isFuture();
                            $isPermanentUrgent = $ad->is_urgent && is_null($ad->urgent_until);
                        @endphp
                        <tr>
                            <td class="ps-4 text-muted">#{{ $ad->id }}</td>
                            <td>
                                <a href="{{ route('admin.ads.show', $ad->id) }}" class="text-decoration-none fw-semibold">
                                    {{ Str::limit($ad->title, 35) }}
                                </a>
                                <br><small class="text-muted">{{ $ad->location }}</small>
                            </td>
                            <td>
                                @if($ad->user)
                                    <a href="{{ route('admin.users.show', $ad->user->id) }}" class="text-decoration-none">
                                        {{ $ad->user->name }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($isUrgentActive || $isPermanentUrgent)
                                    <span class="badge bg-danger px-3 py-2"><i class="fas fa-fire me-1"></i>Urgent</span>
                                @endif
                                @if($isBoostedActive)
                                    <span class="badge bg-warning text-dark px-3 py-2"><i class="fas fa-rocket me-1"></i>Boosté</span>
                                @endif
                                @if(!$isBoostedActive && !$isUrgentActive && !$isPermanentUrgent)
                                    <span class="badge bg-secondary px-3 py-2">Expiré</span>
                                @endif
                            </td>
                            <td>
                                @if($ad->boost_type)
                                    @php
                                        $typeLabels = ['boost_3' => 'Standard 3j', 'boost_7' => 'Standard 7j', 'boost_15' => 'Premium 15j', 'boost_30' => 'VIP 30j'];
                                    @endphp
                                    <span class="text-muted small">{{ $typeLabels[$ad->boost_type] ?? $ad->boost_type }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($isUrgentActive)
                                    <span class="text-danger small">{{ $ad->urgent_until->format('d/m/Y H:i') }}</span>
                                @endif
                                @if($isBoostedActive)
                                    <span class="text-warning small">{{ $ad->boost_end->format('d/m/Y H:i') }}</span>
                                @endif
                                @if($isPermanentUrgent)
                                    <span class="text-muted small">Permanent</span>
                                @endif
                                @if(!$isBoostedActive && !$isUrgentActive && !$isPermanentUrgent)
                                    <span class="text-muted small">
                                        {{ $ad->boost_end ? $ad->boost_end->format('d/m/Y') : ($ad->urgent_until ? $ad->urgent_until->format('d/m/Y') : '—') }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($isBoostedActive)
                                    @php $daysLeft = max(0, (int) now()->diffInDays($ad->boost_end, false)); @endphp
                                    <span class="badge {{ $daysLeft <= 1 ? 'bg-danger' : ($daysLeft <= 3 ? 'bg-warning text-dark' : 'bg-success') }}">{{ $daysLeft }}j</span>
                                @elseif($isUrgentActive)
                                    @php $daysLeft = max(0, (int) now()->diffInDays($ad->urgent_until, false)); @endphp
                                    <span class="badge {{ $daysLeft <= 1 ? 'bg-danger' : ($daysLeft <= 3 ? 'bg-warning text-dark' : 'bg-success') }}">{{ $daysLeft }}j</span>
                                @elseif($isPermanentUrgent)
                                    <span class="badge bg-info">∞</span>
                                @else
                                    <span class="badge bg-secondary">0j</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('admin.ads.show', $ad->id) }}" class="btn btn-sm btn-outline-primary" title="Voir"><i class="fas fa-eye"></i></a>
                                    
                                    @if($isBoostedActive)
                                        <form action="{{ route('admin.ads.revoke-boost', $ad->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Désactiver le boost ?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-warning" title="Révoquer boost"><i class="fas fa-rocket"></i><i class="fas fa-times ms-1" style="font-size:0.6rem"></i></button>
                                        </form>
                                    @endif
                                    
                                    @if($isUrgentActive || $isPermanentUrgent)
                                        <form action="{{ route('admin.ads.revoke-urgent', $ad->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Désactiver le mode urgent ?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger" title="Révoquer urgent"><i class="fas fa-fire"></i><i class="fas fa-times ms-1" style="font-size:0.6rem"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-rocket fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">Aucune annonce boostée ou urgente trouvée.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($ads->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $ads->withQueryString()->links() }}
    </div>
@endif
@endsection

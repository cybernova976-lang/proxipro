@extends('admin.layouts.app')

@section('title', 'Statistiques')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h2 class="h4 fw-bold">
            <i class="fas fa-chart-bar text-primary me-2"></i>Statistiques de la Plateforme
        </h2>
        <p class="text-muted mb-0">Analyses détaillées et rapports de performance</p>
    </div>
    <div class="col-auto">
        <button class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-download me-2"></i>Exporter
        </button>
    </div>
</div>

<!-- Statistiques générales -->
<div class="row g-4 mb-4">
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card border-0 bg-primary bg-gradient text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Utilisateurs</h6>
                        <h2 class="card-title mb-0">{{ number_format($generalStats['total_users']) }}</h2>
                    </div>
                    <i class="fas fa-users fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card border-0 bg-success bg-gradient text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Annonces</h6>
                        <h2 class="card-title mb-0">{{ number_format($generalStats['total_ads']) }}</h2>
                    </div>
                    <i class="fas fa-bullhorn fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card border-0 bg-info bg-gradient text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Actives</h6>
                        <h2 class="card-title mb-0">{{ number_format($generalStats['active_ads']) }}</h2>
                    </div>
                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card border-0 bg-warning bg-gradient text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Vérifiés</h6>
                        <h2 class="card-title mb-0">{{ number_format($generalStats['verified_users']) }}</h2>
                    </div>
                    <i class="fas fa-user-check fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card border-0 bg-danger bg-gradient text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Premium</h6>
                        <h2 class="card-title mb-0">{{ number_format($generalStats['premium_users']) }}</h2>
                    </div>
                    <i class="fas fa-crown fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card border-0 bg-secondary bg-gradient text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Taux vérif.</h6>
                        <h2 class="card-title mb-0">
                            {{ $generalStats['total_users'] > 0 ? number_format($generalStats['verified_users'] / $generalStats['total_users'] * 100, 1) : 0 }}%
                        </h2>
                    </div>
                    <i class="fas fa-percentage fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Graphiques -->
<div class="row g-4 mb-4">
    <!-- Inscriptions -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus me-2 text-primary"></i>
                    Inscriptions (30 derniers jours)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="registrationsChart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Annonces -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-bullhorn me-2 text-success"></i>
                    Nouvelles annonces (30 derniers jours)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="adsChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Répartition par catégorie -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-tags me-2 text-info"></i>
                    Top 10 Catégories
                </h5>
            </div>
            <div class="card-body">
                <canvas id="categoriesChart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Répartition des plans -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-crown me-2 text-warning"></i>
                    Répartition des Plans
                </h5>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div style="max-width: 300px; width: 100%;">
                    <canvas id="plansChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top utilisateurs -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-trophy me-2 text-warning"></i>
                    Top 10 Utilisateurs (par annonces)
                </h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 ps-4">#</th>
                            <th class="border-0">Utilisateur</th>
                            <th class="border-0 text-end pe-4">Annonces</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topUsers as $index => $user)
                        <tr>
                            <td class="ps-4">
                                @if($index === 0)
                                    <span class="badge bg-warning text-dark">🥇</span>
                                @elseif($index === 1)
                                    <span class="badge bg-secondary">🥈</span>
                                @elseif($index === 2)
                                    <span class="badge bg-danger">🥉</span>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </td>
                            <td>
                                <x-admin.user-link :user="$user" :fiche="true" />
                            </td>
                            <td class="text-end pe-4">
                                <span class="badge bg-primary">{{ $user->ads_count }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Répartition par type d'utilisateur -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2 text-primary"></i>
                    Types d'Utilisateurs
                </h5>
            </div>
            <div class="card-body">
                @php
                    $userTypes = config('admin.user_types');
                @endphp
                @forelse($usersByType as $type)
                    @php
                        $typeConfig = $userTypes[$type->user_type ?? 'particulier'] ?? $userTypes['particulier'];
                        $percentage = $generalStats['total_users'] > 0 ? ($type->count / $generalStats['total_users']) * 100 : 0;
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>
                                <i class="fas {{ $typeConfig['icon'] }} text-{{ $typeConfig['color'] }} me-2"></i>
                                {{ $typeConfig['label'] }}
                            </span>
                            <span class="fw-bold">{{ $type->count }} ({{ number_format($percentage, 1) }}%)</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-{{ $typeConfig['color'] }}" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <p>Aucune donnée disponible</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Recensement des utilisateurs -->
<div class="card border-0 shadow-sm mt-4 mb-4">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-clipboard-list me-2 text-primary"></i>
            Recensement des comptes utilisateurs
            <span class="badge bg-primary ms-2">{{ $allUsers->count() }}</span>
        </h5>
        <div class="d-flex gap-2 align-items-center">
            <input type="text" id="userCensusSearch" class="form-control form-control-sm" placeholder="Rechercher..." style="width: 200px;">
            <select id="userCensusFilter" class="form-select form-select-sm" style="width: 180px;">
                <option value="all">Tous les statuts</option>
                <option value="verified">Vérifiés</option>
                <option value="unverified">Non vérifiés</option>
                <option value="active">Actifs</option>
                <option value="inactive">Désactivés</option>
                <option value="complete">Profil complet (≥80%)</option>
                <option value="incomplete">Profil incomplet (&lt;50%)</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- Résumé rapide -->
        <div class="d-flex flex-wrap gap-3 px-4 py-3 bg-light border-bottom">
            <div class="d-flex align-items-center gap-2">
                <span class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: #dcfce7;">
                    <i class="fas fa-check text-success" style="font-size: 0.7rem;"></i>
                </span>
                <small><strong>{{ $allUsers->where('is_verified', true)->count() }}</strong> vérifiés</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: #fee2e2;">
                    <i class="fas fa-times text-danger" style="font-size: 0.7rem;"></i>
                </span>
                <small><strong>{{ $allUsers->where('is_verified', false)->count() }}</strong> non vérifiés</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: #dbeafe;">
                    <i class="fas fa-user-tie text-primary" style="font-size: 0.7rem;"></i>
                </span>
                <small><strong>{{ $allUsers->filter(fn($u) => $u->isProfessionnel())->count() }}</strong> professionnels</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: #fef3c7;">
                    <i class="fas fa-star text-warning" style="font-size: 0.7rem;"></i>
                </span>
                <small><strong>{{ $allUsers->where('profile_completion', '>=', 80)->count() }}</strong> profils complets (≥80%)</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: #fce7f3;">
                    <i class="fas fa-exclamation text-danger" style="font-size: 0.7rem;"></i>
                </span>
                <small><strong>{{ $allUsers->where('profile_completion', '<', 50)->count() }}</strong> profils incomplets (&lt;50%)</small>
            </div>
        </div>

        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0" id="userCensusTable">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="ps-4" style="width: 40px;">#</th>
                        <th>Utilisateur</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Configuration profil</th>
                        <th>Annonces</th>
                        <th>Inscrit le</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allUsers as $index => $u)
                    <tr class="user-census-row" 
                        data-name="{{ strtolower($u->name) }}" 
                        data-email="{{ strtolower($u->email) }}"
                        data-verified="{{ $u->is_verified ? 'verified' : 'unverified' }}"
                        data-active="{{ ($u->is_active ?? true) ? 'active' : 'inactive' }}"
                        data-completion="{{ $u->profile_completion }}"
                        style="cursor: pointer;"
                        onclick="window.location='{{ route('admin.users.show', $u->id) }}'">
                        <td class="ps-4 text-muted small">{{ $index + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($u->avatar)
                                    <img src="{{ storage_url($u->avatar) }}" alt="" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px; font-size: 0.7rem; font-weight: 700; background: {{ $u->is_verified ? '#10b981' : '#94a3b8' }};">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold" style="font-size: 0.88rem;">
                                        {{ $u->name }}
                                        @if($u->is_verified)
                                            <i class="fas fa-check-circle text-success ms-1" style="font-size: 0.7rem;" title="Vérifié"></i>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $u->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($u->isProfessionnel())
                                <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 0.72rem;">
                                    <i class="fas fa-briefcase me-1"></i>Pro
                                </span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.72rem;">
                                    <i class="fas fa-user me-1"></i>Particulier
                                </span>
                            @endif
                            @if($u->role === 'admin')
                                <span class="badge bg-danger" style="font-size: 0.65rem;">Admin</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                @if($u->is_verified)
                                    <span class="badge bg-success bg-opacity-10 text-success" style="font-size: 0.72rem;">
                                        <i class="fas fa-shield-alt me-1"></i>Vérifié
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.72rem;">
                                        <i class="fas fa-clock me-1"></i>Non vérifié
                                    </span>
                                @endif
                                @if(!($u->is_active ?? true))
                                    <span class="badge bg-danger bg-opacity-10 text-danger" style="font-size: 0.72rem;">
                                        <i class="fas fa-ban me-1"></i>Désactivé
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td style="min-width: 180px;">
                            @php
                                $pct = $u->profile_completion;
                                $barColor = $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger');
                            @endphp
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px;">
                                    <div class="progress-bar bg-{{ $barColor }}" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="fw-bold text-{{ $barColor }}" style="font-size: 0.75rem; min-width: 35px;">{{ $pct }}%</span>
                            </div>
                            <small class="text-muted" style="font-size: 0.7rem;">{{ $u->profile_filled }}/{{ $u->profile_total }} champs</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $u->ads_count }}</span>
                        </td>
                        <td>
                            <small class="text-muted">{{ $u->created_at->format('d/m/Y') }}</small>
                        </td>
                        <td class="text-end pe-4" onclick="event.stopPropagation();">
                            <a href="{{ route('admin.users.show', $u->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2" title="Voir le profil">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Données PHP vers JS
    const registrationsData = @json($registrationsByDay);
    const adsData = @json($adsByDay);
    const categoriesData = @json($adsByCategory);
    const plansData = @json($usersByPlan);
    
    // Couleurs
    const colors = {
        primary: 'rgba(59, 130, 246, 0.8)',
        success: 'rgba(34, 197, 94, 0.8)',
        info: 'rgba(6, 182, 212, 0.8)',
        warning: 'rgba(245, 158, 11, 0.8)',
        danger: 'rgba(239, 68, 68, 0.8)',
        secondary: 'rgba(107, 114, 128, 0.8)',
    };
    
    // Graphique Inscriptions
    new Chart(document.getElementById('registrationsChart'), {
        type: 'line',
        data: {
            labels: registrationsData.map(d => new Date(d.date).toLocaleDateString('fr-FR', {day: '2-digit', month: '2-digit'})),
            datasets: [{
                label: 'Inscriptions',
                data: registrationsData.map(d => d.count),
                borderColor: colors.primary,
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
    
    // Graphique Annonces
    new Chart(document.getElementById('adsChart'), {
        type: 'bar',
        data: {
            labels: adsData.map(d => new Date(d.date).toLocaleDateString('fr-FR', {day: '2-digit', month: '2-digit'})),
            datasets: [{
                label: 'Nouvelles annonces',
                data: adsData.map(d => d.count),
                backgroundColor: colors.success,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
    
    // Graphique Catégories
    new Chart(document.getElementById('categoriesChart'), {
        type: 'bar',
        data: {
            labels: categoriesData.map(d => d.category || 'Non défini'),
            datasets: [{
                label: 'Annonces',
                data: categoriesData.map(d => d.count),
                backgroundColor: [
                    colors.primary, colors.success, colors.info, colors.warning, colors.danger,
                    colors.secondary, colors.primary, colors.success, colors.info, colors.warning
                ],
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
    
    // Graphique Plans
    const planColors = {
        'FREE': colors.secondary,
        'STARTER': colors.info,
        'PRO': colors.success,
        'BUSINESS': colors.warning
    };
    
    new Chart(document.getElementById('plansChart'), {
        type: 'doughnut',
        data: {
            labels: plansData.map(d => d.plan || 'FREE'),
            datasets: [{
                data: plansData.map(d => d.count),
                backgroundColor: plansData.map(d => planColors[d.plan || 'FREE'] || colors.secondary),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});

// Recensement - Recherche et filtre
const searchInput = document.getElementById('userCensusSearch');
const filterSelect = document.getElementById('userCensusFilter');
const censusRows = document.querySelectorAll('.user-census-row');

function filterCensus() {
    const search = searchInput.value.toLowerCase().trim();
    const filter = filterSelect.value;

    censusRows.forEach(row => {
        const name = row.dataset.name;
        const email = row.dataset.email;
        const verified = row.dataset.verified;
        const active = row.dataset.active;
        const completion = parseInt(row.dataset.completion);

        let matchSearch = !search || name.includes(search) || email.includes(search);
        let matchFilter = true;

        switch (filter) {
            case 'verified': matchFilter = verified === 'verified'; break;
            case 'unverified': matchFilter = verified === 'unverified'; break;
            case 'active': matchFilter = active === 'active'; break;
            case 'inactive': matchFilter = active === 'inactive'; break;
            case 'complete': matchFilter = completion >= 80; break;
            case 'incomplete': matchFilter = completion < 50; break;
        }

        row.style.display = (matchSearch && matchFilter) ? '' : 'none';
    });
}

if (searchInput) searchInput.addEventListener('input', filterCensus);
if (filterSelect) filterSelect.addEventListener('change', filterCensus);
</script>
@endsection

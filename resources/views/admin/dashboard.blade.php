@extends('admin.layouts.app')

@section('title', 'Tableau de bord Admin')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h2 class="h4 fw-bold">Tableau de bord Administrateur</h2>
        <p class="text-muted mb-0">Statistiques et activités de la plateforme</p>
    </div>
    <div class="col-auto">
        <button class="btn btn-primary">
            <i class="fas fa-download me-2"></i>Exporter les données
        </button>
    </div>
</div>

<!-- Cartes de statistiques -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-lg-6">
        <div class="card stat-card border-0 bg-primary bg-gradient text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2">Utilisateurs</h6>
                        <h2 class="card-title mb-0">{{ $stats['total_users'] }}</h2>
                        <small>+{{ $stats['new_users_today'] }} aujourd'hui</small>
                    </div>
                    <i class="fas fa-users fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6">
        <div class="card stat-card border-0 bg-success bg-gradient text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2">Annonces</h6>
                        <h2 class="card-title mb-0">{{ $stats['total_ads'] }}</h2>
                        <small>{{ $stats['active_ads'] }} actives</small>
                    </div>
                    <i class="fas fa-bullhorn fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6">
        <a href="{{ route('admin.verifications') }}" class="text-decoration-none">
            <div class="card stat-card border-0 text-white" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2">Vérifications en attente</h6>
                            <h2 class="card-title mb-0">{{ $stats['pending_verifications'] ?? 0 }}</h2>
                            <small>À examiner</small>
                        </div>
                        <i class="fas fa-shield-alt fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-xl-3 col-lg-6">
        <div class="card stat-card border-0 bg-info bg-gradient text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2">Vérifiés</h6>
                        <h2 class="card-title mb-0">{{ $stats['verified_users'] }}</h2>
                        <small>{{ $stats['total_users'] > 0 ? number_format($stats['verified_users'] / $stats['total_users'] * 100, 1) : 0 }}%</small>
                    </div>
                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-6">
        <a href="{{ route('admin.service-orders.index') }}" class="text-decoration-none">
            <div class="card stat-card border-0 text-white" style="background: linear-gradient(135deg, #0f766e, #115e59);">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2">Commandes securisees</h6>
                            <h2 class="card-title mb-0">Piloter</h2>
                            <small>Litiges, remboursements, payouts</small>
                        </div>
                        <i class="fas fa-money-check-alt fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <h5 class="mb-0">
                    <i class="fas fa-envelope-open-text me-2 text-info"></i>Résumé configuration e-mail
                </h5>
                <span class="badge rounded-pill {{ ($mailSummary['is_complete'] ?? false) ? 'text-bg-success' : 'text-bg-warning' }} px-3 py-2">
                    {{ ($mailSummary['is_complete'] ?? false) ? 'Config OK' : 'Config incomplète' }}
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="small text-muted text-uppercase fw-semibold mb-1">Canal</div>
                        <div class="fw-semibold">{{ $mailSummary['driver'] ?? config('mail.default') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted text-uppercase fw-semibold mb-1">Contact public</div>
                        <div class="fw-semibold">{{ $mailSummary['contact_email'] ?? 'Non défini' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted text-uppercase fw-semibold mb-1">Réponse</div>
                        <div class="fw-semibold">{{ $mailSummary['reply_to_address'] ?? 'Non défini' }}</div>
                        @if($mailSummary['reply_to_uses_admin_fallback'] ?? false)
                            <div class="small text-muted">Fallback admin actif</div>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted text-uppercase fw-semibold mb-1">Administration</div>
                        <div class="fw-semibold">{{ $mailSummary['admin_email'] ?? 'Non défini' }}</div>
                    </div>
                </div>

                <div class="mt-3 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div class="small text-muted">
                        Expéditeur: {{ $mailSummary['from_name'] ?? 'Non défini' }} &lt;{{ $mailSummary['from_address'] ?? 'Non défini' }}&gt;
                    </div>
                    <a href="{{ route('admin.settings') }}" class="btn btn-sm btn-outline-info">
                        Ouvrir la configuration e-mail
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deux colonnes -->
<div class="row g-4">
    <!-- Vérifications en attente -->
    @if(isset($pendingVerifications) && $pendingVerifications->count() > 0)
    <div class="col-12">
        <div class="card border-0 shadow-sm border-start border-warning border-4">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-shield-alt me-2 text-warning"></i>Vérifications en attente
                    <span class="badge bg-warning text-dark ms-2">{{ $stats['pending_verifications'] }}</span>
                </h5>
                <a href="{{ route('admin.verifications') }}" class="btn btn-sm btn-outline-warning">
                    Voir tout <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Utilisateur</th>
                                <th>Type</th>
                                <th>Document</th>
                                <th>Montant</th>
                                <th>Soumis le</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingVerifications as $pv)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($pv->user && $pv->user->avatar)
                                            <img src="{{ storage_url($pv->user->avatar) }}" alt="" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                        @else
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.75rem; font-weight: 600;">
                                                {{ $pv->user ? strtoupper(substr($pv->user->name, 0, 1)) : '?' }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="fw-semibold small">{{ $pv->user->name ?? 'Utilisateur supprimé' }}</div>
                                            <small class="text-muted">{{ $pv->user->email ?? '-' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($pv->type === 'profile_verification')
                                        <span class="badge bg-info bg-opacity-10 text-info">Profil</span>
                                    @else
                                        <span class="badge" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">Prestataire</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $docTypesMap = ['id_card' => 'CI', 'passport' => 'Passeport', 'driver_license' => 'Permis'];
                                    @endphp
                                    <small>{{ $docTypesMap[$pv->document_type] ?? $pv->document_type }}</small>
                                </td>
                                <td><span class="fw-semibold">{{ number_format($pv->payment_amount, 2) }}€</span></td>
                                <td><small>{{ $pv->submitted_at ? $pv->submitted_at->diffForHumans() : ($pv->created_at ? $pv->created_at->diffForHumans() : '-') }}</small></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.verifications.show', $pv->id) }}" class="btn btn-sm btn-warning text-dark">
                                        <i class="fas fa-eye me-1"></i>Examiner
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Dernières inscriptions -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus me-2 text-primary"></i>Dernières inscriptions
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Date</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($latestUsers as $user)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.users.show', $user->id) }}" class="text-decoration-none">
                                        {{ $user->name }}
                                    </a>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                <td>
                                    @if($user->is_verified ?? false)
                                        <span class="badge bg-success">Vérifié</span>
                                    @else
                                        <span class="badge bg-warning">Non vérifié</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <a href="{{ route('admin.users') }}" class="btn btn-outline-primary btn-sm mt-3">
                    Voir tous les utilisateurs
                </a>
            </div>
        </div>
    </div>
    
    <!-- Dernières annonces -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">
                    <i class="fas fa-bullhorn me-2 text-success"></i>Dernières annonces
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Catégorie</th>
                                <th>Utilisateur</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($latestAds as $ad)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.ads.show', $ad->id) }}" class="text-decoration-none">
                                        {{ Str::limit($ad->title, 25) }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ $ad->category }}</span>
                                </td>
                                <td>{{ $ad->user?->name ?? 'Anonyme' }}</td>
                                <td>
                                    <span class="badge bg-{{ $ad->status == 'active' ? 'success' : 'warning' }}">
                                        {{ $ad->status }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <a href="{{ route('admin.ads') }}" class="btn btn-outline-success btn-sm mt-3">
                    Voir toutes les annonces
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Graphique simplifié -->
<div class="row mt-4">
    <div class="col">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Inscriptions (7 derniers jours)</h5>
            </div>
            <div class="card-body">
                @if($registrations->isEmpty())
                    <p class="text-muted text-center py-4">Aucune donnée disponible pour le moment.</p>
                @else
                    <div class="d-flex align-items-end justify-content-around" style="height: 150px;">
                        @foreach($registrations as $registration)
                            <div class="text-center px-2">
                                <div class="bg-primary rounded" style="height: {{ max($registration->count * 30, 10) }}px; width: 40px;"></div>
                                <div class="mt-2">
                                    <small class="d-block">{{ \Carbon\Carbon::parse($registration->date)->format('d/m') }}</small>
                                    <small class="fw-bold">{{ $registration->count }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

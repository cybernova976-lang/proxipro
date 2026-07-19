@extends('admin.layouts.app')

@section('title', 'Gestion des Abonnements')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h2 class="h4 fw-bold">
            <i class="fas fa-crown text-warning me-2"></i>Gestion des Abonnements
        </h2>
        <p class="text-muted mb-0">Gérer les abonnements et accorder le premium aux utilisateurs</p>
    </div>
</div>

@include('admin.partials.pro-subscription-control')

<div class="row g-3 mb-4">
    @foreach([
        ['Actifs', $providerStats['active'], 'success', 'fa-circle-check'],
        ['En attente / suspendus', $providerStats['pending'], 'warning', 'fa-pause-circle'],
        ['Fin programmée', $providerStats['ending'], 'info', 'fa-calendar-xmark'],
        ['Gérés par Stripe', $providerStats['stripe'], 'primary', 'fa-credit-card'],
    ] as [$label, $value, $color, $icon])
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100"><div class="card-body d-flex align-items-center justify-content-between">
                <div><div class="small text-muted">{{ $label }}</div><div class="h4 fw-bold mb-0">{{ $value }}</div></div>
                <i class="fas {{ $icon }} fa-2x text-{{ $color }} opacity-75"></i>
            </div></div>
        </div>
    @endforeach
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h5 class="fw-bold mb-1"><i class="fas fa-repeat text-primary me-2"></i>Abonnements prestataires</h5>
            <p class="text-muted mb-0">État local synchronisé avec Stripe. Un accès manuel ne crée aucun prélèvement.</p>
        </div>
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#manualProviderGrant">
            <i class="fas fa-gift me-1"></i>Accorder un accès manuel
        </button>
    </div>
    <div class="collapse" id="manualProviderGrant">
        <div class="card-body border-top mx-4 px-0">
            <form method="POST" action="{{ route('admin.subscriptions.provider.grant') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-lg-5"><label class="form-label small fw-semibold">Email utilisateur</label><input type="email" name="email" class="form-control" required></div>
                <div class="col-lg-2"><label class="form-label small fw-semibold">Plan</label><select name="plan" class="form-select"><option value="monthly">Mensuel</option><option value="annual">Annuel</option></select></div>
                <div class="col-lg-2"><label class="form-label small fw-semibold">Durée (jours)</label><input type="number" name="duration" min="1" max="730" value="30" class="form-control" required></div>
                <div class="col-lg-3"><button class="btn btn-primary w-100"><i class="fas fa-check me-1"></i>Accorder sans paiement</button></div>
            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light"><tr><th class="ps-4">Utilisateur</th><th>Plan</th><th>État</th><th>Période</th><th>Origine</th><th class="text-end pe-4">Actions</th></tr></thead>
            <tbody>
                @forelse($providerSubscriptions as $providerSubscription)
                    <tr>
                        <td class="ps-4"><a href="{{ route('admin.users.show', $providerSubscription->user_id) }}" class="fw-semibold text-decoration-none">{{ $providerSubscription->user?->name ?: 'Compte supprimé' }}</a><div class="small text-muted">{{ $providerSubscription->user?->email }}</div></td>
                        <td>{{ $providerSubscription->getPlanLabel() }}<div class="small text-muted">{{ number_format((float) $providerSubscription->amount, 2, ',', ' ') }} €</div></td>
                        <td>
                            @php $providerStatusColor = ['active' => 'success', 'pending' => 'warning', 'cancelled' => 'secondary', 'expired' => 'danger'][$providerSubscription->status] ?? 'secondary'; @endphp
                            <span class="badge bg-{{ $providerStatusColor }}">{{ ucfirst($providerSubscription->status) }}</span>
                            @if($providerSubscription->status === 'active' && ! $providerSubscription->auto_renew)<div class="small text-warning mt-1">Fin à l’échéance</div>@endif
                        </td>
                        <td class="small">{{ optional($providerSubscription->starts_at)->format('d/m/Y') ?: '—' }} → {{ optional($providerSubscription->ends_at)->format('d/m/Y') ?: '—' }}</td>
                        <td>
                            @if($providerSubscription->stripe_subscription_id)
                                <span class="badge bg-primary-subtle text-primary border">Stripe</span><div class="small text-muted">{{ $providerSubscription->stripe_status ?: '—' }}</div>
                            @else
                                <span class="badge bg-light text-dark border">Manuel</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-inline-flex gap-1">
                                @if($providerSubscription->status === 'active')
                                    <form method="POST" action="{{ route('admin.subscriptions.provider.suspend', $providerSubscription) }}" onsubmit="return confirm('Suspendre cet abonnement et les prochains prélèvements ?');">@csrf<button class="btn btn-sm btn-outline-warning" title="Suspendre"><i class="fas fa-pause"></i></button></form>
                                @elseif($providerSubscription->status === 'pending')
                                    <form method="POST" action="{{ route('admin.subscriptions.provider.resume', $providerSubscription) }}">@csrf<button class="btn btn-sm btn-outline-success" title="Réactiver"><i class="fas fa-play"></i></button></form>
                                @endif
                                @if(!in_array($providerSubscription->status, ['cancelled', 'expired'], true))
                                    <form method="POST" action="{{ route('admin.subscriptions.provider.cancel', $providerSubscription) }}" onsubmit="return confirm('Annuler immédiatement cet abonnement ? Cette action coupe aussi Stripe.');">@csrf<button class="btn btn-sm btn-outline-danger" title="Annuler immédiatement"><i class="fas fa-ban"></i></button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun abonnement prestataire enregistré.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($providerSubscriptions->hasPages())<div class="card-footer bg-white">{{ $providerSubscriptions->links() }}</div>@endif
</div>

<!-- Statistiques -->
<div class="mb-2"><h5 class="fw-bold mb-0">Plans de visibilité historiques</h5><p class="text-muted small mb-0">Gestion séparée des anciens plans STARTER / PRO / BUSINESS liés aux annonces.</p></div>
<div class="row g-4 mb-4">
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card border-0 bg-primary bg-gradient text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-1 opacity-75" style="font-size: 0.75rem;">Total Premium</h6>
                        <h3 class="card-title mb-0">{{ $stats['total_premium'] }}</h3>
                    </div>
                    <i class="fas fa-crown fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card border-0 bg-success bg-gradient text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-1 opacity-75" style="font-size: 0.75rem;">Actifs</h6>
                        <h3 class="card-title mb-0">{{ $stats['active_subscriptions'] }}</h3>
                    </div>
                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card border-0 bg-danger bg-gradient text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-1 opacity-75" style="font-size: 0.75rem;">Expirés</h6>
                        <h3 class="card-title mb-0">{{ $stats['expired_subscriptions'] }}</h3>
                    </div>
                    <i class="fas fa-times-circle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card border-0 bg-info bg-gradient text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-1 opacity-75" style="font-size: 0.75rem;">Starter</h6>
                        <h3 class="card-title mb-0">{{ $stats['starter_count'] }}</h3>
                    </div>
                    <i class="fas fa-star fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card border-0 bg-success bg-gradient text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-1 opacity-75" style="font-size: 0.75rem;">Pro</h6>
                        <h3 class="card-title mb-0">{{ $stats['pro_count'] }}</h3>
                    </div>
                    <i class="fas fa-rocket fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-lg-4 col-md-6">
        <div class="card stat-card border-0 bg-warning bg-gradient text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-1 opacity-75" style="font-size: 0.75rem;">Business</h6>
                        <h3 class="card-title mb-0">{{ $stats['business_count'] }}</h3>
                    </div>
                    <i class="fas fa-building fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Configuration des cartes d'abonnement prestataire -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h5 class="fw-bold mb-1">
                    <i class="fas fa-id-badge text-primary me-2"></i>Cartes “Devenir prestataire”
                </h5>
                <p class="text-muted mb-0">Modifiez les prix, badges, prix barrés et fonctionnalités affichés aux utilisateurs.</p>
            </div>
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2">Visible dans le parcours prestataire</span>
        </div>
    </div>
    <div class="card-body px-4">
        <form method="POST" action="{{ route('admin.subscriptions.provider-plans.update') }}">
            @csrf
            @method('PUT')
            <div class="row g-4">
                @foreach($providerSubscriptionPlans as $planKey => $providerPlan)
                    <div class="col-lg-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">{{ $providerPlan['label'] ?? ucfirst($planKey) }}</h6>
                                <div class="d-flex gap-3">
                                    <label class="form-check-label small">
                                        <input type="checkbox" class="form-check-input me-1" name="plans[{{ $planKey }}][enabled]" value="1" {{ !empty($providerPlan['enabled']) ? 'checked' : '' }}>
                                        Actif
                                    </label>
                                    <label class="form-check-label small">
                                        <input type="checkbox" class="form-check-input me-1" name="plans[{{ $planKey }}][recommended]" value="1" {{ !empty($providerPlan['recommended']) ? 'checked' : '' }}>
                                        Recommandé
                                    </label>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Libellé</label>
                                    <input type="text" class="form-control" name="plans[{{ $planKey }}][label]" value="{{ $providerPlan['label'] ?? '' }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Prix affiché</label>
                                    <input type="text" class="form-control" name="plans[{{ $planKey }}][price]" value="{{ $providerPlan['price'] ?? '' }}" readonly required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Montant Stripe</label>
                                    <input type="number" class="form-control" name="plans[{{ $planKey }}][amount]" step="0.01" min="0.50" value="{{ $providerPlan['amount'] ?? 0 }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Période</label>
                                    <input type="text" class="form-control" name="plans[{{ $planKey }}][period]" value="{{ $providerPlan['period'] ?? '' }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Prix barré</label>
                                    <input type="text" class="form-control" name="plans[{{ $planKey }}][original_price]" value="{{ $providerPlan['original_price'] ?? '' }}" placeholder="Ex: 119,88€">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Badge</label>
                                    <input type="text" class="form-control" name="plans[{{ $planKey }}][badge]" value="{{ $providerPlan['badge'] ?? '' }}" placeholder="Ex: -30%">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Sous-texte</label>
                                    <input type="text" class="form-control" name="plans[{{ $planKey }}][subtitle]" value="{{ $providerPlan['subtitle'] ?? '' }}" placeholder="Ex: soit 7,08€/mois">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Description Stripe</label>
                                    <input type="text" class="form-control" name="plans[{{ $planKey }}][description]" value="{{ $providerPlan['description'] ?? '' }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Fonctionnalités affichées</label>
                                    <textarea class="form-control" name="plans[{{ $planKey }}][features]" rows="7" required>{{ implode("\n", $providerPlan['features'] ?? []) }}</textarea>
                                    <small class="text-muted">Une fonctionnalité par ligne.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Enregistrer les cartes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Filtres -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.subscriptions') }}">
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
                    <select name="plan" class="form-select">
                        <option value="">Tous les plans</option>
                        @foreach($plans as $key => $plan)
                            <option value="{{ $key }}" {{ request('plan') == $key ? 'selected' : '' }}>
                                {{ $plan['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="subscription_status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="active" {{ request('subscription_status') == 'active' ? 'selected' : '' }}>Actif</option>
                        <option value="expired" {{ request('subscription_status') == 'expired' ? 'selected' : '' }}>Expiré</option>
                        <option value="none" {{ request('subscription_status') == 'none' ? 'selected' : '' }}>Sans abonnement</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Filtrer
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.subscriptions') }}" class="btn btn-outline-secondary w-100">
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
                        <th class="border-0 py-3 ps-4">Utilisateur</th>
                        <th class="border-0 py-3">Plan actuel</th>
                        <th class="border-0 py-3">Fin d'abonnement</th>
                        <th class="border-0 py-3">Statut</th>
                        <th class="border-0 py-3 pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle bg-primary text-white me-2">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('admin.users.show', $user->id) }}" class="text-decoration-none fw-bold">
                                        {{ $user->name }}
                                    </a>
                                    <br><small class="text-muted">{{ $user->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @php $planConfig = $plans[$user->plan ?? 'FREE'] ?? $plans['FREE']; @endphp
                            <span class="badge bg-{{ $planConfig['color'] }} px-3 py-2">
                                {{ $planConfig['label'] }}
                            </span>
                        </td>
                        <td>
                            @if($user->subscription_end)
                                {{ \Carbon\Carbon::parse($user->subscription_end)->format('d/m/Y H:i') }}
                                <br>
                                <small class="text-muted">
                                    @if(\Carbon\Carbon::parse($user->subscription_end)->isFuture())
                                        {{ \Carbon\Carbon::parse($user->subscription_end)->diffForHumans() }}
                                    @else
                                        Expiré {{ \Carbon\Carbon::parse($user->subscription_end)->diffForHumans() }}
                                    @endif
                                </small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($user->subscription_end && \Carbon\Carbon::parse($user->subscription_end)->isFuture())
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i>Actif
                                </span>
                            @elseif($user->subscription_end)
                                <span class="badge bg-danger">
                                    <i class="fas fa-times-circle me-1"></i>Expiré
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="fas fa-minus-circle me-1"></i>Gratuit
                                </span>
                            @endif
                        </td>
                        <td class="pe-4 text-end">
                            <div class="btn-group">
                                <!-- Modifier le plan -->
                                <button class="btn btn-sm btn-outline-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editModal{{ $user->id }}"
                                        title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <!-- Accorder Premium -->
                                <button class="btn btn-sm btn-outline-warning" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#grantModal{{ $user->id }}"
                                        title="Accorder Premium">
                                    <i class="fas fa-crown"></i>
                                </button>
                                
                                @if($user->plan !== 'FREE' && ($user->subscription_end && \Carbon\Carbon::parse($user->subscription_end)->isFuture()))
                                <!-- Suspendre -->
                                <form action="{{ route('admin.subscriptions.suspend', $user->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Suspendre l\'abonnement de {{ $user->name }} ?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Suspendre">
                                        <i class="fas fa-pause"></i>
                                    </button>
                                </form>
                                @endif
                                
                                @if($user->plan !== 'FREE')
                                <!-- Annuler -->
                                <form action="{{ route('admin.subscriptions.cancel', $user->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Annuler l\'abonnement de {{ $user->name }} et revenir au plan gratuit ?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Annuler">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>

                    <!-- Modal Modifier -->
                    <div class="modal fade" id="editModal{{ $user->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Modifier l'abonnement de {{ $user->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ route('admin.subscriptions.update', $user->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Plan</label>
                                            <select name="plan" class="form-select">
                                                @foreach($plans as $key => $plan)
                                                    <option value="{{ $key }}" {{ ($user->plan ?? 'FREE') == $key ? 'selected' : '' }}>
                                                        {{ $plan['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Date d'expiration</label>
                                            <input type="datetime-local" name="subscription_end" class="form-control"
                                                   value="{{ $user->subscription_end ? \Carbon\Carbon::parse($user->subscription_end)->format('Y-m-d\TH:i') : '' }}">
                                            <small class="text-muted">Laisser vide si le plan est gratuit</small>
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

                    <!-- Modal Accorder Premium -->
                    <div class="modal fade" id="grantModal{{ $user->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-warning bg-opacity-25">
                                    <h5 class="modal-title">
                                        <i class="fas fa-crown text-warning me-2"></i>
                                        Accorder Premium à {{ $user->name }}
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ route('admin.subscriptions.grant-premium', $user->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Cette action accorde le statut premium gratuitement à l'utilisateur.
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Plan à accorder</label>
                                            <div class="row g-2">
                                                @foreach(['STARTER' => 'Starter', 'PRO' => 'Pro', 'BUSINESS' => 'Business'] as $key => $label)
                                                <div class="col-4">
                                                    <input type="radio" class="btn-check" name="plan" id="plan{{ $key }}{{ $user->id }}" value="{{ $key }}" {{ $key == 'PRO' ? 'checked' : '' }}>
                                                    <label class="btn btn-outline-{{ $plans[$key]['color'] ?? 'primary' }} w-100" for="plan{{ $key }}{{ $user->id }}">
                                                        {{ $label }}
                                                    </label>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Durée</label>
                                            <select name="duration" class="form-select">
                                                <option value="7">7 jours</option>
                                                <option value="30" selected>1 mois</option>
                                                <option value="90">3 mois</option>
                                                <option value="365">1 an</option>
                                                <option value="unlimited">Illimité</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-crown me-2"></i>Accorder Premium
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <i class="fas fa-crown fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aucun utilisateur trouvé</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
    <div class="card-footer bg-white border-0 py-3">
        {{ $users->links() }}
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

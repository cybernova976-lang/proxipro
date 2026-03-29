@extends('pro.layout')
@section('title', 'Abonnement Pro - Espace Pro')

@section('content')
<div class="pro-content-header" style="max-width: 960px; margin-left: auto; margin-right: auto;">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                <li class="breadcrumb-item"><a href="{{ route('pro.dashboard') }}" style="color: var(--pro-primary);">Espace Pro</a></li>
                <li class="breadcrumb-item active">Abonnement & Points</li>
            </ol>
        </nav>
        <h1>Abonnement & Points</h1>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">Choisissez un abonnement ou achetez des points à la carte.</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; max-width: 960px; margin-left: auto; margin-right: auto;">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; max-width: 960px; margin-left: auto; margin-right: auto;">
    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Current Subscription Status --}}
@if($subscription && $subscription->isActive())
<div class="pro-card sub-page-card mb-4" style="background: linear-gradient(135deg, rgba(16,185,129,0.08), rgba(59,130,246,0.05)); border-left: 4px solid #10b981; padding: 16px 20px;">
    <div class="d-flex align-items-center gap-3">
        <div style="width: 42px; height: 42px; border-radius: 50%; background: rgba(16,185,129,0.15); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="flex-grow-1">
            <h6 class="fw-bold mb-0 text-success">Abonnement actif — {{ $subscription->getPlanLabel() }}</h6>
            <p class="text-muted mb-0" style="font-size: 0.8rem;">
                Valide jusqu'au <strong>{{ $subscription->ends_at->format('d/m/Y') }}</strong> · {{ $subscription->daysRemaining() }} jours restants
            </p>
        </div>
        <span class="pro-status pro-status-success">Actif</span>
    </div>
</div>
@else
<div class="pro-card sub-page-card mb-4" style="background: linear-gradient(135deg, rgba(245,158,11,0.08), rgba(239,68,68,0.05)); border-left: 4px solid #f59e0b; padding: 16px 20px;">
    <div class="d-flex align-items-center gap-3">
        <div style="width: 42px; height: 42px; border-radius: 50%; background: rgba(245,158,11,0.15); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-0">Aucun abonnement actif</h6>
            <p class="text-muted mb-0" style="font-size: 0.8rem;">Choisissez un plan ci-dessous ou achetez des points à la carte.</p>
        </div>
    </div>
</div>
@endif

{{-- ===== SECTION 1: Plans d'abonnement (compact) ===== --}}
<div class="pro-card sub-page-card mb-4">
    <h6 class="fw-bold mb-3"><i class="fas fa-crown me-2" style="color: #f59e0b;"></i>Plans d'abonnement</h6>
    <div class="row g-3">
        {{-- Monthly --}}
        <div class="col-md-6">
            <div class="sub-plan-card {{ $subscription && $subscription->isActive() && $subscription->plan === 'monthly' ? 'sub-plan-active' : '' }}">
                <div class="d-flex align-items-center gap-3">
                    <div class="sub-plan-icon" style="background: linear-gradient(135deg, #a855f7, #6366f1);">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-baseline gap-2 mb-1">
                            <h6 class="fw-bold mb-0">Mensuel</h6>
                            <span class="text-muted" style="font-size: 0.75rem;">Sans engagement</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1" style="font-size: 0.72rem; color: #64748b;">
                            <span><i class="fas fa-check text-success me-1"></i>Profil vérifié</span>
                            <span><i class="fas fa-check text-success me-1"></i>Devis & factures</span>
                            <span><i class="fas fa-check text-success me-1"></i>Badge pro</span>
                            <span><i class="fas fa-check text-success me-1"></i>Support prioritaire</span>
                        </div>
                    </div>
                    <div class="text-end" style="min-width: 100px;">
                        <div class="fw-bold" style="font-size: 1.5rem; color: var(--pro-primary); line-height: 1;">9,99€</div>
                        <div class="text-muted" style="font-size: 0.72rem;">/mois</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('pro.onboarding.subscribe') }}" class="mt-2">
                    @csrf
                    <input type="hidden" name="plan" value="monthly">
                    <button type="submit" class="btn btn-sm {{ $subscription && $subscription->isActive() && $subscription->plan === 'monthly' ? 'btn-outline-success' : 'btn-outline-primary' }} w-100" style="border-radius: 8px; font-size: 0.8rem; padding: 6px;">
                        @if($subscription && $subscription->isActive() && $subscription->plan === 'monthly')
                            <i class="fas fa-check me-1"></i> Plan actuel
                        @else
                            Choisir ce plan
                        @endif
                    </button>
                </form>
            </div>
        </div>

        {{-- Annual --}}
        <div class="col-md-6">
            <div class="sub-plan-card sub-plan-recommended {{ $subscription && $subscription->isActive() && $subscription->plan === 'annual' ? 'sub-plan-active' : '' }}">
                <div class="sub-plan-badge">-30%</div>
                <div class="d-flex align-items-center gap-3">
                    <div class="sub-plan-icon" style="background: linear-gradient(135deg, #f59e0b, #ef4444);">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-baseline gap-2 mb-1">
                            <h6 class="fw-bold mb-0">Annuel</h6>
                            <span class="text-muted text-decoration-line-through" style="font-size: 0.72rem;">119,88€</span>
                            <span class="text-success fw-semibold" style="font-size: 0.72rem;">7,08€/mois</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1" style="font-size: 0.72rem; color: #64748b;">
                            <span><i class="fas fa-star text-warning me-1"></i>Stats avancées</span>
                            <span><i class="fas fa-star text-warning me-1"></i>Priorité recherche</span>
                            <span><i class="fas fa-star text-warning me-1"></i>Pro Premium</span>
                            <span><i class="fas fa-star text-warning me-1"></i>Support 7j/7</span>
                        </div>
                    </div>
                    <div class="text-end" style="min-width: 100px;">
                        <div class="fw-bold" style="font-size: 1.5rem; color: var(--pro-primary); line-height: 1;">85€</div>
                        <div class="text-muted" style="font-size: 0.72rem;">/an</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('pro.onboarding.subscribe') }}" class="mt-2">
                    @csrf
                    <input type="hidden" name="plan" value="annual">
                    <button type="submit" class="btn btn-sm {{ $subscription && $subscription->isActive() && $subscription->plan === 'annual' ? 'btn-outline-success' : 'btn-pro-primary' }} w-100" style="border-radius: 8px; font-size: 0.8rem; padding: 6px;">
                        @if($subscription && $subscription->isActive() && $subscription->plan === 'annual')
                            <i class="fas fa-crown me-1"></i> Plan actuel
                        @else
                            <i class="fas fa-crown me-1"></i> Choisir ce plan
                        @endif
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ===== SECTION 2: Acheter des points ===== --}}
<div class="pro-card sub-page-card mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold mb-0"><i class="fas fa-coins me-2" style="color: #f59e0b;"></i>Acheter des points</h6>
        <span class="badge" style="background: linear-gradient(135deg, #7c3aed, #9333ea); font-size: 0.78rem; padding: 5px 12px; border-radius: 20px;">
            <i class="fas fa-wallet me-1"></i>Solde : {{ number_format($user->available_points ?? 0, 0, ',', ' ') }} pts
        </span>
    </div>
    <p class="text-muted mb-3" style="font-size: 0.82rem;">Pas besoin d'abonnement ! Achetez des points pour booster vos annonces et gagner en visibilité.</p>

    <div class="row g-2">
        @php
            $packs = [
                ['key' => 'POINTS_5', 'name' => 'Boost 3j', 'pts' => 5, 'price' => 4, 'icon' => 'fas fa-bolt', 'color' => '#6366f1'],
                ['key' => 'POINTS_10', 'name' => 'Boost 7j', 'pts' => 10, 'price' => 6, 'icon' => 'fas fa-rocket', 'color' => '#f59e0b', 'popular' => true],
                ['key' => 'POINTS_20', 'name' => 'Boost 15j', 'pts' => 20, 'price' => 10, 'icon' => 'fas fa-star', 'color' => '#a855f7'],
                ['key' => 'POINTS_30', 'name' => 'Boost 30j', 'pts' => 30, 'price' => 15, 'icon' => 'fas fa-crown', 'color' => '#ef4444'],
                ['key' => 'POINTS_50', 'name' => 'Confort', 'pts' => 50, 'price' => 22, 'icon' => 'fas fa-shield-alt', 'color' => '#10b981'],
                ['key' => 'POINTS_100', 'name' => 'Pro', 'pts' => 100, 'price' => 40, 'icon' => 'fas fa-gem', 'color' => '#2563eb', 'best' => true],
            ];
        @endphp

        @foreach($packs as $pack)
        <div class="col-md-6 col-lg-4">
            <div class="pts-pack-mini {{ isset($pack['popular']) ? 'pts-pack-featured' : '' }} {{ isset($pack['best']) ? 'pts-pack-best' : '' }}">
                @if(isset($pack['popular']))
                    <span class="pts-mini-badge" style="background: #f59e0b;">Populaire</span>
                @elseif(isset($pack['best']))
                    <span class="pts-mini-badge" style="background: #10b981;">Meilleur prix</span>
                @endif
                <div class="d-flex align-items-center gap-2">
                    <div class="pts-mini-icon" style="background: {{ $pack['color'] }}20; color: {{ $pack['color'] }};">
                        <i class="{{ $pack['icon'] }}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold" style="font-size: 0.85rem; line-height: 1.2;">{{ $pack['name'] }}</div>
                        <div style="font-size: 0.72rem; color: #94a3b8;">{{ $pack['pts'] }} points · {{ number_format($pack['price'] / $pack['pts'], 2, ',', ' ') }}€/pt</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold" style="font-size: 1.1rem; color: #0f172a;">{{ number_format($pack['price'], 0) }}€</div>
                    </div>
                    <button class="btn btn-sm btn-pts-mini" onclick="purchasePoints('{{ $pack['key'] }}', this)">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- ===== SECTION 3: Pourquoi devenir Pro ? ===== --}}
<div class="pro-card sub-page-card mb-4">
    <h6 class="fw-bold mb-3"><i class="fas fa-gift me-2 text-primary"></i>Pourquoi devenir Pro ?</h6>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="d-flex gap-2">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(16,185,129,0.1); color: #10b981; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.85rem;">
                    <i class="fas fa-eye"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0" style="font-size: 0.82rem;">Visibilité accrue</h6>
                    <p class="text-muted mb-0" style="font-size: 0.72rem;">Annonces en priorité dans les résultats de recherche.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-flex gap-2">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(59,130,246,0.1); color: #3b82f6; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.85rem;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0" style="font-size: 0.82rem;">Confiance renforcée</h6>
                    <p class="text-muted mb-0" style="font-size: 0.72rem;">Badge vérifié + avis clients = plus de demandes.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-flex gap-2">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(168,85,247,0.1); color: #a855f7; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.85rem;">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0" style="font-size: 0.82rem;">Outils pro</h6>
                    <p class="text-muted mb-0" style="font-size: 0.72rem;">Créez devis et factures, gérez votre clientèle.</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== SECTION 4: Historique ===== --}}
@if($subscriptions->isNotEmpty())
<div class="pro-card sub-page-card">
    <h6 class="fw-bold mb-3"><i class="fas fa-history me-2"></i>Historique des abonnements</h6>
    <div class="table-responsive" style="overflow: visible;">
        <table class="table table-hover mb-0" style="font-size: 0.82rem;">
            <thead>
                <tr><th>Plan</th><th>Début</th><th>Fin</th><th>Montant</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($subscriptions as $sub)
                <tr>
                    <td class="fw-semibold">{{ $sub->getPlanLabel() }}</td>
                    <td>{{ $sub->starts_at->format('d/m/Y') }}</td>
                    <td>{{ $sub->ends_at->format('d/m/Y') }}</td>
                    <td class="fw-bold">{{ $sub->plan === 'annual' ? '85,00 €' : '9,99 €' }}</td>
                    <td>
                        <span class="pro-status pro-status-{{ $sub->isActive() ? 'success' : 'secondary' }}">
                            {{ $sub->isActive() ? 'Actif' : 'Expiré' }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('purchase.invoice', ['type' => 'subscription', 'id' => $sub->id]) }}" class="btn btn-sm btn-outline-secondary" style="font-size: 0.72rem; padding: 2px 8px; border-radius: 6px;" title="Télécharger la facture">
                            <i class="fas fa-file-pdf me-1"></i>Facture
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<style>
/* Constrain card blocks width on subscription page */
.sub-page-card {
    max-width: 960px;
    margin-left: auto;
    margin-right: auto;
}

/* Subscription plan cards - compact */
.sub-plan-card {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 16px;
    transition: all 0.3s ease;
    position: relative;
    background: white;
}
.sub-plan-card:hover {
    border-color: var(--pro-primary);
    box-shadow: 0 4px 16px rgba(99,102,241,0.1);
}
.sub-plan-recommended {
    border-color: var(--pro-primary);
    background: linear-gradient(135deg, rgba(99,102,241,0.02), rgba(168,85,247,0.02));
}
.sub-plan-active {
    border-color: #10b981;
    background: rgba(16,185,129,0.03);
}
.sub-plan-badge {
    position: absolute;
    top: -8px;
    right: 12px;
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    color: white;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 0.65rem;
    font-weight: 700;
}
.sub-plan-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    flex-shrink: 0;
}

/* Points pack cards - mini style */
.pts-pack-mini {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 12px;
    transition: all 0.25s ease;
    position: relative;
    background: white;
}
.pts-pack-mini:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59,130,246,0.1);
    transform: translateY(-2px);
}
.pts-pack-featured { border-color: #f59e0b; }
.pts-pack-best { border-color: #10b981; }
.pts-mini-badge {
    position: absolute;
    top: -7px;
    right: 10px;
    color: white;
    padding: 1px 8px;
    border-radius: 8px;
    font-size: 0.6rem;
    font-weight: 700;
}
.pts-mini-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.btn-pts-mini {
    background: linear-gradient(135deg, #0f172a, #1e3a5f);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 0.75rem;
    transition: all 0.2s;
    flex-shrink: 0;
}
.btn-pts-mini:hover {
    background: linear-gradient(135deg, #1e3a5f, #2563eb);
    color: white;
    transform: scale(1.05);
}
</style>

@endsection

@section('scripts')
<script>
function purchasePoints(productKey, btn) {
    var orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    fetch('{{ route("stripe.create-checkout") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ product_key: productKey })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.url) {
            window.location.href = data.url;
        } else {
            alert(data.error || 'Erreur lors du paiement');
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    })
    .catch(function() {
        alert('Erreur réseau, veuillez réessayer');
        btn.disabled = false;
        btn.innerHTML = orig;
    });
}
</script>
@endsection

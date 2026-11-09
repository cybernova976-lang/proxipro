@extends('pro.layout')
@section('title', 'Abonnement Pro - Espace Pro')
@section('topbar_title', 'Abonnement Premium')

@section('content')
<div class="pro-content-header subscription-page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                <li class="breadcrumb-item"><a href="{{ route('pro.dashboard') }}" style="color: var(--pro-primary);">Espace Pro</a></li>
                <li class="breadcrumb-item active">Abonnement Premium</li>
            </ol>
        </nav>
        <h1>Abonnement Premium</h1>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">Choisissez votre formule Premium, mensuelle ou annuelle.</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show subscription-page-alert" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show subscription-page-alert" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('info'))
<div class="alert alert-info alert-dismissible fade show subscription-page-alert" role="alert">
    <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Current Subscription Status --}}
@if($subscription && $subscription->isActive())
<div class="pro-card sub-page-card mb-4" style="background: linear-gradient(135deg, rgba(16,185,129,0.08), rgba(59,130,246,0.05)); border-left: 4px solid #10b981; padding: 16px 20px;">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <div style="width: 42px; height: 42px; border-radius: 50%; background: rgba(16,185,129,0.15); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="flex-grow-1">
            <h6 class="fw-bold mb-0 text-success">Abonnement actif — {{ $subscription->getPlanLabel() }}</h6>
            <p class="text-muted mb-0" style="font-size: 0.8rem;">
                Valide jusqu'au <strong>{{ optional($subscription->ends_at)->format('d/m/Y') ?: 'prochaine échéance Stripe' }}</strong> · {{ $subscription->daysRemaining() }} jours restants
            </p>
            <p class="mb-0 mt-1" style="font-size: .75rem;">
                @if($subscription->stripe_subscription_id)
                    @if($subscription->auto_renew)
                        <span class="text-success"><i class="fas fa-repeat me-1"></i>Renouvellement automatique actif</span>
                    @else
                        <span class="text-warning"><i class="fas fa-calendar-xmark me-1"></i>Résiliation programmée à l’échéance</span>
                    @endif
                @else
                    <span class="text-muted"><i class="fas fa-gift me-1"></i>Accès accordé manuellement, sans prélèvement</span>
                @endif
            </p>
        </div>
        <span class="pro-status pro-status-success">Actif</span>
        @if($subscription->stripe_subscription_id)
            <form method="POST" action="{{ route('pro.subscription.billing-portal') }}">
                @csrf
                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-credit-card me-1"></i>Factures et carte</button>
            </form>
            @if($subscription->auto_renew)
                <form method="POST" action="{{ route('pro.subscription.cancel-renewal') }}" onsubmit="return confirm('Arrêter le renouvellement automatique à la prochaine échéance ?');">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger">Arrêter le renouvellement</button>
                </form>
            @else
                <form method="POST" action="{{ route('pro.subscription.resume-renewal') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-success">Réactiver le renouvellement</button>
                </form>
            @endif
        @endif
    </div>
</div>
@else
<div class="pro-card sub-page-card mb-4" style="background: linear-gradient(135deg, rgba(245,158,11,0.08), rgba(239,68,68,0.05)); border-left: 4px solid #f59e0b; padding: 16px 20px;">
    <div class="d-flex align-items-center gap-3">
        <div style="width: 42px; height: 42px; border-radius: 50%; background: rgba(245,158,11,0.15); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-0">{{ $proSubscriptionsEnabled ? 'Aucun abonnement actif' : 'Abonnement Pro en préparation' }}</h6>
            <p class="text-muted mb-0" style="font-size: 0.8rem;">
                {{ $proSubscriptionsEnabled ? 'Choisissez une formule ci-dessous pour souscrire par carte via Stripe.' : 'L’espace prestataire reste accessible gratuitement pendant la préparation de l’offre récurrente.' }}
            </p>
        </div>
    </div>
</div>
@endif

{{-- ===== SECTION 1: Plans d'abonnement (compact) ===== --}}
@if($proSubscriptionsEnabled)
<div class="pro-card sub-page-card mb-4">
    <h6 class="fw-bold mb-3"><i class="fas fa-crown me-2" style="color: #f59e0b;"></i>Plans d'abonnement</h6>
    <p class="text-muted mb-3" style="font-size:.85rem">Renouvellement automatique à chaque échéance. Vous pouvez l’arrêter depuis votre abonnement ; votre accès reste actif jusqu’à la fin de la période payée.</p>
    @php $providerSubscriptionPlans = \App\Support\ProviderSubscriptionPlans::active(); @endphp
    <div class="row g-3">
        @foreach($providerSubscriptionPlans as $planKey => $plan)
        <div class="col-md-6">
            <div class="sub-plan-card {{ !empty($plan['recommended']) ? 'sub-plan-recommended' : '' }} {{ $subscription && $subscription->isActive() && $subscription->plan === $planKey ? 'sub-plan-active' : '' }}">
                @if(!empty($plan['badge']))
                    <div class="sub-plan-badge">{{ $plan['badge'] }}</div>
                @endif
                <div class="d-flex align-items-center gap-3">
                    <div class="sub-plan-icon" style="background: {{ !empty($plan['recommended']) ? 'linear-gradient(135deg, #f59e0b, #ef4444)' : 'linear-gradient(135deg, #a855f7, #6366f1)' }};">
                        <i class="fas {{ !empty($plan['recommended']) ? 'fa-crown' : 'fa-calendar-alt' }}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-baseline gap-2 mb-1">
                            <h6 class="fw-bold mb-0">{{ $plan['label'] }}</h6>
                            @if(!empty($plan['original_price']))
                                <span class="text-muted text-decoration-line-through" style="font-size: 0.72rem;">{{ $plan['original_price'] }}</span>
                            @endif
                            @if(!empty($plan['subtitle']))
                                <span class="text-success fw-semibold" style="font-size: 0.72rem;">{{ $plan['subtitle'] }}</span>
                            @endif
                        </div>
                        <div class="d-flex flex-wrap gap-1" style="font-size: 0.82rem; color: #64748b;">
                            @foreach(array_slice($plan['features'] ?? [], 0, 4) as $feature)
                                <span><i class="fas {{ !empty($plan['recommended']) ? 'fa-star text-warning' : 'fa-check text-success' }} me-1"></i>{{ $feature }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="text-end" style="min-width: 100px;">
                        <div class="fw-bold" style="font-size: 1.5rem; color: var(--pro-primary); line-height: 1;">{{ $plan['price'] }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">{{ $plan['period'] }}</div>
                    </div>
                </div>
                @if(count($plan['features'] ?? []) > 4)
                    <details class="sub-plan-details">
                        <summary>Voir tous les avantages</summary>
                        <ul>@foreach(array_slice($plan['features'], 4) as $feature)<li>{{ $feature }}</li>@endforeach</ul>
                    </details>
                @endif
                <form method="POST" action="{{ route('pro.onboarding.subscribe') }}" class="mt-2">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $planKey }}">
                    <button type="submit" class="btn btn-sm {{ $subscription && $subscription->isActive() && $subscription->plan === $planKey ? 'btn-outline-success' : (!empty($plan['recommended']) ? 'btn-pro-primary' : 'btn-outline-primary') }} w-100" style="border-radius: 8px; font-size: 0.8rem; padding: 6px;" {{ $subscription && $subscription->isActive() ? 'disabled' : '' }}>
                        @if($subscription && $subscription->isActive() && $subscription->plan === $planKey)
                            <i class="fas fa-check me-1"></i> Plan actuel
                        @elseif($subscription && $subscription->isActive())
                            Abonnement déjà actif
                        @else
                            Choisir ce plan
                        @endif
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>
@else
<div class="pro-card sub-page-card mb-4" style="border-left: 4px solid #6366f1;">
    <div class="d-flex align-items-start gap-3">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(99,102,241,.12); color: #4f46e5; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i class="fas fa-tools"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-1">Offre Pro non commercialisée</h6>
            <p class="text-muted mb-2" style="font-size:.84rem;">Aucun prélèvement récurrent n’est proposé pour le moment. Les plans affichés dans l’administration servent uniquement à préparer la future offre.</p>
            <span class="badge bg-success-subtle text-success border border-success-subtle">Accès prestataire gratuit pendant la phase de lancement</span>
        </div>
    </div>
</div>
@endif

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
                    <p class="text-muted mb-0" style="font-size: 0.72rem;">Vos offres rejoignent les offres sponsorisées sur l’accueil des clients.</p>
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
                    <p class="text-muted mb-0" style="font-size: 0.72rem;">Le badge Premium distingue votre abonnement. La vérification d’identité reste séparée.</p>
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
/* Let the subscription workspace use the same desktop width as the other Pro pages. */
.subscription-page-header,
.subscription-page-alert,
.sub-page-card {
    width: 100%;
    max-width: none;
}

.subscription-page-alert {
    border-radius: 12px;
}

/* Subscription plan cards - compact */
.sub-plan-card {
    height: 100%;
    display: flex;
    flex-direction: column;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 16px;
    transition: all 0.3s ease;
    position: relative;
    background: white;
}
.sub-plan-card form { margin-top: auto !important; padding-top: 12px; }
.sub-plan-card button { min-height: 44px; }
.sub-plan-details { margin-top: 12px; font-size: .82rem; color: #64748b; }
.sub-plan-details summary { cursor: pointer; color: #4338ca; min-height: 44px; display: list-item; align-content: center; }
.sub-plan-details ul { padding-left: 20px; margin-bottom: 0; }
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

@media (min-width: 1200px) {
    .subscription-page-header {
        margin-bottom: 2rem;
    }

    .sub-page-card {
        padding: 1.75rem;
    }

    .btn-pts-mini {
        min-width: 40px;
        min-height: 40px;
    }
}

@media (max-width: 576px) {
    .sub-page-card {
        width: 100%;
        padding: 16px !important;
    }

    .sub-plan-card {
        padding: 16px 12px 12px;
    }

    .sub-plan-card > .d-flex {
        flex-wrap: wrap;
        align-items: flex-start !important;
        gap: 10px !important;
    }

    .sub-plan-card > .d-flex > .flex-grow-1 {
        flex: 1 1 calc(100% - 52px);
    }

    .sub-plan-card > .d-flex > .text-end {
        width: 100%;
        min-width: 0 !important;
        padding-left: 48px;
        text-align: left !important;
    }

    .sub-page-card .badge {
        white-space: normal;
        text-align: left;
    }

    .sub-page-card .table-responsive {
        overflow-x: auto !important;
    }
}
</style>

@endsection

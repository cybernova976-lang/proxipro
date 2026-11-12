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

{{-- Billing remains accessible after a payment failure or a change of account status. --}}
<div class="pro-card sub-page-card mb-4" style="padding:22px;border-left:4px solid {{ $subscription?->isActive() ? '#10b981' : '#6366f1' }};">
    <h2 class="h5 fw-bold">{{ $subscription?->isActive() ? 'Votre abonnement est actif' : ($subscription?->hasOpenBilling() ? 'Votre facturation demande une action' : 'Votre compte gratuit') }}</h2>
    @if($subscription)
        <p class="mb-2">{{ $subscription->getPlanLabel() }}</p>
        @if($subscription->isActive())
            @if($subscription->stripe_subscription_id)
                <p>{{ $subscription->auto_renew ? 'Prochain renouvellement' : 'Fin de vos avantages Premium' }} : <strong>{{ $subscription->ends_at?->format('d/m/Y') ?? 'date à vérifier dans Stripe' }}</strong>.</p>
                @unless($subscription->auto_renew)<p class="text-success">Le renouvellement est arrêté. Votre accès reste actif jusqu’à cette date.</p>@endunless
            @else
                <p>Accès accordé manuellement, sans prélèvement. {{ $subscription->ends_at ? 'Valable jusqu’au '.$subscription->ends_at->format('d/m/Y').'.' : 'Sans date de fin définie.' }}</p>
            @endif
        @elseif($subscription->hasOpenBilling())
            <div class="alert alert-warning">Votre accès Premium est suspendu ou en attente de confirmation. Vérifiez votre paiement ou résiliez cet abonnement avant d’en ouvrir un autre.</div>
        @endif
    @else
        <p>{{ $proSubscriptionsEnabled ? 'Vous pouvez continuer gratuitement ou choisir Premium selon vos besoins.' : 'La souscription à Premium est momentanément indisponible. Votre espace prestataire reste accessible.' }}</p>
    @endif
    <div class="d-flex flex-wrap gap-2 subscription-management">
        @if($user->stripe_id)
            <form method="POST" action="{{ route('pro.subscription.billing-portal') }}">@csrf<button class="btn btn-outline-primary">Factures et carte bancaire</button></form>
        @endif
        @if($subscription?->hasOpenBilling())
            @if($subscription->isActive() && ! $subscription->auto_renew)
                <form method="POST" action="{{ route('pro.subscription.resume-renewal') }}" onsubmit="return confirm('Réactiver les prélèvements automatiques à la prochaine échéance ?');">@csrf<button class="btn btn-outline-success">Réactiver le renouvellement</button></form>
            @else
                <form method="POST" action="{{ route('pro.subscription.cancel-renewal') }}" onsubmit="return confirm('{{ $subscription->isActive() ? 'Arrêter le renouvellement ? Vos avantages restent actifs jusqu’à la fin de la période payée.' : 'Résilier cet abonnement ? Aucun nouveau renouvellement ne sera effectué.' }}');">@csrf<button class="btn btn-outline-danger">{{ $subscription->isActive() ? 'Résilier à la fin de la période' : 'Résilier cet abonnement' }}</button></form>
            @endif
        @endif
        <a class="btn btn-outline-secondary" href="{{ route('settings.index') }}">Paramètres du compte</a>
        <a class="btn btn-link" href="{{ route('contact.index') }}">Contacter l’assistance</a>
    </div>
</div>
@if($subscription?->isActive() || $proSubscriptionsEnabled)
    @include('partials.subscription-transparency')
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
                    <button type="submit" class="btn btn-sm {{ $subscription && $subscription->isActive() && $subscription->plan === $planKey ? 'btn-outline-success' : (!empty($plan['recommended']) ? 'btn-pro-primary' : 'btn-outline-primary') }} w-100" style="border-radius: 8px; font-size: 0.8rem; padding: 6px;" {{ $subscription && ($subscription->isActive() || $subscription->hasOpenBilling()) ? 'disabled' : '' }}>
                        @if($subscription && $subscription->isActive() && $subscription->plan === $planKey)
                            <i class="fas fa-check me-1"></i> Plan actuel
                        @elseif($subscription && ($subscription->isActive() || $subscription->hasOpenBilling()))
                            Gérer l’abonnement existant
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
            <h6 class="fw-bold mb-1">Abonnement Premium momentanément indisponible</h6>
            <p class="text-muted mb-0" style="font-size:.84rem;">La souscription en ligne est suspendue pour le moment. Aucun paiement n’est demandé et aucun abonnement n’est activé automatiquement.</p>
        </div>
    </div>
</div>
@endif

<div class="pro-card sub-page-card mb-4">@include('partials.premium-comparison')</div>
@if($user->isProfessionnel() || $user->isServiceProvider())
<div class="pro-card sub-page-card mb-4">
    <h2 class="h5 fw-bold">Mesurer votre activité</h2>
    <p class="text-muted">Consultez vos vues de profil, vos annonces et votre activité dans vos statistiques. Ces chiffres décrivent votre audience réelle ; ils ne mesurent pas à eux seuls l’effet de Premium.</p>
    <a href="{{ route('pro.analytics') }}" class="btn btn-outline-primary">Voir mes statistiques</a>
</div>
@endif

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
                    <td>{{ $sub->starts_at?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $sub->ends_at?->format('d/m/Y') ?? '—' }}</td>
                    <td class="fw-bold">{{ number_format((float) $sub->amount, 2, ',', ' ') }} €</td>
                    <td>
                        <span class="pro-status pro-status-{{ $sub->isActive() ? 'success' : 'secondary' }}">
                            {{ $sub->isActive() ? ($sub->auto_renew ? 'Actif' : 'Fin programmée / accès manuel') : ($sub->hasOpenBilling() ? 'À vérifier' : ($sub->status === 'cancelled' ? 'Résilié' : 'Expiré')) }}
                        </span>
                    </td>
                    <td>
                        @if($sub->stripe_subscription_id && $user->stripe_id)
                            <form action="{{ route('pro.subscription.billing-portal') }}" method="POST">@csrf<button class="btn btn-sm btn-outline-secondary">Factures Stripe</button></form>
                        @else
                            <span class="text-muted">Accès manuel</span>
                        @endif
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

.subscription-management .btn { min-height: 44px; white-space: normal; }
@media(max-width:575px) { .subscription-management > form, .subscription-management > a, .subscription-management button { width: 100%; } }

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

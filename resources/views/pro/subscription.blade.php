@extends('pro.layout')
@section('title', 'Abonnement Pro - Espace Pro')
@section('topbar_title', 'Abonnement Premium')

@push('head')
<link rel="stylesheet" href="{{ asset('css/pro-subscription.css') }}?v={{ filemtime(public_path('css/pro-subscription.css')) }}">
@endpush

@section('content')
{{-- Trois zones : où en est mon abonnement · ce que Premium apporte · le comparatif.
     Moyen de paiement, résiliation et factures vivent sur la page Facturation. --}}
@php
    $pkLatePayment = (bool) $subscription?->inPaymentGrace();
    $pkActive = (bool) $subscription?->isActive();
    $pkOpenBilling = (bool) $subscription?->hasOpenBilling();
    $pkManaged = $pkActive && filled($subscription->stripe_subscription_id);
    $pkStopping = $pkManaged && ! $subscription->auto_renew && ! $pkLatePayment;
    [$pkTone, $pkChip, $pkStateTitle] = match (true) {
        $pkLatePayment => ['late', 'Paiement en retard', 'Votre paiement est en retard'],
        $pkStopping => ['muted', 'Résiliation programmée', 'Votre abonnement est actif'],
        $pkActive && ! $pkManaged => ['ok', 'Accès offert', 'Votre abonnement est actif'],
        $pkActive => ['ok', 'Actif', 'Votre abonnement est actif'],
        $pkOpenBilling => ['late', 'Paiement à confirmer', 'Votre facturation demande une action'],
        default => ['muted', 'Compte gratuit', 'Votre compte gratuit'],
    };
    $pkPlans = $proSubscriptionsEnabled ? \App\Support\ProviderSubscriptionPlans::active() : [];
    $pkCurrentPlan = $subscription ? \App\Support\ProviderSubscriptionPlans::get($subscription->plan) : null;
    $pkCanSubscribe = $proSubscriptionsEnabled && ! $pkActive && ! $pkOpenBilling;
    $pkDate = $subscription?->ends_at?->format('d/m/Y');
@endphp

<div class="pro-content-header subscription-page-header">
    <div>
        <nav aria-label="Fil d’Ariane">
            <ol class="breadcrumb mb-1 pk-sub__crumbs">
                <li class="breadcrumb-item"><a href="{{ route('pro.dashboard') }}">Espace Pro</a></li>
                <li class="breadcrumb-item active" aria-current="page">Abonnement Premium</li>
            </ol>
        </nav>
        <h1>Abonnement Premium</h1>
        <p class="pk-sub__lead">Une seule formule, mensuelle et sans engagement : vos offres sont vues avant les autres.</p>
    </div>
</div>

@if(session('info'))
<div class="alert alert-info alert-dismissible fade show subscription-page-alert" role="status">
    <i class="fas fa-info-circle me-2" aria-hidden="true"></i>{{ session('info') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
</div>
@endif

<div class="pk-sub">
    <div class="pk-sub__top">
        {{-- Zone 1 · où en est mon abonnement --}}
        <section class="pro-card sub-page-card pk-sub-state pk-sub-state--{{ $pkTone }}" aria-labelledby="pkSubStateTitle">
            <header class="pk-sub-state__head">
                <h2 id="pkSubStateTitle">{{ $pkStateTitle }}</h2>
                <span class="pk-sub-chip pk-sub-chip--{{ $pkTone }}">{{ $pkChip }}</span>
            </header>

            @if($subscription)
                <dl class="pk-sub-facts">
                    <div><dt>Formule</dt><dd>{{ $subscription->getPlanLabel() }}</dd></div>
                    @if($pkLatePayment)
                        <div><dt>À régler avant le</dt><dd>{{ $subscription->grace_ends_at->format('d/m/Y') }}</dd></div>
                    @elseif($pkActive && ! $pkManaged)
                        <div><dt>Accès accordé par Prokejem</dt><dd>{{ $pkDate ? 'Valable jusqu’au '.$pkDate : 'Sans date de fin définie' }} · sans prélèvement</dd></div>
                    @elseif($pkStopping)
                        <div><dt>Fin de vos avantages Premium</dt><dd>{{ $pkDate ?? 'Date à vérifier sur la page Facturation' }}</dd></div>
                    @elseif($pkActive)
                        <div><dt>Prochain renouvellement</dt><dd>{{ $pkDate ?? 'Date à vérifier sur la page Facturation' }}</dd></div>
                    @endif
                </dl>

                @if($pkLatePayment)
                    <p class="pk-sub-state__note pk-sub-state__note--late">Votre dernier paiement a échoué. Réglez-le avant le {{ $subscription->grace_ends_at->format('d/m/Y') }} pour garder vos avantages Premium ; passé cette date, votre compte repassera en gratuit.</p>
                @elseif($pkStopping)
                    <p class="pk-sub-state__note">Le renouvellement est arrêté. Votre accès reste actif jusqu’à cette date, puis votre compte redevient gratuit.</p>
                @elseif(! $pkActive && $pkOpenBilling)
                    <p class="pk-sub-state__note pk-sub-state__note--late">Votre accès Premium est suspendu ou en attente de confirmation. Vérifiez votre paiement ou résiliez cet abonnement avant d’en ouvrir un autre.</p>
                @endif
            @else
                <p class="pk-sub-state__note">{{ $proSubscriptionsEnabled
                    ? 'Publier et échanger restent gratuits. Premium est une option : vous pouvez la prendre et l’arrêter quand vous voulez.'
                    : 'La souscription à Premium est momentanément indisponible. Votre espace prestataire reste accessible.' }}</p>
            @endif

            <div class="pk-sub-actions subscription-management">
                @if($subscription || $user->stripe_id)
                    <a class="pk-sub-btn{{ $pkLatePayment ? ' pk-sub-btn--primary' : '' }}" href="{{ route('billing.index') }}">
                        {{ $pkLatePayment ? 'Régler mon paiement' : 'Gérer mon abonnement et mes factures' }}
                    </a>
                @endif
                <a class="pk-sub-link" href="{{ route('contact.index') }}">Contacter l’assistance</a>
            </div>
        </section>

        {{-- Bon à savoir : résiliation, prélèvement, ce que Premium ne promet pas. --}}
        @if($pkActive || $proSubscriptionsEnabled)
            <aside class="pk-sub-notes" aria-label="Bon à savoir">
                @include('partials.subscription-transparency')
            </aside>
        @endif
    </div>

    {{-- Zone 2 · la formule : à souscrire, ou les avantages déjà ouverts --}}
    @if($pkCanSubscribe)
        @foreach($pkPlans as $planKey => $plan)
            <section class="pro-card sub-page-card pk-sub-offer" aria-labelledby="pkSubOffer-{{ $planKey }}">
                <div class="pk-sub-offer__main">
                    <h2 id="pkSubOffer-{{ $planKey }}">Premium · {{ $plan['label'] }}</h2>
                    @if(!empty($plan['description']))<p class="pk-sub-offer__desc">{{ $plan['description'] }}</p>@endif
                    <ul class="pk-sub-features">
                        @foreach($plan['features'] ?? [] as $feature)
                            <li><i class="fas fa-check" aria-hidden="true"></i><span>{{ $feature }}</span></li>
                        @endforeach
                    </ul>
                </div>
                <div class="pk-sub-offer__buy">
                    <p class="pk-sub-price"><strong>{{ $plan['price'] }}</strong> <span>{{ $plan['period'] }}</span></p>
                    <p class="pk-sub-offer__terms">Sans engagement. Le montant est confirmé avant paiement.</p>
                    <form method="POST" action="{{ route('pro.onboarding.subscribe') }}">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $planKey }}">
                        <button type="submit" class="pk-sub-btn pk-sub-btn--primary">Choisir ce plan</button>
                    </form>
                </div>
            </section>
        @endforeach
    @elseif($pkActive && $pkCurrentPlan)
        <section class="pro-card sub-page-card" aria-labelledby="pkSubIncluded">
            <h2 class="pk-sub__h2" id="pkSubIncluded">Ce que votre abonnement vous ouvre</h2>
            <ul class="pk-sub-features pk-sub-features--columns">
                @foreach($pkCurrentPlan['features'] ?? [] as $feature)
                    <li><i class="fas fa-check" aria-hidden="true"></i><span>{{ $feature }}</span></li>
                @endforeach
            </ul>
        </section>
    @elseif(! $proSubscriptionsEnabled)
        <section class="pro-card sub-page-card pk-sub-closed">
            <h2 class="pk-sub__h2">Abonnement Premium momentanément indisponible</h2>
            <p>La souscription en ligne est suspendue pour le moment. Aucun paiement n’est demandé et aucun abonnement n’est activé automatiquement.</p>
        </section>
    @endif

    {{-- Zone 3 · le comparatif Gratuit / À la carte / Premium --}}
    <div class="pro-card sub-page-card">@include('partials.premium-comparison')</div>

    @if($user->isProfessionnel() || $user->isServiceProvider())
        <p class="pk-sub__stats">
            Vos vues de profil et l’activité de vos annonces sont mesurées dans vos statistiques ; elles ne mesurent pas à elles seules l’effet de Premium.
            <a class="pk-sub-link" href="{{ route('pro.analytics') }}">Voir mes statistiques</a>
        </p>
    @endif
</div>
@endsection

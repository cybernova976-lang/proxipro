@extends('pro.layout')
@section('title', 'Tableau de bord - Espace Pro')
@section('topbar_title', $isPro ? 'Accueil' : 'Devenir prestataire')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/pro-dashboard.css') }}?v=20261113">
@endpush

@section('content')
@if($isPro)
    {{-- Poste de pilotage : que faire maintenant, quatre chiffres, derniers documents. --}}
    <div class="pk-dash">
        <header class="pk-dash__head">
            <p class="pk-dash__eyebrow">{{ $user->getAccountTypeLabel() }}</p>
            <h1>Bonjour, {{ $user->company_name ?: $user->name }}</h1>
        </header>

        @include('pro.dashboard.next-steps')
        @include('pro.dashboard.figures')

        @if($isProfessional)
            @include('pro.dashboard.documents')
        @endif

        @if(! $subscription && \App\Support\PlatformFeatures::proSubscriptionsEnabled())
            <p class="pk-dash-premium">
                <i class="fas fa-crown" aria-hidden="true"></i>
                <span><strong>Premium</strong> : vos offres mises en avant, le rapport d’activité mensuel et la relance automatique de vos devis.</span>
                <a href="{{ route('pro.subscription') }}">Découvrir Premium</a>
            </p>
        @endif
    </div>
@else
    @include('pro.dashboard.join')
@endif
@endsection

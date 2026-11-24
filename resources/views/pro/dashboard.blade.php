@extends('pro.layout')
@section('title', 'Tableau de bord - Espace Pro')
@section('topbar_title', $isPro ? 'Accueil' : 'Devenir prestataire')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/pro-dashboard.css') }}?v={{ filemtime(public_path('css/pro-dashboard.css')) }}">
@endpush

@section('content')
@if($isPro)
    {{-- Poste de pilotage : que faire maintenant et les derniers documents à gauche, les chiffres à droite. --}}
    <div class="pk-dash">
        <header class="pk-dash__head">
            <p class="pk-dash__eyebrow">{{ Str::ucfirst(now()->locale('fr')->isoFormat('dddd D MMMM YYYY')) }}</p>
            <h1>Bonjour, {{ $user->company_name ?: $user->name }}</h1>
        </header>

        <div class="pk-dash__grid">
            <div class="pk-dash__main">
                @include('pro.dashboard.next-steps')
                @include('pro.dashboard.documents')
            </div>

            <aside class="pk-dash__aside" aria-label="Vos chiffres">
                @include('pro.dashboard.figures')

                @if(! $subscription && \App\Support\PlatformFeatures::proSubscriptionsEnabled())
                    <p class="pk-dash-premium">
                        <i class="fas fa-crown" aria-hidden="true"></i>
                        <span><strong>Premium</strong> : vos offres mises en avant, le rapport d’activité mensuel et la relance automatique de vos devis.</span>
                        <a href="{{ route('pro.subscription') }}">Découvrir Premium</a>
                    </p>
                @endif
            </aside>
        </div>
    </div>
@else
    @include('pro.dashboard.join')
@endif
@endsection

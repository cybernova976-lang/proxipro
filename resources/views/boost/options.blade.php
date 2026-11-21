@extends('layouts.app')
@section('title', ($isOffer ? 'Mettre en avant mon offre' : 'Mettre en avant ma demande').' - Prokejem')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/boost-options.css') }}?v={{ @filemtime(public_path('css/boost-options.css')) ?: 1 }}">
@endpush
@section('content')
{{--
    Page d'achat des nouvelles règles de mise en avant (BoostController::options).
    Un seul choix à la fois, un seul bouton de paiement. Chaque phrase décrit un
    effet réellement appliqué par le classement ; aucun chiffre n'est estimé.
--}}
<div class="pk-boost">
    <header>
        @if($justPublished)
            <p class="pk-boost__eyebrow">Votre annonce est publiée. La publication reste gratuite.</p>
        @endif
        <h1>{{ $isOffer ? 'Mettre en avant mon offre' : 'Mettre en avant ma demande' }}</h1>
        <p class="pk-boost__ad">{{ $ad->title }}@if($ad->location) · {{ $ad->location }}@endif</p>
        @if($views)
            <p class="pk-boost__views">{{ $views['count'] }} vue{{ $views['count'] > 1 ? 's' : '' }} depuis le {{ $views['since']->format('d/m/Y') }}</p>
        @endif
    </header>

    @if(count($current))
        <ul class="pk-boost__current" aria-label="Mise en avant en cours">
            @foreach($current as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    @endif

    @if($canBuy)
        @php $pkSingleChoice = collect($groups)->flatMap(fn ($group) => $group['options'])->where('unavailable', null)->count() === 1; @endphp
        <form method="POST" action="{{ route('boost.purchase.stripe', $ad) }}">
            @csrf
            @if($isPro)
                <p class="pk-boost__intro pk-boost__intro--top">Votre abonnement Premium réduit de 20 % le prix des options ci-dessous.</p>
            @endif
            @foreach($groups as $group)
                <fieldset class="pk-boost__group" data-group="{{ $group['key'] }}">
                    <legend>{{ $group['title'] }}</legend>
                    <p class="pk-boost__intro">{{ $group['intro'] }}</p>
                    @foreach($group['options'] as $key => $choice)
                        @if($choice['unavailable'])
                            <div class="pk-boost__choice pk-boost__choice--off">
                                <span class="pk-boost__label">{{ $choice['label'] }}</span>
                                <span class="pk-boost__price">{{ $choice['unavailable'] }}</span>
                            </div>
                        @else
                            <label class="pk-boost__choice">
                                <input type="radio" name="package" value="{{ $key }}" required @checked($pkSingleChoice || old('package') === $key)>
                                <span class="pk-boost__label">{{ $choice['label'] }}
                                    @if($choice['note'])<span class="pk-boost__note">{{ $choice['note'] }}</span>@endif
                                </span>
                                <span class="pk-boost__price">
                                    @if($choice['full_price'])<s>{{ number_format($choice['full_price'], 2, ',', ' ') }} €</s>@endif
                                    {{ number_format($choice['price'], 2, ',', ' ') }} €
                                </span>
                            </label>
                        @endif
                    @endforeach
                </fieldset>
            @endforeach
            @error('package')<p class="pk-boost__error" role="alert">{{ $message }}</p>@enderror
            <button type="submit" class="pk-boost__pay">Continuer vers le paiement</button>
            <p class="pk-boost__legal">Paiement par carte, sécurisé par Stripe ; le montant est confirmé avant paiement. Aucune place n’est fixe, et une option ne promet ni contact ni vente. <a href="{{ route('legal.platform-rules') }}#classement">Règles de classement</a></p>
        </form>
    @endif

    @if($premium)
        <aside class="pk-boost__premium" aria-labelledby="pkBoostPremiumTitle">
            <h2 id="pkBoostPremiumTitle">Avec l’abonnement Premium, toutes vos offres sont à la une</h2>
            <p>Pour <strong>{{ $premium['price'] }} par mois</strong>@if($premium['cheaper_than']), soit moins qu’une seule offre à la une pendant 30 jours ({{ number_format($premium['cheaper_than'], 2, ',', ' ') }} €)@endif :</p>
            <ul>
                <li>toutes vos offres sont à la une, avec la mention « Sponsorisé » ;</li>
                <li>chacune remonte automatiquement chaque semaine ;</li>
                <li>les options à l’unité sont à −20 %.</li>
            </ul>
            <a href="{{ route('public.provider-offers') }}">Voir l’abonnement Premium</a>
        </aside>
    @endif

    <a class="pk-boost__back" href="{{ route('ads.show', $ad) }}">Voir mon annonce</a>
</div>
@endsection

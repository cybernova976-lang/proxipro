@extends('layouts.app')
@section('title', 'Facturation - Prokejem')

@section('content')
<link rel="stylesheet" href="{{ asset('css/billing.css') }}?v={{ filemtime(public_path('css/billing.css')) }}">
<link rel="stylesheet" href="{{ asset('css/one-time-access.css') }}?v={{ filemtime(public_path('css/one-time-access.css')) }}">
<div class="pk-billing">
    <header class="pk-billing__head">
        <p class="pk-billing__eyebrow">Mon compte</p>
        <h1>Facturation</h1>
        <p>Vos abonnements, votre moyen de paiement et vos paiements, au même endroit.</p>
    </header>

    @if($late)
        @include('billing.partials.late-payment', ['subscription' => $late])
    @endif

    <section class="pk-billing__section" aria-labelledby="pk-billing-subscriptions">
        <h2 id="pk-billing-subscriptions">Mes abonnements</h2>
        @forelse($current as $subscription)
            @include('billing.partials.subscription', ['subscription' => $subscription, 'paymentMethod' => $paymentMethods[$subscription->id] ?? null])
        @empty
            <p class="pk-billing__empty">Vous n’avez aucun abonnement en cours : votre compte est gratuit.</p>
            @include('billing.partials.plans')
        @endforelse
        @include('partials.one-time-access', ['oneTimeOrigin' => 'billing', 'oneTimeFramed' => true])
    </section>

    @include('billing.partials.payments')

    <p class="pk-billing__help">Une question sur un paiement ? <a href="{{ route('contact.index') }}">Contactez l’assistance</a>.</p>
</div>
<script src="{{ asset('js/billing.js') }}?v={{ filemtime(public_path('js/billing.js')) }}" defer></script>
@endsection

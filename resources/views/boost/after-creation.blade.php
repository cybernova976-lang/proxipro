@extends('layouts.app')
@section('title', 'Visibilité de mon annonce - Prokejem')
@push('styles')
<style>
.promotion-page{max-width:1100px;margin:auto;padding:32px 20px;color:#17243a}
.promotion-page h1{font-size:clamp(1.5rem,3vw,2rem);font-weight:750}
.promotion-page>p{color:#64748b;line-height:1.6}
.promotion-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,230px),1fr));gap:16px;margin:24px 0}
.promotion-card{padding:24px;background:white;border:1px solid #dfe5f0;border-radius:16px;display:flex;flex-direction:column;gap:12px}
.promotion-card:only-child{max-width:380px}
.promotion-card h2{font-size:1.15rem;font-weight:700;margin:0}
.promotion-card p{margin:0;line-height:1.6;color:#64748b}
.promotion-card strong{font-size:1.6rem;color:#4338ca}
.promotion-card ul{padding-left:18px;font-size:.9rem;line-height:1.6}
.promotion-card form{margin-top:auto}
.promotion-card button,.promotion-page>a{min-height:44px;display:inline-flex;align-items:center;justify-content:center}
.promotion-card button{width:100%;white-space:normal}
@media(max-width:575px){.promotion-page{padding:24px 16px}.promotion-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<div class="success-container">
<main class="promotion-page">
    <h1>Votre annonce est publiée</h1>
    <p>{{ $ad->title }} · {{ $ad->location }}</p>
    <p>La publication reste gratuite. Vous pouvez choisir une option de visibilité ou consulter directement votre annonce.</p>
    @if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
    @if($isPro)<p>Votre abonnement Premium inclut une réduction de 20 % sur les options ci-dessous.</p>@endif
    @if($ad->service_type === 'offre' && count($packages))
        <h2 class="h5 mt-4">Choisissez la durée de votre boost</h2>
        <p>Votre offre porte la mention « Sponsorisé » et passe avant les offres non sponsorisées sur l’accueil des clients de son territoire. Aucune place n’est fixe. <a href="{{ route('legal.platform-rules') }}#classement">Voir les règles de classement</a>.</p>
    @endif
    <div class="promotion-grid">
        @if($ad->service_type === 'demande')
            <article class="promotion-card" data-type="urgent">
                <h2>Option Urgent</h2>
                <p>Pendant {{ $urgent['duration_days'] }} jours, votre demande passe avant les autres demandes du même métier dans l’accueil des prestataires et porte la mention « Urgent · option payante ».</p>
                <strong>{{ number_format($urgent['price_euros'], 2, ',', ' ') }} €</strong>
                @if($ad->isCurrentlyUrgent())
                    <p>Option déjà active jusqu’au {{ $ad->urgent_until->format('d/m/Y') }}.</p>
                @else
                    <form method="POST" action="{{ route('ads.make-urgent.stripe', $ad) }}">@csrf<button class="btn btn-primary" type="submit">Payer par carte via Stripe</button></form>
                @endif
            </article>
        @else
            @forelse($packages as $key => $package)
                <article class="promotion-card" data-type="boost">
                    <h2>{{ $package['name'] }}</h2>
                    <p>{{ $package['duration_days'] }} jours de visibilité</p>
                    <strong>{{ number_format($package['price_euros'], 2, ',', ' ') }} €</strong>
                    <form method="POST" action="{{ route('boost.purchase.stripe', $ad) }}">@csrf<input type="hidden" name="package" value="{{ $key }}"><button class="btn btn-primary" type="submit">Payer par carte via Stripe</button></form>
                </article>
            @empty
                <p>Votre abonnement Premium couvre déjà la mise en avant de cette offre. Aucun boost supplémentaire n’est nécessaire pour les durées proposées.</p>
            @endforelse
        @endif
    </div>
    <p>Cette option agit sur l’affichage de votre annonce. Le montant est confirmé sur Stripe avant paiement.</p>
    <a class="btn btn-outline-primary" href="{{ route('ads.show', $ad) }}">Voir mon annonce</a>
</main>
</div>
@endsection

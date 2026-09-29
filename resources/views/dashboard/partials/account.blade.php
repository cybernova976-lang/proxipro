<div class="pk-dashboard">
    <header class="pk-dash-head"><div><span class="pk-dash-kicker">Votre espace personnel</span><h1>Compte et achats</h1><p>Vos informations, vos options et votre historique de paiement.</p></div><a href="{{ route('home') }}" class="pk-dash-link">Retour à mon suivi <i class="fas fa-arrow-right"></i></a></header>
    <div class="pk-account-grid">
        <section class="pk-dash-panel"><h2>Votre profil</h2><p>{{ Auth::user()->name }}</p><p>{{ Auth::user()->identity_verified ? 'Votre identité est vérifiée.' : 'Vous pouvez faire vérifier votre identité depuis votre profil.' }}</p><a href="{{ route('profile.show') }}" class="pk-dash-link">Gérer mon profil <i class="fas fa-arrow-right"></i></a></section>
        <section class="pk-dash-panel"><h2>Votre formule</h2><p>{{ $subscription ? $subscription->getPlanLabel() : $accountPlan }}</p>@if($subscription)<p>@if($subscription->ends_at) Active jusqu’au {{ $subscription->ends_at->format('d/m/Y') }}. @else Abonnement actif. @endif</p><a href="{{ route('pro.subscription') }}" class="pk-dash-link">Gérer mon abonnement</a>@else<p>Retrouvez les fonctionnalités et les options disponibles pour votre compte.</p><a href="{{ route('pricing.index') }}" class="pk-dash-link">Consulter les formules</a>@endif</section>
        <section class="pk-dash-panel"><h2>Points et paiements</h2><p>{{ Auth::user()->available_points ?? 0 }} points disponibles</p><div class="pk-dash-links"><a href="{{ route('points.dashboard') }}">Mes points</a><a href="{{ route('home') }}#transactions" onclick="dashboardNav('transactions'); return false;">Historique et factures</a></div></section>
        <section class="pk-dash-panel"><h2>Préférences et assistance</h2><div class="pk-dash-links"><a href="{{ route('home') }}#settings" onclick="dashboardNav('settings'); return false;">Mes paramètres</a><a href="{{ route('contact.index') }}">Contacter l’assistance</a></div></section>
    </div>
    @if($promotions->isNotEmpty())
        <section class="pk-dash-panel mt-4"><h2>Options de visibilité actives</h2><p>Les options appliquées à vos annonces. Les paiements sont détaillés dans votre historique.</p>
            @foreach($promotions as $ad)<div class="pk-promotion"><div><strong>{{ $ad->title }}</strong><p>@if($ad->is_boosted && $ad->boost_end?->isFuture()) Mise en avant jusqu’au {{ $ad->boost_end->format('d/m/Y') }} @endif @if($ad->urgent_until?->isFuture()) · Option Urgent jusqu’au {{ $ad->urgent_until->format('d/m/Y') }} @endif</p></div><a href="{{ route('ads.show', $ad) }}">Voir l’annonce</a></div>@endforeach
        </section>
    @endif
</div>

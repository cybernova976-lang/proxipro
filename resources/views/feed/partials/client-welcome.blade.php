@php($pkFirstName = trim(Str::before(trim(Auth::user()->name ?? 'Utilisateur'), ' ')) ?: 'Utilisateur')
<section class="pk-client-welcome" aria-labelledby="pkStateTitle">
    <div class="pk-client-welcome__top">
        <div>
            <span class="pk-client-welcome__eyebrow">Mon quotidien</span>
            <h1 id="pkStateTitle">Bonjour {{ $pkFirstName }}<span aria-hidden="true">.</span></h1>
            <p>De quoi avez-vous besoin aujourd’hui ?</p>
        </div>
        <a class="pk-btn" href="{{ route('demand.create') }}"><i class="fas fa-plus" aria-hidden="true"></i> Publier une demande</a>
    </div>
    <div class="pk-client-welcome__tools">
        @include('feed.partials.intent-bar')
        <a class="pk-client-location" href="{{ route('feed.professionals', array_filter(['city' => $geoCity, 'country' => $geoCountry])) }}">
            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
            <span>{{ $geoCity ?: 'Choisir une zone' }}@if($geoCountry)<small>{{ $geoCountry }}</small>@endif</span>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
        </a>
    </div>
    <nav class="pk-client-sections" aria-label="Explorer mon accueil">
        <a href="#services"><i class="fas fa-th-large" aria-hidden="true"></i> Services</a>
        <a href="#besoin"><i class="far fa-edit" aria-hidden="true"></i> Décrire mon besoin</a>
        <a href="#emploi"><i class="fas fa-briefcase" aria-hidden="true"></i> Emploi</a>
        <a href="{{ route('home') }}"><i class="fas fa-clipboard-list" aria-hidden="true"></i> Mon suivi</a>
    </nav>
</section>

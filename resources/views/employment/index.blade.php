@extends('layouts.app')
@section('title', 'Emploi - Prokejem')
@section('meta_description', 'Offres d’emploi par territoire : recherche sur le site France Travail.')
@include('employment.partials.head')
@section('content')
<div class="pk-employment-page">
    <header class="pk-employment-page__intro">
        <h1>Explorer les offres d’emploi</h1>
        <p>Choisissez votre territoire : la recherche se fait sur le site France Travail.</p>
    </header>
    <nav class="pk-employment-territories pk-employment-territories--main" aria-label="Emploi par territoire">
        <h2 class="pk-employment-territories__title">Votre territoire</h2>
        <ul>
            @foreach($pkTerritories as $pkTerritoryItem)
                <li><a href="{{ route('employment.territory', ['territoire' => $pkTerritoryItem->slug]) }}">Emploi {{ $pkTerritoryItem->withPreposition() }}</a></li>
            @endforeach
        </ul>
    </nav>
    <section class="pk-employment-card" aria-labelledby="pkEmploymentTitle">
        <span class="pk-employment-card__source"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i> Site externe · France Travail</span>
        <h2 id="pkEmploymentTitle">Ailleurs en France</h2>
        <p>Recherchez un métier, choisissez votre lieu et affinez les résultats selon le contrat qui vous intéresse.</p>
        <a class="pk-employment-card__link" href="{{ $pkSearchUrl }}" target="_blank" rel="noopener noreferrer">
            Offres d’emploi sur France Travail (site externe) <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>
            <span class="visually-hidden">(nouvel onglet)</span>
        </a>
        <small>La recherche et la candidature se poursuivent sur le site France Travail.</small>
    </section>
    @include('employment.partials.next-steps')
    @auth
        <a class="pk-employment-page__back" href="{{ route('feed', ['mode' => 'client']) }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Retrouver les services sur Prokejem</a>
    @endauth
</div>
@endsection

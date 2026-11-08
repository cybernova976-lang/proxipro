@extends('layouts.app')
@section('title', 'Emploi '.$pkTerritory->withPreposition().' - Prokejem')
@section('meta_description', 'Offres d’emploi '.$pkTerritory->withPreposition().' : recherche sur le site France Travail.')
@include('employment.partials.head')
@section('content')
<div class="pk-employment-page">
    <header class="pk-employment-page__intro">
        <h1>Emploi {{ $pkTerritory->withPreposition() }}</h1>
        <p>Les offres d’emploi {{ $pkTerritory->withDe() }} se consultent sur le site France Travail.</p>
    </header>
    <section class="pk-employment-card" aria-labelledby="pkEmploymentTitle">
        <span class="pk-employment-card__source"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i> Site externe · France Travail</span>
        <h2 id="pkEmploymentTitle">Recherchez un emploi {{ $pkTerritory->withPreposition() }}</h2>
        <p>Choisissez un métier et affinez les résultats selon le contrat qui vous intéresse.</p>
        <a class="pk-employment-card__link" href="{{ $pkSearchUrl }}" target="_blank" rel="noopener noreferrer">
            {{ $pkSearchLabel }} (site externe) <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>
            <span class="visually-hidden">(nouvel onglet)</span>
        </a>
        <small>La recherche et la candidature se poursuivent sur le site France Travail.</small>
    </section>
    @include('employment.partials.next-steps')
    @if($pkOtherTerritories !== [])
        <nav class="pk-employment-territories" aria-label="Emploi dans les autres territoires">
            <h2 class="pk-employment-territories__title">Autres territoires</h2>
            <ul>
                @foreach($pkOtherTerritories as $pkOther)
                    <li><a href="{{ route('employment.territory', ['territoire' => $pkOther->slug]) }}">{{ $pkOther->label }}</a></li>
                @endforeach
            </ul>
        </nav>
    @endif
    @auth
        <a class="pk-employment-page__back" href="{{ route('feed', ['mode' => 'client']) }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Retrouver les services sur Prokejem</a>
    @endauth
</div>
@endsection

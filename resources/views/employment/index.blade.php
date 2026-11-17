@extends('layouts.app')
@section('title', 'Emploi - Prokejem')
@section('meta_description', 'Offres d’emploi publiées par les membres de Prokejem, par territoire.')
@include('employment.partials.head')
@section('content')
<div class="pk-employment-page">
    <header class="pk-employment-page__intro">
        <h1>Offres d’emploi</h1>
        <p>Les offres d’emploi publiées par les membres de Prokejem.</p>
    </header>
    @include('employment.partials.offers', ['pkOffersPlace' => ''])
    <nav class="pk-employment-territories" aria-label="Emploi par territoire">
        <h2 class="pk-employment-territories__title">Par territoire</h2>
        <ul>
            @foreach($pkTerritories as $pkTerritoryItem)
                <li><a href="{{ route('employment.territory', ['territoire' => $pkTerritoryItem->slug]) }}">Emploi {{ $pkTerritoryItem->withPreposition() }}</a></li>
            @endforeach
        </ul>
    </nav>
    @include('employment.partials.next-steps')
    @auth
        <a class="pk-employment-page__back" href="{{ route('feed', ['mode' => 'client']) }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Retrouver les services sur Prokejem</a>
    @endauth
</div>
@endsection

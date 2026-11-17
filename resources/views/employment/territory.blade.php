@extends('layouts.app')
@section('title', 'Emploi '.$pkTerritory->withPreposition().' - Prokejem')
@section('meta_description', 'Offres d’emploi '.$pkTerritory->withPreposition().' publiées par les membres de Prokejem.')
@include('employment.partials.head')
@section('content')
<div class="pk-employment-page">
    <header class="pk-employment-page__intro">
        <h1>Emploi {{ $pkTerritory->withPreposition() }}</h1>
        <p>Les offres d’emploi publiées par les membres de Prokejem {{ $pkTerritory->withPreposition() }}.</p>
    </header>
    @include('employment.partials.offers', ['pkOffersPlace' => ' '.$pkTerritory->withPreposition()])
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

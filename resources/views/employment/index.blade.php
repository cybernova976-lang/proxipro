@extends('layouts.app')
@section('title', 'Emploi - Prokejem')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/client-space.css') }}?v={{ filemtime(public_path('css/client-space.css')) }}">
@endpush
@section('content')
<div class="pk-employment-page">
    <header class="pk-employment-page__intro">
        <h1>Explorer les offres d’emploi</h1>
        <p>Un espace dédié à votre recherche d’emploi, à retrouver quand vous en avez besoin.</p>
    </header>
    <section class="pk-employment-card" aria-labelledby="pkEmploymentTitle">
        <span class="pk-employment-card__source"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i> Site externe · France Travail</span>
        <h2 id="pkEmploymentTitle">Trouvez des recrutements dans votre région</h2>
        <p>Recherchez un métier, choisissez votre lieu et affinez les résultats selon le contrat qui vous intéresse.</p>
        <a class="pk-employment-card__link" href="https://candidat.francetravail.fr/rechercheoffre/emploi" target="_blank" rel="noopener noreferrer">
            Explorer les offres sur France Travail <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>
            <span class="visually-hidden">(nouvel onglet)</span>
        </a>
        <small>La recherche et la candidature se poursuivent sur le site France Travail.</small>
    </section>
    <a class="pk-employment-page__back" href="{{ route('feed', ['mode' => 'client']) }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Retrouver les services sur Prokejem</a>
</div>
@endsection

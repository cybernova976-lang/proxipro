@extends('layouts.app')
@section('title', 'Annuaire des prestataires — Prokejem')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/provider-directory.css') }}?v=20260906">
@endpush
@section('content')
<div class="container provider-directory">
    <header class="directory-heading">
        <a href="{{ route('feed') }}" class="directory-back">← Retour à l’accueil</a>
        <p class="directory-eyebrow">LES BONNES PERSONNES POUR VOTRE PROJET</p>
        <h1>Annuaire des prestataires</h1>
        <p>Comparez les services, les réalisations et les avis. Puis échangez avec le prestataire de votre choix.</p>
    </header>
    <form class="directory-search" method="GET" action="{{ route('feed.professionals') }}" role="search" aria-label="Rechercher un prestataire">
        <div class="directory-field">
            <label for="directorySearch">Métier ou nom</label>
            <input id="directorySearch" name="q" value="{{ $search }}" maxlength="100" placeholder="Ex. plombier" type="search">
        </div>
        <div class="directory-field">
            <label for="directoryCity">Ville déclarée</label>
            <input id="directoryCity" name="city" value="{{ $city }}" maxlength="120" placeholder="Ex. Mamoudzou" aria-describedby="directoryLocationHelp">
        </div>
        <div class="directory-field">
            <label for="directoryCountry">Pays / territoire</label>
            <select id="directoryCountry" name="country">
                <option value="">Tous</option>
                @foreach($directoryCountries->merge([$country])->filter()->unique()->sort() as $choice)
                    <option value="{{ $choice }}" @selected($country === $choice)>{{ $choice }}</option>
                @endforeach
            </select>
        </div>
        <button class="directory-button directory-button-primary" type="submit">Rechercher</button>
        <div class="directory-field directory-category">
            <label for="proCategoryFilter">Catégorie de service</label>
            <select id="proCategoryFilter" name="category">
                <option value="">Toutes les catégories</option>
                @foreach($categories as $catName => $catData)
                    <option value="{{ $catName }}" @selected($category === $catName)>{{ $catName }}</option>
                @endforeach
            </select>
        </div>
        @if($subcategory)
            <div class="directory-field directory-category">
                <label for="directorySpecialty">Spécialité</label>
                <select name="subcategory" id="directorySpecialty"><option value="{{ $subcategory }}">{{ $subcategory }}</option><option value="">Toutes les spécialités</option></select>
            </div>
        @endif
        <p id="directoryLocationHelp" class="directory-help">Le lieu correspond à la ville renseignée sur le profil. La disponibilité et le déplacement sont à confirmer avec le prestataire.</p>
    </form>
    <div class="directory-results">
        <div><h2 role="status">{{ $professionals->total() }} profil{{ $professionals->total() > 1 ? 's' : '' }} trouvé{{ $professionals->total() > 1 ? 's' : '' }}</h2>
        <p>Avec ou sans abonnement · Classés par nom</p>
        @if($search !== '' || $city !== '' || $country !== '' || $category || $subcategory)
            <p class="directory-applied" aria-label="Filtres appliqués">
                <strong>Filtres appliqués :</strong>
                @foreach(collect([$search, $city, $country, $subcategory ?: $category])->filter()->unique() as $activeFilter)
                    <span>{{ $activeFilter }}</span>
                @endforeach
            </p>
        @endif
        </div>
        @if($search || $city || $country || $category || $subcategory)
            <a href="{{ route('feed.professionals') }}">Effacer les filtres</a>
        @endif
    </div>
    <div class="directory-grid" id="prosGrid">
        @forelse($professionals as $pro)
            @include('profile.partials.directory-card', ['pro' => $pro])
        @empty
            <section class="directory-empty">
                <h2>Aucun profil ne correspond à ces critères</h2>
                <p>Essayez un autre métier ou élargissez vous-même la recherche. Nous n’ajoutons pas de résultats hors de votre sélection.</p>
                <a class="directory-button directory-button-primary" href="{{ route('feed.professionals', ['category' => $category, 'subcategory' => $subcategory, 'q' => $search]) }}">Rechercher sans lieu</a>
                <a class="directory-button" href="{{ route('demand.create') }}">Publier mon besoin</a>
            </section>
        @endforelse
    </div>
    @if($professionals->hasPages())
        <nav class="directory-pagination" aria-label="Pagination des prestataires">
            @if($professionals->onFirstPage())
                <span aria-disabled="true">Précédent</span>
            @else
                <a class="directory-button" href="{{ $professionals->previousPageUrl() }}" rel="prev">Précédent</a>
            @endif
            <span>Page {{ $professionals->currentPage() }} sur {{ $professionals->lastPage() }}</span>
            @if($professionals->hasMorePages())
                <a class="directory-button" href="{{ $professionals->nextPageUrl() }}" rel="next">Suivant</a>
            @else
                <span aria-disabled="true">Suivant</span>
            @endif
        </nav>
    @endif
</div>
@endsection

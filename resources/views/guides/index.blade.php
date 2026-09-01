@extends('layouts.app')

@section('title', 'Conseils pratiques - Prokejem')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/guides.css') }}?v={{ @filemtime(public_path('css/guides.css')) ?: 1 }}">
@endpush

@section('content')
<main class="guide-page">
    <div class="guide-shell">
        <nav class="guide-breadcrumb" aria-label="Fil d’Ariane">
            <a href="{{ url('/') }}">Accueil</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span>Conseils</span>
        </nav>

        <header class="guide-hero">
            <div class="guide-hero__content">
                <span class="guide-kicker"><i class="fas fa-compass"></i> Le guide de la communauté</span>
                <h1>Les bons repères, au bon moment</h1>
                <p>Des conseils courts et concrets pour publier, choisir, proposer et travailler dans un cadre clair. Aucun classement payant ni chiffre promotionnel dans ces guides.</p>
            </div>
            <div class="guide-hero__mark" aria-hidden="true"><i class="fas fa-lightbulb"></i></div>
        </header>

        <section class="guide-audience" aria-labelledby="clientGuidesTitle">
            <div class="guide-section-head">
                <div>
                    <span class="guide-section-label">Vous cherchez un service</span>
                    <h2 id="clientGuidesTitle">Parcours client</h2>
                </div>
                <a href="{{ route('demand.create') }}">Publier une demande <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="guide-grid">
                @foreach($clientGuides as $guide)
                    @include('guides.partials.card', ['guide' => $guide])
                @endforeach
            </div>
        </section>

        <section class="guide-audience" aria-labelledby="providerGuidesTitle">
            <div class="guide-section-head">
                <div>
                    <span class="guide-section-label">Vous proposez vos compétences</span>
                    <h2 id="providerGuidesTitle">Parcours prestataire</h2>
                </div>
                @auth
                    <a href="{{ route('pro.opportunities') }}">Mes opportunités <i class="fas fa-arrow-right"></i></a>
                @else
                    <a href="{{ route('register', ['account_type' => 'professionnel']) }}">Devenir prestataire <i class="fas fa-arrow-right"></i></a>
                @endauth
            </div>
            <div class="guide-grid">
                @foreach($providerGuides as $guide)
                    @include('guides.partials.card', ['guide' => $guide])
                @endforeach
            </div>
        </section>

        <section class="guide-principles" aria-labelledby="guidePrinciplesTitle">
            <div>
                <span class="guide-section-label">Notre ligne éditoriale</span>
                <h2 id="guidePrinciplesTitle">Des conseils factuels, pas des promesses</h2>
                <p>Les guides expliquent le fonctionnement de la plateforme et les précautions utiles. Ils ne remplacent pas les obligations légales propres à chaque métier.</p>
            </div>
            <div class="guide-principles__links">
                <a href="{{ route('legal.platform-rules') }}"><i class="fas fa-scale-balanced"></i> Règles de la marketplace</a>
                <a href="{{ route('contact.index') }}"><i class="fas fa-headset"></i> Contacter Prokejem</a>
            </div>
        </section>
    </div>
</main>
@endsection

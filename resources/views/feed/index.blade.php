{{--
    ==========================================================================
    PROKEJEM — Page d'accueil (feed)
    ==========================================================================
    Le feed est un poste de pilotage, pas un catalogue : il repond a
    « ou en suis-je et que dois-je faire maintenant ? », pas a
    « qu'y a-t-il sur ce site ? ».

    Hierarchie : mode de consultation, situation et action suivante,
    intention, profils pertinents (client), six annonces, rappel de profil,
    conseils et reassurance. L'accueil sans demande integre son propre champ
    de publication pour ne pas dupliquer cette entree.

    La recherche, les filtres et la carte geographique vivent sur /annonces.
    La publication passe uniquement par /demande et /ads/create.

    Toutes les donnees sont preparees par FeedController@index : cette vue
    ne declenche aucune requete.
--}}
@extends('layouts.app')

@section('title', 'Accueil - Prokejem')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="{{ asset('css/feed.css') }}?v={{ @filemtime(public_path('css/feed.css')) ?: 1 }}">
@endpush

@section('content')
<div class="pk-feed" id="pkFeed">
    <div class="pk-body">

        <div class="pk-main">

            @if($pkCanProvide)
                <nav class="pk-mode" aria-label="Utilisation de l’accueil">
                    <a href="{{ route('feed', ['mode' => 'client']) }}" @if($pkRole === 'client') aria-current="page" @endif>
                        <i class="fas fa-search" aria-hidden="true"></i> Je cherche un service
                    </a>
                    <a href="{{ route('feed', ['mode' => 'provider']) }}" @if($pkRole === 'provider') aria-current="page" @endif>
                        <i class="fas fa-briefcase" aria-hidden="true"></i> Mon activité prestataire
                    </a>
                </nav>
            @endif

            {{-- Zone 2 · carte d'etat --}}
            @include('feed.partials.state-card')

            @if($pkRole === 'provider' || $activeClientRequest || $pkActiveOrder)
                @include('feed.partials.intent-bar')
            @endif

            {{-- Pour un client, les prestataires passent avant le catalogue. --}}
            @if($pkRole === 'client')
                @include('feed.partials.providers')
            @endif

            {{-- Zone 4 · le flux adapte au role --}}
            <section aria-labelledby="pkFeedTitle">
                <div class="pk-sechead">
                    <div>
                        <h2 id="pkFeedTitle">{{ $pkFeedTitle }}</h2>
                        <p class="pk-sechead__sub">
                            <span class="pk-live" aria-hidden="true"></span>
                            <span>
                                @if($geoCity)
                                    @if($pkRole === 'provider')
                                        {{ $geoCity }} et alentours · sélection selon vos métiers
                                    @elseif($useNearbyScope && ! $geoFallbackUsed)
                                        {{ $geoCity }} et alentours
                                    @else
                                        Toutes les zones · explorez les services disponibles
                                    @endif
                                @else
                                    Une sélection récente, mise à jour en continu
                                @endif
                            </span>
                        </p>
                    </div>
                    <a href="{{ $pkBrowseUrl }}" class="pk-sechead__more">
                        Tout voir <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="pk-feed-list" id="pkFeedList">
                    @forelse($pkFeedAds as $ad)
                        @include('feed.partials.ad-card', ['ad' => $ad, 'pkSaved' => $pkSavedAdIds])
                    @empty
                        <div class="pk-empty">
                            <i class="far fa-compass"></i>
                            <h3>Aucune annonce à afficher pour le moment</h3>
                            <p>
                                @if($pkRole === 'provider')
                                    Précisez vos catégories d’intervention pour recevoir les demandes qui vous correspondent.
                                @else
                                    Soyez le premier : publiez votre demande, les prestataires de votre zone la recevront.
                                @endif
                            </p>
                            <a href="{{ $pkRole === 'provider' ? route('pro.onboarding') : route('demand.create') }}" class="pk-btn">
                                {{ $pkRole === 'provider' ? 'Compléter mon activité' : 'Publier une demande' }}
                            </a>
                        </div>
                    @endforelse
                </div>

                @if($pkFeedAds->isNotEmpty())
                    <a href="{{ $pkBrowseUrl }}" class="pk-seeall">
                        <i class="fas fa-clipboard-list"></i>
                        {{ $pkRole === 'provider'
                            ? 'Voir toutes les demandes, la carte et les filtres'
                            : 'Voir tous les services, la carte et les filtres' }}
                        <i class="fas fa-arrow-right"></i>
                    </a>
                @endif
            </section>

            {{-- Le profil accompagne le parcours, sans repousser les opportunités. --}}
            @include('feed.partials.profile-progress')

            {{-- Zone 6 · conseils pratiques, en fin de parcours --}}
            @include('feed.partials.guides')

            {{-- Zone 7 · reassurance adaptee au role --}}
            @include('feed.partials.trust')

        </div>

        {{-- Rail contextuel — a partir de 1180 px --}}
        @include('feed.partials.rail')

    </div>

    <div class="pk-toast" id="pkToast" role="status" aria-live="polite"></div>
</div>

{{-- La barre d'onglets mobile n'est plus incluse ici : elle est desormais
     rendue par layouts/app.blade.php pour apparaitre sur toutes les pages.
     L'inclure a nouveau ici en afficherait deux. --}}

{{-- Configuration transmise au script : aucune donnee sensible --}}
@php
    $pkFeedConfig = [
        'role' => $pkRole,
        'demandUrl' => route('demand.create'),
        'offerUrl' => route('ads.create'),
        'requestsUrl' => route('ads.index', ['type' => 'demandes']),
        'saveUrl' => url('/ads/:id/toggle-save'),
        'categories' => $pkSearchIndex,
    ];
@endphp
<script type="application/json" id="pkFeedConfig">{!! Illuminate\Support\Js::encode($pkFeedConfig) !!}</script>
@endsection

@push('scripts')
<script src="{{ asset('js/feed.js') }}?v={{ @filemtime(public_path('js/feed.js')) ?: 1 }}" defer></script>
@endpush

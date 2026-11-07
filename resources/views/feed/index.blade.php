{{-- Accueil connecté : découverte au centre, espace personnel et aide dans le rail. --}}
@extends('layouts.app')

@section('title', 'Accueil - Prokejem')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="{{ asset('css/feed.css') }}?v={{ @filemtime(public_path('css/feed.css')) ?: 1 }}">
@endpush

@section('content')
<div class="pk-feed{{ $pkRole === 'client' ? ' pk-feed--client' : '' }}" id="pkFeed">
    <div class="pk-body">

        <div class="pk-welcome">

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

            @if($pkRole === 'client')
                @include('feed.partials.client-welcome')
            @else
                @include('feed.partials.state-card')
            @endif
        </div>

        <div class="pk-main">
            @if($pkRole === 'client')
                <div id="pkClientActivity" data-refresh-url="{{ route('client-activity.refresh') }}" data-revision="{{ $pkClientActivity['revision'] }}">
                    @include('feed.partials.client-activity')
                </div>
                @include('feed.partials.quick-needs')
            @endif

            {{-- Zone 4 · le flux adapte au role --}}
            <section class="pk-listings" id="services" aria-labelledby="pkFeedTitle">
                <div class="pk-sechead">
                    <div>
                        <h2 id="pkFeedTitle">{{ $pkFeedTitle }}</h2>
                        <p class="pk-sechead__sub">
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
                                    Découvrez les services et échangez avec les prestataires
                                @endif
                            </span>
                        </p>
                    </div>
                    <a href="{{ $pkBrowseUrl }}" class="pk-sechead__more">
                        Tout voir <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                @if($pkRole === 'client' && $pkFeedAds->isNotEmpty())
                    <p class="pk-feed-selection"><i class="fas fa-info-circle" aria-hidden="true"></i> Les offres Boost et Premium actives sont prioritaires. Les autres offres complètent la sélection.</p>
                @endif
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

                @if($pkRole === 'client' && $pkFeedPagination?->hasPages())
                    <nav class="pk-feed-pagination" aria-label="Pages des offres de services">
                        @if($pkFeedPagination->previousPageUrl())
                            <a href="{{ $pkFeedPagination->previousPageUrl() }}" rel="prev"><i class="fas fa-chevron-left" aria-hidden="true"></i> Précédent</a>
                        @else
                            <span></span>
                        @endif
                        <span>Page {{ $pkFeedPagination->currentPage() }} sur {{ $pkFeedPagination->lastPage() }}</span>
                        @if($pkFeedPagination->nextPageUrl())
                            <a href="{{ $pkFeedPagination->nextPageUrl() }}" rel="next">Suivant <i class="fas fa-chevron-right" aria-hidden="true"></i></a>
                        @else
                            <span></span>
                        @endif
                    </nav>
                @endif

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


        </div>

        {{-- Après les annonces sur téléphone ; à leur droite sur ordinateur. --}}
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
        'professionalsUrl' => route('feed.professionals', array_filter(['city' => $geoCity, 'country' => $geoCountry])),
        'saveUrl' => url('/ads/:id/toggle-save'),
        'categories' => $pkSearchIndex,
    ];
@endphp
<script type="application/json" id="pkFeedConfig">{!! Illuminate\Support\Js::encode($pkFeedConfig) !!}</script>
@endsection

@push('scripts')
<script src="{{ asset('js/feed.js') }}?v={{ @filemtime(public_path('js/feed.js')) ?: 1 }}" defer></script>
@if($pkRole === 'client')
<script src="{{ asset('js/feed-client-activity.js') }}?v={{ @filemtime(public_path('js/feed-client-activity.js')) ?: 1 }}" defer></script>
@endif
@endpush

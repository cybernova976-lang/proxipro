@php
    $pkFirstName = trim(Str::before(trim(Auth::user()->name ?? 'Utilisateur'), ' ')) ?: 'Utilisateur';
    $pkProviderMode = ($pkRole ?? 'client') === 'provider';
@endphp
<section class="pk-discover" aria-labelledby="pkStateTitle">
    <div class="pk-discover__heading">
        <div>
            <h1 id="pkStateTitle">{{ $pkProviderMode ? 'Votre activité prestataire' : 'Bonjour '.$pkFirstName }}</h1>
            <p>{{ $pkProviderMode
                ? 'Explorez les demandes et retrouvez vos missions en cours.'
                : 'Un service à trouver, un projet à faire avancer.' }}</p>
        </div>
        <a class="pk-btn" href="{{ $pkProviderMode ? route('ads.create', ['type' => 'offre']) : route('demand.create') }}">
            <i class="fas fa-plus" aria-hidden="true"></i>
            {{ $pkProviderMode ? 'Proposer un service' : 'Publier une demande' }}
        </a>
    </div>
    <div class="pk-discover__searchline">
        @include('feed.partials.intent-bar')
        @if($geoCity)
            <a class="pk-search-area" href="{{ $pkProviderMode ? $pkBrowseUrl : route('feed.professionals', array_filter(['city' => $geoCity, 'country' => $geoCountry])) }}">
                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                <span>{{ $geoCity }}@if($geoCountry)<small>{{ $geoCountry }}</small>@endif</span>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </a>
        @endif
    </div>
    @if(!$pkProviderMode && !empty($pkQuickCategories))
        <nav class="pk-quickcats" aria-label="Rechercher un prestataire par catégorie">
            @foreach($pkQuickCategories as $pkCatName => $pkCatData)
                <a class="pk-quickcat" href="{{ route('feed.professionals', array_filter(['category' => $pkCatName, 'city' => $geoCity, 'country' => $geoCountry])) }}">
                    <i class="{{ $pkCatData['icon'] ?? 'fas fa-tools' }}" aria-hidden="true"></i>
                    <span>{{ $pkCatName }}</span>
                </a>
            @endforeach
            <a class="pk-quickcat pk-quickcat--all" href="{{ route('feed.professionals') }}">Tous les métiers <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </nav>
    @endif
</section>

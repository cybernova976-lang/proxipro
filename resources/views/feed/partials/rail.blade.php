@php($pkIsProvider = ($pkRole ?? 'client') === 'provider')
<aside class="pk-rail" aria-label="Votre espace et vos repères">
    @if($pkIsProvider)
    <section class="pk-rcard pk-personal" aria-labelledby="pkPersonalTitle">
        <h2 id="pkPersonalTitle">{{ $pkIsProvider ? 'Mon activité' : 'Mon espace' }}</h2>
        <nav class="pk-shortcuts" aria-label="Raccourcis personnels">
            <a href="{{ $pkIsProvider ? route('pro.opportunities') : route('home') }}">
                <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                <span>{{ $pkIsProvider ? 'Mes propositions et missions' : 'Mon suivi' }}<small>{{ $pkIsProvider ? 'Retrouver mes échanges en cours' : 'Demandes, propositions et commandes' }}</small></span>
                <i class="fas fa-chevron-right pk-shortcuts__arrow" aria-hidden="true"></i>
            </a>
            <a href="{{ route('messages.index') }}">
                <i class="far fa-comments" aria-hidden="true"></i><span>Messages</span>
                @if(($pkUnreadMessages ?? 0) > 0)<span class="n">{{ $pkUnreadMessages }}</span>@endif
                <i class="fas fa-chevron-right pk-shortcuts__arrow" aria-hidden="true"></i>
            </a>
            <a href="{{ route('saved-ads.index') }}"><i class="far fa-bookmark" aria-hidden="true"></i><span>Favoris</span><i class="fas fa-chevron-right pk-shortcuts__arrow" aria-hidden="true"></i></a>
            @if($pkIsProvider)
                <a href="{{ route('pro.dashboard') }}"><i class="fas fa-chart-line" aria-hidden="true"></i><span>Mon espace Pro<small>Clients, devis et factures</small></span><i class="fas fa-chevron-right pk-shortcuts__arrow" aria-hidden="true"></i></a>
            @endif
        </nav>
    </section>
    @endif
    @if(!$pkIsProvider)
        @include('feed.partials.providers')
        @include('feed.partials.employment-card')
    @else
        <section class="pk-rcard pk-matching" aria-labelledby="pkMatchingTitle">
            <span class="pk-rcard__lab">Vos opportunités</span>
            <h2 id="pkMatchingTitle">{{ $pkMatchingCount }} demande{{ $pkMatchingCount > 1 ? 's' : '' }} compatible{{ $pkMatchingCount > 1 ? 's' : '' }}</h2>
            <p>Une sélection selon vos métiers et votre zone d’intervention.</p>
            <a class="pk-btn-soft" href="{{ $pkBrowseUrl }}">Explorer les demandes <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </section>
    @endif
    @if(! $pkIsProvider)
        @include('feed.partials.landmarks')
    @else
    @include('feed.partials.profile-progress')
    <details class="pk-help">
        <summary><i class="far fa-question-circle" aria-hidden="true"></i> Conseils et fonctionnement <i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
        <div class="pk-help__content">
            @include('feed.partials.guides')
            @include('feed.partials.trust')
        </div>
    </details>
    @endif
</aside>

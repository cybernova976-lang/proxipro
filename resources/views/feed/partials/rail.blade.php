{{--
    Rail contextuel — visible a partir de 1180 px.
    Rien de permanent : chaque carte n'apparait que si elle a quelque chose a dire.
--}}
@php
    $pkIsProvider = ($pkRole ?? 'client') === 'provider';
    $pkOpenRequests = collect($priorityProviderRequests ?? []);
@endphp

<aside class="pk-rail" aria-label="Informations complémentaires">

    {{-- L'action client est deja presente dans le suivi actualise du feed. --}}
    @if($pkIsProvider)
    <div class="pk-rcard">
        <span class="pk-rcard__lab">Votre prochaine étape</span>
        @if($pkIsProvider && $pkOpenRequests->count() > 0)
            <h2>{{ $pkOpenRequests->count() }} demande{{ $pkOpenRequests->count() > 1 ? 's' : '' }} sans réponse</h2>
            <p>Ces demandes correspondent à votre activité et n’ont encore reçu aucune proposition.</p>
            <a href="#pkFeedList" class="pk-btn-soft">Les voir <i class="fas fa-arrow-down"></i></a>
        @elseif($pkIsProvider)
            <h2>Développez votre visibilité</h2>
            <p>Un profil complet et vérifié apparaît plus souvent dans les résultats de recherche.</p>
            <a href="{{ route('pro.dashboard') }}" class="pk-btn-soft">Mon espace Pro <i class="fas fa-arrow-right"></i></a>
        @endif
    </div>
    @endif

    {{-- Raccourcis --}}
    <div class="pk-rcard">
        <span class="pk-rcard__lab">Raccourcis</span>
        <nav class="pk-shortcuts">
            <a href="{{ $pkIsProvider ? route('ads.myads') : route('demands.tracking') }}"><i class="fas fa-clipboard-list"></i> {{ $pkIsProvider ? 'Mes annonces' : 'Suivi de mes demandes' }}</a>
            <a href="{{ route('messages.index') }}">
                <i class="far fa-comments"></i> Messages
                @if(($pkUnreadMessages ?? 0) > 0)<span class="n">{{ $pkUnreadMessages }}</span>@endif
            </a>
            <a href="{{ route('saved-ads.index') }}"><i class="far fa-bookmark"></i> Favoris</a>
            <a href="{{ route('service-orders.index') }}"><i class="fas fa-shield-alt"></i> Mes commandes</a>
            @if($pkIsProvider)
                <a href="{{ route('pro.dashboard') }}"><i class="fas fa-chart-line"></i> Tableau de bord Pro</a>
                <a href="{{ route('quote-tool.landing') }}"><i class="fas fa-file-invoice"></i> Devis &amp; factures</a>
            @endif
        </nav>
    </div>

    {{--
        Abonnement — jamais un encart permanent.
        Il n'apparait que pour un prestataire non abonne, et seulement adosse
        a un chiffre reel : le nombre de vues de son profil ce mois-ci.
    --}}
    @if($pkShowUpsell)
        <div class="pk-rcard pk-upsell">
            <span class="pk-rcard__lab">Votre visibilité</span>
            @if($pkProfileViews > 0)
                {{-- Vues reelles du mois : dedoublonnees, robots exclus. --}}
                <div class="pk-upsell__figure">
                    {{ $pkProfileViews }} vue{{ $pkProfileViews > 1 ? 's' : '' }}
                </div>
                <p>
                    Votre profil a été consulté {{ $pkProfileViews }} fois ce mois-ci.
                    Découvrez les outils disponibles pour développer votre activité.
                </p>
            @else
                {{-- Pas encore de vue ce mois-ci : on montre l'autre chiffre vrai. --}}
                <div class="pk-upsell__figure">
                    {{ $pkMatchingCount }} demande{{ $pkMatchingCount > 1 ? 's' : '' }}
                </div>
                <p>
                    {{ $pkMatchingCount }} demande{{ $pkMatchingCount > 1 ? 's' : '' }}
                    correspond{{ $pkMatchingCount > 1 ? 'ent' : '' }} à votre métier en ce moment.
                    Découvrez les outils disponibles pour développer votre activité.
                </p>
            @endif
            <a href="{{ route('pro.subscription') }}" class="pk-btn-white">
                Découvrir Prokejem Pro <i class="fas fa-arrow-right"></i>
            </a>
            <span class="pk-upsell__fine">Sans engagement · résiliable à tout moment</span>
        </div>
    @endif

    {{-- Activite recente — uniquement des chiffres mesures, jamais estimes --}}
    @if(! empty($pkActivity))
        <div class="pk-rcard">
            <span class="pk-rcard__lab">Activité récente</span>
            <ul class="pk-pulse">
                @foreach($pkActivity as $pkLine)
                    <li><i class="{{ $pkLine['icon'] }}"></i><span>{!! $pkLine['html'] !!}</span></li>
                @endforeach
            </ul>
            <p class="pk-pulse__note">Chiffres calculés sur les données réelles de la plateforme.</p>
        </div>
    @endif

</aside>

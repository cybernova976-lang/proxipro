{{--
    Rail contextuel — visible a partir de 1180 px.
    Rien de permanent : chaque carte n'apparait que si elle a quelque chose a dire.
--}}
@php
    $pkIsProvider = ($pkRole ?? 'client') === 'provider';
    $pkMyRequest = $activeClientRequest ?? null;
    $pkProposals = (int) ($pkMyRequest->service_proposals_count ?? 0);
    $pkNeedsAttention = (bool) ($activeClientRequestNeedsAttention ?? false);
    $pkOpenRequests = collect($priorityProviderRequests ?? []);
@endphp

<aside class="pk-rail" aria-label="Informations complémentaires">

    {{-- Prochaine etape --}}
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
        @elseif($pkMyRequest && $pkProposals > 0)
            <h2>{{ $pkProposals }} réponse{{ $pkProposals > 1 ? 's' : '' }} vous {{ $pkProposals > 1 ? 'attendent' : 'attend' }}</h2>
            <p>Comparez les profils, les prix et les délais avant de choisir votre prestataire.</p>
            <a href="{{ route('proposals.compare', $pkMyRequest) }}" class="pk-btn-soft">Comparer <i class="fas fa-arrow-right"></i></a>
        @elseif($pkMyRequest && $pkNeedsAttention)
            <h2>Votre demande peut être précisée</h2>
            <p>Elle est toujours sans réponse. Un détail, une photo ou un créneau plus souple peut faciliter la première proposition.</p>
            <a href="{{ route('ads.edit', $pkMyRequest) }}" class="pk-btn-soft">Améliorer la demande <i class="fas fa-arrow-right"></i></a>
        @else
            <h2>Publiez en quelques minutes</h2>
            <p>Un parcours guidé vous pose uniquement les questions utiles : service, lieu, créneau, budget. Vous pouvez y ajouter des photos.</p>
            <a href="{{ route('demand.create') }}" class="pk-btn-soft">Commencer <i class="fas fa-arrow-right"></i></a>
        @endif
    </div>

    {{-- Raccourcis --}}
    <div class="pk-rcard">
        <span class="pk-rcard__lab">Raccourcis</span>
        <nav class="pk-shortcuts">
            <a href="{{ route('ads.myads') }}"><i class="fas fa-clipboard-list"></i> Mes annonces</a>
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
                    Les comptes Pro apparaissent en tête des résultats et sont contactés en priorité.
                </p>
            @else
                {{-- Pas encore de vue ce mois-ci : on montre l'autre chiffre vrai. --}}
                <div class="pk-upsell__figure">
                    {{ $pkMatchingCount }} demande{{ $pkMatchingCount > 1 ? 's' : '' }}
                </div>
                <p>
                    {{ $pkMatchingCount }} demande{{ $pkMatchingCount > 1 ? 's' : '' }}
                    correspond{{ $pkMatchingCount > 1 ? 'ent' : '' }} à votre métier en ce moment.
                    Les comptes Pro apparaissent en tête des résultats et sont contactés en priorité.
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

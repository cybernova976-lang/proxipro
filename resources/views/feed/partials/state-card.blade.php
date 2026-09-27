{{--
    Zone 2 · carte d'etat — la situation de l'utilisateur avant l'inventaire.

    Selon le mode choisi et la situation reelle :
      · prestataire                      → volume d'opportunites et visibilite
      · client avec une demande en cours → ou en est cette demande
      · client avec une mission en cours → suivi de la commande
      · client sans demande              → invitation a publier + acces rapides
--}}
@php
    $pkFirstName = trim(Str::before(trim(Auth::user()->name ?? 'Utilisateur'), ' ')) ?: 'Utilisateur';
    $pkOpenRequests = collect($priorityProviderRequests ?? []);
    $pkMyRequest = $activeClientRequest ?? null;
    $pkProposals = (int) ($pkMyRequest->pending_proposals_count ?? $pkMyRequest->service_proposals_count ?? 0);
    $pkNeedsAttention = (bool) ($activeClientRequestNeedsAttention ?? false);
@endphp

@if(($pkRole ?? 'client') === 'provider')

    {{-- ============ Prestataire ============ --}}
    <section class="pk-state" aria-labelledby="pkStateTitle">
        <span class="pk-state__eyebrow"><i class="fas fa-bolt"></i> Votre activité</span>

        @if($pkMatchingCount > 0)
            <h1 id="pkStateTitle">
                {{ $pkMatchingCount }} demande{{ $pkMatchingCount > 1 ? 's' : '' }}
                correspond{{ $pkMatchingCount > 1 ? 'ent' : '' }} à votre métier
            </h1>
            <p>
                @if($pkOpenRequests->count() > 0)
                    <strong>{{ $pkOpenRequests->count() }}</strong> n’{{ $pkOpenRequests->count() > 1 ? 'ont' : 'a' }}
                    encore reçu aucune réponse. Consultez leur besoin et proposez votre intervention.
                @else
                    Consultez le flux ci-dessous et proposez vos services aux clients qui vous correspondent.
                @endif
            </p>
        @else
            <h1 id="pkStateTitle">Aucune demande compatible pour le moment</h1>
            <p>
                Précisez vos catégories d’intervention et votre zone : nous vous montrerons uniquement
                les demandes qui correspondent réellement à votre activité.
            </p>
            <div class="pk-state__actions">
                <a href="{{ route('pro.onboarding') }}" class="pk-btn-white">
                    Compléter mon activité <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        @endif

        {{--
            Trois chiffres, tous mesures : aucun n'est estime.
            Les vues de profil sont dedoublonnees par visiteur et par jour,
            et n'incluent ni les robots ni les visites du proprietaire.
        --}}
        <div class="pk-state__stats">
            <div>
                <b>{{ $pkMatchingCount }}</b>
                <span>demande{{ $pkMatchingCount > 1 ? 's' : '' }} compatible{{ $pkMatchingCount > 1 ? 's' : '' }}</span>
            </div>
            <div>
                <b>{{ $pkProfileViews }}</b>
                <span>vue{{ $pkProfileViews > 1 ? 's' : '' }} de votre profil ce mois-ci</span>
            </div>
            <div>
                <b>{{ (int) ($userRadius ?? 50) }}&nbsp;km</b>
                <span>{{ $geoCity ?: 'votre zone d’intervention' }}</span>
            </div>
        </div>
    </section>

@elseif(($pkClientActivity['total'] ?? 0) > 0)
    <div id="pkClientActivity" data-refresh-url="{{ route('client-activity.refresh') }}" data-revision="{{ $pkClientActivity['revision'] }}">
        @include('feed.partials.client-activity')
    </div>

@else

    {{-- ============ Client sans demande ============ --}}
    <section class="pk-state pk-state--welcome" aria-labelledby="pkStateTitle">
        <span class="pk-state__eyebrow">Bonjour {{ $pkFirstName }}</span>
        <h1 id="pkStateTitle">De quoi avez-vous besoin&nbsp;?</h1>
        <p>Décrivez votre besoin, puis comparez les propositions. La publication est gratuite.</p>
        @include('feed.partials.intent-bar')

        @if(! empty($pkQuickCategories))
            <div class="pk-quickcats">
                @foreach($pkQuickCategories as $pkCatName => $pkCatData)
                    <button type="button" class="pk-quickcat" data-pk-category="{{ $pkCatName }}">
                        <i class="{{ $pkCatData['icon'] ?? 'fas fa-tools' }}" aria-hidden="true"></i>
                        <b>{{ Str::limit($pkCatName, 26) }}</b>
                    </button>
                @endforeach
            </div>
        @else
            <div class="pk-state__actions">
                <a href="{{ route('demand.create') }}" class="pk-btn-white">
                    Publier ma demande <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        @endif
    </section>

@endif

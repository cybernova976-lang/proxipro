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

@elseif(($pkActiveOrder ?? null) && (! $pkMyRequest || in_array($pkActiveOrder->status, ['awaiting_payment', 'disputed'], true)))

    <section class="pk-state pk-state--active-request" aria-labelledby="pkStateTitle">
        <div class="pk-state__request-head">
            <span class="pk-state__eyebrow"><i class="fas fa-briefcase" aria-hidden="true"></i> Votre mission</span>
            <span class="pk-state__status">{{ $pkActiveOrder->status_label }}</span>
        </div>
        <h1 id="pkStateTitle">{{ $pkActiveOrder->ad?->title ?: 'Votre prestation en cours' }}</h1>
        <p>{{ match ($pkActiveOrder->status) {
            'awaiting_payment' => 'Votre proposition est acceptée. Consultez la commande pour préparer le paiement sécurisé.',
            'disputed' => 'Un litige est en cours. Retrouvez son suivi et les échanges depuis votre commande.',
            default => 'Retrouvez les étapes de votre mission et validez sa réalisation une fois la prestation terminée.',
        } }}</p>
        <div class="pk-state__actions">
            <a href="{{ route('service-orders.index') }}" class="pk-btn-white">Voir ma commande <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            <a href="{{ route('demands.tracking') }}" class="pk-state__secondary">Toutes les étapes</a>
        </div>
    </section>

@elseif($pkMyRequest)

    {{-- ============ Client avec une demande en cours ============ --}}
    <section class="pk-state pk-state--active-request" aria-labelledby="pkStateTitle">
        <div class="pk-state__request-head">
            <span class="pk-state__eyebrow"><i class="far fa-clock"></i> Votre demande en cours</span>
            <span class="pk-state__status{{ $pkProposals > 0 ? ' pk-state__status--answered' : ($pkNeedsAttention ? ' pk-state__status--attention' : '') }}">
                <span class="pk-state__status-dot" aria-hidden="true"></span>
                {{ $pkProposals > 0
                    ? $pkProposals . ' réponse' . ($pkProposals > 1 ? 's' : '') . ' reçue' . ($pkProposals > 1 ? 's' : '')
                    : ($pkNeedsAttention ? 'Toujours aucune réponse' : 'Demande publiée') }}
            </span>
        </div>
        <h1 id="pkStateTitle">{{ $pkMyRequest->title }}</h1>
        <p>
            @if($pkProposals > 0)
                <strong>{{ $pkProposals }} proposition{{ $pkProposals > 1 ? 's' : '' }} à examiner.</strong>
                Comparez les profils, les prix et les délais.
            @elseif($pkNeedsAttention)
                Ajoutez une précision, une photo ou un créneau plus souple pour faciliter les réponses.
            @else
                Votre demande est visible. Retrouvez son avancement et les propositions reçues.
            @endif
        </p>
        <div class="pk-state__actions">
            <a href="{{ $pkNeedsAttention
                ? route('ads.edit', $pkMyRequest)
                : ($pkProposals > 0 ? route('proposals.compare', $pkMyRequest) : route('demands.tracking').'#request-'.$pkMyRequest->id) }}" class="pk-btn-white">
                {{ $pkProposals > 0
                    ? 'Comparer les propositions'
                    : ($pkNeedsAttention ? 'Améliorer ma demande' : 'Suivre ma demande') }}
                <i class="fas fa-arrow-right"></i>
            </a>
            <a href="{{ route('demands.tracking') }}" class="pk-state__secondary">
                <i class="fas fa-route"></i>
                {{ ($pkActiveRequestCount ?? 1) > 1 ? 'Mes '.$pkActiveRequestCount.' demandes en cours' : 'Toutes les étapes' }}
            </a>
        </div>
    </section>

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

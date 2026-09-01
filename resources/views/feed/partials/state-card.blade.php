{{--
    Zone 2 · carte d'etat — la situation de l'utilisateur avant l'inventaire.

    Trois cas, determines par les donnees et jamais par un onglet clique :
      · prestataire                      → volume d'opportunites et visibilite
      · client avec une demande en cours → ou en est cette demande
      · client sans demande              → invitation a publier + acces rapides
--}}
@php
    $pkFirstName = trim(Str::before(trim(Auth::user()->name ?? 'Utilisateur'), ' ')) ?: 'Utilisateur';
    $pkOpenRequests = collect($priorityProviderRequests ?? []);
    $pkMyRequest = $activeClientRequest ?? null;
    $pkProposals = (int) ($pkMyRequest->service_proposals_count ?? 0);
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
                    encore reçu aucune réponse. Répondre en premier augmente nettement vos chances d’être choisi.
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
        <h1 id="pkStateTitle">{{ Str::limit($pkMyRequest->title, 72) }}</h1>
        <p>
            @if($pkProposals > 0)
                <strong>{{ $pkProposals }} prestataire{{ $pkProposals > 1 ? 's' : '' }}
                vous {{ $pkProposals > 1 ? 'ont' : 'a' }} répondu.</strong>
                Comparez les profils et les prix, puis choisissez celui qui vous convient.
                Le paiement n’est débloqué qu’après votre validation.
            @elseif($pkNeedsAttention)
                <strong>Votre demande est visible mais n’a pas encore reçu de proposition.</strong>
                Ajoutez une précision, une photo ou un créneau plus souple pour aider les prestataires à se décider.
            @else
                Votre demande est publiée. Les prestataires de votre zone la reçoivent :
                vous serez prévenu dès la première réponse.
            @endif
        </p>
        <div class="pk-state__actions">
            <a href="{{ $pkNeedsAttention ? route('ads.edit', $pkMyRequest) : route('ads.show', $pkMyRequest) }}" class="pk-btn-white">
                {{ $pkProposals > 0
                    ? 'Voir les ' . $pkProposals . ' réponse' . ($pkProposals > 1 ? 's' : '')
                    : ($pkNeedsAttention ? 'Améliorer ma demande' : 'Suivre ma demande') }}
                <i class="fas fa-arrow-right"></i>
            </a>
            <a href="{{ $pkNeedsAttention ? route('ads.index', ['type' => 'offres']) : route('demand.create') }}" class="pk-btn-outline-light">
                <i class="fas fa-{{ $pkNeedsAttention ? 'users' : 'plus' }}"></i>
                {{ $pkNeedsAttention ? 'Consulter les prestataires' : 'Publier un autre besoin' }}
            </a>
        </div>
    </section>

@else

    {{-- ============ Client sans demande ============ --}}
    <section class="pk-state" aria-labelledby="pkStateTitle">
        <span class="pk-state__eyebrow"><i class="fas fa-hand-sparkles"></i> Bienvenue {{ $pkFirstName }}</span>
        <h1 id="pkStateTitle">De quel service avez-vous besoin ?</h1>
        <p>
            Décrivez votre besoin en quelques minutes. C’est gratuit, et les prestataires
            disponibles autour de vous vous répondent directement.
        </p>

        @if(! empty($pkQuickCategories))
            <div class="pk-quickcats">
                @foreach($pkQuickCategories as $pkCatName => $pkCatData)
                    <button type="button" class="pk-quickcat" data-pk-category="{{ $pkCatName }}">
                        <i class="{{ $pkCatData['icon'] ?? 'fas fa-tools' }}" aria-hidden="true"></i>
                        <b>{{ Str::limit($pkCatName, 26) }}</b>
                        @if((int) ($pkCatData['total'] ?? 0) > 0)
                            <span>{{ (int) $pkCatData['total'] }} annonce{{ (int) $pkCatData['total'] > 1 ? 's' : '' }}</span>
                        @else
                            <span>Publier une demande</span>
                        @endif
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

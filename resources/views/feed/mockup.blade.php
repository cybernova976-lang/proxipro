@extends('layouts.app')

@section('title', 'Nouveau feed - Prokejem')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/feed-mockup.css') }}">
@endpush

@php
    $mockUser = Auth::user();
    $mockPriorityRequests = collect($priorityProviderRequests ?? [])->values();
    $mockPriorityRequestIds = $mockPriorityRequests->pluck('id')->map(fn ($id) => (int) $id);
    $mockRequests = $mockPriorityRequests
        ->concat(collect($homePersonalRequests ?? [])->reject(fn ($ad) => $mockPriorityRequestIds->contains((int) $ad->id)))
        ->take(12)
        ->values();
    $mockOffers = collect($homeProfessionalOffers ?? [])->take(6)->values();
    $mockProviders = collect($homeProfessionalProfiles ?? [])->take(8)->values();
    $mockCategories = collect($missionCategories ?? [])->take(6);
    $mockIsProvider = $mockUser && ($mockUser->isProfessionnel() || $mockUser->isServiceProvider());
    $mockInitialMode = request('mode');
    if (!in_array($mockInitialMode, ['client', 'provider'], true)) {
        $mockInitialMode = $mockIsProvider ? 'provider' : 'client';
    }
    $mockFirstName = trim(explode(' ', trim($mockUser->name ?? 'Utilisateur'))[0] ?? 'Utilisateur');
    $mockMyRequest = $activeClientRequest ?? null;
    $mockProfileCompletion = max(0, min(100, (int) ($proProfileCompletion ?? 0)));
    $mockPrimarySuggestion = collect($proSuggestions ?? [])->first();
    if (!$mockPrimarySuggestion && $mockProfileCompletion < 100) {
        $mockPrimarySuggestion = [
            'id' => 'complete_profile',
            'icon' => 'fas fa-user-pen',
            'title' => 'Complétez votre profil',
            'description' => 'Ajoutez les informations qui manquent pour améliorer votre visibilité auprès des clients.',
            'action_label' => 'Continuer',
        ];
    }
    $mockRemainingSuggestions = max(0, count($proSuggestions ?? []) - 1);
    $mockSuggestionUrl = $mockPrimarySuggestion ? match ($mockPrimarySuggestion['id']) {
        'complete_onboarding', 'add_categories', 'add_location' => route('pro.onboarding'),
        'verify_profile' => route('verification.index'),
        'get_subscription' => route('pro.subscription'),
        'create_ad' => route('ads.create'),
        default => route('pro.profile.edit'),
    } : route('pro.profile.edit');
    $mockSavedAdIds = collect($savedHomeAdIds ?? [])->map(fn ($id) => (int) $id);
    $mockIsLocalPreview = (bool) request()->attributes->get('mockup_local_preview');
    $mockFeedHomeRoute = request()->routeIs('feed.mockup.preview')
        ? route('feed.mockup.preview')
        : route('feed.mockup');
@endphp

@section('content')
<div class="mf-page" id="mockFeedApp" data-initial-mode="{{ $mockInitialMode }}">
    @if(request()->attributes->get('mockup_local_preview'))
        <div class="mf-preview-notice" role="status">
            <i class="fas fa-eye"></i>
            <span><strong>Aperçu local sans connexion.</strong> Aucune session n'est créée et les actions d'enregistrement restent simulées.</span>
        </div>
    @endif
    <div class="mf-shell">
        <aside class="mf-navigation" aria-label="Navigation du nouveau feed">
            <div class="mf-user-card">
                <div class="mf-user-avatar" aria-hidden="true">
                    @if($mockUser?->avatar)
                        <img src="{{ storage_url($mockUser->avatar) }}" alt="">
                    @else
                        {{ strtoupper(substr($mockUser->name ?? 'U', 0, 1)) }}
                    @endif
                </div>
                <div>
                    <strong>{{ Str::limit($mockUser->name ?? 'Utilisateur', 24) }}</strong>
                    <span>{{ $mockIsProvider ? 'Espace prestataire' : 'Espace client' }}</span>
                </div>
            </div>

            <nav class="mf-nav-list">
                <a class="is-active" href="{{ $mockFeedHomeRoute }}"><i class="fas fa-home"></i><span>Accueil</span></a>
                <a href="{{ route('ads.index') }}"><i class="fas fa-clipboard-list"></i><span>Mes annonces</span></a>
                <a href="{{ route('messages.index') }}"><i class="fas fa-comments"></i><span>Messages</span></a>
                <a href="{{ route('service-orders.index') }}"><i class="fas fa-shield-alt"></i><span>Commandes</span></a>
                <a href="{{ route('saved-ads.index') }}"><i class="fas fa-bookmark"></i><span>Favoris</span></a>
            </nav>

            @if($mockIsProvider)
                <div class="mf-nav-section">
                    <span>Outils professionnels</span>
                    <a href="{{ route('pro.dashboard') }}"><i class="fas fa-chart-line"></i>Tableau Pro</a>
                    <a href="{{ route('quote-tool.landing') }}"><i class="fas fa-file-invoice"></i>Devis & factures</a>
                </div>
            @endif

            <div class="mf-nav-help">
                <i class="fas fa-circle-question"></i>
                <div><strong>Besoin d'aide ?</strong><a href="{{ route('contact.index') }}">Contacter Prokejem</a></div>
            </div>
        </aside>

        <main class="mf-content">
            <header class="mf-welcome">
                <div>
                    <span class="mf-eyebrow"><i class="fas fa-magic"></i> Nouvelle expérience</span>
                    <h1>Bonjour {{ $mockFirstName }},</h1>
                    <p data-mode-copy="client">Trouvez rapidement le bon prestataire pour votre besoin.</p>
                    <p data-mode-copy="provider" hidden>Repérez les demandes pertinentes et développez votre activité.</p>
                </div>
                <div class="mf-mode-switch" role="group" aria-label="Choisir une vue de démonstration">
                    <button type="button" data-mode-button="client"><i class="fas fa-search"></i> Vue client</button>
                    <button type="button" data-mode-button="provider"><i class="fas fa-briefcase"></i> Vue prestataire</button>
                </div>
            </header>

            <section class="mf-mode-panel" data-mode-panel="client">
                <div class="mf-client-hero">
                    <div class="mf-client-hero-copy">
                        @if($mockMyRequest)
                            <span>Votre demande en cours</span>
                            <h2>{{ Str::limit($mockMyRequest->title, 72) }}</h2>
                            <p>
                                @if((int) ($mockMyRequest->service_proposals_count ?? 0) > 0)
                                    {{ (int) $mockMyRequest->service_proposals_count }} proposition{{ (int) $mockMyRequest->service_proposals_count > 1 ? 's' : '' }} reçue{{ (int) $mockMyRequest->service_proposals_count > 1 ? 's' : '' }}. Comparez-les avant de choisir.
                                @else
                                    Votre demande est publiée. Prokejem recherche maintenant des prestataires compatibles.
                                @endif
                            </p>
                        @else
                            <span>Votre besoin, notre priorité</span>
                            <h2>De quel service avez-vous besoin ?</h2>
                            <p>Décrivez votre demande, choisissez un créneau, puis comparez les prestataires disponibles.</p>
                        @endif
                    </div>
                    @if($mockMyRequest)
                        <div class="mf-client-status-actions">
                            <a href="{{ route('ads.show', $mockMyRequest) }}">Suivre ma demande <i class="fas fa-arrow-right"></i></a>
                            <button type="button" data-open-request><i class="fas fa-plus"></i> Publier un autre besoin</button>
                        </div>
                    @else
                        <button class="mf-request-composer" type="button" data-open-request>
                            <span><i class="fas fa-search"></i> Ex. Réparer une fuite d'eau</span>
                            <strong>Commencer <i class="fas fa-arrow-right"></i></strong>
                        </button>
                    @endif
                    <div class="mf-trust-strip" aria-label="Garanties Prokejem">
                        <span><i class="fas fa-id-card"></i> Profils vérifiés</span>
                        <span><i class="fas fa-lock"></i> Paiement sécurisé</span>
                        <span><i class="fas fa-headset"></i> Assistance en cas de litige</span>
                    </div>
                </div>

                <section class="mf-section" aria-labelledby="mockPopularServices">
                    <div class="mf-section-heading">
                        <div><span>Accès rapide</span><h2 id="mockPopularServices">Services populaires</h2></div>
                        <button type="button" class="mf-text-button" data-open-request>Voir tous les services <i class="fas fa-arrow-right"></i></button>
                    </div>
                    <div class="mf-service-grid">
                        @forelse($mockCategories as $categoryName => $categoryData)
                            <button type="button" class="mf-service-card" data-request-category="{{ $categoryName }}">
                                <i class="{{ $categoryData['icon'] ?? 'fas fa-tools' }}"></i>
                                <strong>{{ Str::limit($categoryName, 26) }}</strong>
                                <span>{{ (int) ($categoryData['total'] ?? 0) }} annonce{{ (int) ($categoryData['total'] ?? 0) > 1 ? 's' : '' }}</span>
                            </button>
                        @empty
                            <div class="mf-empty">Les catégories apparaîtront ici dès qu'elles seront disponibles.</div>
                        @endforelse
                    </div>
                </section>

                <section class="mf-section" aria-labelledby="mockProvidersTitle">
                    <div class="mf-section-heading">
                        <div><span>À proximité</span><h2 id="mockProvidersTitle">Prestataires recommandés</h2></div>
                        <button type="button" class="mf-text-button" data-switch-to-provider>Explorer les profils <i class="fas fa-arrow-right"></i></button>
                    </div>
                    <div class="mf-provider-grid">
                        @forelse($mockProviders->take(4) as $provider)
                            @php
                                $mockProviderRating = (float) ($provider->verified_reviews_avg ?? 0);
                                $mockProviderReviews = (int) ($provider->verified_reviews_count ?? 0);
                                $mockPrimaryService = $provider->relationLoaded('services') ? $provider->services->first() : null;
                                $mockProviderProfession = $provider->profession ?? $mockPrimaryService?->subcategory ?? 'Prestataire de services';
                            @endphp
                            <article class="mf-provider-card">
                                <a class="mf-provider-card-main" href="{{ route('profile.public', $provider->id) }}">
                                    <div class="mf-provider-photo">
                                        @if($provider->avatar)
                                            <img src="{{ storage_url($provider->avatar) }}" alt="Photo de {{ $provider->name }}">
                                        @else
                                            <span>{{ strtoupper(substr($provider->name, 0, 1)) }}</span>
                                        @endif
                                        @if($provider->is_top_provider ?? false)<em><i class="fas fa-star"></i> Top</em>@endif
                                    </div>
                                    <div class="mf-provider-body">
                                        <div class="mf-provider-title"><div><h3>{{ Str::limit($provider->name, 22) }}</h3><p>{{ Str::limit($mockProviderProfession, 34) }}</p></div><i class="fas fa-check-circle" title="Profil vérifié"></i></div>
                                        <div class="mf-provider-facts">
                                            <span class="is-rating"><i class="fas fa-star"></i> {{ $mockProviderReviews > 0 ? number_format($mockProviderRating, 1, ',', '') : 'Nouveau' }}</span>
                                            @if($mockProviderReviews > 0)<span>{{ $mockProviderReviews }} avis</span>@endif
                                            <span>{{ (int) ($provider->ads_count ?? 0) }} service{{ (int) ($provider->ads_count ?? 0) > 1 ? 's' : '' }}</span>
                                        </div>
                                        <p class="mf-provider-bio">{{ Str::limit($provider->bio ?: 'Disponible pour étudier votre demande et vous proposer une solution adaptée.', 92) }}</p>
                                    </div>
                                </a>
                                <div class="mf-provider-actions"><a href="{{ route('profile.public', $provider->id) }}">Voir le profil</a><button type="button" data-request-category="{{ $mockProviderProfession }}">Demander ce service</button></div>
                            </article>
                        @empty
                            <div class="mf-empty">Les profils compatibles avec votre zone apparaîtront ici.</div>
                        @endforelse
                    </div>
                </section>

                @if($mockOffers->isNotEmpty())
                    <section class="mf-section mf-compact-section" aria-labelledby="mockOffersTitle">
                        <div class="mf-section-heading">
                            <div><span>Prêts à réserver</span><h2 id="mockOffersTitle">Services proposés</h2></div>
                        </div>
                        <div class="mf-offer-row">
                            @foreach($mockOffers->take(3) as $offer)
                                <a href="{{ route('ads.show', $offer) }}"><i class="fas fa-briefcase"></i><div><strong>{{ Str::limit($offer->title, 38) }}</strong><span>{{ Str::limit($offer->location ?? 'Service local', 28) }} · {{ $offer->formatted_price }}</span></div><i class="fas fa-chevron-right"></i></a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </section>

            <section class="mf-mode-panel" data-mode-panel="provider" hidden>
                <div class="mf-provider-summary">
                    <div><span>Demandes prioritaires</span><strong>{{ $mockPriorityRequests->count() }}</strong><small>encore sans proposition</small></div>
                    <div><span>Profil complété</span><strong>{{ $mockProfileCompletion }}%</strong><small>améliorez votre visibilité</small></div>
                    <div><span>Zone active</span><strong>{{ (int) ($userRadius ?? 50) }} km</strong><small>{{ $geoCity ?: 'Selon votre profil' }}</small></div>
                </div>

                <section class="mf-mobile-provider-action" aria-label="Votre prochaine action de profil">
                    <i class="{{ $mockPrimarySuggestion['icon'] ?? 'fas fa-check' }}"></i>
                    <div><span>Votre prochaine action</span><strong>{{ $mockPrimarySuggestion['title'] ?? 'Votre profil est prêt' }}</strong><small>{{ $mockPrimarySuggestion['description'] ?? 'Gardez vos informations à jour pour rester visible.' }}</small></div>
                    <a href="{{ $mockSuggestionUrl }}" aria-label="{{ $mockPrimarySuggestion['action_label'] ?? 'Voir mon profil' }}"><i class="fas fa-arrow-right"></i></a>
                </section>

                <div class="mf-provider-hero">
                    <div><span>Flux d'opportunités</span><h2>Trouvez votre prochaine mission</h2><p>Les demandes urgentes, récentes et proches remontent en premier.</p></div>
                    <div class="mf-opportunity-search">
                        <label for="mockOpportunitySearch"><i class="fas fa-search"></i><span class="visually-hidden">Rechercher une demande</span></label>
                        <input id="mockOpportunitySearch" type="search" placeholder="Métier, besoin ou ville…">
                        <select id="mockOpportunityCategory" aria-label="Filtrer par catégorie"><option value="">Toutes les catégories</option>@foreach($mockCategories as $categoryName => $categoryData)<option value="{{ Str::lower($categoryName) }}">{{ $categoryName }}</option>@endforeach</select>
                    </div>
                </div>

                <section class="mf-section mf-opportunity-section" aria-labelledby="mockOpportunitiesTitle">
                    <div class="mf-section-heading">
                        <div><span>Recommandées pour vous</span><h2 id="mockOpportunitiesTitle">Demandes à proximité</h2></div>
                        <div class="mf-filter-pills" role="group" aria-label="Filtres rapides">
                            <button type="button" class="is-active" data-opportunity-filter="all">Toutes</button>
                            <button type="button" data-opportunity-filter="priority">Prioritaires</button>
                            <button type="button" data-opportunity-filter="urgent">Urgentes</button>
                            <button type="button" data-opportunity-filter="recent">Récentes</button>
                        </div>
                    </div>

                    <div class="mf-opportunity-list" id="mockOpportunityList">
                        @forelse($mockRequests as $requestAd)
                            @php
                                $mockRequestUrgent = $requestAd->is_urgent && (!$requestAd->urgent_until || $requestAd->urgent_until > now());
                                $mockRequestRecent = $requestAd->created_at?->greaterThan(now()->subDays(7)) ?? false;
                                $mockRequestPriority = $mockPriorityRequestIds->contains((int) $requestAd->id);
                                $mockRequestAuthor = $requestAd->user;
                                $mockRequestSearch = Str::lower(implode(' ', [$requestAd->title, $requestAd->description, $requestAd->category, $requestAd->location]));
                                $mockRequestSaved = $mockSavedAdIds->contains((int) $requestAd->id);
                            @endphp
                            <article class="mf-opportunity-card{{ $mockRequestPriority ? ' is-priority' : '' }}" data-opportunity-card data-priority="{{ $mockRequestPriority ? '1' : '0' }}" data-urgent="{{ $mockRequestUrgent ? '1' : '0' }}" data-recent="{{ $mockRequestRecent ? '1' : '0' }}" data-category="{{ Str::lower($requestAd->category ?? '') }}" data-search="{{ $mockRequestSearch }}">
                                <div class="mf-opportunity-main">
                                    <div class="mf-opportunity-meta">
                                        <span class="mf-category-label">{{ Str::limit($requestAd->category ?? 'Service', 32) }}</span>
                                        @if($mockRequestPriority)<span class="mf-priority-label"><i class="fas fa-hand-holding-heart"></i> Encore sans réponse</span>@endif
                                        @if($mockRequestUrgent)<span class="mf-urgent-label"><i class="fas fa-bolt"></i> Urgent</span>@endif
                                        <span>{{ $requestAd->created_at?->diffForHumans() }}</span>
                                    </div>
                                    <h3>{{ Str::limit($requestAd->title, 72) }}</h3>
                                    <p>{{ Str::limit($requestAd->description, 150) }}</p>
                                    <div class="mf-opportunity-facts">
                                        <span><i class="fas fa-map-marker-alt"></i> {{ Str::limit($requestAd->location ?? 'Localisation à préciser', 30) }}</span>
                                        @if(isset($requestAd->distance))<span><i class="fas fa-route"></i> {{ number_format((float) $requestAd->distance, 1, ',', '') }} km</span>@endif
                                        <span><i class="fas fa-calendar-alt"></i> Créneau à convenir</span>
                                    </div>
                                </div>
                                <div class="mf-opportunity-side">
                                    <strong>{{ $requestAd->formatted_price }}</strong>
                                    <span>{{ $mockRequestAuthor?->is_verified ? 'Client vérifié' : 'Nouveau client' }}</span>
                                    <a href="{{ route('ads.show', $requestAd) }}">Voir la demande</a>
                                    <button
                                        type="button"
                                        class="mf-save-preview{{ $mockRequestSaved ? ' is-saved' : '' }}"
                                        data-save-ad
                                        data-ad-id="{{ $requestAd->id }}"
                                        data-preview-only="{{ $mockIsLocalPreview ? '1' : '0' }}"
                                        aria-pressed="{{ $mockRequestSaved ? 'true' : 'false' }}"
                                    >
                                        <i class="{{ $mockRequestSaved ? 'fas' : 'far' }} fa-bookmark"></i>
                                        <span>{{ $mockRequestSaved ? 'Enregistrée' : 'Enregistrer' }}</span>
                                    </button>
                                </div>
                            </article>
                        @empty
                            <div class="mf-empty">Aucune demande ne correspond encore à cette sélection.</div>
                        @endforelse
                    </div>
                    <div class="mf-empty mf-filter-empty" id="mockFilterEmpty" hidden>Aucune demande ne correspond à ces filtres.</div>
                </section>
            </section>
        </main>

        <aside class="mf-context">
            <div data-context-panel="client">
                <section class="mf-context-card mf-next-step">
                    <span class="mf-context-kicker">Votre prochaine étape</span>
                    @if($mockMyRequest)
                        <h2>Suivre votre demande</h2>
                        <p>{{ Str::limit($mockMyRequest->title, 70) }}</p>
                        <a href="{{ route('ads.show', $mockMyRequest) }}">Voir les réponses <i class="fas fa-arrow-right"></i></a>
                    @else
                        <h2>Publiez en quelques minutes</h2>
                        <p>Un parcours guidé vous aide à préciser le service, le lieu et le créneau.</p>
                        <button type="button" data-open-request>Créer une demande <i class="fas fa-arrow-right"></i></button>
                    @endif
                </section>
                <section class="mf-context-card">
                    <div class="mf-context-title"><i class="fas fa-shield-alt"></i><div><h2>Paiement protégé</h2><p>Vous gardez le contrôle.</p></div></div>
                    <ol class="mf-secure-steps"><li><span>1</span>Le prestataire accepte</li><li><span>2</span>Les fonds sont sécurisés</li><li><span>3</span>Vous validez la prestation</li></ol>
                    <a class="mf-secondary-link" href="{{ route('service-orders.index') }}">Voir mes commandes</a>
                </section>
            </div>

            <div data-context-panel="provider" hidden>
                <section class="mf-context-card mf-profile-progress">
                    <span class="mf-context-kicker">Votre prochaine action</span>
                    <div class="mf-context-title"><i class="{{ $mockPrimarySuggestion['icon'] ?? 'fas fa-check' }}"></i><div><h2>{{ $mockPrimarySuggestion['title'] ?? 'Votre profil est prêt' }}</h2><p>Profil complété à {{ $mockProfileCompletion }}%</p></div></div>
                    <div class="mf-progress"><span style="width: {{ $mockProfileCompletion }}%"></span></div>
                    <p>{{ $mockPrimarySuggestion['description'] ?? 'Vos informations essentielles sont complètes. Gardez-les à jour pour rester visible.' }}</p>
                    <a href="{{ $mockSuggestionUrl }}">{{ $mockPrimarySuggestion['action_label'] ?? 'Voir mon profil' }} <i class="fas fa-arrow-right"></i></a>
                    @if($mockRemainingSuggestions > 0)<small class="mf-remaining-actions">{{ $mockRemainingSuggestions }} autre{{ $mockRemainingSuggestions > 1 ? 's' : '' }} étape{{ $mockRemainingSuggestions > 1 ? 's' : '' }} disponible{{ $mockRemainingSuggestions > 1 ? 's' : '' }} dans votre profil.</small>@endif
                </section>
                <section class="mf-context-card">
                    <span class="mf-context-kicker">Raccourcis</span>
                    <div class="mf-shortcuts"><a href="{{ route('pro.dashboard') }}"><i class="fas fa-chart-line"></i><span>Tableau Pro</span></a><a href="{{ route('quote-tool.landing') }}"><i class="fas fa-file-signature"></i><span>Créer un devis</span></a><a href="{{ route('messages.index') }}"><i class="fas fa-comments"></i><span>Messages</span></a><a href="{{ route('service-orders.index') }}"><i class="fas fa-shield-alt"></i><span>Commandes</span></a></div>
                </section>
            </div>
        </aside>
    </div>

    <nav class="mf-mobile-nav" aria-label="Navigation mobile du nouveau feed">
        <a class="is-active" href="{{ $mockFeedHomeRoute }}"><i class="fas fa-home"></i><span>Accueil</span></a>
        <a href="{{ route('ads.index') }}"><i class="fas fa-clipboard-list"></i><span>Annonces</span></a>
        <button type="button" data-open-request><i class="fas fa-plus"></i><span>Publier</span></button>
        <a href="{{ route('messages.index') }}"><i class="fas fa-comments"></i><span>Messages</span></a>
        <a href="{{ route('profile.show') }}"><i class="fas fa-user"></i><span>Profil</span></a>
    </nav>

    <div class="mf-toast" id="newFeedToast" role="status" aria-live="polite" hidden></div>

    <dialog class="mf-request-dialog" id="mockRequestDialog" aria-labelledby="mockRequestDialogTitle">
        <form method="dialog" class="mf-dialog-card">
            <button class="mf-dialog-close" value="cancel" aria-label="Fermer"><i class="fas fa-times"></i></button>
            <span class="mf-eyebrow">Nouvelle demande</span>
            <h2 id="mockRequestDialogTitle">Quel service recherchez-vous ?</h2>
            <p>Choisissez une catégorie. L'assistant vous posera ensuite uniquement les questions utiles.</p>
            <div class="mf-dialog-categories">
                @foreach($mockCategories as $categoryName => $categoryData)
                    <button type="button" data-dialog-category="{{ $categoryName }}"><i class="{{ $categoryData['icon'] ?? 'fas fa-tools' }}"></i><span>{{ $categoryName }}</span></button>
                @endforeach
            </div>
            <a class="mf-dialog-continue" id="mockDialogContinue" href="{{ route('demand.create') }}">Continuer vers la publication <i class="fas fa-arrow-right"></i></a>
            <small>La demande ne sera publiée qu'après un récapitulatif et votre confirmation explicite.</small>
        </form>
    </dialog>
</div>
@endsection

@section('scripts')
<script>
(() => {
    const app = document.getElementById('mockFeedApp');
    if (!app) return;

    const setMode = (mode) => {
        if (!['client', 'provider'].includes(mode)) return;
        app.querySelectorAll('[data-mode-panel]').forEach((panel) => { panel.hidden = panel.dataset.modePanel !== mode; });
        app.querySelectorAll('[data-context-panel]').forEach((panel) => { panel.hidden = panel.dataset.contextPanel !== mode; });
        app.querySelectorAll('[data-mode-copy]').forEach((copy) => { copy.hidden = copy.dataset.modeCopy !== mode; });
        app.querySelectorAll('[data-mode-button]').forEach((button) => {
            const active = button.dataset.modeButton === mode;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        app.dataset.activeMode = mode;
        try { localStorage.setItem('prokejem-feed-mockup-mode', mode); } catch (error) {}
    };

    let initialMode = app.dataset.initialMode || 'client';
    if (!new URLSearchParams(window.location.search).has('mode')) {
        try { initialMode = localStorage.getItem('prokejem-feed-mockup-mode') || initialMode; } catch (error) {}
    }
    setMode(initialMode);
    app.querySelectorAll('[data-mode-button]').forEach((button) => button.addEventListener('click', () => setMode(button.dataset.modeButton)));
    app.querySelectorAll('[data-switch-to-provider]').forEach((button) => button.addEventListener('click', () => setMode('provider')));

    const dialog = document.getElementById('mockRequestDialog');
    const continueLink = document.getElementById('mockDialogContinue');
    const demandUrl = @json(route('demand.create'));
    const openDialog = (category = '') => {
        if (!dialog) return;
        dialog.querySelectorAll('[data-dialog-category]').forEach((button) => button.classList.toggle('is-selected', button.dataset.dialogCategory === category));
        continueLink.href = category ? `${demandUrl}?category=${encodeURIComponent(category)}` : demandUrl;
        if (typeof dialog.showModal === 'function') dialog.showModal();
    };
    app.querySelectorAll('[data-open-request]').forEach((button) => button.addEventListener('click', () => openDialog()));
    app.querySelectorAll('[data-request-category]').forEach((button) => button.addEventListener('click', () => openDialog(button.dataset.requestCategory || '')));
    dialog?.querySelectorAll('[data-dialog-category]').forEach((button) => button.addEventListener('click', () => {
        dialog.querySelectorAll('[data-dialog-category]').forEach((item) => item.classList.remove('is-selected'));
        button.classList.add('is-selected');
        continueLink.href = `${demandUrl}?category=${encodeURIComponent(button.dataset.dialogCategory)}`;
    }));

    const cards = [...app.querySelectorAll('[data-opportunity-card]')];
    const search = document.getElementById('mockOpportunitySearch');
    const category = document.getElementById('mockOpportunityCategory');
    const empty = document.getElementById('mockFilterEmpty');
    let quickFilter = 'all';
    const applyFilters = () => {
        const query = (search?.value || '').trim().toLocaleLowerCase('fr');
        const categoryValue = category?.value || '';
        let visible = 0;
        cards.forEach((card) => {
            const matchesQuery = !query || (card.dataset.search || '').includes(query);
            const matchesCategory = !categoryValue || (card.dataset.category || '').includes(categoryValue);
            const matchesQuick = quickFilter === 'all'
                || (quickFilter === 'priority' && card.dataset.priority === '1')
                || (quickFilter === 'urgent' && card.dataset.urgent === '1')
                || (quickFilter === 'recent' && card.dataset.recent === '1');
            card.hidden = !(matchesQuery && matchesCategory && matchesQuick);
            if (!card.hidden) visible += 1;
        });
        if (empty) empty.hidden = visible !== 0 || cards.length === 0;
    };
    search?.addEventListener('input', applyFilters);
    category?.addEventListener('change', applyFilters);
    app.querySelectorAll('[data-opportunity-filter]').forEach((button) => button.addEventListener('click', () => {
        quickFilter = button.dataset.opportunityFilter;
        app.querySelectorAll('[data-opportunity-filter]').forEach((item) => item.classList.toggle('is-active', item === button));
        applyFilters();
    }));

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const toast = document.getElementById('newFeedToast');
    let toastTimer;
    const showFeedback = (message, type = 'success') => {
        if (!toast) return;
        window.clearTimeout(toastTimer);
        toast.textContent = message;
        toast.dataset.type = type;
        toast.hidden = false;
        toast.classList.add('is-visible');
        toastTimer = window.setTimeout(() => {
            toast.classList.remove('is-visible');
            toast.hidden = true;
        }, 2800);
    };
    const updateSaveButton = (button, saved) => {
        button.setAttribute('aria-pressed', saved ? 'true' : 'false');
        button.classList.toggle('is-saved', saved);
        button.querySelector('i')?.classList.toggle('far', !saved);
        button.querySelector('i')?.classList.toggle('fas', saved);
        const label = button.querySelector('span');
        if (label) label.textContent = saved ? 'Enregistrée' : 'Enregistrer';
    };

    app.querySelectorAll('[data-save-ad]').forEach((button) => button.addEventListener('click', async () => {
        const wasSaved = button.getAttribute('aria-pressed') === 'true';
        if (button.dataset.previewOnly === '1') {
            updateSaveButton(button, !wasSaved);
            showFeedback(!wasSaved ? 'Favori simulé dans l’aperçu local.' : 'Favori retiré de l’aperçu.', 'info');
            return;
        }

        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(`/ads/${encodeURIComponent(button.dataset.adId)}/toggle-save`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            updateSaveButton(button, Boolean(data.saved));
            showFeedback(data.message || (data.saved ? 'Annonce sauvegardée.' : 'Annonce retirée des favoris.'));
        } catch (error) {
            updateSaveButton(button, wasSaved);
            showFeedback('Impossible de modifier ce favori. Réessayez.', 'error');
        } finally {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    }));
})();
</script>
@endsection

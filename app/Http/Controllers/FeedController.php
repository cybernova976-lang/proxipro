<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Services\FeedRankingService;
use App\Support\MarketplaceCategoryRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FeedController extends Controller
{
    private array $viewerServiceCategoryCache = [];

    public function __construct(
        private FeedRankingService $feedRankingService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        // ===== GÉOLOCALISATION AUTOMATIQUE =====
        $geoContext = $this->resolveFeedGeoContext($request, $user);
        $userLat = $geoContext['latitude'];
        $userLng = $geoContext['longitude'];
        $userRadius = (int) ($request->get('radius') ?? $user?->geo_radius ?? 50);
        $geoCity = $geoContext['city'];
        $geoCountry = $geoContext['country'];
        $geoEnabled = ($userLat !== null && $userLng !== null) || $geoCity || $geoCountry;
        $geoSource = $geoContext['source'];
        $feedScope = $request->get('scope', 'all');
        if (! in_array($feedScope, ['nearby', 'all'], true)) {
            $feedScope = 'all';
        }
        $useNearbyScope = $geoEnabled && $feedScope === 'nearby';
        $geoFallbackUsed = false;

        // Une seule requête groupée fournit les compteurs des 291 sous-catégories.
        $missionCategories = $this->getHomeMegaCategories();

        // Appliquer le filtre de type si présent
        $filterType = $request->get('type', 'all'); // all, offres, demandes
        if ($filterType === 'all' && ! $request->has('type')) {
            if ($user && ($user->user_type === 'professionnel' || $user->is_service_provider)) {
                $filterType = 'demandes';
            }
        }

        // ===== SECTION "LES DERNIÈRES PÉPITES" - filtrée par proximité =====
        $allAdsQuery = $this->buildMainFeedAdsQuery($user, $filterType, $useNearbyScope, $userLat, $userLng, $userRadius, $geoCity, $geoCountry);
        $this->orderMainFeedAds($allAdsQuery, $useNearbyScope, $userLat !== null ? (float) $userLat : null, $userLng !== null ? (float) $userLng : null);
        $feedMapQuery = clone $allAdsQuery;
        $ads = $allAdsQuery->paginate(12)->withQueryString();

        // Si peu de résultats, élargir automatiquement le rayon
        $radiusWasExpanded = false;
        $originalRadius = $userRadius;
        if ($useNearbyScope && $ads->total() === 0) {
            $nearbyAvailabilityQuery = $this->buildMainFeedAdsQuery(
                $user,
                $filterType,
                true,
                $userLat,
                $userLng,
                $userRadius,
                $geoCity,
                $geoCountry
            );

            if ((clone $nearbyAvailabilityQuery)->count() > 0) {
                $feedMapQuery = clone $nearbyAvailabilityQuery;
            } else {
                $fallbackQuery = $this->buildMainFeedAdsQuery($user, $filterType, false, null, null, $userRadius, $geoCity, $geoCountry);
                $this->orderMainFeedAds($fallbackQuery, false);
                $feedMapQuery = clone $fallbackQuery;
                $ads = $fallbackQuery->paginate(12)->withQueryString();
                $geoFallbackUsed = true;
            }
        } elseif ($useNearbyScope && $ads->total() < 3 && $userRadius < 200) {
            $expandedRadius = min($userRadius * 3, 500);
            $expandedQuery = $this->buildMainFeedAdsQuery($user, $filterType, true, $userLat, $userLng, $expandedRadius, $geoCity, $geoCountry);
            $this->orderMainFeedAds($expandedQuery, true, $userLat !== null ? (float) $userLat : null, $userLng !== null ? (float) $userLng : null);
            $expandedMapQuery = clone $expandedQuery;
            $adsExp = $expandedQuery->paginate(12)->withQueryString();
            if ($adsExp->total() > $ads->total()) {
                $ads = $adsExp;
                $feedMapQuery = $expandedMapQuery;
                $userRadius = $expandedRadius;
                $radiusWasExpanded = true;
            }
        }

        // ===== SECTION FILTRÉE PAR CATÉGORIE (optionnelle, pour afficher les offres de la catégorie sélectionnée) =====
        $categoryFilteredAds = null;
        if ($request->has('category')) {
            $cat = $request->category;
            $categoryQuery = Ad::marketplaceActive()->with('user');

            // Si c'est une catégorie principale, on inclut toutes ses sous-catégories
            if (isset($missionCategories[$cat])) {
                $subNames = collect($missionCategories[$cat]['subs'])->pluck('name')->toArray();
                $subNames[] = $cat; // Inclure la catégorie mère aussi
                $categoryQuery->whereIn('category', $subNames);
            } else {
                $categoryQuery->where('category', $cat);
            }

            // Appliquer le tri
            $sort = $request->get('sort', 'recent');
            if ($sort === 'popular') {
                $categoryQuery->orderBy('views', 'desc');
            } else {
                $categoryQuery->orderBy('is_pinned', 'desc')->orderBy('created_at', 'desc');
            }

            $categoryFilteredAds = $categoryQuery->get();
        }

        // Handle specific subcategory filter (from pill buttons) using 'search' param as category override
        if ($request->has('search')) {
            $buildSearchAdsQuery = function (bool $nearby) use ($request, $userLat, $userLng, $userRadius, $geoCity, $geoCountry) {
                $query = Ad::marketplaceActive()->with('user')
                    ->where(function ($q) use ($request) {
                        $q->where('category', $request->search)
                            ->orWhere('title', 'LIKE', '%'.$request->search.'%');
                    });

                if ($nearby) {
                    $this->applyAdGeoScope($query, $userLat !== null ? (float) $userLat : null, $userLng !== null ? (float) $userLng : null, $userRadius, $geoCity, $geoCountry);
                }

                $this->orderMainFeedAds($query, $nearby, $userLat !== null ? (float) $userLat : null, $userLng !== null ? (float) $userLng : null);

                return $query;
            };

            $searchAds = $buildSearchAdsQuery($useNearbyScope);
            $feedMapQuery = clone $searchAds;
            $ads = $searchAds->paginate(12)->withQueryString();

            if ($useNearbyScope && $ads->total() === 0) {
                $searchFallbackQuery = $buildSearchAdsQuery(false);
                $feedMapQuery = clone $searchFallbackQuery;
                $ads = $searchFallbackQuery->paginate(12)->withQueryString();
                $geoFallbackUsed = true;
            }
        }

        // Toutes les annonces pour le feed principal
        $sort = $request->get('sort', 'recent');

        // Top Pros - Classement par avis VÉRIFIÉS (uniquement des utilisateurs ayant publié une annonce ou effectué un paiement)
        $topPros = \App\Models\User::where(function ($q) {
            $q->where('user_type', 'professionnel')
                ->orWhere(function ($q2) {
                    $q2->where('user_type', 'particulier')
                        ->where('is_service_provider', true);
                });
        })
            ->where('id', '!=', $user->id)
            ->whereHas('verifiedReviewsReceived') // Au moins 1 avis vérifié
            ->withCount(['verifiedReviewsReceived as verified_reviews_count'])
            ->withAvg(['verifiedReviewsReceived as verified_reviews_avg' => fn ($q) => $q], 'rating')
            ->withCount(['ads as ads_count' => fn ($q) => $q->marketplaceActive()])
            ->orderByDesc('verified_reviews_avg')   // Meilleure note d'abord
            ->orderByDesc('verified_reviews_count')  // Puis le plus d'avis
            ->take(6)
            ->get();

        // Premium Pros - Utilisateurs avec annonces boostées ou abonnement actif
        // Inclut professionnels ET particuliers prestataires
        $usersWithBoostedAds = \App\Models\User::where(function ($q) {
            $q->where('user_type', 'professionnel')
                ->orWhere(function ($q2) {
                    $q2->where('user_type', 'particulier')
                        ->where('is_service_provider', true);
                });
        })
            ->whereHas('ads', fn ($q) => $q->marketplaceActive()->where('is_boosted', true)->where('boost_end', '>', now()))
            ->with(['ads' => fn ($q) => $q->marketplaceActive()
                ->where('is_boosted', true)
                ->where('boost_end', '>', now())
                ->latest()
                ->take(1)])
            ->withCount(['ads as ads_count' => fn ($q) => $q->marketplaceActive()])
            ->orderByDesc('updated_at')
            ->take(20)
            ->get();

        $boostedUserIds = $usersWithBoostedAds->pluck('id')->toArray();

        $remainingPremiumSlots = max(0, 20 - count($boostedUserIds));
        $subscribedPros = $remainingPremiumSlots === 0 ? collect() : \App\Models\User::where(function ($q) {
            $q->where('user_type', 'professionnel')
                ->orWhere(function ($q2) {
                    $q2->where('user_type', 'particulier')
                        ->where('is_service_provider', true);
                });
        })
            ->whereNotIn('id', $boostedUserIds)
            ->where(function ($q) {
                $q->whereNotNull('plan')
                    ->where('plan', '!=', '')
                    ->whereRaw('LOWER(plan) != ?', ['free'])
                    ->where(function ($q2) {
                        $q2->whereNull('subscription_end')
                            ->orWhere('subscription_end', '>', now());
                    });
            })
            ->withCount(['ads as ads_count' => fn ($q) => $q->marketplaceActive()])
            ->with(['ads' => fn ($q) => $q->marketplaceActive()->latest()->take(1)])
            ->inRandomOrder()
            ->take($remainingPremiumSlots)
            ->get();

        // Ajouter aussi les particuliers prestataires récents (même sans abonnement premium)
        $newProviders = \App\Models\User::where('user_type', 'particulier')
            ->where('is_service_provider', true)
            ->whereNotIn('id', $boostedUserIds)
            ->whereNotIn('id', $subscribedPros->pluck('id')->toArray())
            ->withCount(['ads as ads_count' => fn ($q) => $q->marketplaceActive()])
            ->with(['services' => fn ($q) => $q->where('is_active', true)->limit(3)])
            ->orderByDesc('service_provider_since')
            ->take(10)
            ->get();

        $premiumPros = $usersWithBoostedAds->merge($subscribedPros)->merge($newProviders);

        $homePersonalRequests = $this->buildHomeShowcaseAds(
            serviceType: 'demande',
            currentUser: $user,
            authorKind: null,
            limit: 18,
            userLat: $useNearbyScope && ! $geoFallbackUsed && $userLat !== null ? (float) $userLat : null,
            userLng: $useNearbyScope && ! $geoFallbackUsed && $userLng !== null ? (float) $userLng : null,
            userRadius: $useNearbyScope && ! $geoFallbackUsed ? $userRadius : null,
            geoCity: $useNearbyScope && ! $geoFallbackUsed ? $geoCity : null,
            geoCountry: $useNearbyScope && ! $geoFallbackUsed ? $geoCountry : null
        );
        $homeProfessionalOffers = $this->buildHomeShowcaseAds(
            serviceType: 'offre',
            currentUser: $user,
            authorKind: null,
            limit: 18,
            userLat: $useNearbyScope && ! $geoFallbackUsed && $userLat !== null ? (float) $userLat : null,
            userLng: $useNearbyScope && ! $geoFallbackUsed && $userLng !== null ? (float) $userLng : null,
            userRadius: $useNearbyScope && ! $geoFallbackUsed ? $userRadius : null,
            geoCity: $useNearbyScope && ! $geoFallbackUsed ? $geoCity : null,
            geoCountry: $useNearbyScope && ! $geoFallbackUsed ? $geoCountry : null
        );
        $homeProfessionalProfiles = $this->buildHighlightedProfessionalProfiles($user, 18);

        $activeClientRequest = $user?->exists
            ? $user->ads()
                ->marketplaceActive()
                ->where('service_type', 'demande')
                ->withCount('serviceProposals')
                ->latest('created_at')
                ->first()
            : null;

        $priorityProviderRequests = $this->buildPriorityProviderRequests(
            user: $user,
            userLat: $useNearbyScope && ! $geoFallbackUsed && $userLat !== null ? (float) $userLat : null,
            userLng: $useNearbyScope && ! $geoFallbackUsed && $userLng !== null ? (float) $userLng : null,
            userRadius: $useNearbyScope && ! $geoFallbackUsed ? $userRadius : null,
            geoCity: $useNearbyScope && ! $geoFallbackUsed ? $geoCity : null,
            geoCountry: $useNearbyScope && ! $geoFallbackUsed ? $geoCountry : null
        );

        if ($request->attributes->get('mockup_local_preview')) {
            $previewData = $this->buildLocalMockupPreviewData();

            if ($homePersonalRequests->isEmpty()) {
                $homePersonalRequests = $previewData['requests'];
            }
            if ($homeProfessionalOffers->isEmpty()) {
                $homeProfessionalOffers = $previewData['offers'];
            }
            if ($homeProfessionalProfiles->isEmpty()) {
                $homeProfessionalProfiles = $previewData['providers'];
            }
            if ($priorityProviderRequests->isEmpty()) {
                $priorityProviderRequests = $previewData['requests']
                    ->filter(fn (Ad $ad) => $ad->created_at?->lessThanOrEqualTo(now()->subHours(2)))
                    ->take(3)
                    ->values();
            }

            $previewCategoryTotals = [18, 14, 12, 9, 8, 7, 6, 5];
            $previewCategoryIndex = 0;
            $missionCategories = collect($missionCategories)
                ->map(function ($category) use ($previewCategoryTotals, &$previewCategoryIndex) {
                    $category['total'] = $previewCategoryTotals[$previewCategoryIndex]
                        ?? (int) ($category['total'] ?? 0);
                    $previewCategoryIndex++;

                    return $category;
                })
                ->all();
        }

        $savedHomeAdIds = collect();
        $saveableHomeRequests = $priorityProviderRequests
            ->concat($homePersonalRequests)
            ->unique('id')
            ->values();

        if ($user?->exists && $saveableHomeRequests->isNotEmpty()) {
            $savedHomeAdIds = $user->savedAds()
                ->whereIn('ads.id', $saveableHomeRequests->pluck('id')->all())
                ->pluck('ads.id')
                ->map(fn ($id) => (int) $id)
                ->values();
        }

        $homeShowcaseAdIds = $saveableHomeRequests
            ->pluck('id')
            ->merge($homeProfessionalOffers->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // ===== PRO ONBOARDING MODAL DATA =====
        $showOnboardingModal = false;
        $onboardingCategories = [];
        $proSuggestions = [];
        $proProfileCompletion = 0;

        if (! $request->attributes->get('mockup_local_preview')
            && $user
            && ($user->isProfessionnel() || $user->isServiceProvider())) {
            $showOnboardingModal = $user->shouldShowOnboardingModal();
            $proSuggestions = $user->getProSuggestions();
            $proProfileCompletion = $user->getProProfileCompletionPercent();

            // Toujours charger les catégories pour le formulaire d'onboarding,
            // y compris lorsqu'il est ouvert depuis les suggestions du feed.
            $onboardingCategories = MarketplaceCategoryRegistry::enabledServiceOptions();
        }

        if ($request->attributes->get('mockup_local_preview')) {
            $proProfileCompletion = 72;
        }

        $adsMapData = $this->buildAdsMapData(
            (clone $feedMapQuery)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->limit(120)
                ->get()
        );

        // ======================================================================
        // MODELE DE VUE DE LA PAGE D'ACCUEIL
        // ----------------------------------------------------------------------
        // Tout ce que la page d'accueil affiche est prepare ici : la vue et ses
        // partials ne declenchent plus aucune requete.
        // Regle de fond : aucun chiffre affiche n'est estime. Si la donnee
        // n'existe pas, l'element correspondant n'est simplement pas rendu.
        // ======================================================================

        // --- role : deduit des donnees, jamais d'un onglet clique ---
        $pkRole = ($user && ($user->isProfessionnel() || $user->isServiceProvider()))
            ? 'provider'
            : 'client';

        // --- verification d'identite (zone 3) ---
        $pkVerification = $user?->exists
            ? \App\Models\IdentityVerification::where('user_id', $user->id)->latest()->first()
            : null;
        $pkVerificationStatus = $pkVerification?->status;
        $pkIsVerified = (bool) ($user?->hasVerifiedProfileBadge() ?? false);

        // --- une seule action suivante pour un prestataire ---
        $pkSuggestion = collect($proSuggestions)->first();
        $pkSuggestionUrl = $pkSuggestion
            ? match ($pkSuggestion['id'] ?? '') {
                'complete_onboarding', 'add_categories', 'add_location' => route('pro.onboarding'),
                'verify_profile' => route('verification.index'),
                'get_subscription' => route('pro.subscription'),
                'create_ad' => route('ads.create'),
                default => route('pro.profile.edit'),
            }
            : route('pro.profile.edit');
        $pkCompletion = max(0, min(100, (int) $proProfileCompletion));

        // --- zone 4 : le flux, six annonces au maximum ---
        if ($pkRole === 'provider') {
            $pkFeedTitle = 'Demandes qui correspondent a votre metier';
            $pkFeedAds = collect($priorityProviderRequests)
                ->concat($homePersonalRequests)
                ->unique('id')
                ->take(6)
                ->values();
        } else {
            $pkFeedTitle = $geoCity ? 'Demandes pres de vous' : 'Dernieres demandes publiees';
            $pkFeedAds = collect($homePersonalRequests)
                ->reject(fn ($ad) => $activeClientRequest && (int) $ad->id === (int) $activeClientRequest->id)
                ->take(6)
                ->values();
        }

        // Flux trop maigre : on complete avec des offres de service plutot que
        // de laisser une page vide. Mieux vaut six annonces vivantes.
        if ($pkFeedAds->count() < 3) {
            $pkFeedAds = $pkFeedAds
                ->concat($homeProfessionalOffers)
                ->unique('id')
                ->take(6)
                ->values();
        }

        $pkMatchingCount = $pkRole === 'provider' ? $pkFeedAds->count() : 0;

        // --- favoris deja enregistres parmi les annonces reellement affichees ---
        $pkSavedAdIds = collect();
        if ($user?->exists && $pkFeedAds->isNotEmpty()) {
            $pkSavedAdIds = $user->savedAds()
                ->whereIn('ads.id', $pkFeedAds->pluck('id')->all())
                ->pluck('ads.id')
                ->map(fn ($id) => (int) $id)
                ->values();
        }

        // --- zone 2 : les six categories les plus actives ---
        $pkQuickCategories = collect($missionCategories)
            ->sortByDesc(fn ($category) => (int) ($category['total'] ?? 0))
            ->take(6)
            ->all();

        // --- index de recherche du champ d'intention (categories + sous-categories) ---
        $pkSearchIndex = [];
        foreach ($missionCategories as $pkParent => $pkData) {
            $pkSearchIndex[] = [
                'label' => $pkParent,
                'parent' => $pkParent,
                'sub' => null,
                'icon' => $pkData['icon'] ?? 'fas fa-tools',
                'search' => \Illuminate\Support\Str::of($pkParent)->lower()->ascii()->toString(),
            ];
            foreach (($pkData['subs'] ?? []) as $pkSub) {
                $pkSubName = is_array($pkSub) ? ($pkSub['name'] ?? null) : $pkSub;
                if (! $pkSubName) {
                    continue;
                }
                $pkSearchIndex[] = [
                    'label' => $pkSubName,
                    'parent' => $pkParent,
                    'sub' => $pkSubName,
                    'icon' => $pkSub['icon'] ?? ($pkData['icon'] ?? 'fas fa-tag'),
                    'search' => \Illuminate\Support\Str::of($pkSubName)->lower()->ascii()->toString(),
                ];
            }
        }

        // --- activite recente : trois chiffres mesures, mis en cache 10 minutes ---
        $pkActivity = Cache::remember('feed:activity:v1', 600, function () {
            $lines = [];

            $weekRequests = Ad::marketplaceActive()
                ->where('service_type', 'demande')
                ->where('created_at', '>=', now()->subWeek())
                ->count();
            if ($weekRequests > 0) {
                $lines[] = [
                    'icon' => 'fas fa-bolt',
                    'html' => '<b>'.$weekRequests.' demande'.($weekRequests > 1 ? 's' : '').'</b> publiee'
                        .($weekRequests > 1 ? 's' : '').' cette semaine',
                ];
            }

            $newProviders = \App\Models\User::where('created_at', '>=', now()->subDays(30))
                ->where(function ($query) {
                    $query->where('user_type', 'professionnel')
                        ->orWhere('is_service_provider', true);
                })
                ->count();
            if ($newProviders > 0) {
                $lines[] = [
                    'icon' => 'fas fa-user-plus',
                    'html' => '<b>'.$newProviders.' prestataire'.($newProviders > 1 ? 's' : '').'</b> '
                        .($newProviders > 1 ? 'ont' : 'a').' rejoint Prokejem ces 30 derniers jours',
                ];
            }

            $activeAds = Ad::marketplaceActive()->count();
            if ($activeAds > 0) {
                $lines[] = [
                    'icon' => 'fas fa-clipboard-list',
                    'html' => '<b>'.$activeAds.' annonce'.($activeAds > 1 ? 's' : '').'</b> active'
                        .($activeAds > 1 ? 's' : '').' en ce moment',
                ];
            }

            return $lines;
        });

        // --- vues du profil ce mois-ci : chiffre reel, dedoublonne par visiteur ---
        // Une seule requete, et uniquement pour un prestataire : c'est le seul
        // role a qui ce chiffre est montre.
        $pkProfileViews = 0;
        if ($pkRole === 'provider' && $user?->exists) {
            $pkProfileViews = app(\App\Services\ProfileViewCounter::class)->countThisMonth($user);
        }

        // --- abonnement : jamais permanent, seulement adosse a une valeur reelle ---
        // On ne propose l'abonnement que s'il y a quelque chose de vrai a montrer :
        // soit des vues de profil, soit des demandes compatibles.
        $pkShowUpsell = $pkRole === 'provider'
            && $user
            && ! $user->hasActiveProSubscription()
            && ($pkProfileViews > 0 || $pkMatchingCount > 0);

        // La maquette a ete fusionnee dans la page d'accueil : une seule vue.
        $feedView = 'feed.index';

        return view($feedView, compact(
            'missionCategories',
            'ads',
            'topPros',
            'premiumPros',
            'homePersonalRequests',
            'homeProfessionalOffers',
            'homeProfessionalProfiles',
            'activeClientRequest',
            'priorityProviderRequests',
            'homeShowcaseAdIds',
            'savedHomeAdIds',
            'sort',
            'filterType',
            'userLat',
            'userLng',
            'userRadius',
            'geoEnabled',
            'geoCity',
            'geoCountry',
            'geoSource',
            'feedScope',
            'useNearbyScope',
            'geoFallbackUsed',
            'radiusWasExpanded',
            'originalRadius',
            'showOnboardingModal',
            'onboardingCategories',
            'proSuggestions',
            'proProfileCompletion',
            'adsMapData',
            // page d'accueil
            'pkRole',
            'pkFeedTitle',
            'pkFeedAds',
            'pkSavedAdIds',
            'pkQuickCategories',
            'pkSearchIndex',
            'pkMatchingCount',
            'pkVerificationStatus',
            'pkIsVerified',
            'pkSuggestion',
            'pkSuggestionUrl',
            'pkCompletion',
            'pkActivity',
            'pkShowUpsell',
            'pkProfileViews'
        ));
    }

    /**
     * Render the functional mockup locally without creating an authenticated session.
     * The route exposing this method is only registered in the local environment.
     */
    public function previewMockup(Request $request)
    {
        abort_unless(app()->environment('local'), 404);

        $previewAsClient = $request->query('role') === 'client';

        $previewUser = \App\Models\User::query()
            ->when(
                $previewAsClient,
                fn ($query) => $query
                    ->where('user_type', 'particulier')
                    ->where('is_service_provider', false),
                fn ($query) => $query->where(function ($query) {
                    $query->where('user_type', 'professionnel')
                        ->orWhere('is_service_provider', true);
                })
            )
            ->first() ?? \App\Models\User::query()->first();

        if (! $previewUser) {
            $previewUser = (new \App\Models\User)->forceFill([
                'id' => 0,
                'name' => 'Compte aperçu',
                'email' => 'apercu-local@prokejem.test',
                'user_type' => $previewAsClient ? 'particulier' : 'professionnel',
                'account_type' => $previewAsClient ? 'particulier' : 'professionnel',
                'is_service_provider' => ! $previewAsClient,
                'city' => 'Paris',
                'country' => 'France',
                'geo_radius' => 25,
            ]);
        }

        Auth::setUser($previewUser);
        $request->attributes->set('mockup_local_preview', true);

        return $this->index($request);
    }

    /**
     * Provide realistic read-only content when the local database is empty.
     * These models are never persisted and only exist for the preview request.
     */
    private function buildLocalMockupPreviewData(): array
    {
        $makeUser = function (int $id, string $name, array $attributes = []) {
            return (new \App\Models\User)->forceFill(array_merge([
                'id' => $id,
                'name' => $name,
                'email' => "apercu{$id}@prokejem.test",
                'user_type' => 'professionnel',
                'account_type' => 'professionnel',
                'is_service_provider' => true,
                'is_verified' => true,
                'city' => 'Paris',
                'country' => 'France',
            ], $attributes));
        };

        $providers = collect([
            $makeUser(-201, 'Sophie Martin', ['profession' => 'Plombière', 'bio' => 'Interventions soignées, devis clair et disponibilité rapide.', 'hourly_rate' => 42, 'show_hourly_rate' => true, 'specialties' => ['Dépannage urgent', 'Travail soigné'], 'verified_reviews_avg' => 4.9, 'verified_reviews_count' => 47, 'ads_count' => 6, 'is_top_provider' => true]),
            $makeUser(-202, 'Karim Bensaïd', ['profession' => 'Bricolage & montage', 'bio' => 'Montage de meubles, fixations et petits travaux à domicile.', 'hourly_rate' => 30, 'show_hourly_rate' => true, 'specialties' => ['Montage de meubles', 'Résultat garanti'], 'verified_reviews_avg' => 4.8, 'verified_reviews_count' => 31, 'ads_count' => 5]),
            $makeUser(-203, 'Claire Dubois', ['profession' => 'Ménage à domicile', 'bio' => 'Prestations ponctuelles ou régulières, matériel fourni sur demande.', 'hourly_rate' => 24, 'show_hourly_rate' => true, 'specialties' => ['Efficace et discrète', 'Matériel fourni'], 'verified_reviews_avg' => 4.9, 'verified_reviews_count' => 62, 'ads_count' => 4, 'is_top_provider' => true]),
            $makeUser(-204, 'Lucas Bernard', ['profession' => 'Jardinier', 'bio' => 'Entretien, taille et remise en état de jardins et terrasses.', 'hourly_rate' => 28, 'show_hourly_rate' => true, 'specialties' => ['Taille et entretien', 'Conseils personnalisés'], 'verified_reviews_avg' => 4.7, 'verified_reviews_count' => 24, 'ads_count' => 7]),
        ]);

        $clients = collect([
            $makeUser(-301, 'Élodie R.', ['user_type' => 'particulier', 'account_type' => 'particulier', 'is_service_provider' => false]),
            $makeUser(-302, 'Thomas L.', ['user_type' => 'particulier', 'account_type' => 'particulier', 'is_service_provider' => false]),
            $makeUser(-303, 'Nadia M.', ['user_type' => 'particulier', 'account_type' => 'particulier', 'is_service_provider' => false]),
            $makeUser(-304, 'Julien P.', ['user_type' => 'particulier', 'account_type' => 'particulier', 'is_service_provider' => false]),
            $makeUser(-305, 'Manon D.', ['user_type' => 'particulier', 'account_type' => 'particulier', 'is_service_provider' => false]),
        ]);

        $requestRows = [
            [-101, 'Réparer une fuite sous évier', 'Bricolage & Travaux', 'Une fuite est apparue sous l’évier de la cuisine. Intervention souhaitée rapidement.', 'Paris 15e', 65, true, now()->subHours(2)],
            [-102, 'Monter une armoire trois portes', 'Bricolage & Travaux', 'Armoire neuve livrée en kit, montage à prévoir dans une chambre dégagée.', 'Boulogne-Billancourt', 90, false, now()->subHours(8)],
            [-103, 'Nettoyage complet avant état des lieux', 'Nettoyage & Entretien', 'Appartement de 48 m² vide, cuisine et salle de bain à nettoyer en profondeur.', 'Paris 11e', 120, false, now()->subDay()],
            [-104, 'Tailler une haie et évacuer les déchets', 'Jardinage & Extérieur', 'Environ 12 mètres de haie, accès facile depuis la rue.', 'Montreuil', 150, true, now()->subDays(2)],
            [-105, 'Cours de mathématiques niveau seconde', 'Cours & Formation', 'Recherche accompagnement hebdomadaire pour reprendre les bases et gagner en méthode.', 'Vincennes', 30, false, now()->subDays(6)],
        ];

        $requests = collect($requestRows)->map(function (array $row, int $index) use ($clients) {
            $ad = (new Ad)->forceFill([
                'id' => $row[0],
                'user_id' => $clients[$index]->id,
                'title' => $row[1],
                'category' => $row[2],
                'description' => $row[3],
                'location' => $row[4],
                'price' => $row[5],
                'price_type' => 'fixed',
                'service_type' => 'demande',
                'status' => 'active',
                'is_urgent' => $row[6],
                'urgent_until' => $row[6] ? now()->addDay() : null,
                'created_at' => $row[7],
                'service_proposals_count' => 0,
            ]);
            $ad->setRelation('user', $clients[$index]);

            return $ad;
        });

        $offerRows = [
            [-151, 'Dépannage plomberie express', 'Paris et petite couronne', 75, $providers[0]],
            [-152, 'Montage de meubles à domicile', 'Paris ouest', 45, $providers[1]],
            [-153, 'Ménage ponctuel ou régulier', 'Paris centre', 28, $providers[2]],
        ];

        $offers = collect($offerRows)->map(function (array $row) {
            $ad = (new Ad)->forceFill([
                'id' => $row[0],
                'user_id' => $row[4]->id,
                'title' => $row[1],
                'location' => $row[2],
                'price' => $row[3],
                'price_type' => 'fixed',
                'service_type' => 'offre',
                'status' => 'active',
                'created_at' => now()->subDays(2),
            ]);
            $ad->setRelation('user', $row[4]);

            return $ad;
        });

        return compact('requests', 'offers', 'providers');
    }

    private function buildMainFeedAdsQuery(
        $user,
        string $filterType,
        bool $geoEnabled = false,
        ?float $userLat = null,
        ?float $userLng = null,
        int $userRadius = 50,
        ?string $geoCity = null,
        ?string $geoCountry = null
    ) {
        $query = Ad::marketplaceActive()->with('user');

        $this->applyHomeShowcaseVisibility($query, $user);

        if ($filterType === 'offres') {
            $query->where('service_type', 'offre');
        } elseif ($filterType === 'demandes') {
            $query->where('service_type', 'demande');
        }

        if ($geoEnabled) {
            $this->applyAdGeoScope($query, $userLat, $userLng, $userRadius, $geoCity, $geoCountry);
        }

        return $query;
    }

    private function applyAdGeoScope($query, ?float $lat, ?float $lng, int $radius, ?string $city = null, ?string $country = null): void
    {
        $distanceSql = $this->geoDistanceSql();
        $hasCoordinates = $lat !== null && $lng !== null;
        $city = trim((string) $city);
        $country = trim((string) $country);
        $cityNeedles = $this->geoTextNeedles($city);
        $countryNeedles = $this->geoTextNeedles($country);

        if ($hasCoordinates) {
            $query->select('ads.*')
                ->selectRaw("CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN {$distanceSql} ELSE NULL END AS distance", [$lat, $lng, $lat]);
        } else {
            $query->select('ads.*')
                ->selectRaw('NULL AS distance');
        }

        $query->where(function ($q) use ($hasCoordinates, $distanceSql, $lat, $lng, $radius, $cityNeedles, $countryNeedles) {
            if ($hasCoordinates) {
                $q->where(function ($geo) use ($distanceSql, $lat, $lng, $radius) {
                    $geo->whereNotNull('latitude')
                        ->whereNotNull('longitude')
                        ->whereRaw("{$distanceSql} <= ?", [$lat, $lng, $lat, $radius]);
                });
            }

            if (! empty($cityNeedles)) {
                $q->orWhere(function ($text) use ($cityNeedles) {
                    foreach ($cityNeedles as $needle) {
                        $text->orWhereRaw('LOWER(location) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(city) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(address) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(postal_code) LIKE ?', [$needle]);
                    }
                });
            } elseif (! empty($countryNeedles)) {
                $q->orWhere(function ($text) use ($countryNeedles) {
                    foreach ($countryNeedles as $needle) {
                        $text->orWhereRaw('LOWER(country) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(location) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(address) LIKE ?', [$needle]);
                    }
                });
            }
        });
    }

    private function resolveFeedGeoContext(Request $request, $user): array
    {
        $userGeo = $request->attributes->get('user_geo') ?? [];
        $hasProfileCoordinates = $user && method_exists($user, 'hasGeoLocation') && $user->hasGeoLocation();

        $profileCity = trim((string) ($user?->city ?? ''));
        $profileCountry = trim((string) ($user?->country ?? ''));
        $detectedCity = trim((string) ($user?->detected_city ?? ''));
        $detectedCountry = trim((string) ($user?->detected_country ?? ''));

        return [
            'latitude' => $hasProfileCoordinates ? $user->latitude : ($userGeo['latitude'] ?? null),
            'longitude' => $hasProfileCoordinates ? $user->longitude : ($userGeo['longitude'] ?? null),
            'city' => $profileCity !== '' ? $profileCity : ($detectedCity !== '' ? $detectedCity : ($userGeo['city'] ?? null)),
            'country' => $profileCountry !== '' ? $profileCountry : ($detectedCountry !== '' ? $detectedCountry : ($userGeo['country'] ?? null)),
            'source' => $hasProfileCoordinates ? 'profile' : ($userGeo['source'] ?? 'unknown'),
        ];
    }

    private function geoTextNeedles(?string $value): array
    {
        $normalized = mb_strtolower(trim((string) $value));
        if ($normalized === '') {
            return [];
        }

        $terms = [$normalized];

        if (preg_match('/^(.)\1/u', $normalized) === 1) {
            $terms[] = mb_substr($normalized, 1);
        }

        return collect($terms)
            ->filter()
            ->unique()
            ->map(fn ($term) => '%'.$term.'%')
            ->values()
            ->all();
    }

    private function orderMainFeedAds($query, bool $geoApplied, ?float $lat = null, ?float $lng = null): void
    {
        $query->orderByRaw(
            'CASE WHEN (is_boosted = true AND boost_end > ?) OR (is_urgent = true AND (urgent_until IS NULL OR urgent_until > ?)) THEN 0 ELSE 1 END',
            [now(), now()]
        );

        if ($geoApplied) {
            $this->orderByGeoDistance($query, $lat, $lng);
        }

        $query->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc');
    }

    private function orderByGeoDistance($query, ?float $lat, ?float $lng): void
    {
        if ($lat === null || $lng === null) {
            return;
        }

        $distanceSql = $this->geoDistanceSql();

        $query->orderByRaw(
            "CASE WHEN ({$distanceSql}) IS NULL THEN 1 ELSE 0 END",
            [$lat, $lng, $lat]
        )->orderBy('distance');
    }

    private function geoDistanceSql(): string
    {
        return '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))';
    }

    private function buildAdsMapData($ads)
    {
        return collect($ads)
            ->filter(fn ($ad) => $ad->latitude !== null && $ad->longitude !== null)
            ->map(function ($ad) {
                [$publicLatitude, $publicLongitude, $isApproximate] = $this->publicAdCoordinates($ad);
                $marker = [
                    'id' => $ad->id,
                    'title' => $ad->title,
                    'category' => $ad->category,
                    'location' => $ad->location,
                    'price' => $ad->price,
                    'price_type' => $ad->effective_price_type,
                    'formatted_price' => $ad->formatted_price,
                    'latitude' => $publicLatitude,
                    'longitude' => $publicLongitude,
                    'location_is_approximate' => $isApproximate,
                    'is_urgent' => (bool) $ad->is_urgent,
                    'is_boosted' => (bool) $ad->is_boosted,
                    'url' => route('ads.show', $ad),
                ];

                if (isset($ad->distance)) {
                    $marker['distance_km'] = round((float) $ad->distance, 1);
                }

                return $marker;
            })
            ->values();
    }

    private function publicAdCoordinates(Ad $ad): array
    {
        $latitude = (float) $ad->latitude;
        $longitude = (float) $ad->longitude;
        $isApproximate = $ad->service_type === 'demande';

        if ($isApproximate) {
            $latitude = round($latitude, 2);
            $longitude = round($longitude, 2);
        }

        return [$latitude, $longitude, $isApproximate];
    }

    private function buildHomeShowcaseAds(
        string $serviceType,
        $currentUser,
        int $limit = 6,
        ?string $authorKind = null,
        ?float $userLat = null,
        ?float $userLng = null,
        ?int $userRadius = null,
        ?string $geoCity = null,
        ?string $geoCountry = null
    ) {
        $query = Ad::marketplaceActive()
            ->where('service_type', $serviceType)
            ->with('user');

        if ($serviceType === 'demande') {
            $query->withCount('serviceProposals');
        }

        if ($currentUser?->exists) {
            $query->where('user_id', '!=', $currentUser->id);
        }

        $this->applyHomeShowcaseVisibility($query, $currentUser);

        if ($authorKind === 'particulier') {
            $query->whereHas('user', function ($q) {
                $q->where(function ($q2) {
                    $q2->where('user_type', 'particulier')
                        ->orWhereNull('user_type');
                })->where(function ($q2) {
                    $q2->where('is_service_provider', false)
                        ->orWhereNull('is_service_provider');
                });
            });
        }

        if ($authorKind === 'professional') {
            $query->whereHas('user', function ($q) {
                $this->scopeProfessionalUsers($q);
            });
        }

        $geoScoped = ($userLat !== null && $userLng !== null && $userRadius !== null) || $geoCity || $geoCountry;
        if ($geoScoped) {
            $this->applyAdGeoScope($query, $userLat, $userLng, $userRadius ?? 50, $geoCity, $geoCountry);
        }

        $candidates = $query
            ->when($geoScoped && $userLat !== null && $userLng !== null, fn ($q) => $this->orderByGeoDistance($q, (float) $userLat, (float) $userLng))
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(max(120, $limit * 8))
            ->get();

        return $this->feedRankingService->rank($candidates, $currentUser, $limit);
    }

    /**
     * Demandes compatibles qui attendent toujours une première proposition.
     *
     * L'application ne conserve pas encore le nombre de professionnels trouvés
     * au moment exact de la publication. Le signal affiché reste donc volontairement
     * factuel : aucune proposition après deux heures, et non « aucun prestataire trouvé ».
     */
    private function buildPriorityProviderRequests(
        $user,
        ?float $userLat = null,
        ?float $userLng = null,
        ?int $userRadius = null,
        ?string $geoCity = null,
        ?string $geoCountry = null,
        int $limit = 4
    ) {
        if (! $user?->exists || (! $user->isProfessionnel() && ! $user->isServiceProvider())) {
            return collect();
        }

        $viewerCategories = $this->viewerServiceCategories($user);
        if ($viewerCategories === []) {
            return collect();
        }

        $query = Ad::marketplaceActive()
            ->where('service_type', 'demande')
            ->where('user_id', '!=', $user->id)
            ->where('created_at', '<=', now()->subHours(2))
            ->whereIn('category', $viewerCategories)
            ->whereDoesntHave('serviceProposals')
            ->with('user')
            ->withCount('serviceProposals');

        $this->applyHomeShowcaseVisibility($query, $user);

        $geoScoped = ($userLat !== null && $userLng !== null && $userRadius !== null) || $geoCity || $geoCountry;
        if ($geoScoped) {
            $this->applyAdGeoScope($query, $userLat, $userLng, $userRadius ?? 50, $geoCity, $geoCountry);
        }

        return $query
            ->orderByRaw(
                'CASE WHEN is_urgent = true AND (urgent_until IS NULL OR urgent_until > ?) THEN 0 ELSE 1 END',
                [now()]
            )
            ->orderBy('created_at')
            ->take($limit)
            ->get();
    }

    private function applyHomeShowcaseVisibility($query, $currentUser): void
    {
        $viewerCategories = $this->viewerServiceCategories($currentUser);

        $query->where(function ($q) use ($currentUser, $viewerCategories) {
            $q->where('visibility', 'public')
                ->orWhereNull('visibility');

            if ($currentUser && ($currentUser->user_type === 'professionnel' || $currentUser->is_service_provider)) {
                $q->orWhere(function ($targeted) use ($viewerCategories) {
                    $targeted->where('visibility', 'pro_targeted')
                        ->where(function ($targets) use ($viewerCategories) {
                            $targets->whereNull('target_categories')
                                ->orWhereJsonLength('target_categories', 0);

                            foreach ($viewerCategories as $category) {
                                $targets->orWhereJsonContains('target_categories', $category);
                            }
                        });
                });
            }
        });
    }

    private function viewerServiceCategories($user): array
    {
        if (! $user) {
            return [];
        }

        $cacheKey = (string) ($user->getKey() ?? spl_object_id($user));
        if (array_key_exists($cacheKey, $this->viewerServiceCategoryCache)) {
            return $this->viewerServiceCategoryCache[$cacheKey];
        }

        $profileCategories = collect([
            ...((array) ($user->service_subcategories ?? [])),
            ...((array) ($user->pro_service_categories ?? [])),
            $user->service_category,
        ]);

        $serviceCategories = $user->exists
            ? $user->services()
                ->where('is_active', true)
                ->get(['main_category', 'subcategory'])
                ->flatMap(fn ($service) => [$service->main_category, $service->subcategory])
            : collect();

        return $this->viewerServiceCategoryCache[$cacheKey] = $profileCategories
            ->concat($serviceCategories)
            ->filter(fn ($category) => is_string($category) && trim($category) !== '')
            ->map(fn ($category) => trim($category))
            ->unique()
            ->values()
            ->all();
    }

    private function buildHighlightedProfessionalProfiles($currentUser, int $limit = 6)
    {
        $baseQuery = function () use ($currentUser) {
            return \App\Models\User::query()
                ->where(function ($q) {
                    $this->scopeProfessionalUsers($q);
                })
                ->when($currentUser, fn ($q) => $q->where('id', '!=', $currentUser->id))
                ->with(['services' => fn ($q) => $q->where('is_active', true)->limit(2)])
                ->withCount([
                    'verifiedReviewsReceived as verified_reviews_count',
                    'ads as ads_count' => fn ($q) => $q->where('status', 'active'),
                ])
                ->withAvg(['verifiedReviewsReceived as verified_reviews_avg' => fn ($q) => $q], 'rating');
        };

        $rankProfiles = function ($query, int $take) {
            return $query
                ->orderByDesc('verified_reviews_avg')
                ->orderByDesc('verified_reviews_count')
                ->orderByDesc('ads_count')
                ->orderByDesc('updated_at')
                ->take($take)
                ->get()
                ->values();
        };

        $highlightedProfiles = $rankProfiles(
            $baseQuery()->where(function ($q) {
                $this->scopeHighlightedProfiles($q);
            }),
            $limit
        );

        $remainingSlots = max(0, $limit - $highlightedProfiles->count());
        $fallbackProfiles = collect();

        if ($remainingSlots > 0) {
            $fallbackProfiles = $rankProfiles(
                $baseQuery()->whereNotIn('id', $highlightedProfiles->pluck('id')->all()),
                $remainingSlots
            );
        }

        $profiles = $highlightedProfiles
            ->concat($fallbackProfiles)
            ->values();

        $highlightedProfileIds = $highlightedProfiles->pluck('id')->all();

        $topProviderIds = $profiles
            ->filter(fn ($pro) => (int) ($pro->verified_reviews_count ?? 0) > 0 && (float) ($pro->verified_reviews_avg ?? 0) >= 4.5)
            ->sortByDesc(fn ($pro) => ((float) ($pro->verified_reviews_avg ?? 0) * 100000) + (int) ($pro->verified_reviews_count ?? 0))
            ->take(3)
            ->pluck('id')
            ->all();

        return $profiles->map(function ($pro) use ($topProviderIds, $highlightedProfileIds) {
            $pro->setAttribute('is_featured_premium', in_array($pro->id, $highlightedProfileIds, true));
            $pro->setAttribute('is_top_provider', in_array($pro->id, $topProviderIds, true));

            return $pro;
        });
    }

    private function scopeProfessionalUsers($query): void
    {
        $query->where(function ($q) {
            $q->where('user_type', 'professionnel')
                ->orWhere('is_service_provider', true)
                ->orWhere('pro_onboarding_completed', true)
                ->orWhereHas('proSubscriptions', function ($q2) {
                    $q2->where('status', 'active')
                        ->where(function ($q3) {
                            $q3->whereNull('ends_at')
                                ->orWhere('ends_at', '>', now());
                        });
                })
                ->orWhere(function ($q2) {
                    $q2->whereNotNull('plan')
                        ->where('plan', '!=', '')
                        ->whereRaw('LOWER(plan) != ?', ['free'])
                        ->where(function ($q3) {
                            $q3->whereNull('subscription_end')
                                ->orWhere('subscription_end', '>', now());
                        });
                });
        });
    }

    private function scopeHighlightedProfiles($query): void
    {
        $query->where(function ($q) {
            $q->whereHas('ads', function ($q2) {
                $q2->where('status', 'active')
                    ->where('is_boosted', true)
                    ->where('boost_end', '>', now());
            })->orWhereHas('proSubscriptions', function ($q2) {
                $q2->where('status', 'active')
                    ->where(function ($q3) {
                        $q3->whereNull('ends_at')
                            ->orWhere('ends_at', '>', now());
                    });
            })->orWhere(function ($q2) {
                $q2->whereNotNull('plan')
                    ->where('plan', '!=', '')
                    ->whereRaw('LOWER(plan) != ?', ['free'])
                    ->where(function ($q3) {
                        $q3->whereNull('subscription_end')
                            ->orWhere('subscription_end', '>', now());
                    });
            });
        });
    }

    private function buildFeaturedProfessionals($currentUser, int $limit = 8)
    {
        $baseProsFilter = function ($query) use ($currentUser) {
            $query->where(function ($q) {
                $q->where('user_type', 'professionnel')
                    ->orWhere(function ($q2) {
                        $q2->where('user_type', 'particulier')
                            ->where('is_service_provider', true);
                    });
            });

            if ($currentUser) {
                $query->where('id', '!=', $currentUser->id);
            }
        };

        $premiumFilter = function ($query) {
            $query->whereHas('ads', function ($q) {
                $q->where('status', 'active')
                    ->where('is_boosted', true)
                    ->where('boost_end', '>', now());
            })->orWhereHas('proSubscriptions', function ($q) {
                $q->where('status', 'active')
                    ->where(function ($q2) {
                        $q2->whereNull('ends_at')
                            ->orWhere('ends_at', '>', now());
                    });
            })->orWhere(function ($q) {
                $q->whereNotNull('plan')
                    ->where('plan', '!=', '')
                    ->whereRaw('LOWER(plan) != ?', ['free'])
                    ->where(function ($q2) {
                        $q2->whereNull('subscription_end')
                            ->orWhere('subscription_end', '>', now());
                    });
            });
        };

        $rankPros = function ($query, int $take) {
            return $query
                ->withCount([
                    'verifiedReviewsReceived as verified_reviews_count',
                    'ads as ads_count' => fn ($q) => $q->where('status', 'active'),
                ])
                ->withAvg(['verifiedReviewsReceived as verified_reviews_avg' => fn ($q) => $q], 'rating')
                ->orderByDesc('verified_reviews_count')
                ->orderByDesc('ads_count')
                ->orderByDesc('updated_at')
                ->get()
                ->sortByDesc(fn ($pro) => (float) ($pro->verified_reviews_avg ?? 0))
                ->take($take)
                ->values();
        };

        $premiumPros = $rankPros(
            \App\Models\User::query()
                ->where($baseProsFilter)
                ->where($premiumFilter),
            $limit
        )->map(function ($pro) {
            $pro->setAttribute('is_featured_premium', true);

            return $pro;
        });

        $remaining = $limit - $premiumPros->count();

        $nonPremiumPros = collect();
        if ($remaining > 0) {
            $nonPremiumPros = $rankPros(
                \App\Models\User::query()
                    ->where($baseProsFilter)
                    ->whereNot($premiumFilter)
                    ->whereNotIn('id', $premiumPros->pluck('id')->all()),
                $remaining
            )->map(function ($pro) {
                $pro->setAttribute('is_featured_premium', false);

                return $pro;
            });
        }

        $selected = $premiumPros->merge($nonPremiumPros);
        $remaining = $limit - $selected->count();

        if ($remaining > 0) {
            $fallbackPros = \App\Models\User::query()
                ->where($baseProsFilter)
                ->whereNotIn('id', $selected->pluck('id')->all())
                ->withCount(['ads as ads_count' => fn ($q) => $q->where('status', 'active')])
                ->orderByDesc('ads_count')
                ->orderByDesc('updated_at')
                ->take($remaining)
                ->get()
                ->map(function ($pro) {
                    $pro->setAttribute('is_featured_premium', false);

                    return $pro;
                });

            $selected = $selected->merge($fallbackPros);
        }

        return $selected->take($limit);
    }

    /**
     * Construire la liste des proCategories depuis config/categories.php
     * Format : tableau indexé [{name, icon, color, description, subcategories: [{name, icon, count}]}]
     */
    private function buildProCategories(): array
    {
        $counts = $this->activeCategoryCounts('offre');
        $categories = [];
        foreach (\App\Support\MarketplaceCategoryRegistry::enabledServices() as $name => $data) {
            $subs = [];
            foreach (array_slice($data['subcategories'], 0, 5) as $sub) {
                $subs[] = [
                    'name' => $sub,
                    'icon' => $this->getSubcategoryIcon($sub, $data['fa_icon']),
                    'count' => (int) ($counts[$sub] ?? 0),
                ];
            }
            $categories[] = [
                'name' => $name,
                'icon' => $data['fa_icon'],
                'color' => $data['color'],
                'description' => $data['description'],
                'subcategories' => $subs,
            ];
        }

        return $categories;
    }

    /**
     * Icône FontAwesome pour une sous-catégorie, via correspondance de mots-clés.
     */
    private function getSubcategoryIcon(string $sub, string $parentIcon = 'fas fa-tag'): string
    {
        static $map = null;
        if ($map === null) {
            $map = [
                // Bricolage
                'plomb' => 'fas fa-faucet', 'électri' => 'fas fa-bolt', 'peintr' => 'fas fa-paint-roller',
                'menuis' => 'fas fa-hammer', 'carrel' => 'fas fa-border-all', 'maçon' => 'fas fa-cubes',
                'serrur' => 'fas fa-key', 'climati' => 'fas fa-fan', 'chauffag' => 'fas fa-fire',
                'panneau' => 'fas fa-solar-panel', 'rénovation' => 'fas fa-hard-hat', 'plaqui' => 'fas fa-border-all',
                'façad' => 'fas fa-building', 'couvreur' => 'fas fa-home', 'charpen' => 'fas fa-drafting-compass',
                'fenêtr' => 'fas fa-window-maximize', 'vitri' => 'fas fa-window-maximize',
                'domoti' => 'fas fa-robot', 'parquet' => 'fas fa-layer-group',
                'ferronn' => 'fas fa-wrench', 'étanch' => 'fas fa-tint-slash',
                // Jardinage
                'jardin' => 'fas fa-leaf', 'paysag' => 'fas fa-mountain', 'élag' => 'fas fa-tree',
                'piscin' => 'fas fa-swimming-pool', 'arrosage' => 'fas fa-tint', 'tonte' => 'fas fa-fan',
                'clôtur' => 'fas fa-warehouse', 'pépini' => 'fas fa-seedling', 'engazonn' => 'fas fa-seedling',
                'haie' => 'fas fa-cut', 'espace' => 'fas fa-seedling',
                // Nettoyage
                'nettoy' => 'fas fa-broom', 'ménag' => 'fas fa-home', 'repass' => 'fas fa-tshirt',
                'laveur' => 'fas fa-window-maximize', 'haute pression' => 'fas fa-tint',
                'toiture' => 'fas fa-home', 'dératise' => 'fas fa-bug', 'désinf' => 'fas fa-bug',
                'moquett' => 'fas fa-broom', 'copropri' => 'fas fa-building', 'sinistre' => 'fas fa-house-damage',
                'chantier' => 'fas fa-hard-hat', 'autolaveu' => 'fas fa-car',
                // Aide à domicile
                'baby' => 'fas fa-baby', 'soignant' => 'fas fa-user-nurse',
                'nounou' => 'fas fa-baby-carriage', 'scolaire' => 'fas fa-school',
                'livreur' => 'fas fa-box', 'cuisinier' => 'fas fa-utensils',
                'personnes âgée' => 'fas fa-user-nurse', 'garde de nuit' => 'fas fa-moon',
                'compagni' => 'fas fa-heart', 'auxiliaire' => 'fas fa-hands-helping',
                'pet-sitter' => 'fas fa-paw', 'promeneur' => 'fas fa-dog',
                // Cours & Formation
                'professeur' => 'fas fa-chalkboard-teacher', 'coach sport' => 'fas fa-running',
                'musique' => 'fas fa-music', 'langue' => 'fas fa-language',
                'coach de vie' => 'fas fa-brain', 'soutien' => 'fas fa-book-reader',
                'concours' => 'fas fa-trophy', 'arts plast' => 'fas fa-palette',
                'danse' => 'fas fa-user-friends', 'conduite' => 'fas fa-car',
                'formateur' => 'fas fa-chalkboard-teacher', 'yoga' => 'fas fa-pray',
                'méditation' => 'fas fa-peace',
                // Beauté
                'coiffeu' => 'fas fa-cut', 'esthéti' => 'fas fa-spa', 'masseu' => 'fas fa-hands',
                'maquill' => 'fas fa-palette', 'ongul' => 'fas fa-hand-sparkles',
                'barbier' => 'fas fa-user', 'tatoueur' => 'fas fa-pen-nib',
                'diététi' => 'fas fa-apple-alt', 'naturopa' => 'fas fa-leaf',
                'sophrol' => 'fas fa-brain', 'ostéopa' => 'fas fa-bone',
                'réflexol' => 'fas fa-shoe-prints', 'shiatsu' => 'fas fa-hands',
                // Événements
                'dj' => 'fas fa-headphones', 'photograph' => 'fas fa-camera',
                'vidéaste' => 'fas fa-video', 'traiteur' => 'fas fa-utensils',
                'décora' => 'fas fa-paint-brush', 'animat' => 'fas fa-star',
                'wedding' => 'fas fa-ring', 'fleuriste' => 'fas fa-seedling',
                'cérémon' => 'fas fa-microphone', 'régisseur' => 'fas fa-film',
                'sonorisat' => 'fas fa-volume-up', 'musicien' => 'fas fa-music',
                'magicien' => 'fas fa-hat-wizard',
                // Transport
                'déménag' => 'fas fa-truck-moving', 'chauffeur' => 'fas fa-car',
                'coursier' => 'fas fa-bicycle', 'marchandise' => 'fas fa-boxes',
                'poids lourd' => 'fas fa-truck', 'taxi' => 'fas fa-taxi',
                'convoyeur' => 'fas fa-road', 'garde-meuble' => 'fas fa-warehouse',
                'monte-meuble' => 'fas fa-arrow-up', 'vtc' => 'fas fa-car',
                // Informatique
                'développeur web' => 'fas fa-globe', 'développeur mob' => 'fas fa-mobile-alt',
                'technicien' => 'fas fa-desktop', 'réparateur' => 'fas fa-tools',
                'réseau' => 'fas fa-network-wired', 'fibre' => 'fas fa-network-wired',
                'graphiste' => 'fas fa-palette', 'designer' => 'fas fa-palette',
                'community' => 'fas fa-users', 'rédacteur' => 'fas fa-pen', 'seo' => 'fas fa-search',
                'administrat' => 'fas fa-server', 'data' => 'fas fa-database',
                'cybersécuri' => 'fas fa-shield-alt', 'vidéosurveill' => 'fas fa-video',
                'consultant' => 'fas fa-briefcase', 'webmaster' => 'fas fa-globe',
                // Artisanat
                'couturi' => 'fas fa-cut', 'retouche' => 'fas fa-cut',
                'bijout' => 'fas fa-gem', 'joaill' => 'fas fa-gem',
                'potier' => 'fas fa-mortar-pestle', 'cérami' => 'fas fa-mortar-pestle',
                'encadr' => 'fas fa-image', 'restaurateur' => 'fas fa-couch',
                'tapiss' => 'fas fa-couch', 'ébénist' => 'fas fa-tree',
                'sellier' => 'fas fa-suitcase', 'maroquin' => 'fas fa-suitcase',
                'graveur' => 'fas fa-pen-nib', 'vitraill' => 'fas fa-palette',
                'doreur' => 'fas fa-paint-brush', 'luthier' => 'fas fa-guitar', 'relieur' => 'fas fa-book',
                // Santé
                'infirmi' => 'fas fa-syringe', 'kinési' => 'fas fa-user-md',
                'psycholog' => 'fas fa-brain', 'orthophon' => 'fas fa-comments',
                'sage-femme' => 'fas fa-baby', 'ergo' => 'fas fa-wheelchair',
                'podolog' => 'fas fa-shoe-prints', 'dentist' => 'fas fa-tooth',
                'opticien' => 'fas fa-glasses', 'audioproth' => 'fas fa-deaf',
                'pharmacien' => 'fas fa-pills', 'ambulanc' => 'fas fa-ambulance',
                'médico' => 'fas fa-hands-helping',
                // Automobile
                'mécanicien' => 'fas fa-wrench', 'carrossier' => 'fas fa-car',
                'contrôle' => 'fas fa-clipboard-check', 'pneumati' => 'fas fa-circle',
                'débosseleur' => 'fas fa-hammer', 'camping' => 'fas fa-campground',
                'diagnostic' => 'fas fa-stethoscope', 'nautique' => 'fas fa-ship',
                // Immobilier
                'agent immo' => 'fas fa-building', 'architecte' => 'fas fa-drafting-compass',
                'intérieur' => 'fas fa-couch', 'géomètre' => 'fas fa-ruler-combined',
                'topograph' => 'fas fa-ruler-combined', 'métreur' => 'fas fa-ruler',
                'home stag' => 'fas fa-home', 'courtier' => 'fas fa-handshake',
                'patrimoine' => 'fas fa-landmark', 'expert en bât' => 'fas fa-building',
                // Juridique
                'avocat' => 'fas fa-gavel', 'notaire' => 'fas fa-stamp',
                'huissier' => 'fas fa-file-contract', 'comptable' => 'fas fa-calculator',
                'expert-compt' => 'fas fa-file-invoice-dollar', 'secrétaire' => 'fas fa-file-alt',
                'traducteur' => 'fas fa-language', 'interprète' => 'fas fa-language',
                'écrivain' => 'fas fa-pen', 'fiscal' => 'fas fa-file-invoice-dollar',
                'médiateur' => 'fas fa-handshake', 'juridique' => 'fas fa-balance-scale',
                'paie' => 'fas fa-money-check-alt',
                // Agriculture
                'agricult' => 'fas fa-tractor', 'éleveur' => 'fas fa-horse',
                'maraîch' => 'fas fa-carrot', 'apicult' => 'fas fa-bug',
                'vétérin' => 'fas fa-stethoscope', 'toilett' => 'fas fa-shower',
                'maréchal' => 'fas fa-horse', 'arbori' => 'fas fa-tree',
                'viticult' => 'fas fa-wine-glass-alt', 'animalier' => 'fas fa-paw',
                // Restauration
                'chef' => 'fas fa-utensils', 'pâtiss' => 'fas fa-birthday-cake',
                'boulang' => 'fas fa-bread-slice', 'bouch' => 'fas fa-drumstick-bite',
                'poissonn' => 'fas fa-fish', 'sommeli' => 'fas fa-wine-bottle',
                'barman' => 'fas fa-cocktail', 'barmaid' => 'fas fa-cocktail',
                'food truck' => 'fas fa-truck', 'préparateur' => 'fas fa-utensils',
                'chocolat' => 'fas fa-cookie', 'glacier' => 'fas fa-ice-cream',
                // Sports
                'coach personnel' => 'fas fa-running', 'pilates' => 'fas fa-spa',
                'natation' => 'fas fa-swimmer', 'tennis' => 'fas fa-table-tennis',
                'boxe' => 'fas fa-fist-raised', 'arts martiaux' => 'fas fa-user-ninja',
                'physique' => 'fas fa-dumbbell', 'escalade' => 'fas fa-mountain',
                'ski' => 'fas fa-skiing', 'équitation' => 'fas fa-horse', 'running' => 'fas fa-running',
                // Marketplace
                'trajet' => 'fas fa-route', 'longue distance' => 'fas fa-road',
                'aéroport' => 'fas fa-plane-departure', 'événemen' => 'fas fa-calendar-alt',
                'électromén' => 'fas fa-blender', 'meuble' => 'fas fa-couch',
                'vêtement' => 'fas fa-tshirt', 'high-tech' => 'fas fa-mobile-alt',
                'véhicule' => 'fas fa-car', 'immobilier' => 'fas fa-building',
                'cdi' => 'fas fa-file-contract', 'cdd' => 'fas fa-clock',
                'intérim' => 'fas fa-hourglass-half', 'stage' => 'fas fa-graduation-cap',
                'freelance' => 'fas fa-laptop-house', 'temps partiel' => 'fas fa-user-clock',
                'appartement' => 'fas fa-building', 'maison' => 'fas fa-home',
                'voiture' => 'fas fa-car', 'utilitaire' => 'fas fa-truck',
                'vélo' => 'fas fa-bicycle', 'trottinette' => 'fas fa-bicycle',
                'photo' => 'fas fa-camera', 'sono' => 'fas fa-volume-up',
                'mobilier' => 'fas fa-couch', 'sportif' => 'fas fa-football-ball',
                'jeux' => 'fas fa-gamepad', 'console' => 'fas fa-gamepad',
                'téléphone' => 'fas fa-mobile-alt', 'tablette' => 'fas fa-tablet-alt',
                'portefeuille' => 'fas fa-wallet', 'papier' => 'fas fa-wallet',
                'clé' => 'fas fa-key', 'bijou' => 'fas fa-gem', 'montre' => 'fas fa-gem',
                'sac' => 'fas fa-suitcase', 'bagage' => 'fas fa-suitcase',
                'animaux perdus' => 'fas fa-paw', 'disparu' => 'fas fa-user-slash',
                'volé' => 'fas fa-car-crash', 'électronique' => 'fas fa-laptop',
                'lunette' => 'fas fa-glasses', 'autre' => 'fas fa-box',
                'assistant' => 'fas fa-file-alt', 'informatique' => 'fas fa-laptop',
                'rural' => 'fas fa-tree', 'conseil' => 'fas fa-comments',
                'location' => 'fas fa-warehouse',
            ];
        }

        $subLower = mb_strtolower($sub);
        foreach ($map as $keyword => $icon) {
            if (str_contains($subLower, $keyword)) {
                return $icon;
            }
        }

        return $parentIcon;
    }

    private function getCategoriesWithSubcategories()
    {
        $allCategories = \App\Support\MarketplaceCategoryRegistry::enabledAll();
        $colorMap = [
            '#eab308' => 'primary', '#22c55e' => 'success', '#06b6d4' => 'info',
            '#ef4444' => 'danger', '#3b82f6' => 'primary', '#ec4899' => 'pink',
            '#a855f7' => 'purple', '#f97316' => 'orange', '#6366f1' => 'indigo',
            '#14b8a6' => 'teal', '#dc2626' => 'danger', '#475569' => 'secondary',
            '#0891b2' => 'info', '#7c3aed' => 'purple', '#a16207' => 'warning',
            '#ea580c' => 'orange', '#059669' => 'teal', '#10B981' => 'success',
            '#F59E0B' => 'orange', '#3B82F6' => 'primary', '#8B5CF6' => 'purple',
            '#F97316' => 'orange',
        ];

        $result = [];
        foreach ($allCategories as $name => $data) {
            $result[] = [
                'name' => $name,
                'icon' => $data['fa_icon'],
                'color' => $colorMap[$data['color']] ?? 'primary',
                'subcategories' => $data['subcategories'],
                'count' => Ad::where('main_category', $name)->count(),
            ];
        }

        return $result;
    }

    /**
     * Page d'accueil alternative v2 avec nouveau design
     */
    public function indexV2(Request $request)
    {
        // Récupérer l'utilisateur connecté
        $user = Auth::user();

        // Catégories avec sous-catégories
        $categories = $this->getCategoriesWithSubcategories();

        // Annonces à afficher dans le feed
        $feedQuery = Ad::marketplaceActive()
            ->with('user');

        // Filtrer par catégorie si spécifiée
        if ($request->has('category')) {
            $feedQuery->where('category', $request->category);
        }

        // Filtrer par localisation de l'utilisateur
        if ($user->location_preference) {
            $feedQuery->where('location', 'LIKE', "%{$user->location_preference}%");
        }

        // Trier : annonces épinglées d'abord, puis les plus récentes
        $ads = $feedQuery->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Annonces épinglées (boostées) pour la section spéciale
        $pinnedAds = Ad::where('is_pinned', true)
            ->inEnabledCategories()
            ->where('status', 'active')
            ->take(5)
            ->get();

        // Statistiques pour l'utilisateur
        $userStats = [
            'total_ads' => $user->ads()->count(),
            'unread_messages' => 0,
            'available_points' => $user->available_points ?? 0,
            'saved_ads' => 0,
        ];

        // Prestataires suggérés (utilisateurs avec le plus d'annonces actives)
        $suggestedPrestataires = \App\Models\User::select('users.*')
            ->selectRaw('COUNT(ads.id) as ads_count')
            ->join('ads', 'users.id', '=', 'ads.user_id')
            ->where('ads.status', 'active')
            ->where('users.id', '!=', $user->id)
            ->groupBy('users.id')
            ->orderByDesc('ads_count')
            ->take(5)
            ->get();

        return view('feed.index-v2', compact('ads', 'categories', 'pinnedAds', 'userStats', 'suggestedPrestataires'));
    }

    /**
     * Page d'accueil TEST - Nouvelle architecture moderne
     */
    public function indexTest(Request $request)
    {
        $user = Auth::user();

        // Catégories principales pour le mega menu "Trouver un Pro"
        $proCategories = $this->buildProCategories();

        // Categories + subcategories for the mega menu
        $missionCategories = $this->getHomeMegaCategories();

        // Offres de pros (service_type = offre)
        $proOffers = Ad::marketplaceActive()
            ->where('service_type', 'offre')
            ->with('user')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        // Demandes de particuliers (service_type = demande)
        $clientRequests = Ad::marketplaceActive()
            ->where('service_type', 'demande')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        // ===== SECTION "LES DERNIÈRES PÉPITES" - TOUJOURS SANS FILTRE DE CATÉGORIE =====
        $allAdsQuery = Ad::marketplaceActive()->with('user');

        // Appliquer le filtre de type si présent
        $filterType = $request->get('type', 'all'); // all, offres, demandes
        if ($filterType === 'offres') {
            $allAdsQuery->where('service_type', 'offre');
        } elseif ($filterType === 'demandes') {
            $allAdsQuery->where('service_type', 'demande');
        }

        // Toujours trier par épinglé puis récent
        $allAdsQuery->orderBy('is_pinned', 'desc')->orderBy('created_at', 'desc');

        // Récupérer TOUTES les annonces pour la section "Dernières pépites"
        $ads = $allAdsQuery->paginate(12)->withQueryString();

        // ===== SECTION FILTRÉE PAR CATÉGORIE (optionnelle, pour afficher les offres de la catégorie sélectionnée) =====
        $categoryFilteredAds = null;
        if ($request->has('category')) {
            $cat = $request->category;
            $categoryQuery = Ad::marketplaceActive()->with('user');

            // Si c'est une catégorie principale, on inclut toutes ses sous-catégories
            if (isset($missionCategories[$cat])) {
                $subNames = collect($missionCategories[$cat]['subs'])->pluck('name')->toArray();
                $subNames[] = $cat; // Inclure la catégorie mère aussi
                $categoryQuery->whereIn('category', $subNames);
            } else {
                $categoryQuery->where('category', $cat);
            }

            // Appliquer le tri
            $sort = $request->get('sort', 'recent');
            if ($sort === 'popular') {
                $categoryQuery->orderBy('views', 'desc');
            } else {
                $categoryQuery->orderBy('is_pinned', 'desc')->orderBy('created_at', 'desc');
            }

            $categoryFilteredAds = $categoryQuery->get();
        }

        // Handle specific subcategory filter (from pill buttons) using 'search' param as category override
        if ($request->has('search')) {
            // Cette partie reste inchangée pour le filtre de sous-catégorie
            $searchAds = Ad::marketplaceActive()->with('user')
                ->where(function ($q) use ($request) {
                    $q->where('category', $request->search)
                        ->orWhere('title', 'LIKE', '%'.$request->search.'%');
                })
                ->orderBy('created_at', 'desc')
                ->paginate(12)
                ->withQueryString();
            $ads = $searchAds;
        }

        // Toutes les annonces pour le feed principal
        $sort = $request->get('sort', 'recent');

        // Top Pros
        $topPros = \App\Models\User::where('user_type', 'professionnel')
            ->where('id', '!=', $user->id)
            ->withCount(['ads as ads_count' => fn ($q) => $q->where('status', 'active')])
            ->orderByDesc('ads_count')
            ->take(6)
            ->get();

        // Premium Professionals (users with active subscription OR boosted ads)
        // Priorité 1: Utilisateurs avec annonces boostées
        $usersWithBoostedAds = \App\Models\User::where('user_type', 'professionnel')
            ->whereHas('ads', function ($q) {
                $q->where('status', 'active')
                    ->where('is_boosted', true)
                    ->where('boost_end', '>', now());
            })
            ->with(['ads' => fn ($q) => $q->where('status', 'active')
                ->where('is_boosted', true)
                ->where('boost_end', '>', now())
                ->latest()
                ->take(1)])
            ->withCount(['ads as ads_count' => fn ($q) => $q->where('status', 'active')])
            ->get();

        // Priorité 2: Utilisateurs avec abonnement premium (sans doublon)
        $boostedUserIds = $usersWithBoostedAds->pluck('id')->toArray();

        $subscribedPros = \App\Models\User::where('user_type', 'professionnel')
            ->whereNotIn('id', $boostedUserIds)
            ->where(function ($q) {
                $q->whereNotNull('plan')
                    ->where('plan', '!=', '')
                    ->whereRaw('LOWER(plan) != ?', ['free'])
                    ->where(function ($q2) {
                        $q2->whereNull('subscription_end')
                            ->orWhere('subscription_end', '>', now());
                    });
            })
            ->withCount(['ads as ads_count' => fn ($q) => $q->where('status', 'active')])
            ->with(['ads' => fn ($q) => $q->where('status', 'active')->latest()->take(1)])
            ->inRandomOrder()
            ->take(20 - count($boostedUserIds))
            ->get();

        // Combiner les deux groupes (boosted first, then subscribed)
        $premiumPros = $usersWithBoostedAds->merge($subscribedPros);

        // Stats utilisateur
        $userStats = [
            'total_ads' => $user->ads()->count(),
            'active_ads' => $user->ads()->where('status', 'active')->count(),
            'unread_messages' => $user->unreadMessagesCount(),
            'available_points' => $user->available_points ?? 0,
            'saved_ads' => $user->savedAds()->count(),
        ];

        return view('feed.index', compact(
            'proCategories',
            'missionCategories',
            'proOffers',
            'clientRequests',
            'ads',
            'topPros',
            'premiumPros',
            'userStats',
            'sort',
            'filterType'
        ));
    }

    private function getHomeMegaCategories()
    {
        $allCategories = \App\Support\MarketplaceCategoryRegistry::enabledAll();
        $counts = $this->activeCategoryCounts();
        $categoriesWithSubs = [];

        foreach ($allCategories as $name => $data) {
            $subs = [];
            foreach ($data['subcategories'] as $sub) {
                $subs[] = [
                    'name' => $sub,
                    'icon' => $this->getSubcategoryIcon($sub, $data['fa_icon']),
                ];
            }
            $categoriesWithSubs[$name] = [
                'icon' => $data['fa_icon'],
                'color' => $data['color'],
                'subs' => $subs,
            ];
        }

        foreach ($categoriesWithSubs as &$category) {
            $total = 0;
            foreach ($category['subs'] as &$sub) {
                $subCount = (int) ($counts[$sub['name']] ?? 0);
                $sub['count'] = $subCount;
                $total += $subCount;
            }
            $category['total'] = $total;
        }
        unset($category, $sub);

        return $categoriesWithSubs;
    }

    private function activeCategoryCounts(?string $serviceType = null): array
    {
        $loadCounts = function () use ($serviceType) {
            return Ad::query()
                ->marketplaceActive()
                ->when($serviceType, fn ($query) => $query->where('service_type', $serviceType))
                ->select('category', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('category')
                ->pluck('aggregate', 'category')
                ->map(fn ($count) => (int) $count)
                ->all();
        };

        if (app()->environment('testing')) {
            return $loadCounts();
        }

        return Cache::remember(
            'feed:active-category-counts:v2:'.($serviceType ?? 'all'),
            now()->addMinutes(5),
            $loadCounts
        );
    }

    /**
     * AJAX endpoint for filtering ads by category/subcategory
     */
    public function filterAds(Request $request)
    {
        $category = $request->get('category');
        $subcategory = $request->get('subcategory');
        $sort = $request->get('sort', 'recent');
        $location = $request->get('location');
        $priceMin = $request->get('price_min');
        $priceMax = $request->get('price_max');
        $filterType = $request->get('type', 'all');
        if (! in_array($filterType, ['all', 'offres', 'demandes'], true)) {
            $filterType = 'all';
        }
        $radius = $request->get('radius');
        $userLat = $request->get('lat');
        $userLng = $request->get('lng');
        $missionCategories = $this->getHomeMegaCategories();

        // Récupérer la géolocalisation depuis le profil, le middleware ou les paramètres
        $user = Auth::user();
        $geoContext = $this->resolveFeedGeoContext($request, $user);
        if (! $userLat) {
            $userLat = $geoContext['latitude'];
            $userLng = $geoContext['longitude'];
        }
        if (! $radius && Auth::check()) {
            $radius = Auth::user()->geo_radius ?? 50;
        }
        $radius = (int) ($radius ?: 50);
        $geoCity = $geoContext['city'];
        $geoCountry = $geoContext['country'];

        $scope = $request->get('scope', 'all');
        if (! in_array($scope, ['nearby', 'all'], true)) {
            $scope = 'all';
        }

        $hasLocalReference = ($userLat !== null && $userLng !== null) || $geoCity || $geoCountry;
        $geoApplied = (bool) ($hasLocalReference && ! $location && $scope === 'nearby');
        $geoFallbackUsed = false;

        $buildFilteredAdsQuery = function (bool $nearby) use (
            $user,
            $category,
            $subcategory,
            $location,
            $priceMin,
            $priceMax,
            $filterType,
            $missionCategories,
            $userLat,
            $userLng,
            $radius,
            $geoCity,
            $geoCountry
        ) {
            $query = $this->buildMainFeedAdsQuery(
                $user,
                $filterType,
                $nearby,
                $nearby && $userLat !== null ? (float) $userLat : null,
                $nearby && $userLng !== null ? (float) $userLng : null,
                $radius,
                $nearby ? $geoCity : null,
                $nearby ? $geoCountry : null
            );

            if ($category && $category !== 'all') {
                if (isset($missionCategories[$category])) {
                    $subNames = collect($missionCategories[$category]['subs'])->pluck('name')->toArray();
                    $subNames[] = $category;
                    $query->whereIn('category', $subNames);
                } else {
                    $query->where('category', $category);
                }
            }

            if ($subcategory) {
                $query->where(function ($q) use ($subcategory) {
                    $q->where('category', $subcategory)
                        ->orWhere('title', 'LIKE', '%'.$subcategory.'%');
                });
            }

            if ($location) {
                $query->where(function ($q) use ($location) {
                    $locationNeedle = '%'.mb_strtolower(trim((string) $location)).'%';

                    $q->whereRaw('LOWER(location) LIKE ?', [$locationNeedle])
                        ->orWhereRaw('LOWER(city) LIKE ?', [$locationNeedle])
                        ->orWhereRaw('LOWER(address) LIKE ?', [$locationNeedle])
                        ->orWhereRaw('LOWER(country) LIKE ?', [$locationNeedle])
                        ->orWhereRaw('LOWER(postal_code) LIKE ?', [$locationNeedle]);
                });
            }

            if ($priceMin) {
                $query->where('price', '>=', $priceMin);
            }
            if ($priceMax) {
                $query->where('price', '<=', $priceMax);
            }

            return $query;
        };

        $query = $buildFilteredAdsQuery($geoApplied);

        if ($geoApplied && (clone $query)->count() === 0) {
            $query = $buildFilteredAdsQuery(false);
            $geoApplied = false;
            $geoFallbackUsed = true;
        }

        // Apply sorting — always prioritize boosted and urgent ads first
        $query->orderByRaw(
            'CASE WHEN (is_boosted = true AND boost_end > ?) OR (is_urgent = true AND (urgent_until IS NULL OR urgent_until > ?)) THEN 0 ELSE 1 END',
            [now(), now()]
        );
        if ($geoApplied) {
            $this->orderByGeoDistance($query, $userLat !== null ? (float) $userLat : null, $userLng !== null ? (float) $userLng : null);
        }
        switch ($sort) {
            case 'urgent':
                $query->orderBy('is_urgent', 'desc');
                break;
            case 'recommended':
                $query->orderBy('is_pinned', 'desc')->orderBy('views', 'desc');
                break;
            case 'proximity':
                break;
            case 'recent':
            default:
                if (! $geoApplied) {
                    $query->orderBy('is_pinned', 'desc');
                }
                break;
        }

        $query->orderBy('created_at', 'desc');

        $mapMarkers = $this->buildAdsMapData(
            (clone $query)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->limit(120)
                ->get()
        );

        $ads = $query->withCount('comments')->paginate(12);

        // Return JSON if requested
        if ($request->get('format') === 'json' || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'geo_applied' => $geoApplied,
                'geo_fallback_used' => $geoFallbackUsed,
                'scope' => $scope,
                'radius' => $radius,
                'map_markers' => $mapMarkers,
                'ads' => $ads->map(function ($ad) use ($geoApplied) {
                    [$publicLatitude, $publicLongitude, $isApproximate] = $ad->latitude !== null && $ad->longitude !== null
                        ? $this->publicAdCoordinates($ad)
                        : [null, null, false];
                    $data = [
                        'id' => $ad->id,
                        'title' => $ad->title,
                        'description' => $ad->description,
                        'category' => $ad->category,
                        'service_type' => $ad->service_type,
                        'price_type' => $ad->effective_price_type,
                        'price' => $ad->price,
                        'formatted_price' => $ad->formatted_price,
                        'location' => $ad->location,
                        'city' => $ad->city,
                        'photos' => $ad->photos,
                        'is_urgent' => $ad->is_urgent,
                        'is_boosted' => (bool) $ad->is_boosted,
                        'boost_end' => $ad->boost_end ? $ad->boost_end->toIso8601String() : null,
                        'created_at_human' => $ad->created_at->diffForHumans(),
                        'created_at_date' => $ad->created_at->format('d/m/Y'),
                        'published_at' => $ad->created_at->format('d/m/Y'),
                        'user_id' => $ad->user_id,
                        'comments_count' => $ad->comments_count,
                        'shares_count' => $ad->shares_count ?? 0,
                        'latitude' => $publicLatitude,
                        'longitude' => $publicLongitude,
                        'location_is_approximate' => $isApproximate,
                        'url' => route('ads.show', $ad),
                        'reply_restriction' => $ad->reply_restriction ?? 'everyone',
                        'visibility' => $ad->visibility ?? 'public',
                        'user' => $ad->user ? [
                            'id' => $ad->user->id,
                            'name' => $ad->user->name,
                            'avatar' => $ad->user->avatar,
                            'is_verified' => (bool) $ad->user->is_verified,
                        ] : null,
                    ];
                    // Ajouter la distance si filtre géo actif
                    if ($geoApplied && isset($ad->distance)) {
                        $data['distance_km'] = round($ad->distance, 1);
                    }

                    return $data;
                }),
                'total' => $ads->total(),
                'current_page' => $ads->currentPage(),
                'last_page' => $ads->lastPage(),
            ]);
        }

        // Return partial HTML view
        return view('feed.partials.ads-grid', compact('ads'));
    }

    /**
     * Get subcategories for a given category (AJAX)
     */
    public function getSubcategories(Request $request)
    {
        $category = $request->get('category');
        $missionCategories = $this->getHomeMegaCategories();

        if ($category && isset($missionCategories[$category])) {
            return response()->json([
                'success' => true,
                'subcategories' => $missionCategories[$category]['subs'],
                'category' => $category,
            ]);
        }

        return response()->json(['success' => false, 'subcategories' => []]);
    }

    /**
     * Get premium professionals filtered by category/subcategory (AJAX)
     * Inclut les professionnels ET les particuliers prestataires
     */
    public function getProfessionalsByCategory(Request $request)
    {
        $category = $request->get('category');
        $subcategory = $request->get('subcategory');
        $missionCategories = $this->getHomeMegaCategories();

        // Build the list of categories to search for
        $categoriesToSearch = [];

        if ($subcategory) {
            // Specific subcategory selected
            $categoriesToSearch[] = $subcategory;
        } elseif ($category && isset($missionCategories[$category])) {
            // Main category selected - include all its subcategories
            $categoriesToSearch = collect($missionCategories[$category]['subs'])->pluck('name')->toArray();
            $categoriesToSearch[] = $category;
        }

        // Base query: professionals OR particuliers prestataires
        $baseQuery = function () {
            return \App\Models\User::where(function ($q) {
                $q->where('user_type', 'professionnel')
                    ->orWhere(function ($q2) {
                        $q2->where('user_type', 'particulier')
                            ->where('is_service_provider', true);
                    });
            });
        };

        // Get professionals with active ads in the selected category
        $query = $baseQuery();

        // Filter by category if specified
        if (! empty($categoriesToSearch)) {
            $query->where(function ($q) use ($categoriesToSearch) {
                // Soit ils ont des annonces dans cette catégorie
                $q->whereHas('ads', function ($adsQ) use ($categoriesToSearch) {
                    $adsQ->marketplaceActive()
                        ->whereIn('category', $categoriesToSearch);
                })
                // Soit ils ont des services enregistrés dans cette catégorie (particuliers prestataires)
                    ->orWhereHas('services', function ($servQ) use ($categoriesToSearch) {
                        $servQ->where('is_active', true)
                            ->where(function ($catQ) use ($categoriesToSearch) {
                                $catQ->whereIn('main_category', $categoriesToSearch)
                                    ->orWhereIn('subcategory', $categoriesToSearch);
                            });
                    });
            });
        }

        // Get users with boosted ads first
        $usersWithBoostedAds = (clone $query)
            ->whereHas('ads', fn ($q) => $q->marketplaceActive()->where('is_boosted', true)->where('boost_end', '>', now()))
            ->with(['ads' => function ($q) use ($categoriesToSearch) {
                $q->marketplaceActive();
                if (! empty($categoriesToSearch)) {
                    $q->whereIn('category', $categoriesToSearch);
                }
                $q->latest()->take(1);
            }, 'services' => fn ($q) => $q->where('is_active', true)->limit(3)])
            ->withCount(['ads as ads_count' => fn ($q) => $q->marketplaceActive()])
            ->orderByDesc('updated_at')
            ->take(20)
            ->get();

        $boostedUserIds = $usersWithBoostedAds->pluck('id')->toArray();

        // Get subscribed professionals (not already in boosted)
        $remainingPremiumSlots = max(0, 20 - count($boostedUserIds));
        $subscribedPros = $remainingPremiumSlots === 0 ? collect() : (clone $query)
            ->whereNotIn('id', $boostedUserIds)
            ->where(function ($q) {
                $q->whereNotNull('plan')
                    ->where('plan', '!=', '')
                    ->whereRaw('LOWER(plan) != ?', ['free'])
                    ->where(function ($q2) {
                        $q2->whereNull('subscription_end')
                            ->orWhere('subscription_end', '>', now());
                    });
            })
            ->with(['ads' => function ($q) use ($categoriesToSearch) {
                $q->marketplaceActive();
                if (! empty($categoriesToSearch)) {
                    $q->whereIn('category', $categoriesToSearch);
                }
                $q->latest()->take(1);
            }, 'services' => fn ($q) => $q->where('is_active', true)->limit(3)])
            ->withCount(['ads as ads_count' => fn ($q) => $q->marketplaceActive()])
            ->inRandomOrder()
            ->take($remainingPremiumSlots)
            ->get();

        // Get new service providers (particuliers prestataires récents)
        $existingIds = array_merge($boostedUserIds, $subscribedPros->pluck('id')->toArray());

        $newProviders = $baseQuery()
            ->where('is_service_provider', true)
            ->whereNotIn('id', $existingIds);

        if (! empty($categoriesToSearch)) {
            $newProviders->whereHas('services', function ($q) use ($categoriesToSearch) {
                $q->where('is_active', true)
                    ->where(function ($catQ) use ($categoriesToSearch) {
                        $catQ->whereIn('main_category', $categoriesToSearch)
                            ->orWhereIn('subcategory', $categoriesToSearch);
                    });
            });
        }

        $newProviders = $newProviders
            ->with(['services' => fn ($q) => $q->where('is_active', true)->limit(3)])
            ->withCount(['ads as ads_count' => fn ($q) => $q->marketplaceActive()])
            ->orderByDesc('service_provider_since')
            ->take(10)
            ->get();

        $premiumPros = $usersWithBoostedAds->merge($subscribedPros)->merge($newProviders);

        // Return JSON if requested
        if ($request->get('format') === 'json' || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'professionals' => $premiumPros->map(function ($pro) {
                    return [
                        'id' => $pro->id,
                        'name' => $pro->name,
                        'avatar' => $pro->avatar,
                        'user_type' => $pro->user_type,
                        'bio' => $pro->bio,
                        'profession' => $pro->profession,
                        'service_category' => $pro->service_category,
                        'plan' => $pro->plan,
                        'hourly_rate' => $pro->hourly_rate ?? null,
                        'rating' => $pro->reviews_avg_rating ?? 0,
                        'reviews_count' => $pro->reviews_count ?? 0,
                        'ads_count' => $pro->ads_count ?? 0,
                        'location' => $pro->location_preference ?? null,
                        'pro_onboarding_completed' => (bool) $pro->pro_onboarding_completed,
                        'has_active_pro_subscription' => $pro->hasActiveProSubscription(),
                    ];
                }),
                'category' => $category,
                'subcategory' => $subcategory,
                'total' => $premiumPros->count(),
            ]);
        }

        // Return partial HTML for AJAX, or full page for direct access
        if ($request->ajax() || $request->get('format') === 'html') {
            return view('feed.partials.premium-pros-items', compact('premiumPros', 'category', 'subcategory'));
        }

        return view('feed.professionals', [
            'premiumPros' => $premiumPros,
            'category' => $category,
            'subcategory' => $subcategory,
            'categories' => $missionCategories,
        ]);
    }

    /**
     * Stocker la position du navigateur (API Geolocation du browser)
     * Appelé en AJAX depuis le frontend
     */
    public function storeBrowserLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $lat = (float) $request->latitude;
        $lng = (float) $request->longitude;

        // Stocker en session
        session([
            'browser_geolocation' => [
                'latitude' => $lat,
                'longitude' => $lng,
                'detected_at' => now()->toISOString(),
            ],
            'user_geolocation' => [
                'latitude' => $lat,
                'longitude' => $lng,
                'source' => 'browser',
                'detected_at' => now()->toISOString(),
            ],
        ]);

        // Mettre à jour le profil utilisateur
        $user = Auth::user();
        if ($user) {
            // Reverse geocode pour obtenir la ville
            $geoService = app(\App\Services\GeocodingService::class);
            $reverseResult = $geoService->reverseGeocode($lat, $lng);

            $user->update([
                'latitude' => $lat,
                'longitude' => $lng,
                'detected_city' => $reverseResult['city'] ?? null,
                'detected_country' => $reverseResult['country'] ?? null,
                'geo_source' => 'browser',
                'geo_detected_at' => now(),
            ]);

            // Mettre à jour la session avec la ville
            session([
                'user_geolocation' => [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'city' => $reverseResult['city'] ?? null,
                    'country' => $reverseResult['country'] ?? null,
                    'source' => 'browser',
                    'detected_at' => now()->toISOString(),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'city' => $reverseResult['city'] ?? null,
            'country' => $reverseResult['country'] ?? null,
        ]);
    }

    /**
     * Mettre à jour le rayon de recherche préféré de l'utilisateur
     */
    public function updateRadius(Request $request)
    {
        $request->validate([
            'radius' => 'required|integer|min:5|max:500',
        ]);

        $user = Auth::user();
        $user->update(['geo_radius' => (int) $request->radius]);

        session()->put('user_geolocation.radius', (int) $request->radius);

        return response()->json([
            'success' => true,
            'radius' => (int) $request->radius,
        ]);
    }
}

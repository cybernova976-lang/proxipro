<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Services\RecommendationService;
use App\Services\SavedSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedController extends Controller
{
    public function __construct(
        private RecommendationService $recommendationService,
        private SavedSearchService $savedSearchService
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

        // Catégories principales pour le mega menu "Trouver un Pro"
        // Catégories principales pour le mega menu "Trouver un Pro"
        // Construites dynamiquement depuis config/categories.php
        $proCategories = $this->buildProCategories();

        // Categories + subcategories for the mega menu
        $missionCategories = $this->getHomeMegaCategories();

        // Offres de pros - filtrées par proximité
        $proOffersQuery = Ad::where('status', 'active')
            ->where('service_type', 'offre')
            ->with('user');
        if ($useNearbyScope) {
            $this->applyAdGeoScope($proOffersQuery, $userLat !== null ? (float) $userLat : null, $userLng !== null ? (float) $userLng : null, $userRadius, $geoCity, $geoCountry);
        }
        $this->orderMainFeedAds($proOffersQuery, $useNearbyScope, $userLat !== null ? (float) $userLat : null, $userLng !== null ? (float) $userLng : null);
        $proOffers = $proOffersQuery->take(8)->get();

        // Demandes de particuliers - filtrées par proximité
        $clientRequestsQuery = Ad::where('status', 'active')
            ->where('service_type', 'demande')
            ->with('user');
        if ($useNearbyScope) {
            $this->applyAdGeoScope($clientRequestsQuery, $userLat !== null ? (float) $userLat : null, $userLng !== null ? (float) $userLng : null, $userRadius, $geoCity, $geoCountry);
        }
        $this->orderMainFeedAds($clientRequestsQuery, $useNearbyScope, $userLat !== null ? (float) $userLat : null, $userLng !== null ? (float) $userLng : null);
        $clientRequests = $clientRequestsQuery->take(8)->get();

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
                $geoCountry,
                false
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
            $categoryQuery = Ad::where('status', 'active')->with('user');

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
                $query = Ad::where('status', 'active')->with('user')
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
            ->withCount(['ads as ads_count' => fn ($q) => $q->where('status', 'active')])
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
            ->whereHas('ads', fn ($q) => $q->where('is_boosted', true)->where('boost_end', '>', now()))
            ->with(['ads' => fn ($q) => $q->where('status', 'active')
            ->where('is_boosted', true)
            ->where('boost_end', '>', now())
            ->latest()
            ->take(1)])
            ->withCount(['ads as ads_count' => fn ($q) => $q->where('status', 'active')])
            ->get();

        $boostedUserIds = $usersWithBoostedAds->pluck('id')->toArray();

        $subscribedPros = \App\Models\User::where(function ($q) {
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
            ->withCount(['ads as ads_count' => fn ($q) => $q->where('status', 'active')])
            ->with(['ads' => fn ($q) => $q->where('status', 'active')->latest()->take(1)])
            ->inRandomOrder()
            ->take(20 - count($boostedUserIds))
            ->get();

        // Ajouter aussi les particuliers prestataires récents (même sans abonnement premium)
        $newProviders = \App\Models\User::where('user_type', 'particulier')
            ->where('is_service_provider', true)
            ->whereNotIn('id', $boostedUserIds)
            ->whereNotIn('id', $subscribedPros->pluck('id')->toArray())
            ->withCount(['ads as ads_count' => fn ($q) => $q->where('status', 'active')])
            ->with(['services' => fn ($q) => $q->where('is_active', true)->limit(3)])
            ->orderByDesc('service_provider_since')
            ->take(10)
            ->get();

        $premiumPros = $usersWithBoostedAds->merge($subscribedPros)->merge($newProviders);

        // Vitrine sous les annonces: max 2 lignes (8 cartes)
        // Priorité: premium les mieux notés -> non premium les mieux notés -> fallback pour compléter
        $featuredProfessionals = $this->buildFeaturedProfessionals($user, 8);

        // Données JSON-ready pour injection JS du bloc "Professionnels à la une"
        $featuredProsJson = $featuredProfessionals->take(8)->map(function ($pro) {
            return [
                'id' => $pro->id,
                'name' => $pro->name,
                'avatar' => $pro->avatar,
                'profession' => $pro->profession ?? $pro->bio ?? 'Professionnel',
                'is_pro' => method_exists($pro, 'hasActiveProSubscription') ? $pro->hasActiveProSubscription() : false,
                'verified_reviews_avg' => $pro->verified_reviews_avg ?? null,
                'verified_reviews_count' => $pro->verified_reviews_count ?? 0,
            ];
        })->values();

        // Annonces boostées (sponsorisées) - payées par les clients
        $boostedAds = Ad::where('status', 'active')
            ->where('is_boosted', true)
            ->where('boost_end', '>', now())
            ->with('user')
            ->orderBy('boost_type', 'desc') // premium > standard
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Annonces à la une : les 10 dernières publications (hors boostées/urgentes, affichées séparément)
        $featuredAds = Ad::where('status', 'active')
            ->with('user')
            ->where(function ($q) {
                $q->where('is_boosted', false)
                    ->orWhereNull('boost_end')
                    ->orWhere('boost_end', '<=', now());
            })
            ->where(function ($q) {
                $q->where('is_urgent', false)
                    ->orWhere('is_urgent', 0);
            })
            ->orderByRaw("
                CASE WHEN user_id IN (
                    SELECT user_id FROM pro_subscriptions WHERE status = 'active'
                ) THEN 0 ELSE 1 END
            ")
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Annonces urgentes (premium uniquement)
        $urgentAds = Ad::where('status', 'active')
            ->where('is_urgent', true)
            ->where(function ($q) {
                $q->whereNull('urgent_until')
                    ->orWhere('urgent_until', '>', now());
            })
            ->with('user')
            ->whereNotIn('id', $boostedAds->pluck('id'))
            ->orderBy('sidebar_priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

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

        $homeShowcaseAdIds = $homePersonalRequests
            ->pluck('id')
            ->merge($homeProfessionalOffers->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // Annonces sidebar (toutes les urgent + boostées combinées pour le sidebar gauche)
        $sidebarAds = $urgentAds->merge($boostedAds)
            ->sortByDesc(function ($ad) {
                // Trier: urgent premium d'abord, puis boosté VIP, puis boosté premium, puis le reste
                $priority = 0;
                if ($ad->is_urgent) {
                    $priority += 100;
                }
                if ($ad->is_boosted) {
                    if ($ad->boost_type === 'vip') {
                        $priority += 50;
                    } elseif ($ad->boost_type === 'premium') {
                        $priority += 30;
                    } else {
                        $priority += 10;
                    }
                }

                return $priority;
            })
            ->values()
            ->take(15);

        // Stats utilisateur
        $userStats = [
            'total_ads' => $user->ads()->count(),
            'active_ads' => $user->ads()->where('status', 'active')->count(),
            'unread_messages' => $user->unreadMessagesCount(),
            'available_points' => $user->available_points ?? 0,
            'saved_ads' => $user->savedAds()->count(),
            'profile_views' => $user->profile_views ?? 0,
        ];

        // Catégories populaires avec compteurs
        $popularCategories = collect($missionCategories)
            ->sortByDesc('total')
            ->take(6)
            ->map(function ($cat, $name) {
                return ['name' => $name, 'icon' => $cat['icon'] ?? 'fas fa-folder', 'color' => $cat['color'] ?? '#64748b', 'total' => $cat['total'] ?? 0];
            })->values();

        // ===== PRO ONBOARDING MODAL DATA =====
        $showOnboardingModal = false;
        $onboardingCategories = [];
        $proSuggestions = [];
        $proProfileCompletion = 0;

        if ($user && ($user->isProfessionnel() || $user->isServiceProvider())) {
            $showOnboardingModal = $user->shouldShowOnboardingModal();
            $proSuggestions = $user->getProSuggestions();
            $proProfileCompletion = $user->getProProfileCompletionPercent();

            // Always load categories for pro users (needed by onboarding modal
            // when opened from suggestions, not just auto-show)
            $proCtrl = new \App\Http\Controllers\ProDashboardController;
            $onboardingCategories = $proCtrl->getServiceCategoriesPublic();
        }

        $recommendedAds = $this->recommendationService->getFeedRecommendations($user, [
            'latitude' => $userLat,
            'longitude' => $userLng,
            'radius' => $userRadius,
            'scope' => $feedScope,
        ]);

        $adsMapData = $this->buildAdsMapData(
            (clone $feedMapQuery)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->limit(120)
                ->get()
        );

        $currentSearchSnapshot = $this->savedSearchService->buildSnapshot($request->all(), $user, [
            'city' => $geoCity,
            'country' => $geoCountry,
            'latitude' => $userLat,
            'longitude' => $userLng,
            'radius' => $userRadius,
        ]);
        $existingSavedSearch = $user
            ? $this->savedSearchService->findExistingSearch($user, $currentSearchSnapshot)
            : null;

        return view('feed.index', compact(
            'proCategories',
            'missionCategories',
            'proOffers',
            'clientRequests',
            'ads',
            'featuredAds',
            'boostedAds',
            'urgentAds',
            'sidebarAds',
            'topPros',
            'premiumPros',
            'featuredProfessionals',
            'featuredProsJson',
            'homePersonalRequests',
            'homeProfessionalOffers',
            'homeProfessionalProfiles',
            'homeShowcaseAdIds',
            'userStats',
            'popularCategories',
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
            'recommendedAds',
            'adsMapData',
            'currentSearchSnapshot',
            'existingSavedSearch'
        ));
    }

    private function buildMainFeedAdsQuery(
        $user,
        string $filterType,
        bool $geoEnabled = false,
        ?float $userLat = null,
        ?float $userLng = null,
        int $userRadius = 50,
        ?string $geoCity = null,
        ?string $geoCountry = null,
        bool $requireFeaturedVisibility = true
    ) {
        $query = Ad::where('status', 'active')->with('user');

        if ($requireFeaturedVisibility) {
            // Annonces visibles sur le feed principal : payées, boostées ou portées par un compte abonné.
            $query->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('is_boosted', true)
                        ->where('boost_end', '>', now());
                })
                    ->orWhereHas('user', function ($q3) {
                        $q3->whereNotNull('plan')
                            ->where('plan', '!=', '')
                            ->whereRaw('LOWER(plan) != ?', ['free'])
                            ->where(function ($q4) {
                                $q4->whereNull('subscription_end')
                                    ->orWhere('subscription_end', '>', now());
                            });
                    });
            });
        }

        $query->where(function ($q) use ($user) {
            $q->where('visibility', 'public')
                ->orWhereNull('visibility');

            if ($user && ($user->user_type === 'professionnel' || $user->is_service_provider)) {
                $q->orWhere('visibility', 'pro_targeted');
            }

            $q->orWhere(function ($q2) {
                $q2->where('is_boosted', true)->where('boost_end', '>', now());
            });
        });

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
                $marker = [
                    'id' => $ad->id,
                    'title' => $ad->title,
                    'category' => $ad->category,
                    'location' => $ad->location,
                    'price' => $ad->price,
                    'price_type' => $ad->effective_price_type,
                    'formatted_price' => $ad->formatted_price,
                    'latitude' => (float) $ad->latitude,
                    'longitude' => (float) $ad->longitude,
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
        $query = Ad::where('status', 'active')
            ->where('service_type', $serviceType)
            ->with('user');

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

        return $query
            ->orderByRaw(
                'CASE WHEN is_urgent = true AND (urgent_until IS NULL OR urgent_until > ?) THEN 0 ELSE 1 END',
                [now()]
            )
            ->orderByRaw(
                'CASE WHEN is_boosted = true AND boost_end > ? THEN 0 ELSE 1 END',
                [now()]
            )
            ->orderByRaw("CASE WHEN boost_type = 'vip' THEN 0 WHEN boost_type = 'premium' THEN 1 ELSE 2 END")
            ->when($geoScoped && $userLat !== null && $userLng !== null, fn ($q) => $this->orderByGeoDistance($q, (float) $userLat, (float) $userLng))
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->values();
    }

    private function applyHomeShowcaseVisibility($query, $currentUser): void
    {
        $query->where(function ($q) use ($currentUser) {
            $q->where('visibility', 'public')
                ->orWhereNull('visibility');

            if ($currentUser && ($currentUser->user_type === 'professionnel' || $currentUser->is_service_provider)) {
                $q->orWhere('visibility', 'pro_targeted');
            }

            $q->orWhere(function ($q2) {
                $q2->where('is_boosted', true)
                    ->where('boost_end', '>', now());
            });
        });
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
        $categories = [];
        foreach (config('categories.services') as $name => $data) {
            $subs = [];
            foreach (array_slice($data['subcategories'], 0, 5) as $sub) {
                $subs[] = [
                    'name' => $sub,
                    'icon' => $this->getSubcategoryIcon($sub, $data['fa_icon']),
                    'count' => Ad::where('category', $sub)->where('service_type', 'offre')->count(),
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
        $allCategories = array_merge(config('categories.services'), config('categories.marketplace'));
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
        $feedQuery = Ad::where('status', 'active')
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
        $proOffers = Ad::where('status', 'active')
            ->where('service_type', 'offre')
            ->with('user')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        // Demandes de particuliers (service_type = demande)
        $clientRequests = Ad::where('status', 'active')
            ->where('service_type', 'demande')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        // ===== SECTION "LES DERNIÈRES PÉPITES" - TOUJOURS SANS FILTRE DE CATÉGORIE =====
        $allAdsQuery = Ad::where('status', 'active')->with('user');

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
            $categoryQuery = Ad::where('status', 'active')->with('user');

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
            $searchAds = Ad::where('status', 'active')->with('user')
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
        $allCategories = array_merge(config('categories.services'), config('categories.marketplace'));
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
                $subCount = Ad::where('category', $sub['name'])
                    ->where('status', 'active')
                    ->count();
                $sub['count'] = $subCount;
                $total += $subCount;
            }
            $category['total'] = $total;
        }
        unset($category, $sub);

        return $categoriesWithSubs;
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
            $missionCategories,
            $userLat,
            $userLng,
            $radius,
            $geoCity,
            $geoCountry
        ) {
            $query = $this->buildMainFeedAdsQuery(
                $user,
                'all',
                $nearby,
                $nearby && $userLat !== null ? (float) $userLat : null,
                $nearby && $userLng !== null ? (float) $userLng : null,
                $radius,
                $nearby ? $geoCity : null,
                $nearby ? $geoCountry : null,
                false
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
                        'latitude' => $ad->latitude !== null ? (float) $ad->latitude : null,
                        'longitude' => $ad->longitude !== null ? (float) $ad->longitude : null,
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
                    $adsQ->where('status', 'active')
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
            ->whereHas('ads', fn ($q) => $q->where('is_boosted', true)->where('boost_end', '>', now()))
            ->with(['ads' => function ($q) use ($categoriesToSearch) {
                $q->where('status', 'active');
                if (! empty($categoriesToSearch)) {
                    $q->whereIn('category', $categoriesToSearch);
                }
                $q->latest()->take(1);
            }, 'services' => fn ($q) => $q->where('is_active', true)->limit(3)])
            ->withCount(['ads as ads_count' => fn ($q) => $q->where('status', 'active')])
            ->get();

        $boostedUserIds = $usersWithBoostedAds->pluck('id')->toArray();

        // Get subscribed professionals (not already in boosted)
        $subscribedPros = (clone $query)
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
                $q->where('status', 'active');
                if (! empty($categoriesToSearch)) {
                    $q->whereIn('category', $categoriesToSearch);
                }
                $q->latest()->take(1);
            }, 'services' => fn ($q) => $q->where('is_active', true)->limit(3)])
            ->withCount(['ads as ads_count' => fn ($q) => $q->where('status', 'active')])
            ->inRandomOrder()
            ->take(20 - count($boostedUserIds))
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
            ->withCount(['ads as ads_count' => fn ($q) => $q->where('status', 'active')])
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

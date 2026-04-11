<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\IpGeolocationService;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // ===== GÉOLOCALISATION AUTOMATIQUE =====
        $userGeo = $request->attributes->get('user_geo') ?? [];
        $userLat = $userGeo['latitude'] ?? null;
        $userLng = $userGeo['longitude'] ?? null;
        $userRadius = (int) ($request->get('radius') ?? $user->geo_radius ?? 50);
        $geoEnabled = $userLat && $userLng;
        $geoCity = $userGeo['city'] ?? $user->getDisplayCity() ?? null;
        $geoCountry = $userGeo['country'] ?? $user->getDisplayCountry() ?? null;
        $geoSource = $userGeo['source'] ?? 'unknown';

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
        if ($geoEnabled) {
            $proOffersQuery->withinRadius($userLat, $userLng, $userRadius);
        } else {
            $proOffersQuery->orderBy('is_pinned', 'desc')->orderBy('created_at', 'desc');
        }
        $proOffers = $proOffersQuery->take(8)->get();

        // Demandes de particuliers - filtrées par proximité
        $clientRequestsQuery = Ad::where('status', 'active')
            ->where('service_type', 'demande')
            ->with('user');
        if ($geoEnabled) {
            $clientRequestsQuery->withinRadius($userLat, $userLng, $userRadius);
        } else {
            $clientRequestsQuery->orderBy('created_at', 'desc');
        }
        $clientRequests = $clientRequestsQuery->take(8)->get();

        // ===== SECTION "LES DERNIÈRES PÉPITES" - filtrée par proximité =====
        // Nouvelle logique : n'afficher que les annonces des utilisateurs abonnés (plan actif) ou boostées
        $allAdsQuery = Ad::where('status', 'active')->with('user')
            ->where(function($q) {
                $q->where(function($q2) {
                    $q2->where('is_boosted', true)
                        ->where('boost_end', '>', now());
                })
                ->orWhereHas('user', function($q3) {
                    $q3->whereNotNull('plan')
                        ->where('plan', '!=', '')
                        ->where('plan', '!=', 'free')
                        ->where(function($q4) {
                            $q4->whereNull('subscription_end')
                                ->orWhere('subscription_end', '>', now());
                        });
                });
            });

        // Filtre visibilité : pro_targeted visible uniquement par les pros, SAUF annonces boostées
        $allAdsQuery->where(function($q) use ($user) {
            $q->where('visibility', 'public');
            if ($user && ($user->user_type === 'professionnel' || $user->is_service_provider)) {
                $q->orWhere('visibility', 'pro_targeted');
            }
            // Les annonces boostées sont toujours visibles quelle que soit leur visibilité
            $q->orWhere(function($q2) {
                $q2->where('is_boosted', true)->where('boost_end', '>', now());
            });
        });

        // Appliquer le filtre de type si présent
        $filterType = $request->get('type', 'all'); // all, offres, demandes
        if ($filterType === 'all' && !$request->has('type')) {
            if ($user && ($user->user_type === 'professionnel' || $user->is_service_provider)) {
                $filterType = 'demandes';
            }
        }
        // Filtre de type : ne s'applique PAS aux annonces boostées (visibilité payée)
        if ($filterType === 'offres') {
            $allAdsQuery->where(function($q) {
                $q->where('service_type', 'offre')
                  ->orWhere(function($q2) {
                      $q2->where('is_boosted', true)->where('boost_end', '>', now());
                  });
            });
        } elseif ($filterType === 'demandes') {
            $allAdsQuery->where(function($q) {
                $q->where('service_type', 'demande')
                  ->orWhere(function($q2) {
                      $q2->where('is_boosted', true)->where('boost_end', '>', now());
                  });
            });
        }
        // Filtre géo : ne s'applique PAS aux annonces boostées (visibilité payée)
        if ($geoEnabled) {
            $allAdsQuery->where(function($q) use ($userLat, $userLng, $userRadius) {
                $q->whereRaw(
                    "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?",
                    [$userLat, $userLng, $userLat, $userRadius]
                )->orWhere(function($q2) {
                    $q2->where('is_boosted', true)->where('boost_end', '>', now());
                });
            });
        }
        // Toujours prioriser : boostées > épinglées > récentes
        $allAdsQuery->orderByRaw("CASE WHEN is_boosted = true AND boost_end > ? THEN 0 ELSE 1 END", [now()])
                     ->orderBy('is_pinned', 'desc')
                     ->orderBy('created_at', 'desc');
        $ads = $allAdsQuery->paginate(12)->withQueryString();
        
        // Si peu de résultats, élargir automatiquement le rayon
        $radiusWasExpanded = false;
        $originalRadius = $userRadius;
        if ($geoEnabled && $ads->total() < 3 && $userRadius < 200) {
            $expandedRadius = min($userRadius * 3, 500);
            $expandedQuery = Ad::where('status', 'active')->with('user');
            if ($filterType === 'offres') $expandedQuery->where('service_type', 'offre');
            elseif ($filterType === 'demandes') $expandedQuery->where('service_type', 'demande');
            $expandedQuery->withinRadius($userLat, $userLng, $expandedRadius);
            $adsExp = $expandedQuery->paginate(12)->withQueryString();
            if ($adsExp->total() > $ads->total()) {
                $ads = $adsExp;
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
            $searchAds = Ad::where('status', 'active')->with('user')
                ->where(function($q) use ($request) {
                    $q->where('category', $request->search)
                      ->orWhere('title', 'LIKE', '%' . $request->search . '%');
                })
                ->orderBy('created_at', 'desc')
                ->paginate(12)
                ->withQueryString();
            $ads = $searchAds;
        }

        // Toutes les annonces pour le feed principal
        $sort = $request->get('sort', 'recent');

        // Top Pros - Classement par avis VÉRIFIÉS (uniquement des utilisateurs ayant publié une annonce ou effectué un paiement)
        $topPros = \App\Models\User::where(function($q) {
                $q->where('user_type', 'professionnel')
                  ->orWhere(function($q2) {
                      $q2->where('user_type', 'particulier')
                         ->where('is_service_provider', true);
                  });
            })
            ->where('id', '!=', $user->id)
            ->whereHas('verifiedReviewsReceived') // Au moins 1 avis vérifié
            ->withCount(['verifiedReviewsReceived as verified_reviews_count'])
            ->withAvg(['verifiedReviewsReceived as verified_reviews_avg' => fn($q) => $q], 'rating')
            ->withCount(['ads as ads_count' => fn($q) => $q->where('status', 'active')])
            ->orderByDesc('verified_reviews_avg')   // Meilleure note d'abord
            ->orderByDesc('verified_reviews_count')  // Puis le plus d'avis
            ->take(6)
            ->get();

        // Premium Pros - Utilisateurs avec annonces boostées ou abonnement actif
        // Inclut professionnels ET particuliers prestataires
        $usersWithBoostedAds = \App\Models\User::where(function($q) {
                $q->where('user_type', 'professionnel')
                  ->orWhere(function($q2) {
                      $q2->where('user_type', 'particulier')
                         ->where('is_service_provider', true);
                  });
            })
            ->whereHas('ads', fn($q) => $q->where('is_boosted', true)->where('boost_end', '>', now()))
            ->with(['ads' => fn($q) => $q->where('status', 'active')
                                        ->where('is_boosted', true)
                                        ->where('boost_end', '>', now())
                                        ->latest()
                                        ->take(1)])
            ->withCount(['ads as ads_count' => fn($q) => $q->where('status', 'active')])
            ->get();

        $boostedUserIds = $usersWithBoostedAds->pluck('id')->toArray();
        
        $subscribedPros = \App\Models\User::where(function($q) {
                $q->where('user_type', 'professionnel')
                  ->orWhere(function($q2) {
                      $q2->where('user_type', 'particulier')
                         ->where('is_service_provider', true);
                  });
            })
            ->whereNotIn('id', $boostedUserIds)
            ->where(function($q) {
                $q->whereNotNull('plan')
                  ->where('plan', '!=', '')
                  ->where('plan', '!=', 'free')
                  ->where(function($q2) {
                      $q2->whereNull('subscription_end')
                         ->orWhere('subscription_end', '>', now());
                  });
            })
            ->withCount(['ads as ads_count' => fn($q) => $q->where('status', 'active')])
            ->with(['ads' => fn($q) => $q->where('status', 'active')->latest()->take(1)])
            ->inRandomOrder()
            ->take(20 - count($boostedUserIds))
            ->get();

        // Ajouter aussi les particuliers prestataires récents (même sans abonnement premium)
        $newProviders = \App\Models\User::where('user_type', 'particulier')
            ->where('is_service_provider', true)
            ->whereNotIn('id', $boostedUserIds)
            ->whereNotIn('id', $subscribedPros->pluck('id')->toArray())
            ->withCount(['ads as ads_count' => fn($q) => $q->where('status', 'active')])
            ->with(['services' => fn($q) => $q->where('is_active', true)->limit(3)])
            ->orderByDesc('service_provider_since')
            ->take(10)
            ->get();

        $premiumPros = $usersWithBoostedAds->merge($subscribedPros)->merge($newProviders);

        // Vitrine sous les annonces: max 2 lignes (8 cartes)
        // Priorité: premium les mieux notés -> non premium les mieux notés -> fallback pour compléter
        $featuredProfessionals = $this->buildFeaturedProfessionals($user, 8);

        // Données JSON-ready pour injection JS du bloc "Professionnels à la une"
        $featuredProsJson = $featuredProfessionals->take(4)->map(function ($pro) {
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

        // Annonces sidebar (toutes les urgent + boostées combinées pour le sidebar gauche)
        $sidebarAds = $urgentAds->merge($boostedAds)
            ->sortByDesc(function ($ad) {
                // Trier: urgent premium d'abord, puis boosté VIP, puis boosté premium, puis le reste
                $priority = 0;
                if ($ad->is_urgent) $priority += 100;
                if ($ad->is_boosted) {
                    if ($ad->boost_type === 'vip') $priority += 50;
                    elseif ($ad->boost_type === 'premium') $priority += 30;
                    else $priority += 10;
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
            ->map(function($cat, $name) {
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
            $proCtrl = new \App\Http\Controllers\ProDashboardController();
            $onboardingCategories = $proCtrl->getServiceCategoriesPublic();
        }

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
            'radiusWasExpanded',
            'originalRadius',
            'showOnboardingModal',
            'onboardingCategories',
            'proSuggestions',
            'proProfileCompletion'
        ));
    }

    /**
     * Soumettre une candidature / marquer son intérêt pour une annonce
     */
    public function submitCandidature(Request $request, Ad $ad)
    {
        $user = Auth::user();

        if ($user->id === $ad->user_id) {
            return response()->json(['success' => false, 'message' => 'Vous ne pouvez pas postuler à votre propre annonce.'], 422);
        }

        $request->validate([
            'message' => 'nullable|string|max:1000',
        ]);

        try {
            // Notifier l'annonceur (notification database + mail)
            $ad->user->notify(new \App\Notifications\AdCandidatureNotification($ad, $user, $request->input('message')));
        } catch (\Exception $e) {
            // Si l'envoi du mail échoue, enregistrer uniquement la notification en base
            \Illuminate\Support\Facades\Log::warning('Candidature mail failed, saving DB notification only: ' . $e->getMessage());
            try {
                $notification = new \App\Notifications\AdCandidatureNotification($ad, $user, $request->input('message'));
                $ad->user->notifications()->create([
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'type' => get_class($notification),
                    'data' => $notification->toArray($ad->user),
                ]);
            } catch (\Exception $e2) {
                \Illuminate\Support\Facades\Log::error('Candidature DB notification also failed: ' . $e2->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Votre candidature a été envoyée avec succès ! L\'annonceur a été notifié.',
        ]);
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
                  ->where('plan', '!=', 'free')
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
                    'ads as ads_count' => fn($q) => $q->where('status', 'active'),
                ])
                ->withAvg(['verifiedReviewsReceived as verified_reviews_avg' => fn($q) => $q], 'rating')
                ->orderByDesc('verified_reviews_count')
                ->orderByDesc('ads_count')
                ->orderByDesc('updated_at')
                ->get()
                ->sortByDesc(fn($pro) => (float) ($pro->verified_reviews_avg ?? 0))
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
                ->withCount(['ads as ads_count' => fn($q) => $q->where('status', 'active')])
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
                    'name'  => $sub,
                    'icon'  => $this->getSubcategoryIcon($sub, $data['fa_icon']),
                    'count' => Ad::where('category', $sub)->where('service_type', 'offre')->count(),
                ];
            }
            $categories[] = [
                'name'           => $name,
                'icon'           => $data['fa_icon'],
                'color'          => $data['color'],
                'description'    => $data['description'],
                'subcategories'  => $subs,
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
                'name'           => $name,
                'icon'           => $data['fa_icon'],
                'color'          => $colorMap[$data['color']] ?? 'primary',
                'subcategories'  => $data['subcategories'],
                'count'          => Ad::where('main_category', $name)->count(),
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
                ->where(function($q) use ($request) {
                    $q->where('category', $request->search)
                      ->orWhere('title', 'LIKE', '%' . $request->search . '%');
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
            ->withCount(['ads as ads_count' => fn($q) => $q->where('status', 'active')])
            ->orderByDesc('ads_count')
            ->take(6)
            ->get();

        // Premium Professionals (users with active subscription OR boosted ads)
        // Priorité 1: Utilisateurs avec annonces boostées
        $usersWithBoostedAds = \App\Models\User::where('user_type', 'professionnel')
            ->whereHas('ads', function($q) {
                $q->where('status', 'active')
                  ->where('is_boosted', true)
                  ->where('boost_end', '>', now());
            })
            ->with(['ads' => fn($q) => $q->where('status', 'active')
                                        ->where('is_boosted', true)
                                        ->where('boost_end', '>', now())
                                        ->latest()
                                        ->take(1)])
            ->withCount(['ads as ads_count' => fn($q) => $q->where('status', 'active')])
            ->get();

        // Priorité 2: Utilisateurs avec abonnement premium (sans doublon)
        $boostedUserIds = $usersWithBoostedAds->pluck('id')->toArray();
        
        $subscribedPros = \App\Models\User::where('user_type', 'professionnel')
            ->whereNotIn('id', $boostedUserIds)
            ->where(function($q) {
                $q->whereNotNull('plan')
                  ->where('plan', '!=', '')
                  ->where('plan', '!=', 'free')
                  ->where(function($q2) {
                      $q2->whereNull('subscription_end')
                         ->orWhere('subscription_end', '>', now());
                  });
            })
            ->withCount(['ads as ads_count' => fn($q) => $q->where('status', 'active')])
            ->with(['ads' => fn($q) => $q->where('status', 'active')->latest()->take(1)])
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
                'icon'  => $data['fa_icon'],
                'color' => $data['color'],
                'subs'  => $subs,
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

        // Récupérer la géolocalisation depuis le middleware ou les paramètres
        $userGeo = $request->attributes->get('user_geo');
        if (!$userLat && $userGeo) {
            $userLat = $userGeo['latitude'] ?? null;
            $userLng = $userGeo['longitude'] ?? null;
        }
        if (!$radius && Auth::check()) {
            $radius = Auth::user()->geo_radius ?? 50;
        }
        $radius = (int) ($radius ?: 50);

        $query = Ad::where('status', 'active')->with('user');

        // ===== FILTRE VISIBILITÉ =====
        // Les annonces "pro_targeted" ne sont visibles que par les pros dont les catégories correspondent
        $user = Auth::user();
        $query->where(function($q) use ($user) {
            $q->where('visibility', 'public');
            if ($user && ($user->user_type === 'professionnel' || $user->is_service_provider)) {
                $q->orWhere('visibility', 'pro_targeted');
            }
        });

        // ===== FILTRE PROXIMITÉ GÉOGRAPHIQUE =====
        $geoApplied = false;
        if ($userLat && $userLng && !$location) {
            // Si pas de filtre location texte, appliquer le filtre par rayon
            $query->withinRadius((float) $userLat, (float) $userLng, $radius);
            $geoApplied = true;
        }

        // Apply category filter
        if ($category && $category !== 'all') {
            if (isset($missionCategories[$category])) {
                $subNames = collect($missionCategories[$category]['subs'])->pluck('name')->toArray();
                $subNames[] = $category;
                $query->whereIn('category', $subNames);
            } else {
                $query->where('category', $category);
            }
        }

        // Apply subcategory filter (more specific)
        if ($subcategory) {
            $query->where(function($q) use ($subcategory) {
                $q->where('category', $subcategory)
                  ->orWhere('title', 'LIKE', '%' . $subcategory . '%');
            });
        }

        // Apply location filter (text-based, overrides geo)
        if ($location) {
            $query->where(function($q) use ($location) {
                $q->where('location', 'LIKE', '%' . $location . '%')
                  ->orWhere('city', 'LIKE', '%' . $location . '%')
                  ->orWhere('postal_code', 'LIKE', '%' . $location . '%');
            });
        }

        // Apply price filter
        if ($priceMin) {
            $query->where('price', '>=', $priceMin);
        }
        if ($priceMax) {
            $query->where('price', '<=', $priceMax);
        }

        // Apply sorting — always prioritize boosted first
        $query->orderByRaw("CASE WHEN is_boosted = true AND boost_end > ? THEN 0 ELSE 1 END", [now()]);
        switch ($sort) {
            case 'urgent':
                $query->orderBy('is_urgent', 'desc');
                break;
            case 'recommended':
                $query->orderBy('is_pinned', 'desc')->orderBy('views', 'desc');
                break;
            case 'proximity':
                if ($geoApplied) {
                    break;
                }
            case 'recent':
            default:
                if (!$geoApplied) {
                    $query->orderBy('is_pinned', 'desc')->orderBy('created_at', 'desc');
                }
                break;
        }

        $ads = $query->withCount('comments')->paginate(12);

        // Return JSON if requested
        if ($request->get('format') === 'json' || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'geo_applied' => $geoApplied,
                'radius' => $radius,
                'ads' => $ads->map(function($ad) use ($geoApplied) {
                    $data = [
                        'id' => $ad->id,
                        'title' => $ad->title,
                        'description' => $ad->description,
                        'category' => $ad->category,
                        'price' => $ad->price,
                        'location' => $ad->location,
                        'city' => $ad->city,
                        'photos' => $ad->photos,
                        'is_urgent' => $ad->is_urgent,
                        'is_boosted' => (bool) $ad->is_boosted,
                        'boost_end' => $ad->boost_end ? $ad->boost_end->toIso8601String() : null,
                        'created_at_human' => $ad->created_at->diffForHumans(),
                        'user_id' => $ad->user_id,
                        'comments_count' => $ad->comments_count,
                        'shares_count' => $ad->shares_count ?? 0,
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
                'category' => $category
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
        $baseQuery = function() {
            return \App\Models\User::where(function($q) {
                $q->where('user_type', 'professionnel')
                  ->orWhere(function($q2) {
                      $q2->where('user_type', 'particulier')
                         ->where('is_service_provider', true);
                  });
            });
        };

        // Get professionals with active ads in the selected category
        $query = $baseQuery();

        // Filter by category if specified
        if (!empty($categoriesToSearch)) {
            $query->where(function($q) use ($categoriesToSearch) {
                // Soit ils ont des annonces dans cette catégorie
                $q->whereHas('ads', function($adsQ) use ($categoriesToSearch) {
                    $adsQ->where('status', 'active')
                         ->whereIn('category', $categoriesToSearch);
                })
                // Soit ils ont des services enregistrés dans cette catégorie (particuliers prestataires)
                ->orWhereHas('services', function($servQ) use ($categoriesToSearch) {
                    $servQ->where('is_active', true)
                          ->where(function($catQ) use ($categoriesToSearch) {
                              $catQ->whereIn('main_category', $categoriesToSearch)
                                   ->orWhereIn('subcategory', $categoriesToSearch);
                          });
                });
            });
        }

        // Get users with boosted ads first
        $usersWithBoostedAds = (clone $query)
            ->whereHas('ads', fn($q) => $q->where('is_boosted', true)->where('boost_end', '>', now()))
            ->with(['ads' => function($q) use ($categoriesToSearch) {
                $q->where('status', 'active');
                if (!empty($categoriesToSearch)) {
                    $q->whereIn('category', $categoriesToSearch);
                }
                $q->latest()->take(1);
            }, 'services' => fn($q) => $q->where('is_active', true)->limit(3)])
            ->withCount(['ads as ads_count' => fn($q) => $q->where('status', 'active')])
            ->get();

        $boostedUserIds = $usersWithBoostedAds->pluck('id')->toArray();

        // Get subscribed professionals (not already in boosted)
        $subscribedPros = (clone $query)
            ->whereNotIn('id', $boostedUserIds)
            ->where(function($q) {
                $q->whereNotNull('plan')
                  ->where('plan', '!=', '')
                  ->where('plan', '!=', 'free')
                  ->where(function($q2) {
                      $q2->whereNull('subscription_end')
                         ->orWhere('subscription_end', '>', now());
                  });
            })
            ->with(['ads' => function($q) use ($categoriesToSearch) {
                $q->where('status', 'active');
                if (!empty($categoriesToSearch)) {
                    $q->whereIn('category', $categoriesToSearch);
                }
                $q->latest()->take(1);
            }, 'services' => fn($q) => $q->where('is_active', true)->limit(3)])
            ->withCount(['ads as ads_count' => fn($q) => $q->where('status', 'active')])
            ->inRandomOrder()
            ->take(20 - count($boostedUserIds))
            ->get();

        // Get new service providers (particuliers prestataires récents)
        $existingIds = array_merge($boostedUserIds, $subscribedPros->pluck('id')->toArray());
        
        $newProviders = $baseQuery()
            ->where('is_service_provider', true)
            ->whereNotIn('id', $existingIds);
            
        if (!empty($categoriesToSearch)) {
            $newProviders->whereHas('services', function($q) use ($categoriesToSearch) {
                $q->where('is_active', true)
                  ->where(function($catQ) use ($categoriesToSearch) {
                      $catQ->whereIn('main_category', $categoriesToSearch)
                           ->orWhereIn('subcategory', $categoriesToSearch);
                  });
            });
        }
        
        $newProviders = $newProviders
            ->with(['services' => fn($q) => $q->where('is_active', true)->limit(3)])
            ->withCount(['ads as ads_count' => fn($q) => $q->where('status', 'active')])
            ->orderByDesc('service_provider_since')
            ->take(10)
            ->get();

        $premiumPros = $usersWithBoostedAds->merge($subscribedPros)->merge($newProviders);

        // Return JSON if requested
        if ($request->get('format') === 'json' || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'professionals' => $premiumPros->map(function($pro) {
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
            ]
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
                ]
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

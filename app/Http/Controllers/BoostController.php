<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Services\AdPromotionPaymentService;
use App\Support\AdPromotionCatalog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BoostController extends Controller
{
    /**
     * Boost packages available
     */
    private array $boostPackages;

    public function __construct(private readonly AdPromotionPaymentService $promotionPayments)
    {
        $this->boostPackages = AdPromotionCatalog::boosts();
    }

    /**
     * Show the boost options page for an ad — smart version
     */
    public function show(Ad $ad)
    {
        if (Auth::id() !== $ad->user_id) {
            abort(403, 'Vous ne pouvez pas booster cette annonce.');
        }
        if (AdPromotionCatalog::newRulesActive()) {
            return $this->options($ad, false);
        }
        if ($refusal = $this->refuseUnlessOffer($ad)) {
            return $refusal;
        }

        $user = Auth::user();
        $isPro = $user->hasActiveProSubscription();
        $status = $ad->getBoostStatus();
        $subscription = $this->sponsoringSubscription($ad);

        // Build smart recommendations for each package
        $smartPackages = [];
        foreach ($this->boostPackages as $key => $package) {
            $pkg = $package;
            $pkg['key'] = $key;
            $pkg['is_useful'] = true;
            $pkg['warning'] = null;
            $pkg['recommendation'] = null;

            // Premium discount: -20% on card payments.
            if ($isPro) {
                $pkg['price_euros_original'] = $pkg['price_euros'];
                $pkg['price_euros'] = AdPromotionCatalog::discountedCents($pkg['price_euros'], true) / 100;
            }

            // If urgent is active and covers more days than this package → useless
            if ($status['is_urgent'] && $status['urgent_days_left'] >= $package['duration_days'] && ! $status['is_boosted']) {
                $pkg['is_useful'] = false;
                $pkg['warning'] = "Inutile : votre mode Urgent couvre déjà {$status['urgent_days_left']} jours restants.";
            }

            // If already boosted with more days than this package → useless
            if ($status['is_boosted'] && $status['boost_days_left'] >= $package['duration_days']) {
                $pkg['is_useful'] = false;
                $pkg['warning'] = "Votre boost actuel expire déjà dans {$status['boost_days_left']} jours.";
            }

            // If has any visibility and this package is shorter → caution
            if ($status['has_any_visibility'] && $status['best_days_left'] > $package['duration_days']) {
                $pkg['is_useful'] = false;
                $pkg['warning'] = "Vous avez déjà {$status['best_days_left']} jours de visibilité restants.";
            }

            // Abonnement Premium actif : l'offre est déjà dans le groupe sponsorisé.
            if ($subscription) {
                if ($this->subscriptionCovers($subscription, $package['duration_days'])) {
                    $pkg['is_useful'] = false;
                    $pkg['warning'] = 'Inutile : votre abonnement Premium affiche déjà cette offre parmi les offres sponsorisées'
                        .($this->sponsoredUntil($subscription) ? ' jusqu’au '.$this->sponsoredUntil($subscription)->format('d/m/Y') : '').'.';
                } else {
                    $pkg['warning'] = 'Votre abonnement place déjà cette offre parmi les offres sponsorisées jusqu’au '
                        .$this->sponsoredUntil($subscription)->format('d/m/Y').' : le boost ne change l’affichage qu’après cette date.';
                }
            }

            // Smart recommendation for packages that add value when urgent is expiring
            if ($status['is_urgent'] && $status['is_expiring_soon'] && $package['duration_days'] >= 7) {
                $pkg['recommendation'] = "Idéal pour prolonger après l'expiration de votre Urgent !";
            }

            $smartPackages[$key] = $pkg;
        }

        // Determine the best recommended package
        $recommendedKey = null;
        if ($status['is_expiring_soon']) {
            $recommendedKey = 'boost_7'; // Best value when expiring
        } elseif (! $status['has_any_visibility']) {
            $recommendedKey = 'boost_7'; // Best value for new boost
        }

        // La remontée payante n'est plus proposée : aucun tarif n'est transmis à la vue.
        return view('boost.show', [
            'ad' => $ad,
            'packages' => $smartPackages,
            'isPro' => $isPro,
            'boostStatus' => $status,
            'recommendedKey' => $recommendedKey,
            'isAlreadyBoosted' => $status['is_boosted'],
            'boostTimeRemaining' => $status['is_boosted'] ? $ad->boost_end->diffForHumans() : null,
            'currentBoostType' => $ad->boost_type,
        ]);
    }

    /**
     * Process boost purchase with points — smart version
     */
    public function purchaseWithPoints(Request $request, Ad $ad)
    {
        abort(410, \App\Support\RetiredPoints::MESSAGE);
    }

    /**
     * Process boost purchase with Stripe
     */
    public function purchaseWithStripe(Request $request, Ad $ad)
    {
        // Seuls les produits en vente aujourd'hui sont acceptés : packs « en tête »
        // du jeu de règles en vigueur et, avec les nouvelles règles, remontées.
        $newRules = AdPromotionCatalog::newRulesActive();
        $bumps = $newRules ? AdPromotionCatalog::bumps() : [];
        $request->validate([
            'package' => ['required', \Illuminate\Validation\Rule::in([...array_keys($this->boostPackages), ...array_keys($bumps)])],
        ], ['package.*' => 'Choisissez une option de mise en avant.']);

        // Vérifier que l'utilisateur est propriétaire
        if (Auth::id() !== $ad->user_id) {
            return back()->with('error', 'Vous ne pouvez pas booster cette annonce.');
        }
        if ($refusal = $this->refuseUnlessOffer($ad)) {
            return $refusal;
        }
        if (isset($bumps[$request->package])) {
            return $this->purchaseBump($ad, $request->package);
        }

        $user = Auth::user();
        $package = $this->boostPackages[$request->package];
        if ($refusal = $this->refuseIfCoveredBySubscription($ad, $package)) {
            return $refusal;
        }

        // Règles d'origine : un boost plus court que celui en cours est refusé.
        // Nouvelles règles : un nouvel achat prolonge la mise à la une en cours.
        $status = $ad->getBoostStatus();
        if (! $newRules && $status['is_boosted'] && $status['boost_days_left'] >= $package['duration_days']) {
            return back()->with('error', 'Votre boost actuel couvre déjà '.$status['boost_days_left'].' jours restants. Choisissez un pack plus long pour prolonger.');
        }

        // Pro discount: -20% on euros
        $priceEuros = $package['price_euros'];
        if ($user->hasActiveProSubscription()) {
            $priceEuros = round($priceEuros * 0.8, 2);
        }

        // Créer une session Stripe
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $package['name'].' - '.$ad->title,
                            'description' => $package['description'],
                        ],
                        'unit_amount' => (int) ($priceEuros * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->checkoutReturnUrl('boost.success', ['ad' => $ad->id, 'package' => $request->package]),
                'cancel_url' => route('boost.show', $ad),
                'customer_email' => $user->email,
                'metadata' => [
                    'ad_id' => $ad->id,
                    'user_id' => $user->id,
                    'package' => $request->package,
                    'type' => 'ad_boost',
                    'expected_amount_cents' => (int) round($priceEuros * 100),
                    'expected_currency' => 'eur',
                ],
            ]);

            return redirect($session->url);
        } catch (\Exception $e) {
            Log::error('Unable to create boost Stripe checkout.', ['ad_id' => $ad->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Impossible d’ouvrir le paiement sécurisé. Veuillez réessayer.');
        }
    }

    /**
     * Handle successful Stripe payment — smart version
     */
    public function success(Request $request)
    {
        $ad = $this->adFromCheckoutReturn($request);
        $packageKey = $request->query('package');

        if (Auth::id() !== $ad->user_id) {
            return redirect()->route('homepage')->with('error', 'Accès non autorisé.');
        }
        $isBump = is_string($packageKey) && isset(AdPromotionCatalog::bumps()[$packageKey]);
        if (! is_string($packageKey) || (! $isBump && AdPromotionCatalog::boostDefinition($packageKey) === null)) {
            return redirect()->route('boost.show', $ad)->with('error', 'Pack de boost invalide.');
        }

        $session = $this->retrieveCheckoutSession($request, $ad);
        if (! $session) {
            return redirect()->route('boost.show', $ad)->with('error', 'Le paiement Stripe n’a pas pu être confirmé. Aucun boost n’a été appliqué.');
        }
        try {
            $result = $this->promotionPayments->fulfill($session, Auth::id(), $ad->id, $isBump ? 'bump' : 'ad_boost', $packageKey);
        } catch (\Throwable $exception) {
            Log::warning('Boost Stripe confirmation refused.', ['ad_id' => $ad->id, 'error' => $exception->getMessage()]);

            return redirect()->route('boost.show', $ad)->with('error', 'Le paiement Stripe n’a pas pu être confirmé. Aucun boost n’a été appliqué.');
        }
        if ($result['status'] !== 'processed') {
            return redirect()->route('ads.show', $ad)->with('success', 'Ce paiement avait déjà été confirmé.');
        }

        $ad->refresh();
        if ($isBump) {
            return redirect()->route('ads.show', $ad)->with('success', 'Paiement réussi. Votre offre vient de remonter et remontera '
                .((int) $ad->bump_every_days === 1 ? 'chaque jour' : 'chaque semaine').' jusqu’au '.$ad->bump_until->format('d/m/Y').'.');
        }
        if (AdPromotionCatalog::newRulesActive()) {
            return redirect()->route('ads.show', $ad)->with('success', 'Paiement réussi. Votre offre est à la une jusqu’au '.$ad->boost_end->format('d/m/Y').'.');
        }
        $message = '🚀 Paiement réussi ! Annonce boostée jusqu\'au '.$ad->boost_end->format('d/m/Y à H:i').' !';
        if ($ad->isCurrentlyUrgent()) {
            $message .= ' (+ mode Urgent actif)';
        }

        return redirect()->route('ads.show', $ad)->with('success', $message);
    }

    /** Achat d'une remontée automatique (nouvelles règles), payée par carte. */
    private function purchaseBump(Ad $ad, string $key)
    {
        $user = Auth::user();
        $pack = AdPromotionCatalog::bumps()[$key];
        if ($this->sponsoringSubscription($ad) && (int) $pack['every_days'] >= \App\Services\AdBumpService::SUBSCRIBER_EVERY_DAYS) {
            return back()->with('error', 'Inutile : votre abonnement Premium fait déjà remonter vos offres chaque semaine.');
        }
        $amount = AdPromotionCatalog::discountedCents((float) $pack['price_euros'], $user->hasActiveProSubscription());

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $pack['name'].' - '.$ad->title,
                            'description' => 'À chaque échéance, votre offre est reclassée comme si elle venait d’être publiée.',
                        ],
                        'unit_amount' => $amount,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->checkoutReturnUrl('boost.success', ['ad' => $ad->id, 'package' => $key]),
                'cancel_url' => route('boost.show', $ad),
                'customer_email' => $user->email,
                'metadata' => [
                    'ad_id' => $ad->id,
                    'user_id' => $user->id,
                    'package' => $key,
                    'type' => 'bump',
                    'expected_amount_cents' => $amount,
                    'expected_currency' => 'eur',
                ],
            ]);

            return redirect($session->url);
        } catch (\Exception $e) {
            Log::error('Unable to create bump Stripe checkout.', ['ad_id' => $ad->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Impossible d’ouvrir le paiement sécurisé. Veuillez réessayer.');
        }
    }

    /**
     * Page d'achat des nouvelles règles de mise en avant : toutes les options
     * de l'annonce sur un seul écran, un seul choix à la fois, un seul bouton
     * de paiement. Sert aussi de page « votre annonce est publiée ».
     */
    private function options(Ad $ad, bool $justPublished)
    {
        $user = Auth::user();
        $isPro = $user->hasActiveProSubscription();
        $isOffer = $ad->service_type === 'offre';
        $subscription = $this->sponsoringSubscription($ad);
        $option = fn (string $label, float $euros, ?string $unavailable = null, ?string $note = null) => [
            'label' => $label,
            'price' => AdPromotionCatalog::discountedCents($euros, $isPro) / 100,
            'full_price' => $isPro ? $euros : null,
            'unavailable' => $unavailable,
            'note' => $note,
        ];

        $groups = [];
        $current = [];
        if ($isOffer) {
            $boostedUntil = $ad->isCurrentlyBoosted() ? $ad->boost_end : null;
            $featured = [];
            foreach (AdPromotionCatalog::featured() as $key => $pack) {
                $covered = $subscription && $this->subscriptionCovers($subscription, (int) $pack['duration_days']);
                $featured[$key] = $option($pack['label'], (float) $pack['price_euros'],
                    $covered ? 'Déjà incluse dans votre abonnement Premium' : null,
                    match (true) {
                        (bool) $boostedUntil => 'Prolonge jusqu’au '.$boostedUntil->copy()->addDays((int) $pack['duration_days'])->format('d/m/Y'),
                        // Abonnement qui s'arrête avant la fin du pack : l'achat n'agit qu'ensuite.
                        $subscription && ! $covered => 'Votre abonnement met déjà cette offre à la une jusqu’au '.$this->sponsoredUntil($subscription)->format('d/m/Y'),
                        default => null,
                    });
            }
            $groups[] = [
                'key' => 'featured',
                'title' => 'À la une',
                'intro' => 'Votre offre passe avant les offres non sponsorisées, sur l’accueil des clients et dans la liste des annonces. Elle porte la mention « Sponsorisé ».',
                'options' => $featured,
            ];

            $bumpUntil = $ad->hasActiveBump() ? $ad->bump_until : null;
            $bumps = [];
            foreach (AdPromotionCatalog::bumps() as $key => $pack) {
                $included = $subscription && (int) $pack['every_days'] >= \App\Services\AdBumpService::SUBSCRIBER_EVERY_DAYS;
                $bumps[$key] = $option($pack['label'], (float) $pack['price_euros'],
                    $included ? 'Déjà incluse dans votre abonnement Premium' : null,
                    $bumpUntil ? 'S’ajoute à la remontée en cours' : null);
            }
            $groups[] = [
                'key' => 'bump',
                'title' => 'Remontée automatique',
                'intro' => 'À chaque échéance, votre offre est reclassée comme si elle venait d’être publiée : elle repasse devant les offres plus anciennes. Elle porte la mention « Remontée ».',
                'options' => $bumps,
            ];

            if ($subscription) {
                $current[] = 'Abonnement Premium : vos offres sont à la une et remontent chaque semaine'
                    .($this->sponsoredUntil($subscription) ? ', jusqu’au '.$this->sponsoredUntil($subscription)->format('d/m/Y') : '').'.';
            }
            if ($boostedUntil) {
                $current[] = 'À la une jusqu’au '.$boostedUntil->format('d/m/Y').'.';
            }
            if ($bumpUntil) {
                $current[] = 'Remontée automatique '.((int) $ad->bump_every_days === 1 ? 'chaque jour' : 'chaque semaine')
                    .' jusqu’au '.$bumpUntil->format('d/m/Y').'.';
            }
        } else {
            $urgent = AdPromotionCatalog::urgent();
            if ($ad->hasPromotedUrgency()) {
                $current[] = 'Option Urgent en cours jusqu’au '.$ad->urgent_until->format('d/m/Y').'.';
            } else {
                $groups[] = [
                    'key' => 'urgent',
                    'title' => 'Option Urgent',
                    'intro' => 'Votre demande porte la mention « Urgent · option payante », passe avant les autres demandes sur l’accueil des prestataires et apparaît dans le filtre « Urgentes » de la liste des annonces.',
                    'options' => ['urgent' => $option('Pendant toute la durée de votre demande', (float) $urgent['price_euros'])],
                ];
            }
        }

        // Vues réellement comptées : aucun chiffre tant que le comptage n'a pas commencé.
        $views = null;
        $counter = app(\App\Services\AdViewCounter::class);
        if ($since = $counter->countedSince($ad)) {
            $views = ['count' => $counter->countSince($ad, $since), 'since' => $since];
        }

        // Argument de l'abonnement : seulement s'il est réellement en vente.
        $premium = null;
        if ($isOffer && ! $isPro && \App\Support\PlatformFeatures::proSubscriptionsEnabled()) {
            $monthly = \App\Support\ProviderSubscriptionPlans::amount('monthly');
            $featured30 = (float) (AdPromotionCatalog::featured()['boost_30']['price_euros'] ?? 0);
            if ($monthly > 0) {
                $premium = [
                    'price' => number_format($monthly, 2, ',', ' ').' €',
                    // La comparaison n'est écrite que si elle est vraie aux tarifs en vigueur.
                    'cheaper_than' => $monthly < $featured30 ? $featured30 : null,
                ];
            }
        }

        return view('boost.options', [
            'ad' => $ad,
            'justPublished' => $justPublished,
            'isOffer' => $isOffer,
            'isPro' => $isPro,
            'groups' => $groups,
            'current' => $current,
            'views' => $views,
            'premium' => $premium,
            'canBuy' => collect($groups)->flatMap(fn ($group) => $group['options'])->contains(fn ($choice) => $choice['unavailable'] === null),
        ]);
    }

    /**
     * Show boost success page after ad creation
     */
    public function afterCreation(Ad $ad)
    {
        // Vérifier que l'utilisateur est propriétaire
        if (Auth::id() !== $ad->user_id) {
            return redirect()->route('homepage');
        }
        if (AdPromotionCatalog::newRulesActive()) {
            return $this->options($ad, true);
        }

        $isPro = Auth::user()->hasActiveProSubscription();
        $subscription = $this->sponsoringSubscription($ad);
        $packages = [];
        if ($ad->service_type === 'offre') {
            foreach ($this->boostPackages as $key => $package) {
                if ($subscription && $this->subscriptionCovers($subscription, $package['duration_days'])) {
                    continue;
                }
                $package['price_euros'] = AdPromotionCatalog::discountedCents($package['price_euros'], $isPro) / 100;
                $packages[$key] = $package;
            }
        }
        $urgent = AdPromotionCatalog::urgent();
        $urgent['price_euros'] = AdPromotionCatalog::discountedCents($urgent['price_euros'], $isPro) / 100;

        return view('boost.after-creation', [
            'ad' => $ad,
            'packages' => $packages,
            'isPro' => $isPro,
            'urgent' => $urgent,
        ]);
    }

    /**
     * Le boost n'agit que sur le flux des clients, qui ne montre que des
     * offres : il n'est pas vendu pour une demande.
     */
    private function refuseUnlessOffer(Ad $ad): ?\Illuminate\Http\RedirectResponse
    {
        if ($ad->service_type === 'offre') {
            return null;
        }

        return redirect()->route('ads.show', $ad)->with('error', 'Le boost est réservé aux offres de services.');
    }

    /**
     * Abonnement Premium en cours de l'auteur d'une offre (même condition que
     * App\Services\ClientFeedOffersService) : toutes ses offres sont déjà
     * dans le groupe sponsorisé.
     */
    private function sponsoringSubscription(Ad $ad): ?\App\Models\ProSubscription
    {
        if ($ad->service_type !== 'offre' || ! $ad->user) {
            return null;
        }

        return $ad->user->proSubscriptions()->currentlyActive()
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->orderByRaw('ends_at IS NULL DESC')
            ->orderByDesc('ends_at')
            ->first();
    }

    private function subscriptionCovers(\App\Models\ProSubscription $subscription, int $days): bool
    {
        $until = $this->sponsoredUntil($subscription);

        return $until === null || $until->greaterThanOrEqualTo(now()->addDays($days));
    }

    /** @var array<int, ?\Carbon\Carbon> fin de la mise en avant Premium, par abonnement lu */
    private array $sponsoredUntilCache = [];

    /**
     * Fin de la mise en avant apportée par Premium (null : sans date de fin). Un accès
     * sans prélèvement prolongé par un accès déjà payé (paiement unique enchaîné) compte
     * jusqu'au bout de la couverture continue, et pas seulement jusqu'à la fin de la
     * ligne en cours. Un abonnement mensuel garde sa propre échéance.
     */
    private function sponsoredUntil(\App\Models\ProSubscription $subscription): ?\Carbon\Carbon
    {
        if ($subscription->ends_at === null || $subscription->stripe_subscription_id) {
            return $subscription->ends_at;
        }

        return $this->sponsoredUntilCache[$subscription->id] ??= (function () use ($subscription) {
            $chainEnd = $subscription->user
                ? app(\App\Services\ProviderSubscriptionService::class)->oneTimeAccessEnd($subscription->user)
                : null;

            return $chainEnd && $chainEnd->greaterThan($subscription->ends_at) ? $chainEnd : $subscription->ends_at;
        })();
    }

    /** Refuse un pack dont toute la durée est déjà couverte par l'abonnement Premium. */
    private function refuseIfCoveredBySubscription(Ad $ad, array $package): ?\Illuminate\Http\RedirectResponse
    {
        $subscription = $this->sponsoringSubscription($ad);
        if (! $subscription || ! $this->subscriptionCovers($subscription, (int) $package['duration_days'])) {
            return null;
        }

        return back()->with('error', 'Inutile : votre abonnement Premium affiche déjà cette offre parmi les offres sponsorisées'
            .($this->sponsoredUntil($subscription) ? ' jusqu’au '.$this->sponsoredUntil($subscription)->format('d/m/Y') : '').'.');
    }

    /**
     * Get expiring boost alerts for the authenticated user
     * Returns ads whose boost or urgent is expiring within 2 days
     */
    public static function getExpiringAlerts($user): array
    {
        if (! $user) {
            return [];
        }

        $alerts = [];
        $ads = Ad::where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('is_boosted', true)
                        ->where('boost_end', '>', now())
                        ->where('boost_end', '<=', now()->addDays(2));
                })->orWhere(function ($q2) {
                    $q2->where('is_urgent', true)
                        ->where('urgent_until', '>', now())
                        ->where('urgent_until', '<=', now()->addDays(2));
                });
            })->get();

        foreach ($ads as $ad) {
            if ($ad->isCurrentlyBoosted() && $ad->boost_end && $ad->boost_end->diffInHours(now(), true) <= 48) {
                $hours = (int) now()->diffInHours($ad->boost_end, false);
                $alerts[] = [
                    'ad_id' => $ad->id,
                    'ad_title' => $ad->title,
                    'type' => 'boost',
                    'hours_left' => max(0, $hours),
                    'message' => $hours <= 24
                        ? "Le boost de \"{$ad->title}\" expire dans moins de 24h !"
                        : "Le boost de \"{$ad->title}\" expire dans {$hours}h.",
                    'icon' => 'fas fa-rocket',
                    'color' => '#f59e0b',
                ];
            }
            if ($ad->isCurrentlyUrgent() && $ad->urgent_until && $ad->urgent_until->diffInHours(now(), true) <= 48) {
                $hours = (int) now()->diffInHours($ad->urgent_until, false);
                $alerts[] = [
                    'ad_id' => $ad->id,
                    'ad_title' => $ad->title,
                    'type' => 'urgent',
                    'hours_left' => max(0, $hours),
                    'message' => $hours <= 24
                        ? "Le mode Urgent de \"{$ad->title}\" expire dans moins de 24h !"
                        : "Le mode Urgent de \"{$ad->title}\" expire dans {$hours}h.",
                    'icon' => 'fas fa-fire',
                    'color' => '#ef4444',
                ];
            }
        }

        return $alerts;
    }

    /**
     * Remontée payante avec des points : suspendue. Aucun point n'est débité,
     * l'annonce et son boost éventuel restent inchangés.
     */
    public function refreshAd(Ad $ad)
    {
        return $this->refreshSuspended($ad);
    }

    /**
     * Remontée payante par carte : suspendue. Aucune session Stripe n'est ouverte.
     */
    public function refreshAdStripe(Ad $ad)
    {
        return $this->refreshSuspended($ad);
    }

    /**
     * Retour Stripe d'une remontée payée avant la suspension. Le paiement est
     * enregistré une seule fois (AdPromotionPaymentService), sans modifier
     * l'annonce ni son boost.
     */
    public function refreshAdSuccess(Request $request)
    {
        $ad = $this->adFromCheckoutReturn($request);

        if (Auth::id() !== $ad->user_id) {
            return redirect()->route('homepage')->with('error', 'Accès non autorisé.');
        }

        $session = $this->retrieveCheckoutSession($request, $ad);
        if (! $session) {
            return redirect()->route('boost.show', $ad)->with('error', 'Le paiement Stripe n’a pas pu être confirmé. L’annonce n’a pas été modifiée.');
        }
        try {
            $result = $this->promotionPayments->fulfill($session, Auth::id(), $ad->id, 'refresh');
        } catch (\Throwable $exception) {
            Log::warning('Refresh Stripe confirmation refused.', ['ad_id' => $ad->id, 'error' => $exception->getMessage()]);

            return redirect()->route('boost.show', $ad)->with('error', 'Le paiement Stripe n’a pas pu être confirmé. L’annonce n’a pas été modifiée.');
        }
        if ($result['status'] !== 'processed') {
            return redirect()->route('ads.show', $ad)->with('success', 'Ce paiement avait déjà été confirmé.');
        }

        return redirect()->route('ads.show', $ad)->with('info',
            'Paiement enregistré. '.AdPromotionCatalog::REFRESH_SUSPENDED_MESSAGE
            .' Votre annonce et son boost éventuel n’ont pas été modifiés. Contactez le support pour toute question sur ce paiement.'
        );
    }

    private function refreshSuspended(Ad $ad)
    {
        $target = Auth::id() === $ad->user_id ? route('boost.show', $ad) : route('ads.show', $ad);

        return redirect($target)->with('info', AdPromotionCatalog::REFRESH_SUSPENDED_MESSAGE);
    }

    /**
     * Urgent publication config
     */
    public static function getUrgentConfig(): array
    {
        return AdPromotionCatalog::urgent();
    }

    /**
     * Make an ad urgent with points (costs 15 points, lasts 7 days)
     */
    public function makeUrgent(Ad $ad)
    {
        abort(410, \App\Support\RetiredPoints::MESSAGE);
    }

    /**
     * Make an ad urgent with Stripe (14€, lasts 7 days)
     */
    public function makeUrgentStripe(Ad $ad)
    {
        if (Auth::id() !== $ad->user_id) {
            return back()->with('error', 'Vous ne pouvez pas modifier cette annonce.');
        }
        if ($ad->service_type !== 'demande') {
            return back()->with('error', 'Le mode Urgent est réservé aux demandes de services. Utilisez un Boost pour une offre.');
        }

        // Vérifier si déjà urgent
        if ($ad->is_urgent && $ad->urgent_until && $ad->urgent_until->isFuture()) {
            return back()->with('error', 'Cette annonce est déjà en mode URGENT.');
        }
        // Urgence déjà indiquée gratuitement au dépôt : l'option n'ajouterait rien, on ne la vend pas.
        if (! $ad->canBuyUrgentOption()) {
            return back()->with('error', 'Votre demande est déjà signalée « Urgent » sans frais : cette option n’apporterait rien de plus.');
        }

        $user = Auth::user();
        $config = self::getUrgentConfig();

        // Pro discount: -20%
        $priceEuros = $config['price_euros'];
        if ($user->hasActiveProSubscription()) {
            $priceEuros = round($priceEuros * 0.8, 2);
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Option Urgent - '.$ad->title,
                            'description' => ($config['duration_days'] !== null
                                ? 'Pendant '.$config['duration_days'].' jours'
                                : 'Pendant toute la durée de votre demande')
                                .', elle porte la mention « Urgent · option payante » et passe avant les autres demandes sur l’accueil des prestataires.',
                        ],
                        'unit_amount' => (int) ($priceEuros * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->checkoutReturnUrl('boost.urgent.success', ['ad' => $ad->id]),
                'cancel_url' => route('boost.after-creation', $ad),
                'customer_email' => $user->email,
                'metadata' => [
                    'ad_id' => $ad->id,
                    'user_id' => $user->id,
                    'type' => 'urgent',
                    'expected_amount_cents' => (int) round($priceEuros * 100),
                    'expected_currency' => 'eur',
                ],
            ]);

            return redirect($session->url);
        } catch (\Exception $e) {
            Log::error('Unable to create urgent Stripe checkout.', ['ad_id' => $ad->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Impossible d’ouvrir le paiement sécurisé. Veuillez réessayer.');
        }
    }

    /**
     * Handle successful urgent Stripe payment
     */
    public function urgentSuccess(Request $request)
    {
        $ad = $this->adFromCheckoutReturn($request);

        if (Auth::id() !== $ad->user_id) {
            return redirect()->route('homepage')->with('error', 'Accès non autorisé.');
        }
        if ($ad->service_type !== 'demande') {
            return redirect()->route('boost.show', $ad)->with('error', 'Le mode Urgent est réservé aux demandes de services.');
        }

        $session = $this->retrieveCheckoutSession($request, $ad);
        if (! $session) {
            return redirect()->route('boost.after-creation', $ad)->with('error', 'Le paiement Stripe n’a pas pu être confirmé. Le mode Urgent n’a pas été activé.');
        }
        $config = self::getUrgentConfig();
        try {
            $result = $this->promotionPayments->fulfill($session, Auth::id(), $ad->id, 'urgent');
        } catch (\Throwable $exception) {
            Log::warning('Urgent Stripe confirmation refused.', ['ad_id' => $ad->id, 'error' => $exception->getMessage()]);

            return redirect()->route('boost.after-creation', $ad)->with('error', 'Le paiement Stripe n’a pas pu être confirmé. Le mode Urgent n’a pas été activé.');
        }
        if ($result['status'] !== 'processed') {
            return redirect()->route('ads.show', $ad)->with('success', 'Ce paiement avait déjà été confirmé.');
        }

        $ad->refresh();

        return redirect()->route('ads.show', $ad)->with('success', $config['duration_days'] !== null
            ? '🔥 Paiement réussi ! Votre annonce est en mode URGENT pendant '.$config['duration_days'].' jours !'
            : 'Paiement réussi. Votre demande est signalée « Urgent » jusqu’au '.$ad->urgent_until->format('d/m/Y').'.'
        );
    }

    private function checkoutReturnUrl(string $route, array $parameters): string
    {
        // Stripe replaces this literal placeholder after checkout.
        return route($route).'?session_id={CHECKOUT_SESSION_ID}&'.http_build_query($parameters);
    }

    private function adFromCheckoutReturn(Request $request): Ad
    {
        // Earlier checkouts appended "?session_id=..." inside the final query value.
        $lastKey = $request->routeIs('boost.success') ? 'package' : 'ad';
        $value = $request->query($lastKey);
        if (! $request->query->has('session_id') && is_string($value)
            && preg_match('/^([^?]+)\?session_id=(cs_[A-Za-z0-9_]+)$/D', $value, $match)) {
            $request->query->set($lastKey, $match[1]);
            $request->query->set('session_id', $match[2]);
        }

        $adId = filter_var($request->query('ad'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($adId === false, 404);

        return Ad::findOrFail($adId);
    }

    private function retrieveCheckoutSession(Request $request, Ad $ad)
    {
        $sessionId = $request->query('session_id');
        if (! is_string($sessionId) || $sessionId === '' || ! config('services.stripe.secret')) {
            return null;
        }

        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            return \Stripe\Checkout\Session::retrieve($sessionId);
        } catch (\Throwable $exception) {
            Log::warning('Stripe checkout verification failed', [
                'ad_id' => $ad->id,
                'session_id' => $sessionId,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}

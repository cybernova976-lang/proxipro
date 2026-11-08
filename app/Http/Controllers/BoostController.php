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
        if ($refusal = $this->refuseUnlessOffer($ad)) {
            return $refusal;
        }

        $user = Auth::user();
        $userPoints = $user->available_points ?? 0;
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

            // Pro discount: -20% on points, -20% on euros
            if ($isPro) {
                $pkg['price_points_original'] = $pkg['price_points'];
                $pkg['price_euros_original'] = $pkg['price_euros'];
                $pkg['price_points'] = max(1, (int) round($pkg['price_points'] * 0.8));
                $pkg['price_euros'] = round($pkg['price_euros'] * 0.8, 2);
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
                        .($subscription->ends_at ? ' jusqu’au '.$subscription->ends_at->format('d/m/Y') : '').'.';
                } else {
                    $pkg['warning'] = 'Votre abonnement place déjà cette offre parmi les offres sponsorisées jusqu’au '
                        .$subscription->ends_at->format('d/m/Y').' : le boost ne change l’affichage qu’après cette date.';
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
            'userPoints' => $userPoints,
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
        $request->validate([
            'package' => 'required|in:boost_3,boost_7,boost_15,boost_30',
        ]);

        if (Auth::id() !== $ad->user_id) {
            return back()->with('error', 'Vous ne pouvez pas booster cette annonce.');
        }
        if ($refusal = $this->refuseUnlessOffer($ad)) {
            return $refusal;
        }

        $user = Auth::user();
        $package = $this->boostPackages[$request->package];
        $pointsRequired = $package['price_points'];
        if ($refusal = $this->refuseIfCoveredBySubscription($ad, $package)) {
            return $refusal;
        }

        // Pro discount: -20% on points
        if ($user->hasActiveProSubscription()) {
            $pointsRequired = max(1, (int) round($pointsRequired * 0.8));
        }

        $status = $ad->getBoostStatus();

        // Prevent double-boost: reject if current boost covers more than this package
        if ($status['is_boosted'] && $status['boost_days_left'] >= $package['duration_days']) {
            return back()->with('error', 'Votre boost actuel couvre déjà '.$status['boost_days_left'].' jours restants. Choisissez un pack plus long pour prolonger.');
        }

        // Check points - redirect to pricing if insufficient
        if (($user->available_points ?? 0) < $pointsRequired) {
            return redirect()->route('pricing.index')->with('error', 'Points insuffisants. Vous avez '.($user->available_points ?? 0).' points, il vous en faut '.$pointsRequired.'. Achetez des points ci-dessous.');
        }

        // Smart: warn if package is less useful than current visibility
        // But still allow purchase (maybe user wants to stack)
        $user->spendPoints($pointsRequired, 'boost_purchase', 'Achat '.$package['name'].' pour l\'annonce: '.$ad->title);

        // Smart boost end calculation:
        // If already boosted, extend from current boost_end
        // If urgent is active but not boosted, boost starts from now (both run in parallel)
        $boostEnd = Carbon::now()->addDays($package['duration_days']);

        if ($ad->isCurrentlyBoosted() && $ad->boost_end) {
            $boostEnd = $ad->boost_end->addDays($package['duration_days']);
        }

        $ad->update([
            'is_boosted' => true,
            'boost_end' => $boostEnd,
            'boost_type' => $request->package,
            'boost_source' => null,
        ]);

        // Build smart success message
        $message = '🚀 Annonce boostée jusqu\'au '.$boostEnd->format('d/m/Y à H:i').' !';
        if ($status['is_urgent']) {
            $message .= ' (+ mode Urgent actif jusqu\'au '.$status['urgent_until']->format('d/m/Y').')';
        }

        return redirect()->route('ads.show', $ad)->with('success', $message);
    }

    /**
     * Process boost purchase with Stripe
     */
    public function purchaseWithStripe(Request $request, Ad $ad)
    {
        $request->validate([
            'package' => 'required|in:boost_3,boost_7,boost_15,boost_30',
        ]);

        // Vérifier que l'utilisateur est propriétaire
        if (Auth::id() !== $ad->user_id) {
            return back()->with('error', 'Vous ne pouvez pas booster cette annonce.');
        }
        if ($refusal = $this->refuseUnlessOffer($ad)) {
            return $refusal;
        }

        $user = Auth::user();
        $package = $this->boostPackages[$request->package];
        if ($refusal = $this->refuseIfCoveredBySubscription($ad, $package)) {
            return $refusal;
        }

        // Prevent double-boost: reject if current boost covers more than this package
        $status = $ad->getBoostStatus();
        if ($status['is_boosted'] && $status['boost_days_left'] >= $package['duration_days']) {
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
        if (! is_string($packageKey) || ! isset($this->boostPackages[$packageKey])) {
            return redirect()->route('boost.show', $ad)->with('error', 'Pack de boost invalide.');
        }

        $session = $this->retrieveCheckoutSession($request, $ad);
        if (! $session) {
            return redirect()->route('boost.show', $ad)->with('error', 'Le paiement Stripe n’a pas pu être confirmé. Aucun boost n’a été appliqué.');
        }
        try {
            $result = $this->promotionPayments->fulfill($session, Auth::id(), $ad->id, 'ad_boost', $packageKey);
        } catch (\Throwable $exception) {
            Log::warning('Boost Stripe confirmation refused.', ['ad_id' => $ad->id, 'error' => $exception->getMessage()]);

            return redirect()->route('boost.show', $ad)->with('error', 'Le paiement Stripe n’a pas pu être confirmé. Aucun boost n’a été appliqué.');
        }
        if ($result['status'] !== 'processed') {
            return redirect()->route('ads.show', $ad)->with('success', 'Ce paiement avait déjà été confirmé.');
        }

        $ad->refresh();
        $message = '🚀 Paiement réussi ! Annonce boostée jusqu\'au '.$ad->boost_end->format('d/m/Y à H:i').' !';
        if ($ad->isCurrentlyUrgent()) {
            $message .= ' (+ mode Urgent actif)';
        }

        return redirect()->route('ads.show', $ad)->with('success', $message);
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

        // Le boost n'a d'effet que sur les offres : aucun forfait n'est proposé pour une demande.
        return view('boost.after-creation', [
            'ad' => $ad,
            'packages' => $ad->service_type === 'offre' ? $this->boostPackages : [],
            'userPoints' => Auth::user()->available_points ?? 0,
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
        return $subscription->ends_at === null || $subscription->ends_at->greaterThanOrEqualTo(now()->addDays($days));
    }

    /** Refuse un pack dont toute la durée est déjà couverte par l'abonnement Premium. */
    private function refuseIfCoveredBySubscription(Ad $ad, array $package): ?\Illuminate\Http\RedirectResponse
    {
        $subscription = $this->sponsoringSubscription($ad);
        if (! $subscription || ! $this->subscriptionCovers($subscription, (int) $package['duration_days'])) {
            return null;
        }

        return back()->with('error', 'Inutile : votre abonnement Premium affiche déjà cette offre parmi les offres sponsorisées'
            .($subscription->ends_at ? ' jusqu’au '.$subscription->ends_at->format('d/m/Y') : '').'.');
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
        // Vérifier que l'utilisateur est propriétaire
        if (Auth::id() !== $ad->user_id) {
            return back()->with('error', 'Vous ne pouvez pas modifier cette annonce.');
        }
        if ($ad->service_type !== 'demande') {
            return back()->with('error', 'Le mode Urgent est réservé aux demandes de services. Utilisez un Boost pour une offre.');
        }

        // Vérifier si déjà urgent
        if ($ad->is_urgent && $ad->urgent_until && $ad->urgent_until->isFuture()) {
            return back()->with('error', 'Cette annonce est déjà en mode URGENT jusqu\'au '.$ad->urgent_until->format('d/m/Y').'.');
        }

        $user = Auth::user();
        $config = self::getUrgentConfig();
        $urgentCost = $config['price_points'];

        // Pro discount: -20%
        if ($user->hasActiveProSubscription()) {
            $urgentCost = max(1, (int) round($urgentCost * 0.8));
        }

        // Vérifier les points disponibles
        if (($user->available_points ?? 0) < $urgentCost) {
            return back()->with('error', 'Points insuffisants. Il vous faut '.$urgentCost.' points pour publier en mode URGENT. Vous avez '.($user->available_points ?? 0).' points.');
        }

        // Déduire les points
        $user->spendPoints($urgentCost, 'urgent_publication', 'Publication urgente: '.$ad->title);

        // Activer le mode urgent pour 7 jours
        $ad->update([
            'is_urgent' => true,
            'urgent_until' => now()->addDays($config['duration_days']),
            'urgent_source' => null,
            'sidebar_priority' => 1,
        ]);

        return redirect()->route('ads.show', $ad)->with('success',
            '🔥 Votre annonce est maintenant en mode URGENT pendant '.$config['duration_days'].' jours ! (-'.$urgentCost.' points)'
        );
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
                            'name' => 'Publication Urgente 🔥 - '.$ad->title,
                            'description' => 'Votre annonce sera épinglée en section Urgentes pendant '.$config['duration_days'].' jours',
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
            return redirect()->route('boost.show', $ad)->with('error', 'Le paiement Stripe n’a pas pu être confirmé. Le mode Urgent n’a pas été activé.');
        }
        $config = self::getUrgentConfig();
        try {
            $result = $this->promotionPayments->fulfill($session, Auth::id(), $ad->id, 'urgent');
        } catch (\Throwable $exception) {
            Log::warning('Urgent Stripe confirmation refused.', ['ad_id' => $ad->id, 'error' => $exception->getMessage()]);

            return redirect()->route('boost.show', $ad)->with('error', 'Le paiement Stripe n’a pas pu être confirmé. Le mode Urgent n’a pas été activé.');
        }
        if ($result['status'] !== 'processed') {
            return redirect()->route('ads.show', $ad)->with('success', 'Ce paiement avait déjà été confirmé.');
        }

        return redirect()->route('ads.show', $ad)->with('success',
            '🔥 Paiement réussi ! Votre annonce est en mode URGENT pendant '.$config['duration_days'].' jours !'
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

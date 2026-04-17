<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\PointTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BoostController extends Controller
{
    /**
     * Boost packages available
     */
    private $boostPackages = [
        'boost_3' => [
            'name' => 'Boost 3 jours',
            'duration_days' => 3,
            'price_points' => 5,
            'price_euros' => 4.00,
            'description' => 'Votre annonce apparaît dans la section "Professionnels Premium" pendant 3 jours',
            'features' => [
                'Visibilité accrue pendant 3 jours',
                'Badge "Boost" sur votre annonce',
                'Position prioritaire dans les résultats',
            ],
            'icon' => 'fas fa-bolt',
            'color' => '#3b82f6',
        ],
        'boost_7' => [
            'name' => 'Boost 7 jours',
            'duration_days' => 7,
            'price_points' => 10,
            'price_euros' => 6.00,
            'description' => 'Votre annonce apparaît dans la section "Professionnels Premium" pendant 7 jours',
            'features' => [
                'Visibilité accrue pendant 7 jours',
                'Badge "Boost" sur votre annonce',
                'Position prioritaire dans les résultats',
                'Meilleur rapport qualité/prix',
            ],
            'icon' => 'fas fa-rocket',
            'color' => '#10b981',
        ],
        'boost_15' => [
            'name' => 'Boost 15 jours',
            'duration_days' => 15,
            'price_points' => 20,
            'price_euros' => 10.00,
            'description' => 'Votre annonce apparaît dans la section "Professionnels Premium" pendant 15 jours',
            'features' => [
                'Visibilité accrue pendant 15 jours',
                'Badge "Premium" doré sur votre annonce',
                'Position prioritaire dans les résultats',
                'Mise en avant dans les notifications',
            ],
            'icon' => 'fas fa-star',
            'color' => '#f59e0b',
        ],
        'boost_30' => [
            'name' => 'Boost 30 jours',
            'duration_days' => 30,
            'price_points' => 30,
            'price_euros' => 15.00,
            'description' => 'Votre annonce apparaît dans la section "Professionnels Premium" pendant 30 jours',
            'features' => [
                'Visibilité maximale pendant 30 jours',
                'Badge "VIP" exclusif sur votre annonce',
                'Première position garantie',
                'Mise en avant dans les notifications',
                'Support prioritaire',
            ],
            'icon' => 'fas fa-crown',
            'color' => '#8b5cf6',
        ],
    ];

    /**
     * Show the boost options page for an ad — smart version
     */
    public function show(Ad $ad)
    {
        if (Auth::id() !== $ad->user_id) {
            abort(403, 'Vous ne pouvez pas booster cette annonce.');
        }

        $user = Auth::user();
        $userPoints = $user->available_points ?? 0;
        $isPro = $user->hasActiveProSubscription();
        $status = $ad->getBoostStatus();

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
            if ($status['is_urgent'] && $status['urgent_days_left'] >= $package['duration_days'] && !$status['is_boosted']) {
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
        } elseif (!$status['has_any_visibility']) {
            $recommendedKey = 'boost_7'; // Best value for new boost
        }

        // Refresh config with Pro discount
        $refreshConfig = [
            'price_points' => $isPro ? 8 : 10,
            'price_euros' => $isPro ? 2.40 : 3.00,
        ];

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
            'refreshConfig' => $refreshConfig,
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

        $user = Auth::user();
        $package = $this->boostPackages[$request->package];
        $pointsRequired = $package['price_points'];

        // Pro discount: -20% on points
        if ($user->hasActiveProSubscription()) {
            $pointsRequired = max(1, (int) round($pointsRequired * 0.8));
        }

        $status = $ad->getBoostStatus();

        // Prevent double-boost: reject if current boost covers more than this package
        if ($status['is_boosted'] && $status['boost_days_left'] >= $package['duration_days']) {
            return back()->with('error', 'Votre boost actuel couvre déjà ' . $status['boost_days_left'] . ' jours restants. Choisissez un pack plus long pour prolonger.');
        }

        // Check points - redirect to pricing if insufficient
        if (($user->available_points ?? 0) < $pointsRequired) {
            return redirect()->route('pricing.index')->with('error', 'Points insuffisants. Vous avez ' . ($user->available_points ?? 0) . ' points, il vous en faut ' . $pointsRequired . '. Achetez des points ci-dessous.');
        }

        // Smart: warn if package is less useful than current visibility
        // But still allow purchase (maybe user wants to stack)
        $user->spendPoints($pointsRequired, 'boost_purchase', 'Achat ' . $package['name'] . ' pour l\'annonce: ' . $ad->title);

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
        ]);

        // Build smart success message
        $message = '🚀 Annonce boostée jusqu\'au ' . $boostEnd->format('d/m/Y à H:i') . ' !';
        if ($status['is_urgent']) {
            $message .= ' (+ mode Urgent actif jusqu\'au ' . $status['urgent_until']->format('d/m/Y') . ')';
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

        $user = Auth::user();
        $package = $this->boostPackages[$request->package];

        // Prevent double-boost: reject if current boost covers more than this package
        $status = $ad->getBoostStatus();
        if ($status['is_boosted'] && $status['boost_days_left'] >= $package['duration_days']) {
            return back()->with('error', 'Votre boost actuel couvre déjà ' . $status['boost_days_left'] . ' jours restants. Choisissez un pack plus long pour prolonger.');
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
                            'name' => $package['name'] . ' - ' . $ad->title,
                            'description' => $package['description'],
                        ],
                        'unit_amount' => (int)($priceEuros * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('boost.success', ['ad' => $ad->id, 'package' => $request->package]),
                'cancel_url' => route('boost.show', $ad),
                'customer_email' => $user->email,
                'metadata' => [
                    'ad_id' => $ad->id,
                    'user_id' => $user->id,
                    'package' => $request->package,
                ],
            ]);

            return redirect($session->url);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Handle successful Stripe payment — smart version
     */
    public function success(Request $request)
    {
        $ad = Ad::findOrFail($request->ad);
        $packageKey = $request->package;

        if (Auth::id() !== $ad->user_id) {
            return redirect()->route('homepage')->with('error', 'Accès non autorisé.');
        }

        $package = $this->boostPackages[$packageKey];
        $status = $ad->getBoostStatus();

        $boostEnd = Carbon::now()->addDays($package['duration_days']);
        
        if ($ad->isCurrentlyBoosted() && $ad->boost_end) {
            $boostEnd = $ad->boost_end->addDays($package['duration_days']);
        }

        $ad->update([
            'is_boosted' => true,
            'boost_end' => $boostEnd,
            'boost_type' => $packageKey,
        ]);

        $message = '🚀 Paiement réussi ! Annonce boostée jusqu\'au ' . $boostEnd->format('d/m/Y à H:i') . ' !';
        if ($status['is_urgent']) {
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

        return view('boost.after-creation', [
            'ad' => $ad,
            'packages' => $this->boostPackages,
            'userPoints' => Auth::user()->available_points ?? 0,
        ]);
    }

    /**
     * Get expiring boost alerts for the authenticated user
     * Returns ads whose boost or urgent is expiring within 2 days
     */
    public static function getExpiringAlerts($user): array
    {
        if (!$user) return [];

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
     * Refresh an ad (after boost expiration) - costs 10 points
     */
    public function refreshAd(Ad $ad)
    {
        // Vérifier que l'utilisateur est propriétaire
        if (Auth::id() !== $ad->user_id) {
            return back()->with('error', 'Vous ne pouvez pas rafraîchir cette annonce.');
        }

        $user = Auth::user();
        $refreshCost = $user->hasActiveProSubscription() ? 8 : 10;

        // Vérifier les points disponibles
        if (($user->available_points ?? 0) < $refreshCost) {
            return back()->with('error', 'Points insuffisants. Il vous faut ' . $refreshCost . ' points pour rafraîchir votre annonce. Vous avez ' . ($user->available_points ?? 0) . ' points.');
        }

        // Déduire les points
        $user->spendPoints($refreshCost, 'refresh_ad', 'Rafraîchissement de l\'annonce: ' . $ad->title);

        // Mettre à jour l'annonce (remonter dans les résultats)
        $ad->update([
            'updated_at' => now(),
            'is_boosted' => false,
            'boost_end' => null,
            'boost_type' => null,
        ]);

        return redirect()->route('ads.show', $ad)->with('success', 
            '✅ Votre annonce a été rafraîchie et remontera dans les résultats de recherche !'
        );
    }

    /**
     * Refresh an ad with Stripe payment (3€, or 2.40€ for Pro)
     */
    public function refreshAdStripe(Ad $ad)
    {
        if (Auth::id() !== $ad->user_id) {
            return back()->with('error', 'Vous ne pouvez pas rafraîchir cette annonce.');
        }

        $user = Auth::user();
        $priceEuros = $user->hasActiveProSubscription() ? 2.40 : 3.00;

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Rafraîchir l\'annonce - ' . $ad->title,
                            'description' => 'Remonter votre annonce dans les résultats de recherche',
                        ],
                        'unit_amount' => (int)($priceEuros * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('ads.refresh.success', ['ad' => $ad->id]),
                'cancel_url' => route('boost.show', $ad),
                'customer_email' => $user->email,
                'metadata' => [
                    'ad_id' => $ad->id,
                    'user_id' => $user->id,
                    'type' => 'refresh',
                ],
            ]);

            return redirect($session->url);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Handle successful refresh Stripe payment
     */
    public function refreshAdSuccess(Request $request)
    {
        $ad = Ad::findOrFail($request->ad);

        if (Auth::id() !== $ad->user_id) {
            return redirect()->route('homepage')->with('error', 'Accès non autorisé.');
        }

        $ad->update([
            'updated_at' => now(),
            'is_boosted' => false,
            'boost_end' => null,
            'boost_type' => null,
        ]);

        return redirect()->route('ads.show', $ad)->with('success', 
            '✅ Paiement réussi ! Votre annonce a été rafraîchie et remontera dans les résultats !'
        );
    }

    /**
     * Urgent publication config
     */
    public static function getUrgentConfig(): array
    {
        return [
            'price_points' => 15,
            'price_euros' => 14.00,
            'duration_days' => 7,
        ];
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

        // Vérifier si déjà urgent
        if ($ad->is_urgent && $ad->urgent_until && $ad->urgent_until->isFuture()) {
            return back()->with('error', 'Cette annonce est déjà en mode URGENT jusqu\'au ' . $ad->urgent_until->format('d/m/Y') . '.');
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
            return back()->with('error', 'Points insuffisants. Il vous faut ' . $urgentCost . ' points pour publier en mode URGENT. Vous avez ' . ($user->available_points ?? 0) . ' points.');
        }

        // Déduire les points
        $user->spendPoints($urgentCost, 'urgent_publication', 'Publication urgente: ' . $ad->title);

        // Activer le mode urgent pour 7 jours
        $ad->update([
            'is_urgent' => true,
            'urgent_until' => now()->addDays($config['duration_days']),
            'sidebar_priority' => 1,
        ]);

        return redirect()->route('ads.show', $ad)->with('success', 
            '🔥 Votre annonce est maintenant en mode URGENT pendant ' . $config['duration_days'] . ' jours ! (-' . $urgentCost . ' points)'
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
                            'name' => 'Publication Urgente 🔥 - ' . $ad->title,
                            'description' => 'Votre annonce sera épinglée en section Urgentes pendant ' . $config['duration_days'] . ' jours',
                        ],
                        'unit_amount' => (int)($priceEuros * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('boost.urgent.success', ['ad' => $ad->id]),
                'cancel_url' => route('boost.after-creation', $ad),
                'customer_email' => $user->email,
                'metadata' => [
                    'ad_id' => $ad->id,
                    'user_id' => $user->id,
                    'type' => 'urgent',
                ],
            ]);

            return redirect($session->url);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Handle successful urgent Stripe payment
     */
    public function urgentSuccess(Request $request)
    {
        $ad = Ad::findOrFail($request->ad);

        if (Auth::id() !== $ad->user_id) {
            return redirect()->route('homepage')->with('error', 'Accès non autorisé.');
        }

        $config = self::getUrgentConfig();

        $ad->update([
            'is_urgent' => true,
            'urgent_until' => now()->addDays($config['duration_days']),
            'sidebar_priority' => 1,
        ]);

        return redirect()->route('ads.show', $ad)->with('success', 
            '🔥 Paiement réussi ! Votre annonce est en mode URGENT pendant ' . $config['duration_days'] . ' jours !'
        );
    }
}

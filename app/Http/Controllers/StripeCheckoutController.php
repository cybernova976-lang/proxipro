<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Transaction;
use App\Services\ReferralService;
use App\Services\ServiceOrderWorkflowService;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Carbon\Carbon;

class StripeCheckoutController extends Controller
{
    /**
     * Configuration des produits Stripe
     */
    private $products = [
        // Packs de points (alignés sur les tarifs boost)
        'POINTS_5' => ['price' => 400, 'points' => 5, 'name' => '5 Points', 'type' => 'points'],
        'POINTS_10' => ['price' => 600, 'points' => 10, 'name' => '10 Points', 'type' => 'points'],
        'POINTS_20' => ['price' => 1000, 'points' => 20, 'name' => '20 Points', 'type' => 'points'],
        'POINTS_30' => ['price' => 1500, 'points' => 30, 'name' => '30 Points', 'type' => 'points'],
        'POINTS_50' => ['price' => 2200, 'points' => 50, 'name' => '50 Points', 'type' => 'points'],
        'POINTS_100' => ['price' => 4000, 'points' => 100, 'name' => '100 Points', 'type' => 'points'],
    ];

    /**
     * Configuration des récompenses de partage social
     */
    private $socialRewards = [
        'facebook'  => ['points' => 5, 'name' => 'Facebook'],
        'twitter'   => ['points' => 5, 'name' => 'Twitter/X'],
        'instagram' => ['points' => 5, 'name' => 'Instagram'],
        'linkedin'  => ['points' => 5, 'name' => 'LinkedIn'],
        'whatsapp'  => ['points' => 5, 'name' => 'WhatsApp'],
        'telegram'  => ['points' => 5, 'name' => 'Telegram'],
    ];

    public function __construct(
        protected ReferralService $referralService,
        protected ServiceOrderWorkflowService $serviceOrderWorkflowService,
    )
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Créer une session Stripe Checkout
     */
    public function createCheckout(Request $request)
    {
        $request->validate([
            'product_key' => 'required|string',
        ]);

        $user = Auth::user();
        $productKey = $request->product_key;

        if (!isset($this->products[$productKey])) {
            return response()->json(['error' => 'Produit invalide'], 400);
        }

        $product = $this->products[$productKey];

        try {
            // Créer ou récupérer le customer Stripe
            $stripeCustomerId = $user->stripe_id;
            
            if (!$stripeCustomerId) {
                $customer = \Stripe\Customer::create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => ['user_id' => $user->id],
                ]);
                $stripeCustomerId = $customer->id;
                $user->update(['stripe_id' => $stripeCustomerId]);
            }

            // Créer la session Checkout
            $session = StripeSession::create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur', // Euro
                        'product_data' => [
                            'name' => $product['name'],
                            'description' => $product['type'] === 'subscription' 
                                ? $product['points'] . ' points/mois inclus' 
                                : 'Pack de ' . $product['points'] . ' points',
                        ],
                        'unit_amount' => $product['price'],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('stripe.success') . '?session_id={CHECKOUT_SESSION_ID}&product=' . $productKey,
                'cancel_url' => route('pricing.index') . '?canceled=true',
                'metadata' => [
                    'user_id' => $user->id,
                    'product_key' => $productKey,
                    'type' => $product['type'],
                    'points' => $product['points'],
                ],
            ]);

            return response()->json([
                'url' => $session->url,
                'session_id' => $session->id,
            ]);

        } catch (\Exception $e) {
            \Log::error('Stripe Checkout error: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la création du paiement'], 500);
        }
    }

    /**
     * Page de succès après paiement
     */
    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');
        $productKey = $request->get('product');

        if (!$sessionId) {
            return redirect()->route('pricing.index')->with('error', 'Session de paiement invalide');
        }

        try {
            $session = StripeSession::retrieve($sessionId);
            
            if ($session->payment_status !== 'paid') {
                return redirect()->route('pricing.index')->with('error', 'Paiement non confirmé');
            }

            if (($session->metadata->type ?? null) === 'service_order') {
                $serviceOrder = $this->serviceOrderWorkflowService->markPaidFromCheckoutSession($session);

                if (!$serviceOrder) {
                    return redirect()->route('service-orders.index')->with('error', 'Commande introuvable pour ce paiement.');
                }

                return redirect()->route('service-orders.index')
                    ->with('success', 'Paiement Stripe confirme. Les fonds sont bloques jusqu\'a liberation ou litige.');
            }

            if (!$productKey) {
                return redirect()->route('pricing.index')->with('error', 'Produit invalide');
            }

            $userId = $session->metadata->user_id;
            $product = $this->products[$productKey] ?? null;

            if (!$product) {
                return redirect()->route('pricing.index')->with('error', 'Produit invalide');
            }

            $user = User::find($userId);
            if (!$user) {
                return redirect()->route('pricing.index')->with('error', 'Utilisateur non trouvé');
            }

            // Vérifier si cette session a déjà été traitée
            $existingTransaction = Transaction::where('stripe_session_id', $sessionId)->first();
            if ($existingTransaction) {
                return redirect()->route('pricing.index')
                    ->with('success', 'Paiement déjà traité ! Vos points ont été crédités.');
            }

            // Traiter le paiement
            $this->processPayment($user, $product, $productKey, $session);

            $message = '🎉 ' . $product['points'] . ' points ajoutés à votre compte !';

            return redirect()->route('pricing.index')->with('success', $message);

        } catch (\Exception $e) {
            \Log::error('Stripe success callback error: ' . $e->getMessage());
            return redirect()->route('pricing.index')->with('error', 'Erreur lors de la confirmation du paiement');
        }
    }

    /**
     * Traiter le paiement et créditer les points/abonnement
     */
    private function processPayment($user, $product, $productKey, $session)
    {
        // Enregistrer la transaction
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => $product['price'] / 100,
            'type' => 'POINTS',
            'description' => 'Achat de ' . $product['points'] . ' points',
            'status' => 'completed',
            'stripe_session_id' => $session->id,
        ]);

        // Créditer les points (via available_points et total_points)
        $user->addPoints($product['points'], 'purchase', 'Achat de ' . $product['points'] . ' points', 'stripe');
        $this->referralService->grantFirstPurchaseRewards($user->fresh(), $transaction);
    }

    /**
     * Webhook Stripe
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            if ($endpointSecret) {
                $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            } else {
                $event = json_decode($payload);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        // Gérer les événements
        if (isset($event->type) && $event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $metadata = $session->metadata;

            if (($metadata->type ?? null) === 'service_order') {
                $this->serviceOrderWorkflowService->markPaidFromCheckoutSession($session);

                return response()->json(['received' => true]);
            }
            
            $user = User::find($metadata->user_id ?? null);
            $productKey = $metadata->product_key ?? null;
            
            if ($user && $productKey && isset($this->products[$productKey])) {
                $product = $this->products[$productKey];
                
                // Vérifier si pas déjà traité
                $existingTransaction = Transaction::where('stripe_session_id', $session->id)->first();
                if (!$existingTransaction) {
                    $this->processPayment($user, $product, $productKey, $session);
                }
            }
        }

        return response()->json(['received' => true]);
    }

    /**
     * Récupérer les transactions d'un utilisateur
     */
    public function transactions()
    {
        $user = Auth::user();
        $transactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return response()->json($transactions);
    }

    /**
     * Partage social - Réclamer les points
     */
    public function socialShare(Request $request)
    {
        try {
            $request->validate([
                'platform' => 'required|string|in:facebook,twitter,instagram,linkedin,whatsapp,telegram',
            ]);

            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non connecté'], 401);
            }
            
            $platform = $request->platform;

            if (!isset($this->socialRewards[$platform])) {
                return response()->json(['error' => 'Plateforme invalide'], 400);
            }

            $reward = $this->socialRewards[$platform];

            // Vérifier si déjà réclamé
            $alreadyClaimed = \DB::table('social_shares')
                ->where('user_id', $user->id)
                ->where('platform', $platform)
                ->exists();

            if ($alreadyClaimed) {
                return response()->json([
                    'success' => false,
                    'error' => 'Vous avez déjà récupéré vos points ' . $reward['name'],
                    'already_claimed' => true,
                ]);
            }

            // Enregistrer le partage
            \DB::table('social_shares')->insert([
                'user_id' => $user->id,
                'platform' => $platform,
                'points_earned' => $reward['points'],
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Créditer les points en utilisant la méthode du modèle User
            $user->addPoints($reward['points'], 'social_share', 'Partage sur ' . $reward['name']);

            // Calculer le total gagné via partage
            $totalEarned = \DB::table('social_shares')
                ->where('user_id', $user->id)
                ->sum('points_earned');

            return response()->json([
                'success' => true,
                'points_earned' => $reward['points'],
                'total_social_points' => $totalEarned,
                'new_balance' => $user->fresh()->available_points,
                'message' => '🎉 +' . $reward['points'] . ' points grâce à ' . $reward['name'] . ' !',
            ]);
        } catch (\Exception $e) {
            \Log::error('Social share error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'platform' => $request->platform ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Erreur lors du traitement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statut des partages sociaux
     */
    public function socialStatus()
    {
        $user = Auth::user();

        $shares = \DB::table('social_shares')
            ->where('user_id', $user->id)
            ->get();

        $claimedPlatforms = $shares->pluck('platform')->toArray();
        $totalEarned = $shares->sum('points_earned');

        $maxPossible = array_sum(array_column($this->socialRewards, 'points'));

        $availablePlatforms = collect($this->socialRewards)
            ->filter(function ($value, $key) use ($claimedPlatforms) {
                return !in_array($key, $claimedPlatforms);
            })
            ->map(function ($value, $key) {
                return array_merge(['platform' => $key], $value);
            })
            ->values();

        return response()->json([
            'claimed_platforms' => $claimedPlatforms,
            'total_earned' => $totalEarned,
            'max_possible' => $maxPossible,
            'available_platforms' => $availablePlatforms,
            'all_claimed' => count($claimedPlatforms) >= count($this->socialRewards),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserService;
use App\Models\ProSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class BecomeProviderController extends Controller
{
    /**
     * Liste des pays disponibles
     */
    private function getCountries(): array
    {
        return [
            'France' => '🇫🇷',
            'Mayotte' => '🇾🇹',
            'Madagascar' => '🇲🇬',
            'La Réunion' => '🇷🇪',
            'Maurice' => '🇲🇺',
            'Belgique' => '🇧🇪',
            'Suisse' => '🇨🇭',
            'Canada' => '🇨🇦',
            'Sénégal' => '🇸🇳',
            'Côte d\'Ivoire' => '🇨🇮',
            'Maroc' => '🇲🇦',
            'Tunisie' => '🇹🇳',
            'Algérie' => '🇩🇿',
            'Cameroun' => '🇨🇲',
            'Luxembourg' => '🇱🇺',
            'Monaco' => '🇲🇨',
        ];
    }

    /**
     * Villes principales par pays
     */
    private function getCitiesByCountry(): array
    {
        return [
            'France' => [
                'Paris', 'Marseille', 'Lyon', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg', 
                'Montpellier', 'Bordeaux', 'Lille', 'Rennes', 'Reims', 'Saint-Étienne',
                'Toulon', 'Le Havre', 'Grenoble', 'Dijon', 'Angers', 'Nîmes', 'Villeurbanne',
                'Clermont-Ferrand', 'Le Mans', 'Aix-en-Provence', 'Brest', 'Tours', 'Amiens',
                'Limoges', 'Perpignan', 'Metz', 'Besançon', 'Orléans', 'Rouen', 'Mulhouse',
                'Caen', 'Nancy', 'Saint-Denis', 'Argenteuil', 'Roubaix', 'Tourcoing', 'Avignon'
            ],
            'Mayotte' => [
                'Mamoudzou', 'Koungou', 'Dzaoudzi', 'Dembeni', 'Bandraboua', 'Tsingoni',
                'Sada', 'Ouangani', 'Chiconi', 'Pamandzi', 'Mtsamboro', 'Acoua',
                'Chirongui', 'Bouéni', 'Kani-Kéli', 'Bandrélé', 'M\'Tsangamouji'
            ],
            'La Réunion' => [
                'Saint-Denis', 'Saint-Paul', 'Saint-Pierre', 'Le Tampon', 'Saint-André',
                'Saint-Louis', 'Saint-Benoît', 'Le Port', 'Saint-Joseph', 'Sainte-Marie',
                'Sainte-Suzanne', 'Saint-Leu', 'La Possession', 'Bras-Panon', 'Cilaos', 'Salazie'
            ],
            'Maurice' => [
                'Port-Louis', 'Beau Bassin-Rose Hill', 'Vacoas-Phoenix', 'Curepipe',
                'Quatre Bornes', 'Triolet', 'Goodlands', 'Centre de Flacq', 'Mahébourg',
                'Grand Baie', 'Flic en Flac', 'Tamarin'
            ],
            'Belgique' => [
                'Bruxelles', 'Anvers', 'Gand', 'Charleroi', 'Liège', 'Bruges', 'Namur',
                'Louvain', 'Mons', 'Ostende', 'Tournai', 'Hasselt', 'Courtrai'
            ],
            'Suisse' => [
                'Zurich', 'Genève', 'Bâle', 'Lausanne', 'Berne', 'Winterthour', 'Lucerne',
                'Saint-Gall', 'Lugano', 'Bienne', 'Fribourg', 'Neuchâtel', 'Sion'
            ],
            'Canada' => [
                'Montréal', 'Toronto', 'Vancouver', 'Calgary', 'Edmonton', 'Ottawa',
                'Winnipeg', 'Québec', 'Hamilton', 'Kitchener', 'London', 'Victoria',
                'Halifax', 'Gatineau', 'Saskatoon', 'Regina', 'Laval', 'Longueuil'
            ],
            'Maroc' => [
                'Casablanca', 'Rabat', 'Fès', 'Marrakech', 'Tanger', 'Agadir', 'Meknès',
                'Oujda', 'Kénitra', 'Tétouan', 'Safi', 'El Jadida', 'Nador', 'Mohammedia'
            ],
            'Algérie' => [
                'Alger', 'Oran', 'Constantine', 'Annaba', 'Blida', 'Batna', 'Djelfa',
                'Sétif', 'Sidi Bel Abbès', 'Biskra', 'Tébessa', 'El Oued', 'Skikda'
            ],
            'Tunisie' => [
                'Tunis', 'Sfax', 'Sousse', 'Kairouan', 'Bizerte', 'Gabès', 'Ariana',
                'Gafsa', 'Monastir', 'Ben Arous', 'Kasserine', 'Médenine', 'Nabeul'
            ],
            'Sénégal' => [
                'Dakar', 'Thiès', 'Saint-Louis', 'Kaolack', 'Ziguinchor', 'Rufisque',
                'Tambacounda', 'Louga', 'Diourbel', 'Kolda', 'Matam', 'Fatick'
            ],
            'Côte d\'Ivoire' => [
                'Abidjan', 'Bouaké', 'Daloa', 'Yamoussoukro', 'Korhogo', 'San-Pédro',
                'Divo', 'Man', 'Gagnoa', 'Abengourou', 'Anyama', 'Séguéla'
            ],
            'Cameroun' => [
                'Douala', 'Yaoundé', 'Garoua', 'Bamenda', 'Maroua', 'Bafoussam',
                'Ngaoundéré', 'Bertoua', 'Limbe', 'Kribi', 'Ebolowa', 'Buea'
            ],
            'Madagascar' => [
                'Antananarivo', 'Toamasina', 'Antsirabe', 'Fianarantsoa', 'Mahajanga',
                'Toliara', 'Antsiranana', 'Ambovombe', 'Moramanga', 'Manakara'
            ],
            'Luxembourg' => [
                'Luxembourg', 'Esch-sur-Alzette', 'Differdange', 'Dudelange', 
                'Ettelbruck', 'Diekirch', 'Wiltz', 'Echternach', 'Rumelange'
            ],
            'Monaco' => [
                'Monaco', 'Monte-Carlo', 'La Condamine', 'Fontvieille', 'Moneghetti'
            ],
        ];
    }

    /**
     * Liste des catégories avec sous-catégories (source unique : config/categories.php)
     */
    private function getServiceCategories(): array
    {
        $categories = [];
        foreach (config('categories.services') as $name => $data) {
            $categories[$name] = [
                'icon'           => $data['icon'],
                'color'          => $data['color'],
                'subcategories'  => $data['subcategories'],
            ];
        }
        return $categories;
    }

    /**
     * Retourne les données nécessaires pour le modal
     */
    public function getFormData()
    {
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'user' => [
                'name' => $user->name,
                'isOAuth' => $user->isOAuthUser(),
                'needsCompletion' => $user->needsProfileCompletion(),
            ],
            'countries' => $this->getCountries(),
            'cities' => $this->getCitiesByCountry(),
            'categories' => $this->getServiceCategories(),
        ]);
    }

    /**
     * Enregistre le profil prestataire complet
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validation
        $rules = [
            'business_type' => 'required|in:entreprise,auto_entrepreneur',
            'company_name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'subcategory' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'plan' => 'nullable|in:monthly,annual',
        ];

        $messages = [
            'business_type.required' => 'Veuillez choisir votre type d\'activité.',
            'business_type.in' => 'Type d\'activité invalide.',
            'company_name.required' => 'Le nom commercial est obligatoire.',
            'category.required' => 'Veuillez sélectionner une catégorie.',
            'subcategory.required' => 'Veuillez sélectionner une sous-catégorie (métier).',
            'country.required' => 'Veuillez sélectionner votre pays.',
            'city.required' => 'Veuillez sélectionner votre ville.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Mettre à jour le nom si différent
            $updateData = [
                'account_type' => 'professionnel',
                'business_type' => $request->business_type,
                'company_name' => $request->company_name,
                'user_type' => 'professionnel',
                'profession' => $request->subcategory, // Le métier est la sous-catégorie
                'country' => $request->country,
                'city' => $request->city,
                'is_service_provider' => true,
                'service_provider_since' => now(),
                'profile_completed' => true,
                'profile_completed_at' => now(),
                'max_active_ads' => $request->business_type === 'entreprise' ? 20 : 10,
            ];

            // Si le nom commercial est différent, on le met comme nom principal
            if ($request->company_name !== $user->name) {
                $updateData['name'] = $request->company_name;
            }

            $user->update($updateData);

            // Créer ou mettre à jour le service principal (sans supprimer les existants)
            UserService::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'main_category' => $request->category,
                    'subcategory' => $request->subcategory,
                ],
                [
                    'experience_years' => 0,
                    'description' => null,
                    'is_active' => true,
                ]
            );

            DB::commit();

            // Si un plan est sélectionné, rediriger vers Stripe pour le paiement
            if ($request->filled('plan') && in_array($request->plan, ['monthly', 'annual'])) {
                $planType = $request->plan;
                $amount = $planType === 'annual' ? 85.00 : 9.99;
                $planLabel = $planType === 'annual' ? 'Abonnement ProxiPro Annuel' : 'Abonnement ProxiPro Mensuel';

                // Sauvegarder les données pour finaliser après paiement
                session([
                    'become_provider_subscription' => [
                        'plan' => $planType,
                        'category' => $request->category,
                    ],
                ]);

                try {
                    Stripe::setApiKey(config('services.stripe.secret'));

                    $stripeCustomerId = $user->stripe_id;
                    if (!$stripeCustomerId) {
                        $customer = \Stripe\Customer::create([
                            'email' => $user->email,
                            'name' => $user->company_name ?? $user->name,
                            'metadata' => ['user_id' => $user->id],
                        ]);
                        $stripeCustomerId = $customer->id;
                        $user->update(['stripe_id' => $stripeCustomerId]);
                    }

                    $session = StripeSession::create([
                        'customer' => $stripeCustomerId,
                        'payment_method_types' => ['card'],
                        'line_items' => [[
                            'price_data' => [
                                'currency' => 'eur',
                                'product_data' => [
                                    'name' => $planLabel,
                                    'description' => $planType === 'annual'
                                        ? 'Accès complet pendant 1 an (soit 7,08€/mois)'
                                        : 'Accès complet pendant 1 mois, renouvelable',
                                ],
                                'unit_amount' => (int) round($amount * 100),
                            ],
                            'quantity' => 1,
                        ]],
                        'mode' => 'payment',
                        'success_url' => route('become-provider.payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                        'cancel_url' => route('become-provider.payment.cancel'),
                        'metadata' => [
                            'user_id' => $user->id,
                            'type' => 'become_provider_subscription',
                            'plan' => $planType,
                            'amount' => $amount,
                        ],
                    ]);

                    return response()->json([
                        'success' => true,
                        'requires_payment' => true,
                        'checkout_url' => $session->url,
                        'message' => 'Redirection vers le paiement...',
                    ]);

                } catch (\Exception $e) {
                    \Log::error('BecomeProvider Stripe error: ' . $e->getMessage());
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur lors de la création du paiement. Veuillez réessayer.',
                    ], 500);
                }
            }

            // Sans abonnement — profil activé directement
            return response()->json([
                'success' => true,
                'message' => 'Félicitations ! Votre profil prestataire est maintenant actif.',
                'user' => [
                    'name' => $user->name,
                    'profession' => $user->profession,
                    'business_type' => $user->business_type,
                    'is_service_provider' => true,
                ],
                'has_subscription' => false,
                'plan' => null,
                'show_verification' => true,
                'redirect' => route('pro.dashboard')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('BecomeProvider error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue. Veuillez réessayer.',
            ], 500);
        }
    }

    /**
     * Handle successful Stripe payment for become-provider subscription
     */
    public function paymentSuccess(Request $request)
    {
        $sessionId = $request->get('session_id');
        $user = Auth::user();

        if (!$sessionId || !$user) {
            return redirect()->route('feed')->with('error', 'Session de paiement invalide.');
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return redirect()->route('feed')->with('error', 'Le paiement n\'a pas été confirmé.');
            }

            if ((int) $session->metadata->user_id !== $user->id) {
                return redirect()->route('feed')->with('error', 'Session de paiement invalide.');
            }

            // Vérifier idempotence
            $existingSub = ProSubscription::where('stripe_payment_intent', $session->payment_intent)->first();
            if ($existingSub) {
                return redirect()->route('pro.dashboard')->with('success', 'Votre abonnement est déjà actif !');
            }

            $data = session('become_provider_subscription', []);
            $plan = $data['plan'] ?? $session->metadata->plan ?? 'monthly';
            $amount = $plan === 'annual' ? 85.00 : 9.99;
            $category = $data['category'] ?? null;

            DB::beginTransaction();
            try {
                ProSubscription::create([
                    'user_id' => $user->id,
                    'plan' => $plan,
                    'amount' => $amount,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => $plan === 'annual' ? now()->addYear() : now()->addMonth(),
                    'auto_renew' => true,
                    'notifications_enabled' => true,
                    'realtime_notifications' => true,
                    'selected_categories' => $category ? [$category] : [],
                    'intervention_radius' => 30,
                    'stripe_payment_intent' => $session->payment_intent,
                ]);

                $user->update([
                    'pro_subscription_plan' => $plan,
                    'pro_status' => 'active',
                    'pro_onboarding_completed' => true,
                ]);

                DB::commit();
                session()->forget('become_provider_subscription');

                return redirect()->route('pro.dashboard')->with('success', 'Félicitations ! Votre abonnement ' . ($plan === 'annual' ? 'annuel' : 'mensuel') . ' est activé !');

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('BecomeProvider payment success DB error: ' . $e->getMessage());
                return redirect()->route('feed')->with('error', 'Erreur lors de l\'activation. Contactez le support.');
            }

        } catch (\Exception $e) {
            \Log::error('BecomeProvider payment verification error: ' . $e->getMessage());
            return redirect()->route('feed')->with('error', 'Erreur de vérification du paiement.');
        }
    }

    /**
     * Handle cancelled Stripe payment
     */
    public function paymentCancel()
    {
        session()->forget('become_provider_subscription');
        return redirect()->route('feed')->with('info', 'Paiement annulé. Votre profil prestataire est actif mais sans abonnement. Vous pouvez souscrire depuis votre espace pro.');
    }
}

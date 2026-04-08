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

class ServiceProviderController extends Controller
{
    /**
     * Liste des catégories avec sous-catégories (source unique : config/categories.php)
     */
    private function getServiceCategories(): array
    {
        $categories = [];
        foreach (config('categories.services') as $name => $data) {
            $categories[$name] = [
                'icon'           => $data['fa_icon'],
                'color'          => $data['color'],
                'subcategories'  => $data['subcategories'],
            ];
        }
        return $categories;
    }

    /**
     * Affiche le formulaire pour devenir prestataire (modal content)
     */
    public function showForm()
    {
        $user = Auth::user();
        $categories = $this->getServiceCategories();
        $existingServices = $user->services()->get();

        return view('service-provider.form-modal', compact('categories', 'existingServices'));
    }

    /**
     * Retourne les catégories en JSON pour le formulaire
     */
    public function getCategories()
    {
        return response()->json([
            'success' => true,
            'categories' => $this->getServiceCategories()
        ]);
    }

    /**
     * Enregistre les services et active le statut prestataire
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validation
        $validator = Validator::make($request->all(), [
            'services' => 'required|array|min:1',
            'services.*.main_category' => 'required|string|max:100',
            'services.*.subcategory' => 'required|string|max:100',
            'services.*.experience_years' => 'nullable|integer|min:0|max:50',
            'services.*.description' => 'nullable|string|max:500',
            'plan' => 'nullable|in:monthly,annual',
        ], [
            'services.required' => 'Veuillez sélectionner au moins un service.',
            'services.min' => 'Veuillez sélectionner au moins un service.',
            'services.*.main_category.required' => 'La catégorie est requise.',
            'services.*.subcategory.required' => 'La sous-catégorie est requise.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Ajouter les nouveaux services sans supprimer les anciens (mode additif)
            foreach ($request->services as $service) {
                UserService::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'main_category' => $service['main_category'],
                        'subcategory' => $service['subcategory'],
                    ],
                    [
                        'experience_years' => $service['experience_years'] ?? 0,
                        'description' => $service['description'] ?? null,
                        'is_active' => true,
                    ]
                );
            }

            // Activer le statut prestataire
            $user->update([
                'is_service_provider' => true,
                'service_provider_since' => $user->service_provider_since ?? now(),
            ]);

            // Enregistrer les préférences de notification si fournies
            if ($request->has('notification_preferences')) {
                $notifPrefs = $request->input('notification_preferences');
                $notifUpdate = [];
                if (isset($notifPrefs['pro_notifications_email'])) {
                    $notifUpdate['pro_notifications_email'] = (bool) $notifPrefs['pro_notifications_email'];
                }
                if (isset($notifPrefs['pro_notifications_realtime'])) {
                    $notifUpdate['pro_notifications_realtime'] = (bool) $notifPrefs['pro_notifications_realtime'];
                }
                if (isset($notifPrefs['pro_notifications_sms'])) {
                    $notifUpdate['pro_notifications_sms'] = (bool) $notifPrefs['pro_notifications_sms'];
                }
                if (!empty($notifUpdate)) {
                    $user->update($notifUpdate);
                }
            }

            DB::commit();

            // Si un plan est sélectionné, vérifier d'abord si l'utilisateur a déjà un abonnement actif
            if ($request->filled('plan') && in_array($request->plan, ['monthly', 'annual'])) {
                
                // Vérifier si l'utilisateur a déjà un abonnement actif
                if ($user->hasActiveProSubscription()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Vos nouveaux services ont été ajoutés à votre profil. Vous avez déjà un abonnement Pro actif. Vos services précédents ont été conservés.',
                        'services_count' => count($request->services),
                        'is_service_provider' => true,
                        'has_subscription' => true,
                        'already_subscribed' => true,
                    ]);
                }

                $planType = $request->plan;
                $amount = $planType === 'annual' ? 85.00 : 9.99;
                $planLabel = $planType === 'annual' ? 'Abonnement ProxiPro Annuel' : 'Abonnement ProxiPro Mensuel';

                session([
                    'provider_subscription' => [
                        'plan' => $planType,
                        'categories' => array_map(fn($s) => $s['main_category'], $request->services),
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
                        'success_url' => route('service-provider.payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                        'cancel_url' => route('service-provider.payment.cancel'),
                        'metadata' => [
                            'user_id' => $user->id,
                            'type' => 'provider_subscription',
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
                    \Log::error('ServiceProvider Stripe error: ' . $e->getMessage());
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur lors de la création du paiement. Veuillez réessayer.',
                    ], 500);
                }
            }

            // Sans abonnement
            return response()->json([
                'success' => true,
                'message' => 'Félicitations ! Vos nouveaux services ont été ajoutés à votre profil. Vos informations précédentes ont été conservées. Vous pouvez les modifier depuis votre profil.',
                'services_count' => count($request->services),
                'is_service_provider' => true,
                'has_subscription' => false,
                'plan' => null,
                'redirect' => route('service-provider.mes-services')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue. Veuillez réessayer.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Handle successful Stripe payment for provider subscription
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

            $existingSub = ProSubscription::where('stripe_payment_intent', $session->payment_intent)->first();
            if ($existingSub) {
                return redirect()->route('service-provider.mes-services')->with('success', 'Votre abonnement est déjà actif !');
            }

            $data = session('provider_subscription', []);
            $plan = $data['plan'] ?? $session->metadata->plan ?? 'monthly';
            $amount = $plan === 'annual' ? 85.00 : 9.99;
            $categories = $data['categories'] ?? [];

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
                    'selected_categories' => $categories,
                    'intervention_radius' => $user->pro_intervention_radius ?? 30,
                    'stripe_payment_intent' => $session->payment_intent,
                ]);

                $user->update([
                    'pro_subscription_plan' => $plan,
                    'user_type' => 'professionnel',
                    'pro_status' => 'active',
                    'pro_onboarding_completed' => true,
                    'profile_completed' => true,
                    'profile_completed_at' => now(),
                ]);

                DB::commit();
                session()->forget('provider_subscription');

                return redirect()->route('service-provider.mes-services')->with('success', 'Félicitations ! Votre abonnement ' . ($plan === 'annual' ? 'annuel' : 'mensuel') . ' est activé !');

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('ServiceProvider payment success DB error: ' . $e->getMessage());
                return redirect()->route('feed')->with('error', 'Erreur lors de l\'activation. Contactez le support.');
            }

        } catch (\Exception $e) {
            \Log::error('ServiceProvider payment verification error: ' . $e->getMessage());
            return redirect()->route('feed')->with('error', 'Erreur de vérification du paiement.');
        }
    }

    /**
     * Handle cancelled Stripe payment
     */
    public function paymentCancel()
    {
        session()->forget('provider_subscription');
        return redirect()->route('service-provider.mes-services')->with('info', 'Paiement annulé. Votre profil prestataire est actif mais sans abonnement.');
    }

    /**
     * Affiche la page "Mes Services" dédiée au prestataire
     */
    public function mesServices()
    {
        $user = Auth::user();
        $services = $user->services()->orderBy('main_category')->orderBy('subcategory')->get();
        $categoriesCount = $services->pluck('main_category')->unique()->count();
        $subscription = ProSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        return view('service-provider.mes-services', compact('user', 'services', 'categoriesCount', 'subscription'));
    }

    /**
     * Désactive le statut prestataire
     */
    public function deactivate(Request $request)
    {
        $user = Auth::user();

        try {
            // Désactiver tous les services
            $user->services()->update(['is_active' => false]);
            
            // Désactiver le statut prestataire
            $user->update(['is_service_provider' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Votre statut de prestataire a été désactivé. Vous n\'apparaîtrez plus dans les recherches de professionnels.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.'
            ], 500);
        }
    }

    /**
     * Récupère les services actuels de l'utilisateur
     */
    public function getMyServices()
    {
        $user = Auth::user();
        $services = $user->services()->get();

        return response()->json([
            'success' => true,
            'is_service_provider' => $user->is_service_provider,
            'services' => $services,
            'categories' => $this->getServiceCategories()
        ]);
    }

    /**
     * Met à jour un service spécifique
     */
    public function updateService(Request $request, $id)
    {
        $user = Auth::user();
        $service = $user->services()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'experience_years' => 'nullable|integer|min:0|max:50',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $service->update($request->only(['experience_years', 'description', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Service mis à jour.',
            'service' => $service
        ]);
    }

    /**
     * Supprime un service
     */
    public function deleteService($id)
    {
        $user = Auth::user();
        $service = $user->services()->findOrFail($id);
        $service->delete();

        // Si plus aucun service actif, désactiver le statut prestataire
        if ($user->services()->active()->count() === 0) {
            $user->update(['is_service_provider' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Service supprimé.',
            'is_service_provider' => $user->is_service_provider
        ]);
    }

    /**
     * Met à jour des champs de profil individuels (city, address, GPS, notifications)
     */
    public function updateProfileFields(Request $request)
    {
        $user = Auth::user();
        $data = [];

        $allowed = ['name', 'phone', 'city', 'address', 'country', 'postal_code', 'latitude', 'longitude',
                     'pro_notifications_email', 'pro_notifications_realtime', 'pro_notifications_sms'];

        foreach ($allowed as $field) {
            if ($request->has($field)) {
                $value = $request->input($field);
                if (in_array($field, ['pro_notifications_email', 'pro_notifications_realtime', 'pro_notifications_sms'])) {
                    $data[$field] = (bool) $value;
                } elseif (in_array($field, ['latitude', 'longitude'])) {
                    $data[$field] = $value ? (float) $value : null;
                } else {
                    $data[$field] = is_string($value) ? mb_substr($value, 0, 255) : null;
                }
            }
        }

        if (!empty($data)) {
            $user->update($data);
        }

        return response()->json(['success' => true]);
    }
}

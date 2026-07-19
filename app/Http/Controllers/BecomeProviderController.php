<?php

namespace App\Http\Controllers;

use App\Models\UserService;
use App\Services\ProviderSubscriptionService;
use App\Support\MarketplaceCategoryRegistry;
use App\Support\PlatformFeatures;
use App\Support\ProviderSubscriptionPlans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BecomeProviderController extends Controller
{
    public function __construct(private ProviderSubscriptionService $providerSubscriptionService) {}

    /**
     * Liste des pays disponibles
     */
    private function getCountries(): array
    {
        return config('locations.countries', []);
    }

    /**
     * Villes principales par pays
     */
    private function getCitiesByCountry(): array
    {
        return config('locations.cities', []);
    }

    /**
     * Liste des catégories avec sous-catégories (source unique : config/categories.php)
     */
    private function getServiceCategories(): array
    {
        $categories = [];
        foreach (\App\Support\MarketplaceCategoryRegistry::enabledServices() as $name => $data) {
            $categories[$name] = [
                'icon' => $data['icon'],
                'color' => $data['color'],
                'subcategories' => $data['subcategories'],
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

        $missingProfileFields = $user->verificationProfileMissingFields();
        if ($missingProfileFields !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Complétez toutes les informations de votre profil avant de devenir prestataire.',
                'missing_profile_fields' => array_column($missingProfileFields, 'label'),
                'redirect' => route('profile.edit'),
            ], 422);
        }

        if (! $user->hasVerifiedProfileBadge()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez obtenir le badge « Profil vérifié » avant de devenir prestataire.',
                'profile_verification_required' => true,
                'redirect' => route('verification.index'),
            ], 422);
        }

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
            'pro_subscriptions_enabled' => PlatformFeatures::proSubscriptionsEnabled(),
        ]);
    }

    /**
     * Enregistre le profil prestataire complet
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $missingProfileFields = $user->verificationProfileMissingFields();
        if ($missingProfileFields !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Complétez toutes les informations de votre profil avant de devenir prestataire.',
                'missing_profile_fields' => array_column($missingProfileFields, 'label'),
                'redirect' => route('profile.edit'),
            ], 422);
        }

        if (! $user->hasVerifiedProfileBadge()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez obtenir le badge « Profil vérifié » avant de devenir prestataire.',
                'profile_verification_required' => true,
                'redirect' => route('verification.index'),
            ], 422);
        }

        $enabledServices = MarketplaceCategoryRegistry::enabledServices();
        $enabledSubcategories = collect($enabledServices)
            ->flatMap(fn (array $definition): array => $definition['subcategories'] ?? [])
            ->unique()
            ->values()
            ->all();

        // Validation
        $rules = [
            'business_type' => 'nullable|in:entreprise,auto_entrepreneur',
            'company_name' => 'nullable|string|max:255',
            'category' => ['required', 'string', 'max:100', Rule::in(array_keys($enabledServices))],
            'subcategory' => ['required', 'string', 'max:100', Rule::in($enabledSubcategories)],
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'plan' => 'nullable|in:monthly,annual',
        ];

        $messages = [
            'business_type.in' => 'Type d\'activité invalide.',
            'category.required' => 'Veuillez sélectionner une catégorie.',
            'subcategory.required' => 'Veuillez sélectionner une sous-catégorie (métier).',
            'country.required' => 'Veuillez sélectionner votre pays.',
            'city.required' => 'Veuillez sélectionner votre ville.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! in_array($request->subcategory, $enabledServices[$request->category]['subcategories'] ?? [], true)) {
            return response()->json([
                'success' => false,
                'errors' => ['subcategory' => ['Le métier choisi ne correspond pas à une activité actuellement ouverte.']],
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Mettre à jour le nom si différent
            $updateData = [
                'profession' => $request->subcategory, // Le métier est la sous-catégorie
                'country' => $request->country,
                'city' => $request->city,
                'is_service_provider' => true,
                'service_provider_since' => now(),
                'profile_completed' => true,
                'profile_completed_at' => now(),
                'max_active_ads' => $user->max_active_ads ?? 5,
            ];

            if ($user->user_type !== 'professionnel' && $user->account_type !== 'professionnel') {
                $updateData['account_type'] = 'particulier';
                $updateData['user_type'] = 'particulier';
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
            if (PlatformFeatures::proSubscriptionsEnabled()
                && $request->filled('plan')
                && in_array($request->plan, ['monthly', 'annual'], true)) {
                $planType = $request->plan;
                $planConfig = ProviderSubscriptionPlans::get($planType);
                if (! $planConfig || empty($planConfig['enabled'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cet abonnement n’est pas disponible pour le moment.',
                    ], 422);
                }
                // Sauvegarder les données pour finaliser après paiement
                session([
                    'become_provider_subscription' => [
                        'plan' => $planType,
                        'category' => $request->category,
                    ],
                ]);

                try {
                    $session = $this->providerSubscriptionService->createCheckoutSession(
                        $user,
                        $planType,
                        route('become-provider.payment.success').'?session_id={CHECKOUT_SESSION_ID}',
                        route('become-provider.payment.cancel'),
                        'become_provider_subscription'
                    );

                    return response()->json([
                        'success' => true,
                        'requires_payment' => true,
                        'checkout_url' => $session->url,
                        'message' => 'Redirection vers le paiement...',
                    ]);

                } catch (\Throwable $e) {
                    \Log::error('BecomeProvider Stripe error: '.$e->getMessage());

                    return response()->json([
                        'success' => false,
                        'message' => $e instanceof \DomainException
                            ? $e->getMessage()
                            : 'Erreur lors de la création du paiement. Veuillez réessayer.',
                    ], $e instanceof \DomainException ? 422 : 500);
                }
            }

            // Sans abonnement — profil activé directement
            return response()->json([
                'success' => true,
                'message' => 'Félicitations ! Votre profil prestataire est maintenant actif.',
                'user' => [
                    'name' => $user->name,
                    'profession' => $user->profession,
                    'account_type' => $user->account_type,
                    'is_service_provider' => true,
                ],
                'has_subscription' => false,
                'plan' => null,
                'show_verification' => true,
                'redirect' => route('pro.dashboard'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('BecomeProvider error: '.$e->getMessage());

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

        if (! $sessionId || ! $user) {
            return redirect()->route('feed')->with('error', 'Session de paiement invalide.');
        }

        try {
            $data = session('become_provider_subscription', []);
            $category = $data['category'] ?? null;

            $subscription = $this->providerSubscriptionService->completeCheckout(
                $sessionId,
                $user,
                ['selected_categories' => $category ? [$category] : []]
            );
            $plan = $subscription->plan;

            DB::beginTransaction();
            try {
                $user->update([
                    'pro_subscription_plan' => $plan,
                    'pro_status' => 'active',
                    'pro_onboarding_completed' => true,
                ]);

                DB::commit();
                session()->forget('become_provider_subscription');

                return redirect()->route('pro.dashboard')->with('success', 'Félicitations ! Votre abonnement '.($plan === 'annual' ? 'annuel' : 'mensuel').' est activé !');

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('BecomeProvider payment success DB error: '.$e->getMessage());

                return redirect()->route('feed')->with('error', 'Erreur lors de l\'activation. Contactez le support.');
            }

        } catch (\Throwable $e) {
            \Log::error('BecomeProvider payment verification error: '.$e->getMessage());

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

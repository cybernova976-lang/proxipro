<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\User;
use App\Models\UserService;
use App\Services\GeocodingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DemandController extends Controller
{
    /**
     * Affiche le formulaire simplifié de demande de service
     */
    public function create(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Connectez-vous pour publier une demande.');
        }

        $categoriesData = [];
        foreach (config('categories.services') as $name => $data) {
            $categoriesData[$name] = [
                'icon' => $data['icon'],
                'fa_icon' => $data['fa_icon'],
                'color' => $data['color'],
                'subcategories' => $data['subcategories'],
            ];
        }

        // Pré-sélection si paramètres dans l'URL
        $preCategory = $request->get('category');
        $preSubcategory = $request->get('subcategory');

        return view('demands.create', compact('categoriesData', 'preCategory', 'preSubcategory'));
    }

    /**
     * Enregistre la demande et redirige vers la page de matching
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'main_category' => 'required|string|max:100',
            'category' => 'required|string|max:100',
            'country' => 'required|string',
            'city' => 'nullable|string',
            'location' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'urgency' => 'nullable|in:normal,urgent,tres_urgent',
            'photos' => 'nullable|array|max:2',
            'photos.*' => 'image|mimes:jpeg,png,webp|max:5120',
        ]);

        $finalLocation = $request->location;
        if (empty($finalLocation) && $request->city && $request->city !== '__other__') {
            $finalLocation = $request->city;
        }

        if (empty($finalLocation)) {
            return back()->withErrors(['location' => 'Veuillez indiquer une ville.'])->withInput();
        }

        // Géocodage
        $fullAddress = $finalLocation . ', ' . $request->country;
        $geocodingService = new GeocodingService();
        $coordinates = $geocodingService->geocode($fullAddress);

        $ad = new Ad();
        $ad->title = $request->title;
        $ad->description = $request->description;
        $ad->category = $request->category;
        $ad->location = $finalLocation;
        $ad->price = $request->price;
        $ad->service_type = 'demande';
        $ad->radius_km = 50;
        $ad->user_id = Auth::id();
        $ad->country = $request->country;
        $ad->reply_restriction = 'everyone';
        $ad->visibility = 'public';
        $ad->is_urgent = ($request->urgency === 'urgent' || $request->urgency === 'tres_urgent');

        if ($coordinates) {
            $ad->latitude = $coordinates['latitude'];
            $ad->longitude = $coordinates['longitude'];
            $ad->address = $coordinates['address'];
            $ad->postal_code = $coordinates['postal_code'];
        }

        $ad->save();

        // Photos
        if ($request->hasFile('photos')) {
            $paths = [];
            foreach ($request->file('photos') as $photo) {
                $paths[] = $photo->store('ads', config('filesystems.default', 'public'));
            }
            $ad->photos = $paths;
            $ad->save();
        }

        // Notifier les professionnels correspondants
        try {
            app(AdController::class)->notifyMatchingProfessionals($ad);
        } catch (\Exception $e) {
            Log::error('Demand matching notification failed: ' . $e->getMessage(), [
                'ad_id' => $ad->id,
                'category' => $ad->category,
                'exception' => get_class($e),
            ]);
        }

        return redirect()->route('demand.matching', $ad);
    }

    /**
     * Page de résultats : professionnels correspondant à la demande
     */
    public function matching(Ad $ad)
    {
        if ($ad->service_type !== 'demande') {
            return redirect()->route('feed');
        }

        $category = $ad->category;
        $professionals = collect();

        // Chercher les pros via la table user_services
        $serviceUserIds = UserService::where('is_active', true)
            ->where(function ($q) use ($category) {
                $q->where('main_category', $category)
                  ->orWhere('subcategory', $category);
            })
            ->where('user_id', '!=', $ad->user_id)
            ->pluck('user_id')
            ->unique();

        // Chercher les pros via leurs catégories de profil
        $profileProIds = User::where('id', '!=', $ad->user_id)
            ->where(function ($q) {
                $q->where('user_type', 'professionnel')
                  ->orWhere('is_service_provider', true);
            })
            ->get()
            ->filter(function ($user) use ($category) {
                $proCats = $user->pro_service_categories ?? [];
                $subCats = $user->service_subcategories ?? [];
                return in_array($category, $proCats) 
                    || in_array($category, is_array($subCats) ? $subCats : [])
                    || $user->profession === $category;
            })
            ->pluck('id');

        // Chercher les pros via leurs annonces dans la même catégorie
        $adProIds = User::where('id', '!=', $ad->user_id)
            ->where(function ($q) {
                $q->where('user_type', 'professionnel')
                  ->orWhere('is_service_provider', true);
            })
            ->whereHas('ads', function ($q) use ($category) {
                $q->where('status', 'active')
                  ->where('service_type', 'offre')
                  ->where('category', $category);
            })
            ->pluck('id');

        $allProIds = $serviceUserIds->merge($profileProIds)->merge($adProIds)->unique();

        if ($allProIds->isNotEmpty()) {
            $professionals = User::whereIn('id', $allProIds)
                ->with(['services'])
                ->withCount(['ads as active_ads_count' => function ($q) {
                    $q->where('status', 'active');
                }])
                ->get()
                ->sortByDesc(function ($user) {
                    // Trier : abonnés Pro > prestataires > ancienneté
                    $score = 0;
                    if ($user->hasActiveProSubscription()) $score += 100;
                    if ($user->is_service_provider) $score += 50;
                    return $score;
                });
        }

        // Chercher aussi dans la catégorie parente (plus large) si peu de résultats
        $parentCategory = null;
        $additionalPros = collect();
        if ($professionals->count() < 5) {
            foreach (config('categories.services') as $catName => $catData) {
                if (in_array($category, $catData['subcategories'] ?? [])) {
                    $parentCategory = $catName;
                    break;
                }
            }

            if ($parentCategory) {
                $siblingSubcats = config("categories.services.{$parentCategory}.subcategories", []);
                $extraIds = UserService::where('is_active', true)
                    ->whereIn('subcategory', $siblingSubcats)
                    ->where('user_id', '!=', $ad->user_id)
                    ->whereNotIn('user_id', $allProIds)
                    ->pluck('user_id')
                    ->unique()
                    ->take(10);

                if ($extraIds->isNotEmpty()) {
                    $additionalPros = User::whereIn('id', $extraIds)
                        ->with(['services'])
                        ->get();
                }
            }
        }

        return view('demands.matching', compact('ad', 'professionals', 'additionalPros', 'parentCategory'));
    }

    /**
     * API : retourne les pros matching en JSON (pour AJAX)
     */
    public function matchingApi(Ad $ad)
    {
        if ($ad->service_type !== 'demande') {
            return response()->json(['success' => false], 400);
        }

        $category = $ad->category;

        $serviceUserIds = UserService::where('is_active', true)
            ->where(function ($q) use ($category) {
                $q->where('main_category', $category)
                  ->orWhere('subcategory', $category);
            })
            ->where('user_id', '!=', $ad->user_id)
            ->pluck('user_id')
            ->unique();

        $profileProIds = User::where('id', '!=', $ad->user_id)
            ->where(function ($q) {
                $q->where('user_type', 'professionnel')
                  ->orWhere('is_service_provider', true);
            })
            ->get()
            ->filter(function ($user) use ($category) {
                $proCats = $user->pro_service_categories ?? [];
                $subCats = $user->service_subcategories ?? [];
                return in_array($category, $proCats)
                    || in_array($category, is_array($subCats) ? $subCats : []);
            })
            ->pluck('id');

        $allProIds = $serviceUserIds->merge($profileProIds)->unique();

        $professionals = User::whereIn('id', $allProIds)
            ->with(['services'])
            ->get()
            ->map(function ($pro) {
                return [
                    'id' => $pro->id,
                    'name' => $pro->name,
                    'avatar' => $pro->avatar ? storage_url($pro->avatar) : null,
                    'profession' => $pro->profession,
                    'city' => $pro->city,
                    'bio' => \Str::limit($pro->bio, 120),
                    'is_pro' => $pro->hasActiveProSubscription(),
                    'is_verified' => $pro->is_verified ?? false,
                    'profile_url' => route('profile.public', $pro->id),
                ];
            });

        return response()->json([
            'success' => true,
            'professionals' => $professionals,
            'total' => $professionals->count(),
        ]);
    }
}

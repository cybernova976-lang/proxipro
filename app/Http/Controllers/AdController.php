<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\User;
use App\Models\UserService;
use App\Notifications\NewAdMatchingNotification;
use App\Services\GeocodingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdController extends Controller
{
    /**
     * Display a listing of the resource with advanced search.
     */
    public function index(Request $request)
    {
        $isMyAds = false;
        $userFilter = $request->input('user');

        if ($userFilter && Auth::check() && (int) $userFilter === Auth::id()) {
            $query = Ad::query()->where('user_id', Auth::id());
            $isMyAds = true;
        } else {
            $query = Ad::query()->where('status', 'active');
        }
        
        // Filtres de recherche
        $searchTerm = $request->input('q');
        $location = $request->input('location');
        $category = $request->input('category');
        $radius = $request->input('radius', 10); // Rayon par défaut 10km
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $serviceType = $request->input('service_type');
        $sort = $request->input('sort', 'newest');
        
        // Recherche par mot-clé
        if ($searchTerm) {
            $query->search($searchTerm);
        }
        
        // Recherche par catégorie
        if ($category) {
            $query->byCategory($category);
        }
        
        // Recherche par type de service
        if ($serviceType) {
            $query->where('service_type', $serviceType);
        }
        
        // Recherche par prix
        if ($minPrice !== null || $maxPrice !== null) {
            $query->byPriceRange($minPrice, $maxPrice);
        }
        
        // Recherche géolocalisée
        $hasGeoSearch = false;
        if ($location) {
            $geocodingService = new GeocodingService();
            $coordinates = $geocodingService->geocode($location);
            
            if ($coordinates) {
                $query->withinRadius(
                    $coordinates['latitude'],
                    $coordinates['longitude'],
                    $radius
                );
                $hasGeoSearch = true;
            } else {
                // Fallback : recherche textuelle
                $query->where('location', 'LIKE', "%{$location}%");
            }
        } elseif ($request->has('lat') && $request->has('lng')) {
            // Si coordonnées directes fournies
            $query->withinRadius(
                $request->input('lat'),
                $request->input('lng'),
                $radius
            );
            $hasGeoSearch = true;
        }
        
        // Tri
        if (!$hasGeoSearch) {
            switch ($sort) {
                case 'price_low':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_high':
                    $query->orderBy('price', 'desc');
                    break;
                case 'newest':
                default:
                    $query->latest();
                    break;
            }
        }
        
        // Pagination
        $ads = $query->paginate(12)->appends($request->query());
        
        // Services populaires pour suggestions
        $popularServices = Ad::popularServicesByRegion($location ?? 'France');
        
        // Catégories disponibles - depuis config/categories.php (source unique)
        $categories = array_merge(
            array_keys(config('categories.services')),
            array_keys(config('categories.marketplace'))
        );
        
        return view('ads.index', compact('ads', 'popularServices', 'categories', 'isMyAds'));
    }

    /**
     * Display the authenticated user's ads.
     */
    public function myAds(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $request->merge(['user' => Auth::id()]);
        return $this->index($request);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Vérifier si l'utilisateur est connecté
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Vous devez être connecté pour publier une annonce.');
        }
        
        // Catégories disponibles - depuis config/categories.php (source unique)
        $categories = array_merge(
            array_keys(config('categories.services')),
            array_keys(config('categories.marketplace'))
        );
        return view('ads.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
      try {
        $maxPhotos = Auth::user() && Auth::user()->hasActiveProSubscription() ? 4 : 2;

        // Valider les données
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'country' => 'required|string',
            'city' => 'nullable|string',
            'location' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'service_type' => 'required|in:offre,demande',
            'radius_km' => 'nullable|integer|min:1|max:100',
            'photos' => 'nullable|array|max:' . $maxPhotos,
            'photos.*' => 'image|mimes:jpeg,png,webp|max:5120',
            'reply_restriction' => 'nullable|in:everyone,pro_only,verified_only',
            'visibility' => 'nullable|in:public,pro_targeted',
            'target_categories' => 'nullable|array',
            'target_categories.*' => 'string'
        ]);

        // Déterminer la localisation finale
        $finalLocation = $request->location;
        if (empty($finalLocation) && $request->city && $request->city !== '__other__') {
            $finalLocation = $request->city;
        }
        
        if (empty($finalLocation)) {
            return back()->withErrors(['location' => 'Veuillez sélectionner une ville ou saisir une adresse.'])->withInput();
        }

        // Construire l'adresse complète pour le géocodage
        $fullAddress = $finalLocation . ', ' . $request->country;

        // Géocoder l'adresse
        $coordinates = null;
        try {
            $geocodingService = new GeocodingService();
            $coordinates = $geocodingService->geocode($fullAddress);
        } catch (\Exception $e) {
            Log::warning('Géocodage échoué lors de la création: ' . $e->getMessage());
        }
        
        // Créer l'annonce
        $ad = new Ad();
        $ad->title = $request->title;
        $ad->description = $request->description;
        $ad->category = $request->category;
        $ad->location = $finalLocation;
        $ad->price = $request->price;
        $ad->service_type = $request->service_type;
        $ad->radius_km = $request->radius_km ?? 10;
        $ad->user_id = Auth::id();
        $ad->country = $request->country;
        $ad->reply_restriction = $request->reply_restriction ?? 'everyone';
        $ad->visibility = $request->visibility ?? 'public';
        $ad->target_categories = $request->visibility === 'pro_targeted' ? $request->target_categories : null;

        // Si géocodage réussi
        if ($coordinates) {
            $ad->latitude = $coordinates['latitude'];
            $ad->longitude = $coordinates['longitude'];
            $ad->address = $coordinates['address'];
            $ad->postal_code = $coordinates['postal_code'];
        }
        
        $ad->save();

        if ($request->hasFile('photos')) {
            try {
                $defaultDisk = config('filesystems.default', 'public');
                $diskDriver  = config('filesystems.disks.' . $defaultDisk . '.driver', 'local');

                Log::info('Ad photos upload — disk: ' . $defaultDisk . ', driver: ' . $diskDriver, [
                    'ad_id'       => $ad->id,
                    'photo_count' => count($request->file('photos')),
                ]);

                $paths = [];
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('ads', $defaultDisk);
                    if ($path) {
                        $paths[] = $path;
                    } else {
                        Log::error('Ad photo store() returned empty path', [
                            'ad_id'  => $ad->id,
                            'disk'   => $defaultDisk,
                            'driver' => $diskDriver,
                        ]);
                    }
                }
                $ad->photos = $paths;
                $ad->save();

                Log::info('Ad photos stored successfully', ['ad_id' => $ad->id, 'paths' => $paths]);
            } catch (\Exception $e) {
                Log::error('Erreur upload photos pour annonce #' . $ad->id . ': ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'trace'     => $e->getTraceAsString(),
                ]);
                // Ad was already saved — continue to redirect rather than failing the whole request.
            }
        }

        // Notifier les professionnels correspondants
        try {
            $this->notifyMatchingProfessionals($ad);
        } catch (\Exception $e) {
            Log::warning('Failed to notify matching professionals for ad #' . $ad->id . ': ' . $e->getMessage());
        }

        // Rediriger vers la page après publication (urgent + boost)
        return redirect()->route('boost.after-creation', $ad);
      } catch (\Exception $e) {
          Log::error('STORE AD CRASH: ' . $e->getMessage(), [
              'exception' => get_class($e),
              'file'      => $e->getFile() . ':' . $e->getLine(),
              'trace'     => $e->getTraceAsString(),
          ]);
          return back()->withErrors(['general' => 'Erreur serveur: ' . $e->getMessage()])->withInput();
      }
    }

    /**
     * Store a new ad via AJAX from the feed popup (returns JSON).
     */
    public function storeFromPopup(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        $maxPhotos = Auth::user()->hasActiveProSubscription() ? 4 : 2;

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'country' => 'required|string',
            'city' => 'nullable|string',
            'location' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'service_type' => 'required|in:offre,demande',
            'photos' => 'nullable|array|max:' . $maxPhotos,
            'photos.*' => 'image|mimes:jpeg,png,webp|max:5120',
        ]);

        $finalLocation = $request->location;
        if (empty($finalLocation) && $request->city && $request->city !== '__other__') {
            $finalLocation = $request->city;
        }

        if (empty($finalLocation)) {
            return response()->json(['success' => false, 'errors' => ['location' => ['Veuillez sélectionner une ville.']]], 422);
        }

        $fullAddress = $finalLocation . ', ' . $request->country;
        $coordinates = null;
        try {
            $geocodingService = new GeocodingService();
            $coordinates = $geocodingService->geocode($fullAddress);
        } catch (\Exception $e) {
            Log::warning('Géocodage échoué (popup): ' . $e->getMessage());
        }

        $ad = new Ad();
        $ad->title = $request->title;
        $ad->description = $request->description;
        $ad->category = $request->category;
        $ad->location = $finalLocation;
        $ad->price = $request->price;
        $ad->service_type = $request->service_type;
        $ad->radius_km = 10;
        $ad->user_id = Auth::id();
        $ad->country = $request->country;
        $ad->reply_restriction = 'everyone';
        $ad->visibility = 'public';

        if ($coordinates) {
            $ad->latitude = $coordinates['latitude'];
            $ad->longitude = $coordinates['longitude'];
            $ad->address = $coordinates['address'];
            $ad->postal_code = $coordinates['postal_code'];
        }

        $ad->save();

        if ($request->hasFile('photos')) {
            try {
                $defaultDisk = config('filesystems.default', 'public');
                $diskDriver  = config('filesystems.disks.' . $defaultDisk . '.driver', 'local');

                Log::info('Popup ad photos upload — disk: ' . $defaultDisk . ', driver: ' . $diskDriver, [
                    'ad_id'       => $ad->id,
                    'photo_count' => count($request->file('photos')),
                ]);

                $paths = [];
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('ads', $defaultDisk);
                    if ($path) {
                        $paths[] = $path;
                    } else {
                        Log::error('Popup ad photo store() returned empty path', [
                            'ad_id'  => $ad->id,
                            'disk'   => $defaultDisk,
                            'driver' => $diskDriver,
                        ]);
                    }
                }
                $ad->photos = $paths;
                $ad->save();

                Log::info('Popup ad photos stored successfully', ['ad_id' => $ad->id, 'paths' => $paths]);
            } catch (\Exception $e) {
                Log::error('Erreur upload photos pour annonce #' . $ad->id . ': ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'trace'     => $e->getTraceAsString(),
                ]);
            }
        }

        try {
            $this->notifyMatchingProfessionals($ad);
        } catch (\Exception $e) {
            Log::warning('Failed to notify matching professionals for ad #' . $ad->id . ': ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Votre demande a été publiée avec succès !',
            'ad_id' => $ad->id,
            'redirect_url' => route('boost.after-creation', $ad),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ad $ad)
    {
        $isSaved = Auth::check() ? Auth::user()->hasSavedAd($ad) : false;
        return view('ads.show', compact('ad', 'isSaved'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ad $ad)
    {
        // Vérifier que l'utilisateur est propriétaire
        if (Auth::id() !== $ad->user_id) {
            abort(403);
        }
        
        // Catégories disponibles - depuis config/categories.php (source unique)
        $categories = array_merge(
            array_keys(config('categories.services')),
            array_keys(config('categories.marketplace'))
        );
        return view('ads.edit', compact('ad', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ad $ad)
    {
      try {
        // Vérifier que l'utilisateur est propriétaire
        if (Auth::id() !== $ad->user_id) {
            abort(403);
        }
        
        $maxPhotos = Auth::user() && Auth::user()->hasActiveProSubscription() ? 4 : 2;

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'country' => 'required|string',
            'city' => 'nullable|string',
            'location' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'service_type' => 'required|in:offre,demande',
            'radius_km' => 'nullable|integer|min:1|max:100',
            'photos' => 'nullable|array|max:' . $maxPhotos,
            'photos.*' => 'image|mimes:jpeg,png,webp|max:5120',
            'reply_restriction' => 'nullable|in:everyone,pro_only,verified_only',
            'visibility' => 'nullable|in:public,pro_targeted',
            'target_categories' => 'nullable|array',
            'target_categories.*' => 'string',
        ]);

        // Déterminer la localisation finale
        $finalLocation = $request->location;
        if (empty($finalLocation) && $request->city && $request->city !== '__other__') {
            $finalLocation = $request->city;
        }
        
        if (empty($finalLocation)) {
            return back()->withErrors(['location' => 'Veuillez sélectionner une ville ou saisir une adresse.'])->withInput();
        }

        // Re-géocoder si la localisation ou le pays a changé
        if ($ad->location !== $finalLocation || $ad->country !== $request->country) {
            try {
                $fullAddress = $finalLocation . ', ' . $request->country;
                $geocodingService = new GeocodingService();
                $coordinates = $geocodingService->geocode($fullAddress);
                
                if ($coordinates) {
                    $ad->latitude = $coordinates['latitude'];
                    $ad->longitude = $coordinates['longitude'];
                    $ad->address = $coordinates['address'];
                    $ad->postal_code = $coordinates['postal_code'];
                }
            } catch (\Exception $e) {
                Log::warning('Géocodage échoué pour annonce #' . $ad->id . ': ' . $e->getMessage());
            }
        }
        
        $ad->title = $request->title;
        $ad->description = $request->description;
        $ad->category = $request->category;
        $ad->location = $finalLocation;
        $ad->country = $request->country;
        $ad->price = $request->price;
        $ad->service_type = $request->service_type;
        $ad->radius_km = $request->input('radius_km', 10);
        $ad->reply_restriction = $request->input('reply_restriction', 'everyone');
        $ad->visibility = $request->input('visibility', 'public');
        $ad->target_categories = $request->input('visibility') === 'pro_targeted' ? $request->input('target_categories') : null;
        $ad->save();

        if ($request->hasFile('photos')) {
            try {
                $defaultDisk = config('filesystems.default', 'public');
                $diskDriver  = config('filesystems.disks.' . $defaultDisk . '.driver', 'local');

                Log::info('Ad update photos upload — disk: ' . $defaultDisk . ', driver: ' . $diskDriver, [
                    'ad_id'       => $ad->id,
                    'photo_count' => count($request->file('photos')),
                ]);

                $existing = $ad->photos ?? [];
                foreach ($existing as $path) {
                    try {
                        Storage::disk($defaultDisk)->delete($path);
                    } catch (\Exception $e) {
                        Log::warning('Could not delete old ad photo: ' . $e->getMessage(), [
                            'ad_id' => $ad->id,
                            'path'  => $path,
                        ]);
                    }
                }
                $paths = [];
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('ads', $defaultDisk);
                    if ($path) {
                        $paths[] = $path;
                    } else {
                        Log::error('Ad update photo store() returned empty path', [
                            'ad_id'  => $ad->id,
                            'disk'   => $defaultDisk,
                            'driver' => $diskDriver,
                        ]);
                    }
                }
                $ad->photos = $paths;
                $ad->save();

                Log::info('Ad update photos stored successfully', ['ad_id' => $ad->id, 'paths' => $paths]);
            } catch (\Exception $e) {
                Log::error('Erreur upload photos pour annonce #' . $ad->id . ': ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'trace'     => $e->getTraceAsString(),
                ]);
                return back()->withErrors(['photos' => 'Erreur lors du téléchargement des photos. Veuillez réessayer.'])->withInput();
            }
        }

        return redirect()->route('ads.show', $ad)->with('success', 'Annonce mise à jour avec succès !');
      } catch (\Exception $e) {
          Log::error('UPDATE AD CRASH: ' . $e->getMessage(), [
              'ad_id'     => $ad->id ?? null,
              'exception' => get_class($e),
              'file'      => $e->getFile() . ':' . $e->getLine(),
              'trace'     => $e->getTraceAsString(),
          ]);
          return back()->withErrors(['general' => 'Erreur serveur: ' . $e->getMessage()])->withInput();
      }
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * Supprimer une photo spécifique d'une annonce.
     */
    public function deletePhoto(Ad $ad, $index)
    {
        if (Auth::id() !== $ad->user_id) {
            abort(403);
        }

        $photos = $ad->photos ?? [];
        $index = (int) $index;

        if (!isset($photos[$index])) {
            return response()->json(['error' => 'Photo introuvable.'], 404);
        }

        try {
            $defaultDisk = config('filesystems.default', 'public');
            Storage::disk($defaultDisk)->delete($photos[$index]);
        } catch (\Exception $e) {
            Log::warning('Impossible de supprimer la photo du stockage: ' . $e->getMessage(), [
                'ad_id' => $ad->id,
                'path'  => $photos[$index],
            ]);
        }
        
        array_splice($photos, $index, 1);
        $ad->photos = $photos;
        $ad->save();

        return response()->json(['success' => true]);
    }

    public function destroy(Ad $ad)
    {
        // Vérifier que l'utilisateur est propriétaire ou admin
        if (Auth::id() !== $ad->user_id && Auth::user()->role !== 'admin') {
            abort(403);
        }
        
        $ad->delete();
        
        return redirect()->back()->with('success', 'Annonce supprimée avec succès.');
    }

    /**
     * Notifier les professionnels dont le domaine correspond à la catégorie de l'annonce.
     */
    public function notifyMatchingProfessionals(Ad $ad): void
    {
        $category = $ad->category;
        if (!$category) {
            return;
        }

        $publisher = $ad->user;
        if (!$publisher) {
            Log::warning('Cannot notify professionals: ad #' . $ad->id . ' has no associated user.');
            return;
        }

        // Trouver les pros ayant cette catégorie dans leurs services actifs (main_category OU subcategory)
        $matchingUserIds = UserService::where('is_active', true)
            ->where(function ($q) use ($category) {
                $q->where('main_category', $category)
                  ->orWhere('subcategory', $category);
            })
            ->where('user_id', '!=', $ad->user_id)
            ->pluck('user_id')
            ->unique();

        // Aussi chercher dans pro_service_categories (JSON array sur le profil pro)
        $proUsers = User::where('id', '!=', $ad->user_id)
            ->where(function ($q) {
                $q->where('user_type', 'professionnel')
                  ->orWhere('is_service_provider', true);
            })
            ->where('pro_notifications_realtime', true)
            ->get()
            ->filter(function ($user) use ($category) {
                $proCategories = $user->pro_service_categories ?? [];
                $subCategories = $user->service_subcategories ?? [];
                return in_array($category, $proCategories)
                    || in_array($category, is_array($subCategories) ? $subCategories : []);
            })
            ->pluck('id');

        $allUserIds = $matchingUserIds->merge($proUsers)->unique();

        if ($allUserIds->isEmpty()) {
            return;
        }

        $professionals = User::whereIn('id', $allUserIds)->get();
        $notifiedCount = 0;

        foreach ($professionals as $pro) {
            try {
                $pro->notify(new NewAdMatchingNotification($ad, $publisher));
                $notifiedCount++;
            } catch (\Exception $e) {
                Log::error('Notification failed for pro #' . $pro->id . ' on ad #' . $ad->id . ': ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'ad_category' => $category,
                ]);
            }
        }

        if ($notifiedCount > 0) {
            Log::info('Notified ' . $notifiedCount . '/' . $professionals->count() . ' professionals for ad #' . $ad->id . ' (category: ' . $category . ')');
        }
    }
}

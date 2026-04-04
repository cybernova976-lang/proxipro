<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Ad;
use App\Models\Review;

class ProfileController extends Controller
{
    /**
     * Afficher le profil de l'utilisateur connecté
     */
    public function show()
    {
        // Forcer le rechargement depuis la base de données
        $user = \App\Models\User::with('services')->where('id', Auth::id())->firstOrFail();
        $user->refresh(); // Force le rafraîchissement des données depuis la DB
        
        // Récupérer les annonces de l'utilisateur
        $ads = Ad::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();
        
        // Statistiques
        $stats = [
            'total_ads' => Ad::where('user_id', $user->id)->count(),
            'active_ads' => Ad::where('user_id', $user->id)->where('status', 'active')->count(),
            'total_views' => Ad::where('user_id', $user->id)->sum('views'),
        ];

        // Vérification en cours
        $verification = \App\Models\IdentityVerification::where('user_id', $user->id)->latest()->first();

        return view('profile.show', compact('user', 'ads', 'stats', 'verification'));
    }

    /**
     * Afficher le formulaire d'édition du profil
     */
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Mettre à jour le profil
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'hourly_rate' => 'nullable|numeric|min:0|max:999',
            'show_hourly_rate' => 'nullable',
        ];

        $request->validate($rules);

        $data = $request->only(['name', 'email', 'phone', 'bio', 'location']);

        // Gérer le tarif horaire (prestataires uniquement)
        if ($user->user_type === 'professionnel' || $user->is_service_provider || $user->hasActiveProSubscription() || $user->hasCompletedProOnboarding()) {
            $data['hourly_rate'] = $request->filled('hourly_rate') ? $request->hourly_rate : null;
            $data['show_hourly_rate'] = $request->has('show_hourly_rate') ? true : false;
        }

        // Gérer l'upload d'avatar
        if ($request->hasFile('avatar')) {
            try {
                // Supprimer l'ancien avatar
                if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                    try {
                        Storage::disk('public')->delete($user->avatar);
                    } catch (\Exception $e) {
                        Log::warning('Impossible de supprimer l\'ancien avatar: ' . $e->getMessage());
                    }
                }
                
                $path = $request->file('avatar')->store('avatars', 'public');
                if ($path) {
                    $data['avatar'] = $path;
                } else {
                    return back()->withErrors(['avatar' => 'Erreur lors du téléchargement de la photo. Veuillez réessayer.'])->withInput();
                }
            } catch (\Exception $e) {
                Log::error('Erreur upload avatar: ' . $e->getMessage());
                return back()->withErrors(['avatar' => 'Erreur lors du téléchargement de la photo. Veuillez réessayer.'])->withInput();
            }
        }

        $user->update($data);

        return redirect()->route('profile.show')
            ->with('success', 'Profil mis à jour avec succès !');
    }

    /**
     * Sauvegarder les catégories et sous-catégories d'activité (modal post-connexion)
     */
    public function saveCategories(Request $request)
    {
        $request->validate([
            'service_category' => 'required|string|max:255',
            'service_subcategories' => 'required|array|min:1',
            'service_subcategories.*' => 'string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'pro_intervention_radius' => 'nullable|integer|min:1|max:200',
        ]);

        $user = Auth::user();

        // Mode additif : fusionner les nouvelles catégories/sous-catégories avec les existantes
        $existingCategory = $user->service_category;
        $newCategory = $request->input('service_category');

        // Si l'utilisateur avait déjà une catégorie différente, on garde les deux (séparées par virgule)
        if ($existingCategory && $existingCategory !== $newCategory) {
            $existingCats = array_map('trim', explode(',', $existingCategory));
            if (!in_array($newCategory, $existingCats)) {
                $existingCats[] = $newCategory;
            }
            $user->service_category = implode(', ', $existingCats);
        } else {
            $user->service_category = $newCategory;
        }

        // Fusionner les sous-catégories existantes avec les nouvelles
        $existingSubcategories = $user->service_subcategories ?? [];
        $newSubcategories = $request->input('service_subcategories');
        $mergedSubcategories = array_values(array_unique(array_merge(
            is_array($existingSubcategories) ? $existingSubcategories : [],
            is_array($newSubcategories) ? $newSubcategories : []
        )));
        $user->service_subcategories = $mergedSubcategories;

        // Ne remplacer la profession que si elle n'est pas déjà définie
        if (empty($user->profession)) {
            $user->profession = $newSubcategories[0] ?? null;
        }

        if ($request->filled('address')) {
            $user->address = $request->input('address');
        }
        if ($request->filled('city')) {
            $user->city = $request->input('city');
        }
        if ($request->filled('pro_intervention_radius')) {
            $user->pro_intervention_radius = $request->input('pro_intervention_radius');
        }

        $user->save();

        return response()->json(['success' => true, 'message' => 'Vos nouvelles informations ont été ajoutées à votre profil. Les données précédentes ont été conservées.']);
    }

    /**
     * Afficher le profil public d'un utilisateur
     */
    public function publicProfile($id)
    {
        // Forcer le rechargement depuis la base de données (sans cache)
        $user = \App\Models\User::with('services')->where('id', $id)->firstOrFail();
        $user->refresh(); // Force le rafraîchissement des données depuis la DB
        
        // Récupérer les annonces actives de l'utilisateur
        $ads = Ad::where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        
        // Statistiques publiques
        $stats = [
            'total_ads' => Ad::where('user_id', $user->id)->where('status', 'active')->count(),
            'member_since' => $user->created_at->format('M Y'),
        ];

        $reviews = Review::where('reviewed_user_id', $user->id)
            ->with('reviewer')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $ratingCount = Review::where('reviewed_user_id', $user->id)->count();
        $ratingAverage = Review::where('reviewed_user_id', $user->id)->avg('rating');
        $ratingAverage = $ratingAverage ? round($ratingAverage, 1) : 0;

        return view('profile.public', compact('user', 'ads', 'stats', 'reviews', 'ratingCount', 'ratingAverage'));
    }
}

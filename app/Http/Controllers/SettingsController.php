<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Conversation;

class SettingsController extends Controller
{
    /**
     * Afficher la page des paramètres
     */
    public function index()
    {
        $user = Auth::user();
        return view('settings.index', compact('user'));
    }

    /**
     * Mettre à jour le mot de passe
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Mot de passe mis à jour avec succès !');
    }

    /**
     * Mettre à jour les préférences de notification
     */
    public function updateNotifications(Request $request)
    {
        $user = Auth::user();

        $user->update([
            'email_notifications' => $request->boolean('email_notifications'),
            'sms_notifications' => $request->boolean('sms_notifications'),
            'push_notifications' => $request->boolean('push_notifications'),
        ]);

        return back()->with('success', 'Préférences de notification mises à jour !');
    }

    /**
     * Mettre à jour les paramètres de confidentialité
     */
    public function updatePrivacy(Request $request)
    {
        $user = Auth::user();

        $user->update([
            'profile_public' => $request->boolean('profile_public'),
            'show_email' => $request->boolean('show_email'),
            'show_phone' => $request->boolean('show_phone'),
        ]);

        return back()->with('success', 'Paramètres de confidentialité mis à jour !');
    }

    /**
     * Supprimer le compte et toutes les données personnelles
     */
    public function deleteAccount(Request $request)
    {
        $rules = [
            'confirm_delete' => 'required|accepted',
            'reason' => 'nullable|string|max:500',
        ];

        $user = Auth::user();

        // Les utilisateurs OAuth n'ont pas de mot de passe
        if (!$user->isOAuthUser()) {
            $rules['password'] = 'required';
        }

        $request->validate($rules, [
            'password.required' => 'Veuillez saisir votre mot de passe.',
            'confirm_delete.required' => 'Vous devez confirmer la suppression.',
            'confirm_delete.accepted' => 'Vous devez confirmer la suppression.',
        ]);

        // Vérifier le mot de passe (sauf OAuth)
        if (!$user->isOAuthUser() && !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Mot de passe incorrect.']);
        }

        try {
            DB::beginTransaction();

            // 1. Enregistrer la suppression pour audit
            $dataSummary = json_encode([
                'ads_count' => $user->ads()->count(),
                'messages_count' => $user->sentMessages()->count(),
                'reviews_given' => $user->reviewsGiven()->count(),
                'reviews_received' => $user->reviewsReceived()->count(),
                'account_type' => $user->account_type,
                'is_service_provider' => $user->is_service_provider,
            ]);

            DB::table('deleted_accounts')->insert([
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'account_type' => $user->account_type ?? 'particulier',
                'reason' => $request->input('reason', 'Non spécifié'),
                'data_summary' => $dataSummary,
                'deleted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Supprimer les fichiers uploadés
            $filesToDelete = array_filter([
                $user->avatar,
                $user->kbis_document,
                $user->id_document,
            ]);
            foreach ($filesToDelete as $file) {
                try {
                    if ($file && Storage::disk(config('filesystems.default', config('filesystems.default', 'public')))->exists($file)) {
                        Storage::disk(config('filesystems.default', config('filesystems.default', 'public')))->delete($file);
                    }
                } catch (\Exception $e) {
                    Log::warning('Impossible de supprimer le fichier lors de la suppression du compte: ' . $e->getMessage());
                }
            }

            // Supprimer les images des annonces
            foreach ($user->ads as $ad) {
                if (!empty($ad->photos)) {
                    $images = is_array($ad->photos) ? $ad->photos : json_decode($ad->photos, true) ?? [];
                    foreach ($images as $image) {
                        try {
                            if (Storage::disk(config('filesystems.default', config('filesystems.default', 'public')))->exists($image)) {
                                Storage::disk(config('filesystems.default', config('filesystems.default', 'public')))->delete($image);
                            }
                        } catch (\Exception $e) {
                            Log::warning('Impossible de supprimer l\'image d\'annonce: ' . $e->getMessage());
                        }
                    }
                }
            }

            // 3. Supprimer les données relationnelles
            // Annonces
            $user->ads()->delete();

            // Transactions de points
            $user->pointTransactions()->delete();

            // Services
            $user->services()->delete();

            // Avis reçus et donnés
            $user->reviewsReceived()->delete();
            $user->reviewsGiven()->delete();

            // Détacher les badges et annonces sauvegardées
            $user->badges()->detach();
            $user->savedAds()->detach();

            // Messages envoyés
            $user->sentMessages()->delete();

            // Conversations
            Conversation::where('user1_id', $user->id)
                ->orWhere('user2_id', $user->id)
                ->delete();

            // Transactions de paiement
            $user->transactions()->delete();

            // Données pro
            if (method_exists($user, 'proClients')) {
                $user->proClients()->delete();
            }
            if (method_exists($user, 'proQuotes')) {
                $user->proQuotes()->delete();
            }
            if (method_exists($user, 'proInvoices')) {
                $user->proInvoices()->delete();
            }
            if (method_exists($user, 'proDocuments')) {
                // Supprimer les fichiers des documents pro
                foreach ($user->proDocuments as $doc) {
                    if (!empty($doc->file_path) && Storage::disk(config('filesystems.default', config('filesystems.default', 'public')))->exists($doc->file_path)) {
                        Storage::disk(config('filesystems.default', config('filesystems.default', 'public')))->delete($doc->file_path);
                    }
                }
                $user->proDocuments()->delete();
            }
            if (method_exists($user, 'proSubscriptions')) {
                $user->proSubscriptions()->delete();
            }

            // Objets perdus
            if ($user->relationLoaded('lostItems') || method_exists($user, 'lostItems')) {
                try { $user->lostItems()->delete(); } catch (\Exception $e) {}
            } else {
                DB::table('lost_items')->where('user_id', $user->id)->delete();
            }

            // Signalements
            DB::table('reports')->where('reporter_id', $user->id)->delete();

            // Messages de contact
            DB::table('contact_messages')->where('user_id', $user->id)->delete();

            // Demandes de vérification
            DB::table('verification_requests')->where('user_id', $user->id)->delete();
            DB::table('identity_verifications')->where('user_id', $user->id)->delete();

            // 4. Anonymiser les données personnelles de l'utilisateur avant soft-delete
            $user->forceFill([
                'name' => 'Utilisateur supprimé',
                'email' => 'deleted_' . $user->id . '_' . time() . '@deleted.local',
                'phone' => null,
                'address' => null,
                'avatar' => null,
                'bio' => null,
                'company_name' => null,
                'siret' => null,
                'kbis_document' => null,
                'id_document' => null,
                'website_url' => null,
                'social_links' => null,
                'insurance_number' => null,
                'latitude' => null,
                'longitude' => null,
                'detected_city' => null,
                'detected_country' => null,
                'provider' => null,
                'provider_id' => null,
                'remember_token' => null,
            ])->save();

            // 5. Déconnecter l'utilisateur
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // 6. Soft-delete le compte
            $user->delete();

            DB::commit();

            Log::info('Compte utilisateur supprimé', ['user_id' => $user->id]);

            return redirect()->route('homepage')
                ->with('success', 'Votre compte et toutes vos données personnelles ont été supprimés avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression du compte', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['delete' => 'Une erreur est survenue lors de la suppression. Veuillez réessayer ou contacter le support.']);
        }
    }
}

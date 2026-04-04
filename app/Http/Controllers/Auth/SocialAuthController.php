<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Rediriger vers le fournisseur OAuth
     */
    public function redirect($provider)
    {
        // Valider le provider
        if (!in_array($provider, ['google', 'facebook'])) {
            return redirect()->route('login')->with('error', 'Fournisseur non supporté.');
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Gérer le callback du fournisseur OAuth
     */
    public function callback($provider)
    {
        try {
            // Valider le provider
            if (!in_array($provider, ['google', 'facebook'])) {
                return redirect()->route('login')->with('error', 'Fournisseur non supporté.');
            }

            $socialUser = Socialite::driver($provider)->user();
            $socialEmail = $socialUser->getEmail();
            $socialProviderId = $socialUser->getId();

            if (!$socialEmail) {
                return redirect()->route('login')->with('error', 'Impossible de récupérer votre adresse e-mail depuis ' . ucfirst($provider) . '. Veuillez vérifier les autorisations de votre compte.');
            }

            // ── 1. Chercher un utilisateur existant par provider_id (inclure les soft-deleted) ──
            $user = User::withTrashed()
                        ->where('provider', $provider)
                        ->where('provider_id', $socialProviderId)
                        ->first();

            if (!$user) {
                // ── 2. Chercher par email (inclure les soft-deleted) ──
                $user = User::withTrashed()
                            ->where('email', $socialEmail)
                            ->first();
            }

            // ── 3. Si l'utilisateur existe mais est soft-deleted → le restaurer ──
            if ($user && $user->trashed()) {
                \Log::info('Restoring soft-deleted user for social auth', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'provider' => $provider,
                ]);
                $user->restore();
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $socialProviderId,
                    'avatar' => $this->saveAvatar($socialUser->getAvatar()) ?? $user->avatar,
                    'name' => $socialUser->getName() ?? $user->name,
                ]);
                $user->email_verified_at = $user->email_verified_at ?? now();
                $user->is_active = true;
                $user->save();

                Auth::login($user, true);
                session()->flash('show_provider_welcome', true);
                return redirect()->route('feed')->with('success', 'Votre compte a été réactivé et connecté via ' . ucfirst($provider) . ' !');
            }

            // ── 4. Utilisateur existant actif ──
            if ($user) {
                // Si le compte n'était pas lié à ce provider, le lier
                if ($user->provider !== $provider || $user->provider_id !== $socialProviderId) {
                    $user->update([
                        'provider' => $provider,
                        'provider_id' => $socialProviderId,
                        'avatar' => $user->avatar ?? $this->saveAvatar($socialUser->getAvatar()),
                    ]);
                } else {
                    // Mettre à jour l'avatar si nécessaire
                    if (!$user->avatar && $socialUser->getAvatar()) {
                        $user->update([
                            'avatar' => $this->saveAvatar($socialUser->getAvatar()),
                        ]);
                    }
                }

                Auth::login($user, true);
                return redirect()->route('feed')->with('success', 'Connexion réussie via ' . ucfirst($provider) . ' !');
            }

            // ── 5. Nouvel utilisateur → Création du compte ──
            $user = User::create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'Utilisateur',
                'email' => $socialEmail,
                'password' => Hash::make(Str::random(24)),
                'provider' => $provider,
                'provider_id' => $socialProviderId,
                'avatar' => $this->saveAvatar($socialUser->getAvatar()),
            ]);

            // Set protected fields explicitly (not mass-assignable)
            $user->email_verified_at = now(); // Email vérifié par le provider
            $user->is_verified = false;
            $user->is_active = true;
            $user->role = 'user';
            $user->available_points = 5;
            $user->total_points = 5;
            $user->save();
            
            // Enregistrer la transaction de points de bienvenue
            if (class_exists(\App\Models\PointTransaction::class)) {
                try {
                    \App\Models\PointTransaction::create([
                        'user_id' => $user->id,
                        'points' => 5,
                        'type' => 'welcome_bonus',
                        'description' => 'Bonus de bienvenue à l\'inscription (5 points gratuits)',
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to log OAuth welcome points: ' . $e->getMessage());
                }
            }
            
            // Marquer comme nouvel utilisateur OAuth pour afficher le modal de bienvenue
            session()->flash('show_provider_welcome', true);

            // Connecter l'utilisateur
            Auth::login($user, true);

            return redirect()->route('feed')->with('success', 'Bienvenue ! Votre compte a été créé via ' . ucfirst($provider) . '.');

        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            \Log::warning('Social auth invalid state (user may have used back button): ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'La session a expiré. Veuillez réessayer la connexion avec ' . ucfirst($provider) . '.');
        } catch (\Exception $e) {
            \Log::error('Social auth error: ' . $e->getMessage(), [
                'provider' => $provider,
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('login')->with('error', 'Erreur de connexion avec ' . ucfirst($provider) . '. Veuillez réessayer.');
        }
    }

    /**
     * Sauvegarder l'avatar depuis l'URL du provider
     * With security: MIME validation + size limit
     */
    private function saveAvatar($avatarUrl)
    {
        if (!$avatarUrl) {
            return null;
        }

        try {
            // Use HTTP client with timeout and size limit
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withOptions(['stream' => true])
                ->get($avatarUrl);

            if (!$response->successful()) {
                return null;
            }

            $contents = $response->body();
            
            // Size check: max 2MB
            if (strlen($contents) > 2 * 1024 * 1024) {
                \Log::warning('Social avatar too large, skipping', ['url' => $avatarUrl]);
                return null;
            }

            // MIME type validation
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($contents);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($mimeType, $allowedMimes)) {
                \Log::warning('Social avatar invalid MIME type', ['mime' => $mimeType, 'url' => $avatarUrl]);
                return null;
            }

            // Map MIME to extension
            $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
            $ext = $extensions[$mimeType] ?? 'jpg';
            
            $filename = 'avatars/' . Str::uuid() . '.' . $ext;
            \Storage::disk(config('filesystems.default', 'public'))->put($filename, $contents);
            
            return $filename;
        } catch (\Exception $e) {
            \Log::warning('Could not save social avatar: ' . $e->getMessage());
            return null;
        }
    }
}

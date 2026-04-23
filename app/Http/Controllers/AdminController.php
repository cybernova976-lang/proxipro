<?php

namespace App\Http\Controllers;

use App\Mail\AdminTestMail;
use App\Models\User;
use App\Models\Ad;
use App\Models\Setting;
use App\Models\Advertisement;
use App\Models\IdentityVerification;
use App\Models\Report;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    // Tableau de bord admin
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_ads' => Ad::count(),
            'active_ads' => Ad::where('status', 'active')->count(),
            'pending_ads' => Ad::where('status', 'pending')->count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'verified_users' => User::where('is_verified', true)->count(),
            'pending_verifications' => IdentityVerification::where('status', 'pending')->count(),
        ];

        // Graphique des inscriptions (7 derniers jours)
        $registrations = User::select(
                DB::raw('created_at::date as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Dernières inscriptions
        $latestUsers = User::latest()->take(10)->get();

        // Dernières annonces
        $latestAds = Ad::with('user')->latest()->take(10)->get();

        // Dernières vérifications en attente
        $pendingVerifications = IdentityVerification::with('user')
            ->where('status', 'pending')
            ->where('payment_status', 'paid')
            ->latest('submitted_at')
            ->take(5)
            ->get();

        $mailSummary = $this->buildMailConfigSummary();

        return view('admin.dashboard', compact('stats', 'registrations', 'latestUsers', 'latestAds', 'pendingVerifications', 'mailSummary'));
    }

    // Liste des utilisateurs
    public function users(Request $request)
    {
        $query = User::withCount('ads');
        
        // Recherche par nom ou email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
        
        // Filtre par rôle
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        // Filtre par type d'utilisateur
        if ($request->filled('user_type')) {
            $query->where('user_type', $request->user_type);
        }
        
        // Filtre par statut
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'verified':
                    $query->where('is_verified', true);
                    break;
                case 'unverified':
                    $query->where('is_verified', false);
                    break;
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
            }
        }
        
        $users = $query->latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    // Voir un utilisateur
    public function showUser($id)
    {
        $user = User::with('ads')->findOrFail($id);
        return view('admin.users.show', compact('user'));
    }

    // Mettre à jour un utilisateur
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'role' => 'nullable|in:user,admin,moderator',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? $user->phone;
        $user->role = $validated['role'] ?? $user->role ?? 'user';
        
        $newVerifiedStatus = $request->boolean('is_verified');
        $oldVerifiedStatus = (bool) $user->is_verified;
        $user->is_verified = $newVerifiedStatus;
        
        // Synchroniser les champs identity_verified / pro_verified avec is_verified
        if ($newVerifiedStatus && !$oldVerifiedStatus) {
            // Admin marque comme vérifié → activer tous les flags
            $user->identity_verified = true;
            $user->identity_verified_at = now();
            $user->pro_verified = true;
            $user->pro_verified_at = now();
        } elseif (!$newVerifiedStatus && $oldVerifiedStatus) {
            // Admin retire la vérification → réinitialiser tous les flags
            $user->identity_verified = false;
            $user->identity_verified_at = null;
            $user->pro_verified = false;
            $user->pro_verified_at = null;
            
            // Aussi révoquer la vérification active si elle existe
            $activeVerification = \App\Models\IdentityVerification::where('user_id', $user->id)
                ->where('status', 'approved')
                ->first();
            if ($activeVerification) {
                $activeVerification->update([
                    'status' => 'rejected',
                    'rejection_reason' => 'Vérification révoquée par l\'administrateur.',
                    'reviewed_at' => now(),
                    'reviewed_by' => \Auth::id(),
                ]);
            }
        }
        
        $user->is_active = $request->boolean('is_active');
        $user->save();

        return back()->with('success', 'Utilisateur mis à jour avec succès');
    }

    // Supprimer un utilisateur
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.users')->with('success', 'Utilisateur supprimé avec succès');
    }

    // Liste des annonces
    public function ads(Request $request)
    {
        $query = Ad::with('user');
        
        // Filtres
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }
        
        $ads = $query->latest()->paginate(20);
        
        $stats = [
            'total' => Ad::count(),
            'active' => Ad::where('status', 'active')->count(),
            'pending' => Ad::where('status', 'pending')->count(),
            'rejected' => Ad::where('status', 'rejected')->count(),
        ];
        
        return view('admin.ads.index', compact('ads', 'stats'));
    }

    // Voir une annonce
    public function showAd($id)
    {
        $ad = Ad::with('user')->findOrFail($id);
        return view('admin.ads.show', compact('ad'));
    }

    // Mettre à jour une annonce
    public function updateAd(Request $request, $id)
    {
        $ad = Ad::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:active,pending,rejected,expired',
        ]);

        $ad->status = $validated['status'];
        $ad->save();

        return back()->with('success', 'Annonce mise à jour avec succès');
    }

    // Supprimer une annonce
    public function deleteAd($id)
    {
        $ad = Ad::findOrFail($id);
        $ad->delete();

        return redirect()->route('admin.ads')->with('success', 'Annonce supprimée avec succès');
    }

    // ===== GESTION BOOST / URGENT (ADMIN) =====

    /**
     * Liste des annonces boostées et urgentes
     */
    public function boosts(Request $request)
    {
        $query = Ad::with('user')->where('status', 'active');

        // Filtre par type de visibilité
        if ($request->filled('visibility_type')) {
            if ($request->visibility_type === 'boosted') {
                $query->where('is_boosted', true)->where('boost_end', '>', now());
            } elseif ($request->visibility_type === 'urgent') {
                $query->where('is_urgent', true)->where(function ($q) {
                    $q->whereNull('urgent_until')->orWhere('urgent_until', '>', now());
                });
            } elseif ($request->visibility_type === 'expired') {
                $query->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('is_boosted', true)->where('boost_end', '<=', now());
                    })->orWhere(function ($q2) {
                        $q2->where('is_urgent', true)->whereNotNull('urgent_until')->where('urgent_until', '<=', now());
                    });
                });
            }
        } else {
            $query->where(function ($q) {
                $q->where('is_boosted', true)->orWhere('is_urgent', true);
            });
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $ads = $query->latest()->paginate(20);

        $stats = [
            'active_boosts' => Ad::where('is_boosted', true)->where('boost_end', '>', now())->count(),
            'active_urgents' => Ad::where('is_urgent', true)->where(function ($q) {
                $q->whereNull('urgent_until')->orWhere('urgent_until', '>', now());
            })->count(),
            'expired_boosts' => Ad::where('is_boosted', true)->whereNotNull('boost_end')->where('boost_end', '<=', now())->count(),
            'expired_urgents' => Ad::where('is_urgent', true)->whereNotNull('urgent_until')->where('urgent_until', '<=', now())->count(),
        ];

        return view('admin.boosts.index', compact('ads', 'stats'));
    }

    /**
     * Activer/étendre un boost manuellement (admin)
     */
    public function grantBoost(Request $request, $id)
    {
        $ad = Ad::findOrFail($id);

        $validated = $request->validate([
            'boost_type' => 'required|in:boost_3,boost_7,boost_15,boost_30',
        ]);

        $durations = ['boost_3' => 3, 'boost_7' => 7, 'boost_15' => 15, 'boost_30' => 30];
        $days = $durations[$validated['boost_type']];

        $boostEnd = now()->addDays($days);
        if ($ad->isCurrentlyBoosted() && $ad->boost_end) {
            $boostEnd = $ad->boost_end->addDays($days);
        }

        $ad->update([
            'is_boosted' => true,
            'boost_end' => $boostEnd,
            'boost_type' => $validated['boost_type'],
        ]);

        return back()->with('success', "Boost activé pour « {$ad->title} » jusqu'au {$boostEnd->format('d/m/Y à H:i')}.");
    }

    /**
     * Activer/étendre le mode urgent manuellement (admin)
     */
    public function grantUrgent(Request $request, $id)
    {
        $ad = Ad::findOrFail($id);

        $validated = $request->validate([
            'duration_days' => 'required|integer|min:1|max:90',
        ]);

        $urgentUntil = now()->addDays($validated['duration_days']);
        if ($ad->isCurrentlyUrgent() && $ad->urgent_until) {
            $urgentUntil = $ad->urgent_until->addDays($validated['duration_days']);
        }

        $ad->update([
            'is_urgent' => true,
            'urgent_until' => $urgentUntil,
            'sidebar_priority' => 1,
        ]);

        return back()->with('success', "Mode Urgent activé pour « {$ad->title} » jusqu'au {$urgentUntil->format('d/m/Y à H:i')}.");
    }

    /**
     * Désactiver un boost (admin)
     */
    public function revokeBoost($id)
    {
        $ad = Ad::findOrFail($id);
        $ad->update([
            'is_boosted' => false,
            'boost_end' => null,
            'boost_type' => null,
        ]);

        return back()->with('success', "Boost désactivé pour « {$ad->title} ».");
    }

    /**
     * Désactiver le mode urgent (admin)
     */
    public function revokeUrgent($id)
    {
        $ad = Ad::findOrFail($id);
        $ad->update([
            'is_urgent' => false,
            'urgent_until' => null,
            'sidebar_priority' => 0,
        ]);

        return back()->with('success', "Mode Urgent désactivé pour « {$ad->title} ».");
    }

    // Logs des comptes supprimés
    public function deletedAccounts()
    {
        $deletedUsers = User::onlyTrashed()->latest('deleted_at')->paginate(20);

        // Récupérer les logs détaillés de suppression (email original, motif, etc.)
        $deletionLogs = DB::table('deleted_accounts')
            ->get()
            ->keyBy('user_id');

        return view('admin.deleted-accounts', compact('deletedUsers', 'deletionLogs'));
    }

    // Restaurer un compte supprimé
    public function restoreAccount($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();
        
        return back()->with('success', 'Compte de ' . $user->name . ' restauré avec succès');
    }

    // Supprimer définitivement un compte
    public function forceDeleteAccount($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $userName = $user->name;
        $user->forceDelete();
        
        return back()->with('success', 'Compte de ' . $userName . ' supprimé définitivement');
    }

    // ===== GESTION DES ABONNEMENTS =====

    // Liste des abonnements
    public function subscriptions(Request $request)
    {
        $query = User::query();
        
        // Filtrer par plan
        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }
        
        // Filtrer par statut abonnement
        if ($request->filled('subscription_status')) {
            if ($request->subscription_status === 'active') {
                $query->where('subscription_end', '>', now());
            } elseif ($request->subscription_status === 'expired') {
                $query->where('subscription_end', '<=', now())
                      ->whereNotNull('subscription_end');
            } elseif ($request->subscription_status === 'none') {
                $query->whereNull('subscription_end');
            }
        }
        
        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
        
        $users = $query->orderByDesc('subscription_end')->paginate(20);
        
        // Statistiques
        $stats = [
            'total_premium' => User::where('plan', '!=', 'FREE')->count(),
            'active_subscriptions' => User::where('subscription_end', '>', now())->count(),
            'expired_subscriptions' => User::where('subscription_end', '<=', now())
                                           ->whereNotNull('subscription_end')->count(),
            'starter_count' => User::where('plan', 'STARTER')->count(),
            'pro_count' => User::where('plan', 'PRO')->count(),
            'business_count' => User::where('plan', 'BUSINESS')->count(),
        ];
        
        $plans = config('admin.plans');
        
        return view('admin.subscriptions.index', compact('users', 'stats', 'plans'));
    }

    // Modifier l'abonnement d'un utilisateur
    public function updateSubscription(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validated = $request->validate([
            'plan' => 'required|in:FREE,STARTER,PRO,BUSINESS',
            'subscription_end' => 'nullable|date',
        ]);
        
        $user->plan = $validated['plan'];
        
        if ($validated['plan'] === 'FREE') {
            $user->subscription_end = null;
        } elseif ($request->filled('subscription_end')) {
            $user->subscription_end = $validated['subscription_end'];
        }
        
        $user->save();
        
        return back()->with('success', 'Abonnement de ' . $user->name . ' mis à jour avec succès');
    }

    // Accorder le premium gratuitement
    public function grantPremium(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validated = $request->validate([
            'plan' => 'required|in:STARTER,PRO,BUSINESS',
            'duration' => 'required|in:7,30,90,365,unlimited',
        ]);
        
        $user->plan = $validated['plan'];
        
        if ($validated['duration'] === 'unlimited') {
            $user->subscription_end = now()->addYears(100);
        } else {
            $user->subscription_end = now()->addDays((int)$validated['duration']);
        }
        
        $user->save();
        
        return back()->with('success', 'Premium ' . $validated['plan'] . ' accordé à ' . $user->name);
    }

    // Suspendre un abonnement
    public function suspendSubscription($id)
    {
        $user = User::findOrFail($id);
        $user->subscription_end = now()->subDay();
        $user->save();
        
        return back()->with('success', 'Abonnement de ' . $user->name . ' suspendu');
    }

    // Annuler un abonnement (retour à FREE)
    public function cancelSubscription($id)
    {
        $user = User::findOrFail($id);
        $user->plan = 'FREE';
        $user->subscription_end = null;
        $user->save();
        
        return back()->with('success', 'Abonnement de ' . $user->name . ' annulé');
    }

    // ===== STATISTIQUES =====

    public function stats()
    {
        // Statistiques générales
        $generalStats = [
            'total_users' => User::count(),
            'total_ads' => Ad::count(),
            'active_ads' => Ad::where('status', 'active')->count(),
            'verified_users' => User::where('is_verified', true)->count(),
            'premium_users' => User::where('plan', '!=', 'FREE')->count(),
        ];
        
        // Inscriptions par jour (30 derniers jours)
        $registrationsByDay = User::select(
                DB::raw('created_at::date as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Annonces par jour (30 derniers jours)
        $adsByDay = Ad::select(
                DB::raw('created_at::date as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Répartition par catégorie
        $adsByCategory = Ad::select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
        
        // Répartition des plans
        $usersByPlan = User::select('plan', DB::raw('COUNT(*) as count'))
            ->groupBy('plan')
            ->get();
        
        // Répartition par type d'utilisateur
        $usersByType = User::select('user_type', DB::raw('COUNT(*) as count'))
            ->groupBy('user_type')
            ->get();
        
        // Top utilisateurs par annonces
        $topUsers = User::withCount('ads')
            ->orderByDesc('ads_count')
            ->limit(10)
            ->get();
        
        // Recensement complet des utilisateurs avec niveau de configuration
        $allUsers = User::withCount('ads')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($user) {
                // Calcul du niveau de complétion du profil
                $fields = [
                    'name' => !empty($user->name),
                    'email' => !empty($user->email),
                    'phone' => !empty($user->phone),
                    'avatar' => !empty($user->avatar),
                    'city' => !empty($user->city),
                    'country' => !empty($user->country),
                    'address' => !empty($user->address),
                    'bio' => !empty($user->bio),
                ];
                
                if ($user->isProfessionnel()) {
                    $fields['profession'] = !empty($user->profession);
                    $fields['business_type'] = !empty($user->business_type);
                    $fields['company_name'] = !empty($user->company_name);
                }
                
                $filled = count(array_filter($fields));
                $total = count($fields);
                $user->profile_completion = $total > 0 ? round(($filled / $total) * 100) : 0;
                $user->profile_filled = $filled;
                $user->profile_total = $total;
                
                return $user;
            });
        
        return view('admin.stats', compact(
            'generalStats',
            'registrationsByDay',
            'adsByDay',
            'adsByCategory',
            'usersByPlan',
            'usersByType',
            'topUsers',
            'allUsers'
        ));
    }

    // ===== PARAMÈTRES =====

    public function settings()
    {
        $settings = [
            'general' => Setting::getGroup('general'),
            'ads' => Setting::getGroup('ads'),
            'points' => Setting::getGroup('points'),
            'email' => Setting::getGroup('email'),
            'security' => Setting::getGroup('security'),
        ];

        $mailSummary = $this->buildMailConfigSummary();
        
        return view('admin.settings', compact('settings', 'mailSummary'));
    }

    public function updateSettingsGeneral(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:100',
            'contact_email' => 'required|email|max:255',
            'maintenance_mode' => 'nullable',
        ]);

        Setting::set('site_name', $validated['site_name'], 'general');
        Setting::set('contact_email', $validated['contact_email'], 'general');
        Setting::set('maintenance_mode', $request->has('maintenance_mode') ? '1' : '0', 'general');

        return back()->with('success', 'Paramètres généraux mis à jour avec succès');
    }

    public function updateSettingsAds(Request $request)
    {
        $validated = $request->validate([
            'free_ads_limit' => 'required|integer|min:0',
            'ad_validity_days' => 'required|integer|min:1',
            'auto_moderation' => 'nullable',
        ]);

        Setting::set('free_ads_limit', $validated['free_ads_limit'], 'ads');
        Setting::set('ad_validity_days', $validated['ad_validity_days'], 'ads');
        Setting::set('auto_moderation', $request->has('auto_moderation') ? '1' : '0', 'ads');

        return back()->with('success', 'Paramètres des annonces mis à jour avec succès');
    }

    public function updateSettingsPoints(Request $request)
    {
        $validated = $request->validate([
            'signup_points' => 'required|integer|min:0',
            'daily_login_points' => 'required|integer|min:0',
            'share_points' => 'required|integer|min:0',
            'message_cost' => 'required|integer|min:0',
        ]);

        Setting::set('signup_points', $validated['signup_points'], 'points');
        Setting::set('daily_login_points', $validated['daily_login_points'], 'points');
        Setting::set('share_points', $validated['share_points'], 'points');
        Setting::set('message_cost', $validated['message_cost'], 'points');

        return back()->with('success', 'Paramètres des points mis à jour avec succès');
    }

    public function updateSettingsEmail(Request $request)
    {
        $validated = $request->validate([
            'mail_driver' => 'required|in:failover,brevo,brevo_secondary,smtp,mailgun,ses,log',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name' => 'required|string|max:255',
            'mail_reply_to_address' => 'nullable|email|max:255',
            'mail_reply_to_name' => 'nullable|string|max:255',
            'mail_admin_address' => 'required|email|max:255',
        ]);

        $replyToAddress = trim((string) ($validated['mail_reply_to_address'] ?? ''));
        if ($replyToAddress === '') {
            $replyToAddress = $validated['mail_admin_address'];
        }

        $replyToName = trim((string) ($validated['mail_reply_to_name'] ?? ''));
        if ($replyToName === '') {
            $replyToName = $validated['mail_from_name'];
        }

        Setting::set('mail_driver', $validated['mail_driver'], 'email');
        Setting::set('mail_from_address', $validated['mail_from_address'], 'email');
        Setting::set('mail_from_name', $validated['mail_from_name'], 'email');
        Setting::set('mail_reply_to_address', $replyToAddress, 'email');
        Setting::set('mail_reply_to_name', $replyToName, 'email');
        Setting::set('mail_admin_address', $validated['mail_admin_address'], 'email');
        Setting::set('email_new_user', $request->has('email_new_user') ? '1' : '0', 'email');
        Setting::set('email_new_ad', $request->has('email_new_ad') ? '1' : '0', 'email');
        Setting::set('email_new_message', $request->has('email_new_message') ? '1' : '0', 'email');

        return back()->with('success', 'Paramètres email mis à jour avec succès');
    }

    public function sendTestEmail(Request $request)
    {
        $validated = $request->validate([
            'test_email' => 'nullable|email|max:255',
        ]);

        $targetEmail = $validated['test_email']
            ?? Setting::get('mail_admin_address', config('mail.admin_email'))
            ?? config('mail.from.address');

        try {
            Mail::to($targetEmail)->send(new AdminTestMail([
                'mailer' => config('mail.default'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'reply_to_address' => config('mail.reply_to.address'),
                'reply_to_name' => config('mail.reply_to.name'),
                'admin_email' => config('mail.admin_email'),
                'environment' => app()->environment(),
                'sent_at' => now()->format('d/m/Y H:i:s'),
            ]));

            return back()->with('success', "Email de test envoyé à {$targetEmail}");
        } catch (\Throwable $e) {
            return back()->with('error', 'Échec de l\'envoi du mail de test : ' . $e->getMessage());
        }
    }

    public function updateSettingsSecurity(Request $request)
    {
        Setting::set('email_verification_enabled', $request->has('email_verification_enabled') ? '1' : '0', 'security');

        return back()->with('success', 'Paramètres de sécurité mis à jour avec succès');
    }

    public function updateSettingsSystem(Request $request)
    {
        $action = $request->input('action');

        if ($action === 'clear_cache') {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            return back()->with('success', 'Cache vidé avec succès');
        }

        if ($action === 'optimize') {
            Artisan::call('optimize');
            return back()->with('success', 'Application optimisée avec succès');
        }

        return back()->with('error', 'Action non reconnue');
    }

    protected function buildMailConfigSummary(): array
    {
        $contactEmail = Setting::get('contact_email', config('mail.admin_email'));
        $driver = Setting::get('mail_driver', config('mail.default'));
        $fromAddress = Setting::get('mail_from_address', config('mail.from.address'));
        $fromName = Setting::get('mail_from_name', config('mail.from.name'));
        $adminEmail = Setting::get('mail_admin_address', $contactEmail ?: config('mail.admin_email'));
        $storedReplyToAddress = Setting::get('mail_reply_to_address', config('mail.reply_to.address'));
        $storedReplyToName = Setting::get('mail_reply_to_name', config('mail.reply_to.name'));
        $replyToAddress = $storedReplyToAddress ?: $adminEmail;
        $replyToName = $storedReplyToName ?: $fromName;

        $missingFields = [];
        foreach ([
            'contact_email' => $contactEmail,
            'mail_from_address' => $fromAddress,
            'mail_from_name' => $fromName,
            'mail_admin_address' => $adminEmail,
            'mail_reply_to_address' => $replyToAddress,
        ] as $field => $value) {
            if (! is_string($value) || trim($value) === '') {
                $missingFields[] = $field;
            }
        }

        return [
            'driver' => $driver,
            'contact_email' => $contactEmail,
            'from_address' => $fromAddress,
            'from_name' => $fromName,
            'reply_to_address' => $replyToAddress,
            'reply_to_name' => $replyToName,
            'admin_email' => $adminEmail,
            'reply_to_uses_admin_fallback' => $storedReplyToAddress !== $replyToAddress,
            'is_complete' => count($missingFields) === 0,
            'missing_fields' => $missingFields,
        ];
    }

    // ===== GESTION DES ADMINS =====

    // Liste des administrateurs (réservé à l'admin principal)
    public function admins()
    {
        // Vérifier si c'est l'admin principal
        if (!$this->isPrincipalAdmin()) {
            abort(403, 'Accès réservé à l\'administrateur principal');
        }
        
        $admins = User::where('role', 'admin')->get();
        $privileges = config('admin.privileges');
        
        return view('admin.admins.index', compact('admins', 'privileges'));
    }

    // Promouvoir un utilisateur en admin
    public function promoteToAdmin(Request $request, $id)
    {
        if (!$this->isPrincipalAdmin()) {
            abort(403, 'Accès réservé à l\'administrateur principal');
        }
        
        $user = User::findOrFail($id);
        
        $validated = $request->validate([
            'privileges' => 'nullable|array',
            'privileges.*' => 'in:' . implode(',', array_keys(config('admin.privileges'))),
        ]);
        
        $user->role = 'admin';
        $user->admin_privileges = json_encode($validated['privileges'] ?? []);
        $user->save();
        
        return back()->with('success', $user->name . ' a été promu administrateur');
    }

    // Révoquer les droits admin
    public function revokeAdmin($id)
    {
        if (!$this->isPrincipalAdmin()) {
            abort(403, 'Accès réservé à l\'administrateur principal');
        }
        
        $user = User::findOrFail($id);
        
        // Vérifier que ce n'est pas l'admin principal
        if ($user->email === config('admin.principal_admin.email')) {
            return back()->with('error', 'Impossible de révoquer l\'administrateur principal');
        }
        
        $user->role = 'user';
        $user->admin_privileges = null;
        $user->save();
        
        return back()->with('success', 'Droits administrateur de ' . $user->name . ' révoqués');
    }

    // Mettre à jour les privilèges d'un admin
    public function updateAdminPrivileges(Request $request, $id)
    {
        if (!$this->isPrincipalAdmin()) {
            abort(403, 'Accès réservé à l\'administrateur principal');
        }
        
        $user = User::findOrFail($id);
        
        // Vérifier que ce n'est pas l'admin principal
        if ($user->email === config('admin.principal_admin.email')) {
            return back()->with('error', 'Impossible de modifier les privilèges de l\'administrateur principal');
        }
        
        $validated = $request->validate([
            'privileges' => 'nullable|array',
            'privileges.*' => 'in:' . implode(',', array_keys(config('admin.privileges'))),
        ]);
        
        $user->admin_privileges = json_encode($validated['privileges'] ?? []);
        $user->save();
        
        return back()->with('success', 'Privilèges de ' . $user->name . ' mis à jour');
    }

    // ===== GESTION DES PUBLICITES =====

    /**
     * Liste des publicités
     */
    public function advertisements()
    {
        $advertisements = Advertisement::orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('admin.advertisements.index', compact('advertisements'));
    }

    /**
     * Formulaire de création d'une publicité
     */
    public function createAdvertisement()
    {
        return view('admin.advertisements.create');
    }

    /**
     * Enregistrer une nouvelle publicité
     */
    public function storeAdvertisement(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'link' => 'nullable|url|max:500',
            'position' => 'required|in:sidebar,banner,popup',
            'priority' => 'required|integer|min:0|max:100',
            'is_active' => 'nullable',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $advertisement = new Advertisement();
        $advertisement->title = $validated['title'];
        $advertisement->description = $validated['description'] ?? null;
        $advertisement->link = $validated['link'] ?? null;
        $advertisement->position = $validated['position'];
        $advertisement->priority = $validated['priority'];
        $advertisement->is_active = $request->has('is_active');
        $advertisement->starts_at = $validated['starts_at'] ?? null;
        $advertisement->ends_at = $validated['ends_at'] ?? null;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('advertisements', config('filesystems.default', 'public'));
            $advertisement->image = $path;
        }

        $advertisement->save();

        return redirect()->route('admin.advertisements')->with('success', 'Publicité créée avec succès');
    }

    /**
     * Formulaire d'édition d'une publicité
     */
    public function editAdvertisement($id)
    {
        $advertisement = Advertisement::findOrFail($id);
        return view('admin.advertisements.edit', compact('advertisement'));
    }

    /**
     * Mettre à jour une publicité
     */
    public function updateAdvertisement(Request $request, $id)
    {
        $advertisement = Advertisement::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'link' => 'nullable|url|max:500',
            'position' => 'required|in:sidebar,banner,popup',
            'priority' => 'required|integer|min:0|max:100',
            'is_active' => 'nullable',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $advertisement->title = $validated['title'];
        $advertisement->description = $validated['description'] ?? null;
        $advertisement->link = $validated['link'] ?? null;
        $advertisement->position = $validated['position'];
        $advertisement->priority = $validated['priority'];
        $advertisement->is_active = $request->has('is_active');
        $advertisement->starts_at = $validated['starts_at'] ?? null;
        $advertisement->ends_at = $validated['ends_at'] ?? null;

        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image
            if ($advertisement->image) {
                \Storage::disk(config('filesystems.default', 'public'))->delete($advertisement->image);
            }
            $path = $request->file('image')->store('advertisements', config('filesystems.default', 'public'));
            $advertisement->image = $path;
        }

        $advertisement->save();

        return redirect()->route('admin.advertisements')->with('success', 'Publicité mise à jour avec succès');
    }

    /**
     * Supprimer une publicité
     */
    public function deleteAdvertisement($id)
    {
        $advertisement = Advertisement::findOrFail($id);
        
        if ($advertisement->image) {
            \Storage::disk(config('filesystems.default', 'public'))->delete($advertisement->image);
        }
        
        $advertisement->delete();

        return back()->with('success', 'Publicité supprimée avec succès');
    }

    /**
     * Activer/Désactiver une publicité
     */
    public function toggleAdvertisement($id)
    {
        $advertisement = Advertisement::findOrFail($id);
        $advertisement->is_active = !$advertisement->is_active;
        $advertisement->save();

        $status = $advertisement->is_active ? 'activée' : 'désactivée';
        return back()->with('success', 'Publicité ' . $status . ' avec succès');
    }

    // ===== GESTION DES VÉRIFICATIONS =====

    /**
     * Liste des vérifications de profil
     */
    public function verifications(Request $request)
    {
        $query = IdentityVerification::with('user', 'reviewer');

        // Filtrer par statut
        if ($request->filled('status')) {
            if ($request->status === 'resubmitted') {
                $query->where('status', 'pending')->where('resubmission_count', '>', 0);
            } else {
                $query->where('status', $request->status);
            }
        } else {
            // Par défaut, montrer toutes les demandes non traitées (pending + returned)
            $query->whereIn('status', ['pending', 'returned']);
        }

        // Filtrer par statut de paiement
        if ($request->filled('payment')) {
            $query->where('payment_status', $request->payment);
        }

        // Filtrer par type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Recherche par nom/email utilisateur
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $verifications = $query->latest('submitted_at')->paginate(15);

        $stats = [
            'pending' => IdentityVerification::where('status', 'pending')->count(),
            'pending_paid' => IdentityVerification::where('status', 'pending')->where('payment_status', 'paid')->count(),
            'pending_unpaid' => IdentityVerification::where('status', 'pending')->where('payment_status', '!=', 'paid')->count(),
            'returned' => IdentityVerification::where('status', 'returned')->count(),
            'resubmitted' => IdentityVerification::where('status', 'pending')->where('resubmission_count', '>', 0)->count(),
            'approved' => IdentityVerification::where('status', 'approved')->count(),
            'rejected' => IdentityVerification::where('status', 'rejected')->count(),
            'total' => IdentityVerification::count(),
            'to_process' => IdentityVerification::whereIn('status', ['pending', 'returned'])->count(),
        ];

        return view('admin.verifications.index', compact('verifications', 'stats'));
    }

    /**
     * Détail d'une vérification
     */
    public function showVerification($id)
    {
        $verification = IdentityVerification::with('user', 'reviewer')->findOrFail($id);
        return view('admin.verifications.show', compact('verification'));
    }

    /**
     * Approuver une vérification (tous les documents validés)
     */
    public function approveVerification($id)
    {
        $verification = IdentityVerification::findOrFail($id);

        if (!$verification->isPending() && !$verification->isReturned()) {
            return back()->with('error', 'Cette vérification a déjà été traitée.');
        }

        DB::beginTransaction();
        try {
            // Si les documents sont dans le dossier temporaire, les déplacer
            if (str_contains($verification->document_front ?? '', 'verifications-temp/')) {
                $this->moveVerificationDocsToPerma($verification);
            }

            // Marquer tous les documents comme approuvés
            $verification->update([
                'status' => 'approved',
                'document_front_status' => 'approved',
                'document_back_status' => $verification->document_back ? 'approved' : $verification->document_back_status,
                'selfie_status' => 'approved',
                'professional_document_status' => $verification->professional_document ? 'approved' : $verification->professional_document_status,
                'payment_status' => 'paid',
                'reviewed_at' => now(),
                'reviewed_by' => \Auth::id(),
            ]);

            // Mettre à jour le statut de l'utilisateur
            $user = $verification->user;
            $user->update([
                'is_verified' => true,
                'identity_verified' => true,
                'identity_verified_at' => now(),
                'pro_verified' => true,
                'pro_verified_at' => now(),
            ]);

            // Ajouter des points bonus
            if (method_exists($user, 'addPoints')) {
                $user->addPoints(50, 'bonus', 'Bonus vérification de profil approuvée');
            }

            // Envoyer notification
            $user->notify(new \App\Notifications\VerificationApproved($verification));

            DB::commit();
            return back()->with('success', 'Vérification approuvée avec succès. L\'utilisateur ' . $user->name . ' est maintenant vérifié.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur approbation vérification: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'approbation: ' . $e->getMessage());
        }
    }

    /**
     * Rejeter une vérification (rejet global)
     */
    public function rejectVerification(Request $request, $id)
    {
        $verification = IdentityVerification::findOrFail($id);

        if (!$verification->isPending() && !$verification->isReturned()) {
            return back()->with('error', 'Cette vérification a déjà été traitée.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $verification->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'document_front_status' => 'rejected',
            'document_back_status' => $verification->document_back ? 'rejected' : $verification->document_back_status,
            'selfie_status' => 'rejected',
            'professional_document_status' => $verification->professional_document ? 'rejected' : $verification->professional_document_status,
            'reviewed_at' => now(),
            'reviewed_by' => \Auth::id(),
        ]);

        // Réinitialiser les flags de vérification de l'utilisateur
        $user = $verification->user;
        $user->update([
            'is_verified' => false,
            'identity_verified' => false,
            'identity_verified_at' => null,
            'pro_verified' => false,
            'pro_verified_at' => null,
        ]);

        // Notifier l'utilisateur
        $rejectedDocs = $verification->getRejectedDocuments();
        $verification->user->notify(new \App\Notifications\VerificationDocumentRejected(
            $verification, $rejectedDocs, $request->rejection_reason
        ));

        return back()->with('success', 'Vérification rejetée. L\'utilisateur a été notifié du motif.');
    }

    /**
     * Revue par document : approuver/rejeter chaque document individuellement
     */
    public function reviewDocuments(Request $request, $id)
    {
        $verification = IdentityVerification::findOrFail($id);

        if (!$verification->isPending() && !$verification->isReturned()) {
            return back()->with('error', 'Cette vérification a déjà été traitée.');
        }

        $request->validate([
            'document_front_status' => 'required|in:approved,rejected,pending',
            'document_front_rejection_reason' => 'nullable|string|max:500',
            'document_back_status' => 'nullable|in:approved,rejected,pending',
            'document_back_rejection_reason' => 'nullable|string|max:500',
            'selfie_status' => 'required|in:approved,rejected,pending',
            'selfie_rejection_reason' => 'nullable|string|max:500',
            'professional_document_status' => 'nullable|in:approved,rejected,pending',
            'professional_document_rejection_reason' => 'nullable|string|max:500',
            'admin_message' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $updateData = [
                'document_front_status' => $request->document_front_status,
                'document_front_rejection_reason' => $request->document_front_status === 'rejected' ? $request->document_front_rejection_reason : null,
                'selfie_status' => $request->selfie_status,
                'selfie_rejection_reason' => $request->selfie_status === 'rejected' ? $request->selfie_rejection_reason : null,
                'admin_message' => $request->admin_message,
                'reviewed_at' => now(),
                'reviewed_by' => \Auth::id(),
            ];

            if ($verification->document_back) {
                $updateData['document_back_status'] = $request->document_back_status ?? 'pending';
                $updateData['document_back_rejection_reason'] = ($request->document_back_status ?? '') === 'rejected' ? $request->document_back_rejection_reason : null;
            }

            if ($verification->professional_document) {
                $updateData['professional_document_status'] = $request->professional_document_status ?? 'pending';
                $updateData['professional_document_rejection_reason'] = ($request->professional_document_status ?? '') === 'rejected' ? $request->professional_document_rejection_reason : null;
            }

            $verification->update($updateData);
            $verification->refresh();

            // Check if all approved → auto-approve the verification
            if ($verification->allDocumentsApproved()) {
                if (str_contains($verification->document_front ?? '', 'verifications-temp/')) {
                    $this->moveVerificationDocsToPerma($verification);
                }

                $verification->update([
                    'status' => 'approved',
                    'payment_status' => 'paid',
                ]);

                $user = $verification->user;
                $user->update([
                    'is_verified' => true,
                    'identity_verified' => true,
                    'identity_verified_at' => now(),
                    'pro_verified' => true,
                    'pro_verified_at' => now(),
                ]);

                if (method_exists($user, 'addPoints')) {
                    $user->addPoints(50, 'bonus', 'Bonus vérification de profil approuvée');
                }

                $user->notify(new \App\Notifications\VerificationApproved($verification));

                DB::commit();
                return back()->with('success', 'Tous les documents sont validés. Le profil de ' . $user->name . ' est maintenant vérifié !');
            }

            // If any document rejected → status returned, notify user
            if ($verification->hasRejectedDocuments()) {
                $verification->update(['status' => 'returned']);
                $rejectedDocs = $verification->getRejectedDocuments();
                $verification->user->notify(new \App\Notifications\VerificationDocumentRejected(
                    $verification, $rejectedDocs, $request->admin_message
                ));

                DB::commit();
                return back()->with('success', 'Documents évalués. Le formulaire a été renvoyé à l\'utilisateur avec les documents rejetés.');
            }

            DB::commit();
            return back()->with('success', 'Statuts des documents mis à jour.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur revue documents: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la revue: ' . $e->getMessage());
        }
    }

    /**
     * Renvoyer le formulaire à l'utilisateur pour corrections
     */
    public function returnVerification(Request $request, $id)
    {
        $verification = IdentityVerification::findOrFail($id);

        if (!$verification->isPending() && !$verification->isReturned()) {
            return back()->with('error', 'Cette vérification a déjà été traitée.');
        }

        $request->validate([
            'admin_message' => 'required|string|max:1000',
            'document_front_status' => 'nullable|in:approved,rejected,pending',
            'document_back_status' => 'nullable|in:approved,rejected,pending',
            'selfie_status' => 'nullable|in:approved,rejected,pending',
            'professional_document_status' => 'nullable|in:approved,rejected,pending',
        ]);

        $updateData = [
            'status' => 'returned',
            'admin_message' => $request->admin_message,
            'reviewed_at' => now(),
            'reviewed_by' => \Auth::id(),
        ];

        // Document Front : utiliser le statut envoyé depuis la revue, sinon marquer comme rejeté
        $frontStatus = $request->input('document_front_status', $verification->document_front_status);
        if ($frontStatus === 'approved') {
            $updateData['document_front_status'] = 'approved';
            $updateData['document_front_rejection_reason'] = null;
        } else {
            $updateData['document_front_status'] = 'rejected';
            $updateData['document_front_rejection_reason'] = $request->input('document_front_rejection_reason') ?: $request->admin_message;
        }

        // Document Back
        if ($verification->document_back) {
            $backStatus = $request->input('document_back_status', $verification->document_back_status);
            if ($backStatus === 'approved') {
                $updateData['document_back_status'] = 'approved';
                $updateData['document_back_rejection_reason'] = null;
            } else {
                $updateData['document_back_status'] = 'rejected';
                $updateData['document_back_rejection_reason'] = $request->input('document_back_rejection_reason') ?: $request->admin_message;
            }
        }

        // Selfie
        $selfieStatus = $request->input('selfie_status', $verification->selfie_status);
        if ($selfieStatus === 'approved') {
            $updateData['selfie_status'] = 'approved';
            $updateData['selfie_rejection_reason'] = null;
        } else {
            $updateData['selfie_status'] = 'rejected';
            $updateData['selfie_rejection_reason'] = $request->input('selfie_rejection_reason') ?: $request->admin_message;
        }

        // Document professionnel
        if ($verification->professional_document) {
            $proStatus = $request->input('professional_document_status', $verification->professional_document_status);
            if ($proStatus === 'approved') {
                $updateData['professional_document_status'] = 'approved';
                $updateData['professional_document_rejection_reason'] = null;
            } else {
                $updateData['professional_document_status'] = 'rejected';
                $updateData['professional_document_rejection_reason'] = $request->input('professional_document_rejection_reason') ?: $request->admin_message;
            }
        }

        $verification->update($updateData);

        // Notifier l'utilisateur
        $verification->refresh();
        $verification->user->notify(new \App\Notifications\VerificationDocumentRejected(
            $verification,
            $verification->getRejectedDocuments(),
            $request->admin_message
        ));

        return back()->with('success', 'Le formulaire a été renvoyé à l\'utilisateur pour corrections.');
    }

    // ===== HELPERS =====

    /**
     * Déplacer les documents de vérification du dossier temporaire vers le permanent
     */
    protected function moveVerificationDocsToPerma(IdentityVerification $verification)
    {
        $fields = ['document_front', 'document_back', 'selfie'];
        foreach ($fields as $field) {
            if (!empty($verification->$field) && \Storage::disk(config('filesystems.default', 'public'))->exists($verification->$field)) {
                $newPath = str_replace('verifications-temp/', 'verifications/', $verification->$field);
                // Créer le dossier de destination si nécessaire
                $dir = dirname($newPath);
                if (!\Storage::disk(config('filesystems.default', 'public'))->exists($dir)) {
                    \Storage::disk(config('filesystems.default', 'public'))->makeDirectory($dir);
                }
                \Storage::disk(config('filesystems.default', 'public'))->move($verification->$field, $newPath);
                $verification->$field = $newPath;
            }
        }
        $verification->save();
    }

    /**
     * Vérifie si l'utilisateur actuel est l'admin principal
     */
    protected function isPrincipalAdmin()
    {
        return \Auth::user()->email === config('admin.principal_admin.email');
    }

    /**
     * Vérifie si l'utilisateur visualisé est l'admin principal
     */
    public static function isUserPrincipalAdmin($user)
    {
        return $user->email === config('admin.principal_admin.email');
    }

    // =========================================
    // GESTION DES SIGNALEMENTS
    // =========================================

    public function reports(Request $request)
    {
        $query = Report::with(['reporter', 'ad.user'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('reporter', fn($r) => $r->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('ad', fn($r) => $r->where('title', 'like', "%{$search}%"))
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $reports = $query->paginate(20);

        $stats = [
            'total' => Report::count(),
            'pending' => Report::where('status', 'pending')->count(),
            'resolved' => Report::where('status', 'resolved')->count(),
            'dismissed' => Report::where('status', 'dismissed')->count(),
        ];

        return view('admin.reports.index', compact('reports', 'stats'));
    }

    public function showReport($id)
    {
        $report = Report::with(['reporter', 'ad.user'])->findOrFail($id);
        return view('admin.reports.show', compact('report'));
    }

    public function resolveReport(Request $request, $id)
    {
        $report = Report::findOrFail($id);
        $report->update([
            'status' => 'resolved',
        ]);

        // Si l'admin veut aussi supprimer l'annonce
        if ($request->has('delete_ad') && $report->ad) {
            $report->ad->delete();
        }

        return back()->with('success', 'Signalement traité et marqué comme résolu.');
    }

    public function dismissReport($id)
    {
        $report = Report::findOrFail($id);
        $report->update(['status' => 'dismissed']);
        return back()->with('success', 'Signalement rejeté.');
    }

    public function deleteReport($id)
    {
        $report = Report::findOrFail($id);
        $report->delete();
        return back()->with('success', 'Signalement supprimé.');
    }

    // ===== CONTACT MESSAGES MANAGEMENT =====

    public function contactMessages(Request $request)
    {
        $query = ContactMessage::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $messages = $query->paginate(15);

        $stats = [
            'total' => ContactMessage::count(),
            'pending' => ContactMessage::where('status', 'pending')->count(),
            'read' => ContactMessage::where('status', 'read')->count(),
            'replied' => ContactMessage::where('status', 'replied')->count(),
            'closed' => ContactMessage::where('status', 'closed')->count(),
        ];

        return view('admin.contact-messages.index', compact('messages', 'stats'));
    }

    public function showContactMessage($id)
    {
        $contactMessage = ContactMessage::with('user')->findOrFail($id);

        if ($contactMessage->status === 'pending') {
            $contactMessage->update(['status' => 'read']);
        }

        return view('admin.contact-messages.show', compact('contactMessage'));
    }

    public function replyContactMessage(Request $request, $id)
    {
        $request->validate([
            'admin_reply' => 'required|string|max:10000',
        ]);

        $message = ContactMessage::findOrFail($id);
        $message->update([
            'admin_reply' => $request->admin_reply,
            'status' => 'replied',
            'replied_at' => now(),
        ]);

        return back()->with('success', 'Réponse envoyée avec succès.');
    }

    public function updateContactMessageStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,read,replied,closed',
        ]);

        $message = ContactMessage::findOrFail($id);
        $message->update(['status' => $request->status]);

        return back()->with('success', 'Statut mis à jour.');
    }

    public function deleteContactMessage($id)
    {
        $message = ContactMessage::findOrFail($id);
        $message->delete();

        return redirect()->route('admin.contact-messages')->with('success', 'Message supprimé.');
    }
}

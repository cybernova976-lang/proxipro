<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Billable, SoftDeletes;

    /**
     * Boot the model - Suppression en cascade des données liées
     */
    protected static function booted(): void
    {
        // Lors d'un soft-delete : supprimer les annonces de l'utilisateur
        static::deleting(function (User $user) {
            if (!$user->isForceDeleting()) {
                $user->ads()->delete();
            }
        });

        // Lors d'une suppression définitive (forceDelete)
        static::forceDeleting(function (User $user) {
            // Supprimer les annonces de l'utilisateur
            $user->ads()->delete();
            
            // Supprimer les transactions de points
            $user->pointTransactions()->delete();
            
            // Supprimer les services
            $user->services()->delete();
            
            // Supprimer les avis reçus et donnés
            $user->reviewsReceived()->delete();
            $user->reviewsGiven()->delete();
            
            // Détacher les badges
            $user->badges()->detach();
            
            // Détacher les annonces sauvegardées
            $user->savedAds()->detach();

            // Supprimer les alertes sauvegardées
            $user->savedSearches()->delete();
            
            // Supprimer les messages envoyés
            $user->sentMessages()->delete();
            
            // Supprimer les conversations où l'utilisateur participe
            Conversation::where('user1_id', $user->id)
                ->orWhere('user2_id', $user->id)
                ->delete();
        });
    }

    /**
     * The attributes that are mass assignable.
     * SECURITY: Sensitive fields (role, is_verified, admin_privileges, etc.)
     * are intentionally excluded - they must be set explicitly.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'stripe_id',
        'stripe_connect_account_id',
        'stripe_connect_onboarding_completed_at',
        'stripe_connect_payouts_enabled',
        'stripe_connect_charges_enabled',
        'referral_code',
        'referred_by_user_id',
        'referral_bonus_granted_at',
        'first_qualifying_purchase_at',
        'password',
        'phone',
        'address',
        'postal_code',
        'avatar',
        'bio',
        'location_preference',
        'preferred_categories',
        'user_type',
        'is_service_provider',
        'service_provider_since',
        'provider',
        'provider_id',
        // Champs profils professionnels
        'account_type',
        'business_type',
        'company_name',
        'siret',
        'business_sector',
        'service_category',
        'service_subcategories',
        'kbis_document',
        'id_document',
        'newsletter_subscribed',
        // Champs profil complet
        'profession',
        'country',
        'city',
        'profile_completed',
        'profile_completed_at',
        // Géolocalisation automatique
        'latitude',
        'longitude',
        'detected_city',
        'detected_country',
        'geo_radius',
        'geo_source',
        'geo_detected_at',
        // Champs pro dashboard
        'pro_onboarding_completed',
        'pro_onboarding_step',
        'pro_onboarding_skipped',
        'pro_subscription_plan',
        'pro_notifications_realtime',
        'pro_notifications_email',
        'pro_notifications_sms',
        'pro_phone_sms',
        'pro_service_categories',
        'pro_intervention_radius',
        'pro_status',
        'website_url',
        'social_links',
        'insurance_number',
        'hourly_rate',
        'show_hourly_rate',
        'specialties',
        'years_experience',
        // Champs outil devis/facture
        'free_quotes_used',
        'paid_quotes_remaining',
        // Verification flags
        'is_verified',
        'identity_verified',
        'identity_verified_at',
        'pro_verified',
        'pro_verified_at',
        // Email verification code
        'email_verification_code',
        'email_verification_code_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'stripe_connect_onboarding_completed_at' => 'datetime',
            'email_verification_code_expires_at' => 'datetime',
            'referral_bonus_granted_at' => 'datetime',
            'first_qualifying_purchase_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'identity_verified' => 'boolean',
            'identity_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'preferred_categories' => 'array',
            'admin_privileges' => 'array',
            'is_service_provider' => 'boolean',
            'service_provider_since' => 'datetime',
            'service_provider_verified' => 'boolean',
            'pro_verified' => 'boolean',
            'service_subcategories' => 'array',
            'pro_verified_at' => 'datetime',
            'can_boost_ads' => 'boolean',
            'newsletter_subscribed' => 'boolean',
            'stripe_connect_payouts_enabled' => 'boolean',
            'stripe_connect_charges_enabled' => 'boolean',
            'profile_completed' => 'boolean',
            'profile_completed_at' => 'datetime',
            'pro_onboarding_completed' => 'boolean',
            'pro_onboarding_skipped' => 'boolean',
            'pro_onboarding_step' => 'integer',
            'pro_notifications_realtime' => 'boolean',
            'pro_notifications_email' => 'boolean',
            'pro_notifications_sms' => 'boolean',
            'pro_service_categories' => 'array',
            'social_links' => 'array',
            'specialties' => 'array',
            'hourly_rate' => 'decimal:2',
            'show_hourly_rate' => 'boolean',
            'years_experience' => 'integer',
            'pro_intervention_radius' => 'integer',
            'free_quotes_used' => 'integer',
            'paid_quotes_remaining' => 'integer',
        ];
    }

    /**
     * Vérifie si l'utilisateur est inscrit via OAuth (Google, Facebook, etc.)
     */
    public function isOAuthUser(): bool
    {
        return !empty($this->provider);
    }

    /**
     * Vérifie si l'utilisateur OAuth doit compléter son profil
     */
    public function needsProfileCompletion(): bool
    {
        return $this->isOAuthUser() && !$this->profile_completed && !$this->is_service_provider;
    }

    /**
     * Relation avec les services/compétences du prestataire
     */
    public function services()
    {
        return $this->hasMany(UserService::class);
    }

    /**
     * Services actifs du prestataire
     */
    public function activeServices()
    {
        return $this->hasMany(UserService::class)->where('is_active', true);
    }

    /**
     * Vérifie si l'utilisateur est un prestataire (particulier ou pro)
     */
    public function isServiceProvider(): bool
    {
        return $this->is_service_provider || $this->user_type === 'professionnel';
    }

    /**
     * Vérifie si l'utilisateur est un particulier prestataire
     */
    public function isParticulierPrestataire(): bool
    {
        return $this->user_type === 'particulier' && $this->is_service_provider;
    }

    /**
     * Vérifie si l'utilisateur est un professionnel (entreprise ou auto-entrepreneur)
     */
    public function isProfessionnel(): bool
    {
        return $this->account_type === 'professionnel';
    }

    /**
     * Vérifie si l'utilisateur est une entreprise
     */
    public function isEntreprise(): bool
    {
        return $this->account_type === 'professionnel' && $this->business_type === 'entreprise';
    }

    /**
     * Vérifie si l'utilisateur est un auto-entrepreneur
     */
    public function isAutoEntrepreneur(): bool
    {
        return $this->account_type === 'professionnel' && $this->business_type === 'auto_entrepreneur';
    }

    /**
     * Vérifie si l'utilisateur est un particulier (non professionnel)
     */
    public function isParticulier(): bool
    {
        return $this->account_type === 'particulier';
    }

    /**
     * Vérifie si le profil professionnel est vérifié
     */
    public function isProVerified(): bool
    {
        return $this->pro_verified === true;
    }

    /**
     * Retourne le libellé du type de compte
     */
    public function getAccountTypeLabel(): string
    {
        if ($this->isEntreprise()) {
            return 'Entreprise';
        } elseif ($this->isAutoEntrepreneur()) {
            return 'Auto-entrepreneur';
        }
        return 'Particulier';
    }

    /**
     * Retourne le nombre maximum d'annonces actives autorisées
     */
    public function getMaxActiveAds(): int
    {
        // Entreprise vérifiée: illimité (1000)
        if ($this->isEntreprise() && $this->isProVerified()) {
            return 1000;
        }
        // Entreprise non vérifiée: 20
        if ($this->isEntreprise()) {
            return 20;
        }
        // Auto-entrepreneur vérifié: 15
        if ($this->isAutoEntrepreneur() && $this->isProVerified()) {
            return 15;
        }
        // Auto-entrepreneur non vérifié: 10
        if ($this->isAutoEntrepreneur()) {
            return 10;
        }
        // Particulier: 5
        return $this->max_active_ads ?? 5;
    }

    /**
     * Vérifie si l'utilisateur peut publier une nouvelle annonce
     */
    public function canPublishNewAd(): bool
    {
        $activeAdsCount = $this->ads()->where('status', 'active')->count();
        return $activeAdsCount < $this->getMaxActiveAds();
    }

    /**
     * Récupère les catégories principales où l'utilisateur offre des services
     */
    public function getServiceCategories()
    {
        return $this->services()->active()->pluck('main_category')->unique();
    }

    /**
     * Récupère les sous-catégories où l'utilisateur offre des services
     */
    public function getServiceSubcategories()
    {
        return $this->services()->active()->pluck('subcategory');
    }

    /**
     * Vérifie si l'utilisateur offre un service dans une catégorie
     */
    public function offersServiceIn($categoryOrSubcategory): bool
    {
        return $this->services()
            ->active()
            ->where(function($q) use ($categoryOrSubcategory) {
                $q->where('main_category', $categoryOrSubcategory)
                  ->orWhere('subcategory', $categoryOrSubcategory);
            })
            ->exists();
    }

    /**
     * Vérifie si l'utilisateur est l'administrateur principal
     */
    public function isPrincipalAdmin(): bool
    {
        return $this->email === config('admin.principal_admin.email');
    }

    /**
     * Vérifie si l'utilisateur a un privilège admin spécifique
     */
    public function hasAdminPrivilege(string $privilege): bool
    {
        // L'admin principal a tous les privilèges
        if ($this->isPrincipalAdmin()) {
            return true;
        }

        // Les autres admins ont des privilèges spécifiques
        $privileges = $this->admin_privileges ?? [];
        return in_array($privilege, $privileges);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }

    public function referralRewardsEarned()
    {
        return $this->hasMany(ReferralReward::class, 'referrer_user_id');
    }

    public function referralRewardsTriggered()
    {
        return $this->hasMany(ReferralReward::class, 'referee_user_id');
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class)
                    ->withPivot('earned_at');
    }

    public function addPoints($points, $type, $description, $source = null)
    {
        $this->total_points += $points;
        $this->available_points += $points;
        
        // Si c'est un gain quotidien
        if ($type === 'daily') {
            $this->daily_points += $points;
        }
        
        $this->save();

        // Créer la transaction
        $this->pointTransactions()->create([
            'points' => $points,
            'type' => $type,
            'description' => $description,
            'source' => $source
        ]);

        // Vérifier si l'utilisateur a gagné un niveau
        $this->checkLevelUp();

        // Vérifier les badges
        $this->checkBadges();
    }

    public function spendPoints($points, $type, $description)
    {
        if ($this->available_points < $points) {
            return false;
        }

        $this->available_points -= $points;
        $this->save();

        $this->pointTransactions()->create([
            'points' => -$points,
            'type' => $type,
            'description' => $description
        ]);

        return true;
    }

    public function checkLevelUp()
    {
        $newLevel = floor($this->total_points / 100) + 1;
        
        if ($newLevel > $this->level) {
            $this->level = $newLevel;
            $this->save();
            return true;
        }
        
        return false;
    }

    public function checkBadges()
    {
        $badges = Badge::where('points_required', '<=', $this->total_points)
                       ->where('level_required', '<=', $this->level)
                       ->get();

        foreach ($badges as $badge) {
            if (!$this->badges->contains($badge->id)) {
                $this->badges()->attach($badge->id);
            }
        }
    }

    public function hasBadge($badgeName)
    {
        return $this->badges()->where('name', $badgeName)->exists();
    }

    public function resetDailyPoints()
    {
        $this->daily_points = 0;
        $this->last_daily_reset = now();
        $this->save();
    }

    // Relations de messagerie
    public function conversations()
    {
        return Conversation::where('user1_id', $this->id)
            ->orWhere('user2_id', $this->id);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Messages reçus par l'utilisateur (via conversations)
     */
    public function receivedMessages()
    {
        return Message::whereHas('conversation', function($query) {
            $query->where('user1_id', $this->id)
                  ->orWhere('user2_id', $this->id);
        })->where('sender_id', '!=', $this->id);
    }

    public function unreadMessagesCount()
    {
        return Message::whereHas('conversation', function($query) {
            $query->where('user1_id', $this->id)
                  ->orWhere('user2_id', $this->id);
        })->where('sender_id', '!=', $this->id)
          ->where('is_read', false)
          ->count();
    }

    public function canSendMessage()
    {
        // Messagerie gratuite pour tous les utilisateurs connectés
        return true;
    }

    /**
     * Les abonnements ont été supprimés.
     * Cette méthode est conservée pour la compatibilité mais retourne toujours false.
     */
    public function hasActiveSubscription()
    {
        return false;
    }

    /**
     * Les abonnements premium ont été supprimés.
     * Retourne false pour tous les utilisateurs.
     */
    public function isPremium(): bool
    {
        return false;
    }

    /**
     * Annonces sauvegardées par l'utilisateur
     */
    public function savedAds()
    {
        return $this->belongsToMany(Ad::class, 'saved_ads')->withTimestamps();
    }

    public function savedSearches()
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function serviceOrdersPlaced()
    {
        return $this->hasMany(ServiceOrder::class, 'buyer_id');
    }

    public function serviceOrdersReceived()
    {
        return $this->hasMany(ServiceOrder::class, 'seller_id');
    }

    /**
     * Avis reçus par l'utilisateur
     */
    public function reviewsReceived()
    {
        return $this->hasMany(Review::class, 'reviewed_user_id');
    }

    /**
     * Avis vérifiés reçus : uniquement ceux laissés par des utilisateurs
     * ayant publié au moins une annonce OU ayant effectué au moins un paiement.
     * Ceci empêche les avis abusifs de comptes fantômes.
     */
    public function verifiedReviewsReceived()
    {
        return $this->hasMany(Review::class, 'reviewed_user_id')
            ->whereHas('reviewer', function ($q) {
                $q->where(function ($q2) {
                    // Le reviewer a publié au moins une annonce
                    $q2->whereHas('ads');
                })->orWhere(function ($q2) {
                    // OU le reviewer a effectué au moins un paiement
                    $q2->whereHas('transactions', function ($q3) {
                        $q3->where('status', 'completed');
                    });
                });
            });
    }

    /**
     * Avis donnés par l'utilisateur
     */
    public function reviewsGiven()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    /**
     * Transactions de paiement de l'utilisateur
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Vérifie si l'utilisateur a sauvegardé une annonce
     */
    public function hasSavedAd(Ad $ad): bool
    {
        return $this->savedAds()->where('ad_id', $ad->id)->exists();
    }

    /**
     * Sauvegarder/Retirer une annonce des favoris
     */
    public function toggleSaveAd(Ad $ad): bool
    {
        if ($this->hasSavedAd($ad)) {
            $this->savedAds()->detach($ad->id);
            return false; // Retirée
        } else {
            $this->savedAds()->attach($ad->id);
            return true; // Sauvegardée
        }
    }

    // =========================================
    // RELATIONS PRO DASHBOARD
    // =========================================

    // =========================================
    // OUTIL DEVIS/FACTURE (Quote Tool)
    // =========================================

    public function canCreateFreeQuote(): bool
    {
        return ($this->free_quotes_used ?? 0) < 1;
    }

    public function canCreateDocument(): bool
    {
        // Abonnés pro : génération illimitée gratuite
        if ($this->hasActiveProSubscription()) {
            return true;
        }
        return $this->canCreateFreeQuote() || ($this->paid_quotes_remaining ?? 0) > 0;
    }

    public function useDocumentCredit(): void
    {
        // Abonnés pro : pas de déduction de crédits
        if ($this->hasActiveProSubscription()) {
            return;
        }
        if ($this->canCreateFreeQuote()) {
            $this->increment('free_quotes_used');
        } elseif ($this->paid_quotes_remaining > 0) {
            $this->decrement('paid_quotes_remaining');
        }
    }

    public function getDocumentCreditsRemaining(): int
    {
        // Abonnés pro : illimité (on retourne -1 pour signaler "illimité")
        if ($this->hasActiveProSubscription()) {
            return -1;
        }
        $free = $this->canCreateFreeQuote() ? 1 : 0;
        return $free + ($this->paid_quotes_remaining ?? 0);
    }

    /**
     * Clients du professionnel
     */
    public function proClients()
    {
        return $this->hasMany(ProClient::class, 'provider_id');
    }

    /**
     * Devis créés par le professionnel
     */
    public function proQuotes()
    {
        return $this->hasMany(ProQuote::class);
    }

    /**
     * Factures créées par le professionnel
     */
    public function proInvoices()
    {
        return $this->hasMany(ProInvoice::class);
    }

    /**
     * Documents du professionnel
     */
    public function proDocuments()
    {
        return $this->hasMany(ProDocument::class);
    }

    /**
     * Abonnement pro actif
     */
    public function proSubscription()
    {
        return $this->hasOne(ProSubscription::class)->where('status', 'active');
    }

    /**
     * Tous les abonnements pro
     */
    public function proSubscriptions()
    {
        return $this->hasMany(ProSubscription::class);
    }

    /**
     * Vérifie si le pro a un abonnement actif
     */
    public function hasActiveProSubscription(): bool
    {
        return $this->proSubscription()->exists();
    }

    /**
     * Vérifie si l'onboarding pro est complété
     */
    public function hasCompletedProOnboarding(): bool
    {
        return (bool) $this->pro_onboarding_completed;
    }

    /**
     * Smart check: devrait-on afficher le modal de sélection de catégories ?
     * Vérifie intelligemment si l'utilisateur a VRAIMENT besoin de choisir ses catégories
     * en tenant compte de TOUTES les sources possibles (onboarding, inscription, profil).
     */
    public function shouldShowCategorySelectionModal(): bool
    {
        // Pas un professionnel → jamais afficher
        if ($this->account_type !== 'professionnel') {
            return false;
        }

        // L'onboarding complet n'est pas terminé → le modal d'onboarding principal s'en charge
        if (!$this->hasCompletedProOnboarding()) {
            return false;
        }

        // Si service_category est déjà rempli → pas besoin
        if (!empty($this->service_category)) {
            return false;
        }

        // Si pro_service_categories est rempli (via onboarding) → pas besoin non plus
        if (!empty($this->pro_service_categories) && is_array($this->pro_service_categories) && count($this->pro_service_categories) > 0) {
            return false;
        }

        // Si l'utilisateur a un abonnement actif → il a déjà tout configuré
        if ($this->hasActiveProSubscription()) {
            return false;
        }

        // Si l'utilisateur a des services enregistrés → il a déjà configuré
        if ($this->services()->exists()) {
            return false;
        }

        // Aucune catégorie trouvée nulle part → afficher le modal
        return true;
    }

    /**
     * Should the onboarding modal be shown?
     * Shows for professionals who haven't completed AND haven't permanently dismissed
     */
    public function shouldShowOnboardingModal(): bool
    {
        if (!$this->isProfessionnel() && !$this->isServiceProvider()) {
            return false;
        }
        if ($this->hasCompletedProOnboarding()) {
            return false;
        }
        return true;
    }

    /**
     * Smart suggestion engine - returns prioritized suggestions for the professional
     */
    public function getProSuggestions(): array
    {
        $suggestions = [];

        if (!$this->isProfessionnel() && !$this->isServiceProvider()) {
            return $suggestions;
        }

        // 1. Complete onboarding
        if (!$this->hasCompletedProOnboarding()) {
            $step = $this->pro_onboarding_step ?? 0;
            $suggestions[] = [
                'id' => 'complete_onboarding',
                'priority' => 100,
                'type' => 'warning',
                'icon' => 'fas fa-magic',
                'title' => $step > 0
                    ? 'Reprendre la configuration (étape ' . $step . '/6)'
                    : 'Configurer votre espace professionnel',
                'description' => 'Complétez votre profil pour recevoir des demandes de clients près de chez vous.',
                'action' => 'openOnboardingModal(' . max($step, 1) . ')',
                'action_label' => $step > 0 ? 'Reprendre' : 'Commencer',
                'color' => '#6366f1',
            ];
        }

        // 2. No subscription
        if (!$this->hasActiveProSubscription() && $this->hasCompletedProOnboarding()) {
            $suggestions[] = [
                'id' => 'get_subscription',
                'priority' => 90,
                'type' => 'info',
                'icon' => 'fas fa-crown',
                'title' => 'Passez à ProxiPro Premium',
                'description' => 'Recevez 3x plus de demandes et accédez aux outils avancés. À partir de 9,99€/mois.',
                'action' => "window.location.href='" . route('pro.subscription') . "'",
                'action_label' => 'Voir les offres',
                'color' => '#f59e0b',
            ];
        }

        // 3. Missing phone
        if (empty($this->phone)) {
            $suggestions[] = [
                'id' => 'add_phone',
                'priority' => 80,
                'type' => 'tip',
                'icon' => 'fas fa-phone',
                'title' => 'Ajoutez votre numéro de téléphone',
                'description' => 'Les clients préfèrent les professionnels joignables par téléphone.',
                'action' => "window.location.href='" . route('pro.profile.edit') . "'",
                'action_label' => 'Ajouter',
                'color' => '#10b981',
            ];
        }

        // 4. Missing categories
        if (empty($this->pro_service_categories) || (is_array($this->pro_service_categories) && count($this->pro_service_categories) === 0)) {
            $suggestions[] = [
                'id' => 'add_categories',
                'priority' => 85,
                'type' => 'warning',
                'icon' => 'fas fa-tags',
                'title' => 'Sélectionnez vos catégories de métier',
                'description' => 'Sans catégories, les clients ne peuvent pas vous trouver.',
                'action' => 'openOnboardingModal(3)',
                'action_label' => 'Choisir',
                'color' => '#ef4444',
            ];
        }

        // 5. No location
        if (!$this->hasGeoLocation() && empty($this->city)) {
            $suggestions[] = [
                'id' => 'add_location',
                'priority' => 75,
                'type' => 'tip',
                'icon' => 'fas fa-map-marker-alt',
                'title' => 'Indiquez votre zone d\'intervention',
                'description' => 'Définissez votre localisation pour recevoir des demandes locales.',
                'action' => 'openOnboardingModal(2)',
                'action_label' => 'Localiser',
                'color' => '#3b82f6',
            ];
        }

        // 6. Profile not verified
        if (!$this->isProVerified()) {
            $suggestions[] = [
                'id' => 'verify_profile',
                'priority' => 60,
                'type' => 'tip',
                'icon' => 'fas fa-shield-alt',
                'title' => 'Faites vérifier votre profil',
                'description' => 'Les profils vérifiés reçoivent 3x plus de contacts clients.',
                'action' => "window.location.href='" . route('verification.index') . "'",
                'action_label' => 'Vérifier',
                'color' => '#14b8a6',
            ];
        }

        // 7. No bio
        if (empty($this->bio)) {
            $suggestions[] = [
                'id' => 'add_bio',
                'priority' => 50,
                'type' => 'tip',
                'icon' => 'fas fa-pen',
                'title' => 'Ajoutez une description',
                'description' => 'Présentez-vous pour gagner la confiance de vos futurs clients.',
                'action' => "window.location.href='" . route('pro.profile.edit') . "'",
                'action_label' => 'Rédiger',
                'color' => '#8b5cf6',
            ];
        }

        // 8. No active ads
        if ($this->ads()->where('status', 'active')->count() === 0 && $this->hasCompletedProOnboarding()) {
            $suggestions[] = [
                'id' => 'create_ad',
                'priority' => 55,
                'type' => 'info',
                'icon' => 'fas fa-bullhorn',
                'title' => 'Publiez votre première annonce',
                'description' => 'Mettez en avant vos services pour attirer plus de clients.',
                'action' => "window.location.href='" . route('ads.create') . "'",
                'action_label' => 'Publier',
                'color' => '#f97316',
            ];
        }

        // Sort by priority descending
        usort($suggestions, fn($a, $b) => $b['priority'] - $a['priority']);

        return $suggestions;
    }

    /**
     * Get profile completion percentage
     */
    public function getProProfileCompletionPercent(): int
    {
        $fields = [
            !empty($this->company_name) || !empty($this->name),
            !empty($this->email),
            !empty($this->phone),
            !empty($this->city) || !empty($this->detected_city),
            !empty($this->address),
            !empty($this->bio),
            !empty($this->pro_service_categories) && count($this->pro_service_categories ?? []) > 0,
            $this->hasGeoLocation(),
            !empty($this->avatar),
            $this->isProVerified(),
            $this->hasActiveProSubscription(),
            $this->hasCompletedProOnboarding(),
        ];
        $total = count($fields);
        $filled = count(array_filter($fields));
        return (int) round(($filled / $total) * 100);
    }

    // =========================================
    // GÉOLOCALISATION
    // =========================================

    /**
     * Vérifie si l'utilisateur a des coordonnées GPS
     */
    public function hasGeoLocation(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude)
            && $this->latitude != 0 && $this->longitude != 0;
    }

    /**
     * Retourne le rayon de recherche préféré (en km)
     */
    public function getGeoRadius(): int
    {
        return $this->geo_radius ?? 50;
    }

    /**
     * Retourne le nom de la ville détectée ou renseignée
     */
    public function getDisplayCity(): ?string
    {
        return $this->detected_city ?? $this->city ?? null;
    }

    /**
     * Retourne le nom du pays détecté ou renseigné
     */
    public function getDisplayCountry(): ?string
    {
        return $this->detected_country ?? $this->country ?? null;
    }

    /**
     * Retourne un résumé de la localisation pour affichage
     */
    public function getLocationLabel(): string
    {
        $city = $this->getDisplayCity();
        $country = $this->getDisplayCountry();

        if ($city && $country) {
            return "{$city}, {$country}";
        }
        return $city ?? $country ?? 'Position non détectée';
    }
}

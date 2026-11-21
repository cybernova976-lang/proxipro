<?php

namespace App\Models;

use App\Support\MarketplaceCategoryRegistry;
use App\Support\Territory;
use App\Support\TerritoryResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Ad extends Model
{
    protected static function booted(): void
    {
        $clearFeedCategoryCache = function () {
            Cache::forget('feed:active-category-counts:v2:all');
            Cache::forget('feed:active-category-counts:v2:offre');
        };

        static::saved($clearFeedCategoryCache);
        static::deleted($clearFeedCategoryCache);

        // Dernier rempart : supprimer une annonce efface ses commandes en base.
        // Une annonce qui porte une commande à conserver n'est donc jamais supprimée.
        static::deleting(fn (Ad $ad) => $ad->hasKeptOrders() ? false : null);
    }

    /** L'annonce porte-t-elle une commande en cours (à accepter, à payer, payée ou en litige) ? */
    public function hasOpenOrders(): bool
    {
        return $this->serviceOrders()->whereIn('status', ServiceOrder::OPEN_STATUSES)->exists();
    }

    /** L'annonce porte-t-elle une commande à conserver (en cours, terminée ou remboursée) ? */
    public function hasKeptOrders(): bool
    {
        return $this->serviceOrders()->whereIn('status', ServiceOrder::KEPT_STATUSES)->exists();
    }

    /**
     * Retire toutes les annonces d'un compte : celles qui portent une commande
     * à conserver sont seulement dépubliées, les autres sont supprimées.
     */
    public static function removeAllOf(User $user): void
    {
        $keptIds = static::where('user_id', $user->id)
            ->whereHas('serviceOrders', fn ($orders) => $orders->whereIn('status', ServiceOrder::KEPT_STATUSES))
            ->pluck('id');

        static::whereIn('id', $keptIds)->update(['status' => 'inactive']);
        static::where('user_id', $user->id)->whereNotIn('id', $keptIds)->delete();
    }

    protected $fillable = [
        'title',
        'description',
        'category',
        'main_category',
        'publication_domain',
        'ad_details',
        'location',
        'city',
        'price',
        'price_type',
        'service_type',
        'status',
        'expires_at',
        'publication_terms_accepted_at',
        'publication_terms_version',
        'photos',
        'user_id',
        'latitude',
        'longitude',
        'address',
        'postal_code',
        'country',
        'radius_km',
        'is_pinned',
        'pinned_until',
        'is_boosted',
        'boost_end',
        'boost_type',
        'boost_source',
        'is_urgent',
        'urgent_until',
        'urgent_source',
        'sidebar_priority',
        'bumped_at',
        'bump_every_days',
        'bump_until',
        'shares_count',
        'reply_restriction',
        'visibility',
        'target_categories',
        'views',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'radius_km' => 'integer',
        'price' => 'decimal:2',
        'is_pinned' => 'boolean',
        'pinned_until' => 'datetime',
        'is_boosted' => 'boolean',
        'boost_end' => 'datetime',
        'is_urgent' => 'boolean',
        'urgent_until' => 'datetime',
        'bumped_at' => 'datetime',
        'bump_until' => 'datetime',
        'bump_every_days' => 'integer',
        'expires_at' => 'datetime',
        'publication_terms_accepted_at' => 'datetime',
        'sidebar_priority' => 'integer',
        'photos' => 'array',
        'target_categories' => 'array',
        'ad_details' => 'array',
        // Prestataires réellement prévenus à la publication ; NULL = non mesuré.
        'notified_providers_count' => 'integer',
    ];

    public function scopeMarketplaceActive($query)
    {
        return $query
            ->inEnabledCategories()
            ->where('status', 'active')
            ->where(function ($activeQuery) {
                $activeQuery->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeInEnabledCategories($query)
    {
        return MarketplaceCategoryRegistry::applyEnabledScope($query);
    }

    /**
     * Annonces rattachées au territoire, selon les règles de TerritoryResolver
     * (code postal, pays d'outre-mer, commune connue, département).
     */
    public function scopeInTerritory($query, Territory $territory)
    {
        [$sql, $bindings] = app(TerritoryResolver::class)->sqlCondition($territory, self::territoryColumns($query));

        return $query->whereRaw($sql, $bindings);
    }

    /** Offres déclarées « À distance » (ad_details.intervention_mode = remote) situées hors du territoire. */
    public function scopeRemoteOutside($query, Territory $territory)
    {
        [$sql, $bindings] = app(TerritoryResolver::class)->sqlCondition($territory, self::territoryColumns($query));

        return $query->where($query->getModel()->getTable().'.ad_details->intervention_mode', 'remote')
            ->whereRaw('NOT '.$sql, $bindings);
    }

    /** Retire les annonces des comptes internes ou de test (config feed.hidden_author_ids). */
    public function scopeExcludingHiddenAuthors($query)
    {
        $ids = self::hiddenAuthorIds();

        return $ids === [] ? $query : $query->whereNotIn($query->getModel()->getTable().'.user_id', $ids);
    }

    /** @return list<int> */
    public static function hiddenAuthorIds(): array
    {
        $ids = config('feed.hidden_author_ids', []);
        $ids = is_array($ids) ? $ids : explode(',', (string) $ids);

        return array_values(array_unique(array_filter(array_map('intval', $ids), fn (int $id) => $id > 0)));
    }

    public function isRemoteIntervention(): bool
    {
        $details = is_array($this->ad_details) ? $this->ad_details : [];

        return ($details['intervention_mode'] ?? null) === 'remote';
    }

    private static function territoryColumns($query): array
    {
        $table = $query->getModel()->getTable();

        return [
            'postal' => $table.'.postal_code',
            'country' => $table.'.country',
            'city' => $table.'.city',
            'location' => $table.'.location',
        ];
    }

    /**
     * Moment souhaité, dit honnêtement : « Cette semaine » plutôt que la date
     * technique du jour enregistrée pour une demande guidée sans date précise.
     */
    public function desiredWhenLabel(): ?string
    {
        return self::whenLabelFromDetails(is_array($this->ad_details) ? $this->ad_details : []);
    }

    /**
     * Même règle, à partir du tableau ad_details (sans modèle).
     * Avec $withWindow, le moment de la journée est ajouté : « · Matin ».
     */
    public static function whenLabelFromDetails(array $details, bool $withWindow = true): ?string
    {
        $labels = ['this_week' => 'Cette semaine', 'this_month' => 'Ce mois-ci', 'no_rush' => 'Pas pressé'];
        $when = is_string($details['when'] ?? null) ? $details['when'] : null;
        $label = $labels[$when] ?? null;

        if ($label === null && ! empty($details['desired_date'])) {
            try {
                $label = \Carbon\Carbon::parse($details['desired_date'])->locale('fr')->translatedFormat('d F Y');
            } catch (\Throwable) {
                $label = null;
            }
        }

        if ($label === null) {
            return null;
        }

        $windows = ['morning' => 'Matin', 'afternoon' => 'Après-midi', 'evening' => 'Soir'];
        $window = is_string($details['time_window'] ?? null) ? ($windows[$details['time_window']] ?? null) : null;

        return $withWindow && $window !== null ? $label.' · '.$window : $label;
    }

    public function getEffectivePriceTypeAttribute(): string
    {
        $type = $this->attributes['price_type'] ?? null;

        if (in_array($type, ['fixed', 'hourly', 'negotiable'], true)) {
            return $type;
        }

        if ($this->price !== null) {
            return $this->service_type === 'demande' ? 'hourly' : 'fixed';
        }

        return 'negotiable';
    }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->effective_price_type === 'negotiable' || $this->price === null) {
            return 'À négocier';
        }

        $price = (float) $this->price;
        $decimals = floor($price) === $price ? 0 : 2;
        $formatted = number_format($price, $decimals, ',', ' ');

        if ($this->effective_price_type === 'hourly') {
            return $formatted.' €/h';
        }

        $details = is_array($this->ad_details) ? $this->ad_details : [];
        $unit = match ($this->publication_domain) {
            'ridesharing' => '/place',
            'rental' => match ($details['rental_period'] ?? null) {
                'hour' => '/h',
                'day' => '/jour',
                'week' => '/semaine',
                'month' => '/mois',
                default => '',
            },
            'employment' => match ($details['compensation_period'] ?? null) {
                'hour' => '/h',
                'day' => '/jour',
                'month' => '/mois',
                'year' => '/an',
                default => '',
            },
            default => '',
        };

        return $formatted.' €'.$unit;
    }

    public function getPriceModeLabelAttribute(): string
    {
        return match ($this->effective_price_type) {
            'fixed' => 'Prix global',
            'hourly' => 'Tarif horaire',
            default => 'À négocier',
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get comments for the ad
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function serviceOrders()
    {
        return $this->hasMany(ServiceOrder::class);
    }

    public function serviceProposals()
    {
        return $this->hasMany(ServiceProposal::class);
    }

    /**
     * Portée géographique pour les recherches par rayon (formule de Haversine)
     */
    public function scopeWithinRadius($query, $lat, $lng, $radius)
    {
        $haversine = '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))';

        return $query
            ->select('*')
            ->selectRaw("{$haversine} AS distance", [$lat, $lng, $lat])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("{$haversine} < ?", [$lat, $lng, $lat, $radius])
            ->orderBy('distance');
    }

    /**
     * Recherche par catégorie
     */
    public function scopeByCategory($query, $category)
    {
        if ($category) {
            return $query->where('category', $category);
        }

        return $query;
    }

    /**
     * Recherche par prix
     */
    public function scopeByPriceRange($query, $minPrice, $maxPrice)
    {
        if ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        return $query;
    }

    /**
     * Recherche par mot-clé
     */
    public function scopeSearch($query, $searchTerm)
    {
        if (! $searchTerm) {
            return $query;
        }

        return $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'LIKE', "%{$searchTerm}%")
                ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                ->orWhere('location', 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Services populaires par région
     */
    public static function popularServicesByRegion($region, $limit = 5)
    {
        return self::where('location', 'LIKE', "%{$region}%")
            ->inEnabledCategories()
            ->where('status', 'active')
            ->select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderBy('count', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Scope: Annonces urgentes actives (premium seulement)
     */
    public function scopeUrgent($query)
    {
        return $query->where('is_urgent', true)
            ->where(function ($q) {
                $q->whereNull('urgent_until')
                    ->orWhere('urgent_until', '>', now());
            });
    }

    /**
     * Scope: Annonces boostées actives
     */
    public function scopeBoosted($query)
    {
        return $query->where('is_boosted', true)
            ->where('boost_end', '>', now());
    }

    /**
     * Scope: Annonces pour le sidebar gauche (urgent + boosté)
     */
    public function scopeSidebar($query)
    {
        return $query->inEnabledCategories()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('is_urgent', true)
                        ->where(function ($q3) {
                            $q3->whereNull('urgent_until')
                                ->orWhere('urgent_until', '>', now());
                        });
                })->orWhere(function ($q2) {
                    $q2->where('is_boosted', true)
                        ->where('boost_end', '>', now());
                });
            });
    }

    /**
     * Vérifie si l'annonce est actuellement urgente et active
     */
    public function isCurrentlyUrgent(): bool
    {
        return $this->is_urgent && (! $this->urgent_until || $this->urgent_until->isFuture());
    }

    /**
     * Urgence mise en avant : option payante en cours, ou urgence accordée par
     * l'administration avec une échéance. L'urgence simplement cochée au dépôt
     * (sans échéance) reste une information et ne classe pas, avec les
     * nouvelles règles de mise en avant.
     */
    public function hasPromotedUrgency(): bool
    {
        return $this->is_urgent && $this->urgent_until && $this->urgent_until->isFuture();
    }

    /**
     * L'option payante « Urgent » apporterait-elle quelque chose à cette demande ?
     * Avec les règles en vigueur, une urgence indiquée gratuitement au dépôt
     * donne déjà la mention « Urgent » et la priorité sur l'accueil des
     * prestataires : la vendre en plus ne changerait rien. Avec les nouvelles
     * règles, seule l'option classe, donc elle reste proposée.
     */
    public function canBuyUrgentOption(): bool
    {
        if ($this->service_type !== 'demande' || $this->hasPromotedUrgency()) {
            return false;
        }

        return \App\Support\AdPromotionCatalog::newRulesActive() || ! $this->isCurrentlyUrgent();
    }

    /** Portée SQL de hasPromotedUrgency(). */
    public function scopePromotedUrgent($query)
    {
        return $query->where('is_urgent', true)->where('urgent_until', '>', now());
    }

    /** Une remontée automatique achetée est-elle en cours ? */
    public function hasActiveBump(): bool
    {
        return $this->bump_until !== null && $this->bump_until->isFuture();
    }

    /**
     * Expression SQL de la date qui classe « les plus récentes d'abord » : la
     * dernière remontée quand il y en a une, sinon la publication.
     */
    public static function freshnessSql(string $table = 'ads'): string
    {
        return "COALESCE({$table}.bumped_at, {$table}.created_at)";
    }

    /**
     * Vérifie si l'annonce est actuellement boostée et active
     */
    public function isCurrentlyBoosted(): bool
    {
        return $this->is_boosted && $this->boost_end && $this->boost_end->isFuture();
    }

    /**
     * Retourne la meilleure date d'expiration de visibilité (urgent OU boost, le plus tard)
     */
    public function getBestVisibilityEnd(): ?\Carbon\Carbon
    {
        $dates = [];
        if ($this->isCurrentlyUrgent() && $this->urgent_until) {
            $dates[] = $this->urgent_until;
        }
        if ($this->isCurrentlyBoosted() && $this->boost_end) {
            $dates[] = $this->boost_end;
        }

        return count($dates) > 0 ? max($dates) : null;
    }

    /**
     * Retourne les jours restants de la meilleure visibilité active
     */
    public function getRemainingVisibilityDays(): int
    {
        $best = $this->getBestVisibilityEnd();

        return $best ? max(0, (int) now()->diffInDays($best, false)) : 0;
    }

    /**
     * Retourne un résumé complet du statut de boost/urgent
     */
    public function getBoostStatus(): array
    {
        $isUrgent = $this->isCurrentlyUrgent();
        $isBoosted = $this->isCurrentlyBoosted();
        $isPermanentUrgent = $isUrgent && is_null($this->urgent_until);
        $urgentDaysLeft = $isUrgent && $this->urgent_until ? max(0, (int) now()->diffInDays($this->urgent_until, false)) : 0;
        $boostDaysLeft = $isBoosted && $this->boost_end ? max(0, (int) now()->diffInDays($this->boost_end, false)) : 0;
        $bestEnd = $this->getBestVisibilityEnd();

        return [
            'is_urgent' => $isUrgent,
            'is_permanent_urgent' => $isPermanentUrgent,
            'is_boosted' => $isBoosted,
            'urgent_days_left' => $urgentDaysLeft,
            'boost_days_left' => $boostDaysLeft,
            'urgent_until' => $isUrgent ? $this->urgent_until : null,
            'boost_end' => $isBoosted ? $this->boost_end : null,
            'best_visibility_end' => $bestEnd,
            'best_days_left' => $bestEnd ? max(0, (int) now()->diffInDays($bestEnd, false)) : 0,
            'has_any_visibility' => $isUrgent || $isBoosted,
            'boost_type' => $this->boost_type,
            'is_expiring_soon' => $bestEnd && now()->diffInDays($bestEnd, false) <= 2 && now()->diffInDays($bestEnd, false) >= 0,
        ];
    }
}

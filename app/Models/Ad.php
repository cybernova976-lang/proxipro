<?php

namespace App\Models;

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
        'is_urgent',
        'urgent_until',
        'sidebar_priority',
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
        'expires_at' => 'datetime',
        'publication_terms_accepted_at' => 'datetime',
        'sidebar_priority' => 'integer',
        'photos' => 'array',
        'target_categories' => 'array',
        'ad_details' => 'array',
    ];

    public function scopeMarketplaceActive($query)
    {
        return $query
            ->where('status', 'active')
            ->where(function ($activeQuery) {
                $activeQuery->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
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
        return $query->where('status', 'active')
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

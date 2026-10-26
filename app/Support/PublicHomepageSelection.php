<?php

namespace App\Support;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Rules for the public showcase, without changing members' profiles or ads.
 * A supplied photo or logo is required; its presence does not verify identity.
 */
final class PublicHomepageSelection
{
    public const RECENT_AD_DAYS = 30;

    public const MAX_RECENT_ADS = 2;

    private const RECENT_AD_CANDIDATES = 40;

    public const MAX_FEATURED_PROVIDERS = 3;

    public static function recentAds(): Collection
    {
        $now = now();
        $query = Ad::marketplaceActive()
            ->where('visibility', 'public')
            ->whereIn('service_type', ['offre', 'demande'])
            ->whereBetween('created_at', [$now->copy()->subDays(self::RECENT_AD_DAYS), $now])
            ->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
            ->where(fn (Builder $query) => $query
                ->whereRaw("TRIM(COALESCE(city, '')) <> ''")
                ->orWhereRaw("TRIM(COALESCE(location, '')) <> ''"));

        self::requireTextLength($query, 'title', 8);
        self::requireTextLength($query, 'description', 30);

        return $query
            ->with('user:id,name,avatar,identity_verified')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::RECENT_AD_CANDIDATES)
            ->get()
            ->reject(fn (Ad $ad) => self::hasExplicitTypeConflict($ad))
            ->take(self::MAX_RECENT_ADS)
            ->values();
    }

    public static function featuredProviders(): Builder
    {
        $query = User::query()
            ->where('is_active', true)
            ->where('profile_public', true)
            ->where(fn (Builder $query) => $query
                ->where('user_type', 'professionnel')
                ->orWhere('is_service_provider', true));

        foreach (['profession', 'city', 'avatar'] as $column) {
            $query->whereRaw("TRIM(COALESCE(users.{$column}, '')) <> ''");
        }

        self::requireTextLength($query, 'users.bio', 40);

        return $query
            ->where(fn (Builder $query) => $query
                ->whereHas('ads', fn (Builder $query) => $query
                    ->marketplaceActive()
                    ->where('visibility', 'public')
                    ->where('service_type', 'offre'))
                ->orWhereHas('professionalRealizations', fn (Builder $query) => $query
                    ->whereRaw("TRIM(COALESCE(photo_path, '')) <> ''")))
            ->withCount([
                'ads as active_ads_count' => fn (Builder $query) => $query
                    ->marketplaceActive()
                    ->where('visibility', 'public')
                    ->where('service_type', 'offre'),
                'verifiedReviewsReceived as verified_reviews_count',
            ])
            ->withAvg('verifiedReviewsReceived as verified_reviews_avg', 'rating')
            ->orderByDesc('identity_verified')
            ->orderByDesc('verified_reviews_count')
            ->orderByDesc('active_ads_count')
            ->orderBy('id')
            ->limit(self::MAX_FEATURED_PROVIDERS);
    }

    private static function requireTextLength(Builder $query, string $column, int $minimum): void
    {
        // SQLite LENGTH counts characters; the other supported engines use CHAR_LENGTH.
        $function = $query->getConnection()->getDriverName() === 'sqlite' ? 'LENGTH' : 'CHAR_LENGTH';
        $query->whereRaw("{$function}(TRIM(COALESCE({$column}, ''))) >= ?", [$minimum]);
    }

    /**
     * Keep a few explicit title/type contradictions off the homepage only.
     * This is not semantic moderation: it neither proves consistency nor
     * changes the declared type or the publication's visibility elsewhere.
     */
    private static function hasExplicitTypeConflict(Ad $ad): bool
    {
        $title = Str::ascii(Str::lower(trim($ad->title)));
        $title = preg_replace('/\s+/', ' ', $title);
        $requestLanguage = preg_match('/\b(?:cherche|recherche|besoin)\b/', $title) === 1;
        $availabilityLanguage = preg_match('/\bdisponibles?\b/', $title) === 1;
        $proposalLanguage = preg_match('/\b(?:mes|nos) services\b|\b(?:je|nous)(?: vous)? (?:propose|proposons)\b/', $title) === 1;

        if ($ad->service_type === 'demande') {
            return ($availabilityLanguage || $proposalLanguage) && ! $requestLanguage;
        }

        $startsWithRequest = preg_match('/^(?:(?:je|nous) )?(?:cherche|recherche|besoin)\b/', $title) === 1;

        return $startsWithRequest && ! $proposalLanguage;
    }
}

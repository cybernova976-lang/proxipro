<?php

namespace App\Services;

use App\Models\ProSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientFeedOffersService
{
    /**
     * Order the complete visible result set in SQL before paginating. A capped
     * PHP candidate list would make older paid offers permanently unreachable.
     */
    public function paginate(Builder $query, ?User $viewer): LengthAwarePaginator
    {
        $subscription = ProSubscription::query()->selectRaw('1')
            ->whereColumn('pro_subscriptions.user_id', 'ads.user_id')
            ->currentlyActive()
            ->where(fn ($started) => $started->whereNull('starts_at')->orWhere('starts_at', '<=', now()));

        if ($query->getQuery()->columns === null) {
            $query->select('ads.*');
        }

        $query->selectRaw(
            'CASE WHEN (ads.is_boosted = ? AND ads.boost_end > ?) OR EXISTS ('.$subscription->toSql().') THEN 1 ELSE 0 END AS feed_is_promoted',
            [true, now(), ...$subscription->getBindings()]
        );

        $categories = collect([
            ...((array) ($viewer?->preferred_categories ?? [])),
            ...((array) ($viewer?->service_subcategories ?? [])),
            ...((array) ($viewer?->pro_service_categories ?? [])),
            $viewer?->service_category,
        ])->filter(fn ($category) => is_string($category) && trim($category) !== '')
            ->map(fn ($category) => mb_strtolower(trim($category)))
            ->unique()->values()->all();

        if ($categories !== []) {
            $placeholders = implode(', ', array_fill(0, count($categories), '?'));
            $query->selectRaw('CASE WHEN LOWER(ads.category) IN ('.$placeholders.') THEN 0 ELSE 1 END AS feed_affinity', $categories);
        } else {
            $query->selectRaw('0 AS feed_affinity');
        }

        // Two offers per author get a first pass within each promotion tier.
        // Windowing the full visible set keeps pagination and diversity consistent.
        $ranked = $query->getModel()->newQuery()
            ->fromSub($query->reorder()->toBase(), 'ads')->select('ads.*')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY ads.user_id, ads.feed_is_promoted ORDER BY ads.feed_affinity, ads.created_at DESC, ads.id DESC) AS feed_author_position');

        // The unique tie-breaker keeps pages stable even when timestamps match.
        return $query->getModel()->newQuery()->fromSub($ranked->toBase(), 'ads')
            ->select('ads.*')->setEagerLoads($query->getEagerLoads())
            ->orderByDesc('ads.feed_is_promoted')
            ->orderByRaw('CASE WHEN ads.feed_author_position <= 2 THEN 0 ELSE 1 END')
            ->orderBy('ads.feed_affinity')
            ->orderByDesc('ads.created_at')->orderByDesc('ads.id')
            ->paginate(6, ['*'], 'offers_page')
            ->withQueryString()->fragment('pkFeedTitle');
    }
}

<?php

namespace App\Services;

use App\Models\Ad;
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
        return $this->ranked($query, $viewer)
            ->paginate((int) config('feed.client.page_size', 6), ['*'], 'offers_page')
            ->withQueryString()->fragment('pkFeedTitle');
    }

    /**
     * Les mêmes offres, dans l'ordre du flux client : sert à la pagination et
     * à la liste « offres d'ailleurs » affichée quand le territoire est vide.
     */
    public function ranked(Builder $query, ?User $viewer): Builder
    {
        $this->addPromotedColumn($query);

        $categories = $this->viewerCategories($viewer);

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
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY ads.user_id, ads.feed_is_promoted ORDER BY ads.feed_affinity, '.Ad::freshnessSql().' DESC, ads.id DESC) AS feed_author_position');

        // The unique tie-breaker keeps pages stable even when timestamps match.
        return $query->getModel()->newQuery()->fromSub($ranked->toBase(), 'ads')
            ->select('ads.*')->setEagerLoads($query->getEagerLoads())
            ->orderByDesc('ads.feed_is_promoted')
            ->orderByRaw('CASE WHEN ads.feed_author_position <= 2 THEN 0 ELSE 1 END')
            ->orderBy('ads.feed_affinity')
            // « Plus récentes d'abord » : la dernière remontée compte comme une publication.
            ->orderByRaw(Ad::freshnessSql().' DESC')->orderByDesc('ads.id');
    }

    /**
     * Annonces admises sur l'accueil des particuliers (règle du 21/11/2026) :
     * toutes les offres, et les demandes dont l'annonce est boostée ou dont
     * l'auteur a un abonnement Premium en cours. Une demande gratuite d'un
     * membre non abonné reste dans « Annonces » et sur l'accueil des prestataires.
     */
    public function homeEligible(Builder $query): Builder
    {
        $subscription = $this->activeSubscriptionOfAuthor();

        return $query->where(function (Builder $eligible) use ($subscription) {
            $eligible->where('ads.service_type', 'offre')
                ->orWhere(function (Builder $request) use ($subscription) {
                    $request->where('ads.service_type', 'demande')
                        ->where(function (Builder $promoted) use ($subscription) {
                            $promoted->where(fn (Builder $boost) => $boost->where('ads.is_boosted', true)->where('ads.boost_end', '>', now()))
                                ->orWhereRaw('EXISTS ('.$subscription->toSql().')', $subscription->getBindings());
                        });
                });
        });
    }

    /**
     * Ajoute la colonne feed_is_promoted : 1 pour une annonce (offre ou demande)
     * mise en avant contre paiement (boost acheté en cours, ou auteur abonné
     * Premium), 0 sinon. Une seule définition, pour l'accueil des clients et la
     * liste des annonces. Un boost accordé gratuitement par l'administration
     * n'est pas sponsorisé.
     */
    public function addPromotedColumn(Builder $query): Builder
    {
        $subscription = $this->activeSubscriptionOfAuthor();

        if ($query->getQuery()->columns === null) {
            $query->select('ads.*');
        }

        return $query->selectRaw(
            'CASE WHEN (ads.is_boosted = ? AND ads.boost_end > ? AND COALESCE(ads.boost_source, \'\') <> \'admin\') OR EXISTS ('.$subscription->toSql().') THEN 1 ELSE 0 END AS feed_is_promoted',
            [true, now(), ...$subscription->getBindings()]
        );
    }

    /** Sous-requête : l'auteur de l'annonce a un abonnement Premium en cours. */
    private function activeSubscriptionOfAuthor(): Builder
    {
        return ProSubscription::query()->selectRaw('1')
            ->whereColumn('pro_subscriptions.user_id', 'ads.user_id')
            ->currentlyActive()
            ->where(fn ($started) => $started->whereNull('starts_at')->orWhere('starts_at', '<=', now()));
    }

    /**
     * Catégories suivies par le lecteur, en minuscules : elles départagent
     * les offres d'un même groupe (voir App\Services\Feed\RankingDisclosure).
     *
     * @return list<string>
     */
    public function viewerCategories(?User $viewer): array
    {
        return collect([
            ...((array) ($viewer?->preferred_categories ?? [])),
            ...((array) ($viewer?->service_subcategories ?? [])),
            ...((array) ($viewer?->pro_service_categories ?? [])),
            $viewer?->service_category,
        ])->filter(fn ($category) => is_string($category) && trim($category) !== '')
            ->map(fn ($category) => mb_strtolower(trim($category)))
            ->unique()->values()->all();
    }
}

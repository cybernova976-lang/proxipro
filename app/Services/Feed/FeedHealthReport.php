<?php

namespace App\Services\Feed;

use App\Models\Ad;
use App\Models\ProSubscription;
use App\Models\User;
use App\Services\IpGeolocationService;
use App\Support\Territory;
use App\Support\TerritoryResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mesures de l'écran /admin/feed-sante. Lecture seule : aucune écriture,
 * aucun cache, aucune notification.
 *
 * Le rattachement au territoire passe par TerritoryResolver, avec les mêmes
 * règles que le flux client. Les compteurs d'offres par territoire et la
 * liste « Cherche… » appliquent aussi les filtres du flux client (visibilité
 * publique, comptes masqués exclus) : ils correspondent à ce que voit un
 * visiteur de chaque territoire. La liste des auteurs, elle, inclut les
 * comptes déjà masqués.
 */
class FeedHealthReport
{
    /** Taille des listes affichées : l'écran reste lisible et la requête bornée. */
    public const LIST_LIMIT = 200;

    private const AD_PLACE_COLUMNS = ['id', 'user_id', 'postal_code', 'country', 'city', 'location'];

    public function __construct(private readonly TerritoryResolver $territories) {}

    public function build(): array
    {
        return [
            'territories' => $this->territoryRows(),
            'boosts' => $this->activeBoosts(),
            'plan_only_subscribers' => $this->planOnlySubscribers(),
            'misfiled_requests' => $this->offersTitledAsRequests(),
            'offer_authors' => $this->activeOfferAuthors(),
            'hidden_author_ids' => Ad::hiddenAuthorIds(),
            'default_location_users' => $this->defaultLocationUsersCount(),
            'generated_at' => now(),
        ];
    }

    /** Offres actives, avant filtre de visibilité et masquage des comptes. */
    public function activeOffersQuery(): Builder
    {
        return Ad::marketplaceActive()
            ->where('service_type', 'offre')
            ->where(fn ($domain) => $domain->whereNull('publication_domain')->orWhere('publication_domain', 'service'));
    }

    /** Offres que voit un client ou un visiteur : visibilité publique, comptes masqués exclus. */
    public function clientVisibleOffersQuery(): Builder
    {
        return $this->activeOffersQuery()
            ->where(fn ($v) => $v->where('visibility', 'public')->orWhereNull('visibility'))
            ->excludingHiddenAuthors();
    }

    /**
     * Une ligne par territoire rencontré : offres actives, offres promues
     * actives (boost en cours ou abonnement Premium actif de l'auteur),
     * prestataires, demandes publiées sur 30 et 90 jours.
     *
     * @return list<array{code: ?string, label: string, offers: int, promoted: int, providers: int, requests_30: int, requests_90: int}>
     */
    private function territoryRows(): array
    {
        $rows = [];
        $now = now();
        $premiumAuthors = array_flip($this->activePremiumUserIds());

        $this->clientVisibleOffersQuery()
            ->select([...self::AD_PLACE_COLUMNS, 'is_boosted', 'boost_end', 'boost_source'])
            ->lazyById(500)
            ->each(function (Ad $ad) use (&$rows, $now, $premiumAuthors) {
                $row = &$this->row($rows, $this->territories->forAd($ad));
                $row['offers']++;
                // Même règle que le flux client : un boost accordé par l'administration n'est pas une promotion.
                $boosted = (bool) $ad->is_boosted && $ad->boost_end !== null && $ad->boost_end->greaterThan($now)
                    && $ad->boost_source !== 'admin';
                if ($boosted || isset($premiumAuthors[(int) $ad->user_id])) {
                    $row['promoted']++;
                }
            });

        $since30 = $now->copy()->subDays(30);
        Ad::query()
            ->where('service_type', 'demande')
            ->where('created_at', '>=', $now->copy()->subDays(90))
            ->select([...self::AD_PLACE_COLUMNS, 'created_at'])
            ->lazyById(500)
            ->each(function (Ad $ad) use (&$rows, $since30) {
                $row = &$this->row($rows, $this->territories->forAd($ad));
                $row['requests_90']++;
                if ($ad->created_at !== null && $ad->created_at->greaterThanOrEqualTo($since30)) {
                    $row['requests_30']++;
                }
            });

        User::query()
            ->where(fn ($providers) => $providers->where('is_service_provider', true)
                ->orWhere('user_type', 'professionnel')
                ->orWhere('account_type', 'professionnel'))
            ->select(['id', 'postal_code', 'country', 'city', 'geo_source', 'detected_country', 'detected_city', 'latitude', 'longitude'])
            ->lazyById(500)
            ->each(function (User $provider) use (&$rows) {
                $row = &$this->row($rows, $this->territories->forUser($provider));
                $row['providers']++;
            });

        return $this->sortRows($rows);
    }

    /** @return array<string, mixed> ligne du territoire, créée au besoin */
    private function &row(array &$rows, ?Territory $territory): array
    {
        $key = $territory?->code ?? '';
        if (! isset($rows[$key])) {
            $rows[$key] = [
                'code' => $territory?->code,
                'label' => $territory?->label ?? 'Territoire inconnu',
                'overseas' => $territory?->isOverseas() ?? false,
                'offers' => 0, 'promoted' => 0, 'providers' => 0, 'requests_30' => 0, 'requests_90' => 0,
            ];
        }

        return $rows[$key];
    }

    /** Outre-mer d'abord (ordre de la configuration), puis départements par code, puis l'inconnu. */
    private function sortRows(array $rows): array
    {
        $overseasOrder = array_flip(array_map(fn (Territory $t) => $t->code, $this->territories->overseasTerritories()));

        uasort($rows, function (array $a, array $b) use ($overseasOrder) {
            $rank = fn (array $row) => match (true) {
                $row['code'] === null => [2, ''],
                isset($overseasOrder[$row['code']]) => [0, str_pad((string) $overseasOrder[$row['code']], 3, '0', STR_PAD_LEFT)],
                default => [1, $row['code']],
            };

            return $rank($a) <=> $rank($b);
        });

        return array_values($rows);
    }

    /** @return list<int> auteurs dont un abonnement Premium est en cours */
    private function activePremiumUserIds(): array
    {
        return ProSubscription::query()
            ->currentlyActive()
            ->where(fn ($started) => $started->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** Boosts en cours, avec leur type (pour repérer les anciennes formules « Première position garantie »). */
    private function activeBoosts(): Collection
    {
        return Ad::query()
            ->where('is_boosted', true)
            ->where('boost_end', '>', now())
            ->with('user:id,name')
            ->select([...self::AD_PLACE_COLUMNS, 'title', 'service_type', 'status', 'boost_type', 'boost_end'])
            ->orderBy('boost_end')
            ->limit(self::LIST_LIMIT)
            ->get()
            ->each(fn (Ad $ad) => $ad->setAttribute('pk_territory', $this->territories->forAd($ad)));
    }

    /** Comptes dont users.plan indique une formule payante sans abonnement actif dans pro_subscriptions. */
    private function planOnlySubscribers(): Collection
    {
        return User::query()
            ->whereNotNull('plan')
            ->whereRaw('LOWER(TRIM(plan)) NOT IN (?, ?)', ['', 'free'])
            ->whereDoesntHave('proSubscriptions', fn ($subscription) => $subscription->currentlyActive())
            ->select(['id', 'name', 'plan', 'subscription_end'])
            ->orderBy('id')
            ->limit(self::LIST_LIMIT)
            ->get();
    }

    /** Offres actives dont le titre commence par « Cherche » : probables demandes mal typées. */
    private function offersTitledAsRequests(): Collection
    {
        return $this->clientVisibleOffersQuery()
            ->whereRaw('LOWER(TRIM(title)) LIKE ?', ['cherche%'])
            ->with('user:id,name')
            ->select(['id', 'user_id', 'title', 'created_at'])
            ->orderByDesc('created_at')
            ->limit(self::LIST_LIMIT)
            ->get();
    }

    /** Auteurs d'offres actives, pour remplir FEED_HIDDEN_AUTHOR_IDS. */
    private function activeOfferAuthors(): Collection
    {
        $counts = $this->activeOffersQuery()
            ->select('user_id', DB::raw('COUNT(*) AS offers_count'))
            ->groupBy('user_id')
            ->orderByDesc('offers_count')
            ->orderBy('user_id')
            ->limit(self::LIST_LIMIT)
            ->toBase()
            ->get();

        $names = User::withTrashed()->whereIn('id', $counts->pluck('user_id')->all())->pluck('name', 'id');

        return $counts->map(fn ($row) => [
            'id' => (int) $row->user_id,
            'name' => $names[$row->user_id] ?? null,
            'offers_count' => (int) $row->offers_count,
        ]);
    }

    /**
     * Comptes sans lieu déclaré qui portent encore la position par défaut
     * (Mamoudzou) posée avant le marquage geo_source = default. Ils sont
     * traités comme territoire inconnu (TerritoryResolver::forUser).
     */
    private function defaultLocationUsersCount(): int
    {
        return User::query()
            ->whereBetween('latitude', [IpGeolocationService::DEFAULT_LATITUDE - 0.00005, IpGeolocationService::DEFAULT_LATITUDE + 0.00005])
            ->whereBetween('longitude', [IpGeolocationService::DEFAULT_LONGITUDE - 0.00005, IpGeolocationService::DEFAULT_LONGITUDE + 0.00005])
            ->where(fn ($q) => $q->whereNull('geo_source')->orWhereRaw("TRIM(geo_source) = ''"))
            ->where(fn ($q) => $q->whereNull('postal_code')->orWhere('postal_code', ''))
            ->where(fn ($q) => $q->whereNull('country')->orWhere('country', ''))
            ->where(fn ($q) => $q->whereNull('city')->orWhere('city', ''))
            ->count();
    }
}

<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\ProfileView;
use App\Models\ProInvoice;
use App\Models\ProQuote;
use App\Models\Review;
use App\Models\SavedAd;
use App\Models\ServiceOrder;
use App\Models\ServiceProposal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Rapport d'activité mensuel d'un prestataire (avantage Premium).
 *
 * Règle de fond : chaque chiffre est compté dans une table datée. Une valeur
 * `null` signifie « non mesuré sur cette période » et n'est jamais affichée
 * comme un zéro. Les vues d'annonce ne figurent pas ici : elles ne sont pas
 * comptées par la plateforme.
 */
class ProviderActivityReport
{
    /** Nombre de mois terminés consultables, en plus du mois en cours. */
    public const HISTORY_MONTHS = 12;

    private bool $viewsStartLoaded = false;

    private ?CarbonImmutable $viewsStart = null;

    /**
     * Mois consultables, du plus récent au plus ancien : le mois en cours puis
     * les mois terminés, sans remonter avant l'inscription du prestataire.
     *
     * @return array<int, CarbonImmutable>
     */
    public function months(User $provider): array
    {
        $current = CarbonImmutable::now()->startOfMonth();
        $first = CarbonImmutable::parse($provider->created_at ?? $current)->startOfMonth();
        $months = [];

        for ($i = 0; $i <= self::HISTORY_MONTHS; $i++) {
            $month = $current->subMonthsNoOverflow($i);
            if ($month->lessThan($first)) {
                break;
            }
            $months[] = $month;
        }

        return $months ?: [$current];
    }

    /** Dernier mois terminé : celui du rapport envoyé par e-mail. */
    public function lastCompletedMonth(): CarbonImmutable
    {
        return CarbonImmutable::now()->startOfMonth()->subMonthNoOverflow();
    }

    /**
     * @return array{month: CarbonImmutable, label: string, previous_label: string, in_progress: bool,
     *               has_activity: bool, sections: array<int, array{title: string, metrics: array<int, array<string, mixed>>}>}
     */
    public function forMonth(User $provider, CarbonInterface $month): array
    {
        $start = CarbonImmutable::parse($month)->startOfMonth();
        $previousStart = $start->subMonthNoOverflow();
        $current = $this->figures($provider, $start);
        $previous = $this->figures($provider, $previousStart);

        $metric = function (string $key, string $label, string $hint, string $unit = '') use ($current, $previous): array {
            $value = $current[$key];
            $before = $previous[$key];

            return [
                'key' => $key, 'label' => $label, 'hint' => $hint, 'unit' => $unit,
                'value' => $value, 'previous' => $before,
                'delta' => $value !== null && $before !== null ? round($value - $before, 2) : null,
            ];
        };

        $sections = [
            ['title' => 'Votre visibilité', 'metrics' => [
                $metric('profile_views', 'Vues de votre profil', 'Une vue par visiteur et par jour ; les robots et vos propres visites ne comptent pas.'),
                $metric('favorites', 'Ajouts en favoris', 'Membres qui ont enregistré une de vos annonces.'),
                $metric('contacts', 'Nouveaux contacts', 'Conversations ouvertes par un membre qui vous a écrit en premier.'),
            ]],
            ['title' => 'Vos missions sur Prokejem', 'metrics' => [
                $metric('proposals_sent', 'Propositions envoyées', 'Propositions faites en réponse à des demandes de clients.'),
                $metric('proposals_accepted', 'Propositions acceptées', 'Propositions acceptées par un client pendant le mois.'),
                $metric('orders_received', 'Commandes reçues', 'Prestations réservées et payées en ligne auprès de vous.'),
                $metric('orders_completed', 'Missions terminées', 'Commandes terminées, paiement libéré.'),
                $metric('revenue', 'Montant versé', 'Somme qui vous a été versée pour les missions terminées, commission déduite.', '€'),
            ]],
            ['title' => 'Votre réputation', 'metrics' => array_values(array_filter([
                $metric('reviews', 'Avis reçus', 'Avis laissés par vos clients pendant le mois.'),
                $current['reviews'] > 0
                    ? $metric('rating', 'Note moyenne du mois', 'Moyenne des avis reçus pendant le mois.', '/ 5')
                    : null,
            ]))],
        ];

        if ($provider->isProfessionnel()) {
            $sections[] = ['title' => 'Vos devis et factures', 'metrics' => [
                $metric('quotes_sent', 'Devis envoyés', 'Devis envoyés à vos clients depuis l’Espace Pro.'),
                $metric('quotes_accepted', 'Devis acceptés', 'Devis marqués comme acceptés pendant le mois.'),
                $metric('invoices_paid', 'Factures encaissées', 'Total des factures marquées comme payées pendant le mois.', '€'),
            ]];
        }

        return [
            'month' => $start,
            'label' => $this->label($start),
            'previous_label' => $this->label($previousStart, withYear: $previousStart->year !== $start->year),
            'in_progress' => $start->isSameMonth(CarbonImmutable::now()),
            'has_activity' => collect($current)->except('rating')->contains(fn ($value) => $value !== null && $value > 0),
            'sections' => $sections,
        ];
    }

    public function label(CarbonInterface $month, bool $withYear = true): string
    {
        return $month->locale('fr')->translatedFormat($withYear ? 'F Y' : 'F');
    }

    /** @return array<string, int|float|null> */
    private function figures(User $provider, CarbonImmutable $start): array
    {
        $end = $start->endOfMonth();
        $between = [$start, $end];
        $id = $provider->id;

        $reviews = Review::where('reviewed_user_id', $id)->whereBetween('created_at', $between);
        $completed = ServiceOrder::where('seller_id', $id)->where('status', ServiceOrder::STATUS_COMPLETED)
            ->whereBetween('released_at', $between);
        $average = (clone $reviews)->avg('rating');

        return [
            'profile_views' => $this->profileViews($provider, $start, $end),
            'favorites' => SavedAd::whereIn('ad_id', Ad::select('id')->where('user_id', $id))
                ->where('user_id', '!=', $id)->whereBetween('created_at', $between)->count(),
            'contacts' => $this->contacts($id, $between),
            'proposals_sent' => ServiceProposal::where('provider_id', $id)->whereBetween('created_at', $between)->count(),
            'proposals_accepted' => ServiceProposal::where('provider_id', $id)
                ->where('status', ServiceProposal::STATUS_ACCEPTED)->whereBetween('responded_at', $between)->count(),
            'orders_received' => ServiceOrder::where('seller_id', $id)->whereBetween('created_at', $between)->count(),
            'orders_completed' => (clone $completed)->count(),
            'revenue' => round((float) (clone $completed)->sum('seller_amount'), 2),
            'reviews' => (clone $reviews)->count(),
            'rating' => $average !== null ? round((float) $average, 1) : null,
            'quotes_sent' => ProQuote::where('user_id', $id)->whereBetween('sent_at', $between)->count(),
            'quotes_accepted' => ProQuote::where('user_id', $id)->whereBetween('accepted_at', $between)->count(),
            'invoices_paid' => round((float) ProInvoice::where('user_id', $id)->where('status', 'paid')
                ->whereBetween('paid_at', $between)->sum('total'), 2),
        ];
    }

    /** `null` tant que le comptage dédoublonné des vues n'existait pas encore sur la période. */
    private function profileViews(User $provider, CarbonImmutable $start, CarbonImmutable $end): ?int
    {
        if (! $this->viewsStartLoaded) {
            $first = ProfileView::min('viewed_on');
            $this->viewsStart = $first ? CarbonImmutable::parse($first)->startOfDay() : null;
            $this->viewsStartLoaded = true;
        }

        if ($this->viewsStart === null || $this->viewsStart->greaterThan($end)) {
            return null;
        }

        return ProfileView::where('profile_user_id', $provider->id)
            ->whereBetween('viewed_on', [$start->toDateString(), $end->toDateString()])->count();
    }

    /** Conversations créées pendant la période dont le premier message vient de l'autre membre. */
    private function contacts(int $providerId, array $between): int
    {
        $conversations = DB::table('conversations')
            ->where(fn ($query) => $query->where('user1_id', $providerId)->orWhere('user2_id', $providerId))
            ->whereBetween('created_at', $between)->pluck('id');
        if ($conversations->isEmpty()) {
            return 0;
        }

        $firstMessages = DB::table('messages')->whereIn('conversation_id', $conversations)
            ->groupBy('conversation_id')->selectRaw('MIN(id) as first_id')->pluck('first_id');

        return DB::table('messages')->whereIn('id', $firstMessages)->where('sender_id', '!=', $providerId)->count();
    }
}

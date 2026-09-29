<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Une seule liste, paginée en base, pour les demandes et les commandes du client. */
class ActivityDashboardService
{
    public function __construct(private ClientActivityService $activity) {}

    public function data(User $user, Request $request): array
    {
        $demands = Ad::query()->where('user_id', $user->id)->where('service_type', 'demande')
            ->whereDoesntHave('serviceOrders', fn ($query) => $query->where('buyer_id', $user->id)
                ->whereIn('status', [...ClientActivityService::ACTIVE_ORDER_STATUSES, ServiceOrder::STATUS_COMPLETED, ServiceOrder::STATUS_REFUNDED]))
            ->selectRaw("id, 'request' as kind, created_at as sort_at, CASE
                WHEN status NOT IN ('active', 'pending') OR expires_at < ? THEN 'closed'
                WHEN status = 'active' AND EXISTS (SELECT 1 FROM service_proposals WHERE service_proposals.ad_id = ads.id AND service_proposals.status = 'pending') THEN 'action'
                ELSE 'waiting' END as stage", [now()]);
        $orders = ServiceOrder::query()->where('buyer_id', $user->id)
            ->selectRaw("id, 'order' as kind, updated_at as sort_at, CASE
                WHEN status IN ('awaiting_payment', 'disputed') THEN 'action'
                WHEN status = 'pending_acceptance' THEN 'waiting'
                WHEN status = 'funded' THEN 'ongoing' ELSE 'closed' END as stage");
        $entries = DB::query()->fromSub($demands->toBase()->unionAll($orders->toBase()), 'activity');
        $counts = array_replace(['action' => 0, 'waiting' => 0, 'ongoing' => 0, 'closed' => 0],
            (clone $entries)->selectRaw('stage, COUNT(*) as total')->groupBy('stage')->pluck('total', 'stage')->map(fn ($value) => (int) $value)->all());
        $filter = in_array($request->query('etat'), ['action', 'waiting', 'ongoing', 'closed'], true) ? $request->query('etat') : 'active';
        $focused = $request->integer('demande');
        $focusKind = 'request';
        $entry = $focused ? (clone $entries)->where('kind', 'request')->where('id', $focused)->first() : null;
        if ($focused && ! $entry) {
            $orderId = ServiceOrder::where('buyer_id', $user->id)->where('ad_id', $focused)->latest('updated_at')->value('id');
            $entry = $orderId ? (clone $entries)->where('kind', 'order')->where('id', $orderId)->first() : null;
            if ($entry) {
                $focused = $entry->id;
                $focusKind = 'order';
            }
        }
        // Un ancien lien de notification ouvre la bonne demande, même hors de la première page.
        if ($entry) {
            $filter = $entry->stage;
        } else {
            $focused = 0;
        }
        $filtered = (clone $entries)->when($filter === 'active', fn ($query) => $query->where('stage', '!=', 'closed'), fn ($query) => $query->where('stage', $filter));
        if ($focused) {
            $filtered->orderByRaw('CASE WHEN kind = ? AND id = ? THEN 0 ELSE 1 END', [$focusKind, $focused]);
        }
        $rows = $filtered->orderByRaw("CASE stage WHEN 'action' THEN 0 WHEN 'ongoing' THEN 1 WHEN 'waiting' THEN 2 ELSE 3 END")
            ->orderByDesc('sort_at')->orderBy('kind')->orderByDesc('id')
            ->paginate(10, ['*'], 'page', $focused ? 1 : null)->withPath(route('home'))->appends(['etat' => $filter]);
        $ads = Ad::where('user_id', $user->id)->whereIn('id', $rows->getCollection()->where('kind', 'request')->pluck('id'))
            ->withCount(['serviceProposals', 'serviceProposals as pending_proposals_count' => fn ($query) => $query->where('status', 'pending')])->get()->keyBy('id');
        $bookings = ServiceOrder::where('buyer_id', $user->id)->whereIn('id', $rows->getCollection()->where('kind', 'order')->pluck('id'))->with('ad')->get()->keyBy('id');
        $rows->through(function ($row) use ($ads, $bookings) {
            $model = $row->kind === 'request' ? $ads[$row->id] : $bookings[$row->id];
            $item = $row->kind === 'request' ? $this->activity->requestItem($model) : $this->activity->orderItem($model);
            if ($row->kind === 'request') {
                $item['action_url'] = $row->stage === 'action' ? route('proposals.compare', $model) : route('ads.show', $model);
                $item['action_label'] = $row->stage === 'action' ? 'Comparer les propositions' : 'Voir la demande';
                if ($row->stage === 'closed') {
                    $item['status'] = in_array($model->status, ['archived', 'inactive'], true) ? 'Demande archivée' : 'Demande clôturée';
                    $item['description'] = 'Cette demande ne figure plus parmi vos recherches en cours.';
                } elseif ($model->status === 'pending') {
                    $item['status'] = 'En cours de validation';
                    $item['description'] = 'Votre demande attend sa validation avant publication.';
                }
            }

            return $item + ['stage' => $row->stage, 'date' => $model->created_at,
                'editable' => $row->kind === 'request' && $row->stage !== 'closed' ? route('ads.edit', $model) : null];
        });

        return ['activityItems' => $rows, 'activityCounts' => $counts, 'activityFilter' => $filter,
            'canProvide' => $user->isProfessionnel() || $user->isServiceProvider(), 'pkRole' => 'client'];
    }

    public function account(User $user): array
    {
        $plan = strtolower(trim((string) $user->plan));
        $subscription = $user->proSubscription()->first();

        return [
            'accountPlan' => $plan === '' || $plan === 'free' ? 'Gratuit' : ucfirst($plan),
            'subscription' => $subscription,
            // L'urgence d'un besoin n'est pas un achat. Les promotions sont présentées sans présumer leur mode de financement.
            'promotions' => $user->ads()->where('status', 'active')->where(fn ($query) => $query
                ->where(fn ($boost) => $boost->where('is_boosted', true)->where('boost_end', '>', now()))
                ->orWhere(fn ($urgent) => $urgent->where('is_urgent', true)->where('urgent_until', '>', now())))
                ->latest()->get(),
        ];
    }
}

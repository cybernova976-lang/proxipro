<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\ServiceProposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ClientActivityService
{
    public const ACTIVE_ORDER_STATUSES = [
        ServiceOrder::STATUS_PENDING_ACCEPTANCE, ServiceOrder::STATUS_AWAITING_PAYMENT,
        ServiceOrder::STATUS_FUNDED, ServiceOrder::STATUS_DISPUTED,
    ];

    public function __construct(private AdLifecycleService $adLifecycle) {}

    public function requestsQuery(User $user): Builder
    {
        return Ad::query()->where('user_id', $user->id)->marketplaceActive()
            ->where('service_type', 'demande')
            ->whereDoesntHave('serviceOrders', fn ($orders) => $orders
                ->where('buyer_id', $user->id)
                ->whereIn('status', [...self::ACTIVE_ORDER_STATUSES, ServiceOrder::STATUS_COMPLETED, ServiceOrder::STATUS_REFUNDED]))
            ->withCount([
                'serviceProposals',
                'serviceProposals as pending_proposals_count' => fn ($proposals) => $proposals->where('status', ServiceProposal::STATUS_PENDING),
            ])
            ->orderByRaw('CASE WHEN (SELECT COUNT(*) FROM service_proposals WHERE service_proposals.ad_id = ads.id AND status = ?) > 0 THEN 0 ELSE 1 END', [ServiceProposal::STATUS_PENDING])
            ->orderByDesc('pending_proposals_count')->latest('created_at')->orderByDesc('id');
    }

    public function ordersQuery(User $user): Builder
    {
        // Les commandes restent suivies meme si l'annonce a expire ou a ete archivee.
        return ServiceOrder::where('buyer_id', $user->id)
            ->whereIn('status', self::ACTIVE_ORDER_STATUSES)->with('ad')
            ->orderByRaw('CASE WHEN status = ? THEN 0 WHEN status = ? THEN 1 WHEN status = ? THEN 2 ELSE 3 END', [
                ServiceOrder::STATUS_DISPUTED, ServiceOrder::STATUS_AWAITING_PAYMENT, ServiceOrder::STATUS_PENDING_ACCEPTANCE,
            ])->latest('updated_at')->orderByDesc('id');
    }

    public function summary(User $user): array
    {
        $requestsQuery = $this->requestsQuery($user);
        $ordersQuery = $this->ordersQuery($user);
        $requestCount = (clone $requestsQuery)->count();
        $orderCount = (clone $ordersQuery)->count();
        // Quatre candidats de chaque source suffisent pour les quatre premieres places.
        $requests = $requestsQuery->take(4)->get();
        $orders = $ordersQuery->take(4)->get();
        $items = $requests->map(fn ($ad) => $this->requestItem($ad))
            ->concat($orders->map(fn ($order) => $this->orderItem($order)))
            ->sort(function ($a, $b) {
                return ($a['priority'] <=> $b['priority'])
                    ?: ($b['proposal_count'] <=> $a['proposal_count'])
                    ?: ($b['sort_at'] <=> $a['sort_at'])
                    ?: ($b['sort_id'] <=> $a['sort_id']);
            })->take(4)->values();

        $total = $requestCount + $orderCount;
        $revision = hash('sha256', json_encode([$total, $items->map(fn ($item) => [
            $item['key'], $item['title'], $item['status'], $item['action_url'], $item['action_label'],
        ])->all()], JSON_UNESCAPED_UNICODE));

        return [
            'total' => $total, 'request_count' => $requestCount, 'order_count' => $orderCount,
            'primary' => $items->first(), 'others' => $items->skip(1)->values(), 'revision' => $revision,
            'first_request' => $requests->first(), 'first_order' => $orders->first(),
        ];
    }

    public function requestItem(Ad $ad): array
    {
        $count = (int) $ad->pending_proposals_count;

        return [
            'key' => 'request-'.$ad->id, 'kind' => 'request', 'title' => $ad->title,
            'status' => $count ? $count.' proposition'.($count > 1 ? 's' : '').' à examiner' : 'En attente de propositions',
            'tone' => $count ? 'action' : 'waiting',
            'description' => $count ? 'Comparez les profils, les prix et les délais.'
                : 'Votre demande est publiée. Vous serez prévenu à la réception d’une proposition.',
            'action_label' => $count ? 'Comparer les propositions' : 'Suivre ma demande',
            'short_action' => $count ? 'Comparer' : 'Suivre',
            'action_url' => $count ? route('proposals.compare', $ad)
                : route('home', ['demande' => $ad->id]).'#request-'.$ad->id,
            'priority' => $count ? 2 : 4, 'proposal_count' => $count,
            'sort_at' => $ad->created_at?->timestamp ?? 0, 'sort_id' => $ad->id,
        ];
    }

    public function orderItem(ServiceOrder $order): array
    {
        [$status, $description, $shortAction, $priority, $tone] = match ($order->status) {
            ServiceOrder::STATUS_COMPLETED => ['Mission terminée', 'La prestation est terminée et les fonds ont été libérés.', 'Voir', 7, 'complete'],
            ServiceOrder::STATUS_REFUNDED => ['Commande remboursée', 'Le remboursement de cette commande a été enregistré.', 'Voir', 7, 'muted'],
            ServiceOrder::STATUS_REFUSED => ['Commande refusée', 'Le prestataire n’a pas accepté cette commande.', 'Voir', 7, 'muted'],
            ServiceOrder::STATUS_DISPUTED => ['Litige en cours', 'Un litige est en cours. Retrouvez son suivi et les échanges depuis votre commande.', 'Consulter', 0, 'attention'],
            ServiceOrder::STATUS_AWAITING_PAYMENT => ['En attente de paiement', 'Votre proposition est acceptée. Consultez la commande pour préparer le paiement sécurisé.', 'Voir la commande', 1, 'action'],
            ServiceOrder::STATUS_PENDING_ACCEPTANCE => ['En attente du prestataire', 'Le prestataire doit encore confirmer votre commande. Retrouvez son suivi et les échanges.', 'Suivre', 5, 'waiting'],
            default => ['Mission en cours', 'Retrouvez les étapes de votre mission et validez sa réalisation une fois la prestation terminée.', 'Suivre', 6, 'active'],
        };

        return [
            'key' => 'order-'.$order->id, 'kind' => 'order',
            'title' => $order->ad?->title ?: ($order->metadata['ad_title'] ?? 'Votre prestation en cours'),
            'status' => $status, 'description' => $description, 'tone' => $tone,
            'action_label' => 'Voir ma commande', 'short_action' => $shortAction,
            'action_url' => route('service-orders.index').'#order-'.$order->id,
            'priority' => $priority, 'proposal_count' => 0, 'sort_at' => $order->updated_at?->timestamp ?? 0, 'sort_id' => $order->id,
        ];
    }
}

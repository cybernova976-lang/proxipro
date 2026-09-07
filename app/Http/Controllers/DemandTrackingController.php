<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\ServiceOrder;
use App\Models\ServiceProposal;
use App\Services\AdLifecycleService;
use Illuminate\Support\Facades\Auth;

class DemandTrackingController extends Controller
{
    public function __construct(private AdLifecycleService $adLifecycle) {}

    public function __invoke()
    {
        $demands = Ad::query()
            ->where('user_id', Auth::id())
            ->where('service_type', 'demande')
            ->with([
                'serviceProposals' => fn ($query) => $query
                    ->with(['provider', 'serviceOrder'])
                    ->latest(),
                'serviceOrders' => fn ($query) => $query
                    ->with('seller')
                    ->latest(),
            ])
            ->latest()
            ->paginate(10);

        $trackingItems = $demands->getCollection()
            ->map(fn (Ad $demand) => $this->trackingItem($demand));

        $demands->setCollection($trackingItems);

        $ownedDemands = Ad::query()
            ->where('user_id', Auth::id())
            ->where('service_type', 'demande');

        $activeOrderStatuses = [
            ServiceOrder::STATUS_PENDING_ACCEPTANCE,
            ServiceOrder::STATUS_AWAITING_PAYMENT,
            ServiceOrder::STATUS_FUNDED,
            ServiceOrder::STATUS_DISPUTED,
        ];

        $summary = [
            'total' => (clone $ownedDemands)->count(),
            'awaiting' => (clone $ownedDemands)
                ->where('status', 'active')
                ->whereDoesntHave('serviceOrders')
                ->whereDoesntHave('serviceProposals', fn ($query) => $query->where('status', ServiceProposal::STATUS_PENDING))
                ->count(),
            'responses' => (clone $ownedDemands)
                ->whereDoesntHave('serviceOrders')
                ->whereHas('serviceProposals', fn ($query) => $query->where('status', ServiceProposal::STATUS_PENDING))
                ->count(),
            'active' => (clone $ownedDemands)
                ->whereHas('serviceOrders', fn ($query) => $query->whereIn('status', $activeOrderStatuses))
                ->count(),
        ];

        return view('demands.tracking', compact('demands', 'summary'));
    }

    private function trackingItem(Ad $demand): array
    {
        $proposals = $demand->serviceProposals;
        $pendingProposals = $proposals->where('status', ServiceProposal::STATUS_PENDING);
        $order = $demand->serviceOrders->first();

        $presentation = $order
            ? $this->orderPresentation($order)
            : $this->demandPresentation($demand, $pendingProposals->count());

        return array_merge($presentation, [
            'demand' => $demand,
            'order' => $order,
            'proposal_count' => $proposals->count(),
            'pending_proposal_count' => $pendingProposals->count(),
            'steps' => [
                1 => 'Publiée',
                2 => 'Propositions',
                3 => 'Prestataire choisi',
                4 => 'Mission en cours',
                5 => 'Terminée',
            ],
        ]);
    }

    private function demandPresentation(Ad $demand, int $pendingProposalCount): array
    {
        $expired = $demand->status === 'expired'
            || ($demand->expires_at && $demand->expires_at->isPast());

        if ($demand->status === 'archived' || $expired) {
            return [
                'key' => 'closed',
                'step' => 1,
                'tone' => 'muted',
                'status' => $expired ? 'Demande expirée' : 'Demande archivée',
                'explanation' => 'Cette demande n’est plus visible dans le marché.',
                'action_label' => 'Examiner la demande',
                'action_url' => route('ads.show', $demand),
            ];
        }

        if ($pendingProposalCount > 0) {
            return [
                'key' => 'proposals',
                'step' => 2,
                'tone' => 'action',
                'status' => $pendingProposalCount.' proposition'.($pendingProposalCount > 1 ? 's' : '').' à comparer',
                'explanation' => 'Comparez les prix, créneaux, profils et avis avant de choisir.',
                'action_label' => 'Comparer les propositions',
                'action_url' => route('proposals.compare', $demand),
            ];
        }

        if ($this->adLifecycle->needsFirstResponseAttention($demand, 0)) {
            return [
                'key' => 'attention',
                'step' => 1,
                'tone' => 'attention',
                'status' => 'Toujours aucune proposition',
                'explanation' => 'Ajoutez une précision, une photo ou un créneau plus souple pour faciliter une première réponse.',
                'action_label' => 'Améliorer ma demande',
                'action_url' => route('ads.edit', $demand),
            ];
        }

        return [
            'key' => 'published',
            'step' => 1,
            'tone' => 'waiting',
            'status' => 'Recherche de prestataires en cours',
            'explanation' => 'Votre demande est publiée. Vous serez prévenu dès la première proposition.',
            'action_label' => 'Voir la demande',
            'action_url' => route('ads.show', $demand),
        ];
    }

    private function orderPresentation(ServiceOrder $order): array
    {
        return match ($order->status) {
            ServiceOrder::STATUS_AWAITING_PAYMENT => [
                'key' => 'selected',
                'step' => 3,
                'tone' => 'action',
                'status' => 'Prestataire choisi · paiement à effectuer',
                'explanation' => 'La proposition est acceptée. Finalisez le paiement sécurisé pour confirmer la mission.',
                'action_label' => 'Finaliser le paiement',
                'action_url' => route('service-orders.index').'#order-'.$order->id,
            ],
            ServiceOrder::STATUS_FUNDED => [
                'key' => 'funded',
                'step' => 4,
                'tone' => 'active',
                'status' => 'Mission en cours · fonds protégés',
                'explanation' => 'Le paiement est bloqué. Libérez les fonds uniquement après une prestation terminée.',
                'action_label' => 'Suivre la mission',
                'action_url' => route('service-orders.index').'#order-'.$order->id,
            ],
            ServiceOrder::STATUS_COMPLETED => [
                'key' => 'completed',
                'step' => 5,
                'tone' => 'complete',
                'status' => 'Mission terminée',
                'explanation' => 'La prestation est terminée et les fonds ont été libérés.',
                'action_label' => 'Voir le récapitulatif',
                'action_url' => route('service-orders.index').'#order-'.$order->id,
            ],
            ServiceOrder::STATUS_DISPUTED => [
                'key' => 'disputed',
                'step' => 4,
                'tone' => 'attention',
                'status' => 'Litige en cours',
                'explanation' => 'Les fonds restent bloqués pendant l’examen du litige.',
                'action_label' => 'Consulter le litige',
                'action_url' => route('service-orders.index').'#order-'.$order->id,
            ],
            ServiceOrder::STATUS_REFUNDED => [
                'key' => 'closed',
                'step' => 5,
                'tone' => 'muted',
                'status' => 'Commande remboursée',
                'explanation' => 'Le remboursement de cette commande a été enregistré.',
                'action_label' => 'Voir le récapitulatif',
                'action_url' => route('service-orders.index').'#order-'.$order->id,
            ],
            ServiceOrder::STATUS_PENDING_ACCEPTANCE => [
                'key' => 'selected',
                'step' => 3,
                'tone' => 'waiting',
                'status' => 'Confirmation du prestataire en attente',
                'explanation' => 'Le prestataire doit encore accepter la commande.',
                'action_label' => 'Voir la commande',
                'action_url' => route('service-orders.index').'#order-'.$order->id,
            ],
            default => [
                'key' => 'closed',
                'step' => 3,
                'tone' => 'muted',
                'status' => $order->status_label,
                'explanation' => 'Consultez le détail de la commande pour connaître son état.',
                'action_label' => 'Voir la commande',
                'action_url' => route('service-orders.index').'#order-'.$order->id,
            ],
        };
    }
}

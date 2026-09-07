<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\IdentityVerification;
use App\Models\Report;
use App\Models\ServiceOrder;
use App\Models\StripeWebhookEvent;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class OperationsQueueController extends Controller
{
    public function __invoke(): View
    {
        $items = collect()
            ->concat($this->paymentFailures())
            ->concat($this->disputes())
            ->concat($this->pendingReports())
            ->concat($this->pendingVerifications())
            ->concat($this->unansweredDemands())
            ->sortBy([
                ['priority', 'desc'],
                ['occurred_at', 'asc'],
            ])
            ->values();

        $counts = $items
            ->countBy('type')
            ->merge(['total' => $items->count()]);

        return view('admin.operations', compact('items', 'counts'));
    }

    private function paymentFailures(): Collection
    {
        return StripeWebhookEvent::query()
            ->where('status', StripeWebhookEvent::STATUS_FAILED)
            ->oldest()
            ->limit(30)
            ->get()
            ->map(fn (StripeWebhookEvent $event) => [
                'type' => 'payment',
                'priority' => 100,
                'tone' => 'danger',
                'icon' => 'fa-credit-card',
                'label' => 'Incident de paiement',
                'title' => $event->event_type,
                'context' => 'Événement Stripe '.$event->event_id.' en échec après '.$event->attempts.' tentative'.($event->attempts > 1 ? 's' : '').'.',
                'next_action' => 'Contrôler l’erreur enregistrée avant toute relance.',
                'owner' => 'Paiements',
                'deadline' => 'À traiter immédiatement',
                'occurred_at' => $event->created_at,
                'url' => route('admin.payments.index').'#event-'.$event->id,
            ]);
    }

    private function disputes(): Collection
    {
        return ServiceOrder::query()
            ->where('status', ServiceOrder::STATUS_DISPUTED)
            ->with(['ad:id,title', 'buyer:id,name', 'seller:id,name'])
            ->oldest('disputed_at')
            ->limit(30)
            ->get()
            ->map(fn (ServiceOrder $order) => [
                'type' => 'dispute',
                'priority' => 90,
                'tone' => 'danger',
                'icon' => 'fa-scale-balanced',
                'label' => 'Litige',
                'title' => $order->order_number.' · '.($order->ad?->title ?: 'Prestation'),
                'context' => trim(($order->buyer?->name ?: 'Client supprimé').' / '.($order->seller?->name ?: 'Prestataire supprimé')),
                'next_action' => 'Examiner les éléments des deux parties avant décision.',
                'owner' => 'Médiation',
                'deadline' => 'Décision interne sous 48 h',
                'occurred_at' => $order->disputed_at ?: $order->updated_at,
                'url' => route('admin.service-orders.index', ['status' => ServiceOrder::STATUS_DISPUTED]).'#order-'.$order->id,
            ]);
    }

    private function pendingReports(): Collection
    {
        return Report::query()
            ->where('status', 'pending')
            ->with(['reporter:id,name', 'ad:id,title'])
            ->oldest()
            ->limit(30)
            ->get()
            ->map(fn (Report $report) => [
                'type' => 'report',
                'priority' => 80,
                'tone' => 'warning',
                'icon' => 'fa-flag',
                'label' => 'Signalement',
                'title' => $report->ad?->title ?: 'Annonce supprimée',
                'context' => 'Motif : '.str_replace('_', ' ', $report->reason).'. Signalé par '.($report->reporter?->name ?: 'Compte supprimé').'.',
                'next_action' => 'Vérifier l’annonce et qualifier le signalement.',
                'owner' => 'Modération',
                'deadline' => 'Première lecture sous 24 h',
                'occurred_at' => $report->created_at,
                'url' => route('admin.reports.show', $report->id),
            ]);
    }

    private function pendingVerifications(): Collection
    {
        return IdentityVerification::query()
            ->where('status', 'pending')
            ->with('user:id,name')
            ->oldest('submitted_at')
            ->limit(30)
            ->get()
            ->map(fn (IdentityVerification $verification) => [
                'type' => 'verification',
                'priority' => 70,
                'tone' => 'info',
                'icon' => 'fa-shield-halved',
                'label' => 'Vérification d’identité',
                'title' => $verification->user?->name ?: 'Compte supprimé',
                'context' => 'Document : '.str_replace('_', ' ', $verification->document_type).'.',
                'next_action' => 'Contrôler les documents et consigner la décision.',
                'owner' => 'Vérification',
                'deadline' => 'Réponse interne sous 48 h',
                'occurred_at' => $verification->submitted_at ?: $verification->created_at,
                'url' => route('admin.verifications.show', $verification->id),
            ]);
    }

    private function unansweredDemands(): Collection
    {
        return Ad::query()
            ->where('service_type', 'demande')
            ->where('status', 'active')
            ->where('created_at', '<=', now()->subHours(2))
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereDoesntHave('serviceProposals')
            ->with('user:id,name')
            ->oldest()
            ->limit(30)
            ->get()
            ->map(fn (Ad $demand) => [
                'type' => 'unanswered',
                'priority' => 50,
                'tone' => 'attention',
                'icon' => 'fa-bullhorn',
                'label' => 'Demande sans réponse',
                'title' => $demand->title,
                'context' => ($demand->category ?: 'Service non précisé').' · '.($demand->city ?: $demand->location ?: 'Lieu non précisé').' · '.($demand->user?->name ?: 'Compte supprimé'),
                'next_action' => 'Examiner la couverture métier et la qualité de la demande. Ne pas relancer au nom du client.',
                'owner' => 'Animation du marché',
                'deadline' => 'Diagnostic sous 24 h',
                'occurred_at' => $demand->created_at,
                'url' => route('admin.ads.show', $demand->id),
            ]);
    }
}

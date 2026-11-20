@php
    // État d'un abonnement Premium, avec le détail d'un paiement en retard quand il y en a un.
    $stateColor = ['active' => 'success', 'pending' => 'warning', 'cancelled' => 'secondary', 'expired' => 'danger'][$subscription->status] ?? 'secondary';
    $stateLate = $subscription->isPaymentLate();
    $stateDeadline = null;
    if ($stateLate && $subscription->grace_ends_at) {
        $stateDaysLeft = $subscription->graceDaysLeft();
        $stateDeadline = $subscription->inPaymentGrace()
            ? 'Avantages gardés jusqu’au '.$subscription->grace_ends_at->format('d/m/Y').' ('.($stateDaysLeft > 1 ? $stateDaysLeft.' jours restants' : 'dernier jour').')'
            : 'Délai écoulé le '.$subscription->grace_ends_at->format('d/m/Y').' : fin automatique en cours';
    }
@endphp
@if($stateLate)
    <span class="badge bg-danger">Paiement en retard</span>
    <div class="small text-muted mt-1">Refusé le {{ $subscription->payment_failed_at->format('d/m/Y') }}</div>
    @if($stateDeadline)
        <div class="small text-muted">{{ $stateDeadline }}</div>
    @endif
    <div class="small text-muted">Rappels envoyés : {{ (int) $subscription->payment_reminders_sent }} sur {{ count(\App\Models\ProSubscription::PAYMENT_REMINDER_DAYS) }}</div>
@else
    <span class="badge bg-{{ $stateColor }}">{{ \App\Support\StatusLabels::providerSubscription($subscription->status) }}</span>
    @if($subscription->status === 'active' && ! $subscription->auto_renew)
        <div class="small text-warning mt-1">Fin à l’échéance</div>
    @endif
@endif

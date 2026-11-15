@php
    // Libellés lisibles des types de transaction enregistrés par les services de paiement.
    $transactionTypeLabels = [
        'SUBSCRIPTION' => 'Abonnement Premium',
        'AD_BOOST' => 'Boost d’annonce',
        'AD_REFRESH' => 'Remontée d’annonce',
        'AD_URGENT' => 'Option Urgent',
        'IDENTITY_VERIFICATION' => 'Vérification de profil',
        'POINTS' => 'Achat de points (ancien système)',
        'DOCUMENT_CREDITS' => 'Crédits devis et factures',
        'SERVICE_ORDER_PAYMENT' => 'Paiement d’une commande',
        'SERVICE_ORDER_RELEASE' => 'Versement reçu pour une commande',
        'SERVICE_ORDER_REFUND' => 'Remboursement d’une commande',
    ];
    $transactionStatusLabels = ['completed' => 'Payé', 'pending' => 'En attente', 'failed' => 'Échoué', 'refunded' => 'Remboursé'];
    $subscriptionStatusLabels = ['active' => 'Actif', 'pending' => 'Suspendu ou en attente', 'cancelled' => 'Annulé', 'expired' => 'Expiré'];
    $subscriptionStatusColors = ['active' => 'success', 'pending' => 'warning', 'cancelled' => 'secondary', 'expired' => 'danger'];
@endphp
<div class="card border-0 shadow-sm mb-4" id="adminUserPurchases">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0"><i class="fas fa-receipt me-2 text-primary"></i>Abonnements et achats</h5>
    </div>
    <div class="card-body pt-0">
        <div class="row g-3 mb-4 text-center">
            <div class="col-6 col-md-3">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-bold fs-5" data-user-figure="premium">{{ $user->hasActiveProSubscription() ? 'Oui' : 'Non' }}</div>
                    <div class="small text-muted">Premium actif</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-bold fs-5" data-user-figure="paid">{{ number_format($userPaidTotal, 2, ',', ' ') }} €</div>
                    <div class="small text-muted">Total des paiements effectués</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-bold fs-5" data-user-figure="orders-placed">{{ $ordersPlacedCount }}</div>
                    <div class="small text-muted">Commandes passées (client)</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-bold fs-5" data-user-figure="orders-received">{{ $ordersReceivedCount }}</div>
                    <div class="small text-muted">Commandes reçues (prestataire)</div>
                </div>
            </div>
        </div>

        <h6 class="fw-semibold mb-2">Historique des abonnements Premium</h6>
        @if($subscriptionHistory->isEmpty())
            <p class="text-muted small">Ce compte n’a jamais eu d’abonnement Premium.</p>
        @else
            <div class="table-responsive mb-4">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Formule</th><th>État</th><th>Du</th><th>Au</th><th>Origine</th><th>Dernier paiement</th></tr></thead>
                    <tbody>
                        @foreach($subscriptionHistory as $subscription)
                            <tr>
                                <td>{{ $subscription->getPlanLabel() }}</td>
                                <td><span class="badge bg-{{ $subscriptionStatusColors[$subscription->status] ?? 'secondary' }}">{{ $subscriptionStatusLabels[$subscription->status] ?? ucfirst($subscription->status) }}</span></td>
                                <td class="text-nowrap">{{ optional($subscription->starts_at)->format('d/m/Y') ?: '—' }}</td>
                                <td class="text-nowrap">{{ optional($subscription->ends_at)->format('d/m/Y') ?: '—' }}</td>
                                <td>{{ $subscription->stripe_subscription_id ? 'Paiement Stripe' : 'Accordé par un administrateur' }}</td>
                                <td class="text-nowrap">{{ optional($subscription->last_payment_at)->format('d/m/Y') ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <h6 class="fw-semibold mb-2">Paiements et achats</h6>
        @if($userTransactions->isEmpty())
            <p class="text-muted small mb-0">Aucun paiement enregistré pour ce compte.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Achat</th><th>Détail</th><th class="text-end">Montant</th><th>État</th></tr></thead>
                    <tbody>
                        @foreach($userTransactions as $transaction)
                            <tr>
                                <td class="text-nowrap">{{ optional($transaction->created_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ $transactionTypeLabels[$transaction->type] ?? $transaction->type }}</td>
                                <td class="small text-muted">{{ $transaction->description ?: '—' }}</td>
                                <td class="text-end text-nowrap fw-semibold">{{ number_format((float) $transaction->amount, 2, ',', ' ') }} €</td>
                                <td><span class="badge bg-{{ $transaction->status === 'completed' ? 'success' : 'warning' }}">{{ $transactionStatusLabels[$transaction->status] ?? $transaction->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($userTransactionsCount > $userTransactions->count())
                <p class="small text-muted mt-2 mb-0">
                    Les {{ $userTransactions->count() }} derniers paiements sur {{ $userTransactionsCount }}.
                    <a href="{{ route('admin.payments.index') }}">Voir tous les paiements Stripe</a>
                </p>
            @endif
        @endif
    </div>
</div>

{{-- Transactions Partial --}}
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Historique des transactions</h1>
            <p class="text-muted mb-0">Tous vos paiements et mouvements de points</p>
        </div>
        <a href="{{ route('home.export-transactions-pdf') }}" class="btn btn-sm" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px;">
            <i class="fas fa-file-pdf"></i>Exporter PDF
        </a>
    </div>

    @if($transactions->isEmpty() && $pointTransactions->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-receipt fa-3x text-muted mb-3 opacity-50"></i>
                <h5 class="fw-bold text-muted">Aucune transaction pour le moment</h5>
                <p class="text-muted">Vos achats de points, paiements et transactions apparaîtront ici.</p>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Montant / Points</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $tx)
                        <tr>
                            <td class="text-muted small">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge {{ $tx->type === 'POINTS' ? 'bg-warning text-dark' : ($tx->type === 'SUBSCRIPTION' ? 'bg-purple' : ($tx->type === 'BOOST' ? 'bg-orange' : 'bg-secondary')) }}">
                                    @if($tx->type === 'POINTS')
                                        <i class="fas fa-coins me-1"></i>Points
                                    @elseif($tx->type === 'SUBSCRIPTION')
                                        <i class="fas fa-crown me-1"></i>Abonnement
                                    @elseif($tx->type === 'BOOST')
                                        <i class="fas fa-rocket me-1"></i>Boost
                                    @else
                                        <i class="fas fa-credit-card me-1"></i>{{ $tx->type ?? 'Paiement' }}
                                    @endif
                                </span>
                            </td>
                            <td>{{ Str::limit($tx->description ?? '-', 50) }}</td>
                            <td><strong>{{ number_format($tx->amount, 0, ',', ' ') }} €</strong></td>
                            <td>
                                <span class="badge {{ $tx->status == 'completed' ? 'bg-success' : ($tx->status == 'pending' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                    {{ $tx->status == 'completed' ? 'Complété' : ($tx->status == 'pending' ? 'En attente' : ucfirst($tx->status ?? 'Inconnu')) }}
                                </span>
                            </td>
                            <td>
                                @if($tx->status === 'completed')
                                    <a href="{{ route('purchase.invoice', ['type' => 'points', 'id' => $tx->id]) }}" class="btn btn-sm btn-outline-secondary" title="Facture">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach

                        @foreach($pointTransactions as $ptx)
                        <tr>
                            <td class="text-muted small">{{ $ptx->created_at->format('d/m/Y H:i') }}</td>
                            <td><span class="badge bg-warning text-dark"><i class="fas fa-coins me-1"></i>Points</span></td>
                            <td>{{ Str::limit($ptx->description ?? '-', 50) }}</td>
                            <td>
                                <span class="{{ $ptx->points >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                    {{ $ptx->points >= 0 ? '+' : '' }}{{ $ptx->points }} pts
                                </span>
                            </td>
                            <td><span class="badge bg-success">Complété</span></td>
                            <td></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@extends('admin.layouts.app')

@section('title', 'Paiements et webhooks')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h2 class="h4 fw-bold"><i class="fas fa-credit-card me-2 text-primary"></i>Paiements et webhooks Stripe</h2>
        <p class="text-muted mb-0">Contrôlez les confirmations reçues, repérez les échecs et relancez-les sans créditer deux fois.</p>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0">Journal des webhooks</h3>
        <span class="badge bg-danger">{{ $events->where('status', 'failed')->count() }} échec(s) sur cette page</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Reçu le</th><th>Événement</th><th>Objet</th><th>État</th><th>Tentatives</th><th>Détail</th><th></th></tr></thead>
            <tbody>
            @forelse($events as $event)
                @php($badge = match($event->status) {'processed' => 'success', 'ignored' => 'secondary', 'failed' => 'danger', default => 'warning'})
                <tr>
                    <td class="text-nowrap">{{ $event->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td><code>{{ $event->event_type }}</code><div class="small text-muted">{{ $event->event_id }}</div></td>
                    <td><code>{{ $event->object_id ?: '—' }}</code></td>
                    <td><span class="badge bg-{{ $badge }}">{{ $event->status }}</span></td>
                    <td>{{ $event->attempts }}</td>
                    <td style="max-width: 340px"><span class="small text-break">{{ $event->error_message ?: '—' }}</span></td>
                    <td>
                        @if($event->status === 'failed')
                            <form method="POST" action="{{ route('admin.payments.webhooks.retry', $event) }}" onsubmit="return confirm('Relancer ce webhook Stripe ?');">
                                @csrf
                                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-rotate me-1"></i>Relancer</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Aucun webhook Stripe enregistré pour le moment.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($events->hasPages())<div class="card-footer bg-white">{{ $events->links() }}</div>@endif
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white"><h3 class="h6 mb-0">Transactions comptabilisées</h3></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Date</th><th>Utilisateur</th><th>Type</th><th>Montant</th><th>Session / facture Stripe</th><th>État</th></tr></thead>
            <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td class="text-nowrap">{{ $transaction->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $transaction->user?->name ?: 'Compte supprimé' }}<div class="small text-muted">{{ $transaction->user?->email }}</div></td>
                    <td><code>{{ $transaction->type }}</code></td>
                    <td>{{ number_format((float) $transaction->amount, 2, ',', ' ') }} €</td>
                    <td><code class="small">{{ $transaction->stripe_session_id ?: '—' }}</code></td>
                    <td><span class="badge bg-{{ $transaction->status === 'completed' ? 'success' : 'warning' }}">{{ $transaction->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Aucune transaction enregistrée.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())<div class="card-footer bg-white">{{ $transactions->links() }}</div>@endif
</div>
@endsection

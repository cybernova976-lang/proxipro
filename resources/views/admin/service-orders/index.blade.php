@extends('admin.layouts.app')

@section('title', 'Commandes securisees')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col">
        <h2 class="h4 fw-bold mb-1">Commandes securisees</h2>
        <p class="text-muted mb-0">Controlez les litiges, les liberations de fonds et les remboursements Stripe.</p>
    </div>
    <div class="col-auto">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour admin
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Litiges ouverts</div><div class="fs-3 fw-bold text-warning">{{ $stats['disputed'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Fonds bloques</div><div class="fs-3 fw-bold text-primary">{{ $stats['funded'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Terminees</div><div class="fs-3 fw-bold text-success">{{ $stats['completed'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Remboursees</div><div class="fs-3 fw-bold text-info">{{ $stats['refunded'] }}</div></div></div></div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Recherche</label>
                <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Commande, annonce, acheteur, vendeur">
            </div>
            <div class="col-md-4">
                <label class="form-label">Statut</label>
                <select name="status" class="form-select">
                    <option value="">Tous</option>
                    @foreach(['pending_acceptance' => 'En attente', 'awaiting_payment' => 'Attente paiement', 'funded' => 'Fonds bloques', 'disputed' => 'Litige', 'completed' => 'Terminee', 'refunded' => 'Remboursee'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i>Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Commande</th>
                    <th>Annonce</th>
                    <th>Acheteur / vendeur</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Stripe Connect</th>
                    <th>Decision admin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $order->order_number }}</div>
                            <small class="text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $order->ad->title }}</div>
                            <small class="text-muted">{{ $order->ad->category }}</small>
                        </td>
                        <td>
                            <div><strong>A:</strong> {{ $order->buyer->name }}</div>
                            <div><strong>V:</strong> {{ $order->seller->name }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ number_format((float) $order->amount, 2, ',', ' ') }} €</div>
                            <small class="text-muted">Net vendeur {{ number_format((float) $order->seller_amount, 2, ',', ' ') }} €</small>
                        </td>
                        <td>
                            <span class="badge text-bg-light border">{{ $order->status_label }}</span><br>
                            <small class="text-muted">{{ $order->payment_status_label }}</small>
                            @if($order->dispute_reason)
                                <div class="small text-danger mt-2">Litige: {{ $order->dispute_reason }}</div>
                            @endif
                        </td>
                        <td>
                            @if($order->seller->stripe_connect_account_id)
                                <span class="badge {{ $order->seller->stripe_connect_payouts_enabled ? 'text-bg-success' : 'text-bg-warning' }}">
                                    {{ $order->seller->stripe_connect_payouts_enabled ? 'Actif' : 'En attente' }}
                                </span>
                            @else
                                <span class="badge text-bg-secondary">Non configure</span>
                            @endif
                            @if($order->stripe_transfer_id)
                                <div class="small text-muted mt-1">Transfer: {{ $order->stripe_transfer_id }}</div>
                            @endif
                            @if($order->stripe_refund_id)
                                <div class="small text-muted mt-1">Refund: {{ $order->stripe_refund_id }}</div>
                            @endif
                        </td>
                        <td style="min-width: 320px;">
                            @if($order->canAdminResolveDispute())
                                <form action="{{ route('admin.service-orders.release', $order) }}" method="POST" class="mb-2">
                                    @csrf
                                    <textarea name="resolution_note" class="form-control form-control-sm mb-2" rows="2" placeholder="Note optionnelle de libération"></textarea>
                                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-unlock-alt me-1"></i>Liberer les fonds</button>
                                </form>
                                <form action="{{ route('admin.service-orders.refund', $order) }}" method="POST">
                                    @csrf
                                    <textarea name="resolution_note" class="form-control form-control-sm mb-2" rows="2" placeholder="Motif obligatoire du remboursement" required></textarea>
                                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-undo-alt me-1"></i>Rembourser</button>
                                </form>
                            @else
                                <div class="small text-muted">{{ $order->admin_resolution_note ?: 'Aucune action admin requise pour cette commande.' }}</div>
                                @if($order->admin_resolution)
                                    <div class="mt-2"><span class="badge text-bg-info">{{ $order->admin_resolution }}</span></div>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">Aucune commande securisee correspondante.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
        <div class="card-footer bg-white">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
@extends('layouts.app')

@section('title', 'Commandes securisees - ProxiPro')

@push('styles')
<style>
    .service-orders-page { max-width: 1180px; margin: 0 auto; padding: 32px 20px 48px; }
    .service-orders-title { font-size: 1.9rem; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
    .service-orders-subtitle { color: #64748b; margin-bottom: 28px; }
    .service-orders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; }
    .service-order-card { background: white; border: 1px solid #e2e8f0; border-radius: 18px; padding: 20px; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05); }
    .service-order-top { display:flex; justify-content:space-between; gap:12px; margin-bottom:12px; }
    .service-order-number { font-size: 0.78rem; font-weight: 800; color: #0f766e; letter-spacing: 0.04em; text-transform: uppercase; }
    .service-order-title { margin: 4px 0 0; font-size: 1rem; font-weight: 800; color: #0f172a; }
    .service-order-status { display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px; background:#ecfeff; color:#155e75; font-size:0.75rem; font-weight:700; }
    .service-order-stats { display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; margin:14px 0; }
    .service-order-stat { background:#f8fafc; border-radius:12px; padding:12px; }
    .service-order-stat-label { font-size:0.72rem; color:#64748b; margin-bottom:4px; }
    .service-order-stat-value { font-size:0.92rem; font-weight:800; color:#0f172a; }
    .service-order-meta { font-size:0.84rem; color:#475569; margin-bottom:12px; }
    .service-order-empty { background:white; border:1px solid #e2e8f0; border-radius:20px; padding:72px 20px; text-align:center; }
    .service-order-actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:14px; }
    .service-order-note { margin-top: 12px; padding: 12px; border-radius: 12px; background:#fff7ed; color:#9a3412; font-size:0.84rem; }
    .service-order-reason { width:100%; border:1px solid #cbd5e1; border-radius:12px; padding:10px 12px; font-size:0.84rem; min-height:84px; }
</style>
@endpush

@section('content')
<div class="service-orders-page">
    <h1 class="service-orders-title"><i class="fas fa-shield-alt me-2" style="color:#0f766e;"></i>Commandes securisees</h1>
    <p class="service-orders-subtitle">Suivez vos commandes acheteur et les demandes recues en tant que vendeur.</p>

    @if(($needsStripeConnectOnboarding ?? false) && auth()->user()?->role !== 'admin')
        <div class="alert alert-warning d-flex justify-content-between align-items-center gap-3 flex-wrap" style="border-radius:16px;">
            <div>
                <strong>Activez Stripe Connect pour recevoir les fonds.</strong><br>
                Tant que le compte vendeur n'est pas finalise, les libérations de fonds restent bloquées.
            </div>
            <a href="{{ route('service-orders.connect.onboarding') }}" class="btn btn-warning text-dark">
                <i class="fas fa-plug me-1"></i>Configurer Stripe Connect
            </a>
        </div>
    @endif

    <div class="mb-4">
        <h2 class="h5 fw-bold text-dark mb-3">Mes commandes</h2>
        @if($ordersAsBuyer->count() > 0)
            <div class="service-orders-grid">
                @foreach($ordersAsBuyer as $order)
                    <div class="service-order-card">
                        <div class="service-order-top">
                            <div>
                                <div class="service-order-number">{{ $order->order_number }}</div>
                                <div class="service-order-title">{{ $order->ad->title }}</div>
                            </div>
                            <span class="service-order-status">{{ $order->status_label }}</span>
                        </div>
                        <div class="service-order-stats">
                            <div class="service-order-stat"><div class="service-order-stat-label">Montant</div><div class="service-order-stat-value">{{ number_format((float) $order->amount, 2, ',', ' ') }} €</div></div>
                            <div class="service-order-stat"><div class="service-order-stat-label">Commission</div><div class="service-order-stat-value">{{ number_format((float) $order->commission_amount, 2, ',', ' ') }} €</div></div>
                            <div class="service-order-stat"><div class="service-order-stat-label">Paiement</div><div class="service-order-stat-value">{{ $order->payment_status_label }}</div></div>
                        </div>
                        <div class="service-order-meta">Vendeur: <strong>{{ $order->seller->name }}</strong></div>
                        @if($order->seller->stripe_connect_account_id)
                            <div class="service-order-meta">Connect vendeur: <strong>{{ $order->seller->stripe_connect_payouts_enabled ? 'actif' : 'en attente' }}</strong></div>
                        @endif
                        @if($order->message)
                            <div class="service-order-meta">Message: {{ $order->message }}</div>
                        @endif
                        @if($order->canBuyerPay())
                            <div class="service-order-actions">
                                <form action="{{ route('service-orders.checkout', $order) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-credit-card me-1"></i>Payer avec Stripe</button>
                                </form>
                            </div>
                        @endif
                        @if($order->canBuyerRelease())
                            <div class="service-order-actions">
                                <form action="{{ route('service-orders.release', $order) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-unlock-alt me-1"></i>Liberer les fonds</button>
                                </form>
                            </div>
                            <form action="{{ route('service-orders.dispute', $order) }}" method="POST" class="mt-2">
                                @csrf
                                <textarea name="reason" class="service-order-reason" placeholder="Expliquez le probleme pour ouvrir un litige." required></textarea>
                                <div class="service-order-actions">
                                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-exclamation-triangle me-1"></i>Ouvrir un litige</button>
                                </div>
                            </form>
                        @endif
                        @if($order->released_at)
                            <div class="service-order-note">Fonds liberes le {{ $order->released_at->format('d/m/Y H:i') }}.</div>
                        @endif
                        @if($order->refunded_at)
                            <div class="service-order-note">Remboursement Stripe emis le {{ $order->refunded_at->format('d/m/Y H:i') }}.</div>
                        @endif
                        @if($order->dispute_reason)
                            <div class="service-order-note">Litige: {{ $order->dispute_reason }}</div>
                        @endif
                        @if($order->refused_reason)
                            <div class="service-order-note">Refus: {{ $order->refused_reason }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="service-order-empty"><i class="fas fa-receipt mb-3" style="font-size:2.4rem;color:#cbd5e1;"></i><h3 class="h5">Aucune commande envoyee</h3><p class="text-muted mb-0">Declenchez une commande securisee depuis une annonce.</p></div>
        @endif
    </div>

    <div>
        <h2 class="h5 fw-bold text-dark mb-3">Demandes recues</h2>
        @if($ordersAsSeller->count() > 0)
            <div class="service-orders-grid">
                @foreach($ordersAsSeller as $order)
                    <div class="service-order-card">
                        <div class="service-order-top">
                            <div>
                                <div class="service-order-number">{{ $order->order_number }}</div>
                                <div class="service-order-title">{{ $order->ad->title }}</div>
                            </div>
                            <span class="service-order-status">{{ $order->status_label }}</span>
                        </div>
                        <div class="service-order-stats">
                            <div class="service-order-stat"><div class="service-order-stat-label">Montant</div><div class="service-order-stat-value">{{ number_format((float) $order->amount, 2, ',', ' ') }} €</div></div>
                            <div class="service-order-stat"><div class="service-order-stat-label">Net vendeur</div><div class="service-order-stat-value">{{ number_format((float) $order->seller_amount, 2, ',', ' ') }} €</div></div>
                            <div class="service-order-stat"><div class="service-order-stat-label">Paiement</div><div class="service-order-stat-value">{{ $order->payment_status_label }}</div></div>
                        </div>
                        <div class="service-order-meta">Acheteur: <strong>{{ $order->buyer->name }}</strong></div>
                        <div class="service-order-meta">Connect vendeur: <strong>{{ $order->seller->stripe_connect_payouts_enabled ? 'actif' : 'en attente' }}</strong></div>
                        @if($order->scheduled_for)
                            <div class="service-order-meta">Souhaite demarrer le {{ $order->scheduled_for->format('d/m/Y') }}</div>
                        @endif
                        @if($order->message)
                            <div class="service-order-meta">Message: {{ $order->message }}</div>
                        @endif
                        @if($order->canSellerAccept())
                            <div class="service-order-actions">
                                <form action="{{ route('service-orders.accept', $order) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check me-1"></i>Accepter</button>
                                </form>
                            </div>
                            <form action="{{ route('service-orders.refuse', $order) }}" method="POST" class="mt-2">
                                @csrf
                                <textarea name="reason" class="service-order-reason" placeholder="Motif de refus optionnel"></textarea>
                                <div class="service-order-actions">
                                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-times me-1"></i>Refuser</button>
                                </div>
                            </form>
                        @endif
                        @if($order->paid_at)
                            <div class="service-order-note">Paiement Stripe confirme le {{ $order->paid_at->format('d/m/Y H:i') }}. Les fonds sont bloques jusqu'a liberation.</div>
                        @endif
                        @if($order->refunded_at)
                            <div class="service-order-note">Remboursement Stripe emis le {{ $order->refunded_at->format('d/m/Y H:i') }}.</div>
                        @endif
                        @if($order->dispute_reason)
                            <div class="service-order-note">Litige: {{ $order->dispute_reason }}</div>
                        @endif
                        @if($order->admin_resolution_note)
                            <div class="service-order-note">Decision admin: {{ $order->admin_resolution_note }}</div>
                        @endif
                        @if($order->refused_reason)
                            <div class="service-order-note">Refus: {{ $order->refused_reason }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="service-order-empty"><i class="fas fa-inbox mb-3" style="font-size:2.4rem;color:#cbd5e1;"></i><h3 class="h5">Aucune demande recue</h3><p class="text-muted mb-0">Les commandes securisees recues apparaîtront ici.</p></div>
        @endif
    </div>
</div>
@endsection
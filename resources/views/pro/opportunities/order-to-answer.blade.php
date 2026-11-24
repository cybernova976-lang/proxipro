{{-- Commande réservée par un client : le prestataire l'accepte ici, ou la refuse depuis ses commandes (motif demandé). --}}
<article class="pipeline-card pipeline-card--to-answer" data-order-id="{{ $order->id }}" data-order-to-answer>
    <div class="pipeline-card-eyebrow">
        <span class="pro-status pro-status-warning"><i class="fas fa-hourglass-half" aria-hidden="true"></i> {{ $order->status_label }}</span>
        <span>{{ $order->order_number }}</span>
    </div>
    <h3>{{ $order->ad?->title ?? 'Commande' }}</h3>
    <div class="pipeline-client">
        <i class="fas fa-circle-user" aria-hidden="true"></i>
        <span>{{ $order->buyer?->name ?? 'Client' }}</span>
    </div>
    <dl class="pipeline-facts">
        <div class="pipeline-fact"><dt>Montant</dt><dd>{{ \App\Support\Money::eur($order->amount) }}</dd></div>
        <div class="pipeline-fact"><dt>Date</dt><dd>{{ $order->scheduled_for?->format('d/m/Y') ?? 'À convenir' }}</dd></div>
    </dl>
    <div class="pipeline-card-actions">
        @if($order->canSellerAccept())
            <form method="POST" action="{{ route('service-orders.accept', $order) }}">
                @csrf
                <button type="submit" class="pipeline-card-action"><i class="fas fa-check" aria-hidden="true"></i> Accepter</button>
            </form>
            <a href="{{ route('service-orders.index') }}" class="pipeline-card-action pipeline-card-action--muted">Refuser</a>
        @else
            <a href="{{ route('service-orders.index') }}" class="pipeline-card-action">Répondre <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        @endif
    </div>
</article>

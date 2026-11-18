@php
    // Carte d'une commande vue par le prestataire (celui qui réalise et qui est payé).
    $pkStatus = \App\Models\ServiceOrder::class;
    $pkBuyerName = $order->buyer?->name ?? 'Client';
    $pkPayable = (bool) auth()->user()->stripe_connect_payouts_enabled;
    $pkMoney = fn ($value) => number_format((float) $value, 2, ',', ' ').' €';
    $pkMisdirected = $order->isReservationOnDemand();
    // À défaire tant qu'elle n'est pas payée : à refuser, ou à annuler si elle a déjà été acceptée.
    $pkAccepted = $order->status === $pkStatus::STATUS_AWAITING_PAYMENT;
    $pkToUndo = $pkMisdirected && in_array($order->status, [$pkStatus::STATUS_PENDING_ACCEPTANCE, $pkStatus::STATUS_AWAITING_PAYMENT], true);
    // Commande née d'une proposition : le texte enregistré est celui du prestataire, pas du client.
    $pkFromProposal = ($order->metadata['source'] ?? null) === 'service_proposal';
    // Refusée ou annulée : aucun argent n'a circulé, on n'affiche pas de lignes de paiement.
    $pkVoid = in_array($order->status, [$pkStatus::STATUS_REFUSED, $pkStatus::STATUS_CANCELED], true);
    $pkTone = match ($order->status) {
        $pkStatus::STATUS_PENDING_ACCEPTANCE => 'action',
        $pkStatus::STATUS_DISPUTED => 'alert',
        $pkStatus::STATUS_AWAITING_PAYMENT, $pkStatus::STATUS_FUNDED => 'waiting',
        default => 'closed',
    };
    $pkNext = match (true) {
        $pkToUndo => 'Cette réservation a été faite sur votre demande, avec une ancienne version du site. Or sur une demande, c’est vous le client : vous n’avez rien à encaisser. '.($pkAccepted ? 'Annulez-la' : 'Refusez-la').', puis invitez '.$pkBuyerName.' à vous envoyer une proposition.',
        $order->status === $pkStatus::STATUS_PENDING_ACCEPTANCE => $pkBuyerName.' souhaite réserver cette prestation. Acceptez pour que le paiement en ligne lui soit ouvert.',
        $order->canBuyerPay() && ! $pkPayable => 'Vous avez accepté. Activez la réception de vos paiements : le client ne peut pas payer avant.',
        $order->canBuyerPay() => 'Vous avez accepté. Le client doit maintenant payer en ligne ; vous serez prévenu dès que ce sera fait.',
        $order->status === $pkStatus::STATUS_FUNDED => 'Le client a payé'.($order->paid_at ? ' le '.$order->paid_at->format('d/m/Y') : '').' : l’argent est conservé en sécurité. Réalisez la prestation ; vous recevrez '.$pkMoney($order->seller_amount).' dès que le client aura confirmé la fin du travail.',
        $order->status === $pkStatus::STATUS_COMPLETED => 'Prestation terminée'.($order->released_at ? ' le '.$order->released_at->format('d/m/Y') : '').' : '.$pkMoney($order->seller_amount).' vous ont été versés.',
        $order->status === $pkStatus::STATUS_DISPUTED => 'Le client a signalé un problème. Le paiement reste conservé en sécurité pendant que notre équipe examine la situation.',
        $order->status === $pkStatus::STATUS_REFUNDED => 'Le client a été remboursé'.($order->refunded_at ? ' le '.$order->refunded_at->format('d/m/Y') : '').'.',
        $order->status === $pkStatus::STATUS_REFUSED => 'Vous avez refusé cette commande.',
        $order->status === $pkStatus::STATUS_CANCELED => 'Commande annulée'.($order->canceled_at ? ' le '.$order->canceled_at->format('d/m/Y') : '').'. Aucun paiement n’a été encaissé.',
        default => null,
    };
@endphp
<article id="order-{{ $order->id }}" class="pk-order" @if($pkMisdirected) data-order-misdirected @endif>
    <header class="pk-order__head">
        <span class="pk-order__status pk-order__status--{{ $pkToUndo ? 'action' : $pkTone }}">{{ $pkToUndo ? ($pkAccepted ? 'À annuler' : 'À refuser') : $order->status_label }}</span>
        <h3 class="pk-order__title">{{ $order->ad?->title ?? 'Prestation' }}</h3>
        <p class="pk-order__ref">Commande n° {{ $order->order_number }}</p>
    </header>

    <dl class="pk-order__facts">
        <div><dt>{{ $pkMisdirected ? 'Envoyée par' : 'Client' }}</dt><dd>{{ $pkBuyerName }}</dd></div>
        <div><dt>Prix convenu</dt><dd>{{ $pkMoney($order->amount) }}</dd></div>
        @unless($pkMisdirected || $pkVoid)
            <div><dt>Commission Prokejem</dt><dd>− {{ $pkMoney($order->commission_amount) }}</dd></div>
            <div class="is-total"><dt>Vous recevez</dt><dd>{{ $pkMoney($order->seller_amount) }}</dd></div>
            <div><dt>Paiement</dt><dd>{{ $order->payment_status_label }}</dd></div>
        @endunless
        @if($order->scheduled_for)
            <div><dt>Date souhaitée</dt><dd>{{ $order->scheduled_for->format('d/m/Y') }}</dd></div>
        @endif
    </dl>

    @if($order->message)
        <p class="pk-order__message"><b>{{ $pkFromProposal ? 'Votre proposition' : 'Message de '.$pkBuyerName }}</b>{{ $order->message }}</p>
    @endif

    @if($pkNext)
        <p class="pk-order__next pk-order__next--{{ $pkTone }}">{{ $pkNext }}</p>
    @endif
    @if($order->refused_reason)
        <p class="pk-order__message"><b>Motif du refus</b>{{ $order->refused_reason }}</p>
    @endif
    @if($order->dispute_reason)
        <p class="pk-order__message"><b>Problème signalé par le client</b>{{ $order->dispute_reason }}</p>
    @endif
    @if($order->admin_resolution_note)
        <p class="pk-order__message"><b>Décision de Prokejem</b>{{ $order->admin_resolution_note }}</p>
    @endif

    @if($order->canSellerAccept() || $order->canBeCanceledBy(auth()->user()))
        <div class="pk-order__actions">
            @if($order->canSellerAccept())
                <form action="{{ route('service-orders.accept', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="pk-orders__btn">Accepter la commande</button>
                </form>
            @endif
            @if($order->canBeCanceledBy(auth()->user()))
                <form action="{{ route('service-orders.cancel', $order) }}" method="POST" data-pk-confirm="Annuler cette commande ? Le client n’a rien payé ; il sera prévenu.">
                    @csrf
                    <button type="submit" class="{{ $pkToUndo ? 'pk-orders__btn' : 'pk-orders__link' }}" data-order-cancel="seller">Annuler la commande</button>
                </form>
            @endif
        </div>
    @endif

    @if($order->canSellerRefuse())
        <details class="pk-order__more" @if($pkMisdirected) open @endif>
            <summary>Refuser cette commande</summary>
            <form action="{{ route('service-orders.refuse', $order) }}" method="POST">
                @csrf
                <textarea name="reason" maxlength="500" placeholder="Motif du refus (facultatif) : il sera transmis à {{ $pkBuyerName }}."></textarea>
                <button type="submit" class="pk-orders__btn pk-orders__btn--quiet" data-order-refuse>Refuser</button>
            </form>
        </details>
    @endif
</article>

@php
    // Carte d'une commande vue par le client (celui qui paie la prestation).
    $pkStatus = \App\Models\ServiceOrder::class;
    $pkSellerName = $order->seller?->name ?? 'Prestataire';
    $pkSellerPayable = (bool) $order->seller?->stripe_connect_payouts_enabled;
    $pkAmount = number_format((float) $order->amount, 2, ',', ' ').' €';
    // Commande née d'une proposition : le texte enregistré est celui du prestataire, pas du client.
    $pkFromProposal = ($order->metadata['source'] ?? null) === 'service_proposal';
    // Refusée ou annulée : aucun argent n'a circulé, on n'affiche pas de lignes de paiement.
    $pkMisdirected = $order->isReservationOnDemand();
    // À défaire tant qu'elle n'est pas payée : à refuser, ou à annuler si elle a déjà été acceptée.
    $pkAccepted = $order->status === $pkStatus::STATUS_AWAITING_PAYMENT;
    $pkToUndo = $pkMisdirected && in_array($order->status, [$pkStatus::STATUS_PENDING_ACCEPTANCE, $pkStatus::STATUS_AWAITING_PAYMENT], true);
    $pkVoid = $pkMisdirected || in_array($order->status, [$pkStatus::STATUS_REFUSED, $pkStatus::STATUS_CANCELED], true);
    $pkTone = match ($order->status) {
        $pkStatus::STATUS_AWAITING_PAYMENT, $pkStatus::STATUS_FUNDED => 'action',
        $pkStatus::STATUS_DISPUTED => 'alert',
        $pkStatus::STATUS_PENDING_ACCEPTANCE => 'waiting',
        default => 'closed',
    };
    $pkNext = match (true) {
        $pkToUndo => 'Cette annonce est une demande : son auteur cherche un prestataire, il ne vend pas de prestation. Annulez cette réservation et envoyez-lui plutôt une proposition depuis l’annonce.',
        $order->status === $pkStatus::STATUS_PENDING_ACCEPTANCE => $pkSellerName.' doit d’abord accepter votre réservation. Vous serez prévenu, et vous pourrez alors payer en ligne.',
        $order->canBuyerPay() && ! $pkSellerPayable => 'Le prestataire n’a pas encore activé la réception de ses paiements : le paiement en ligne sera possible dès que ce sera fait.',
        $order->canBuyerPay() => 'Payez en ligne pour confirmer la prestation. Votre argent est conservé en sécurité : le prestataire ne le reçoit qu’une fois le travail terminé.',
        $order->isAwaitingAdminDecision() => 'Vous n’avez pas confirmé la fin de cette prestation malgré nos rappels. Prokejem examine maintenant la situation et décidera de payer le prestataire ou de vous rembourser. Vous pouvez encore confirmer ou signaler un problème ici.',
        $order->canBuyerRelease() => 'Vous avez payé'.($order->paid_at ? ' le '.$order->paid_at->format('d/m/Y') : '').'. Votre argent est conservé en sécurité. Quand le travail est terminé, confirmez-le ici : '.$pkSellerName.' sera alors payé.',
        $order->status === $pkStatus::STATUS_COMPLETED => 'Prestation terminée'.($order->released_at ? ' le '.$order->released_at->format('d/m/Y') : '').' : le prestataire a été payé.',
        $order->status === $pkStatus::STATUS_DISPUTED => 'Vous avez signalé un problème. Votre argent reste conservé en sécurité pendant que notre équipe examine la situation.',
        $order->status === $pkStatus::STATUS_REFUNDED => 'Vous avez été remboursé'.($order->refunded_at ? ' le '.$order->refunded_at->format('d/m/Y') : '').'.',
        $order->status === $pkStatus::STATUS_REFUSED => 'Le prestataire n’a pas accepté cette commande. Vous n’avez rien payé.',
        $order->status === $pkStatus::STATUS_CANCELED => 'Commande annulée'.($order->canceled_at ? ' le '.$order->canceled_at->format('d/m/Y') : '').'. Aucun paiement n’a été encaissé.',
        default => null,
    };
@endphp
<article id="order-{{ $order->id }}" class="pk-order">
    <header class="pk-order__head">
        <span class="pk-order__status pk-order__status--{{ $pkToUndo ? 'action' : $pkTone }}">{{ $pkToUndo ? 'À annuler' : $order->status_label }}</span>
        <h3 class="pk-order__title">{{ $order->ad?->title ?? 'Prestation' }}</h3>
        <p class="pk-order__ref">Commande n° {{ $order->order_number }}</p>
    </header>

    <dl class="pk-order__facts">
        <div><dt>{{ $pkMisdirected ? 'Auteur de la demande' : 'Prestataire' }}</dt><dd>{{ $pkSellerName }}</dd></div>
        <div class="is-total"><dt>Prix convenu</dt><dd>{{ $pkAmount }}</dd></div>
        @unless($pkVoid)
            <div><dt>Frais ajoutés</dt><dd>Aucun</dd></div>
            <div><dt>Paiement</dt><dd>{{ $order->payment_status_label }}</dd></div>
        @endunless
        @if($order->scheduled_for)
            <div><dt>Date souhaitée</dt><dd>{{ $order->scheduled_for->format('d/m/Y') }}</dd></div>
        @endif
    </dl>

    @if($order->message)
        <p class="pk-order__message"><b>{{ $pkFromProposal ? 'Proposition de '.$pkSellerName : 'Votre message' }}</b>{{ $order->message }}</p>
    @endif

    @if($pkNext)
        <p class="pk-order__next pk-order__next--{{ $pkTone }}">{{ $pkNext }}</p>
    @endif
    @if($order->refused_reason)
        <p class="pk-order__message"><b>Motif du refus</b>{{ $order->refused_reason }}</p>
    @endif
    @if($order->dispute_reason)
        <p class="pk-order__message"><b>Problème signalé</b>{{ $order->dispute_reason }}</p>
    @endif
    @if($order->admin_resolution_note)
        <p class="pk-order__message"><b>Décision de Prokejem</b>{{ $order->admin_resolution_note }}</p>
    @endif

    @if($order->canBuyerPay() || $order->canBuyerRelease() || $order->canBeCanceledBy(auth()->user()))
        <div class="pk-order__actions">
            @if($order->canBuyerPay() && ! $pkMisdirected)
                <form action="{{ route('service-orders.checkout', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="pk-orders__btn">Payer en ligne</button>
                </form>
            @endif
            @if($order->canBuyerRelease())
                <form action="{{ route('service-orders.release', $order) }}" method="POST" data-pk-confirm="Confirmer que la prestation est terminée ? {{ $pkSellerName }} sera payé. Ce versement est définitif.">
                    @csrf
                    <button type="submit" class="pk-orders__btn" data-order-release>Prestation terminée : payer le prestataire</button>
                </form>
            @endif
            @if($order->canBeCanceledBy(auth()->user()))
                <form action="{{ route('service-orders.cancel', $order) }}" method="POST" data-pk-confirm="Annuler cette commande ? Rien ne vous sera prélevé et le prestataire sera prévenu.">
                    @csrf
                    <button type="submit" class="{{ $pkToUndo ? 'pk-orders__btn' : 'pk-orders__link' }}" data-order-cancel="buyer">Annuler la commande</button>
                </form>
            @endif
        </div>
    @endif

    @if($order->canBuyerDispute())
        <details class="pk-order__more">
            <summary>Un problème avec cette prestation ?</summary>
            <form action="{{ route('service-orders.dispute', $order) }}" method="POST">
                @csrf
                <textarea name="reason" placeholder="Expliquez ce qui ne va pas (10 caractères minimum). Votre argent reste conservé et notre équipe intervient." minlength="10" maxlength="1000" required></textarea>
                <button type="submit" class="pk-orders__btn pk-orders__btn--quiet">Signaler le problème</button>
            </form>
        </details>
    @endif

    @if($order->status === \App\Models\ServiceOrder::STATUS_COMPLETED)
        @include('service-orders.partials.reminder', ['order' => $order])
    @endif
</article>

{{-- Un abonnement ou un accès à paiement unique en cours : état, échéance, moyen de paiement,
     puis les actions possibles. Un accès à paiement unique n'a ni moyen de paiement ni résiliation. --}}
@php
    $pkManaged = $subscription->hasOpenBilling();
    $pkOneTime = $subscription->isOneTimeAccess();
    // Accès déjà payé qui commencera à la fin de l'accès en cours : présenté avec lui.
    $pkQueued = $pkOneTime && $subscription->isPaidAhead();
    $pkActive = $subscription->isActive() || $pkQueued;
    $pkLate = $subscription->inPaymentGrace();
    $pkStopping = $pkActive && $pkManaged && ! $subscription->auto_renew && ! $pkLate;
    [$pkTone, $pkState] = match (true) {
        $pkLate => ['late', 'Paiement en retard'],
        $pkStopping => ['muted', 'Résiliation programmée'],
        $pkActive && $pkOneTime => ['ok', $pkQueued ? 'À venir' : 'Actif'],
        $pkActive && ! $pkManaged => ['ok', 'Accès offert'],
        $pkActive => ['ok', 'Actif'],
        default => ['late', 'Paiement à confirmer'],
    };
    $pkPlan = \App\Support\ProviderSubscriptionPlans::get($subscription->plan);
    $pkPeriod = $subscription->plan === 'annual' ? 'par an' : 'par mois';
    $pkDate = $subscription->ends_at?->format('d/m/Y');
@endphp
<article class="pk-billing-card">
    <header class="pk-billing-card__head">
        <div>
            <h3>Premium · {{ $pkOneTime ? $subscription->oneTimeLabel() : ($pkPlan['label'] ?? ucfirst($subscription->plan)) }}</h3>
            <p class="pk-billing-card__price">{{ number_format((float) $subscription->amount, 2, ',', ' ') }} € {{ $pkOneTime ? 'réglés en une fois' : $pkPeriod }}</p>
        </div>
        <span class="pk-billing-chip pk-billing-chip--{{ $pkTone }}">{{ $pkState }}</span>
    </header>

    <dl class="pk-billing-card__facts">
        @if($pkLate)
            <div><dt>Paiement refusé le</dt><dd>{{ $subscription->payment_failed_at->format('d/m/Y') }}</dd></div>
            <div><dt>À régler avant le</dt><dd>{{ $subscription->grace_ends_at->format('d/m/Y') }}</dd></div>
        @elseif($pkActive && $pkOneTime)
            <div>
                <dt>{{ $pkQueued ? 'À la suite de votre accès en cours' : 'Accès Premium' }}</dt>
                <dd>{{ $pkQueued ? 'Du '.$subscription->starts_at->format('d/m/Y').' au '.$pkDate : 'Jusqu’au '.$pkDate }} — aucun renouvellement</dd>
            </div>
        @elseif(! $pkManaged)
            <div><dt>Accès accordé par Prokejem</dt><dd>{{ $pkDate ? 'Valable jusqu’au '.$pkDate : 'Sans date de fin' }} · aucun prélèvement</dd></div>
        @elseif($pkStopping)
            <div><dt>Fin de vos avantages</dt><dd>{{ $pkDate ?? 'Date à confirmer' }} · aucun nouveau prélèvement</dd></div>
        @elseif($pkActive)
            <div><dt>Prochain prélèvement</dt><dd>{{ $pkDate ?? 'Date à confirmer' }}</dd></div>
        @else
            <div><dt>État</dt><dd>Vos avantages Premium sont fermés tant que le paiement n’est pas confirmé.</dd></div>
        @endif
        @if($pkManaged)
            <div><dt>Moyen de paiement</dt><dd>{{ $paymentMethod['label'] ?? 'Enregistré chez Stripe, notre prestataire de paiement' }}</dd></div>
        @endif
        @if($subscription->last_payment_at)
            <div><dt>{{ $pkOneTime ? 'Paiement unique reçu' : 'Dernier paiement reçu' }}</dt><dd>{{ $subscription->last_payment_at->format('d/m/Y') }}</dd></div>
        @endif
    </dl>

    @if($pkManaged)
        <div class="pk-billing-card__actions">
            @unless($pkLate)
                <form method="POST" action="{{ route('billing.payment-method') }}">
                    @csrf
                    <button type="submit" class="pk-billing-btn">Changer de moyen de paiement</button>
                </form>
            @endunless
            @if($pkStopping)
                <form method="POST" action="{{ route('pro.subscription.resume-renewal') }}" data-pk-confirm="Reprendre l’abonnement ? Le prochain prélèvement aura lieu le {{ $pkDate }}.">
                    @csrf
                    <button type="submit" class="pk-billing-btn">Reprendre l’abonnement</button>
                </form>
            @else
                <form method="POST" action="{{ route('pro.subscription.cancel-renewal') }}"
                      data-pk-confirm="{{ $pkLate
                        ? 'Résilier maintenant ? Le paiement en attente est annulé et votre compte repasse tout de suite en gratuit.'
                        : ($pkActive
                            ? 'Résilier votre abonnement ? Vos avantages restent ouverts jusqu’au '.$pkDate.', puis votre compte repasse en gratuit.'
                            : 'Résilier cet abonnement ? Aucun prélèvement ne sera plus effectué.') }}">
                    @csrf
                    <button type="submit" class="pk-billing-btn pk-billing-btn--quiet">Résilier l’abonnement</button>
                </form>
            @endif
        </div>
        @unless($pkLate)
            <p class="pk-billing-card__note">{{ $sepaAvailable ? 'Vous pouvez payer par carte bancaire ou par prélèvement SEPA.' : 'Le paiement se fait par carte bancaire.' }} Rien n’est débité quand vous changez de moyen de paiement.</p>
        @endunless
    @elseif($pkActive && $pkOneTime)
        <p class="pk-billing-card__note">Aucun prélèvement ne suivra : cet accès s’arrête seul à sa date de fin, sans résiliation à faire.</p>
    @endif
</article>

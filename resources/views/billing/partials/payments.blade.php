{{-- Paiements d'abonnement réellement reçus, puis les abonnements terminés. --}}
@if($payments->isNotEmpty() || $user->stripe_id)
    <section class="pk-billing__section" aria-labelledby="pk-billing-payments">
        <h2 id="pk-billing-payments">Mes paiements</h2>
        @if($payments->isNotEmpty())
            <table class="pk-billing-table">
                <thead>
                    <tr><th scope="col">Date</th><th scope="col">Objet</th><th scope="col">Montant</th></tr>
                </thead>
                <tbody>
                    @foreach($payments as $pkPayment)
                        <tr>
                            <td data-label="Date">{{ $pkPayment->created_at->format('d/m/Y') }}</td>
                            <td data-label="Objet">{{ $pkPayment->description }}</td>
                            <td data-label="Montant">{{ number_format((float) $pkPayment->amount, 2, ',', ' ') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="pk-billing__empty">Aucun paiement d’abonnement enregistré pour le moment.</p>
        @endif
        @if($user->stripe_id)
            <form method="POST" action="{{ route('pro.subscription.billing-portal') }}" class="pk-billing__invoices">
                @csrf
                <button type="submit" class="pk-billing-btn">Télécharger mes factures</button>
                <span>Vos factures sont conservées par Stripe, notre prestataire de paiement.</span>
            </form>
        @endif
    </section>
@endif

@if($past->isNotEmpty())
    <section class="pk-billing__section" aria-labelledby="pk-billing-past">
        <h2 id="pk-billing-past">Abonnements terminés</h2>
        <table class="pk-billing-table">
            <thead>
                <tr><th scope="col">Formule</th><th scope="col">Début</th><th scope="col">Fin</th><th scope="col">État</th></tr>
            </thead>
            <tbody>
                @foreach($past as $pkPast)
                    <tr>
                        <td data-label="Formule">{{ $pkPast->getPlanLabel() }}</td>
                        <td data-label="Début">{{ $pkPast->starts_at?->format('d/m/Y') ?? '—' }}</td>
                        <td data-label="Fin">{{ $pkPast->ends_at?->format('d/m/Y') ?? '—' }}</td>
                        <td data-label="État">{{ $pkPast->status === 'cancelled' ? 'Résilié' : 'Terminé' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endif

{{-- Paiements Premium réellement reçus (échéances d'abonnement et paiements uniques), puis les abonnements terminés. --}}
@if($payments->isNotEmpty() || $user->stripe_id)
    <section class="pk-billing__section" aria-labelledby="pk-billing-payments">
        <h2 id="pk-billing-payments">Mes paiements</h2>
        @if($payments->isNotEmpty())
            <table class="pk-billing-table">
                <thead>
                    <tr><th scope="col">Date</th><th scope="col">Objet</th><th scope="col">Montant</th><th scope="col">Facture</th></tr>
                </thead>
                <tbody>
                    @foreach($payments as $pkPayment)
                        @php
                            // Abonnement : le paiement porte l'identifiant de sa facture. Paiement unique : Stripe émet un justificatif après le paiement.
                            $pkHasInvoice = str_starts_with((string) $pkPayment->stripe_session_id, 'in_')
                                || ($pkPayment->metadata['kind'] ?? null) === \App\Services\ProviderSubscriptionService::ONE_TIME_CHECKOUT_TYPE;
                        @endphp
                        <tr>
                            <td data-label="Date">{{ $pkPayment->created_at->format('d/m/Y') }}</td>
                            <td data-label="Objet">{{ $pkPayment->description }}</td>
                            <td data-label="Montant">{{ number_format((float) $pkPayment->amount, 2, ',', ' ') }} €</td>
                            <td data-label="Facture">
                                @if($pkHasInvoice)
                                    <a class="pk-billing-invoice" href="{{ route('billing.invoice', $pkPayment) }}" target="_blank" rel="noopener"
                                       aria-label="Télécharger la facture du {{ $pkPayment->created_at->format('d/m/Y') }}">
                                        <i class="fas fa-file-pdf" aria-hidden="true"></i> Télécharger
                                    </a>
                                @else
                                    <span aria-label="Aucune facture">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="pk-billing__empty">Aucun paiement Premium enregistré pour le moment.</p>
        @endif
        @if($payments->isNotEmpty())
            <p class="pk-billing__invoices">Chaque facture vous est aussi envoyée par e-mail dès que le paiement est reçu.</p>
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

{{-- Paiement refusé : ce que le membre risque, jusqu'à quand, et le seul bouton plein de la page. --}}
@php
    $pkDaysLeft = $subscription->graceDaysLeft();
@endphp
<section class="pk-billing-late" role="alert" aria-labelledby="pk-billing-late-title">
    <h2 id="pk-billing-late-title">Votre dernier paiement a échoué</h2>
    <p>
        Le paiement de votre abonnement a été refusé le {{ $subscription->payment_failed_at->format('d/m/Y') }}.
        Vous gardez tous vos avantages Premium jusqu’au <strong>{{ $subscription->grace_ends_at->format('d/m/Y') }}</strong>
        ({{ $pkDaysLeft > 1 ? 'il vous reste '.$pkDaysLeft.' jours' : 'dernier jour' }}).
        Sans paiement à cette date, votre abonnement sera résilié et votre compte repassera en gratuit, sans les avantages Premium.
    </p>
    <form method="POST" action="{{ route('billing.payment-method') }}">
        @csrf
        <button type="submit" class="pk-billing-btn pk-billing-btn--primary">Mettre à jour mon moyen de paiement</button>
    </form>
    <p class="pk-billing-late__note">
        {{ $sepaAvailable ? 'Carte bancaire ou prélèvement SEPA.' : 'Par carte bancaire.' }}
        Le paiement en retard est présenté de nouveau dès que le nouveau moyen de paiement est enregistré.
    </p>
</section>

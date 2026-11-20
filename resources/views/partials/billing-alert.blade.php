{{-- Rappel affiché sur toutes les pages tant qu'un paiement d'abonnement est en retard.
     La requête n'est faite que pour un membre qui a déjà payé par Stripe. --}}
@auth
    @php
        $pkLatePayment = filled(auth()->user()->stripe_id) && ! request()->routeIs('billing.*')
            ? auth()->user()->proSubscriptions()->inPaymentGrace()->latest('id')->first()
            : null;
    @endphp
    @if($pkLatePayment)
        <link rel="stylesheet" href="{{ asset('css/billing.css') }}?v={{ filemtime(public_path('css/billing.css')) }}">
        <div class="pk-pay-alert" role="alert">
            <span>Le paiement de votre abonnement a échoué. Réglez-le avant le {{ $pkLatePayment->grace_ends_at->format('d/m/Y') }} pour garder vos avantages Premium.</span>
            <a href="{{ route('billing.index') }}">Régler mon paiement</a>
        </div>
    @endif
@endauth

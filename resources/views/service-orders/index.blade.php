@extends('layouts.app')

@section('title', 'Mes commandes - Prokejem')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/service-orders.css') }}?v={{ filemtime(public_path('css/service-orders.css')) }}">
@endpush

@section('content')
<div class="pk-orders">
    <h1 class="pk-orders__title">Mes commandes</h1>
    <p class="pk-orders__intro">Une commande, c’est une prestation réservée et payée en ligne sur Prokejem. L’argent du client est conservé pendant la prestation : le prestataire n’est payé qu’une fois le travail terminé.</p>

    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    @if($needsStripeConnectOnboarding ?? false)
        <div class="pk-orders__setup">
            <div>
                <strong>Activez la réception de vos paiements.</strong>
                <p>Pour vous verser l’argent de vos clients, la loi impose de vérifier une fois votre identité et votre compte bancaire. Cela se fait en quelques minutes chez Stripe, notre partenaire de paiement. Tant que ce n’est pas fait, vos clients ne peuvent pas payer en ligne.</p>
            </div>
            <a href="{{ route('service-orders.connect.onboarding') }}" class="pk-orders__btn">Activer mes paiements</a>
        </div>
    @endif

    @if($ordersAsBuyer->isEmpty() && $ordersAsSeller->isEmpty())
        <div class="pk-orders__empty">
            <h2>Aucune commande pour le moment</h2>
            <p>Une commande se crée de deux façons : vous choisissez une proposition reçue pour l’une de vos demandes, ou vous réservez la prestation d’un professionnel depuis son annonce.</p>
            <p>Si vous êtes prestataire, les commandes de vos clients apparaîtront ici.</p>
        </div>
    @endif

    @if($ordersAsBuyer->isNotEmpty())
        <section class="pk-orders__section" data-orders-role="client" aria-labelledby="pk-orders-client">
            <h2 class="pk-orders__section-title" id="pk-orders-client">Je suis le client</h2>
            <p class="pk-orders__section-hint">Les prestations que vous faites réaliser. Vous payez en ligne, sans frais ajoutés.</p>
            <div class="pk-orders__grid">
                @foreach($ordersAsBuyer as $order)
                    @include('service-orders.partials.buyer-card', ['order' => $order])
                @endforeach
            </div>
        </section>
    @endif

    @if($ordersAsSeller->isNotEmpty())
        <section class="pk-orders__section" data-orders-role="prestataire" aria-labelledby="pk-orders-provider">
            <h2 class="pk-orders__section-title" id="pk-orders-provider">Je suis le prestataire</h2>
            <p class="pk-orders__section-hint">Les prestations que vous réalisez pour vos clients. La commission de Prokejem ({{ \App\Support\MarketplaceCommission::label() }}) est déduite du montant qui vous est versé.</p>
            <div class="pk-orders__grid">
                @foreach($ordersAsSeller as $order)
                    @include('service-orders.partials.seller-card', ['order' => $order])
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection

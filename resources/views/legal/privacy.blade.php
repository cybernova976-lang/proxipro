@extends('layouts.app')

@section('title', 'Politique de confidentialité - ProxiPro')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}" class="text-decoration-none"><i class="fas fa-home me-1"></i>Accueil</a></li>
                    <li class="breadcrumb-item active">Confidentialité</li>
                </ol>
            </nav>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <h1 class="mb-4">Politique de confidentialité</h1>
                    
                    <h4 class="mt-4">1. Collecte des données</h4>
                    <p>Nous collectons les données personnelles suivantes :</p>
                    <ul>
                        <li>Nom et prénom</li>
                        <li>Adresse email</li>
                        <li>Numéro de téléphone (optionnel)</li>
                        <li>Localisation (optionnel)</li>
                        <li>Photo de profil (optionnel)</li>
                    </ul>
                    
                    <h4 class="mt-4">2. Utilisation des données</h4>
                    <p>Vos données sont utilisées pour :</p>
                    <ul>
                        <li>Gérer votre compte utilisateur</li>
                        <li>Permettre la mise en relation avec d'autres utilisateurs</li>
                        <li>Améliorer nos services</li>
                        <li>Vous envoyer des communications relatives à notre service</li>
                    </ul>
                    
                    <h4 class="mt-4">3. Protection des données</h4>
                    <p>Nous mettons en œuvre des mesures de sécurité appropriées pour protéger vos données personnelles contre tout accès non autorisé, modification, divulgation ou destruction.</p>
                    
                    <h4 class="mt-4">4. Partage des données</h4>
                    <p>Vos données ne sont pas vendues à des tiers. Elles peuvent être partagées uniquement dans les cas suivants :</p>
                    <ul>
                        <li>Avec votre consentement explicite</li>
                        <li>Pour répondre à une obligation légale</li>
                        <li>Avec nos prestataires de services (hébergement, paiement)</li>
                    </ul>
                    
                    <h4 class="mt-4">5. Vos droits</h4>
                    <p>Conformément au RGPD, vous disposez des droits suivants :</p>
                    <ul>
                        <li>Droit d'accès à vos données</li>
                        <li>Droit de rectification</li>
                        <li>Droit à l'effacement</li>
                        <li>Droit à la portabilité</li>
                        <li>Droit d'opposition</li>
                    </ul>
                    
                    <h4 class="mt-4">6. Contact</h4>
                    <p>Pour toute question concernant vos données personnelles, contactez-nous à : <a href="mailto:contact@ProxiPro.com">contact@ProxiPro.com</a></p>
                    
                    <div class="mt-5 text-muted">
                        <p><small>Dernière mise à jour : {{ date('d/m/Y') }}</small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

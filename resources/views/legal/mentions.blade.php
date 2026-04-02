@extends('layouts.app')

@section('title', 'Mentions légales - ProxiPro')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}" class="text-decoration-none"><i class="fas fa-home me-1"></i>Accueil</a></li>
                    <li class="breadcrumb-item active">Mentions légales</li>
                </ol>
            </nav>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <h1 class="mb-4">Mentions légales</h1>
                    
                    <h4 class="mt-4">1. Éditeur du site</h4>
                    <p>Le site ProxiPro est édité par :</p>
                    <ul>
                        <li><strong>Raison sociale :</strong> ProxiPro</li>
                        <li><strong>Adresse :</strong> Mayotte, France</li>
                        <li><strong>Email :</strong> contact@ProxiPro.com</li>
                    </ul>
                    
                    <h4 class="mt-4">2. Hébergement</h4>
                    <p>Le site est hébergé par :</p>
                    <ul>
                        <li><strong>Hébergeur :</strong> [Nom de l'hébergeur]</li>
                        <li><strong>Adresse :</strong> [Adresse de l'hébergeur]</li>
                    </ul>
                    
                    <h4 class="mt-4">3. Propriété intellectuelle</h4>
                    <p>L'ensemble du contenu de ce site (textes, images, logos, graphismes) est protégé par le droit d'auteur. Toute reproduction ou représentation, totale ou partielle, est interdite sans autorisation préalable.</p>
                    
                    <h4 class="mt-4">4. Responsabilité</h4>
                    <p>ProxiPro s'efforce d'assurer l'exactitude des informations diffusées sur ce site. Toutefois, ProxiPro ne peut garantir l'exactitude, la complétude et l'actualité des informations publiées.</p>
                    
                    <h4 class="mt-4">5. Données personnelles</h4>
                    <p>Pour toute information concernant le traitement de vos données personnelles, veuillez consulter notre <a href="{{ route('legal.privacy') }}">Politique de confidentialité</a>.</p>
                    
                    <div class="mt-5 text-muted">
                        <p><small>Dernière mise à jour : {{ date('d/m/Y') }}</small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

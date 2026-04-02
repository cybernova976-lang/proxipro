@extends('layouts.app')

@section('title', 'Politique des cookies - ProxiPro')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}" class="text-decoration-none"><i class="fas fa-home me-1"></i>Accueil</a></li>
                    <li class="breadcrumb-item active">Cookies</li>
                </ol>
            </nav>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <h1 class="mb-4">Politique des cookies</h1>
                    
                    <h4 class="mt-4">1. Qu'est-ce qu'un cookie ?</h4>
                    <p>Un cookie est un petit fichier texte stocké sur votre appareil lors de votre visite sur notre site. Les cookies nous permettent de vous reconnaître et de mémoriser vos préférences.</p>
                    
                    <h4 class="mt-4">2. Types de cookies utilisés</h4>
                    
                    <h5 class="mt-3">Cookies essentiels</h5>
                    <p>Ces cookies sont nécessaires au fonctionnement du site :</p>
                    <ul>
                        <li>Gestion de la session utilisateur</li>
                        <li>Sécurité (token CSRF)</li>
                        <li>Mémorisation de la connexion</li>
                    </ul>
                    
                    <h5 class="mt-3">Cookies de performance</h5>
                    <p>Ces cookies nous aident à comprendre comment les visiteurs interagissent avec notre site :</p>
                    <ul>
                        <li>Statistiques de visite anonymes</li>
                        <li>Pages les plus consultées</li>
                    </ul>
                    
                    <h5 class="mt-3">Cookies de fonctionnalité</h5>
                    <p>Ces cookies permettent d'améliorer votre expérience :</p>
                    <ul>
                        <li>Mémorisation de vos préférences</li>
                        <li>Langue et région</li>
                    </ul>
                    
                    <h4 class="mt-4">3. Gestion des cookies</h4>
                    <p>Vous pouvez gérer vos préférences de cookies à tout moment via les paramètres de votre navigateur :</p>
                    <ul>
                        <li><strong>Chrome :</strong> Paramètres → Confidentialité et sécurité → Cookies</li>
                        <li><strong>Firefox :</strong> Options → Vie privée et sécurité → Cookies</li>
                        <li><strong>Safari :</strong> Préférences → Confidentialité → Cookies</li>
                        <li><strong>Edge :</strong> Paramètres → Cookies et autorisations de site</li>
                    </ul>
                    
                    <h4 class="mt-4">4. Conséquences du refus des cookies</h4>
                    <p>Si vous désactivez les cookies, certaines fonctionnalités du site pourraient ne pas fonctionner correctement, notamment la connexion à votre compte.</p>
                    
                    <div class="mt-5 text-muted">
                        <p><small>Dernière mise à jour : {{ date('d/m/Y') }}</small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

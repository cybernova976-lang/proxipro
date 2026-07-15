@extends('layouts.app')

@section('title', 'Politique des cookies - ' . config('app.name', 'ProxiPro'))
@section('meta_description', 'Informations sur les cookies nécessaires au fonctionnement de ' . config('app.name', 'ProxiPro') . '.')

@section('content')
<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-8">
    <nav aria-label="breadcrumb" class="mb-3"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li><li class="breadcrumb-item active">Cookies</li></ol></nav>
    <div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
        <h1 class="mb-4">Politique des cookies</h1>
        <h4 class="mt-4">1. Cookies utilisés</h4>
        <p>La plateforme utilise des cookies strictement nécessaires à la session, à la protection CSRF, à la connexion et à certaines mesures de prévention des abus. Sans eux, l’authentification, les formulaires ou certains outils ne peuvent pas fonctionner correctement.</p>

        <h4 class="mt-4">2. Mesure d’audience et publicité</h4>
        <p>Aucun outil de mesure d’audience publicitaire ou cookie marketing n’est déclaré dans la version actuelle. Si un tel service est ajouté, cette page et le mécanisme de consentement devront être mis à jour avant son activation.</p>

        <h4 class="mt-4">3. Services externes</h4>
        <p>Une connexion OAuth ou un paiement peut vous rediriger vers le service choisi, par exemple Google, Facebook ou Stripe. Ces services appliquent leurs propres politiques lorsque vous interagissez avec eux.</p>

        <h4 class="mt-4">4. Vos choix</h4>
        <p>Vous pouvez supprimer ou bloquer les cookies depuis votre navigateur. Le blocage des cookies nécessaires peut empêcher la connexion et l’envoi de formulaires.</p>

        <p class="mt-5 text-muted small">Dernière mise à jour : {{ config('legal.last_updated') ?: date('d/m/Y') }}</p>
    </div></div>
</div></div></div>
@endsection

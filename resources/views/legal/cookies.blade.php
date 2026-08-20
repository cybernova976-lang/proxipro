@extends('layouts.app')

@section('title', 'Politique des cookies - ' . config('app.name', 'Prokejem'))
@section('meta_description', 'Informations sur les cookies nécessaires au fonctionnement de ' . config('app.name', 'Prokejem') . '.')

@section('content')
<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-8">
    <nav aria-label="breadcrumb" class="mb-3"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li><li class="breadcrumb-item active">Cookies</li></ol></nav>
    <div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
        <h1 class="mb-4">Politique des cookies</h1>
        <h4 class="mt-4">1. Cookies utilisés</h4>
        <p>La plateforme utilise des cookies strictement nécessaires à la session, à la protection CSRF, à la connexion et à certaines mesures de prévention des abus. Sans eux, l’authentification, les formulaires ou certains outils ne peuvent pas fonctionner correctement.</p>

        <h4 class="mt-4">2. Mesure d’audience et publicité</h4>
        <p>Prokejem réalise une mesure d’audience interne, limitée à l’amélioration du service. Elle utilise la session déjà nécessaire au fonctionnement du site et ne dépose aucun cookie publicitaire ou identifiant tiers.</p>
        <p>Seuls des compteurs agrégés par jour, page, type d’appareil et mode d’ouverture (navigateur ou application installée) sont conservés. La table de mesure ne contient ni identifiant de compte, ni adresse IP, ni user-agent complet, ni recherche, ni contenu de message, ni paramètre d’URL. Ces compteurs sont supprimés au plus tard après 25 mois et ne sont pas transmis à un tiers.</p>
        <div class="border rounded-3 p-3 my-3" id="audienceMeasurementChoice">
            <p class="mb-2 fw-semibold">Mesure d’audience sur cet appareil</p>
            <p class="small text-muted mb-3" id="audienceMeasurementStatus">Vérification du choix…</p>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="audienceMeasurementDisable">Désactiver la mesure</button>
            <button type="button" class="btn btn-outline-primary btn-sm" id="audienceMeasurementEnable">Autoriser la mesure</button>
        </div>

        <h4 class="mt-4">3. Services externes</h4>
        <p>Une connexion OAuth ou un paiement peut vous rediriger vers le service choisi, par exemple Google, Facebook ou Stripe. Ces services appliquent leurs propres politiques lorsque vous interagissez avec eux.</p>

        <h4 class="mt-4">4. Vos choix</h4>
        <p>Vous pouvez supprimer ou bloquer les cookies depuis votre navigateur. Le blocage des cookies nécessaires peut empêcher la connexion et l’envoi de formulaires.</p>

        <p class="mt-5 text-muted small">Dernière mise à jour : {{ config('legal.last_updated') ?: date('d/m/Y') }}</p>
    </div></div>
</div></div></div>
<script>
(() => {
    const key = 'prokejem_usage_disabled';
    const status = document.getElementById('audienceMeasurementStatus');
    const disable = document.getElementById('audienceMeasurementDisable');
    const enable = document.getElementById('audienceMeasurementEnable');

    const storageAvailable = (() => {
        try {
            localStorage.setItem('prokejem_storage_test', '1');
            localStorage.removeItem('prokejem_storage_test');
            return true;
        } catch (error) {
            return false;
        }
    })();

    const refresh = () => {
        if (!storageAvailable) {
            status.textContent = 'Le stockage local est indisponible : aucune mesure n’est envoyée depuis cet appareil.';
            disable.hidden = true;
            enable.hidden = true;
            return;
        }

        const isDisabled = localStorage.getItem(key) === '1';
        status.textContent = isDisabled
            ? 'La mesure d’audience est désactivée sur cet appareil.'
            : 'La mesure d’audience agrégée est actuellement autorisée sur cet appareil.';
        disable.hidden = isDisabled;
        enable.hidden = !isDisabled;
    };

    disable.addEventListener('click', () => { localStorage.setItem(key, '1'); refresh(); });
    enable.addEventListener('click', () => { localStorage.removeItem(key); refresh(); });
    refresh();
})();
</script>
@endsection

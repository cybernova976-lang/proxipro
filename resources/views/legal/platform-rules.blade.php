@extends('layouts.app')
@section('title', 'Règles de la marketplace - ' . config('app.name', 'Lunamars'))
@section('content')
<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-9"><div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
    <h1>Règles de la marketplace</h1>
    <p class="lead text-muted">Une présentation claire des profils, des annonces et des critères de visibilité.</p>

    <h4 class="mt-4">1. Espaces distincts</h4>
    <p>Le feed distingue les demandes publiées par des particuliers, les offres publiées par des prestataires ou professionnels, et les profils de prestataires. Le statut public de l’auteur accompagne le contenu afin que les utilisateurs identifient la nature de l’annonceur.</p>

    <h4 class="mt-4">2. Conditions de publication</h4>
    <p>Le profil doit contenir les informations demandées pour la catégorie choisie. Une annonce doit avoir un besoin ou une offre identifiable, une localisation ou une modalité à distance, une catégorie correcte, un prix ou budget sincère lorsque pertinent et des médias sur lesquels l’auteur possède les droits. Les doublons destinés à saturer le feed peuvent être regroupés ou retirés.</p>

    <h4 class="mt-4">3. Quotas et capacité</h4>
    <p>Le nombre d’annonces actives dépend du statut et, le cas échéant, du forfait affiché dans le compte. La plateforme peut limiter le nombre total de contenus simultanément exposés sur une zone du feed afin de préserver la lisibilité. Les contenus non retenus dans une fenêtre d’exposition restent accessibles par la recherche ou sont replacés dans une rotation équitable ; un paiement ne supprime pas les contrôles de conformité.</p>

    <h4 class="mt-4" id="classement">4. Classement et recommandations</h4>
    <p>Les principaux critères sont la correspondance avec la recherche et la catégorie, la distance ou la zone choisie, la fraîcheur, la complétude et la qualité du contenu, la fiabilité du compte, les interactions récentes et la disponibilité. Le feed peut aussi réserver de la diversité entre catégories, statuts et auteurs afin qu’un même groupe ne monopolise pas l’espace.</p>

    <h4 class="mt-4">5. Visibilité payante</h4>
    <p>Un boost augmente temporairement la probabilité d’exposition selon sa durée et sa zone, sans garantir une première place fixe ni un résultat commercial. Les contenus influencés par un paiement sont signalés par une mention telle que « Sponsorisé », « Boost » ou « Prioritaire ». Si la capacité prioritaire est atteinte, la diffusion est mise en rotation ou démarre selon la période annoncée, sans réduire la durée payée.</p>

    <h4 class="mt-4">6. Modération</h4>
    <p>Les contrôles peuvent être automatisés puis confirmés humainement. Un contenu peut être refusé, déclassé, masqué ou supprimé pour mauvaise catégorie, informations trompeuses, risque, illégalité, fraude ou violation des conditions. L’auteur peut demander un réexamen au support.</p>

    <h4 class="mt-4">7. Signalement et coopération</h4>
    <p>Tout utilisateur peut signaler un contenu ou comportement depuis les outils disponibles ou le <a href="{{ route('contact.index') }}">formulaire de contact</a>. Un signalement doit être de bonne foi et suffisamment précis. Les demandes des autorités compétentes sont traitées conformément à la loi.</p>

    <p class="mt-5 text-muted small">Dernière mise à jour : {{ config('legal.last_updated') ?: '17/07/2026' }}</p>
</div></div></div></div></div>
@endsection

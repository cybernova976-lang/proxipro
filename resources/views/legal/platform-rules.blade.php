@extends('layouts.app')
@section('title', 'Règles de la marketplace - ' . config('app.name', 'Prokejem'))
@section('content')
@php
    // Les valeurs viennent de la configuration du feed : la page ne peut pas s'écarter du code.
    $pkRulesPageSize = max(1, (int) config('feed.client.page_size', 6));
    $pkRulesEffective = app(\App\Services\Feed\RankingDisclosure::class)->sponsoredEffectiveLabel();
    $pkRulesUpdated = config('legal.platform_rules_updated') ?: config('legal.last_updated');
    // Le masquage des comptes internes n'est annoncé que s'il est réellement actif (FEED_HIDDEN_AUTHOR_IDS).
    $pkRulesHidesInternal = \App\Models\Ad::hiddenAuthorIds() !== [];
@endphp
<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-9"><div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
    <h1>Règles de la marketplace</h1>
    <p class="lead text-muted">Une présentation claire des profils, des annonces et des critères de visibilité.</p>

    <h4 class="mt-4">1. Espaces distincts</h4>
    <p>Le feed distingue les demandes publiées par des particuliers, les offres publiées par des prestataires ou professionnels, et les profils de prestataires. Le statut public de l’auteur accompagne le contenu afin que les utilisateurs identifient la nature de l’annonceur.</p>

    <h4 class="mt-4">2. Conditions de publication</h4>
    <p>Le profil doit contenir les informations demandées pour la catégorie choisie. Une annonce doit avoir un besoin ou une offre identifiable, une localisation ou une modalité à distance, une catégorie correcte, un prix ou budget sincère lorsque pertinent et des médias sur lesquels l’auteur possède les droits. Les doublons destinés à saturer le feed peuvent être regroupés ou retirés.</p>

    <h4 class="mt-4">3. Quotas et capacité</h4>
    <p>Le nombre d’annonces actives dépend du statut et, le cas échéant, du forfait affiché dans le compte. L’accueil des clients affiche {{ $pkRulesPageSize }} offres par page : les offres suivantes restent accessibles par les pages suivantes et par la <a href="{{ route('ads.index') }}">liste des annonces</a>. Aujourd’hui, aucun nombre maximal d’offres sponsorisées par page n’est appliqué. Un paiement ne supprime pas les contrôles de conformité.</p>

    <h4 class="mt-4" id="classement">4. Classement et recommandations</h4>
    <p><strong>Accueil des clients.</strong> Les offres de services présentées sur l’accueil connecté sont choisies et classées ainsi :</p>
    <ul>
        <li><strong>Périmètre :</strong> les offres actives du territoire du visiteur (son département, ou Mayotte, la Guyane, La Réunion, la Guadeloupe, la Martinique), déterminé d’après le lieu indiqué dans son profil (commune, code postal ou territoire) ou, à défaut, d’après sa position détectée automatiquement, ainsi que les offres d’autres territoires déclarées « À distance ». Si le territoire n’est pas connu, toutes les zones sont affichées et la page l’indique. Le lien « Voir les autres territoires » affiche toutes les zones.</li>
        <li><strong>Ordre</strong>, appliqué dans cette suite :
            <ol>
                <li>les offres sponsorisées passent en premier (voir l’article 5) ;</li>
                <li>dans chaque groupe (offres sponsorisées, puis autres offres), deux offres au plus par prestataire passent avant ses offres suivantes ;</li>
                <li>à égalité, les offres des catégories que le visiteur suit dans son profil passent en premier ;</li>
                <li>enfin, les offres les plus récentes d’abord (date de publication).</li>
            </ol>
        </li>
    </ul>
    <p>Une phrase qui résume ce critère est affichée au-dessus de la liste.@if ($pkRulesHidesInternal) Les annonces des comptes internes ou de test de la plateforme ne sont pas affichées.@endif</p>
    <p><strong>Liste des annonces.</strong> Les annonces sont classées par date de publication, les plus récentes d’abord, ou par prix ou par distance lorsque le visiteur choisit ce tri. Une option payante n’y modifie pas l’ordre.</p>
    <p><strong>Accueil des prestataires.</strong> Les demandes qui correspondent au métier du prestataire sont classées ainsi : les demandes encore sans proposition d’abord ; à égalité, les demandes signalées « Urgent » (option payante ou urgence indiquée au dépôt) ; puis les plus récentes.</p>
    <p><strong>Prestataires suggérés après une demande.</strong> Les prestataires abonnés (Premium) sont présentés avant les autres prestataires.</p>

    <h4 class="mt-4">5. Visibilité payante</h4>
    <p>Sur l’accueil des clients, toute offre mise en avant contre paiement porte la mention « Sponsorisé », suivie d’un lien « Pourquoi ? » vers cette page. Deux options produisent cet effet :</p>
    <ul>
        <li><strong>Boost :</strong> pendant la durée achetée, l’offre boostée rejoint le groupe des offres sponsorisées.</li>
        <li><strong>Abonnement Premium :</strong> pendant l’abonnement, toutes les offres actives du prestataire abonné rejoignent le groupe des offres sponsorisées.</li>
    </ul>
    <p>Aucune position n’est garantie. Les offres sponsorisées sont classées entre elles selon les règles 2 à 4 de l’article 4. Leur nombre n’est pas limité aujourd’hui : lorsqu’elles sont nombreuses, une offre sponsorisée peut figurer en page 2 ou au-delà. Un boost ou un abonnement ne garantit ni contact, ni vente, ni résultat commercial. Ailleurs sur le site, une annonce boostée peut porter la mention « Boosté ».</p>
    <p><strong>Option Urgent (demandes) :</strong> une demande qui a pris l’option payante est signalée « Urgent · option payante » ; une urgence indiquée gratuitement lors du dépôt est signalée « Urgent ».</p>
    <p><strong>Remontée :</strong> la remontée payante d’une annonce n’est plus proposée pour le moment. Elle ne modifie jamais un boost en cours.</p>
    <p><strong>Évolution annoncée :</strong> {{ $pkRulesEffective }}, un nouveau mécanisme remplacera l’ordre des offres sponsorisées décrit ci-dessus. Sur chaque page de {{ $pkRulesPageSize }} offres, 2 places au plus seront sponsorisées. Elles seront attribuées par un tirage au sort quotidien parmi les offres sponsorisées du territoire, avec une seule carte par prestataire et par page. Un abonnement Premium comptera pour une seule annonce du prestataire. Les autres offres garderont l’ordre décrit à l’article 4.</p>

    <h4 class="mt-4">6. Modération</h4>
    <p>Les contrôles peuvent être automatisés puis confirmés humainement. Un contenu peut être refusé, déclassé, masqué ou supprimé pour mauvaise catégorie, informations trompeuses, risque, illégalité, fraude ou violation des conditions. L’auteur peut demander un réexamen au support.</p>

    <h4 class="mt-4">7. Signalement et coopération</h4>
    <p>Tout utilisateur peut signaler un contenu ou comportement depuis les outils disponibles ou le <a href="{{ route('contact.index') }}">formulaire de contact</a>. Un signalement doit être de bonne foi et suffisamment précis. Les demandes des autorités compétentes sont traitées conformément à la loi.</p>

    <p class="mt-5 text-muted small">Dernière mise à jour : {{ $pkRulesUpdated ?: '08/11/2026' }}</p>
</div></div></div></div></div>
@endsection

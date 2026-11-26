@extends('layouts.app')

@section('title', 'Conditions générales d’utilisation - ' . config('app.name', 'Prokejem'))
@section('meta_description', 'Conditions d’accès et d’utilisation de la plateforme ' . config('app.name', 'Prokejem') . '.')

@section('content')
<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-9">
<nav aria-label="breadcrumb" class="mb-3"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li><li class="breadcrumb-item active">Conditions d’utilisation</li></ol></nav>
<div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
    <h1>Conditions générales d’utilisation</h1>
    <p class="lead text-muted">Ces conditions encadrent l’accès à la marketplace, la publication, la mise en relation et les outils associés.</p>
    @include('legal.partials.operator')

    <h4 class="mt-4">1. Champ d’application et acceptation</h4>
    <p>En créant un compte ou en utilisant une fonctionnalité réservée, l’utilisateur accepte les présentes conditions, les <a href="{{ route('legal.platform-rules') }}">règles de la marketplace</a> et la <a href="{{ route('legal.privacy') }}">politique de confidentialité</a>. Il doit disposer de la capacité juridique requise et fournir des informations exactes, actuelles et non trompeuses.</p>

    <h4 class="mt-4">2. Rôle de la plateforme</h4>
    <p>{{ config('app.name', 'Prokejem') }} est un intermédiaire de mise en relation. Sauf indication expresse pour un service précis, la plateforme n’est ni l’employeur, ni le mandataire, ni le fournisseur de la prestation conclue entre utilisateurs. Le client choisit son cocontractant ; le prestataire détermine son offre et reste responsable de son exécution.</p>

    <h4 class="mt-4">3. Comptes et statuts affichés</h4>
    <p>Une personne ne doit utiliser qu’un compte légitime et protéger ses accès. La plateforme distingue le particulier demandeur, le « particulier prestataire non professionnel » et le professionnel déclaré. Un badge « Profil vérifié » confirme uniquement les contrôles effectués au moment de la vérification ; il ne constitue ni une garantie de compétence, ni une assurance de bonne exécution.</p>

    <h4 class="mt-4">4. Prestataires et professionnels</h4>
    <p>Avant d’offrir un service, l’utilisateur vérifie qu’il est autorisé à l’exercer et respecte les règles sociales, fiscales, d’assurance, de qualification et d’information applicables dans son pays. Un particulier prestataire ne doit pas se présenter comme une entreprise. L’émission de devis ou factures via l’Espace PRO est soumise aux <a href="{{ route('legal.pro-terms') }}">conditions PRO</a>.</p>

    <h4 class="mt-4">5. Annonces, profils et messages</h4>
    <p>Les contenus doivent être licites, loyaux, précis et respectueux des tiers. Sont interdits les contenus frauduleux, discriminatoires, dangereux, contrefaisants, sexuellement illicites, portant atteinte à la vie privée ou proposant une activité réglementée sans autorisation. Le spam, la collecte abusive de données et le contournement des dispositifs de sécurité sont également interdits.</p>

    <h4 class="mt-4">6. Classement, priorité et publicité</h4>
    <p>Les principaux paramètres de classement, l’effet des options payantes et les règles de capacité du feed sont décrits dans les <a href="{{ route('legal.platform-rules') }}#classement">règles de la marketplace</a>. Une visibilité payante est signalée et n’emporte aucune garantie de contact, de vente ou de position permanente.</p>

    <h4 class="mt-4">7. Formation et exécution du contrat</h4>
    <p>Une commande, un devis accepté ou tout autre accord explicite peut former un contrat directement entre le client et le prestataire. Avant l’accord, les parties vérifient l’objet, le prix total, le calendrier, le lieu, les frais, les conditions d’annulation et les garanties. Les professionnels communiquent aux consommateurs les informations précontractuelles et le droit de rétractation lorsqu’il s’applique.</p>

    <h4 class="mt-4">8. Prix, paiement, annulation et remboursement</h4>
    <p id="commission">Lorsqu’une commande est réglée en ligne via Prokejem, le client paie le prix convenu avec le prestataire, sans frais ajoutés. La plateforme perçoit une commission de <strong>{{ \App\Support\MarketplaceCommission::label() }}</strong> du prix de la commande, déduite du montant versé au prestataire. Le paiement est encaissé par l’intermédiaire de Stripe et la part du prestataire ne lui est versée qu’après confirmation de la fin de la prestation par le client, ou après décision de la plateforme en cas de litige. Le prix, la commission et le montant versé sont affichés sur la commande. Un accord réglé directement entre le client et le prestataire, sans paiement en ligne sur Prokejem, ne donne lieu à aucune commission.</p>
    <p id="confirmation">Une fois la commande payée en ligne, il appartient au client de confirmer la fin de la prestation depuis ses commandes : c’est cette confirmation qui déclenche le versement au prestataire. La plateforme rappelle au client de le faire. <strong>Aucun versement n’est effectué automatiquement à l’expiration d’un délai.</strong> Si, malgré ces rappels, le client ne confirme pas la fin de la prestation et ne signale aucun problème, la plateforme examine la situation, peut demander des éléments au client et au prestataire, puis décide soit de verser la somme au prestataire, soit de rembourser le client. Sa décision est communiquée aux deux parties.</p>
    <p>Les prix, frais de plateforme et modalités de paiement sont affichés avant validation. Les paiements en ligne, lorsqu’ils sont activés, sont traités par le prestataire de paiement indiqué. Une demande d’annulation ou de remboursement est examinée selon l’état de la commande, les conditions acceptées entre les parties et les droits impératifs du consommateur. Aucun texte de la plateforme ne réduit un droit auquel la loi interdit de renoncer.</p>

    <h4 class="mt-4">9. Avis et réputation</h4>
    <p>Un avis doit relater une expérience réelle et rester pertinent. Les faux avis, contreparties non signalées, menaces, données personnelles inutiles et propos injurieux sont interdits. La plateforme peut demander des éléments de vérification, limiter la visibilité ou retirer un avis en motivant sa décision.</p>

    <h4 class="mt-4">10. Modération, restriction et recours</h4>
    <p>La plateforme peut refuser, déréférencer ou retirer un contenu, limiter une fonctionnalité, suspendre ou fermer un compte en cas de violation, fraude, risque pour les utilisateurs ou obligation légale. La mesure est proportionnée au risque lorsque les circonstances le permettent. L’utilisateur est informé du motif et peut contester via le <a href="{{ route('contact.index') }}">support</a>, sauf interdiction légale ou impératif de sécurité.</p>

    <h4 class="mt-4">11. Responsabilité et disponibilité</h4>
    <p>Chaque utilisateur répond de ses contenus, décisions et obligations. La plateforme ne garantit pas la conclusion ou la qualité d’une prestation entre utilisateurs. Elle reste responsable de ses propres obligations et met en œuvre des moyens raisonnables de sécurité et de continuité, sans garantir un service ininterrompu.</p>

    <h4 class="mt-4">12. Propriété intellectuelle et données</h4>
    <p>L’utilisateur conserve ses droits sur ses contenus et accorde à la plateforme, pendant leur disponibilité, les droits techniques nécessaires à leur hébergement, adaptation d’affichage et diffusion dans le service. Il garantit disposer des droits requis. Les données personnelles sont traitées conformément à la politique de confidentialité.</p>

    <h4 class="mt-4">13. Fermeture du compte</h4>
    <p>L’utilisateur peut demander la fermeture de son compte, sous réserve des commandes, litiges et obligations de conservation en cours. Une adresse associée à un compte supprimé pour fraude, abus ou risque peut être bloquée afin d’empêcher une réinscription non autorisée.</p>

    <h4 class="mt-4">14. Évolution, droit applicable et litiges</h4>
    <p id="evolution">Toute nouvelle version des présentes conditions est présentée à chaque utilisateur connecté, avec un résumé des changements et sa date d’entrée en vigueur. Avant cette date, l’utilisateur peut l’accepter ou poursuivre sa navigation ; à compter de cette date, l’utilisation du compte suppose de l’avoir acceptée. L’utilisateur qui ne souhaite pas l’accepter peut demander la fermeture de son compte dans les conditions de l’article 13. La date et la version acceptées sont conservées à titre de preuve.</p>
    <p>Les changements importants sont communiqués dans un délai raisonnable. Les droits impératifs du pays de résidence du consommateur demeurent applicables. Les parties recherchent d’abord une solution amiable avec le support ; les voies de médiation et juridictions légalement compétentes restent accessibles.</p>

    @php
        $pkTermsVersion = \App\Models\TermsVersion::current();
    @endphp
    <p class="mt-5 text-muted small">@if($pkTermsVersion)<span data-terms-version>Version des conditions : {{ $pkTermsVersion->version }}, applicable à partir du {{ $pkTermsVersion->effective_at->format('d/m/Y') }}.</span>@else <span data-terms-updated>Dernière mise à jour des conditions : {{ config('legal.terms_updated') }}.</span>@endif
    Dernière mise à jour de l’identité de l’exploitant : {{ config('legal.last_updated') }}.</p>
</div></div>
</div></div></div>
@endsection

@extends('layouts.app')
@section('title', 'Conditions de l’Espace PRO - ' . config('app.name', 'ProxiPro'))
@section('content')
<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-9"><div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
    <h1>Conditions de l’Espace PRO</h1>
    <p class="lead text-muted">Version {{ config('legal.pro_terms_version') }} — devis, factures, clients et outils professionnels.</p>

    <h4 class="mt-4">1. Accès</h4>
    <p>Le CRM peut être proposé aux prestataires autorisés. L’émission officielle de devis et factures est réservée à un compte professionnel dont le profil est complet, vérifié, immatriculé et ayant accepté la version en vigueur. Avant cela, les documents restent des brouillons non contractuels. La souscription d’une offre commerciale ne transforme jamais un particulier en professionnel et ne remplace aucun justificatif d’immatriculation.</p>

    <h4 class="mt-4">2. Exactitude des informations</h4>
    <p>Le professionnel garantit l’exactitude de sa raison sociale, de son immatriculation, de son adresse, de sa TVA, de ses assurances et des informations client. Il les actualise avant toute émission. Les informations de l’émetteur sont figées dans le document au moment où celui-ci reçoit son numéro définitif.</p>

    <h4 class="mt-4">3. Devis</h4>
    <p>Le professionnel renseigne l’objet, les prestations ou biens, quantités, prix, taxes, durée de validité, lieu d’exécution, frais éventuels, caractère gratuit ou payant du devis et acompte. Il conserve la preuve de l’acceptation du client et respecte les règles propres à son secteur et au consommateur.</p>

    <h4 class="mt-4">4. Factures</h4>
    <p>Une facture reçoit un numéro chronologique définitif lors de sa finalisation ou de son envoi. Elle ne peut ensuite être modifiée ou supprimée depuis l’outil ; une correction doit être matérialisée par un avoir ou un document rectificatif approprié. Le professionnel vérifie les mentions fiscales et commerciales exigées dans son pays.</p>

    <h4 class="mt-4">5. Facturation électronique</h4>
    <p>Le PDF généré facilite les échanges mais ne constitue pas, à lui seul, une plateforme agréée de facturation électronique ni un flux structuré. Le professionnel utilise les dispositifs officiels ou partenaires exigés par la réglementation et conserve ses originaux selon les durées applicables.</p>

    <h4 class="mt-4">6. Données des clients</h4>
    <p>Le carnet de clients est réservé aux besoins professionnels légitimes du titulaire. Il est interdit d’y importer des données obtenues illicitement, de prospecter sans base légale ou de partager les coordonnées hors des finalités annoncées. Le professionnel répond aux droits exercés directement auprès de lui pour les traitements dont il est responsable.</p>

    <h4 class="mt-4">7. Abonnement, disponibilité et export</h4>
    <p>Les fonctionnalités incluses, limites, prix et périodicité mensuelle ou annuelle sont affichés avant souscription. Lorsque l’offre récurrente est activée, le paiement par Stripe déclenche un renouvellement automatique à chaque échéance. L’utilisateur peut arrêter ce renouvellement depuis la page « Abonnement & Points » ; son accès payé reste alors ouvert jusqu’à la fin de la période en cours. Une résiliation déjà arrivée à terme nécessite une nouvelle souscription.</p>
    <p>Une modification tarifaire dans l’administration s’applique aux nouvelles souscriptions ; elle ne modifie pas silencieusement le prix Stripe d’un abonnement déjà créé. Toute évolution appliquée à un renouvellement existant doit être annoncée conformément au droit applicable. Les remboursements, droits de rétractation et exceptions restent régis par les dispositions impératives applicables au titulaire.</p>
    <p>L’utilisateur exporte régulièrement ses documents et ne confie pas à la plateforme son unique copie comptable. La suspension d’un accès n’efface pas les obligations de paiement ou de conservation déjà nées.</p>

    <h4 class="mt-4">8. Contrôles et suspension</h4>
    <p>L’accès peut être limité si les informations expirent, si le statut professionnel n’est plus valide, en cas de fraude, d’usage illégal ou de risque pour les clients. Les brouillons peuvent rester consultables lorsque la sécurité et la loi le permettent. Une contestation peut être adressée au support.</p>

    <h4 class="mt-4">9. Portée internationale</h4>
    <p>L’outil propose une base adaptée au lancement en France et dans l’Union européenne, mais chaque professionnel doit adapter les mentions, taxes, devise, langues et procédures aux pays dans lesquels il contracte. Aucune mention automatique ne remplace l’avis d’un comptable ou conseil local.</p>

    <p class="mt-5 text-muted small">L’acceptation est horodatée et devra être renouvelée lors d’une modification substantielle.</p>
</div></div></div></div></div>
@endsection

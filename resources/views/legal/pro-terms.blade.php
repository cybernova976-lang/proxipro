@extends('layouts.app')
@section('title', 'Conditions de l’Espace PRO - ' . config('app.name', 'Prokejem'))
@section('content')
<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-9"><div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
    <h1>Conditions de l’Espace PRO</h1>
    <p class="lead text-muted">Version {{ config('legal.pro_terms_version') }} — devis, factures, clients et outils professionnels.</p>
    @include('legal.partials.operator')
    <p class="small text-muted">Identité de l’exploitant mise à jour le {{ config('legal.last_updated') }}.</p>

    <h4 class="mt-4">1. Accès et documents offerts</h4>
    <p>Tout membre connecté, particulier ou professionnel, peut créer, émettre et télécharger {{ \App\Services\CommercialDocumentAllowance::FREE['quote'] }} devis et {{ \App\Services\CommercialDocumentAllowance::FREE['invoice'] }} factures sans frais, sans fournir de justificatif. Sans Premium actif (abonnement ou accès à paiement unique), le PDF d’un devis, d’une facture ou d’un avoir émis porte le filigrane « Prokejem · version gratuite » et une mention en pied de page ; un document émis pendant une période Premium n’en porte pas. Au-delà, la création et l’émission de nouveaux documents nécessitent un Premium actif (abonnement ou accès à paiement unique), lorsqu’il est proposé à la vente. Les avoirs qui corrigent une facture déjà émise ne sont pas décomptés. Le carnet de clients et le suivi des demandes peuvent être réservés aux prestataires.</p>
    <p>Le membre qui émet un document en est seul responsable : exactitude de son contenu, des montants et des informations du client, et respect des mentions légales qui s’imposent à lui. S’il exerce à titre professionnel, il lui appartient notamment d’y faire figurer son numéro d’immatriculation (SIRET en France), son régime de TVA et, le cas échéant, son assurance professionnelle. Prokejem fournit l’outil de création ; il n’est pas partie aux devis et factures échangés entre le membre et ses clients et ne vérifie pas leur conformité. Ni l’utilisation de l’outil ni la souscription d’un abonnement ne confèrent la qualité de professionnel ou ne remplacent une immatriculation.</p>

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
    <p>Les fonctionnalités incluses, limites, prix et périodicité mensuelle sont affichés avant souscription. Lorsque l’offre récurrente est activée, le paiement par Stripe déclenche un renouvellement automatique à chaque échéance. L’utilisateur peut arrêter ce renouvellement depuis la page « Abonnement et factures » ; son accès payé reste alors ouvert jusqu’à la fin de la période en cours. Une résiliation déjà arrivée à terme nécessite une nouvelle souscription.</p>
    @if(\App\Support\ProviderSubscriptionPlans::oneTimeSentence())
        <p id="acces-paiement-unique">Lorsqu’il est proposé à la vente, Premium peut aussi être pris sans abonnement, par un paiement unique par carte : {{ \App\Support\ProviderSubscriptionPlans::oneTimeSentence() }}, au prix affiché avant paiement. Cet accès ouvre, pendant la durée payée, les mêmes fonctionnalités que l’abonnement Premium. Il n’est ni reconduit ni renouvelé : aucun prélèvement n’a lieu ensuite et l’accès prend fin de lui-même à l’échéance, sans démarche de résiliation. Un nouvel achat effectué avant cette échéance prolonge l’accès à compter de sa date de fin. Un accès à paiement unique ne se cumule pas avec un abonnement en cours.</p>
    @endif
    {{-- La règle de rétractation reste lisible même quand les accès ne sont plus en vente : elle vaut pour les achats déjà faits. --}}
        <p id="retractation-acces-unique">Le titulaire qui achète un accès à paiement unique en tant que consommateur dispose d’un délai de rétractation de quatorze jours à compter de son achat, sans avoir à motiver sa décision (articles L221-18 et suivants du Code de la consommation). L’accès s’ouvrant dès le paiement, avant la fin de ce délai, le titulaire doit en faire la demande expresse en cochant, avant de payer, la case prévue à cet effet ; cette demande est horodatée. S’il se rétracte après l’ouverture de son accès, il informe Prokejem de sa décision par une déclaration dénuée d’ambiguïté, notamment par le formulaire de contact, avant la fin du délai : il est alors remboursé du prix payé, diminué d’un montant proportionnel à la période déjà écoulée jusqu’à la communication de sa décision (article L221-25), au plus tard quatorze jours après celle-ci et par le même moyen de paiement. Son accès Premium prend fin à la date de sa rétractation. L’accès étant fourni pendant toute la durée payée, il n’est pas pleinement exécuté avant la fin du délai : le droit de rétractation n’est donc pas perdu par la seule ouverture de l’accès (article L221-28).</p>
    <p>Une modification tarifaire dans l’administration s’applique aux nouvelles souscriptions et aux nouveaux achats ; elle ne modifie pas silencieusement le prix Stripe d’un abonnement déjà créé ni celui d’un accès déjà payé. Toute évolution appliquée à un renouvellement existant doit être annoncée conformément au droit applicable. Pour l’abonnement comme pour l’accès à paiement unique, les remboursements, droits de rétractation et exceptions restent régis par les dispositions impératives applicables au titulaire.</p>
    <p>L’utilisateur exporte régulièrement ses documents et ne confie pas à la plateforme son unique copie comptable. La suspension d’un accès n’efface pas les obligations de paiement ou de conservation déjà nées.</p>

    <h4 class="mt-4">8. Contrôles et suspension</h4>
    <p>L’accès peut être limité si les informations expirent, si le statut professionnel n’est plus valide, en cas de fraude, d’usage illégal ou de risque pour les clients. Les brouillons peuvent rester consultables lorsque la sécurité et la loi le permettent. Une contestation peut être adressée au support.</p>

    <h4 class="mt-4">9. Portée internationale</h4>
    <p>L’outil propose une base adaptée au lancement en France et dans l’Union européenne, mais chaque professionnel doit adapter les mentions, taxes, devise, langues et procédures aux pays dans lesquels il contracte. Aucune mention automatique ne remplace l’avis d’un comptable ou conseil local.</p>

    <p class="mt-5 text-muted small">L’acceptation est horodatée et devra être renouvelée lors d’une modification substantielle.</p>
</div></div></div></div></div>
@endsection

@extends('layouts.app')

@section('title', 'Politique de confidentialité - ' . config('app.name', 'Lunamars'))
@section('meta_description', 'Comment ' . config('app.name', 'Lunamars') . ' collecte, utilise et protège vos données personnelles.')

@section('content')
<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-8">
    <nav aria-label="breadcrumb" class="mb-3"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li><li class="breadcrumb-item active">Confidentialité</li></ol></nav>
    <div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
        <h1 class="mb-4">Politique de confidentialité</h1>

        <h4 class="mt-4">1. Responsable et contact</h4>
        <p>Le responsable du traitement est {{ \App\Support\PlatformFeatures::legalValue('legal_entity_name', 'entity_name') ?: 'l’entité éditrice indiquée dans les mentions légales' }}. Pour exercer vos droits, utilisez le <a href="{{ route('contact.index') }}">formulaire de contact</a>@if(config('legal.privacy_contact')) ou écrivez à <a href="mailto:{{ config('legal.privacy_contact') }}">{{ config('legal.privacy_contact') }}</a>@endif.</p>

        <h4 class="mt-4">2. Données traitées</h4>
        <ul>
            <li>compte et profil : identité, coordonnées, photo, profession, zone d’activité et préférences ;</li>
            <li>contenus et échanges : annonces, propositions, messages, commentaires, avis, signalements et pièces jointes ;</li>
            <li>vérification : documents d’identité ou professionnels transmis et état de leur contrôle ;</li>
            <li>transactions : références de commande, montants, statut du paiement et identifiants fournis par Stripe — la plateforme ne conserve pas les numéros complets de carte ;</li>
            <li>données techniques et de sécurité : adresse IP, journaux, appareil, session et cookies strictement nécessaires ;</li>
            <li>localisation saisie ou, avec votre autorisation, position utilisée pour une recherche de proximité.</li>
        </ul>

        <h4 class="mt-4">3. Finalités et bases juridiques</h4>
        <ul>
            <li><strong>Exécution du service :</strong> compte, profil, annonces, recherche, messagerie, commandes, devis, factures et support ;</li>
            <li><strong>Obligations légales :</strong> comptabilité, réponse aux autorités, lutte contre certaines fraudes et gestion des droits ;</li>
            <li><strong>Intérêt légitime :</strong> sécurité, prévention des abus, preuve, modération et amélioration non intrusive du service, après mise en balance des intérêts ;</li>
            <li><strong>Consentement :</strong> localisation précise, communications facultatives et cookies non nécessaires lorsqu’ils sont activés.</li>
        </ul>

        <h4 class="mt-4">4. Destinataires</h4>
        <p>Les données utiles au profil ou à une annonce sont visibles selon vos paramètres de confidentialité. Elles peuvent aussi être transmises aux prestataires indispensables au service, notamment l’hébergeur, le service d’envoi d’e-mails, Stripe pour les paiements et les autorités légalement habilitées. Elles ne sont pas vendues.</p>

        <h4 class="mt-4">5. Durées de conservation</h4>
        <div class="table-responsive"><table class="table table-sm align-middle">
            <thead><tr><th>Données</th><th>Durée de référence</th></tr></thead>
            <tbody>
                <tr><td>Compte et profil</td><td>Pendant la relation, puis jusqu’à 3 ans après la dernière activité ou la fermeture, sauf preuve ou obligation plus longue.</td></tr>
                <tr><td>Annonces, messages et support</td><td>Pendant l’utilisation puis jusqu’à 3 ans pour la sécurité, la preuve et les litiges.</td></tr>
                <tr><td>Commandes, paiements et pièces comptables</td><td>Durée légale comptable ou fiscale applicable, pouvant atteindre 10 ans.</td></tr>
                <tr><td>Pièces de vérification</td><td>Accès fortement limité et suppression après la décision dès qu’elles ne sont plus nécessaires ; le résultat du contrôle peut être conservé pour la preuve et la prévention de la fraude.</td></tr>
                <tr><td>Journaux de sécurité et adresses IP</td><td>En principe 12 mois, sauf incident, fraude ou obligation légale.</td></tr>
                <tr><td>Consentements et oppositions</td><td>Pendant la durée nécessaire à prouver et respecter le choix.</td></tr>
            </tbody>
        </table></div>
        <p class="small text-muted">Une conservation peut être prolongée dans une archive à accès restreint pour respecter une obligation ou défendre un droit, puis les données sont supprimées ou anonymisées.</p>

        <h4 class="mt-4">6. Vos droits</h4>
        <p>Vous pouvez demander l’accès, la rectification, l’effacement, la limitation, l’opposition ou la portabilité lorsque ces droits s’appliquent, retirer un consentement et définir les suites applicables après votre décès lorsque le droit national le prévoit. Une réponse est apportée en principe sous un mois ; une preuve d’identité peut être demandée en cas de doute raisonnable. Vous pouvez aussi introduire une réclamation auprès de l’autorité de contrôle compétente, notamment la CNIL en France.</p>

        <h4 class="mt-4">7. Sécurité et transferts</h4>
        <p>Des mesures organisationnelles et techniques protègent les accès, sessions, documents et paiements. Lorsqu’un prestataire traite des données hors de l’Espace économique européen, le transfert repose sur une décision d’adéquation ou des garanties reconnues, telles que les clauses contractuelles types, avec les mesures complémentaires nécessaires.</p>

        <h4 class="mt-4">8. Classement et décisions automatisées</h4>
        <p>Des systèmes peuvent classer les annonces, détecter les doublons ou signaler un risque à partir de critères décrits dans les <a href="{{ route('legal.platform-rules') }}#classement">règles de la marketplace</a>. Une restriction importante peut être contestée auprès du support. La plateforme ne prend pas de décision produisant un effet juridique uniquement sur la base d’un profilage sans appliquer les garanties requises.</p>

        <p class="mt-5 text-muted small">Dernière mise à jour : {{ config('legal.last_updated') ?: date('d/m/Y') }}</p>
    </div></div>
</div></div></div>
@endsection

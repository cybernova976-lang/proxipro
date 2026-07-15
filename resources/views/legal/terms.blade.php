@extends('layouts.app')

@section('title', 'Conditions d’utilisation - ' . config('app.name', 'ProxiPro'))
@section('meta_description', 'Règles d’utilisation de la plateforme ' . config('app.name', 'ProxiPro') . '.')

@section('content')
<div class="container py-5"><div class="row justify-content-center"><div class="col-lg-8">
    <nav aria-label="breadcrumb" class="mb-3"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li><li class="breadcrumb-item active">Conditions d’utilisation</li></ol></nav>
    <div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
        <h1 class="mb-4">Conditions d’utilisation</h1>

        <h4 class="mt-4">1. Objet et acceptation</h4>
        <p>{{ config('app.name', 'ProxiPro') }} met en relation des particuliers et des prestataires. En créant un compte ou en utilisant une fonctionnalité réservée, vous acceptez les présentes conditions et vous vous engagez à fournir des informations exactes.</p>

        <h4 class="mt-4">2. Rôle de la plateforme</h4>
        <p>La plateforme facilite la recherche, les propositions, la messagerie et, lorsque disponible, le paiement. Elle n’est ni l’employeur, ni le mandataire, ni le fournisseur de la prestation proposée par un utilisateur. Chaque partie vérifie les qualifications, assurances, autorisations, obligations fiscales et conditions utiles avant de s’engager.</p>

        <h4 class="mt-4">3. Comptes et vérification</h4>
        <p>Vous êtes responsable de la confidentialité de votre compte et devez signaler tout accès suspect. Un badge de vérification signifie uniquement que les éléments demandés ont été contrôlés au moment de l’examen ; il ne garantit ni les compétences, ni le comportement futur, ni la qualité d’une prestation.</p>

        <h4 class="mt-4">4. Annonces et propositions</h4>
        <p>Les annonces et propositions doivent être loyales, suffisamment précises et licites. Sont notamment interdits les contenus trompeurs, discriminatoires, dangereux, contrefaisants, portant atteinte à la vie privée ou proposant des activités réglementées sans droit de les exercer. La plateforme peut masquer ou retirer un contenu et suspendre un compte en cas de risque ou de violation.</p>

        <h4 class="mt-4">5. Commandes, prix et paiement</h4>
        <p>L’acceptation d’une proposition crée une commande entre le client et le prestataire. Le récapitulatif précise le prix et les frais applicables avant paiement. Les paiements en ligne sont traités par Stripe. Les fonds, remboursements, annulations et éventuelles commissions suivent le statut de la commande et les informations présentées au moment de la validation.</p>

        <h4 class="mt-4">6. Exécution, annulation et litiges</h4>
        <p>Les utilisateurs conviennent directement du périmètre, du calendrier et des conditions d’intervention. En cas de problème, ils doivent conserver les échanges dans la plateforme et utiliser le mécanisme de litige ou le <a href="{{ route('contact.index') }}">formulaire de contact</a>. Les règles détaillées d’annulation, de remboursement et de médiation doivent être validées et publiées avant le lancement commercial.</p>

        <h4 class="mt-4">7. Avis</h4>
        <p>Les avis affichés comme avis de mission sont liés à une commande terminée. Ils doivent décrire une expérience réelle, sans propos injurieux ni données personnelles inutiles. Ils peuvent être modérés lorsqu’ils enfreignent ces règles.</p>

        <h4 class="mt-4">8. Responsabilité</h4>
        <p>Chaque utilisateur répond de ses actes, contenus et obligations. Dans les limites permises par la loi, la plateforme ne répond pas de la qualité ou de l’inexécution d’une prestation conclue entre utilisateurs, mais elle reste responsable de ses propres obligations légales et de son service technique.</p>

        <h4 class="mt-4">9. Données et propriété intellectuelle</h4>
        <p>Vous conservez vos droits sur vos contenus et accordez à la plateforme les droits techniques nécessaires pour les héberger et les afficher pendant leur publication. Consultez la <a href="{{ route('legal.privacy') }}">politique de confidentialité</a> pour les traitements de données.</p>

        <h4 class="mt-4">10. Évolution et droit applicable</h4>
        <p>Les modifications substantielles sont portées à la connaissance des utilisateurs. Le droit applicable, la juridiction compétente et, le cas échéant, le dispositif de médiation doivent être complétés par l’éditeur selon son lieu d’établissement et les publics servis avant le lancement commercial.</p>

        <p class="mt-5 text-muted small">Dernière mise à jour : {{ config('legal.last_updated') ?: date('d/m/Y') }}</p>
    </div></div>
</div></div></div>
@endsection

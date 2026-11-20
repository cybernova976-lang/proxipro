<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Devis et factures en ligne pour les professionnels — {{ config('app.name', 'Prokejem') }}</title>
    <meta name="description" content="Dans l’espace Pro de Prokejem, un professionnel crée ses devis et ses factures, les envoie par e-mail et fait accepter un devis en ligne par son client. Sans crédits à acheter.">
    <link rel="canonical" href="{{ route('quote-tool.landing') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @include('partials.pwa-head')
    <link rel="stylesheet" href="{{ asset('css/quote-landing.css') }}?v={{ filemtime(public_path('css/quote-landing.css')) }}">
</head>
<body class="pk-docs">
@php
    // Ce que l'espace Pro fait réellement, sans abonnement puis avec Premium.
    $pkIncluded = [
        ['Devis et factures numérotés', 'Chaque document reçoit son numéro définitif à l’émission, dans une suite continue. Un brouillon reste modifiable jusque-là.'],
        ['Devis accepté en ligne', 'Votre client reçoit un lien, lit le devis sans créer de compte, puis l’accepte ou le refuse. Sa réponse est datée et figure sur le PDF.'],
        ['PDF et envoi par e-mail', 'Vous téléchargez le document ou vous l’envoyez à votre client depuis sa fiche, avec le PDF en pièce jointe.'],
        ['Acompte et facture du devis', 'Un devis accepté se transforme en facture préremplie. S’il prévoit un acompte, vous le facturez à part ; la facture finale le déduit.'],
        ['Avoirs', 'Une facture émise ne se modifie pas : vous la corrigez ou l’annulez par un avoir, total ou partiel.'],
        ['Suivi des paiements', 'Une facture envoyée et échue passe « En retard » toute seule. Vos montants en attente et encaissés sont calculés pour vous.'],
    ];
    $pkPremium = [
        'Modèles de devis réutilisables',
        'Relance automatique des devis sans réponse',
        'Votre logo sur vos devis et vos factures',
        'Rappel automatique de vos factures impayées',
    ];
@endphp
<header class="pk-docs__header">
    <nav class="pk-docs__wrap pk-docs__nav" aria-label="Navigation principale">
        <a class="pk-docs__brand" href="{{ route('homepage') }}"><x-brand-mark variant="wordmark" :size="36" :decorative="false" /></a>
        @guest
            <a class="pk-docs__link" href="{{ route('login') }}">Connexion</a>
        @else
            <a class="pk-docs__link" href="{{ route('feed') }}">Mon accueil</a>
        @endguest
    </nav>
</header>
<main class="pk-docs__wrap pk-docs__main">
    <section class="pk-docs__hero" aria-labelledby="pk-docs-title">
        <span class="pk-docs__eyebrow">Espace Pro</span>
        <h1 id="pk-docs-title">Vos devis et vos factures, sans crédits à acheter.</h1>
        <p class="pk-docs__lead">Les devis et les factures font partie de l’espace Pro de Prokejem. Ils sont réservés aux comptes professionnels, et ne demandent pas d’abonnement.</p>
        @guest
            <a class="pk-docs__button" href="{{ route('register', ['continue' => 'provider']) }}">Créer mon compte professionnel</a>
        @else
            <a class="pk-docs__button" href="{{ route('pro.dashboard') }}">Ouvrir mon espace Pro</a>
        @endguest
        <p class="pk-docs__note">L’activation d’un profil prestataire comprend un contrôle d’identité, gratuit. <a href="{{ route('public.provider-offers') }}">Voir les offres et tarifs</a>.</p>
    </section>

    <section aria-labelledby="pk-docs-included">
        <h2 id="pk-docs-included">Ce que vous faites sans abonnement</h2>
        <ul class="pk-docs__grid">
            @foreach($pkIncluded as [$pkTitle, $pkText])
                <li class="pk-docs__card">
                    <h3>{{ $pkTitle }}</h3>
                    <p>{{ $pkText }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="pk-docs__premium" aria-labelledby="pk-docs-premium">
        <h2 id="pk-docs-premium">Ce que Premium ajoute</h2>
        <ul>
            @foreach($pkPremium as $pkLine)
                <li>{{ $pkLine }}</li>
            @endforeach
        </ul>
        <p><a href="{{ route('public.provider-offers') }}">Comparer le gratuit, les options à la carte et Premium</a></p>
    </section>

    <section class="pk-docs__terms" aria-labelledby="pk-docs-terms">
        <h2 id="pk-docs-terms">Avant de commencer</h2>
        <p>Pour émettre un devis ou une facture, la checklist de conformité de l’espace Pro doit être terminée : statut professionnel déclaré, profil complet avec le badge « Profil vérifié », raison sociale, numéro d’immatriculation valide, adresse professionnelle et conditions de l’espace Pro acceptées. En attendant, vos documents restent en brouillon.</p>
        <p>Le formulaire demande les mentions utiles à une facture (conditions de paiement, pénalités de retard pour un client professionnel, mention d’exonération de TVA). Vous restez responsable du contenu de vos documents : faites-les relire par votre comptable en cas de doute.</p>
        <p><a href="{{ route('legal.pro-terms') }}">Consulter les conditions de l’espace Pro</a></p>
    </section>
</main>
<footer class="pk-docs__footer">
    <div class="pk-docs__wrap pk-docs__footer-links">
        <a href="{{ route('legal.mentions') }}">Mentions légales</a>
        <a href="{{ route('legal.platform-rules') }}">Règles de la marketplace</a>
        <a href="{{ route('contact.index') }}">Contact</a>
    </div>
</footer>
</body>
</html>

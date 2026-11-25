<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Devis et factures en ligne, 2 de chaque offerts — {{ config('app.name', 'Prokejem') }}</title>
    <meta name="description" content="Dans l’espace Pro de Prokejem, chaque membre, particulier ou professionnel, crée 2 devis et 2 factures offerts, sans justificatif. Les envoyer par e-mail et faire accepter un devis en ligne. Sans crédits à acheter.">
    <link rel="canonical" href="{{ route('quote-tool.landing') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @include('partials.pwa-head')
    <link rel="stylesheet" href="{{ asset('css/quote-landing.css') }}?v={{ filemtime(public_path('css/quote-landing.css')) }}">
</head>
<body class="pk-docs">
@php
    // Ce que l'espace Pro fait réellement, avec les documents offerts puis avec Premium.
    // Quota : App\Services\CommercialDocumentAllowance (décision du fondateur du 24/11/2026).
    $pkFreeQuotes = \App\Services\CommercialDocumentAllowance::FREE['quote'];
    $pkFreeInvoices = \App\Services\CommercialDocumentAllowance::FREE['invoice'];
    // Premium n'est promis que s'il peut être souscrit ; le quota s'applique dans les deux cas.
    $pkSubscriptionsOpen = $proSubscriptionsEnabled ?? \App\Support\PlatformFeatures::proSubscriptionsEnabled();
    // Version gratuite : les PDF portent un filigrane, que Premium retire (décision du 25/11/2026).
    $pkWatermarkNote = 'Leurs PDF portent le filigrane « Prokejem · version gratuite »'
        .($pkSubscriptionsOpen ? ' ; Premium le retire.' : '.');
    $pkIncluded = [
        ['Devis et factures numérotés', 'Chaque document reçoit son numéro définitif à l’émission, dans une suite continue. Un brouillon reste modifiable jusque-là.'],
        ['Devis accepté en ligne', 'Votre client reçoit un lien, lit le devis sans créer de compte, puis l’accepte ou le refuse. Sa réponse est datée et figure sur le PDF.'],
        ['PDF et envoi par e-mail', 'Vous téléchargez le document ou vous l’envoyez à votre client depuis sa fiche, avec le PDF en pièce jointe.'],
        ['Acompte et facture du devis', 'Un devis accepté se transforme en facture préremplie. S’il prévoit un acompte, vous le facturez à part ; la facture finale le déduit.'],
        ['Avoirs', 'Une facture émise ne se modifie pas : vous la corrigez ou l’annulez par un avoir, total ou partiel.'],
        ['Suivi des paiements', 'Une facture envoyée et échue passe « En retard » toute seule. Vos montants en attente et encaissés sont calculés pour vous.'],
    ];
    $pkPremium = [
        'Devis et factures sans limite, au-delà des '.$pkFreeQuotes.' devis et '.$pkFreeInvoices.' factures offerts',
        'Vos devis et vos factures sans le filigrane de la version gratuite',
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
        <p class="pk-docs__lead">{{ $pkFreeQuotes }} devis et {{ $pkFreeInvoices }} factures offerts, sans justificatif : particulier ou professionnel, il suffit d’un compte Prokejem.
            @if($pkSubscriptionsOpen)
                Si l’outil vous plaît, l’abonnement Premium lève la limite.
            @else
                Au-delà, l’abonnement Premium lèvera la limite : il n’est pas encore ouvert.
            @endif
        </p>
        @guest
            <a class="pk-docs__button" href="{{ route('register') }}">Créer mon compte</a>
        @else
            <a class="pk-docs__button" href="{{ route('pro.quotes') }}">Créer mes devis et factures</a>
        @endguest
        <p class="pk-docs__note">Ni SIRET, ni pièce justificative pour vos documents offerts. {{ $pkWatermarkNote }} <a href="{{ route('public.provider-offers') }}">Voir les offres et tarifs</a>.</p>
    </section>

    <section aria-labelledby="pk-docs-included">
        <h2 id="pk-docs-included">Ce que comprennent vos documents offerts</h2>
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
        <h2 id="pk-docs-premium">{{ $pkSubscriptionsOpen ? 'Ce que Premium ajoute' : 'Ce que Premium ajoutera' }}</h2>
        @unless($pkSubscriptionsOpen)
            <p data-pk-premium-closed>{{ \App\Services\CommercialDocumentAllowance::SUBSCRIPTIONS_CLOSED }}</p>
        @endunless
        <ul>
            @foreach($pkPremium as $pkLine)
                <li>{{ $pkLine }}</li>
            @endforeach
        </ul>
        <p><a href="{{ route('public.provider-offers') }}">Comparer le gratuit, les options à la carte et Premium</a></p>
    </section>

    <section class="pk-docs__terms" aria-labelledby="pk-docs-terms">
        <h2 id="pk-docs-terms">Avant de commencer</h2>
        <p>Aucun justificatif n’est demandé pour vos {{ $pkFreeQuotes }} devis et {{ $pkFreeInvoices }} factures offerts. Chaque document reprend les informations de votre compte telles que vous les avez renseignées : votre nom ou votre raison sociale, vos coordonnées, et votre SIRET seulement si vous l’avez indiqué. Aucun numéro n’est ajouté à votre place.</p>
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

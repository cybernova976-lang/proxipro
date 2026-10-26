<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offres et tarifs prestataires — {{ config('app.name', 'Prokejem') }}</title>
    <meta name="description" content="Découvrez les coûts avant de créer votre profil prestataire : inscription, contrôle d’identité, options de visibilité et offres PRO disponibles sur PROKEJEM.">
    <link rel="canonical" href="{{ route('public.provider-offers') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @include('partials.pwa-head')
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f7f9fc; color: #475569; font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; line-height: 1.65; }
        a { color: #4338ca; text-underline-offset: 3px; }
        a:focus-visible { outline: 3px solid #818cf8; outline-offset: 4px; }
        .offers-wrap { max-width: 1120px; margin: 0 auto; padding: 0 24px; }
        .offers-header { background: white; border-bottom: 1px solid #e2e8f0; }
        .offers-nav { display: flex; align-items: center; justify-content: space-between; gap: 20px; min-height: 76px; }
        .offers-brand img, .offers-brand svg { display: block; max-width: 180px; height: auto; }
        .offers-nav-links { display: flex; align-items: center; gap: 22px; }
        .offers-nav-links a { min-height: 44px; display: inline-flex; align-items: center; text-decoration: none; font-weight: 650; font-size: .9rem; }
        .offers-main { padding-top: 42px; padding-bottom: 48px; }
        .offers-back { display: inline-flex; align-items: center; min-height: 44px; font-size: .9rem; }
        .offers-intro { max-width: 750px; margin: 16px 0 30px; }
        .offers-eyebrow { display: block; color: #6d5bb9; font-size: .76rem; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; }
        h1, h2, h3 { color: #0f172a; line-height: 1.2; }
        h1 { font-size: clamp(2rem, 4.3vw, 3rem); letter-spacing: -1.4px; margin: 12px 0 18px; }
        h2 { font-size: 1.5rem; margin: 0 0 12px; }
        h3 { font-size: 1.1rem; margin: 0 0 12px; }
        p { margin: 0 0 16px; }
        .offers-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
        .offers-card { min-width: 0; padding: 28px; background: white; border: 1px solid #e2e8f0; border-radius: 20px; overflow-wrap: anywhere; }
        .offers-price { display: block; color: #4338ca; font-size: 2rem; font-weight: 800; line-height: 1.3; margin: 0 0 16px; }
        .offers-price small { font-size: .9rem; font-weight: 500; color: #64748b; }
        .offers-card ul { padding-left: 19px; margin: 0; }
        .offers-card li { margin: 8px 0; }
        .offers-section { margin-top: 34px; }
        .offers-section > p { max-width: 800px; }
        .offers-note { padding: 20px 24px; border: 1px solid #dce6f3; border-radius: 16px; background: #eef4fc; }
        .offers-pack-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .offers-pack { border: 1px solid #e2e8f0; border-radius: 14px; background: white; padding: 18px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .offers-pack strong { color: #1e293b; }
        .offers-pack span { color: #4338ca; font-weight: 750; white-space: nowrap; }
        .offers-small { color: #64748b; font-size: .88rem; }
        .offers-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 14px; margin-top: 28px; }
        .offers-button { display: inline-flex; align-items: center; justify-content: center; min-height: 50px; padding: 12px 22px; border-radius: 12px; background: #4f46e5; color: white; font-weight: 750; text-decoration: none; }
        .offers-button:hover { background: #4338ca; }
        .offers-footer { background: white; border-top: 1px solid #e2e8f0; padding: 24px 0; }
        .offers-footer-links { display: flex; flex-wrap: wrap; gap: 12px 24px; }
        .offers-footer a { min-height: 44px; display: inline-flex; align-items: center; font-size: .88rem; }
        @media (max-width: 767px) {
            .offers-wrap { padding-inline: 20px; }
            .offers-nav { min-height: 68px; gap: 12px; }
            .offers-brand img, .offers-brand svg { max-width: 140px; }
            .offers-nav-links { gap: 12px; }
            .offers-nav-links .offers-home-link { display: none; }
            .offers-main { padding-top: 20px; padding-bottom: 32px; }
            .offers-grid { grid-template-columns: minmax(0, 1fr); gap: 14px; }
            .offers-card { padding: 22px; }
            .offers-pack-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
            .offers-pack { display: block; padding: 14px; }
            .offers-pack span { display: block; margin-top: 6px; }
            .offers-actions { align-items: stretch; flex-direction: column; }
            .offers-actions > a { text-align: center; min-height: 44px; }
        }
    </style>
</head>
<body>
<header class="offers-header">
    <nav class="offers-wrap offers-nav" aria-label="Navigation principale">
        <a class="offers-brand" href="{{ route('homepage') }}"><x-brand-mark variant="wordmark" :size="36" :decorative="false" /></a>
        <div class="offers-nav-links"><a class="offers-home-link" href="{{ route('homepage') }}">Accueil</a><a href="{{ route('login') }}">Connexion</a></div>
    </nav>
</header>
<main class="offers-wrap offers-main" id="offers-main">
    <a class="offers-back" href="{{ route('homepage') }}">← Retour à l’accueil</a>
    <div class="offers-intro">
        <span class="offers-eyebrow">Offres prestataires</span>
        <h1>Les coûts, avant de vous lancer.</h1>
        <p>Créez votre compte, préparez votre profil et choisissez les options utiles à votre activité. Voici ce qui est gratuit et ce qui peut être payant.</p>
    </div>
    <div class="offers-grid">
        <section class="offers-card" aria-labelledby="offers-account">
            <h2 id="offers-account">Commencer sur PROKEJEM</h2>
            <span class="offers-price">Gratuit</span>
            <ul><li>Créer votre compte et renseigner votre profil.</li><li>Consulter les annonces et les profils publics.</li><li>Publier une demande de service.</li></ul>
        </section>
        <section class="offers-card" aria-labelledby="offers-identity">
            <h2 id="offers-identity">Activer votre profil prestataire</h2>
            <span class="offers-price">{{ number_format((float) $verificationPrice, 2, ',', ' ') }} € <small>pour le contrôle d’identité du profil</small></span>
            <p>Le parcours d’activation prestataire demande un profil complété et le badge « Profil vérifié ».</p>
            <p class="offers-small">Le contrôle d’identité se règle par carte, séparément des points et des abonnements. Le paiement ne remplace pas la vérification des pièces.</p>
        </section>
    </div>

    <section class="offers-section" aria-labelledby="offers-pro">
        <h2 id="offers-pro">Les offres PRO</h2>
        @if($subscriptionPlans !== [])
            <p>Les abonnements donnent accès aux fonctions PRO indiquées dans chaque offre. Les conditions, le renouvellement et le paiement sont présentés dans votre compte avant la souscription.</p>
            <div class="offers-grid">
                @foreach($subscriptionPlans as $plan)
                    <article class="offers-card">
                        <h3>{{ $plan['label'] }}</h3>
                        <span class="offers-price">{{ number_format((float) $plan['amount'], 2, ',', ' ') }} € <small>{{ $plan['period'] ?? '' }}</small></span>
                        <p>{{ $plan['description'] ?? '' }}</p>
                        <ul>@foreach($plan['features'] ?? [] as $feature)<li>{{ $feature }}</li>@endforeach</ul>
                    </article>
                @endforeach
            </div>
        @else
            <p class="offers-note">Les abonnements PRO ne sont pas proposés à la souscription actuellement. Le contrôle d’identité et les éventuelles options de visibilité restent des coûts distincts.</p>
        @endif
    </section>

    <section class="offers-section" aria-labelledby="offers-points">
        <h2 id="offers-points">Les options de visibilité</h2>
        <p>Des packs de points sont proposés pour les options payantes de la plateforme. L’option choisie et son coût en points sont indiqués avant validation dans votre compte.</p>
        <div class="offers-pack-grid">
            @foreach($pointPacks as $pack)
                <div class="offers-pack"><strong>{{ $pack['points'] }} points</strong><span>{{ number_format((float) $pack['price'], 2, ',', ' ') }} €</span></div>
            @endforeach
        </div>
        <p class="offers-small" style="margin-top:14px;">Les points ne règlent pas le contrôle d’identité du profil. Une option de visibilité ne garantit ni demande, ni mission, ni revenu.</p>
    </section>

    <section class="offers-section offers-note" aria-labelledby="offers-payment">
        <h2 id="offers-payment">Et le prix de la prestation ?</h2>
        <p>Le client et le prestataire conviennent du service, du prix et des modalités avant de commencer.</p>
        <p>Pour une commande réglée via PROKEJEM, la commission de la plateforme est de <strong>{{ number_format((float) $commissionPercent, 2, ',', ' ') }} %</strong> du prix de la prestation. Elle est déduite du montant versé au prestataire. Le détail de la commande est présenté avant validation.</p>
        <p class="offers-small">Les outils de devis et factures professionnels nécessitent une identité professionnelle renseignée et les conditions PRO applicables. <a href="{{ route('legal.pro-terms') }}">Consulter les conditions de l’espace PRO</a>.</p>
    </section>
    <div class="offers-actions"><a class="offers-button" href="{{ route('register', ['continue' => 'provider']) }}">Créer mon profil prestataire →</a><a href="{{ route('contact.index') }}">Une question sur les offres ? Contactez-nous.</a></div>
</main>
<footer class="offers-footer"><div class="offers-wrap offers-footer-links"><a href="{{ route('legal.mentions') }}">Mentions légales</a><a href="{{ route('legal.platform-rules') }}">Règles de la marketplace</a><a href="{{ route('legal.pro-terms') }}">Conditions de l’espace PRO</a></div></footer>
</body>
</html>

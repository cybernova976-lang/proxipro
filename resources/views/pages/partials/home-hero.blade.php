<header class="hero" id="home-hero">
    <picture class="home-hero-media home-hero-background" aria-hidden="true">
        <img src="{{ asset('images/home/artisan-maison-panorama-1920.webp') }}" srcset="{{ asset('images/home/artisan-maison-panorama-960.webp') }} 960w, {{ asset('images/home/artisan-maison-panorama-1920.webp') }} 1920w" sizes="100vw" width="1920" height="960" alt="" fetchpriority="high" decoding="async">
    </picture>
    <div class="container hero-grid">
        <div class="home-hero-intro">
            <p class="hero-label"><span class="hero-label-dot"></span>Particuliers · Indépendants · Entreprises</p>
            <h1 class="hero-title">
                Trouvez le bon prestataire, <span class="accent">près de chez vous.</span>
            </h1>
            <p class="hero-desc">Bricolage, ménage, jardinage, cours, informatique… Trouvez de l’aide ou faites découvrir votre savoir-faire.</p>
            <nav class="home-paths" aria-label="Choisissez votre parcours">
                <a href="#home-search" class="home-path home-path-client"><i class="fas fa-search" aria-hidden="true"></i><span><strong>Je cherche un service</strong><small>Explorer les offres ou publier un besoin</small></span></a>
                <a href="{{ route('register', ['continue' => 'provider']) }}" class="home-path"><i class="fas fa-toolbox" aria-hidden="true"></i><span><strong>Je propose mes services</strong><small>Créer mon profil prestataire</small></span></a>
            </nav>
        </div>
        <div class="search-card home-discovery-card" id="home-search" role="search" aria-labelledby="home-search-title">
            <h2 class="search-card-title" id="home-search-title">Quel service recherchez-vous ?</h2>
            <form action="{{ route('ads.index') }}" method="GET" class="home-discovery-fields">
                <input type="hidden" name="service_type" value="offre">
                <div class="home-discovery-control">
                    <label class="home-field-label" for="home-service">Service recherché</label>
                    <div class="search-field"><i class="fas fa-search" aria-hidden="true"></i><input id="home-service" name="q" type="search" placeholder="Plombier, ménage, cours…" maxlength="120"></div>
                </div>
                <div class="home-discovery-control">
                    <label class="home-field-label" for="home-location">Ville ou code postal <span>(facultatif)</span></label>
                    <div class="search-field"><i class="fas fa-map-marker-alt" aria-hidden="true"></i><input id="home-location" type="text" name="location" placeholder="Votre ville" maxlength="120"></div>
                </div>
                <button type="submit" class="btn-hero-search">Voir les services <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
            </form>
            <p class="home-search-note">Ou <a href="{{ route('demand.create') }}">Publier mon besoin <i class="fas fa-arrow-right" aria-hidden="true"></i></a> gratuitement.</p>
            <p class="home-location-note">Services et profils publics, consultables sans compte.</p>
        </div>
    </div>
    <div class="container home-hero-reassurance">
        <div class="hero-trust">
            <span class="hero-trust-item"><i class="fas fa-user-shield" aria-hidden="true"></i> Identité contrôlée quand le badge est affiché</span>
            <span class="hero-trust-item"><i class="fas fa-comments" aria-hidden="true"></i> Messagerie pour échanger directement</span>
            <span class="hero-trust-item"><i class="fas fa-star" aria-hidden="true"></i> Avis liés aux missions terminées</span>
        </div>
        <p class="home-platform-role">PROKEJEM vous met en relation. Vous choisissez le prestataire et convenez ensemble de la prestation.</p>
    </div>
</header>

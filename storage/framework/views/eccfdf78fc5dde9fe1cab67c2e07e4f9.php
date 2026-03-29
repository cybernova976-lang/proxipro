

<?php $__env->startSection('title', 'Accueil - ProxiPro'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    /* =========================================
       PAGE D'ACCUEIL FEED - STYLE MARKETPLACE PRO
       ========================================= */
    :root {
        --primary: #4f46e5;
        --primary-dark: #4338ca;
        --primary-light: rgba(79, 70, 229, 0.08);
        --secondary: #7c3aed;
        --accent: #f59e0b;

        --bg-body: #f8fafc;
        --text-dark: #0f172a;
        --text-light: #64748b;
        --white: #ffffff;
        --border: #e2e8f0;
    }

    body {
        background-color: var(--bg-body);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    /* =========================================
       HERO SECTION - Compact & Modern
       ========================================= */
    .hero-shape-1 {
        position: absolute; top: 0; right: 0; width: 15rem; height: 15rem;
        border-radius: 9999px; background-color: rgba(79, 70, 229, 0.35);
        filter: blur(50px); transform: translate(5rem, -5rem);
    }
    .hero-shape-2 {
        position: absolute; bottom: 0; left: 0; width: 12rem; height: 12rem;
        border-radius: 9999px; background-color: rgba(124, 58, 237, 0.25);
        filter: blur(40px); transform: translate(-3rem, 3rem);
    }

    .hero-greeting {
        color: white;
        text-align: center;
        margin-bottom: 0.5rem;
    }
    .hero-greeting h1 {
        font-size: 1.25rem;
        font-weight: 700;
        margin: 0;
    }
    .hero-greeting h1 span {
        color: #a5b4fc;
    }
    .hero-subtitle {
        color: #c7d2fe;
        font-size: 0.85rem;
        margin-top: 0.15rem;
    }

    /* Quick category shortcuts */
    .category-shortcuts {
        background: white;
        border-bottom: 1px solid #e2e8f0;
        padding: 10px 0;
    }
    .category-shortcuts-inner {
        display: flex;
        align-items: center;
        gap: 8px;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }
    .category-shortcuts-inner::-webkit-scrollbar { display: none; }
    .cat-shortcut {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    .cat-shortcut:hover {
        background: var(--primary-light);
        border-color: var(--primary);
        color: var(--primary);
    }
    .cat-shortcut i {
        font-size: 0.75rem;
        opacity: 0.7;
    }

    /* =========================================
       BARRE DE FILTRES - Fixée au header
       ========================================= */
    .filter-bar-container {
        background: white;
        border-bottom: 1px solid #e2e8f0;
        padding: 10px 0;
        position: fixed;
        top: 90px; /* Juste en dessous du header */
        left: 0;
        right: 0;
        z-index: 999;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.98);
    }

    /* Spacer pour compenser la barre fixe */
    .filter-bar-spacer {
        height: 70px;
    }

    /* Hero section compacte repositionnée */
    .hero-section-compact {
        background: linear-gradient(160deg, #1e1b4b 0%, #312e81 40%, #4338ca 100%);
        padding: 1.2rem 0 1.5rem;
        position: relative;
        overflow: hidden;
        margin-top: 0;
    }

    .filter-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Boutons dropdown de filtre */
    .filter-dropdown {
        position: relative;
    }

    .filter-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 25px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .filter-btn:hover {
        background: #e2e8f0;
        border-color: #cbd5e1;
    }

    .filter-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
    }

    .filter-btn i {
        font-size: 0.8rem;
    }

    .filter-btn .chevron {
        margin-left: 4px;
        font-size: 0.7rem;
        transition: transform 0.2s;
    }

    .filter-dropdown.open .filter-btn .chevron {
        transform: rotate(180deg);
    }

    /* Menu dropdown */
    .filter-menu {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        min-width: 220px;
        max-height: 320px;
        overflow-y: auto;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.2s ease;
    }

    .filter-dropdown.open .filter-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .filter-menu-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        font-size: 0.875rem;
        color: #475569;
        cursor: pointer;
        transition: background 0.15s;
        border-bottom: 1px solid #f1f5f9;
    }

    .filter-menu-item:last-child {
        border-bottom: none;
    }

    .filter-menu-item:hover {
        background: #f8fafc;
    }

    .filter-menu-item.selected {
        background: #eff6ff;
        color: var(--primary);
        font-weight: 600;
    }

    .filter-menu-item i {
        width: 20px;
        color: #94a3b8;
    }

    .filter-menu-item.selected i {
        color: var(--primary);
    }

    /* Toggle Buttons - Je cherche un Pro / Je cherche une mission */
    .toggle-group {
        display: flex;
        background: #f1f5f9;
        border-radius: 25px;
        padding: 4px;
        gap: 4px;
    }

    .toggle-btn {
        padding: 8px 16px;
        border: none;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        background: transparent;
        color: #64748b;
    }

    .toggle-btn.active {
        background: var(--primary);
        color: white;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
    }

    .toggle-btn:hover:not(.active) {
        background: #e2e8f0;
    }

    /* Séparateur vertical */
    .filter-separator {
        width: 1px;
        height: 28px;
        background: #e2e8f0;
        margin: 0 4px;
    }

    /* Bouton Publier une offre dans la barre de filtre */
    .btn-publish-offer {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        margin-left: auto;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border: none;
        border-radius: 25px;
        font-size: 0.85rem;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
        white-space: nowrap;
    }

    .btn-publish-offer:hover {
        background: linear-gradient(135deg, var(--primary-dark), #3730a3);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
    }

    .btn-publish-offer i {
        font-size: 0.9rem;
    }

    /* =========================================
       CONTENEUR PRINCIPAL
       ========================================= */
    .content-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px 20px;
        background: var(--bg-body);
        min-height: calc(100vh - 200px);
    }

    /* ===== LAYOUT 3 COLONNES CENTRÉ (CSS Grid) ===== */
    .feed-layout {
        display: grid;
        grid-template-columns: 250px minmax(0, 1fr);
        gap: 24px;
        max-width: 1400px;
        margin: 0 auto;
        align-items: start;
    }

    .feed-main {
        min-width: 0;
        max-width: 950px;
    }

    .feed-sidebar-left {
        position: sticky;
        top: 170px;
        max-height: calc(100vh - 190px);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        scrollbar-width: none;
        -ms-overflow-style: none;
        z-index: 100;
        transform: translateX(-60px);
    }
    .feed-sidebar-left::-webkit-scrollbar { display: none; }

    .feed-sidebar-right {
        position: sticky;
        top: 170px;
        max-height: calc(100vh - 190px);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 16px;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .feed-sidebar-right::-webkit-scrollbar { display: none; }

    .feed-sidebar { display: none; }

    @media (min-width: 1400px) {
        .feed-layout {
            grid-template-columns: 260px minmax(0, 1fr);
            max-width: 1500px;
        }
    }

    @media (max-width: 1200px) {
        .feed-layout {
            grid-template-columns: 240px minmax(0, 1fr);
            max-width: 1100px;
        }
        .feed-sidebar-right { display: none; }
    }

    @media (max-width: 992px) {
        .feed-layout {
            grid-template-columns: 1fr;
            max-width: 680px;
        }
        .feed-sidebar-left { display: none; }
        .feed-sidebar-right { display: none; }
    }

    @media (max-width: 640px) {
        .content-container { padding: 16px 12px; }
        .feed-layout { max-width: 100%; }
    }

    /* ===== SIDEBAR DROITE - MENU UTILISATEUR ===== */
    .sidebar-menu-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 6px 16px rgba(0, 0, 0, 0.04);
        transition: box-shadow 0.2s ease;
    }
    .sidebar-menu-card:hover {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 8px 24px rgba(0, 0, 0, 0.06);
    }

    .sidebar-menu-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    .sidebar-menu-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 0.85rem;
        overflow: hidden;
        flex-shrink: 0;
    }

    .sidebar-menu-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .sidebar-menu-name {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }

    .sidebar-menu-sub {
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 2px;
    }
    .sidebar-menu-links {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin-top: 12px;
    }
        .sidebar-menu-card {
            transform: none; /* Reset transform for sidebar menu card */
        }

    .sidebar-menu-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 12px;
        border-radius: 8px;
        text-decoration: none;
        background: transparent;
        border: none;
        color: #1e293b;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.15s ease;
    }

    .sidebar-menu-link i {
        width: 18px;
        text-align: center;
        color: #64748b;
        font-size: 0.9rem;
    }

    .sidebar-menu-link:hover {
        background: #f1f5f9;
        color: var(--primary);
    }
    .sidebar-menu-link:hover i {
        color: var(--primary);
    }

    .sidebar-menu-logout {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #f1f5f9;
    }

    .sidebar-menu-logout button {
        width: 100%;
        border: none;
        background: transparent;
        color: #ef4444;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 9px 12px;
        border-radius: 8px;
        transition: all 0.15s ease;
        cursor: pointer;
        text-align: left;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sidebar-menu-logout button:hover {
        background: #fef2f2;
        color: #dc2626;
    }

    .sidebar-left-widgets {
        margin-top: 16px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    /* Widgets cards dans la sidebar */
    .sidebar-left-widgets > div {
        border-radius: 14px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 6px 16px rgba(0, 0, 0, 0.04) !important;
        border-color: #e2e8f0 !important;
        transition: box-shadow 0.2s ease;
    }
    .sidebar-left-widgets > div:hover {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 8px 24px rgba(0, 0, 0, 0.06) !important;
    }
    /* Widget Pro : override les styles g\u00e9n\u00e9riques */
    .sidebar-left-widgets > .pro-widget {
        border: none !important;
        box-shadow: 0 4px 20px rgba(49, 46, 129, 0.25) !important;
    }
    .sidebar-left-widgets > .pro-widget:hover {
        box-shadow: 0 6px 30px rgba(49, 46, 129, 0.35) !important;
    }
    .sidebar-left-widgets > .pro-widget.pro-widget--active {
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 16px rgba(0,0,0,0.04) !important;
    }
    .sidebar-left-widgets > .pro-widget.pro-widget--active:hover {
        box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 8px 24px rgba(0,0,0,0.06) !important;
    }



    /* Grille des cartes prestataires */
    .providers-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        max-width: 100%;
        margin-left: auto;
        margin-right: auto;
    }

    /* Carte prestataire - Style Marketplace */
    .provider-card {
        background: transparent;
        cursor: pointer;
        transition: transform 0.3s ease;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .provider-card:hover {
        transform: translateY(-5px);
    }

    .provider-image-wrapper {
        position: relative;
        width: 100%;
        aspect-ratio: 1 / 1;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }

    .provider-image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .provider-image-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2.5rem;
        font-weight: 700;
    }

    .provider-badge-top {
        position: absolute;
        bottom: 8px;
        left: 8px;
        background: rgba(138, 43, 226, 0.85);
        backdrop-filter: blur(4px);
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .provider-card-info {
        padding: 8px 2px;
    }

    .provider-info-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2px;
    }

    .provider-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: #2d3436;
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0;
    }

    .provider-price {
        font-size: 1rem;
        font-weight: 600;
        color: #2d3436;
    }

    .badge-pro {
        background: #0084ff;
        color: white;
        font-size: 0.65rem;
        padding: 2px 6px;
        border-radius: 4px;
        text-transform: uppercase;
    }

    .provider-rating {
        display: flex;
        align-items: center;
        font-size: 0.9rem;
    }

    .star-icon {
        color: #fdcb6e;
        margin-right: 4px;
        font-weight: bold;
    }

    .rating-value {
        font-weight: 700;
        color: #2d3436;
    }

    .reviews-count {
        color: #636e72;
        margin-left: 6px;
    }

    .provider-category {
        font-size: 0.8rem;
        color: #64748b;
        margin-top: 4px;
    }

    /* =========================================
       URGENT ADS HORIZONTAL CAROUSEL
       ========================================= */
    .urgent-carousel-section {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .urgent-carousel-header {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        padding: 0;
    }
    .urgent-carousel-header h3 {
        font-size: 1rem;
        font-weight: 700;
        color: #dc2626;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .urgent-carousel-header h3 i {
        font-size: 1.1rem;
        animation: urgentPulse 1.5s ease-in-out infinite;
    }
    @keyframes urgentPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.7; transform: scale(1.15); }
    }
    .urgent-carousel-track-wrapper {
        position: relative;
        overflow: hidden;
        border-radius: 0.75rem;
    }
    /* Arrows INSIDE the track, overlaid left & right */
    .urgent-carousel-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 10;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: none;
        background: rgba(255,255,255,0.92);
        color: #dc2626;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.82rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    .urgent-carousel-btn:hover {
        background: white;
        box-shadow: 0 4px 14px rgba(220,38,38,0.25);
        transform: translateY(-50%) scale(1.08);
    }
    .urgent-carousel-btn:disabled {
        opacity: 0;
        pointer-events: none;
    }
    .urgent-carousel-btn.prev-btn {
        left: 6px;
    }
    .urgent-carousel-btn.next-btn {
        right: 6px;
    }
    .urgent-carousel-track {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        scroll-behavior: smooth;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding: 4px 2px 8px;
    }
    .urgent-carousel-track::-webkit-scrollbar {
        display: none;
    }
    /* 5 cards per row: calc((100% - 4*10px gap) / 5) */
    .urgent-card {
        flex: 0 0 calc((100% - 40px) / 5);
        min-width: 120px;
        background: white;
        border-radius: 0.75rem;
        border: 1px solid #fecaca;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.25s;
        text-decoration: none;
        color: inherit;
        position: relative;
    }
    .urgent-card:hover {
        border-color: #dc2626;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220,38,38,0.12);
        color: inherit;
    }
    .urgent-card-img {
        width: 100%;
        height: 100px;
        object-fit: cover;
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fca5a5;
        font-size: 2rem;
    }
    .urgent-card-img img {
        width: 100%;
        height: 100px;
        object-fit: cover;
    }
    .urgent-card-badge {
        position: absolute;
        top: 8px;
        left: 8px;
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        color: white;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 3px 8px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .urgent-card-body {
        padding: 10px 12px 12px;
    }
    .urgent-card-title {
        font-size: 0.82rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .urgent-card-meta {
        font-size: 0.72rem;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .urgent-card-price {
        font-size: 0.85rem;
        font-weight: 800;
        color: #dc2626;
        margin-top: 4px;
    }
    .urgent-card-countdown {
        position: absolute;
        top: 8px;
        right: 8px;
        background: rgba(0,0,0,0.65);
        color: white;
        font-size: 0.6rem;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 3px;
    }

    /* =========================================
       ANNONCES BOOSTÉES / URGENTES EN GRAND
       ========================================= */
    .highlighted-ads-section {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
        margin-bottom: 1.25rem;
    }
    .highlighted-ad-card {
        background: white;
        border-radius: 14px;
        overflow: hidden;
        border: 2px solid transparent;
        transition: all 0.25s ease;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        position: relative;
    }
    .highlighted-ad-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }
    .highlighted-ad-card.urgent { border-color: #fca5a5; background: linear-gradient(135deg, #fff5f5, #ffffff); }
    .highlighted-ad-card.boosted { border-color: #93c5fd; background: linear-gradient(135deg, #eff6ff, #ffffff); }
    .highlighted-ad-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        z-index: 5;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .highlighted-ad-card.urgent .highlighted-ad-badge { background: #dc2626; color: white; }
    .highlighted-ad-card.boosted .highlighted-ad-badge { background: linear-gradient(135deg, #3b82f6, #6366f1); color: white; }
    .highlighted-ad-img {
        width: 100%;
        aspect-ratio: 16 / 9;
        background: #f1f5f9;
        overflow: hidden;
    }
    .highlighted-ad-img img { width: 100%; height: 100%; object-fit: cover; }
    .highlighted-ad-img-placeholder {
        width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
        color: #cbd5e1; font-size: 2rem;
    }
    .highlighted-ad-body { padding: 14px 16px; }
    .highlighted-ad-title { font-weight: 700; font-size: 1rem; color: #1e293b; margin-bottom: 4px; line-height: 1.3; }
    .highlighted-ad-desc { font-size: 0.82rem; color: #64748b; line-height: 1.4; margin-bottom: 10px; }
    .highlighted-ad-meta { display: flex; justify-content: space-between; align-items: center; }
    .highlighted-ad-user { display: flex; align-items: center; gap: 8px; }
    .highlighted-ad-user img { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; }
    .highlighted-ad-user-placeholder {
        width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #a78bfa);
        display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.7rem;
    }
    .highlighted-ad-user span { font-size: 0.82rem; color: #64748b; }
    .highlighted-ad-price { font-weight: 700; color: #059669; font-size: 0.95rem; }

    .providers-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
    }
    .provider-card { background: transparent; cursor: pointer; transition: transform 0.3s ease; text-decoration: none; color: inherit; display: block; }
    .provider-card:hover { transform: translateY(-5px); color: inherit; }
    .provider-image-wrapper {
        position: relative; width: 100%; aspect-ratio: 1 / 1; border-radius: 12px;
        overflow: hidden; box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }
    .provider-image-wrapper img { width: 100%; height: 100%; object-fit: cover; }
    .provider-image-placeholder {
        width: 100%; height: 100%; background: linear-gradient(135deg, var(--primary, #4f46e5), var(--secondary, #7c3aed));
        display: flex; align-items: center; justify-content: center; color: white; font-size: 2.2rem; font-weight: 700;
    }
    .provider-badge-top {
        position: absolute; bottom: 8px; left: 8px; background: rgba(138, 43, 226, 0.85); backdrop-filter: blur(4px);
        color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.68rem; font-weight: 600;
        display: flex; align-items: center; gap: 4px;
    }
    .provider-card-info { padding: 8px 2px; }
    .provider-info-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px; }
    .provider-name { font-size: 0.85rem; font-weight: 600; color: #2d3436; display: flex; align-items: center; gap: 6px; margin: 0; }
    .badge-pro { background: #0084ff; color: white; font-size: 0.6rem; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; }
    .provider-rating { display: flex; align-items: center; font-size: 0.82rem; }
    .star-icon { color: #fdcb6e; margin-right: 4px; font-weight: bold; }
    .rating-value { font-weight: 700; color: #2d3436; }
    .reviews-count { color: #636e72; margin-left: 6px; font-size: 0.75rem; }
    .provider-category { font-size: 0.75rem; color: #64748b; margin-top: 4px; margin-bottom: 0; }

    /* =========================================
       BOUTON CANDIDATURE POPUP
       ========================================= */
    .ad-detail-candidature { padding: 0 20px 12px; }
    .btn-candidature {
        width: 100%; padding: 12px; border: none; border-radius: 10px;
        background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;
        font-weight: 600; font-size: 0.9rem; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        transition: all 0.2s;
    }
    .btn-candidature:hover { background: linear-gradient(135deg, #2563eb, #1d4ed8); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }
    .candidature-form { padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; }
    .candidature-textarea {
        width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px;
        font-size: 0.85rem; resize: vertical; min-height: 60px; max-height: 150px;
        margin-bottom: 10px; font-family: inherit;
    }
    .candidature-textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    .btn-candidature-cancel {
        padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 8px; background: white;
        color: #64748b; font-size: 0.82rem; cursor: pointer; font-weight: 500;
    }
    .btn-candidature-cancel:hover { background: #f1f5f9; }
    .btn-candidature-send {
        padding: 8px 20px; border: none; border-radius: 8px;
        background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;
        font-size: 0.82rem; cursor: pointer; font-weight: 600;
        display: flex; align-items: center; gap: 6px;
    }
    .btn-candidature-send:hover { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
    .btn-candidature-send:disabled { opacity: 0.6; cursor: not-allowed; }

    @media (max-width: 640px) {
        .highlighted-ads-section { grid-template-columns: 1fr; }
        .providers-grid { grid-template-columns: repeat(2, 1fr); }
    }

    /* =========================================
       CAROUSEL ANNONCES À LA UNE
       ========================================= */
    .featured-ads-carousel {
        margin-bottom: 1.25rem;
    }
    .featured-ads-wrapper {
        position: relative;
        overflow: hidden;
    }
    .featured-ads-track {
        display: flex;
        gap: 0.75rem;
        overflow-x: auto;
        scroll-behavior: smooth;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding: 4px 2px 8px;
    }
    .featured-ads-track::-webkit-scrollbar { display: none; }
    .featured-ads-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 10;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: none;
        background: rgba(255,255,255,0.95);
        color: #334155;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.78rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    .featured-ads-arrow:hover {
        background: white;
        box-shadow: 0 4px 14px rgba(0,0,0,0.2);
        transform: translateY(-50%) scale(1.08);
    }
    .featured-ads-arrow-left { left: 6px; }
    .featured-ads-arrow-right { right: 6px; }
    .featured-ad-card {
        flex: 0 0 180px;
        min-width: 160px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        transition: all 0.2s;
    }
    .featured-ad-card:hover {
        border-color: var(--primary, #4f46e5);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(79,70,229,0.1);
        color: inherit;
    }
    .featured-ad-img {
        position: relative;
        width: 100%;
        aspect-ratio: 4 / 3;
        background: #f1f5f9;
        overflow: hidden;
    }
    .featured-ad-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .featured-ad-img-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #cbd5e1;
        font-size: 1.5rem;
    }
    .featured-ad-badge-pro {
        position: absolute;
        top: 6px;
        left: 6px;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 0.58rem;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 9999px;
        background: rgba(16,185,129,0.9);
        color: white;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        backdrop-filter: blur(4px);
    }
    .featured-ad-info {
        padding: 0.6rem 0.65rem;
    }
    .featured-ad-title {
        font-weight: 600;
        font-size: 0.8rem;
        color: #0f172a;
        line-height: 1.3;
        margin-bottom: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .featured-ad-meta {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.7rem;
        color: #64748b;
        margin-bottom: 4px;
    }
    .featured-ad-user-avatar {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        object-fit: cover;
    }
    .featured-ad-bottom {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 4px;
    }
    .featured-ad-price {
        font-weight: 700;
        font-size: 0.78rem;
        color: var(--primary, #4f46e5);
    }
    .featured-ad-cat {
        font-size: 0.65rem;
        color: #94a3b8;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 80px;
    }
    @media (max-width: 576px) {
        .featured-ad-card {
            flex: 0 0 155px;
            min-width: 140px;
        }
    }

    /* =========================================
       SECTION "PROFESSIONNELS À LA UNE"
       ========================================= */
    .featured-pros-section {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .featured-pros-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }
    .featured-pros-header h2 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .featured-pros-header h2 i {
        color: var(--primary, #4f46e5);
        font-size: 1rem;
    }
    .featured-pros-viewall {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--primary, #4f46e5);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .featured-pros-viewall:hover { opacity: 0.7; }
    .featured-pros-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
    }
    .featured-pro-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .featured-pro-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        color: inherit;
    }
    .featured-pro-image {
        aspect-ratio: 1 / 1;
        overflow: hidden;
    }
    .featured-pro-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .featured-pro-image-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, var(--primary, #4f46e5), var(--secondary, #7c3aed));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 2.5rem;
    }
    .featured-pro-info {
        padding: 0.75rem;
    }
    .featured-pro-name {
        font-weight: 600;
        font-size: 0.9rem;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        margin-bottom: 0.25rem;
    }
    .featured-pro-rating {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.8rem;
        color: #f59e0b;
    }
    .featured-pro-rating span {
        color: #64748b;
        margin-left: 0.25rem;
    }
    .featured-pro-category {
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 2px;
    }
    .badge-pro-featured {
        background: #eef2ff;
        color: var(--primary, #4f46e5);
        font-size: 0.6rem;
        font-weight: 700;
        padding: 0.15rem 0.35rem;
        border-radius: 0.25rem;
        text-transform: uppercase;
    }
    @media (max-width: 576px) {
        .featured-pros-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }
    }

    /* Boosted ad indicator in feed posts */
    .fb-post-sponsored-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.68rem;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 6px;
        margin-left: 6px;
    }
    .fb-post-sponsored-tag.boost {
        background: #f3f4f6;
        color: #4b5563;
    }
    .fb-post-sponsored-tag.urgent {
        background: #fef2f2;
        color: #991b1b;
    }
    .fb-post.boosted-post {
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .fb-post.boosted-post:hover {
        border-color: #cbd5e1;
    }

    /* Step progress for new popup */
    .sub-popup-step-progress {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 16px 28px 0;
    }
    .sub-popup-step-dot {
        width: 28px;
        height: 4px;
        border-radius: 4px;
        background: #e5e7eb;
        transition: all 0.3s;
    }
    .sub-popup-step-dot.active {
        background: #7c3aed;
        width: 36px;
    }
    .sub-popup-step-dot.done {
        background: #10b981;
    }

    /* Wizard editable profile fields */
    .wizard-field-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .wizard-field-row:last-child { border-bottom: none; }
    .wizard-field-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.82rem;
        flex-shrink: 0;
    }
    .wizard-field-body {
        flex: 1;
        min-width: 0;
    }
    .wizard-field-label {
        font-size: 0.7rem;
        color: #6b7280;
        font-weight: 600;
        margin-bottom: 2px;
        display: block;
    }
    .wizard-field-input {
        width: 100%;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 6px 10px;
        font-size: 0.82rem;
        color: #111827;
        background: #fff;
        transition: border-color 0.2s, box-shadow 0.2s;
        outline: none;
        font-family: inherit;
    }
    .wizard-field-input:focus {
        border-color: #7c3aed;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }
    .wizard-field-input:disabled {
        background: #f9fafb;
    }

    /* Category tree (Step 2) */
    .wiz-cat-group {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        margin-bottom: 8px;
        overflow: hidden;
        transition: box-shadow 0.2s;
    }
    .wiz-cat-group.has-selected {
        border-color: #7c3aed;
        box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.1);
    }
    .wiz-cat-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        cursor: pointer;
        background: #f8fafc;
        transition: background 0.15s;
        user-select: none;
    }
    .wiz-cat-header:hover { background: #f1f5f9; }
    .wiz-cat-header-icon {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        flex-shrink: 0;
    }
    .wiz-cat-header-name {
        flex: 1;
        font-size: 0.82rem;
        font-weight: 600;
        color: #111827;
    }
    .wiz-cat-header-count {
        font-size: 0.68rem;
        color: #7c3aed;
        font-weight: 700;
        background: #ede9fe;
        padding: 1px 7px;
        border-radius: 10px;
        display: none;
    }
    .wiz-cat-header-arrow {
        color: #9ca3af;
        font-size: 0.7rem;
        transition: transform 0.2s;
    }
    .wiz-cat-group.open .wiz-cat-header-arrow { transform: rotate(180deg); }
    .wiz-cat-subcats {
        display: none;
        padding: 6px 12px 10px;
        background: #fff;
    }
    .wiz-cat-group.open .wiz-cat-subcats { display: block; }
    .wiz-cat-subcat {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 10px;
        margin: 3px;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 500;
        color: #374151;
        background: #f3f4f6;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.15s;
        user-select: none;
    }
    .wiz-cat-subcat:hover { background: #ede9fe; color: #5b21b6; }
    .wiz-cat-subcat.selected {
        background: #7c3aed;
        color: #fff;
        border-color: #7c3aed;
    }

    /* Profile recap step */
    .sub-popup-profile-field {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: #f8fafc;
        border-radius: 10px;
        margin-bottom: 8px;
        border: 1px solid #f1f5f9;
    }
    .sub-popup-profile-field-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .sub-popup-profile-field-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.82rem;
        flex-shrink: 0;
    }
    .sub-popup-profile-field-label {
        font-size: 0.78rem;
        color: #6b7280;
    }
    .sub-popup-profile-field-value {
        font-size: 0.82rem;
        font-weight: 600;
        color: #111827;
    }
    .sub-popup-profile-field .status-ok { color: #10b981; font-size: 0.85rem; }
    .sub-popup-profile-field .status-missing { color: #ef4444; font-size: 0.72rem; font-weight: 600; }

    /* Notification toggles */
    .sub-popup-notif-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 14px;
        background: #f8fafc;
        border-radius: 10px;
        margin-bottom: 8px;
        border: 1px solid #f1f5f9;
    }
    .sub-popup-notif-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .sub-popup-notif-info i {
        font-size: 1rem;
        width: 20px;
        text-align: center;
    }
    .sub-popup-notif-info strong {
        display: block;
        font-size: 0.82rem;
        color: #111827;
    }
    .sub-popup-notif-info small {
        font-size: 0.7rem;
        color: #9ca3af;
    }
    .sub-popup-toggle {
        position: relative;
        width: 42px;
        height: 24px;
        cursor: pointer;
    }
    .sub-popup-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }
    .sub-popup-toggle-slider {
        position: absolute;
        inset: 0;
        background: #d1d5db;
        border-radius: 12px;
        transition: all 0.25s;
    }
    .sub-popup-toggle-slider::before {
        content: '';
        position: absolute;
        width: 18px;
        height: 18px;
        left: 3px;
        bottom: 3px;
        background: white;
        border-radius: 50%;
        transition: all 0.25s;
    }
    .sub-popup-toggle input:checked + .sub-popup-toggle-slider {
        background: #7c3aed;
    }
    .sub-popup-toggle input:checked + .sub-popup-toggle-slider::before {
        transform: translateX(18px);
    }

    /* =========================================
       PUBLICATION STYLE FACEBOOK - Fil d'actualité
       ========================================= */
    .missions-feed {
        max-width: 100%;
        display: flex;
        flex-direction: column;
        gap: 24px;
        margin: 0;
    }

    /* --- Post container --- */
    .fb-post {
        background: white;
        border-radius: 1rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        overflow: hidden;
        transition: box-shadow 0.2s;
    }
    .fb-post:hover {
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }

    /* --- Post header: avatar + name + meta --- */
    .fb-post-header {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.7rem 1rem;
    }

    .fb-post-avatar {
        width: 2.2rem;
        height: 2.2rem;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        cursor: pointer;
    }

    .fb-post-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .fb-post-avatar-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1rem;
    }

    .fb-post-header-info {
        flex: 1;
        min-width: 0;
    }

    .fb-post-author {
        font-weight: 600;
        font-size: 0.88rem;
        color: #050505;
        display: flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }

    .fb-post-author:hover {
        text-decoration: underline;
    }

    .fb-post-badge {
        display: inline-block;
        background: #e7f3ff;
        color: #1877f2;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 4px;
        text-transform: uppercase;
    }

    .fb-post-meta {
        font-size: 0.75rem;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .fb-post-meta i {
        font-size: 0.65rem;
    }

    .fb-post-options {
        margin-left: auto;
        position: relative;
    }
    .fb-post-options-btn {
        background: none;
        border: none;
        color: #65676b;
        font-size: 1.1rem;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 50%;
        transition: background 0.15s;
    }

    .fb-post-options-btn:hover {
        background: #f0f2f5;
    }

    /* --- Post options dropdown menu --- */
    .fb-post-options-menu {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: #fff;
        border-radius: 0.75rem;
        box-shadow: 0 4px 24px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.04);
        min-width: 240px;
        z-index: 100;
        padding: 6px 0;
        animation: menuFadeIn 0.15s ease;
    }
    .fb-post-options-menu.show {
        display: block;
    }
    @keyframes menuFadeIn {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .fb-post-menu-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        font-size: 0.88rem;
        color: #1c1e21;
        cursor: pointer;
        transition: background 0.12s;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
    }
    .fb-post-menu-item:hover {
        background: #f2f3f5;
    }
    .fb-post-menu-item i {
        font-size: 1rem;
        width: 20px;
        text-align: center;
        color: #65676b;
    }
    .fb-post-menu-item .menu-item-text {
        display: flex;
        flex-direction: column;
    }
    .fb-post-menu-item .menu-item-text small {
        font-size: 0.72rem;
        color: #8a8d91;
        font-weight: 400;
    }
    .fb-post-menu-divider {
        height: 1px;
        background: #e4e6eb;
        margin: 4px 0;
    }
    .fb-post-menu-item.danger { color: #e41e3f; }
    .fb-post-menu-item.danger i { color: #e41e3f; }

    /* --- Report modal --- */
    .report-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    .report-modal-overlay.show { display: flex; }
    .report-modal {
        background: #fff;
        border-radius: 1rem;
        width: 90%;
        max-width: 480px;
        max-height: 85vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        animation: reportSlideIn 0.2s ease;
    }
    @keyframes reportSlideIn {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .report-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid #e4e6eb;
    }
    .report-modal-header h3 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1c1e21;
        margin: 0;
    }
    .report-modal-close {
        background: #e4e6eb;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #65676b;
        font-size: 1rem;
        transition: background 0.15s;
    }
    .report-modal-close:hover { background: #d8dadf; }
    .report-modal-body { padding: 16px 20px; }
    .report-modal-body p {
        font-size: 0.88rem;
        color: #65676b;
        margin-bottom: 14px;
    }
    .report-reason-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border: 2px solid #e4e6eb;
        border-radius: 0.75rem;
        margin-bottom: 8px;
        cursor: pointer;
        transition: all 0.15s;
        font-size: 0.9rem;
        color: #1c1e21;
    }
    .report-reason-item:hover { border-color: #bec3c9; background: #f8f9fa; }
    .report-reason-item.selected { border-color: #e41e3f; background: #fef2f2; }
    .report-reason-item i {
        font-size: 1.1rem;
        color: #65676b;
        width: 22px;
        text-align: center;
    }
    .report-reason-item.selected i { color: #e41e3f; }
    .report-reason-text { flex: 1; }
    .report-reason-text small { display: block; font-size: 0.75rem; color: #8a8d91; margin-top: 2px; }
    .report-reason-item .report-radio {
        width: 18px; height: 18px; border-radius: 50%;
        border: 2px solid #bec3c9; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.15s;
    }
    .report-reason-item.selected .report-radio {
        border-color: #e41e3f; background: #e41e3f;
    }
    .report-reason-item.selected .report-radio::after {
        content: ''; width: 6px; height: 6px; background: #fff; border-radius: 50%;
    }
    .report-message-area {
        width: 100%;
        border: 2px solid #e4e6eb;
        border-radius: 0.75rem;
        padding: 10px 14px;
        font-size: 0.88rem;
        resize: vertical;
        min-height: 70px;
        margin-top: 10px;
        font-family: inherit;
        transition: border-color 0.15s;
    }
    .report-message-area:focus { outline: none; border-color: #e41e3f; }
    .report-modal-footer {
        padding: 14px 20px;
        border-top: 1px solid #e4e6eb;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }
    .report-btn-cancel {
        padding: 8px 20px;
        border-radius: 0.5rem;
        border: none;
        background: #e4e6eb;
        color: #1c1e21;
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        transition: background 0.15s;
    }
    .report-btn-cancel:hover { background: #d8dadf; }
    .report-btn-submit {
        padding: 8px 20px;
        border-radius: 0.5rem;
        border: none;
        background: #e41e3f;
        color: #fff;
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        transition: background 0.15s;
        opacity: 0.5;
        pointer-events: none;
    }
    .report-btn-submit.active { opacity: 1; pointer-events: auto; }
    .report-btn-submit.active:hover { background: #c8182f; }

    /* --- Post body: title + description + price + tags --- */
    .fb-post-body {
        padding: 0 1rem 0.5rem;
    }

    .fb-post-title {
        font-size: 0.97rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.35rem;
        line-height: 1.3;
    }

    .fb-post-text {
        font-size: 0.88rem;
        color: #475569;
        line-height: 1.5;
        word-break: break-word;
        margin-bottom: 4px;
    }

    .fb-post-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }

    .fb-post-tag {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        background: #f1f5f9;
        color: #475569;
        font-size: 0.65rem;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        font-weight: 600;
    }

    .fb-post-tag.price {
        background: var(--primary-light, #eef2ff);
        color: var(--primary, #4f46e5);
        font-weight: 700;
    }

    .fb-post-tag.urgent {
        background: #fee2e2;
        color: #dc2626;
        font-weight: 700;
    }

    /* --- Post photos --- */
    .fb-post-photos {
        width: 100%;
        overflow: hidden;
        cursor: pointer;
        margin: 0.35rem 1rem 0.5rem;
        border-radius: 0.5rem;
        width: calc(100% - 2rem);
    }

    .fb-post-photos.single {
        display: flex;
        justify-content: flex-start;
    }
    .fb-post-photos.single img {
        max-width: 100%;
        max-height: 320px;
        width: auto;
        object-fit: contain;
        display: block;
        border-radius: 0.5rem;
    }

    .fb-post-photos.multi {
        display: grid;
        gap: 2px;
    }

    .fb-post-photos.multi.two {
        grid-template-columns: 1fr 1fr;
    }

    .fb-post-photos.multi.three-plus {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1fr 1fr;
    }

    .fb-post-photos.multi.three-plus .fb-photo-item:first-child {
        grid-row: 1 / 3;
    }

    .fb-photo-item {
        overflow: hidden;
        position: relative;
        background: #e4e6eb;
    }

    .fb-photo-item img {
        width: 100%;
        height: 100%;
        min-height: 150px;
        max-height: 350px;
        object-fit: cover;
        display: block;
        transition: opacity 0.15s;
    }

    .fb-photo-item:hover img {
        opacity: 0.95;
    }

    .fb-photo-more-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.8rem;
        font-weight: 700;
    }

    .fb-post-no-photo {
        width: 100%;
        height: 90px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.3rem;
    }

    /* --- Reactions count bar --- */
    .fb-post-reactions-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.35rem 1rem;
        font-size: 0.8rem;
        color: #64748b;
        border-top: 1px solid #e2e8f0;
    }

    .fb-post-reactions-bar .reactions-left {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .fb-post-reactions-bar .reactions-right {
        display: flex;
        gap: 12px;
    }

    .fb-post-reactions-bar .reactions-right span {
        cursor: pointer;
    }

    .fb-post-reactions-bar .reactions-right span:hover {
        text-decoration: underline;
    }

    /* --- Action buttons bar (Like / Comment / Share / Contact) --- */
    .fb-post-actions {
        display: flex;
        gap: 0.25rem;
        padding: 0.2rem 1rem 0.5rem;
    }

    .fb-action-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.35rem;
        font-size: 0.8rem;
        font-weight: 500;
        color: #64748b;
        background: none;
        border: none;
        cursor: pointer;
        border-radius: 0.5rem;
        transition: background 0.2s, color 0.2s;
    }

    .fb-action-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .fb-action-btn.liked {
        color: #dc2626;
    }

    .fb-action-btn i {
        font-size: 0.9rem;
    }

    .fb-action-btn.contact-btn {
        color: var(--primary, #4f46e5);
    }

    /* --- Inline comments section --- */
    .fb-post-comments {
        padding: 0.75rem 1.25rem 1rem;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        border-radius: 0 0 1rem 1rem;
    }

    .fb-comments-list {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 8px;
    }

    .fb-comment-form {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .fb-comment-form .fb-comment-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
    }

    .fb-comment-form .fb-comment-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .fb-comment-input-wrap {
        flex: 1;
        display: flex;
        align-items: center;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 9999px;
        padding: 0 6px 0 14px;
    }

    .fb-comment-input-wrap input {
        flex: 1;
        border: none;
        background: transparent;
        font-size: 0.88rem;
        padding: 8px 0;
        outline: none;
        color: #050505;
    }

    .fb-comment-input-wrap input::placeholder {
        color: #8e8e8e;
    }

    .fb-comment-send-btn {
        background: none;
        border: none;
        color: #1877f2;
        font-size: 0.95rem;
        cursor: pointer;
        padding: 6px;
        border-radius: 50%;
        transition: background 0.15s;
    }

    .fb-comment-send-btn:hover:not(:disabled) {
        background: #e7f3ff;
    }

    .fb-comment-send-btn:disabled {
        color: #bec3c9;
        cursor: default;
    }

    .no-comments-msg {
        text-align: center;
        color: #8e8e8e;
        font-size: 0.82rem;
        padding: 8px 0;
    }
    .mission-photos {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 3px;
        border-radius: 8px;
        overflow: hidden;
        margin-top: 10px;
    }

    .mission-photos.single-photo {
        grid-template-columns: 1fr;
    }

    .mission-photos.single-photo .mission-photo {
        height: 300px;
    }

    .mission-photos.two-photos {
        grid-template-columns: repeat(2, 1fr);
    }

    .mission-photo {
        height: 160px;
        overflow: hidden;
        position: relative;
        cursor: pointer;
        background: #e2e8f0;
    }

    .mission-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.2s;
    }

    .mission-photo:hover img {
        transform: scale(1.03);
    }

    .mission-photo-more {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: 700;
    }

    /* =========================================
       STATS INLINE BADGES
       ========================================= */
    .mission-stats-inline {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #65676b;
        font-size: 0.8rem;
    }

    .stat-badge {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .stat-badge i {
        font-size: 0.75rem;
    }

    /* =========================================
       PHOTO LIGHTBOX
       ========================================= */
    .photo-lightbox {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.95);
        z-index: 99999;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .photo-lightbox.active {
        display: flex;
    }

    .photo-lightbox-close {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 44px;
        height: 44px;
        border: none;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        font-size: 1.5rem;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .photo-lightbox-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .photo-lightbox img {
        max-width: 95%;
        max-height: 95%;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    .photo-lightbox-title {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        color: white;
        font-size: 1rem;
        font-weight: 500;
        background: rgba(0, 0, 0, 0.6);
        padding: 8px 20px;
        border-radius: 20px;
        max-width: 80%;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* =========================================
       BOUTON 3 POINTS (MENU)
       ========================================= */
    .btn-three-dots {
        width: 36px;
        height: 36px;
        border: none;
        background: transparent;
        color: #65676b;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s;
    }

    .btn-three-dots:hover {
        background: #f0f2f5;
    }

    .btn-three-dots i {
        font-size: 1rem;
    }

    /* =========================================
       BOUTONS ACTIONS SOCIALES AMÉLIORÉS
       ========================================= */
    .mission-actions {
        display: flex;
        justify-content: space-around;
        padding: 6px 12px;
        border-top: 1px solid #f1f5f9;
        background: white;
        gap: 6px;
        position: relative;
        z-index: 5;
    }

    .mission-action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 6px 10px;
        border: none;
        background: transparent;
        color: #65676b;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        border-radius: 6px;
        transition: all 0.2s ease;
        flex: 0 1 auto;
    }

    .mission-action-btn:hover {
        background: #f0f2f5;
        color: #1e293b;
    }

    .mission-action-btn.liked {
        color: #dc2626;
        background: #fef2f2;
    }

    .mission-action-btn.liked i {
        animation: pulse 0.3s ease;
    }

    .mission-action-btn.active-comments {
        color: var(--primary);
        background: #eff6ff;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

    /* =========================================
       MENU DE PARTAGE AMÉLIORÉ
       ========================================= */
    .share-menu {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        transform: scale(0.95);
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        padding: 12px;
        min-width: 220px;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s ease;
        z-index: 1000;
    }

    .share-menu.open {
        opacity: 1;
        visibility: visible;
        transform: scale(1);
    }

    .share-menu-header {
        font-size: 0.85rem;
        font-weight: 600;
        color: #64748b;
        padding: 8px 12px;
        border-bottom: 1px solid #f1f5f9;
        margin-bottom: 8px;
    }

    .share-option {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.15s ease;
        font-size: 0.9rem;
        color: #1e293b;
    }

    .share-option:hover {
        background: #f8fafc;
    }

    .share-option i {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
    }

    .share-option.copy i { background: #f1f5f9; color: #64748b; }
    .share-option.twitter i { background: #e0f2fe; color: #0ea5e9; }
    .share-option.facebook i { background: #dbeafe; color: #2563eb; }
    .share-option.linkedin i { background: #e0e7ff; color: #4f46e5; }
    .share-option.whatsapp i { background: #dcfce7; color: #16a34a; }

    .share-option.copied {
        color: #16a34a;
    }

    .share-wrapper {
        position: relative;
        z-index: 10;
    }

    .mission-stats {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 14px;
        color: #65676b;
        font-size: 0.8rem;
    }

    .mission-stats span {
        display: flex;
        align-items: center;
        gap: 3px;
    }

    .mission-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 14px;
        background: white;
    }

    .mission-price {
        font-size: 1rem;
        font-weight: 700;
        color: #059669;
        background: #ecfdf5;
        padding: 5px 12px;
        border-radius: 18px;
    }

    .mission-badges {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .mission-badge {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .mission-badge-urgent {
        background: #fef2f2;
        color: #dc2626;
    }

    .mission-badge-category {
        background: #eff6ff;
        color: var(--primary);
    }

    /* =========================================
       SIDEBAR TOP PROS
       ========================================= */
    .top-pros-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        border: 1px solid #e2e8f0;
    }

    .top-pros-header {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .top-pros-header h3 {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .top-pros-header h3 i {
        color: #eab308;
    }

    .top-pros-list {
        padding: 8px 0;
    }

    .top-pro-item {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        gap: 12px;
        text-decoration: none;
        color: inherit;
        transition: background 0.15s;
    }

    .top-pro-item:hover {
        background: #f8fafc;
    }

    .top-pro-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        border: 2px solid #e2e8f0;
    }

    .top-pro-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .top-pro-avatar-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1rem;
    }

    .top-pro-info {
        flex: 1;
        min-width: 0;
    }

    .top-pro-name {
        font-weight: 600;
        color: #1e293b;
        font-size: 0.9rem;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .top-pro-category {
        font-size: 0.75rem;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .top-pro-rating {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 0.8rem;
    }

    .top-pro-rating .star {
        color: #eab308;
    }

    .top-pro-rating span {
        color: #475569;
        font-weight: 600;
    }

    .top-pro-rank {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .top-pro-rank.gold {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: white;
    }

    .top-pro-rank.silver {
        background: linear-gradient(135deg, #9ca3af, #6b7280);
        color: white;
    }

    .top-pro-rank.bronze {
        background: linear-gradient(135deg, #d97706, #b45309);
        color: white;
    }

    .top-pro-rank.normal {
        background: #f1f5f9;
        color: #64748b;
    }

    .top-pros-footer {
        padding: 12px 16px;
        border-top: 1px solid #f1f5f9;
        text-align: center;
    }

    .top-pros-footer a {
        color: var(--primary);
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
    }

    .top-pros-footer a:hover {
        text-decoration: underline;
    }

    /* =========================================
       SECTION VIDE
       ========================================= */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-state i {
        font-size: 4rem;
        color: #cbd5e1;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        color: #475569;
        font-size: 1.25rem;
        margin-bottom: 8px;
    }

    .empty-state p {
        color: #94a3b8;
        font-size: 0.9rem;
    }

    /* Mobile: cacher les filtres secondaires par défaut */
    @media (max-width: 768px) {
        .filter-dropdown.secondary-filter {
            display: none !important;
        }
        .filter-dropdown.secondary-filter.mobile-visible {
            display: block !important;
        }
        .more-filters-btn {
            display: flex !important;
        }
    }
    @media (min-width: 769px) {
        .more-filters-btn {
            display: none;
        }
        .filter-dropdown.secondary-filter {
            display: block;
        }
    }

    /* =========================================
       RESPONSIVE
       ========================================= */
    @media (max-width: 768px) {
        .filter-bar-container {
            top: 64px;
            padding: 8px 0;
        }
        .filter-bar-spacer {
            height: 60px;
        }
        .filter-bar {
            overflow-x: auto;
            flex-wrap: nowrap;
            padding: 0 12px 8px;
            gap: 8px;
            -webkit-overflow-scrolling: touch;
        }
        .filter-btn {
            padding: 8px 12px;
            font-size: 0.8rem;
        }
        .toggle-group {
            order: -1;
            flex-shrink: 0;
        }
        .toggle-btn {
            padding: 7px 12px;
            font-size: 0.8rem;
        }
        .btn-publish-offer {
            padding: 8px 14px;
            font-size: 0.8rem;
        }
        .content-container {
            padding: 16px 10px;
        }
        .providers-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        .missions-feed {
            max-width: 100%;
        }
        .fb-post-header {
            padding: 0.6rem 0.8rem;
        }
        .fb-post-body {
            padding: 0 0.8rem 0.6rem;
        }
        .fb-post-footer {
            padding: 0.4rem 0.8rem;
        }
        .hero-section-compact {
            padding: 0.8rem 0 1rem;
        }
        .featured-ads-arrow {
            width: 28px;
            height: 28px;
            font-size: 0.75rem;
        }
        .highlighted-ad-body {
            padding: 10px 12px;
        }
        .highlighted-ad-title {
            font-size: 0.9rem;
        }
        .highlighted-ad-desc {
            font-size: 0.78rem;
        }
    }

    @media (max-width: 576px) {
        .filter-bar-container {
            top: 56px;
            padding: 6px 0;
        }
        .filter-bar-spacer {
            height: 54px;
        }
        .filter-bar {
            padding: 0 8px 6px;
            gap: 6px;
        }
        .filter-btn {
            padding: 7px 10px;
            font-size: 0.75rem;
            gap: 5px;
        }
        .filter-separator {
            display: none;
        }
        .toggle-btn {
            padding: 6px 10px;
            font-size: 0.75rem;
        }
        .btn-publish-offer {
            padding: 7px 10px;
            font-size: 0.75rem;
        }
        .btn-publish-offer span {
            display: none;
        }
        .content-container {
            padding: 12px 8px;
        }
        .providers-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .missions-feed {
            max-width: 100%;
        }
        .fb-post {
            border-radius: 0.75rem;
        }
        .fb-post-header {
            padding: 0.5rem 0.7rem;
            gap: 0.5rem;
        }
        .fb-post-avatar {
            width: 1.9rem;
            height: 1.9rem;
        }
        .fb-post-author {
            font-size: 0.82rem;
        }
        .fb-post-meta {
            font-size: 0.7rem;
        }
        .fb-post-body {
            padding: 0 0.7rem 0.5rem;
            font-size: 0.85rem;
        }
        .fb-post-footer {
            padding: 0.3rem 0.7rem;
        }
        .empty-state {
            padding: 40px 16px;
        }
        .empty-state i {
            font-size: 3rem;
        }
        .empty-state h3 {
            font-size: 1.1rem;
        }
        .cat-shortcut {
            padding: 5px 10px;
            font-size: 0.72rem;
        }
        .featured-ads-track {
            gap: 0.5rem;
        }
    }

    @media (max-width: 420px) {
        .filter-bar-container {
            padding: 5px 0;
        }
        .filter-bar {
            padding: 0 6px 5px;
            gap: 5px;
        }
        .filter-btn .chevron {
            display: none;
        }
        .providers-grid {
            grid-template-columns: 1fr;
        }
        .missions-feed {
            max-width: 100%;
        }
        .fb-post-header {
            padding: 0.45rem 0.6rem;
        }
        .fb-post-body {
            padding: 0 0.6rem 0.45rem;
            font-size: 0.82rem;
        }
        .fb-post-footer {
            padding: 0.25rem 0.6rem;
        }
        .fb-post-badge {
            font-size: 0.6rem;
            padding: 1px 5px;
        }
        .highlighted-ad-body {
            padding: 8px 10px;
        }
        .highlighted-ad-title {
            font-size: 0.85rem;
        }
    }

    /* =========================================
       LOADING STATE
       ========================================= */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
    }

    .loading-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 4px solid #e2e8f0;
        border-top-color: var(--primary);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* =========================================
       SECTION COMMENTAIRES INLINE AMÉLIORÉE
       ========================================= */
    .comments-section-inline {
        display: none;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        border-radius: 0 0 16px 16px;
        overflow: hidden;
        animation: slideDown 0.3s ease;
    }

    .comments-section-inline.open {
        display: block;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .comments-header {
        padding: 14px 16px;
        background: white;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .comments-header h5 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .comments-close-btn {
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 6px;
        border-radius: 50%;
        transition: all 0.2s;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .comments-close-btn:hover {
        background: #f1f5f9;
        color: #475569;
    }

    .comments-list-inline {
        max-height: 350px;
        overflow-y: auto;
        padding: 12px 16px;
    }

    .comment-item-inline {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
        animation: fadeInComment 0.3s ease;
    }

    .comment-item-inline:last-child {
        margin-bottom: 0;
    }

    @keyframes fadeInComment {
        from {
            opacity: 0;
            transform: translateY(-8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .comment-avatar-inline {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        flex-shrink: 0;
        overflow: hidden;
    }

    .comment-avatar-inline img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .comment-avatar-placeholder-inline {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .comment-content-inline {
        flex: 1;
        min-width: 0;
    }

    .comment-bubble {
        background: white;
        border-radius: 12px;
        padding: 10px 14px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .comment-author-inline {
        font-weight: 700;
        font-size: 0.85rem;
        color: #1e293b;
        text-decoration: none;
        display: block;
        margin-bottom: 4px;
    }

    .comment-author-inline:hover {
        color: var(--primary);
    }

    .comment-text-inline {
        font-size: 0.9rem;
        color: #475569;
        line-height: 1.5;
        word-break: break-word;
        margin: 0;
    }

    .comment-actions-inline {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 6px;
        padding-left: 4px;
    }

    .comment-action-btn {
        font-size: 0.75rem;
        color: #64748b;
        background: none;
        border: none;
        cursor: pointer;
        padding: 2px 6px;
        border-radius: 4px;
        transition: all 0.15s;
        font-weight: 500;
    }

    .comment-action-btn:hover {
        color: var(--primary);
        background: #eff6ff;
    }

    .comment-action-btn.liked {
        color: #dc2626;
    }

    .comment-time-inline {
        font-size: 0.7rem;
        color: #94a3b8;
    }

    .comments-form-inline {
        padding: 14px 16px;
        background: white;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }

    .comment-input-wrapper {
        flex: 1;
        display: flex;
        gap: 10px;
        align-items: center;
        background: #f1f5f9;
        border-radius: 24px;
        padding: 4px 4px 4px 16px;
    }

    .comment-input-inline {
        flex: 1;
        border: none;
        background: transparent;
        font-size: 0.9rem;
        outline: none;
        padding: 8px 0;
        color: #1e293b;
        min-height: 20px;
    }

    .comment-input-inline::placeholder {
        color: #94a3b8;
    }

    .comment-submit-btn {
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        flex-shrink: 0;
    }

    .comment-submit-btn:hover:not(:disabled) {
        background: var(--primary-dark);
        transform: scale(1.05);
    }

    .comment-submit-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .no-comments-inline {
        text-align: center;
        padding: 30px 20px;
        color: #94a3b8;
        font-size: 0.9rem;
    }

    .no-comments-inline i {
        font-size: 2rem;
        margin-bottom: 12px;
        display: block;
        color: #cbd5e1;
    }

    .login-to-comment {
        text-align: center;
        padding: 16px;
        background: white;
        border-top: 1px solid #e2e8f0;
        font-size: 0.9rem;
        color: #64748b;
    }

    .login-to-comment a {
        color: var(--primary);
        font-weight: 600;
        text-decoration: none;
    }

    .login-to-comment a:hover {
        text-decoration: underline;
    }

    /* Voir plus de commentaires */
    .load-more-comments {
        text-align: center;
        padding: 12px;
        color: var(--primary);
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }

    .load-more-comments:hover {
        background: #f8fafc;
    }

    /* =========================================
       POPUP DETAIL PUBLICATION
       ========================================= */
    .ad-detail-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 10000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        backdrop-filter: blur(4px);
    }

    .ad-detail-overlay.active {
        display: flex;
    }

    .ad-detail-popup {
        background: white;
        border-radius: 16px;
        width: 100%;
        max-width: 700px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
        animation: popupSlideIn 0.3s ease;
        position: relative;
    }

    @keyframes popupSlideIn {
        from { opacity: 0; transform: translateY(30px) scale(0.97); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .ad-detail-close {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 36px;
        height: 36px;
        border: none;
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border-radius: 50%;
        cursor: pointer;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        transition: background 0.2s;
    }

    .ad-detail-close:hover {
        background: rgba(0, 0, 0, 0.7);
    }

    .ad-detail-photos {
        position: relative;
        width: 100%;
        background: #f1f5f9;
    }

    .ad-detail-photos-grid {
        display: grid;
        gap: 3px;
    }

    .ad-detail-photos-grid.single { grid-template-columns: 1fr; }
    .ad-detail-photos-grid.double { grid-template-columns: repeat(2, 1fr); }
    .ad-detail-photos-grid.triple { grid-template-columns: repeat(3, 1fr); }

    .ad-detail-photo {
        height: 280px;
        overflow: hidden;
        cursor: pointer;
        position: relative;
    }

    .ad-detail-photos-grid.double .ad-detail-photo,
    .ad-detail-photos-grid.triple .ad-detail-photo {
        height: 200px;
    }

    .ad-detail-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }

    .ad-detail-photo:hover img {
        transform: scale(1.03);
    }

    .ad-detail-no-photo {
        height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        font-size: 3rem;
        border-radius: 16px 16px 0 0;
    }

    .ad-detail-content {
        padding: 20px 24px;
    }

    .ad-detail-user {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }

    .ad-detail-user-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
    }

    .ad-detail-user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .ad-detail-user-avatar-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1.2rem;
    }

    .ad-detail-user-info h4 {
        margin: 0 0 2px;
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
    }

    .ad-detail-user-info p {
        margin: 0;
        font-size: 0.8rem;
        color: #64748b;
    }

    .ad-detail-title {
        font-size: 1.35rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 8px;
        line-height: 1.3;
    }

    .ad-detail-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .ad-detail-badges .badge-item {
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .ad-detail-badges .badge-category {
        background: #eff6ff;
        color: var(--primary);
    }

    .ad-detail-badges .badge-urgent {
        background: #fef2f2;
        color: #dc2626;
    }

    .ad-detail-badges .badge-location {
        background: #f0fdf4;
        color: #16a34a;
    }

    .ad-detail-badges .badge-reply {
        background: #fefce8;
        color: #ca8a04;
    }

    .ad-detail-price {
        font-size: 1.4rem;
        font-weight: 800;
        color: #059669;
        margin-bottom: 16px;
    }

    .ad-detail-description {
        font-size: 0.95rem;
        line-height: 1.7;
        color: #475569;
        margin-bottom: 20px;
        white-space: pre-line;
    }

    .ad-detail-actions {
        display: flex;
        gap: 10px;
        padding: 14px 24px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .ad-detail-actions button,
    .ad-detail-actions a {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 10px 16px;
        border: none;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }

    .ad-detail-btn-like {
        background: #fef2f2;
        color: #dc2626;
    }

    .ad-detail-btn-like:hover {
        background: #fee2e2;
    }

    .ad-detail-btn-like.liked {
        background: #dc2626;
        color: white;
    }

    .ad-detail-btn-contact {
        background: var(--primary);
        color: white;
    }

    .ad-detail-btn-contact:hover {
        opacity: 0.9;
    }

    .ad-detail-btn-share {
        background: #f1f5f9;
        color: #475569;
    }

    .ad-detail-btn-share:hover {
        background: #e2e8f0;
    }

    .ad-detail-btn-save {
        background: #fefce8;
        color: #ca8a04;
    }

    .ad-detail-btn-save:hover {
        background: #fef9c3;
    }

    /* Comments section inside popup */
    .ad-detail-comments {
        border-top: 1px solid #e2e8f0;
        padding: 16px 24px;
    }

    .ad-detail-comments h5 {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ad-detail-comments .comments-list-inline {
        max-height: 300px;
        overflow-y: auto;
        padding: 0;
    }

    .ad-detail-comments .comment-form-inline {
        margin-top: 12px;
    }

    @media (max-width: 768px) {
        .ad-detail-popup {
            max-width: 100%;
            max-height: 95vh;
            margin: 10px;
            border-radius: 12px;
        }

        .ad-detail-photo {
            height: 200px;
        }

        .ad-detail-actions {
            flex-wrap: wrap;
        }

        .ad-detail-actions button,
        .ad-detail-actions a {
            flex: 1 1 45%;
        }
    }

    @media (max-width: 576px) {
        .ad-detail-overlay {
            padding: 8px;
        }
        .ad-detail-popup {
            margin: 0;
            max-height: 100vh;
            border-radius: 10px;
        }
        .ad-detail-photo {
            height: 180px;
        }
        .ad-detail-content {
            padding: 12px 14px;
        }
        .ad-detail-title {
            font-size: 1.1rem;
        }
        .ad-detail-price {
            font-size: 1.2rem;
        }
        .ad-detail-description {
            font-size: 0.85rem;
        }
        .ad-detail-actions {
            padding: 10px 14px;
            gap: 8px;
        }
        .ad-detail-actions button,
        .ad-detail-actions a {
            flex: 1 1 100%;
            font-size: 0.82rem;
            padding: 10px;
        }
        .ad-detail-comments {
            padding: 12px 14px;
        }
        .ad-detail-candidature {
            padding: 0 14px 10px;
        }
    }

    @media (max-width: 420px) {
        .ad-detail-photo {
            height: 150px;
        }
        .ad-detail-photos-grid.double .ad-detail-photo,
        .ad-detail-photos-grid.triple .ad-detail-photo {
            height: 120px;
        }
        .ad-detail-close {
            width: 30px;
            height: 30px;
            top: 8px;
            right: 8px;
            font-size: 0.9rem;
        }
    }

    /* =========================================
       TOAST NOTIFICATIONS AMÉLIORÉES
       ========================================= */
    .toast-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .toast-custom {
        background: white;
        border-radius: 12px;
        padding: 14px 18px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 280px;
        animation: slideInToast 0.3s ease;
    }

    @keyframes slideInToast {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .toast-custom.success {
        border-left: 4px solid #22c55e;
    }

    .toast-custom.error {
        border-left: 4px solid #ef4444;
    }

    .toast-custom.info {
        border-left: 4px solid var(--primary);
    }

    .toast-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .toast-custom.success .toast-icon {
        background: #dcfce7;
        color: #16a34a;
    }

    .toast-custom.error .toast-icon {
        background: #fee2e2;
        color: #dc2626;
    }

    .toast-custom.info .toast-icon {
        background: #eff6ff;
        color: var(--primary);
    }

    .toast-content {
        flex: 1;
    }

    .toast-title {
        font-weight: 700;
        font-size: 0.95rem;
        color: #1e293b;
        margin-bottom: 2px;
    }

    .toast-message {
        font-size: 0.85rem;
        color: #64748b;
    }

    .toast-close {
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 4px;
        border-radius: 50%;
        transition: all 0.15s;
    }

    .toast-close:hover {
        background: #f1f5f9;
        color: #475569;
    }

    /* =========================================
       GEOLOCATION INDICATOR & RADIUS
       ========================================= */
    .geo-banner {
        background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
        border-bottom: 1px solid #c7d2fe;
        padding: 8px 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        flex-wrap: wrap;
        font-size: 0.85rem;
        color: #4338ca;
    }

    .geo-banner-icon {
        font-size: 1rem;
        color: #6366f1;
    }

    .geo-banner-text {
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .geo-banner-city {
        font-weight: 700;
        color: #312e81;
    }

    .geo-radius-control {
        display: flex;
        align-items: center;
        gap: 8px;
        background: white;
        border-radius: 20px;
        padding: 4px 14px;
        border: 1px solid #c7d2fe;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }

    .geo-radius-control label {
        font-size: 0.78rem;
        color: #6366f1;
        font-weight: 600;
        margin: 0;
        white-space: nowrap;
    }

    .geo-radius-select {
        border: none;
        background: transparent;
        color: #312e81;
        font-weight: 700;
        font-size: 0.82rem;
        cursor: pointer;
        padding: 2px 4px;
        outline: none;
    }

    .geo-radius-select:focus {
        outline: none;
    }

    .geo-banner-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .geo-btn-precise {
        background: white;
        border: 1px solid #c7d2fe;
        color: #6366f1;
        font-size: 0.75rem;
        padding: 3px 10px;
        border-radius: 14px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 4px;
        font-weight: 500;
    }

    .geo-btn-precise:hover {
        background: #6366f1;
        color: white;
        border-color: #6366f1;
    }

    .geo-btn-disable {
        background: transparent;
        border: none;
        color: #a5b4fc;
        font-size: 0.78rem;
        cursor: pointer;
        padding: 2px 6px;
        transition: color 0.2s;
    }

    .geo-btn-disable:hover {
        color: #ef4444;
    }

    .geo-source-badge {
        font-size: 0.68rem;
        background: #c7d2fe;
        color: #4338ca;
        padding: 1px 7px;
        border-radius: 8px;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .geo-banner {
            padding: 6px 12px;
            gap: 8px;
            font-size: 0.78rem;
        }
        .geo-radius-control {
            padding: 3px 10px;
        }
    }

    /* ===== CRÉER UN POST (style LinkedIn) ===== */
    .create-post-card {
        background: white;
        border-radius: 1rem;
        border: 1px solid #e2e8f0;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .create-post-inner {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .create-post-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
    }
    .create-post-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .create-post-avatar-placeholder {
        width: 100%; height: 100%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex; align-items: center; justify-content: center;
        color: white; font-weight: 700; font-size: 1rem;
    }
    .create-post-input {
        flex: 1;
        padding: 0.625rem 1rem;
        border-radius: 9999px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #64748b;
        font-size: 0.95rem;
        text-decoration: none;
        display: block;
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s;
    }
    .create-post-input:hover { background: #f1f5f9; border-color: #cbd5e1; color: #64748b; }
    .create-post-actions {
        display: flex;
        gap: 0.25rem;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid #e2e8f0;
    }
    .create-post-action {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px;
        border-radius: 8px;
        background: none;
        border: none;
        color: #65676b;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.15s;
    }
    .create-post-action:hover { background: #f1f5f9; color: #475569; }
    .create-post-action i { font-size: 1.1rem; }

    /* ===== AVATAR HEADER SIDEBAR ===== */
    .sidebar-user-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        margin-bottom: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        text-decoration: none;
        color: inherit;
        transition: box-shadow 0.2s;
    }
    .sidebar-user-header:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); color: inherit; }
    .sidebar-user-header-avatar {
        width: 48px; height: 48px; border-radius: 50%;
        overflow: hidden; flex-shrink: 0;
    }
    .sidebar-user-header-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .sidebar-user-header-avatar-placeholder {
        width: 100%; height: 100%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex; align-items: center; justify-content: center;
        color: white; font-weight: 700; font-size: 1.1rem;
    }
    .sidebar-user-header-name { font-weight: 700; font-size: 0.95rem; color: #0f172a; }
    .sidebar-user-header-sub { font-size: 0.78rem; color: #64748b; margin-top: 2px; }

    /* ===== POST URGENT DANS LE FLUX ===== */
    .fb-post.urgent-flow {
        border: 1px solid #e5e7eb;
        border-left: 3px solid #dc2626;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }
    .fb-post.urgent-flow:hover {
        border-color: #d1d5db;
        border-left-color: #dc2626;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .urgent-flow-badge {
        display: inline-flex; align-items: center; gap: 4px;
        background: #dc2626;
        color: white; font-size: 0.68rem; font-weight: 600;
        padding: 3px 10px; border-radius: 6px;
        text-transform: uppercase; letter-spacing: 0.3px;
    }

    /* ===== PRIX VISIBLE SUR PHOTO ===== */
    .fb-post-photos { position: relative; }
    .price-overlay-badge {
        position: absolute; bottom: 12px; right: 12px;
        background: rgba(5, 150, 105, 0.92);
        color: white; padding: 6px 14px; border-radius: 20px;
        font-size: 0.95rem; font-weight: 800;
        backdrop-filter: blur(4px);
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        z-index: 5;
    }

    /* ===== CARTE PRO INLINE (interspersée dans le feed) ===== */
    .pro-inline-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.25rem;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .pro-inline-header {
        display: flex; align-items: center; gap: 8px; margin-bottom: 1rem;
        font-size: 0.85rem; color: var(--primary); font-weight: 600;
    }
    .pro-inline-grid {
        display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;
    }
    .pro-inline-item {
        display: flex; align-items: center; gap: 10px; padding: 0.75rem;
        background: #f8fafc; border-radius: 0.75rem; text-decoration: none;
        color: inherit; border: 1px solid #e2e8f0; transition: all 0.2s;
    }
    .pro-inline-item:hover {
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1);
        transform: translateY(-2px); color: inherit;
    }
    .pro-inline-avatar {
        width: 40px; height: 40px; border-radius: 50%; overflow: hidden; flex-shrink: 0;
    }
    .pro-inline-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .pro-inline-avatar-placeholder {
        width: 100%; height: 100%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex; align-items: center; justify-content: center;
        color: white; font-weight: 700; font-size: 0.9rem;
    }
    .pro-inline-name { font-weight: 600; font-size: 0.85rem; color: #1e293b; }
    .pro-inline-cat { font-size: 0.72rem; color: #64748b; }
    @media (max-width: 576px) { .pro-inline-grid { grid-template-columns: 1fr; } }

    /* ===== FILTRES SECONDAIRES TOGGLE ===== */
    .filter-bar:not(.show-all-filters) .secondary-filter { display: none !important; }
    .more-filters-btn { position: relative; }
    .filter-active-badge {
        position: absolute; top: -6px; right: -6px;
        background: #dc2626; color: white;
        font-size: 0.65rem; font-weight: 700;
        width: 18px; height: 18px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
    }

    /* ===== INFINITE SCROLL LOADER ===== */
    .infinite-scroll-trigger { text-align: center; padding: 30px 20px; }
    .infinite-scroll-spinner {
        display: inline-block; width: 36px; height: 36px;
        border: 3px solid #e2e8f0; border-top-color: var(--primary);
        border-radius: 50%; animation: spin 0.8s linear infinite;
    }
    .infinite-scroll-end { color: #94a3b8; font-size: 0.85rem; padding: 10px; }

    /* ===== SIDEBAR DROITE - WIDGETS ===== */
    .right-sidebar-card {
        background: white; border: 1px solid #e2e8f0;
        border-radius: 14px; padding: 18px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .right-sidebar-title {
        font-size: 0.9rem; font-weight: 700; color: #1e293b;
        margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
    }
    .right-sidebar-pro-item {
        display: flex; align-items: center; gap: 10px; padding: 8px 0;
        text-decoration: none; color: inherit; border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s;
    }
    .right-sidebar-pro-item:last-child { border-bottom: none; }
    .right-sidebar-pro-item:hover { color: var(--primary); }
    .right-sidebar-pro-avatar {
        width: 36px; height: 36px; border-radius: 50%;
        overflow: hidden; flex-shrink: 0;
    }
    .right-sidebar-pro-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .right-sidebar-pro-avatar-placeholder {
        width: 100%; height: 100%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex; align-items: center; justify-content: center;
        color: white; font-weight: 700; font-size: 0.8rem;
    }
    .right-sidebar-pro-name { font-weight: 600; font-size: 0.82rem; color: #1e293b; }
    .right-sidebar-pro-meta { font-size: 0.7rem; color: #64748b; }

    /* ===== POPUP CATÉGORIES MULTI-STEP (style Yoojo) ===== */
    .category-popup-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        justify-content: center;
        align-items: flex-start;
        padding-top: 60px;
    }
    .category-popup-overlay.active { display: flex; }
    .category-popup {
        background: white;
        border-radius: 16px;
        width: 92%;
        max-width: 580px;
        max-height: 80vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        animation: popupSlideIn 0.25s ease;
        position: relative;
        overflow: hidden;
    }
    @keyframes popupSlideIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .category-popup-header {
        padding: 20px 24px 16px;
        border-bottom: 1px solid #e5e7eb;
    }
    .category-popup-breadcrumb {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.78rem;
        color: #94a3b8;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    .category-popup-breadcrumb span { cursor: default; }
    .category-popup-breadcrumb .bc-link {
        color: var(--primary);
        cursor: pointer;
        font-weight: 500;
    }
    .category-popup-breadcrumb .bc-link:hover { text-decoration: underline; }
    .category-popup-breadcrumb .bc-sep { color: #d1d5db; }
    .category-popup-breadcrumb .bc-current { color: #1e293b; font-weight: 600; }
    .category-popup-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #111827;
        margin: 0 0 12px 0;
    }
    .category-popup-search {
        width: 100%;
        padding: 12px 16px 12px 42px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 0.95rem;
        outline: none;
        transition: border-color 0.2s;
        background: #f9fafb url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%239ca3af'%3E%3Cpath fill-rule='evenodd' d='M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z'/%3E%3C/svg%3E") no-repeat 14px center;
        background-size: 18px;
    }
    .category-popup-search:focus { border-color: var(--primary); background-color: white; }
    .category-popup-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: none;
        border: none;
        color: #6366f1;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        padding: 0;
        margin-bottom: 8px;
    }
    .category-popup-back:hover { color: #4f46e5; }
    .category-popup-body {
        overflow-y: auto;
        padding: 8px 16px 16px;
        flex: 1;
    }
    .category-popup-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px 14px;
        border-radius: 10px;
        text-decoration: none;
        color: #1e293b;
        font-size: 0.9rem;
        font-weight: 500;
        transition: background 0.15s, border-color 0.15s;
        cursor: pointer;
        border: 1px solid transparent;
    }
    .category-popup-item:hover {
        background: #f0f4ff;
        border-color: #dbeafe;
        color: var(--primary);
    }
    .category-popup-item-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #f1f5f9;
        color: #6366f1;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .category-popup-item-text { flex: 1; min-width: 0; }
    .category-popup-item-name { font-weight: 600; font-size: 0.92rem; }
    .category-popup-item-desc { font-size: 0.75rem; color: #94a3b8; margin-top: 2px; }
    .category-popup-item-arrow { color: #d1d5db; font-size: 0.75rem; flex-shrink: 0; }
    .category-popup-no-result {
        text-align: center;
        padding: 32px 16px;
        color: #94a3b8;
    }
    .category-popup-no-result i { font-size: 2rem; margin-bottom: 8px; display: block; opacity: 0.5; }
    .category-popup-close {
        position: absolute;
        top: 16px;
        right: 16px;
        background: none;
        border: none;
        font-size: 1.3rem;
        color: #9ca3af;
        cursor: pointer;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background 0.15s;
        z-index: 2;
    }
    .category-popup-close:hover { background: #f3f4f6; color: #374151; }
    /* Step 3 - Description */
    .category-popup-step3 { padding: 20px 24px; }
    .category-popup-step3-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    .category-popup-step3-textarea {
        width: 100%;
        min-height: 100px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px;
        font-size: 0.92rem;
        outline: none;
        resize: vertical;
        font-family: inherit;
        transition: border-color 0.2s;
    }
    .category-popup-step3-textarea:focus { border-color: var(--primary); }
    .category-popup-step3-submit {
        display: block;
        width: 100%;
        margin-top: 14px;
        padding: 12px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.95rem;
        cursor: pointer;
        transition: opacity 0.15s;
    }
    .category-popup-step3-submit:hover { opacity: 0.9; }
    .category-popup-selection-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #ede9fe;
        color: #6366f1;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        margin-bottom: 12px;
    }
    .category-popup-selection-tag i { font-size: 0.7rem; }
    /* Step progress dots */
    .category-popup-steps-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 20px;
    }
    .step-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #e5e7eb;
        transition: all 0.3s;
    }
    .step-dot.active {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        transform: scale(1.2);
    }
    .step-dot.done {
        background: #10b981;
    }
    .step-dot-line {
        width: 24px;
        height: 2px;
        background: #e5e7eb;
    }
    .step-dot-line.done {
        background: #10b981;
    }
    .category-popup-skip {
        display: block;
        text-align: center;
        margin-top: 10px;
        color: #9ca3af;
        font-size: 0.82rem;
        cursor: pointer;
        text-decoration: none;
        transition: color 0.15s;
    }
    .category-popup-skip:hover { color: #6366f1; }
    .category-popup-next-info {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 16px;
        padding: 10px 14px;
        background: #f0fdf4;
        border-radius: 10px;
        color: #166534;
        font-size: 0.8rem;
    }
    .category-popup-next-info i { color: #10b981; font-size: 1rem; }

    /* ===== POPUP FULL FORM (Step 3) ===== */
    .popup-form-group { margin-bottom: 16px; }
    .popup-form-label {
        display: block;
        font-size: 0.82rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }
    .popup-form-label .required { color: #ef4444; }
    .popup-form-input, .popup-form-select {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 0.9rem;
        outline: none;
        transition: border-color 0.2s;
        background: #f9fafb;
        font-family: inherit;
        box-sizing: border-box;
    }
    .popup-form-input:focus, .popup-form-select:focus {
        border-color: #6366f1;
        background: white;
    }
    .popup-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .popup-form-row-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 10px;
    }
    @media (max-width: 520px) {
        .popup-form-row, .popup-form-row-3 { grid-template-columns: 1fr; }
    }
    .popup-photo-upload {
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        padding: 16px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        background: #fafbfc;
    }
    .popup-photo-upload:hover {
        border-color: #6366f1;
        background: #f0f4ff;
    }
    .popup-photo-upload i { font-size: 1.5rem; color: #6366f1; display: block; margin-bottom: 6px; }
    .popup-photo-upload span { font-size: 0.8rem; color: #6b7280; }
    .popup-photo-previews {
        display: flex;
        gap: 8px;
        margin-top: 10px;
        flex-wrap: wrap;
    }
    .popup-photo-preview {
        position: relative;
        width: 64px;
        height: 64px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,0.1);
    }
    .popup-photo-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .popup-photo-preview .popup-photo-remove {
        position: absolute;
        top: 2px; right: 2px;
        width: 20px; height: 20px;
        background: rgba(0,0,0,0.6);
        border: none;
        border-radius: 50%;
        color: white;
        font-size: 0.65rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .popup-photo-preview .popup-photo-remove:hover { background: #ef4444; }
    .popup-form-hint {
        font-size: 0.75rem;
        color: #9ca3af;
        margin-top: 4px;
    }
    .popup-form-error {
        color: #ef4444;
        font-size: 0.78rem;
        margin-top: 4px;
    }
    .popup-success-container {
        text-align: center;
        padding: 20px 0;
    }
    .popup-success-icon {
        width: 70px; height: 70px;
        margin: 0 auto 16px;
        background: linear-gradient(135deg, #10b981, #34d399);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .popup-success-icon i { font-size: 2rem; color: white; }
    .popup-success-title { font-size: 1.15rem; font-weight: 700; color: #111827; margin-bottom: 6px; }
    .popup-success-text { font-size: 0.88rem; color: #6b7280; margin-bottom: 20px; }
    .popup-success-actions { display: flex; gap: 10px; justify-content: center; }
    .popup-success-btn {
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        border: none;
        transition: opacity 0.15s;
        text-decoration: none;
    }
    .popup-success-btn:hover { opacity: 0.85; }
    .popup-success-btn-primary {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
    }
    .popup-success-btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    .popup-submit-spinner {
        display: inline-block;
        width: 18px; height: 18px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: popupSpin 0.6s linear infinite;
        vertical-align: middle;
        margin-right: 8px;
    }
    @keyframes popupSpin { to { transform: rotate(360deg); } }
    .category-popup-step3 {
        overflow-y: auto;
        max-height: calc(80vh - 120px);
    }

    /* ===== WIDGET DEVENIR PRO (sidebar) ===== */
    .pro-widget {
        background: linear-gradient(135deg, #0f766e, #0e7490);
        border-radius: 14px;
        padding: 20px 18px;
        color: white;
        position: relative;
        overflow: hidden;
    }
    .pro-widget.pro-widget--active {
        background: white;
        color: #111827;
        border: 1px solid #e2e8f0;
    }
    .pro-widget.pro-widget--active .pro-widget-badge {
        background: #dcfce7;
        color: #166534;
    }
    .pro-widget.pro-widget--active h3 { color: #111827; }
    .pro-widget.pro-widget--active p { color: #6b7280; opacity: 1; }
    .pro-widget::before {
        content: '';
        position: absolute;
        top: -30px;
        right: -30px;
        width: 100px;
        height: 100px;
        background: rgba(255,255,255,0.06);
        border-radius: 50%;
    }
    .pro-widget-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(255,255,255,0.15);
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 12px;
    }
    .pro-widget h3 {
        font-size: 0.95rem;
        font-weight: 700;
        margin-bottom: 6px;
        line-height: 1.3;
    }
    .pro-widget p {
        font-size: 0.76rem;
        opacity: 0.8;
        margin-bottom: 16px;
        line-height: 1.5;
    }
    .pro-widget-stats {
        display: flex;
        gap: 0;
        margin-bottom: 16px;
        background: rgba(255,255,255,0.08);
        border-radius: 10px;
        overflow: hidden;
    }
    .pro-widget-stat {
        flex: 1;
        text-align: center;
        padding: 8px 4px;
    }
    .pro-widget-stat + .pro-widget-stat {
        border-left: 1px solid rgba(255,255,255,0.1);
    }
    .pro-widget-stat strong {
        display: block;
        font-size: 1.05rem;
        font-weight: 800;
    }
    .pro-widget-stat small {
        font-size: 0.62rem;
        opacity: 0.7;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .pro-widget-cta {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 11px 16px;
        background: linear-gradient(135deg, #a78bfa, #7c3aed);
        border: 2px solid rgba(255,255,255,0.2);
        border-radius: 10px;
        color: white;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }
    .pro-widget-cta:hover {
        background: linear-gradient(135deg, #c4b5fd, #8b5cf6);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(124,58,237,0.4);
        color: white;
    }
    .pro-widget-cta-active {
        background: linear-gradient(135deg, #10b981, #059669);
        border-color: rgba(255,255,255,0.25);
    }
    .pro-widget-cta-active:hover {
        background: linear-gradient(135deg, #34d399, #10b981);
        box-shadow: 0 6px 20px rgba(16,185,129,0.4);
    }

    /* ===== POPUP DEVENIR PRO (multi-step) ===== */
    .sub-popup-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.55);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }
    .sub-popup-overlay.active { display: flex; }
    .sub-popup {
        background: white;
        border-radius: 20px;
        width: 94%;
        max-width: 520px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 80px rgba(0,0,0,0.25);
        animation: popupSlideIn 0.3s ease;
        position: relative;
    }
    .sub-popup-close {
        position: absolute;
        top: 14px;
        right: 14px;
        background: rgba(0,0,0,0.05);
        border: none;
        font-size: 1.1rem;
        color: #9ca3af;
        cursor: pointer;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        z-index: 2;
        transition: all 0.15s;
    }
    .sub-popup-close:hover { background: #f3f4f6; color: #374151; }

    /* Hero header */
    .sub-popup-hero {
        background: linear-gradient(135deg, #312e81 0%, #4c1d95 50%, #6d28d9 100%);
        padding: 32px 28px 24px;
        text-align: center;
        color: white;
        border-radius: 20px 20px 0 0;
        position: relative;
        overflow: hidden;
    }
    .sub-popup-hero::before {
        content: '';
        position: absolute;
        top: -60px;
        right: -40px;
        width: 160px;
        height: 160px;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
    }
    .sub-popup-hero::after {
        content: '';
        position: absolute;
        bottom: -30px;
        left: -20px;
        width: 100px;
        height: 100px;
        background: rgba(255,255,255,0.04);
        border-radius: 50%;
    }
    .sub-popup-hero-icon {
        width: 64px;
        height: 64px;
        background: rgba(255,255,255,0.15);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 1.6rem;
    }
    .sub-popup-hero h3 {
        font-size: 1.3rem;
        font-weight: 800;
        margin-bottom: 6px;
    }
    .sub-popup-hero p {
        opacity: 0.85;
        font-size: 0.88rem;
        line-height: 1.5;
        max-width: 360px;
        margin: 0 auto;
    }

    /* Body content */
    .sub-popup-body {
        padding: 24px 28px 28px;
    }

    /* Step title */
    .sub-popup-step-title {
        font-size: 0.82rem;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .sub-popup-step-title i { color: #7c3aed; }

    /* Benefit cards */
    .sub-popup-benefits {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 24px;
    }
    .sub-popup-benefit {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #f1f5f9;
        transition: all 0.15s;
    }
    .sub-popup-benefit:hover { border-color: #e0e7ff; background: #f5f3ff; }
    .sub-popup-benefit-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        flex-shrink: 0;
    }
    .sub-popup-benefit-text strong {
        display: block;
        font-size: 0.8rem;
        color: #1e293b;
        margin-bottom: 2px;
    }
    .sub-popup-benefit-text small {
        font-size: 0.72rem;
        color: #94a3b8;
        line-height: 1.3;
    }

    /* Plan cards */
    .sub-popup-plans {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    .sub-popup-plan {
        flex: 1;
        border: 2px solid #e5e7eb;
        border-radius: 14px;
        padding: 16px 14px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }
    .sub-popup-plan:hover {
        border-color: #a78bfa;
        background: #faf5ff;
    }
    .sub-popup-plan.selected {
        border-color: #7c3aed;
        background: #f5f3ff;
        box-shadow: 0 0 0 3px rgba(124,58,237,0.12);
    }
    .sub-popup-plan-popular {
        position: absolute;
        top: -10px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        font-size: 0.6rem;
        font-weight: 700;
        padding: 2px 10px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .sub-popup-plan-price {
        font-size: 1.5rem;
        font-weight: 800;
        color: #111827;
        line-height: 1;
    }
    .sub-popup-plan-price small {
        font-size: 0.7rem;
        font-weight: 500;
        color: #6b7280;
    }
    .sub-popup-plan-name {
        font-size: 0.78rem;
        font-weight: 600;
        color: #374151;
        margin-top: 4px;
    }
    .sub-popup-plan-desc {
        font-size: 0.68rem;
        color: #9ca3af;
        margin-top: 2px;
    }
    .sub-popup-plan-savings {
        display: inline-block;
        margin-top: 6px;
        background: #dcfce7;
        color: #166534;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 10px;
    }

    /* Category chips */
    .sub-popup-categories {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 20px;
        max-height: 150px;
        overflow-y: auto;
        padding: 2px;
    }
    .sub-popup-cat-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border: 2px solid #e5e7eb;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 500;
        color: #374151;
        cursor: pointer;
        transition: all 0.15s;
        user-select: none;
        background: white;
    }
    .sub-popup-cat-chip:hover { border-color: #a78bfa; background: #faf5ff; }
    .sub-popup-cat-chip.selected {
        border-color: #7c3aed;
        background: #7c3aed;
        color: white;
    }
    .sub-popup-cat-chip.selected i { color: white; }
    .sub-popup-cat-chip i { font-size: 0.7rem; color: #9ca3af; }

    /* Actions */
    .sub-popup-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .sub-popup-btn-primary {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }
    .sub-popup-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 25px rgba(124,58,237,0.35);
        color: white;
    }
    .sub-popup-btn-secondary {
        display: block;
        text-align: center;
        padding: 10px;
        color: #9ca3af;
        font-size: 0.82rem;
        cursor: pointer;
        text-decoration: none;
        border: none;
        background: none;
        width: 100%;
        transition: color 0.15s;
    }
    .sub-popup-btn-secondary:hover { color: #6366f1; }

    /* Testimonial */
    .sub-popup-testimonial {
        margin-top: 16px;
        padding: 12px 16px;
        background: #fffbeb;
        border-radius: 10px;
        border: 1px solid #fde68a;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }
    .sub-popup-testimonial-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.75rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    .sub-popup-testimonial-text {
        font-size: 0.78rem;
        color: #92400e;
        line-height: 1.4;
        font-style: italic;
    }
    .sub-popup-testimonial-text strong {
        font-style: normal;
        display: block;
        margin-top: 3px;
        font-size: 0.72rem;
        color: #b45309;
    }

    /* Free mode info */
    .sub-popup-free-note {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        background: #f0fdf4;
        border-radius: 10px;
        margin-bottom: 16px;
        font-size: 0.78rem;
        color: #166534;
    }
    .sub-popup-free-note i { color: #10b981; font-size: 0.9rem; flex-shrink: 0; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

<!-- Barre de Filtres (fixée au header) -->
<div class="filter-bar-container">
    <div class="filter-bar">
        <!-- Toggle Pro / Offre / Demandes -->
        <div class="toggle-group">
            <button class="toggle-btn<?php echo e(($filterType ?? 'all') === 'all' ? '' : ''); ?>" id="togglePro" onclick="setViewMode('providers')">
                <i class="fas fa-user-tie me-1"></i> Je cherche un Pro
            </button>
            <a href="<?php echo e(route('feed', ['type' => 'offres'])); ?>" class="toggle-btn<?php echo e(($filterType ?? 'all') === 'offres' ? ' active' : (($filterType ?? 'all') === 'all' ? ' active' : '')); ?>" id="toggleOffres">
                <i class="fas fa-briefcase me-1"></i> Offres de pros
            </a>
            <a href="<?php echo e(route('feed', ['type' => 'demandes'])); ?>" class="toggle-btn<?php echo e(($filterType ?? 'all') === 'demandes' ? ' active' : ''); ?>" id="toggleDemandes">
                <i class="fas fa-search me-1"></i> Demandes
            </a>
        </div>

        <div class="filter-separator"></div>

        <!-- Catégorie -->
        <div class="filter-dropdown" id="categoryDropdown">
            <button class="filter-btn" onclick="toggleDropdown('categoryDropdown')">
                <i class="fas fa-folder"></i>
                <span id="categoryLabel">Catégorie</span>
                <i class="fas fa-chevron-down chevron"></i>
            </button>
            <div class="filter-menu" id="categoryMenu">
                <div class="filter-menu-item selected" data-value="" onclick="selectCategory('')">
                    <i class="fas fa-check"></i> Toutes les catégories
                </div>
                <?php $__currentLoopData = $missionCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $catName => $catData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="filter-menu-item" data-value="<?php echo e($catName); ?>" onclick="selectCategory('<?php echo e($catName); ?>')">
                    <i class="<?php echo e($catData['icon'] ?? 'fas fa-folder'); ?>"></i> <?php echo e($catName); ?>

                    <span class="ms-auto text-muted small"><?php echo e($catData['total'] ?? 0); ?></span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <!-- Sous-catégorie -->
        <div class="filter-dropdown" id="subcategoryDropdown" style="display: none;">
            <button class="filter-btn" onclick="toggleDropdown('subcategoryDropdown')">
                <i class="fas fa-tags"></i>
                <span id="subcategoryLabel">Sous-catégorie</span>
                <i class="fas fa-chevron-down chevron"></i>
            </button>
            <div class="filter-menu" id="subcategoryMenu">
                <!-- Rempli dynamiquement -->
            </div>
        </div>

        <!-- Filtre par -->
        <div class="filter-dropdown" id="sortDropdown">
            <button class="filter-btn" onclick="toggleDropdown('sortDropdown')">
                <i class="fas fa-sort-amount-down"></i>
                <span id="sortLabel">Trier</span>
                <i class="fas fa-chevron-down chevron"></i>
            </button>
            <div class="filter-menu">
                <div class="filter-menu-item selected" data-value="recommended" onclick="selectSort('recommended')">
                    <i class="fas fa-star"></i> Recommandé
                </div>
                <div class="filter-menu-item" data-value="recent" onclick="selectSort('recent')">
                    <i class="fas fa-clock"></i> Plus récent
                </div>
                <div class="filter-menu-item" data-value="urgent" onclick="selectSort('urgent')">
                    <i class="fas fa-bolt"></i> Urgent
                </div>
                <?php if($geoEnabled ?? false): ?>
                <div class="filter-menu-item" data-value="proximity" onclick="selectSort('proximity')">
                    <i class="fas fa-map-marker-alt"></i> Proximité
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Plus de filtres -->
        <button class="filter-btn more-filters-btn" onclick="toggleMoreFilters()">
            <i class="fas fa-sliders-h"></i>
            <span>Plus de filtres</span>
            <span class="filter-active-badge" id="activeFilterCount" style="display:none;">0</span>
        </button>

        <!-- Localisation -->
        <div class="filter-dropdown secondary-filter" id="locationDropdown">
            <button class="filter-btn" onclick="toggleDropdown('locationDropdown')">
                <i class="fas fa-map-marker-alt"></i>
                <span id="locationLabel">Localisation</span>
                <i class="fas fa-chevron-down chevron"></i>
            </button>
            <div class="filter-menu" style="min-width: 280px;">
                <div style="padding: 12px;">
                    <label class="form-label small text-muted mb-1">Pays</label>
                    <select class="form-select form-select-sm mb-2" id="countrySelect" onchange="updateFilterCities()">
                        <option value="">-- Sélectionner un pays --</option>
                        <optgroup label="France - Outre-mer">
                            <option value="Mayotte">Mayotte</option>
                            <option value="La Réunion">La Réunion</option>
                            <option value="Guadeloupe">Guadeloupe</option>
                            <option value="Martinique">Martinique</option>
                            <option value="Guyane">Guyane</option>
                        </optgroup>
                        <optgroup label="Océan Indien">
                            <option value="Madagascar">Madagascar</option>
                            <option value="Maurice">Maurice</option>
                        </optgroup>
                        <optgroup label="Europe">
                            <option value="France">France</option>
                            <option value="Belgique">Belgique</option>
                            <option value="Suisse">Suisse</option>
                        </optgroup>
                        <optgroup label="Afrique">
                            <option value="Sénégal">Sénégal</option>
                            <option value="Côte d'Ivoire">Côte d'Ivoire</option>
                            <option value="Maroc">Maroc</option>
                            <option value="Tunisie">Tunisie</option>
                            <option value="Algérie">Algérie</option>
                        </optgroup>
                        <optgroup label="Amérique">
                            <option value="Canada">Canada</option>
                        </optgroup>
                    </select>
                    
                    <label class="form-label small text-muted mb-1">Ville</label>
                    <select class="form-select form-select-sm mb-2" id="citySelect" disabled>
                        <option value="">-- Sélectionner une ville --</option>
                    </select>
                    
                    <div id="customCityWrapper" style="display: none;">
                        <input type="text" class="form-control form-control-sm mb-2" placeholder="Saisir une ville" id="cityInput">
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-danger btn-sm flex-grow-1" onclick="resetLocationFilter()">
                            <i class="fas fa-times me-1"></i> Réinitialiser
                        </button>
                        <button class="btn btn-primary btn-sm flex-grow-1" onclick="applyLocationFilter()">
                            <i class="fas fa-check me-1"></i> Appliquer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prix -->
        <div class="filter-dropdown secondary-filter" id="priceDropdown">
            <button class="filter-btn" onclick="toggleDropdown('priceDropdown')">
                <i class="fas fa-euro-sign"></i>
                <span id="priceLabel">Prix</span>
                <i class="fas fa-chevron-down chevron"></i>
            </button>
            <div class="filter-menu" style="min-width: 250px;">
                <div style="padding: 12px;">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <input type="number" class="form-control form-control-sm" placeholder="Min €" id="priceMin">
                        </div>
                        <div class="col-6">
                            <input type="number" class="form-control form-control-sm" placeholder="Max €" id="priceMax">
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <button class="btn btn-outline-secondary btn-sm" onclick="setPriceRange(0, 50)">0-50€</button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="setPriceRange(50, 100)">50-100€</button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="setPriceRange(100, 500)">100-500€</button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="setPriceRange(500, null)">500€+</button>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-danger btn-sm flex-grow-1" onclick="resetPriceFilter()">
                            <i class="fas fa-times me-1"></i> Réinitialiser
                        </button>
                        <button class="btn btn-primary btn-sm flex-grow-1" onclick="applyPriceFilter()">
                            <i class="fas fa-check me-1"></i> Appliquer
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Spacer pour compenser la barre fixe -->
<div class="filter-bar-spacer"></div>

<!-- Géolocalisation Banner -->
<?php if($geoEnabled ?? false): ?>
<div class="geo-banner" id="geoBanner">
    <span class="geo-banner-icon"><i class="fas fa-map-marker-alt"></i></span>
    <span class="geo-banner-text">
        Annonces proches de
        <span class="geo-banner-city" id="geoCityLabel"><?php echo e($geoCity ?? 'votre position'); ?><?php if($geoCountry ?? false): ?>, <?php echo e($geoCountry); ?><?php endif; ?></span>
        <span class="geo-source-badge" id="geoSourceBadge">
            <?php if(($geoSource ?? '') === 'browser'): ?> Ma position
            <?php elseif(($geoSource ?? '') === 'profile'): ?> Mon adresse
            <?php elseif(($geoSource ?? '') === 'ip'): ?> Localisation approximative
            <?php else: ?> Détection auto
            <?php endif; ?>
        </span>
    </span>
    <div class="geo-radius-control">
        <label><i class="fas fa-bullseye me-1"></i>Rayon</label>
        <select class="geo-radius-select" id="geoRadiusSelect" onchange="changeGeoRadius(this.value)">
            <option value="5" <?php echo e(($userRadius ?? 50) == 5 ? 'selected' : ''); ?>>5 km</option>
            <option value="10" <?php echo e(($userRadius ?? 50) == 10 ? 'selected' : ''); ?>>10 km</option>
            <option value="25" <?php echo e(($userRadius ?? 50) == 25 ? 'selected' : ''); ?>>25 km</option>
            <option value="50" <?php echo e(($userRadius ?? 50) == 50 ? 'selected' : ''); ?>>50 km</option>
            <option value="100" <?php echo e(($userRadius ?? 50) == 100 ? 'selected' : ''); ?>>100 km</option>
            <option value="200" <?php echo e(($userRadius ?? 50) == 200 ? 'selected' : ''); ?>>200 km</option>
            <option value="500" <?php echo e(($userRadius ?? 50) == 500 ? 'selected' : ''); ?>>500 km</option>
        </select>
    </div>
    <div class="geo-banner-actions">
        <button class="geo-btn-precise" id="geoPreciseBtn" onclick="requestBrowserGeolocation()" title="Utiliser votre GPS pour une localisation précise">
            <i class="fas fa-crosshairs"></i> Préciser
        </button>
        <button class="geo-btn-disable" onclick="disableGeoFiltering()" title="Voir toutes les annonces sans filtre géographique">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
<?php endif; ?>

<!-- Notification d'élargissement du rayon -->
<?php if($radiusWasExpanded ?? false): ?>
<div class="geo-expanded-banner" style="background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 1px solid #f59e0b; border-radius: 10px; padding: 12px 18px; margin: 0 auto 16px; max-width: 1200px; display: flex; align-items: center; gap: 10px; font-size: 0.85rem;">
    <i class="fas fa-search-location" style="color: #d97706; font-size: 1.1rem;"></i>
    <span style="color: #92400e;">
        Peu de résultats à <strong><?php echo e($originalRadius ?? 50); ?> km</strong> — rayon élargi à <strong><?php echo e($userRadius); ?> km</strong> pour afficher plus d'annonces.
    </span>
    <a href="<?php echo e(route('feed', ['radius' => $originalRadius ?? 50, 'type' => $filterType ?? 'all'])); ?>" style="margin-left: auto; color: #d97706; font-weight: 600; font-size: 0.8rem; text-decoration: none; white-space: nowrap;">
        <i class="fas fa-undo me-1"></i>Revenir à <?php echo e($originalRadius ?? 50); ?> km
    </a>
</div>
<?php endif; ?>

<!-- Contenu Principal -->
<div class="content-container">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Section Prestataires (cachée par défaut) -->
    <div id="providersSection" style="display: none;">
        <div class="text-center mb-4">
            <h2 class="fw-bold mb-1" id="providersSectionTitle">Nos meilleurs prestataires</h2>
            <p class="text-muted mb-0" id="providersSectionSubtitle">Professionnels vérifiés et recommandés</p>
        </div>
        <div class="providers-grid" id="providersGrid">
            <?php if(isset($premiumPros) && $premiumPros->count() > 0): ?>
                <?php $__currentLoopData = $premiumPros; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pro): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('profile.public', $pro->id)); ?>" class="provider-card">
                    <div class="provider-image-wrapper">
                        <?php if($pro->avatar): ?>
                            <img src="<?php echo e(asset('storage/' . $pro->avatar)); ?>" alt="<?php echo e($pro->name); ?>">
                        <?php else: ?>
                            <div class="provider-image-placeholder">
                                <?php echo e(strtoupper(substr($pro->name, 0, 1))); ?>

                            </div>
                        <?php endif; ?>
                        <?php if($pro->profession): ?>
                        <div class="provider-badge-top"><i class="fas fa-briefcase"></i> <?php echo e(Str::limit($pro->profession, 25)); ?></div>
                        <?php elseif($pro->service_category): ?>
                        <div class="provider-badge-top"><i class="fas fa-briefcase"></i> <?php echo e(Str::limit($pro->service_category, 25)); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="provider-card-info">
                        <div class="provider-info-header">
                            <h3 class="provider-name">
                                <?php echo e(Str::limit($pro->name, 18)); ?>

                                <?php if($pro->user_type === 'professionnel' || $pro->hasActiveProSubscription() || $pro->hasCompletedProOnboarding()): ?>
                                <span class="badge-pro">PRO</span>
                                <?php endif; ?>
                            </h3>
                            <?php if($pro->hourly_rate): ?>
                            <span class="provider-price"><?php echo e($pro->hourly_rate); ?> €/h</span>
                            <?php endif; ?>
                        </div>
                        <div class="provider-rating">
                            <span class="star-icon">★</span>
                            <span class="rating-value"><?php echo e($pro->reviews_avg_rating ? number_format($pro->reviews_avg_rating, 1, ',', '') : 'Nouveau'); ?></span>
                            <span class="reviews-count">(<?php echo e($pro->reviews_count ?? 0); ?> avis)</span>
                        </div>
                        <?php if($pro->bio): ?>
                        <p class="provider-category"><?php echo e(Str::limit($pro->bio, 40)); ?></p>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php else: ?>
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="fas fa-users"></i>
                    <h3>Aucun prestataire disponible</h3>
                    <p>Revenez bientôt pour découvrir nos professionnels</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section Missions (affichée par défaut) -->
    <div id="missionsSection">
        
        <div class="text-center mb-4" style="max-width: 1060px; margin: 0 auto;">
            <h2 class="fw-bold mb-1" style="color: var(--text-dark); font-size: 1.5rem;" id="missionsSectionTitle">
                <i class="fas fa-fire" style="color: var(--accent); margin-right: 6px;"></i>
                <?php if(($filterType ?? 'all') === 'demandes'): ?>
                    Dernières demandes de clients
                <?php elseif(($filterType ?? 'all') === 'offres'): ?>
                    Offres de professionnels
                <?php else: ?>
                    Dernières publications
                <?php endif; ?>
            </h2>
            <p class="text-muted mb-0" style="font-size: 0.9rem;" id="missionsSectionSubtitle">
                <?php if(($filterType ?? 'all') === 'demandes'): ?>
                    Des particuliers recherchent vos compétences
                <?php elseif(($filterType ?? 'all') === 'offres'): ?>
                    Trouvez le professionnel qu'il vous faut
                <?php else: ?>
                    Trouvez des opportunités près de chez vous
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Layout 2 colonnes: Feed (gauche) + Sidebar (droite) -->
        <div class="feed-layout">
            <!-- Sidebar utilisateur (affichée à gauche via CSS order: 1) -->
            <div class="feed-sidebar-left">
                
                <?php
                    $feedVerification = \App\Models\IdentityVerification::where('user_id', Auth::id())->latest()->first();
                ?>
                <?php if($feedVerification && $feedVerification->status === 'returned'): ?>
                <a href="<?php echo e(route('verification.index')); ?>" style="display: block; background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 2px solid #f59e0b; border-radius: 10px; padding: 14px 16px; margin-bottom: 12px; text-decoration: none; transition: transform 0.15s;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(245,158,11,0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-exclamation-triangle" style="color: #d97706;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: #b45309; font-size: 0.82rem;">Corrections requises</div>
                            <div style="font-size: 0.72rem; color: #92400e; line-height: 1.3;">Votre vérification nécessite des modifications</div>
                        </div>
                        <i class="fas fa-chevron-right" style="color: #d97706; margin-left: auto; font-size: 0.7rem;"></i>
                    </div>
                </a>
                <?php elseif($feedVerification && $feedVerification->status === 'pending'): ?>
                <a href="<?php echo e(route('verification.index')); ?>" style="display: block; background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 1px solid #93c5fd; border-radius: 10px; padding: 14px 16px; margin-bottom: 12px; text-decoration: none;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(59,130,246,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-clock" style="color: #2563eb;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: #1e40af; font-size: 0.82rem;">Vérification en cours</div>
                            <div style="font-size: 0.72rem; color: #3b82f6; line-height: 1.3;">Vos documents sont en cours d'examen</div>
                        </div>
                    </div>
                </a>
                <?php elseif($feedVerification && $feedVerification->status === 'rejected'): ?>
                <a href="<?php echo e(route('verification.index')); ?>" style="display: block; background: linear-gradient(135deg, #fef2f2, #fee2e2); border: 1px solid #fca5a5; border-radius: 10px; padding: 14px 16px; margin-bottom: 12px; text-decoration: none;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(239,68,68,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-times-circle" style="color: #dc2626;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: #dc2626; font-size: 0.82rem;">Vérification refusée</div>
                            <div style="font-size: 0.72rem; color: #991b1b; line-height: 1.3;">Soumettez une nouvelle demande</div>
                        </div>
                        <i class="fas fa-chevron-right" style="color: #dc2626; margin-left: auto; font-size: 0.7rem;"></i>
                    </div>
                </a>
                <?php elseif(!Auth::user()->is_verified): ?>
                <a href="<?php echo e(route('verification.index')); ?>" style="display: block; background: linear-gradient(135deg, #ecfdf5, #d1fae5); border: 1px solid #86efac; border-radius: 10px; padding: 14px 16px; margin-bottom: 12px; text-decoration: none;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(16,185,129,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-shield-alt" style="color: #059669;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: #059669; font-size: 0.82rem;">Vérifiez votre profil</div>
                            <div style="font-size: 0.72rem; color: #16a34a; line-height: 1.3;">Obtenez le badge vérifié</div>
                        </div>
                        <i class="fas fa-chevron-right" style="color: #059669; margin-left: auto; font-size: 0.7rem;"></i>
                    </div>
                </a>
                <?php endif; ?>

                
                <div class="sidebar-menu-card">
                    <div class="sidebar-menu-links">
                        <a class="sidebar-menu-link" href="<?php echo e(route('profile.show')); ?>">
                            <i class="fas fa-user"></i> Mon profil
                        </a>
                        <a class="sidebar-menu-link" href="<?php echo e(route('ads.index')); ?>">
                            <i class="fas fa-bullhorn"></i> Annonces
                        </a>
                        <a class="sidebar-menu-link" href="<?php echo e(route('pro.dashboard')); ?>">
                            <i class="fas fa-crown" style="color: #7c3aed;"></i> Espace Pro
                        </a>
                        <a class="sidebar-menu-link" href="<?php echo e(route('quote-tool.landing')); ?>">
                            <i class="fas fa-file-invoice" style="color: #16a34a;"></i> Devis & Factures
                        </a>
                        <a class="sidebar-menu-link" href="<?php echo e(route('messages.index')); ?>">
                            <i class="fas fa-comments"></i> Messages
                        </a>
                        <a class="sidebar-menu-link" href="<?php echo e(route('saved-ads.index')); ?>">
                            <i class="fas fa-bookmark"></i> Favoris
                        </a>
                        <a class="sidebar-menu-link" href="<?php echo e(route('settings.index')); ?>">
                            <i class="fas fa-cog"></i> Parametres
                        </a>
                    </div>

                </div>

                <div class="sidebar-left-widgets">
                    <!-- CTA Proposer mes services (pro) -->
                    <?php if(auth()->guard()->check()): ?>
                    <?php if(Auth::user()->hasActiveProSubscription()): ?>
                    
                    <div class="pro-widget pro-widget--active">
                        <div class="pro-widget-badge"><i class="fas fa-crown"></i> Pro actif</div>
                        <h3>Votre espace Pro</h3>
                        <p>Gérez votre profil et vos paramètres de services.</p>
                        <button type="button" onclick="handleProposerServices()" class="pro-widget-cta pro-widget-cta-active" style="margin-bottom: 8px;">
                            <i class="fas fa-hand-holding-heart"></i> Proposer mes services
                        </button>
                        <a href="<?php echo e(route('demand.create')); ?>" class="pro-widget-cta" style="background: #f3f4f6; border-color: #e5e7eb; color: #374151; text-decoration:none; text-align:center;">
                            <i class="fas fa-plus"></i> Publier une demande
                        </a>
                    </div>
                    <?php elseif(Auth::user()->is_service_provider): ?>
                    
                    <div class="pro-widget">
                        <div class="pro-widget-badge"><i class="fas fa-user-check"></i> Prestataire</div>
                        <h3>Complétez votre profil Pro</h3>
                        <p>Vérifiez vos infos et boostez votre visibilité.</p>
                        <button onclick="handleProposerServices()" class="pro-widget-cta">
                            <i class="fas fa-hand-holding-heart"></i> Proposer mes services
                        </button>
                    </div>
                    <?php else: ?>
                    
                    <div class="pro-widget">
                        <div class="pro-widget-badge"><i class="fas fa-star"></i> Opportunité</div>
                        <h3>Vous êtes professionnel ?</h3>
                        <p>Rejoignez +500 pros et recevez des demandes de clients près de chez vous.</p>
                        <div class="pro-widget-stats">
                            <div class="pro-widget-stat">
                                <strong>500+</strong>
                                <small>Pros inscrits</small>
                            </div>
                            <div class="pro-widget-stat">
                                <strong>24h</strong>
                                <small>Réponse moy.</small>
                            </div>
                            <div class="pro-widget-stat">
                                <strong>Gratuit</strong>
                                <small>Pour démarrer</small>
                            </div>
                        </div>
                        <button onclick="handleProposerServices()" class="pro-widget-cta">
                            <i class="fas fa-hand-holding-heart"></i> Proposer mes services
                        </button>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="pro-widget">
                        <div class="pro-widget-badge"><i class="fas fa-star"></i> Opportunité</div>
                        <h3>Vous êtes professionnel ?</h3>
                        <p>Inscrivez-vous et proposez vos services à des milliers de clients.</p>
                        <a href="<?php echo e(route('login')); ?>" class="pro-widget-cta">
                            <i class="fas fa-hand-holding-heart"></i> Proposer mes services
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Top Pros -->
                    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <h3 style="font-size: 0.85rem; font-weight: 600; color: #111827;">
                                <i class="fas fa-crown" style="color: #f59e0b; margin-right: 6px;"></i>Top Pros
                            </h3>
                        </div>
                        <div>
                            <?php if(isset($topPros) && $topPros->count() > 0): ?>
                                <?php $__currentLoopData = $topPros; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $pro): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a href="<?php echo e(route('profile.public', $pro->id)); ?>" style="display: flex; align-items: center; gap: 10px; padding: 8px 0; text-decoration: none; border-bottom: 1px solid #f3f4f6; <?php echo e($index == $topPros->count() - 1 ? 'border-bottom: none;' : ''); ?>">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden; flex-shrink: 0;">
                                        <?php if($pro->avatar): ?>
                                            <img src="<?php echo e(asset('storage/' . $pro->avatar)); ?>" alt="<?php echo e($pro->name); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 100%; background: #e5e7eb; display: flex; align-items: center; justify-content: center; color: #6b7280; font-weight: 600; font-size: 0.75rem;">
                                                <?php echo e(strtoupper(substr($pro->name, 0, 1))); ?>

                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-weight: 500; color: #111827; font-size: 0.8rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo e($pro->name); ?></div>
                                        <div style="font-size: 0.7rem; color: #9ca3af;"><?php echo e(Str::limit($pro->bio, 20) ?? 'Professionnel'); ?></div>
                                    </div>
                                    <div style="color: #f59e0b; font-weight: 600; font-size: 0.75rem;">
                                        <?php echo e(number_format($pro->verified_reviews_avg ?? 0, 1, ',', '')); ?>

                                    </div>
                                </a>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php else: ?>
                                <div style="text-align: center; padding: 16px; color: #9ca3af; font-size: 0.75rem;">
                                    Aucun pro classe pour le moment
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #f3f4f6; text-align: center;">
                            <a href="javascript:void(0)" onclick="setViewMode('providers'); window.scrollTo({top: 0, behavior: 'smooth'});" style="color: #2563eb; font-weight: 500; font-size: 0.75rem; text-decoration: none;">
                                Voir tous les pros →
                            </a>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Colonne principale: Feed des missions -->
            <div class="feed-main">

                
                <?php if(auth()->guard()->check()): ?>
                <div class="create-post-card">
                    <div class="create-post-inner">
                        <div class="create-post-avatar">
                            <?php if(Auth::user()->avatar): ?>
                                <img src="<?php echo e(asset('storage/' . Auth::user()->avatar)); ?>" alt="<?php echo e(Auth::user()->name); ?>">
                            <?php else: ?>
                                <div class="create-post-avatar-placeholder"><?php echo e(strtoupper(substr(Auth::user()->name, 0, 1))); ?></div>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo e(route('demand.create')); ?>" class="create-post-input" style="text-decoration:none;">De quoi avez-vous besoin ?</a>
                    </div>
                </div>
                <?php endif; ?>

                
                <?php echo $__env->make('partials.pro-suggestions', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                
                <?php if(isset($urgentAds) && $urgentAds->count() > 0): ?>
                <div class="urgent-carousel-section">
                    <div class="urgent-carousel-header">
                        <h3><i class="fas fa-fire"></i> Publications urgentes</h3>
                    </div>
                    <div class="urgent-carousel-track-wrapper">
                        <button class="urgent-carousel-btn prev-btn" onclick="scrollUrgentCarousel(-1)" id="urgentPrev" title="Précédent">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="urgent-carousel-btn next-btn" onclick="scrollUrgentCarousel(1)" id="urgentNext" title="Suivant">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <div class="urgent-carousel-track" id="urgentCarouselTrack">
                            <?php $__currentLoopData = $urgentAds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $uAd): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $uPhotos = $uAd->photos ?? [];
                                if (is_string($uPhotos)) {
                                    $dec = json_decode($uPhotos, true);
                                    $uPhotos = is_array($dec) ? $dec : (trim($uPhotos) !== '' ? [$uPhotos] : []);
                                }
                                $urgentDaysLeft = $uAd->urgent_until ? (int) now()->diffInDays($uAd->urgent_until, false) : null;
                            ?>
                            <a href="<?php echo e(route('ads.show', $uAd)); ?>" class="urgent-card">
                                <div class="urgent-card-badge">
                                    <i class="fas fa-fire"></i> URGENT
                                </div>
                                <?php if($urgentDaysLeft !== null): ?>
                                <div class="urgent-card-countdown">
                                    <i class="fas fa-clock"></i>
                                    <?php echo e($urgentDaysLeft > 0 ? $urgentDaysLeft . 'j restant' . ($urgentDaysLeft > 1 ? 's' : '') : 'Dernier jour'); ?>

                                </div>
                                <?php endif; ?>
                                <div class="urgent-card-img">
                                    <?php if(count($uPhotos) > 0): ?>
                                        <img src="<?php echo e(asset('storage/' . $uPhotos[0])); ?>" alt="<?php echo e($uAd->title); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-image"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="urgent-card-body">
                                    <div class="urgent-card-title"><?php echo e($uAd->title); ?></div>
                                    <div class="urgent-card-meta">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo e(Str::limit($uAd->location ?? $uAd->city ?? 'Non précisé', 20)); ?>

                                    </div>
                                    <?php if($uAd->price): ?>
                                    <div class="urgent-card-price"><?php echo e(number_format($uAd->price, 0, ',', ' ')); ?> €</div>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                    $showcasePros = $featuredProfessionals ?? ($premiumPros ?? collect());
                ?>

                
                

                
                

                <div class="missions-feed" id="missionsGrid">
            <?php $__empty_1 = true; $__currentLoopData = $ads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loopIndex => $ad): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

            
            <?php if($ad->is_urgent): ?>
                <?php continue; ?>
            <?php endif; ?>

            
            <?php if($loopIndex === 2 && isset($showcasePros) && $showcasePros->count() > 0): ?>
                <div class="featured-pros-section">
                    <div class="featured-pros-header">
                        <h2><i class="fas fa-user-shield" style="color: var(--primary, #4f46e5); margin-right: 6px;"></i> Profils professionnels à la une</h2>
                    </div>
                    <div class="providers-grid" id="featuredProfessionalsGrid">
                        <?php $__currentLoopData = $showcasePros->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sPro): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('profile.public', $sPro->id)); ?>" class="provider-card">
                            <div class="provider-image-wrapper">
                                <?php if($sPro->avatar): ?>
                                    <img src="<?php echo e(asset('storage/' . $sPro->avatar)); ?>" alt="<?php echo e($sPro->name); ?>">
                                <?php else: ?>
                                    <div class="provider-image-placeholder"><?php echo e(strtoupper(substr($sPro->name, 0, 1))); ?></div>
                                <?php endif; ?>
                                <div class="provider-badge-top">
                                    <i class="fas fa-briefcase"></i> <?php echo e(Str::limit($sPro->profession ?? $sPro->bio ?? 'Professionnel', 15)); ?>

                                </div>
                            </div>
                            <div class="provider-card-info">
                                <div class="provider-info-header">
                                    <h3 class="provider-name">
                                        <?php echo e(Str::limit($sPro->name, 16)); ?>

                                        <?php if($sPro->hasActiveProSubscription()): ?>
                                            <span class="badge-pro">PRO</span>
                                        <?php endif; ?>
                                    </h3>
                                </div>
                                <div class="provider-rating">
                                    <span class="star-icon">★</span>
                                    <span class="rating-value"><?php echo e($sPro->verified_reviews_avg ? number_format($sPro->verified_reviews_avg, 1, ',', '') : 'Nouveau'); ?></span>
                                    <?php if($sPro->verified_reviews_count ?? 0 > 0): ?>
                                    <span class="reviews-count">(<?php echo e($sPro->verified_reviews_count); ?> avis)</span>
                                    <?php endif; ?>
                                </div>
                                <p class="provider-category"><?php echo e(Str::limit($sPro->profession ?? $sPro->bio ?? 'Professionnel', 30)); ?></p>
                            </div>
                        </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endif; ?>

            

            <?php
                $photos = $ad->photos ?? [];
                if (is_string($photos)) {
                    $decoded = json_decode($photos, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $photos = $decoded;
                    } elseif (trim($photos) !== '') {
                        $photos = [$photos];
                    } else {
                        $photos = [];
                    }
                } elseif (!is_array($photos)) {
                    $photos = (array) $photos;
                }
                $photos = array_values(array_filter($photos));
                $photoCount = count($photos);
                $photoUrls = [];
                foreach ($photos as $p) {
                    $p = trim($p); $p = ltrim($p, '/');
                    if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) { $photoUrls[] = $p; }
                    elseif (str_starts_with($p, 'storage/')) { $photoUrls[] = asset($p); }
                    elseif (str_starts_with($p, 'public/')) { $photoUrls[] = asset('storage/' . str_replace('public/', '', $p)); }
                    else { $photoUrls[] = asset('storage/' . $p); }
                }
                $isNew = $ad->created_at->diffInHours() < 24;
                $hasProBadge = $ad->user && ($ad->user->user_type === 'professionnel' || $ad->user->hasActiveProSubscription());
                $commentsCount = $ad->comments()->count();

                // Reply restriction check
                $adRestriction = $ad->reply_restriction ?? 'everyone';
                $canReplyToAd = true;
                $replyRestrictionMsg = '';
                if (Auth::check() && Auth::id() !== $ad->user_id) {
                    if ($adRestriction === 'pro_only') {
                        $u = Auth::user();
                        $isPro = $u->user_type === 'professionnel' || $u->hasActiveProSubscription() || $u->hasCompletedProOnboarding();
                        if (!$isPro) {
                            $canReplyToAd = false;
                            $replyRestrictionMsg = 'Réservé aux PRO';
                        }
                    } elseif ($adRestriction === 'verified_only') {
                        if (!Auth::user()->is_verified) {
                            $canReplyToAd = false;
                            $replyRestrictionMsg = 'Profils vérifiés uniquement';
                        }
                    }
                }
            ?>

            <div class="fb-post<?php echo e($ad->is_urgent ? ' urgent-flow' : ''); ?><?php echo e(($ad->is_boosted && $ad->boost_end && $ad->boost_end > now()) ? ' boosted-post' : ''); ?>" data-ad-id="<?php echo e($ad->id); ?>"
                 data-ad-json="<?php echo e(htmlspecialchars(json_encode([
                    'id' => $ad->id, 'title' => $ad->title, 'description' => $ad->description,
                    'category' => $ad->category, 'price' => $ad->price, 'location' => $ad->location,
                    'photos' => $photos, 'is_urgent' => (bool)$ad->is_urgent,
                    'reply_restriction' => $ad->reply_restriction ?? 'everyone',
                    'visibility' => $ad->visibility ?? 'public',
                    'created_at_human' => $ad->created_at->diffForHumans(),
                    'user_id' => $ad->user_id, 'comments_count' => $commentsCount,
                    'shares_count' => $ad->shares_count ?? 0,
                    'user' => $ad->user ? ['id'=>$ad->user->id,'name'=>$ad->user->name,'avatar'=>$ad->user->avatar,'is_verified'=>(bool)$ad->user->is_verified] : null,
                 ]), ENT_QUOTES, 'UTF-8')); ?>">

                
                <div class="fb-post-header">
                    <a href="<?php echo e($ad->user ? route('profile.public', $ad->user->id) : '#'); ?>" class="fb-post-avatar">
                        <?php if($ad->user && $ad->user->avatar): ?>
                            <img src="<?php echo e(asset('storage/' . $ad->user->avatar)); ?>" alt="<?php echo e($ad->user->name); ?>">
                        <?php else: ?>
                            <div class="fb-post-avatar-placeholder"><?php echo e(strtoupper(substr($ad->user->name ?? 'U', 0, 1))); ?></div>
                        <?php endif; ?>
                    </a>
                    <div class="fb-post-header-info">
                        <a href="<?php echo e($ad->user ? route('profile.public', $ad->user->id) : '#'); ?>" class="fb-post-author">
                            <?php echo e($ad->user->name ?? 'Utilisateur'); ?>

                            <?php if($ad->user && $ad->user->is_verified): ?>
                                <i class="fas fa-check-circle" style="color: #1877f2; font-size: 0.75rem;"></i>
                            <?php endif; ?>
                            <?php if($hasProBadge): ?>
                                <span class="fb-post-badge">PRO</span>
                            <?php endif; ?>
                        </a>
                        <div class="fb-post-meta">
                            <span><?php echo e($ad->created_at->diffForHumans()); ?></span>
                            <span>·</span>
                            <span><?php echo e(Str::limit($ad->location ?? 'France', 25)); ?></span>
                            <?php if($ad->is_urgent): ?>
                                <span>·</span>
                                <span style="color: #dc2626; font-weight: 600;"><i class="fas fa-bolt"></i> Urgent</span>
                            <?php endif; ?>
                            <?php if($ad->is_boosted && $ad->boost_end && $ad->boost_end > now()): ?>
                                <span class="fb-post-sponsored-tag boost"><i class="fas fa-rocket"></i> Sponsorisé</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="fb-post-options">
                        <button class="fb-post-options-btn" onclick="event.stopPropagation(); togglePostMenu(this)" title="Plus d'options">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <div class="fb-post-options-menu">
                            <button class="fb-post-menu-item" onclick="event.stopPropagation(); window.location.href='/profile/<?php echo e($ad->user_id); ?>'">
                                <i class="fas fa-user"></i>
                                <span class="menu-item-text">Afficher le profil<small>Voir le profil de l'annonceur</small></span>
                            </button>
                            <button class="fb-post-menu-item" onclick="event.stopPropagation(); savePost(<?php echo e($ad->id); ?>, this)">
                                <i class="far fa-bookmark"></i>
                                <span class="menu-item-text">Enregistrer la publication<small>Ajouter à vos éléments enregistrés</small></span>
                            </button>
                            <button class="fb-post-menu-item" onclick="event.stopPropagation(); copyPostLink(<?php echo e($ad->id); ?>)">
                                <i class="fas fa-link"></i>
                                <span class="menu-item-text">Copier le lien</span>
                            </button>
                            <div class="fb-post-menu-divider"></div>
                            <button class="fb-post-menu-item" onclick="event.stopPropagation(); hidePost(<?php echo e($ad->id); ?>, this)">
                                <i class="fas fa-eye-slash"></i>
                                <span class="menu-item-text">Masquer la publication<small>Voir moins de publications de ce type</small></span>
                            </button>
                            <?php if(auth()->guard()->check()): ?>
                            <?php if(Auth::id() === $ad->user_id): ?>
                            <button class="fb-post-menu-item" onclick="event.stopPropagation(); window.location.href='<?php echo e(route('ads.edit', $ad->id)); ?>'">
                                <i class="fas fa-edit"></i>
                                <span class="menu-item-text">Modifier la publication</span>
                            </button>
                            <?php endif; ?>
                            <?php endif; ?>
                            <div class="fb-post-menu-divider"></div>
                            <button class="fb-post-menu-item danger" onclick="event.stopPropagation(); reportPost(<?php echo e($ad->id); ?>)">
                                <i class="fas fa-flag"></i>
                                <span class="menu-item-text">Signaler la publication<small>Cette publication pose problème</small></span>
                            </button>
                        </div>
                    </div>
                </div>

                
                <div class="fb-post-body">
                    <div class="fb-post-title"><?php echo e($ad->title); ?></div>
                    <div class="fb-post-text"><?php echo e(Str::limit($ad->description, 300)); ?></div>
                    <div class="fb-post-tags">
                        <?php if($ad->price): ?>
                            <span class="fb-post-tag price"><i class="fas fa-tag"></i> <?php echo e(number_format($ad->price, 0, ',', ' ')); ?> €</span>
                        <?php else: ?>
                            <span class="fb-post-tag"><i class="fas fa-tag"></i> À discuter</span>
                        <?php endif; ?>
                        <?php if($ad->category): ?>
                            <span class="fb-post-tag"><i class="fas fa-folder"></i> <?php echo e($ad->category); ?></span>
                        <?php endif; ?>
                        <?php if($adRestriction === 'pro_only'): ?>
                            <span class="fb-post-tag" style="background:#dbeafe;color:#2563eb;font-weight:600;"><i class="fas fa-briefcase"></i> PRO uniquement</span>
                        <?php elseif($adRestriction === 'verified_only'): ?>
                            <span class="fb-post-tag" style="background:#d1fae5;color:#059669;font-weight:600;"><i class="fas fa-check-circle"></i> Vérifiés uniquement</span>
                        <?php endif; ?>
                    </div>
                </div>

                
                <?php if($photoCount > 0): ?>
                <div class="fb-post-photos <?php echo e($photoCount === 1 ? 'single' : 'multi'); ?> <?php echo e($photoCount === 2 ? 'two' : ''); ?> <?php echo e($photoCount >= 3 ? 'three-plus' : ''); ?>"
                     onclick="openAdDetail(this.closest('.fb-post'))">
                    <?php $__currentLoopData = array_slice($photoUrls, 0, min($photoCount, 4)); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="fb-photo-item">
                            <img src="<?php echo e($url); ?>" alt="<?php echo e($ad->title); ?>" onerror="this.parentElement.style.display='none';">
                            <?php if($i === 3 && $photoCount > 4): ?>
                                <div class="fb-photo-more-overlay">+<?php echo e($photoCount - 4); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php if($ad->price): ?>
                    <div class="price-overlay-badge"><?php echo e(number_format($ad->price, 0, ',', ' ')); ?> €</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                
                <div class="fb-post-reactions-bar">
                    <div class="reactions-left">
                        <span>👍</span>
                        <span class="likes-count-<?php echo e($ad->id); ?>">0</span>
                    </div>
                    <div class="reactions-right">
                        <span onclick="toggleComments(<?php echo e($ad->id); ?>)"><span class="comments-count-<?php echo e($ad->id); ?>"><?php echo e($commentsCount); ?></span> commentaires</span>
                        <span><?php echo e($ad->shares_count ?? 0); ?> partages</span>
                    </div>
                </div>

                
                <div class="fb-post-actions">
                    <button class="fb-action-btn" onclick="toggleLike(<?php echo e($ad->id); ?>, this)">
                        <i class="far fa-thumbs-up"></i> J'aime
                    </button>
                    <?php if($canReplyToAd): ?>
                        <button class="fb-action-btn" onclick="toggleComments(<?php echo e($ad->id); ?>)">
                            <i class="far fa-comment"></i> Commenter
                        </button>
                    <?php else: ?>
                        <button class="fb-action-btn" style="opacity: 0.45; cursor: not-allowed;" title="<?php echo e($replyRestrictionMsg); ?>" onclick="alert('<?php echo e($replyRestrictionMsg); ?>')">
                            <i class="fas fa-lock" style="font-size: 0.75rem;"></i> Commenter
                        </button>
                    <?php endif; ?>
                    <button class="fb-action-btn" onclick="sharePost(<?php echo e($ad->id); ?>)">
                        <i class="fas fa-share"></i> Partager
                    </button>
                    <?php if($canReplyToAd): ?>
                        <button class="fb-action-btn contact-btn" onclick="window.location.href='<?php echo e(route('ads.show', $ad->id)); ?>'">
                            <i class="fas fa-envelope"></i> Contacter
                        </button>
                    <?php else: ?>
                        <button class="fb-action-btn contact-btn" style="opacity: 0.45; cursor: not-allowed;" title="<?php echo e($replyRestrictionMsg); ?>" onclick="alert('<?php echo e($replyRestrictionMsg); ?>. Rendez-vous sur la page de l\'annonce pour plus de détails.')">
                            <i class="fas fa-lock" style="font-size: 0.75rem;"></i> Contacter
                        </button>
                    <?php endif; ?>
                </div>

                
                <div class="fb-post-comments" id="comments-section-<?php echo e($ad->id); ?>" style="display: none;">
                    <div class="fb-comments-list" id="comments-list-<?php echo e($ad->id); ?>">
                        <div class="no-comments-msg" id="no-comments-<?php echo e($ad->id); ?>">
                            <i class="far fa-comment-dots"></i> Soyez le premier à commenter
                        </div>
                    </div>
                    <?php if(auth()->guard()->check()): ?>
                    <?php if($canReplyToAd): ?>
                    <form class="fb-comment-form" onsubmit="submitComment(event, <?php echo e($ad->id); ?>)">
                        <div class="fb-comment-avatar">
                            <?php if(Auth::user()->avatar): ?>
                                <img src="<?php echo e(asset('storage/' . Auth::user()->avatar)); ?>" alt="<?php echo e(Auth::user()->name); ?>">
                            <?php else: ?>
                                <div class="fb-post-avatar-placeholder" style="width:32px;height:32px;font-size:0.8rem;"><?php echo e(strtoupper(substr(Auth::user()->name, 0, 1))); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="fb-comment-input-wrap">
                            <input type="text" id="comment-input-<?php echo e($ad->id); ?>" placeholder="Écrire un commentaire..." autocomplete="off">
                            <button type="submit" class="fb-comment-send-btn" id="comment-submit-<?php echo e($ad->id); ?>">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                    
                    <div style="text-align:center; padding: 10px; font-size: 0.82rem; color: #92400e; background: #fef3c7; border-radius: 6px; margin: 8px 12px;">
                        <i class="fas fa-lock me-1"></i> <?php echo e($replyRestrictionMsg); ?>

                        <?php if($adRestriction === 'pro_only'): ?>
                            — <a href="<?php echo e(route('pro.dashboard')); ?>" style="color: #d97706; font-weight: 600; text-decoration: none;">Devenir Pro</a>
                        <?php elseif($adRestriction === 'verified_only'): ?>
                            — <a href="<?php echo e(route('verification.index')); ?>" style="color: #059669; font-weight: 600; text-decoration: none;">Vérifier mon profil</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div style="text-align:center; padding: 8px; font-size: 0.85rem; color: #65676b;">
                        <a href="<?php echo e(route('login')); ?>" style="color: #1877f2; text-decoration: none; font-weight: 600;">Connectez-vous</a> pour commenter
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div style="text-align: center; padding: 60px 20px; color: #65676b;">
                <i class="fas fa-briefcase" style="font-size: 2.5rem; color: #bec3c9; margin-bottom: 12px;"></i>
                <h3 style="font-weight: 600; color: #050505;">Aucune publication</h3>
                <p>Soyez le premier à publier une demande !</p>
                <a href="<?php echo e(route('demand.create')); ?>" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-2"></i>Publier une demande
                </a>
            </div>
            <?php endif; ?>
            </div>

            
            <div class="infinite-scroll-trigger" id="infiniteScrollTrigger">
                <?php if($ads->hasMorePages()): ?>
                <div class="infinite-scroll-spinner" id="infiniteSpinner"></div>
                <?php else: ?>
                <div class="infinite-scroll-end">Vous avez tout vu !</div>
                <?php endif; ?>
            </div>
            <input type="hidden" id="currentPage" value="<?php echo e($ads->currentPage()); ?>">
            <input type="hidden" id="lastPage" value="<?php echo e($ads->lastPage()); ?>">

            </div>

            
            
        </div>
    </div>
</div>

<!-- FOOTER - Mentions légales -->
<footer class="site-footer">
    <div class="container">
        <div class="row">
            <!-- À propos -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <span class="logo-icon">P</span>
                        <span class="logo-text">ProxiPro</span>
                    </div>
                    <p class="footer-description">
                        La plateforme de mise en relation entre particuliers et professionnels. 
                        Trouvez des prestataires qualifiés ou proposez vos services.
                    </p>
                    <div class="footer-social">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            
            <!-- Liens utiles -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h5 class="footer-title">Liens utiles</h5>
                <ul class="footer-links">
                    <li><a href="<?php echo e(route('feed')); ?>">Accueil</a></li>
                    <li><a href="<?php echo e(route('demand.create')); ?>">Publier une demande</a></li>
                    <li><a href="<?php echo e(route('pricing.index')); ?>">Tarifs</a></li>
                    <li><a href="<?php echo e(route('contact.index')); ?>">Contact</a></li>
                </ul>
            </div>
            
            <!-- Informations légales -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="footer-title">Informations légales</h5>
                <ul class="footer-links">
                    <li><a href="<?php echo e(route('legal.terms')); ?>">Conditions d'utilisation</a></li>
                    <li><a href="<?php echo e(route('legal.privacy')); ?>">Politique de confidentialité</a></li>
                    <li><a href="<?php echo e(route('legal.cookies')); ?>">Politique des cookies</a></li>
                    <li><a href="<?php echo e(route('legal.mentions')); ?>">Mentions légales</a></li>
                </ul>
            </div>
            
            <!-- Contact -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="footer-title">Contact</h5>
                <ul class="footer-contact">
                    <li><i class="fas fa-envelope"></i> contact@ProxiPro.com</li>
                    <li><i class="fas fa-phone"></i> +262 693 00 00 00</li>
                    <li><i class="fas fa-map-marker-alt"></i> Mayotte, France</li>
                </ul>
            </div>
        </div>
        
        <hr class="footer-divider">
        
        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="copyright">© <?php echo e(date('Y')); ?> ProxiPro. Tous droits réservés.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="footer-credits">Fait avec <i class="fas fa-heart text-danger"></i> à Mayotte</p>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
/* =========================================
   FOOTER - Style moderne
   ========================================= */
.site-footer {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #94a3b8;
    padding: 60px 0 0;
    margin-top: 60px;
}

.footer-brand {
    margin-bottom: 20px;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.footer-logo .logo-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.2rem;
}

.footer-logo .logo-text {
    font-size: 1.4rem;
    font-weight: 700;
    color: white;
}

.footer-description {
    font-size: 0.9rem;
    line-height: 1.7;
    margin-bottom: 20px;
}

.footer-social {
    display: flex;
    gap: 10px;
}

.social-link {
    width: 38px;
    height: 38px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    text-decoration: none;
    transition: all 0.3s ease;
}

.social-link:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-3px);
}

.footer-title {
    color: white;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 20px;
    position: relative;
    padding-bottom: 10px;
}

.footer-title::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 30px;
    height: 2px;
    background: var(--primary);
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 12px;
}

.footer-links a {
    color: #94a3b8;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.footer-links a:hover {
    color: white;
    padding-left: 5px;
}

.footer-contact {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-contact li {
    margin-bottom: 12px;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.footer-contact i {
    color: var(--primary);
    width: 16px;
}

.footer-divider {
    border-color: rgba(255, 255, 255, 0.1);
    margin: 30px 0 20px;
}

.footer-bottom {
    padding-bottom: 30px;
}

.copyright {
    margin: 0;
    font-size: 0.85rem;
}

.footer-credits {
    margin: 0;
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .site-footer {
        padding: 40px 0 0;
        text-align: center;
    }
    
    .footer-logo {
        justify-content: center;
    }
    
    .footer-social {
        justify-content: center;
    }
    
    .footer-title::after {
        left: 50%;
        transform: translateX(-50%);
    }
    
    .footer-bottom .row > div {
        text-align: center !important;
    }
}

@media (max-width: 576px) {
    .site-footer {
        padding: 30px 0 0;
    }
    .site-footer .container {
        padding: 0 12px;
    }
    .footer-title {
        font-size: 0.95rem;
    }
    .footer-link {
        font-size: 0.82rem;
    }
    .footer-contact {
        font-size: 0.82rem;
    }
    .footer-bottom {
        padding-bottom: 20px;
    }
    .copyright, .footer-credits {
        font-size: 0.78rem;
    }
}
</style>

<!-- LIGHTBOX POUR PHOTOS -->
<div id="photoLightbox" class="photo-lightbox" onclick="closePhotoLightbox(event)">
    <button class="photo-lightbox-close" onclick="closePhotoLightbox(event)">
        <i class="fas fa-times"></i>
    </button>
    <img id="lightboxImage" src="" alt="Photo">
    <div id="lightboxTitle" class="photo-lightbox-title"></div>
</div>

<!-- POPUP DÉTAIL PUBLICATION -->
<div class="ad-detail-overlay" id="adDetailOverlay" onclick="if(event.target===this) closeAdDetail()">
    <div class="ad-detail-popup" id="adDetailPopup">
        <button class="ad-detail-close" onclick="closeAdDetail()">
            <i class="fas fa-times"></i>
        </button>

        <!-- Photos -->
        <div class="ad-detail-photos" id="adDetailPhotos"></div>

        <!-- Contenu -->
        <div class="ad-detail-content">
            <div class="ad-detail-user" id="adDetailUser"></div>
            <h2 class="ad-detail-title" id="adDetailTitle"></h2>
            <div class="ad-detail-badges" id="adDetailBadges"></div>
            <div class="ad-detail-price" id="adDetailPrice"></div>
            <div class="ad-detail-description" id="adDetailDescription"></div>
        </div>

        <!-- Bouton Postuler / Envoyer candidature -->
        <?php if(auth()->guard()->check()): ?>
        <div class="ad-detail-candidature" id="adDetailCandidature">
            <div class="candidature-collapsed" id="candidatureCollapsed">
                <button type="button" class="btn-candidature" onclick="toggleCandidatureForm()">
                    <i class="fas fa-hand-paper"></i> Je suis intéressé(e) — Envoyer ma candidature
                </button>
            </div>
            <div class="candidature-form" id="candidatureForm" style="display:none;">
                <h5 style="font-size:0.9rem;font-weight:600;color:#1e293b;margin-bottom:8px;">
                    <i class="fas fa-paper-plane" style="color:#3b82f6;margin-right:6px;"></i>Envoyer votre candidature
                </h5>
                <textarea id="candidatureMessage" class="candidature-textarea" placeholder="Présentez-vous en quelques mots... (optionnel)" maxlength="1000"></textarea>
                <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button type="button" class="btn-candidature-cancel" onclick="toggleCandidatureForm()">Annuler</button>
                    <button type="button" class="btn-candidature-send" id="btnSendCandidature" onclick="submitCandidature()">
                        <i class="fas fa-paper-plane"></i> Envoyer
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="ad-detail-actions" id="adDetailActions"></div>

        <!-- Commentaires -->
        <div class="ad-detail-comments">
            <h5><i class="far fa-comments"></i> <span id="adDetailCommentsTitle">Commentaires</span></h5>
            <div class="comments-list-inline" id="adDetailCommentsList">
                <div class="no-comments-inline">
                    <i class="far fa-comment-dots"></i>
                    Aucun commentaire. Soyez le premier à commenter !
                </div>
            </div>
            <?php if(auth()->guard()->check()): ?>
            <div class="comment-form-inline">
                <div class="comment-input-wrapper">
                    <input type="text" class="comment-input-inline" id="adDetailCommentInput" 
                           placeholder="Écrivez un commentaire..." 
                           onkeypress="if(event.key === 'Enter') submitPopupComment()">
                    <button class="comment-submit-btn" onclick="submitPopupComment()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="login-to-comment">
                <a href="<?php echo e(route('login')); ?>">Connectez-vous</a> pour commenter
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<?php echo $__env->make('partials.pro-onboarding-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


<div class="category-popup-overlay" id="categoryPopupOverlay" onclick="if(event.target===this) closeCategoryPopup()">
    <div class="category-popup">
        <button class="category-popup-close" onclick="closeCategoryPopup()">&times;</button>
        <div class="category-popup-header">
            <div class="category-popup-breadcrumb" id="categoryBreadcrumb">
                <span class="bc-current">Catégories</span>
            </div>
            <h3 class="category-popup-title" id="categoryPopupTitle">De quoi avez-vous besoin ?</h3>
            <input type="text" class="category-popup-search" id="categoryPopupSearch" placeholder="Rechercher un service..." oninput="filterCategoryPopup(this.value)" autocomplete="off">
        </div>
        <div class="category-popup-body" id="categoryPopupBody">
            
        </div>
        <div class="category-popup-step3" id="categoryPopupStep3" style="display:none;">
            
        </div>
    </div>
</div>


<div class="sub-popup-overlay" id="subPopupOverlay" onclick="if(event.target===this) closeSubPopup()">
    <div class="sub-popup" style="max-width: 540px;">
        <button class="sub-popup-close" onclick="closeSubPopup()">&times;</button>

        
        <div class="sub-popup-step-progress" id="subPopupProgress">
            <div class="sub-popup-step-dot active" data-step="1"></div>
            <div class="sub-popup-step-dot" data-step="2"></div>
            <div class="sub-popup-step-dot" data-step="3"></div>
            <div class="sub-popup-step-dot" data-step="4"></div>
        </div>

        
        <div class="sub-popup-hero" style="padding-bottom: 10px;">
            <div class="sub-popup-hero-icon">
                <i class="fas fa-briefcase" id="subPopupHeroIcon"></i>
            </div>
            <h3 id="subPopupHeroTitle">Votre profil professionnel</h3>
            <p id="subPopupHeroDesc">Complétez vos informations pour proposer vos services.</p>
        </div>

        
        <div class="sub-popup-body" id="subPopupBody">

            
            <div id="subPopupStep1">
                <?php if(auth()->guard()->check()): ?>
                <?php $user = Auth::user(); ?>

                <div id="wizardProfileSaveMsg" style="display:none; background:#f0fdf4; border-radius:10px; padding:8px 14px; margin-bottom:12px; font-size:0.75rem; color:#166534; align-items:center; gap:6px;">
                    <i class="fas fa-check-circle"></i> <span></span>
                </div>

                <div style="max-height: 380px; overflow-y: auto; padding-right: 4px;">
                    
                    <div class="wizard-field-row">
                        <div class="wizard-field-icon" style="background:#7c3aed15; color:#7c3aed;"><i class="fas fa-user"></i></div>
                        <div class="wizard-field-body">
                            <label class="wizard-field-label">Nom / Entreprise <span style="color:#ef4444;">*</span></label>
                            <input type="text" id="wizProfileName" class="wizard-field-input" value="<?php echo e($user->name ?? ''); ?>" placeholder="Votre nom ou raison sociale">
                        </div>
                    </div>

                    
                    <div class="wizard-field-row">
                        <div class="wizard-field-icon" style="background:#2563eb15; color:#2563eb;"><i class="fas fa-envelope"></i></div>
                        <div class="wizard-field-body">
                            <label class="wizard-field-label">Email</label>
                            <input type="email" class="wizard-field-input" value="<?php echo e($user->email ?? ''); ?>" disabled style="opacity:0.6; cursor:not-allowed;">
                        </div>
                    </div>

                    
                    <div class="wizard-field-row">
                        <div class="wizard-field-icon" style="background:#10b98115; color:#10b981;"><i class="fas fa-phone"></i></div>
                        <div class="wizard-field-body">
                            <label class="wizard-field-label">Téléphone</label>
                            <input type="tel" id="wizProfilePhone" class="wizard-field-input" value="<?php echo e($user->phone ?? ''); ?>" placeholder="Ex: 0639 12 34 56">
                        </div>
                    </div>

                    
                    <div class="wizard-field-row">
                        <div class="wizard-field-icon" style="background:#ef444415; color:#ef4444;"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="wizard-field-body">
                            <label class="wizard-field-label">Ville</label>
                            <input type="text" id="wizProfileCity" class="wizard-field-input" value="<?php echo e($user->city ?? $user->detected_city ?? ''); ?>" placeholder="Votre ville">
                        </div>
                    </div>

                    
                    <div class="wizard-field-row">
                        <div class="wizard-field-icon" style="background:#f59e0b15; color:#f59e0b;"><i class="fas fa-home"></i></div>
                        <div class="wizard-field-body">
                            <label class="wizard-field-label">Adresse</label>
                            <input type="text" id="wizProfileAddress" class="wizard-field-input" value="<?php echo e($user->address ?? ''); ?>" placeholder="Votre adresse complète">
                        </div>
                    </div>

                    
                    <div class="wizard-field-row" style="align-items:flex-start;">
                        <div class="wizard-field-icon" style="background:#8b5cf615; color:#8b5cf6; margin-top:4px;"><i class="fas fa-pen"></i></div>
                        <div class="wizard-field-body">
                            <label class="wizard-field-label">Bio / Description</label>
                            <textarea id="wizProfileBio" class="wizard-field-input" rows="2" placeholder="Décrivez votre activité en quelques mots..." style="resize:vertical; min-height:48px;"><?php echo e($user->bio ?? ''); ?></textarea>
                        </div>
                    </div>

                    
                    <div class="wizard-field-row">
                        <div class="wizard-field-icon" style="background:#06b6d415; color:#06b6d4;"><i class="fas fa-camera"></i></div>
                        <div class="wizard-field-body">
                            <label class="wizard-field-label">Photo de profil</label>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div id="wizProfileAvatarPreview" style="width:36px; height:36px; border-radius:50%; background:#e5e7eb; overflow:hidden; flex-shrink:0; display:flex; align-items:center; justify-content:center;">
                                    <?php if($user->avatar): ?>
                                        <img src="<?php echo e(asset('storage/' . $user->avatar)); ?>" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <i class="fas fa-user" style="color:#9ca3af; font-size:0.85rem;"></i>
                                    <?php endif; ?>
                                </div>
                                <label style="cursor:pointer; font-size:0.78rem; color:#7c3aed; font-weight:600; display:flex; align-items:center; gap:4px;">
                                    <i class="fas fa-upload" style="font-size:0.7rem;"></i> Changer la photo
                                    <input type="file" id="wizProfileAvatar" accept="image/jpeg,image/png,image/jpg" style="display:none;" onchange="previewWizardAvatar(this)">
                                </label>
                            </div>
                        </div>
                    </div>

                    
                    <div class="wizard-field-row">
                        <div class="wizard-field-icon" style="background:#3b82f615; color:#3b82f6;"><i class="fas fa-crosshairs"></i></div>
                        <div class="wizard-field-body">
                            <label class="wizard-field-label">Localisation GPS</label>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span id="wizGpsStatus" style="font-size:0.78rem; color:<?php echo e((!empty($user->latitude)) ? '#10b981' : '#ef4444'); ?>; font-weight:600;">
                                    <?php echo e((!empty($user->latitude)) ? '✓ Activée' : '✗ Non activée'); ?>

                                </span>
                                <?php if(empty($user->latitude)): ?>
                                <button type="button" onclick="detectWizardGps()" style="background:#3b82f6; color:#fff; border:none; border-radius:6px; padding:4px 10px; font-size:0.72rem; font-weight:600; cursor:pointer;">
                                    <i class="fas fa-location-arrow"></i> Activer
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php endif; ?>

                <div class="sub-popup-actions" style="margin-top:14px;">
                    <button class="sub-popup-btn-primary" onclick="saveWizardProfileAndNext()">
                        <i class="fas fa-save" style="margin-right:6px;"></i> Enregistrer et continuer
                    </button>
                    <button class="sub-popup-btn-secondary" onclick="closeSubPopup()">Plus tard</button>
                </div>
            </div>

            
            <div id="subPopupStep2" style="display:none;">
                <div class="sub-popup-step-title">
                    <i class="fas fa-th-large"></i> Vos domaines d'activité
                </div>
                <p style="font-size:0.78rem; color:#6b7280; margin-bottom:10px;">Sélectionnez vos catégories, puis choisissez vos sous-catégories.</p>

                
                <div style="position:relative; margin-bottom:12px;">
                    <input type="text" id="wizCatSearch" class="wizard-field-input" placeholder="Rechercher une catégorie..." oninput="filterWizardCategories(this.value)" style="padding-left:32px;">
                    <i class="fas fa-search" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#9ca3af; font-size:0.78rem;"></i>
                </div>

                <div id="subPopupCategoriesTree" style="max-height: 310px; overflow-y: auto; padding-right: 4px;">
                    
                </div>

                <div id="subPopupSelectedCount" style="text-align:center; padding:6px 0; font-size:0.75rem; color:#7c3aed; font-weight:600; display:none;">
                    0 sous-catégorie(s) sélectionnée(s)
                </div>

                <div class="sub-popup-actions" style="margin-top:10px;">
                    <button class="sub-popup-btn-primary" onclick="saveWizardCategoriesAndNext()">
                        Continuer <i class="fas fa-arrow-right" style="margin-left:6px;"></i>
                    </button>
                    <button class="sub-popup-btn-secondary" onclick="goToWizardStep(1)">
                        <i class="fas fa-arrow-left" style="font-size:0.7rem;"></i> Retour
                    </button>
                </div>
            </div>

            
            <div id="subPopupStep3" style="display:none;">
                <div class="sub-popup-step-title">
                    <i class="fas fa-bell"></i> Notifications & alertes
                </div>
                <p style="font-size:0.78rem; color:#6b7280; margin-bottom:14px;">Configurez comment recevoir les demandes de clients.</p>

                <div class="sub-popup-notif-row">
                    <div class="sub-popup-notif-info">
                        <i class="fas fa-envelope" style="color:#2563eb;"></i>
                        <div>
                            <strong>Notifications par e-mail</strong>
                            <small>Recevez un e-mail pour chaque nouvelle demande</small>
                        </div>
                    </div>
                    <label class="sub-popup-toggle">
                        <input type="checkbox" id="notifEmail" checked>
                        <span class="sub-popup-toggle-slider"></span>
                    </label>
                </div>

                <div class="sub-popup-notif-row">
                    <div class="sub-popup-notif-info">
                        <i class="fas fa-bolt" style="color:#f59e0b;"></i>
                        <div>
                            <strong>Alertes en temps réel</strong>
                            <small>Notification instantanée sur le site</small>
                        </div>
                    </div>
                    <label class="sub-popup-toggle">
                        <input type="checkbox" id="notifRealtime" checked>
                        <span class="sub-popup-toggle-slider"></span>
                    </label>
                </div>

                <div class="sub-popup-notif-row">
                    <div class="sub-popup-notif-info">
                        <i class="fas fa-mobile-alt" style="color:#10b981;"></i>
                        <div>
                            <strong>Notifications SMS</strong>
                            <small>Recevez un SMS pour les demandes urgentes</small>
                        </div>
                    </div>
                    <label class="sub-popup-toggle">
                        <input type="checkbox" id="notifSms">
                        <span class="sub-popup-toggle-slider"></span>
                    </label>
                </div>

                <div style="background:#f0fdf4; border-radius:10px; padding:10px 14px; margin-top:14px; font-size:0.75rem; color:#166534; display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-info-circle"></i>
                    <span>Vous pouvez modifier ces paramètres à tout moment depuis votre tableau de bord.</span>
                </div>

                <div class="sub-popup-actions" style="margin-top:16px;">
                    <button class="sub-popup-btn-primary" onclick="saveWizardNotifsAndNext()">
                        Continuer <i class="fas fa-arrow-right" style="margin-left:6px;"></i>
                    </button>
                    <button class="sub-popup-btn-secondary" onclick="goToWizardStep(2)">
                        <i class="fas fa-arrow-left" style="font-size:0.7rem;"></i> Retour
                    </button>
                </div>
            </div>

            
            <div id="subPopupStep4" style="display:none;">
                <div class="sub-popup-step-title">
                    <i class="fas fa-rocket"></i> Boostez votre profil
                </div>

                <?php if(auth()->guard()->check()): ?>
                <?php if(Auth::user()->hasActiveProSubscription()): ?>
                
                <div style="background: linear-gradient(135deg, #dcfce7, #bbf7d0); border: 1px solid #86efac; border-radius: 14px; padding: 20px; margin-bottom: 16px; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 8px;">✅</div>
                    <h4 style="color: #166534; font-weight: 700; margin-bottom: 6px;">Vous êtes déjà abonné Pro !</h4>
                    <p style="color: #15803d; font-size: 0.85rem; margin-bottom: 0;">
                        Votre abonnement est actif. Vos services ont été mis à jour avec succès.
                    </p>
                </div>

                <div class="sub-popup-actions">
                    <button class="sub-popup-btn-primary" id="subPopupSubmitBtn" onclick="submitProRegistration()" style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                        <i class="fas fa-check"></i> <span id="subPopupSubmitLabel">Enregistrer les modifications</span>
                    </button>
                    <button class="sub-popup-btn-secondary" onclick="goToWizardStep(3)">
                        <i class="fas fa-arrow-left" style="font-size:0.7rem;"></i> Retour
                    </button>
                </div>
                <?php else: ?>
                
                <p style="font-size:0.78rem; color:#6b7280; margin-bottom:14px;">Passez Pro pour apparaître en priorité et recevoir plus de demandes clients.</p>

                
                <div style="background:#f8fafc; border-radius:10px; padding:12px 14px; margin-bottom:14px; border:1px solid #e5e7eb;">
                    <div style="font-size:0.72rem; font-weight:600; color:#6b7280; text-transform:uppercase; margin-bottom:6px;">Récapitulatif</div>
                    <div style="display:flex; gap:16px; flex-wrap:wrap;">
                        <div style="font-size:0.78rem; color:#111827;"><i class="fas fa-user" style="color:#7c3aed; margin-right:4px;"></i> <span id="wizRecapName">—</span></div>
                        <div style="font-size:0.78rem; color:#111827;"><i class="fas fa-map-marker-alt" style="color:#ef4444; margin-right:4px;"></i> <span id="wizRecapCity">—</span></div>
                        <div style="font-size:0.78rem; color:#111827;"><i class="fas fa-th-large" style="color:#3b82f6; margin-right:4px;"></i> <span id="wizRecapCats">—</span></div>
                    </div>
                </div>

                <div class="sub-popup-plans">
                    <div class="sub-popup-plan" id="planFree" onclick="selectProPlan('free')">
                        <div class="sub-popup-plan-price">0€</div>
                        <div class="sub-popup-plan-name">Gratuit</div>
                        <div class="sub-popup-plan-desc">Visibilité de base</div>
                    </div>
                    <div class="sub-popup-plan selected" id="planMonthly" onclick="selectProPlan('monthly')">
                        <span class="sub-popup-plan-popular">Populaire</span>
                        <div class="sub-popup-plan-price">9,99€ <small>/mois</small></div>
                        <div class="sub-popup-plan-name">Pro Mensuel</div>
                        <div class="sub-popup-plan-desc">Sans engagement</div>
                    </div>
                    <div class="sub-popup-plan" id="planAnnual" onclick="selectProPlan('annual')">
                        <div class="sub-popup-plan-price">85€ <small>/an</small></div>
                        <div class="sub-popup-plan-name">Pro Annuel</div>
                        <div class="sub-popup-plan-desc">Soit 7,08€/mois</div>
                        <div class="sub-popup-plan-savings"><i class="fas fa-tag"></i> -30%</div>
                    </div>
                </div>

                
                <div style="margin:14px 0 12px;">
                    <div style="font-size:0.78rem; font-weight:600; color:#111827; margin-bottom:8px;">Avantages de l'abonnement Pro :</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                        <div style="display:flex; align-items:center; gap:6px; font-size:0.72rem; color:#374151;"><i class="fas fa-check" style="color:#10b981; font-size:0.65rem;"></i> Profil mis en avant</div>
                        <div style="display:flex; align-items:center; gap:6px; font-size:0.72rem; color:#374151;"><i class="fas fa-check" style="color:#10b981; font-size:0.65rem;"></i> Badge Pro vérifié</div>
                        <div style="display:flex; align-items:center; gap:6px; font-size:0.72rem; color:#374151;"><i class="fas fa-check" style="color:#10b981; font-size:0.65rem;"></i> Alertes e-mail clients</div>
                        <div style="display:flex; align-items:center; gap:6px; font-size:0.72rem; color:#374151;"><i class="fas fa-check" style="color:#10b981; font-size:0.65rem;"></i> Jusqu'à 4 photos/annonce</div>
                        <div style="display:flex; align-items:center; gap:6px; font-size:0.72rem; color:#374151;"><i class="fas fa-check" style="color:#10b981; font-size:0.65rem;"></i> 3x plus de contacts</div>
                        <div style="display:flex; align-items:center; gap:6px; font-size:0.72rem; color:#374151;"><i class="fas fa-check" style="color:#10b981; font-size:0.65rem;"></i> Support prioritaire</div>
                    </div>
                </div>

                <div class="sub-popup-actions">
                    <button class="sub-popup-btn-primary" id="subPopupSubmitBtn" onclick="submitProRegistration()">
                        <i class="fas fa-rocket"></i> <span id="subPopupSubmitLabel">S'abonner — 9,99€/mois</span>
                    </button>
                    <button class="sub-popup-btn-secondary" onclick="goToWizardStep(3)">
                        <i class="fas fa-arrow-left" style="font-size:0.7rem;"></i> Retour
                    </button>
                </div>

                <div class="sub-popup-testimonial">
                    <div class="sub-popup-testimonial-avatar">MA</div>
                    <div class="sub-popup-testimonial-text">
                        « Depuis que je suis Pro, je reçois 3 à 5 demandes par semaine. Mon profil est vu par des centaines de clients. »
                        <strong>— Mohamed A., Plombier à Mamoudzou</strong>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>


<div class="report-modal-overlay" id="reportModalOverlay" onclick="if(event.target===this) closeReportModal()">
    <div class="report-modal">
        <div class="report-modal-header">
            <h3><i class="fas fa-flag" style="color:#e41e3f;margin-right:8px;"></i> Signaler la publication</h3>
            <button class="report-modal-close" onclick="closeReportModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="report-modal-body">
            <p>Veuillez sélectionner la raison du signalement. Votre identité ne sera pas révélée à l'auteur de la publication.</p>
            <input type="hidden" id="reportAdId" value="">
            <div id="reportReasonsList">
                <div class="report-reason-item" onclick="selectReportReason(this, 'spam')">
                    <i class="fas fa-ban"></i>
                    <div class="report-reason-text">Spam ou publicité non sollicitée<small>Contenu promotionnel abusif ou répétitif</small></div>
                    <div class="report-radio"></div>
                </div>
                <div class="report-reason-item" onclick="selectReportReason(this, 'fausse_annonce')">
                    <i class="fas fa-mask"></i>
                    <div class="report-reason-text">Fausse annonce ou arnaque<small>Information trompeuse, tarif frauduleux</small></div>
                    <div class="report-radio"></div>
                </div>
                <div class="report-reason-item" onclick="selectReportReason(this, 'contenu_inapproprie')">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="report-reason-text">Contenu inapproprié ou offensant<small>Langage vulgaire, images choquantes</small></div>
                    <div class="report-radio"></div>
                </div>
                <div class="report-reason-item" onclick="selectReportReason(this, 'harcelement')">
                    <i class="fas fa-user-slash"></i>
                    <div class="report-reason-text">Harcèlement ou intimidation<small>Comportement menaçant ou abusif</small></div>
                    <div class="report-radio"></div>
                </div>
                <div class="report-reason-item" onclick="selectReportReason(this, 'usurpation')">
                    <i class="fas fa-user-secret"></i>
                    <div class="report-reason-text">Usurpation d'identité<small>Se fait passer pour quelqu'un d'autre</small></div>
                    <div class="report-radio"></div>
                </div>
                <div class="report-reason-item" onclick="selectReportReason(this, 'contenu_illegal')">
                    <i class="fas fa-gavel"></i>
                    <div class="report-reason-text">Contenu illégal<small>Activité illicite, vente interdite</small></div>
                    <div class="report-radio"></div>
                </div>
                <div class="report-reason-item" onclick="selectReportReason(this, 'doublon')">
                    <i class="fas fa-clone"></i>
                    <div class="report-reason-text">Publication en double<small>La même annonce a été postée plusieurs fois</small></div>
                    <div class="report-radio"></div>
                </div>
                <div class="report-reason-item" onclick="selectReportReason(this, 'mauvaise_categorie')">
                    <i class="fas fa-folder-minus"></i>
                    <div class="report-reason-text">Mauvaise catégorie<small>L'annonce ne correspond pas à sa catégorie</small></div>
                    <div class="report-radio"></div>
                </div>
                <div class="report-reason-item" onclick="selectReportReason(this, 'autre')">
                    <i class="fas fa-ellipsis-h"></i>
                    <div class="report-reason-text">Autre raison<small>Précisez dans le champ ci-dessous</small></div>
                    <div class="report-radio"></div>
                </div>
            </div>
            <textarea class="report-message-area" id="reportMessage" placeholder="Détails supplémentaires (facultatif)..." maxlength="1000"></textarea>
        </div>
        <div class="report-modal-footer">
            <button class="report-btn-cancel" onclick="closeReportModal()">Annuler</button>
            <button class="report-btn-submit" id="reportSubmitBtn" onclick="submitReport()">
                <i class="fas fa-paper-plane" style="margin-right:6px;"></i> Envoyer le signalement
            </button>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    console.log('=== Script ProxiPro Feed chargé ===');
    
    // =========================================
    // SYSTÈME DE PUBLICATION SOCIALE AMÉLIORÉ
    // =========================================

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const likedAds = new Set();

    // Données des professionnels à la une (pour injection JS)
    const featuredProsData = <?php echo json_encode($featuredProsJson ?? [], 15, 512) ?>;

    // =========================================
    // GESTION DU LIGHTBOX PHOTO
    // =========================================
    function openPhotoLightbox(photoUrl, title) {
        const lightbox = document.getElementById('photoLightbox');
        const lightboxImage = document.getElementById('lightboxImage');
        const lightboxTitle = document.getElementById('lightboxTitle');
        
        if (lightbox && lightboxImage) {
            lightboxImage.src = photoUrl;
            lightboxTitle.textContent = title || '';
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closePhotoLightbox(event) {
        // Ne pas fermer si on clique sur l'image elle-même
        if (event && event.target && event.target.tagName === 'IMG') {
            return;
        }
        
        const lightbox = document.getElementById('photoLightbox');
        if (lightbox) {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    // Fermer avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePhotoLightbox({});
            closeCategoryPopup();
            closeSubPopup();
        }
    });

    // =========================================
    // URGENT CAROUSEL SCROLL
    // =========================================
    function scrollUrgentCarousel(direction) {
        const track = document.getElementById('urgentCarouselTrack');
        if (!track) return;
        const scrollAmount = 240; // card width + gap
        track.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
    }

    // Scroll "Professionnels à la une"
    function scrollFeaturedPros(direction) {
        const track = document.getElementById('featuredProsTrack');
        if (!track) return;
        const scrollAmount = 280;
        track.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
        setTimeout(() => updateFeaturedProsArrows(), 350);
    }
    function updateFeaturedProsArrows() {
        const track = document.getElementById('featuredProsTrack');
        const prevBtn = document.getElementById('featuredProsPrev');
        const nextBtn = document.getElementById('featuredProsNext');
        if (!track || !prevBtn || !nextBtn) return;
        prevBtn.disabled = track.scrollLeft <= 5;
        nextBtn.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 5;
    }
    document.addEventListener('DOMContentLoaded', function() {
        const fpTrack = document.getElementById('featuredProsTrack');
        if (fpTrack) {
            fpTrack.addEventListener('scroll', updateFeaturedProsArrows);
            updateFeaturedProsArrows();
        }
    });

    // Scroll "Annonces à la une"
    function scrollFeaturedAds(direction) {
        const track = document.getElementById('featuredAdsTrack');
        if (!track) return;
        track.scrollBy({ left: direction * 300, behavior: 'smooth' });
        setTimeout(updateFeaturedAdsArrows, 350);
    }
    function updateFeaturedAdsArrows() {
        const track = document.getElementById('featuredAdsTrack');
        if (!track) return;
        const leftBtn = track.parentElement.querySelector('.featured-ads-arrow-left');
        const rightBtn = track.parentElement.querySelector('.featured-ads-arrow-right');
        if (leftBtn) leftBtn.style.opacity = track.scrollLeft <= 5 ? '0' : '1';
        if (rightBtn) rightBtn.style.opacity = (track.scrollLeft + track.clientWidth >= track.scrollWidth - 5) ? '0' : '1';
    }
    document.addEventListener('DOMContentLoaded', function() {
        const faTrack = document.getElementById('featuredAdsTrack');
        if (faTrack) {
            faTrack.addEventListener('scroll', updateFeaturedAdsArrows);
            updateFeaturedAdsArrows();
        }
    });

    // =========================================
    // GESTION DES LIKES
    // =========================================
    function toggleLike(adId, btnEl) {
        if (event) event.stopPropagation();
        const isLiked = likedAds.has(adId);
        const likesCountEl = document.querySelector(`.likes-count-${adId}`);
        
        if (isLiked) {
            likedAds.delete(adId);
            if (btnEl) { btnEl.classList.remove('liked'); btnEl.innerHTML = '<i class="far fa-thumbs-up"></i> J\'aime'; }
            if (likesCountEl) { const c = parseInt(likesCountEl.textContent)||0; likesCountEl.textContent = Math.max(0, c-1); }
        } else {
            likedAds.add(adId);
            if (btnEl) { btnEl.classList.add('liked'); btnEl.innerHTML = '<i class="fas fa-thumbs-up"></i> J\'aime'; }
            if (likesCountEl) { const c = parseInt(likesCountEl.textContent)||0; likesCountEl.textContent = c+1; }
        }
        
        fetch(`/ads/${adId}/like`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        }).catch(err => console.error('Erreur like:', err));
    }

    // =========================================
    // MENU OPTIONS DES PUBLICATIONS (...)
    // =========================================
    function togglePostMenu(btn) {
        const menu = btn.nextElementSibling;
        const wasOpen = menu.classList.contains('show');
        // Fermer tous les menus ouverts
        document.querySelectorAll('.fb-post-options-menu.show').forEach(m => m.classList.remove('show'));
        if (!wasOpen) menu.classList.add('show');
    }

    // Fermer les menus au clic extérieur
    document.addEventListener('click', function() {
        document.querySelectorAll('.fb-post-options-menu.show').forEach(m => m.classList.remove('show'));
    });

    function savePost(adId, btn) {
        <?php if(auth()->guard()->check()): ?>
        fetch(`/ads/${adId}/toggle-save`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(r => r.json())
        .then(data => {
            const icon = btn.querySelector('i');
            if (data.saved) {
                icon.className = 'fas fa-bookmark';
                showToast('Publication enregistrée !', 'success');
            } else {
                icon.className = 'far fa-bookmark';
                showToast('Publication retirée des enregistrements', 'info');
            }
        })
        .catch(() => showToast('Erreur, réessayez', 'error'));
        <?php else: ?>
        showToast('Connectez-vous pour enregistrer', 'info');
        <?php endif; ?>
        closePostMenus();
    }

    function copyPostLink(adId) {
        const url = `${window.location.origin}/ads/${adId}`;
        navigator.clipboard.writeText(url).then(() => {
            showToast('Lien copié dans le presse-papier !', 'success');
        }).catch(() => {
            showToast('Impossible de copier le lien', 'error');
        });
        closePostMenus();
    }

    function hidePost(adId, btn) {
        const post = btn.closest('.fb-post');
        if (post) {
            post.style.transition = 'opacity 0.3s, max-height 0.4s';
            post.style.opacity = '0';
            post.style.maxHeight = post.offsetHeight + 'px';
            setTimeout(() => {
                post.style.maxHeight = '0';
                post.style.overflow = 'hidden';
                post.style.padding = '0';
                post.style.margin = '0';
            }, 300);
            setTimeout(() => post.remove(), 700);
            // Stocker en local pour ne pas le réafficher
            const hidden = JSON.parse(localStorage.getItem('hiddenPosts') || '[]');
            if (!hidden.includes(adId)) { hidden.push(adId); localStorage.setItem('hiddenPosts', JSON.stringify(hidden)); }
            showToast('Publication masquée', 'info');
        }
        closePostMenus();
    }

    function reportPost(adId) {
        <?php if(auth()->guard()->check()): ?>
        document.getElementById('reportAdId').value = adId;
        document.getElementById('reportMessage').value = '';
        document.querySelectorAll('.report-reason-item').forEach(i => i.classList.remove('selected'));
        document.getElementById('reportSubmitBtn').classList.remove('active');
        document.getElementById('reportModalOverlay').classList.add('show');
        <?php else: ?>
        showToast('Connectez-vous pour signaler', 'info');
        <?php endif; ?>
        closePostMenus();
    }

    let selectedReportReason = null;

    function selectReportReason(el, reason) {
        document.querySelectorAll('.report-reason-item').forEach(i => i.classList.remove('selected'));
        el.classList.add('selected');
        selectedReportReason = reason;
        document.getElementById('reportSubmitBtn').classList.add('active');
    }

    function closeReportModal() {
        document.getElementById('reportModalOverlay').classList.remove('show');
        selectedReportReason = null;
    }

    function submitReport() {
        const adId = document.getElementById('reportAdId').value;
        const message = document.getElementById('reportMessage').value.trim();
        if (!selectedReportReason) return;

        const btn = document.getElementById('reportSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i> Envoi...';

        fetch(`/ads/${adId}/report`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ reason: selectedReportReason, message: message || null })
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            closeReportModal();
            if (data.already_reported) {
                showToast('Vous avez déjà signalé cette publication', 'info');
            } else {
                showToast('Signalement envoyé. Merci pour votre vigilance !', 'success');
            }
        })
        .catch(() => {
            closeReportModal();
            showToast('Erreur lors du signalement', 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane" style="margin-right:6px;"></i> Envoyer le signalement';
        });
    }

    function closePostMenus() {
        document.querySelectorAll('.fb-post-options-menu.show').forEach(m => m.classList.remove('show'));
    }

    function sharePost(adId) {
        if (event) event.stopPropagation();
        const url = `${window.location.origin}/ads/${adId}`;
        if (navigator.share) {
            navigator.share({ title: 'Publication ProxiPro', url: url }).catch(() => {});
        } else {
            navigator.clipboard.writeText(url).then(() => {
                showToast('Lien copié !', 'success');
            }).catch(() => {
                showToast('Impossible de copier le lien', 'error');
            });
        }
    }
    
    function saveAd(adId) {
        // Appel AJAX pour sauvegarder
        fetch(`/saved-ads/${adId}/toggle`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(r => r.json())
        .then(data => {
            showToast('Annonce sauvegardée !', 'success');
        })
        .catch(err => {
            console.error(err);
            showToast('Annonce sauvegardée !', 'success');
        });
    }

    // =========================================
    // GESTION DU PARTAGE
    // =========================================
    function toggleShareMenu(adId, btn) {
        console.log('toggleShareMenu appelé pour:', adId);
        event.stopPropagation();
        const menu = document.getElementById(`share-menu-${adId}`);
        console.log('Menu trouvé:', menu);
        if (!menu) {
            console.error('Menu non trouvé:', `share-menu-${adId}`);
            return;
        }
        const isOpen = menu.classList.contains('open');
        console.log('Menu est ouvert:', isOpen);
        
        // Fermer tous les menus
        document.querySelectorAll('.share-menu.open').forEach(m => m.classList.remove('open'));
        
        if (!isOpen) {
            menu.classList.add('open');
            console.log('Menu maintenant ouvert');
        }
    }
    
    // Fermer le menu de partage en cliquant ailleurs
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.share-wrapper')) {
            document.querySelectorAll('.share-menu.open').forEach(m => m.classList.remove('open'));
        }
    });

    function copyLink(adId) {
        const url = `${window.location.origin}/ads/${adId}`;
        
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(() => {
                showShareFeedback(adId, 'copy');
                showToast('Lien copié dans le presse-papiers !', 'success');
                incrementShareCount(adId);
            });
        } else {
            fallbackCopyToClipboard(url);
            incrementShareCount(adId);
        }
        
        document.getElementById(`share-menu-${adId}`).classList.remove('open');
    }

    function fallbackCopyToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showToast('Lien copié dans le presse-papiers !', 'success');
        } catch (err) {
            showToast('Impossible de copier le lien', 'error');
        }
        document.body.removeChild(textArea);
    }

    function shareTo(platform, adId, title = '') {
        const url = `${window.location.origin}/ads/${adId}`;
        const shareTitle = title || 'Découvrez cette offre sur ProxiPro';
        let shareUrl = '';
        
        switch (platform) {
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(shareTitle)}`;
                break;
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
                break;
            case 'linkedin':
                shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`;
                break;
            case 'whatsapp':
                shareUrl = `https://wa.me/?text=${encodeURIComponent(shareTitle + ' ' + url)}`;
                break;
        }
        
        if (shareUrl) {
            window.open(shareUrl, '_blank', 'width=600,height=400');
            showToast(`Partagé sur ${platform.charAt(0).toUpperCase() + platform.slice(1)} !`, 'success');
            incrementShareCount(adId);
        }
        
        // Fermer le menu
        document.getElementById(`share-menu-${adId}`).classList.remove('open');
    }
    
    // Incrémenter le compteur de partages
    function incrementShareCount(adId) {
        fetch(`/api/ads/${adId}/share`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour le compteur dans l'interface
                const shareCountEl = document.querySelector(`.shares-count-${adId}`);
                if (shareCountEl) {
                    shareCountEl.textContent = data.shares_count;
                }
            }
        })
        .catch(error => console.log('Erreur partage:', error));
    }

    function showShareFeedback(adId, type) {
        const menu = document.getElementById(`share-menu-${adId}`);
        const option = menu.querySelector(`.share-option.${type}`);
        if (option) {
            option.classList.add('copied');
            const originalText = option.querySelector('span').textContent;
            option.querySelector('span').textContent = 'Copié !';
            setTimeout(() => {
                option.classList.remove('copied');
                option.querySelector('span').textContent = originalText;
            }, 1500);
        }
    }

    // =========================================
    // GESTION DES COMMENTAIRES
    // =========================================
    const loadedComments = new Set(); // Pour éviter de recharger les commentaires déjà chargés
    
    function toggleComments(adId) {
        if (event) event.stopPropagation();
        
        const section = document.getElementById(`comments-section-${adId}`);
        if (!section) return;
        
        const isVisible = section.style.display !== 'none';
        
        if (isVisible) {
            section.style.display = 'none';
        } else {
            section.style.display = 'block';
            
            // Charger les commentaires si pas déjà fait
            if (!loadedComments.has(adId)) {
                loadComments(adId);
            }
            
            // Focus sur l'input
            setTimeout(() => {
                const input = document.getElementById(`comment-input-${adId}`);
                if (input) input.focus();
            }, 100);
        }
    }
    
    async function loadComments(adId) {
        const commentsList = document.getElementById(`comments-list-${adId}`);
        if (!commentsList) return;
        
        try {
            const response = await fetch(`/ads/${adId}/comments`, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) return;
            
            const data = await response.json();
            
            // Mettre à jour le compteur avec le nombre total de commentaires
            if (data.total !== undefined) {
                const countElements = document.querySelectorAll(`.comments-count-${adId}`);
                countElements.forEach(el => {
                    el.textContent = data.total;
                });
            }
            
            if (data.success && data.comments && data.comments.length > 0) {
                // Masquer le message "aucun commentaire"
                const noComments = document.getElementById(`no-comments-${adId}`);
                if (noComments) noComments.style.display = 'none';
                
                // Afficher les commentaires
                let html = '';
                data.comments.forEach(comment => {
                    const avatarHtml = comment.user.avatar 
                        ? `<img src="${comment.user.avatar}" alt="${comment.user.name}">`
                        : `<div class="comment-avatar-placeholder-inline">${comment.user.initial}</div>`;
                    
                    const deleteBtn = comment.user.id == <?php echo e(Auth::id() ?? 'null'); ?> 
                        ? `<button class="comment-action-btn" onclick="deleteComment(${comment.id}, ${adId})"><i class="fas fa-trash-alt"></i> Supprimer</button>`
                        : '';
                    
                    html += `
                        <div class="comment-item-inline" data-comment-id="${comment.id}">
                            <div class="comment-avatar-inline">${avatarHtml}</div>
                            <div class="comment-content-inline">
                                <div class="comment-bubble">
                                    <a href="/profile/${comment.user.id}" class="comment-author-inline">${comment.user.name}</a>
                                    <p class="comment-text-inline">${escapeHtml(comment.content)}</p>
                                </div>
                                <div class="comment-actions-inline">
                                    <button class="comment-action-btn" onclick="likeComment(${comment.id})">
                                        <i class="far fa-heart"></i> J'aime
                                    </button>
                                    <span class="comment-time-inline">${comment.created_at}</span>
                                    ${deleteBtn}
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                commentsList.innerHTML = html;
                loadedComments.add(adId);
            }
        } catch (error) {
            console.error('Erreur chargement commentaires:', error);
        }
    }

    async function submitComment(event, adId) {
        event.preventDefault();
        
        const input = document.getElementById(`comment-input-${adId}`);
        const submitBtn = document.getElementById(`comment-submit-${adId}`);
        const content = input.value.trim();
        
        if (!content) return;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        try {
            const response = await fetch(`/ads/${adId}/comments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ content: content })
            });
            
            // Vérifier si la réponse est OK
            if (!response.ok) {
                if (response.status === 401) {
                    showToast('Vous devez être connecté pour commenter', 'error');
                    return;
                }
                if (response.status === 403) {
                    try {
                        const errData = await response.json();
                        showToast(errData.message || 'Vous n\'êtes pas autorisé à commenter cette annonce', 'error');
                    } catch(e) {
                        showToast('Vous n\'êtes pas autorisé à commenter cette annonce', 'error');
                    }
                    return;
                }
                if (response.status === 419) {
                    showToast('Session expirée, veuillez rafraîchir la page', 'error');
                    return;
                }
                const errorText = await response.text();
                console.error('Erreur serveur:', response.status, errorText);
                showToast('Erreur lors de l\'ajout du commentaire', 'error');
                return;
            }
            
            const data = await response.json();
            
            if (data.success) {
                const noComments = document.getElementById(`no-comments-${adId}`);
                if (noComments) noComments.style.display = 'none';
                
                const commentsList = document.getElementById(`comments-list-${adId}`);
                const comment = data.comment;
                
                const avatarHtml = comment.user.avatar 
                    ? `<img src="${comment.user.avatar}" alt="${comment.user.name}">`
                    : `<div class="comment-avatar-placeholder-inline">${comment.user.initial}</div>`;
                
                const commentHtml = `
                    <div class="comment-item-inline new-comment" data-comment-id="${comment.id}">
                        <div class="comment-avatar-inline">${avatarHtml}</div>
                        <div class="comment-content-inline">
                            <div class="comment-bubble">
                                <a href="/profile/${comment.user.id}" class="comment-author-inline">${comment.user.name}</a>
                                <p class="comment-text-inline">${escapeHtml(comment.content)}</p>
                            </div>
                            <div class="comment-actions-inline">
                                <button class="comment-action-btn" onclick="likeComment(${comment.id})">
                                    <i class="far fa-heart"></i> J'aime
                                </button>
                                <span class="comment-time-inline">À l'instant</span>
                                <button class="comment-action-btn" onclick="deleteComment(${comment.id}, ${adId})">
                                    <i class="fas fa-trash-alt"></i> Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                commentsList.insertAdjacentHTML('afterbegin', commentHtml);
                updateCommentCount(adId, 1);
                input.value = '';
                
                showToast('Commentaire ajouté !', 'success');
            } else {
                showToast(data.message || 'Erreur lors de l\'ajout', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showToast('Une erreur est survenue', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        }
    }

    async function deleteComment(commentId, adId) {
        if (!confirm('Supprimer ce commentaire ?')) return;
        
        try {
            const response = await fetch(`/comments/${commentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Erreur serveur:', response.status, errorText);
                showToast('Erreur lors de la suppression', 'error');
                return;
            }
            
            const data = await response.json();
            
            if (data.success) {
                const commentEl = document.querySelector(`.comment-item-inline[data-comment-id="${commentId}"]`);
                if (commentEl) {
                    commentEl.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    commentEl.style.opacity = '0';
                    commentEl.style.transform = 'translateX(-20px)';
                    setTimeout(() => {
                        commentEl.remove();
                        updateCommentCount(adId, -1);
                        
                        const commentsList = document.getElementById(`comments-list-${adId}`);
                        if (commentsList && commentsList.querySelectorAll('.comment-item-inline').length === 0) {
                            commentsList.innerHTML = `
                                <div class="no-comments-inline" id="no-comments-${adId}">
                                    <i class="far fa-comment-dots"></i>
                                    Aucun commentaire. Soyez le premier à commenter !
                                </div>
                            `;
                        }
                    }, 300);
                }
                
                showToast('Commentaire supprimé', 'success');
            } else {
                showToast(data.message || 'Erreur lors de la suppression', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showToast('Erreur lors de la suppression', 'error');
        }
    }

    function likeComment(commentId) {
        const btn = event.target.closest('.comment-action-btn');
        btn.classList.toggle('liked');
        
        const icon = btn.querySelector('i');
        if (btn.classList.contains('liked')) {
            icon.classList.remove('far');
            icon.classList.add('fas');
            btn.style.color = '#dc2626';
        } else {
            icon.classList.remove('fas');
            icon.classList.add('far');
            btn.style.color = '';
        }
        
        // Appel AJAX
        fetch(`/comments/${commentId}/like`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        }).catch(err => console.error('Erreur like commentaire:', err));
    }

    function updateCommentCount(adId, delta) {
        const countElements = document.querySelectorAll(`.comments-count-${adId}`);
        countElements.forEach(el => {
            const current = parseInt(el.textContent) || 0;
            el.textContent = Math.max(0, current + delta);
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // =========================================
    // SYSTÈME DE TOAST NOTIFICATIONS AMÉLIORÉ
    // =========================================
    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer') || createToastContainer();
        const toastId = 'toast-' + Date.now();
        
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            info: 'info-circle'
        };
        
        const titles = {
            success: 'Succès',
            error: 'Erreur',
            info: 'Information'
        };
        
        const toastHtml = `
            <div id="${toastId}" class="toast-custom ${type}">
                <div class="toast-icon">
                    <i class="fas fa-${icons[type]}"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${titles[type]}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" onclick="this.closest('.toast-custom').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', toastHtml);
        const toast = document.getElementById(toastId);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100px)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
    
    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
        return container;
    }
    
    // =========================================
    // POPUP CATÉGORIES MULTI-STEP (style Yoojo)
    // =========================================
    let popupState = { step: 1, category: null, subcategory: null };

    function openCategoryPopup() {
        popupState = { step: 1, category: null, subcategory: null };
        renderPopupStep1();
        document.getElementById('categoryPopupOverlay').classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('categoryPopupSearch').focus(), 100);
    }

    function closeCategoryPopup() {
        document.getElementById('categoryPopupOverlay').classList.remove('active');
        document.body.style.overflow = '';
        document.getElementById('categoryPopupSearch').value = '';
    }

    // === STEP 1 : Liste des catégories principales ===
    function renderPopupStep1() {
        popupState.step = 1;
        popupState.category = null;
        popupState.subcategory = null;
        const body = document.getElementById('categoryPopupBody');
        const step3 = document.getElementById('categoryPopupStep3');
        const search = document.getElementById('categoryPopupSearch');

        step3.style.display = 'none';
        body.style.display = 'block';
        search.style.display = 'block';
        search.value = '';
        search.placeholder = 'Rechercher un service...';

        document.getElementById('categoryPopupTitle').textContent = 'De quoi avez-vous besoin ?';
        document.getElementById('categoryBreadcrumb').innerHTML = '<span class="bc-current">Catégories</span>';

        body.innerHTML = '';
        for (const [catName, catData] of Object.entries(categoriesData)) {
            const icon = catData.icon || 'fas fa-folder';
            const subs = catData.subs || [];
            const subsPreview = subs.map(s => s.name).slice(0, 3).join(', ');

            const item = document.createElement('div');
            item.className = 'category-popup-item';
            item.setAttribute('data-search', catName.toLowerCase() + ' ' + subs.map(s => s.name.toLowerCase()).join(' '));
            item.innerHTML = `
                <div class="category-popup-item-icon"><i class="${icon}"></i></div>
                <div class="category-popup-item-text">
                    <div class="category-popup-item-name">${catName}</div>
                    <div class="category-popup-item-desc">${subsPreview}${subs.length > 3 ? '...' : ''}</div>
                </div>
                <i class="fas fa-chevron-right category-popup-item-arrow"></i>
            `;
            item.onclick = () => renderPopupStep2(catName);
            body.appendChild(item);
        }
    }

    // === STEP 2 : Sous-catégories d'une catégorie ===
    function renderPopupStep2(catName) {
        popupState.step = 2;
        popupState.category = catName;
        popupState.subcategory = null;
        const body = document.getElementById('categoryPopupBody');
        const step3 = document.getElementById('categoryPopupStep3');
        const search = document.getElementById('categoryPopupSearch');
        const catData = categoriesData[catName];
        if (!catData) return;

        step3.style.display = 'none';
        body.style.display = 'block';
        search.style.display = 'block';
        search.value = '';
        search.placeholder = 'Rechercher dans ' + catName + '...';

        document.getElementById('categoryPopupTitle').textContent = catName;
        document.getElementById('categoryBreadcrumb').innerHTML = `
            <div class="category-popup-steps-indicator" style="margin-bottom:0;">
                <div class="step-dot done"></div>
                <div class="step-dot-line done"></div>
                <div class="step-dot active"></div>
                <div class="step-dot-line"></div>
                <div class="step-dot"></div>
            </div>
            <div style="margin-top:6px;">
                <span class="bc-link" onclick="renderPopupStep1()">Catégories</span>
                <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
                <span class="bc-current">${catName}</span>
            </div>
        `;

        body.innerHTML = '';
        const subs = catData.subs || [];
        const icon = catData.icon || 'fas fa-folder';

        subs.forEach(sub => {
            const item = document.createElement('div');
            item.className = 'category-popup-item';
            item.setAttribute('data-search', sub.name.toLowerCase());
            item.innerHTML = `
                <div class="category-popup-item-icon" style="background:#ede9fe;"><i class="${sub.icon || icon}" style="color:#7c3aed;"></i></div>
                <div class="category-popup-item-text">
                    <div class="category-popup-item-name">${sub.name}</div>
                    <div class="category-popup-item-desc">${sub.count || 0} annonce${(sub.count || 0) > 1 ? 's' : ''} disponible${(sub.count || 0) > 1 ? 's' : ''}</div>
                </div>
                <i class="fas fa-chevron-right category-popup-item-arrow"></i>
            `;
            item.onclick = () => renderPopupStep3(catName, sub.name);
            body.appendChild(item);
        });
    }

    // === STEP 3 : Formulaire complet de publication ===
    const popupCitiesByCountry = {
        "France": ["Paris", "Marseille", "Lyon", "Toulouse", "Nice", "Nantes", "Strasbourg", "Montpellier", "Bordeaux", "Lille", "Rennes", "Reims", "Le Havre", "Saint-Étienne", "Toulon", "Grenoble", "Dijon", "Angers", "Nîmes"],
        "Mayotte": ["Mamoudzou", "Koungou", "Dzaoudzi", "Dembeni", "Bandraboua", "Tsingoni", "Sada", "Ouangani", "Chiconi", "Pamandzi", "Mtsamboro", "Acoua", "Chirongui", "Bouéni", "Kani-Kéli", "Bandrélé", "M'Tsangamouji"],
        "Madagascar": ["Antananarivo", "Toamasina", "Antsirabe", "Fianarantsoa", "Mahajanga", "Toliara", "Antsiranana", "Ambatondrazaka", "Antalaha", "Nosy Be"],
        "La Réunion": ["Saint-Denis", "Saint-Paul", "Saint-Pierre", "Le Tampon", "Saint-André", "Saint-Louis", "Saint-Benoît", "Le Port", "Saint-Joseph", "Sainte-Marie"],
        "Maurice": ["Port-Louis", "Beau Bassin-Rose Hill", "Vacoas-Phoenix", "Curepipe", "Quatre Bornes", "Triolet", "Goodlands", "Centre de Flacq", "Mahébourg", "Grand Baie"],
        "Belgique": ["Bruxelles", "Anvers", "Gand", "Charleroi", "Liège", "Bruges", "Namur", "Louvain", "Mons"],
        "Suisse": ["Zurich", "Genève", "Bâle", "Lausanne", "Berne", "Lucerne", "Fribourg", "Neuchâtel", "Sion"],
        "Canada": ["Toronto", "Montréal", "Vancouver", "Calgary", "Edmonton", "Ottawa", "Winnipeg", "Québec"],
        "Sénégal": ["Dakar", "Thiès", "Rufisque", "Kaolack", "M'Bour", "Saint-Louis", "Ziguinchor", "Touba"],
        "Côte d'Ivoire": ["Abidjan", "Bouaké", "Yamoussoukro", "Korhogo", "San-Pédro", "Man", "Daloa"],
        "Maroc": ["Casablanca", "Rabat", "Fès", "Marrakech", "Tanger", "Agadir", "Meknès", "Oujda"],
        "Tunisie": ["Tunis", "Sfax", "Sousse", "Kairouan", "Bizerte", "Gabès", "Monastir"],
        "Algérie": ["Alger", "Oran", "Constantine", "Annaba", "Blida", "Batna", "Sétif", "Djelfa"]
    };

    let popupSelectedPhotos = [];
    const popupMaxPhotos = 2;

    function renderPopupStep3(catName, subName) {
        popupState.step = 3;
        popupState.category = catName;
        popupState.subcategory = subName;
        popupSelectedPhotos = [];
        const body = document.getElementById('categoryPopupBody');
        const step3 = document.getElementById('categoryPopupStep3');
        const search = document.getElementById('categoryPopupSearch');

        body.style.display = 'none';
        search.style.display = 'none';
        step3.style.display = 'block';

        document.getElementById('categoryPopupTitle').textContent = 'Publier votre demande';
        document.getElementById('categoryBreadcrumb').innerHTML = `
            <div class="category-popup-steps-indicator" style="margin-bottom:0;">
                <div class="step-dot done"></div>
                <div class="step-dot-line done"></div>
                <div class="step-dot done"></div>
                <div class="step-dot-line done"></div>
                <div class="step-dot active"></div>
            </div>
            <div style="margin-top:6px;">
                <span class="bc-link" onclick="renderPopupStep1()">Catégories</span>
                <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
                <span class="bc-link" onclick="renderPopupStep2('${catName.replace(/'/g, "\\'")}')">${catName}</span>
                <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
                <span class="bc-current">${subName}</span>
            </div>
        `;

        step3.innerHTML = `
            <div class="category-popup-selection-tag">
                <i class="fas fa-tag"></i> ${catName} → ${subName}
            </div>

            <div class="popup-form-group">
                <label class="popup-form-label">Titre de votre demande <span class="required">*</span></label>
                <input type="text" class="popup-form-input" id="popupAdTitle" 
                    placeholder="Ex: Recherche ${subName.toLowerCase()} à Mamoudzou" maxlength="255">
                <div class="popup-form-error" id="popupTitleError"></div>
            </div>

            <div class="popup-form-group">
                <label class="popup-form-label">Description <span class="required">*</span></label>
                <textarea class="category-popup-step3-textarea" id="categoryNeedDescription" 
                    placeholder="Décrivez votre besoin : type de travaux, délais, budget estimé..."
                    style="min-height:80px;"></textarea>
                <div class="popup-form-error" id="popupDescError"></div>
            </div>

            <div class="popup-form-group">
                <label class="popup-form-label"><i class="fas fa-camera me-1"></i> Photos <span style="color:#9ca3af;font-weight:400;">(facultatif)</span></label>
                <div class="popup-photo-upload" onclick="document.getElementById('popupPhotoInput').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Cliquez pour ajouter des photos (max ${popupMaxPhotos})</span>
                </div>
                <input type="file" id="popupPhotoInput" multiple accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="handlePopupPhotos(this)">
                <div class="popup-photo-previews" id="popupPhotoPreviews"></div>
                <div class="popup-form-hint">Formats : JPG, PNG, WEBP • Max 5 MB par photo</div>
            </div>

            <div class="popup-form-group">
                <label class="popup-form-label"><i class="fas fa-map-marker-alt me-1"></i> Localisation <span class="required">*</span></label>
                <div class="popup-form-row">
                    <div>
                        <select class="popup-form-select" id="popupCountry" onchange="updatePopupCities()">
                            <option value="">-- Pays --</option>
                            <option value="France">🇫🇷 France</option>
                            <option value="Mayotte">🇾🇹 Mayotte</option>
                            <option value="Madagascar">🇲🇬 Madagascar</option>
                            <option value="La Réunion">🇷🇪 La Réunion</option>
                            <option value="Maurice">🇲🇺 Maurice</option>
                            <option value="Belgique">🇧🇪 Belgique</option>
                            <option value="Suisse">🇨🇭 Suisse</option>
                            <option value="Canada">🇨🇦 Canada</option>
                            <option value="Sénégal">🇸🇳 Sénégal</option>
                            <option value="Côte d'Ivoire">🇨🇮 Côte d'Ivoire</option>
                            <option value="Maroc">🇲🇦 Maroc</option>
                            <option value="Tunisie">🇹🇳 Tunisie</option>
                            <option value="Algérie">🇩🇿 Algérie</option>
                        </select>
                    </div>
                    <div>
                        <select class="popup-form-select" id="popupCity" disabled>
                            <option value="">-- Ville --</option>
                        </select>
                    </div>
                </div>
                <input type="text" class="popup-form-input" id="popupLocationManual" 
                    placeholder="Saisissez votre ville" style="display:none; margin-top:8px;">
                <div class="popup-form-error" id="popupLocationError"></div>
            </div>

            <div class="popup-form-group">
                <label class="popup-form-label"><i class="fas fa-euro-sign me-1"></i> Budget estimé <span style="color:#9ca3af;font-weight:400;">(facultatif)</span></label>
                <div style="display:flex; gap:0;">
                    <input type="number" class="popup-form-input" id="popupPrice" placeholder="Prix" min="0" step="0.01"
                        style="border-radius:10px 0 0 10px; flex:1;">
                    <span style="background:#e5e7eb; border:2px solid #e5e7eb; border-left:none; border-radius:0 10px 10px 0; padding:0 14px; display:flex; align-items:center; font-weight:600; color:#6b7280; font-size:0.9rem;">€</span>
                </div>
                <div class="popup-form-hint">Laissez vide si le prix est à discuter</div>
            </div>

            <button class="category-popup-step3-submit" id="popupSubmitBtn" onclick="submitPopupAd()">
                <i class="fas fa-paper-plane me-2"></i>Publier ma demande
            </button>
            <div class="popup-form-error" id="popupGlobalError" style="text-align:center; margin-top:8px;"></div>
        `;

        // Auto-select country if user has one
        <?php if(auth()->guard()->check()): ?>
        <?php if(Auth::user()->country): ?>
        setTimeout(() => {
            const countryEl = document.getElementById('popupCountry');
            if (countryEl) {
                countryEl.value = <?php echo json_encode(Auth::user()->country, 15, 512) ?>;
                updatePopupCities();
            }
        }, 50);
        <?php endif; ?>
        <?php endif; ?>

        setTimeout(() => document.getElementById('popupAdTitle')?.focus(), 100);
    }

    function updatePopupCities() {
        const countryEl = document.getElementById('popupCountry');
        const cityEl = document.getElementById('popupCity');
        const manualEl = document.getElementById('popupLocationManual');
        const country = countryEl.value;

        cityEl.innerHTML = '<option value="">-- Ville --</option>';
        manualEl.style.display = 'none';
        manualEl.value = '';

        if (country && popupCitiesByCountry[country]) {
            cityEl.disabled = false;
            popupCitiesByCountry[country].forEach(city => {
                const opt = document.createElement('option');
                opt.value = city;
                opt.textContent = city;
                cityEl.appendChild(opt);
            });
            const otherOpt = document.createElement('option');
            otherOpt.value = '__other__';
            otherOpt.textContent = '🔤 Autre ville';
            cityEl.appendChild(otherOpt);
        } else {
            cityEl.disabled = true;
        }

        cityEl.onchange = function() {
            if (this.value === '__other__') {
                manualEl.style.display = 'block';
                manualEl.focus();
            } else {
                manualEl.style.display = 'none';
                manualEl.value = '';
            }
        };
    }

    function handlePopupPhotos(input) {
        const files = Array.from(input.files);
        const remaining = popupMaxPhotos - popupSelectedPhotos.length;
        const toAdd = files.slice(0, remaining);

        toAdd.forEach(file => {
            if (file.size > 5 * 1024 * 1024) return;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) return;
            popupSelectedPhotos.push(file);
        });

        input.value = '';
        renderPopupPhotosPreviews();
    }

    function renderPopupPhotosPreviews() {
        const container = document.getElementById('popupPhotoPreviews');
        if (!container) return;
        container.innerHTML = '';
        popupSelectedPhotos.forEach((file, idx) => {
            const div = document.createElement('div');
            div.className = 'popup-photo-preview';
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            const btn = document.createElement('button');
            btn.className = 'popup-photo-remove';
            btn.innerHTML = '&times;';
            btn.onclick = (e) => { e.stopPropagation(); popupSelectedPhotos.splice(idx, 1); renderPopupPhotosPreviews(); };
            div.appendChild(img);
            div.appendChild(btn);
            container.appendChild(div);
        });
    }

    function submitPopupAd() {
        // Clear errors
        ['popupTitleError', 'popupDescError', 'popupLocationError', 'popupGlobalError'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = '';
        });

        const title = document.getElementById('popupAdTitle')?.value?.trim() || '';
        const description = document.getElementById('categoryNeedDescription')?.value?.trim() || '';
        const country = document.getElementById('popupCountry')?.value || '';
        const city = document.getElementById('popupCity')?.value || '';
        const manualLocation = document.getElementById('popupLocationManual')?.value?.trim() || '';
        const price = document.getElementById('popupPrice')?.value || '';

        // Client-side validation
        let hasError = false;
        if (!title) {
            document.getElementById('popupTitleError').textContent = 'Le titre est obligatoire.';
            hasError = true;
        }
        if (!description) {
            document.getElementById('popupDescError').textContent = 'La description est obligatoire.';
            hasError = true;
        }
        if (!country || (!city && !manualLocation) || city === '') {
            document.getElementById('popupLocationError').textContent = 'Veuillez sélectionner un pays et une ville.';
            hasError = true;
        }
        if (hasError) return;

        const submitBtn = document.getElementById('popupSubmitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="popup-submit-spinner"></span>Publication en cours...';

        const formData = new FormData();
        formData.append('title', title);
        formData.append('description', description);
        formData.append('category', popupState.subcategory || popupState.category);
        formData.append('country', country);
        formData.append('service_type', 'demande');

        if (city === '__other__' && manualLocation) {
            formData.append('location', manualLocation);
        } else if (city && city !== '__other__') {
            formData.append('city', city);
            formData.append('location', city);
        }

        if (price) formData.append('price', price);

        popupSelectedPhotos.forEach(file => {
            formData.append('photos[]', file);
        });

        fetch('<?php echo e(route("ads.storeFromPopup")); ?>', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showPopupSuccess(data);
            } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Publier ma demande';
                if (data.errors) {
                    const firstError = Object.values(data.errors)[0];
                    document.getElementById('popupGlobalError').textContent = Array.isArray(firstError) ? firstError[0] : firstError;
                } else {
                    document.getElementById('popupGlobalError').textContent = data.message || 'Une erreur est survenue.';
                }
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Publier ma demande';
            document.getElementById('popupGlobalError').textContent = 'Erreur de connexion. Veuillez réessayer.';
        });
    }

    function showPopupSuccess(data) {
        const step3 = document.getElementById('categoryPopupStep3');
        document.getElementById('categoryPopupTitle').textContent = '';
        document.getElementById('categoryBreadcrumb').innerHTML = '';

        step3.innerHTML = `
            <div class="popup-success-container">
                <div class="popup-success-icon"><i class="fas fa-check"></i></div>
                <div class="popup-success-title">Demande publiée avec succès !</div>
                <div class="popup-success-text">Votre annonce est maintenant visible. Les professionnels correspondants seront notifiés.</div>
                <div class="popup-success-actions">
                    <a href="${data.redirect_url}" class="popup-success-btn popup-success-btn-primary">
                        <i class="fas fa-rocket me-1"></i> Booster l'annonce
                    </a>
                    <button class="popup-success-btn popup-success-btn-secondary" onclick="closeCategoryPopup(); location.reload();">
                        Fermer
                    </button>
                </div>
            </div>
        `;
    }

    function submitCategoryNeed() {
        submitPopupAd();
    }

    function filterCategoryPopup(query) {
        const items = document.querySelectorAll('#categoryPopupBody .category-popup-item');
        const q = query.toLowerCase().trim();
        let hasVisible = false;

        items.forEach(item => {
            const searchText = item.getAttribute('data-search') || '';
            const match = !q || searchText.includes(q);
            item.style.display = match ? 'flex' : 'none';
            if (match) hasVisible = true;
        });

        let noResult = document.getElementById('categoryPopupNoResult');
        if (!hasVisible && q) {
            if (!noResult) {
                noResult = document.createElement('div');
                noResult.id = 'categoryPopupNoResult';
                noResult.className = 'category-popup-no-result';
                noResult.innerHTML = '<i class="fas fa-search"></i><p>Aucun résultat pour "' + q.replace(/</g,'&lt;') + '"</p>';
                document.getElementById('categoryPopupBody').appendChild(noResult);
            } else {
                noResult.innerHTML = '<i class="fas fa-search"></i><p>Aucun résultat pour "' + q.replace(/</g,'&lt;') + '"</p>';
            }
            noResult.style.display = 'block';
        } else if (noResult) {
            noResult.style.display = 'none';
        }
    }

    // =========================================
    // PROPOSER MES SERVICES — Wizard 4 étapes
    // =========================================
    let selectedProPlan = 'monthly';
    let wizardSelectedSubcats = {}; // { 'Bricolage & Travaux': ['Plombier', 'Électricien'], ... }
    let currentWizardStep = 1;
    const totalWizardSteps = 4;
    let wizCatTreeBuilt = false;

    const wizardStepConfig = {
        1: { icon: 'fas fa-user-edit', title: 'Votre profil professionnel', desc: 'Complétez ou vérifiez vos informations.' },
        2: { icon: 'fas fa-th-large', title: 'Vos domaines d\'activité', desc: 'Sélectionnez vos catégories et sous-catégories.' },
        3: { icon: 'fas fa-bell', title: 'Notifications & alertes', desc: 'Configurez comment recevoir les demandes clients.' },
        4: { icon: 'fas fa-rocket', title: 'Boostez votre profil', desc: 'Passez Pro pour être mis en avant et recevoir plus de demandes.' }
    };

    function handleProposerServices() {
        <?php if(auth()->guard()->check()): ?>
        <?php if(Auth::user()->hasActiveProSubscription() && Auth::user()->is_service_provider && Auth::user()->pro_service_categories && count(Auth::user()->pro_service_categories) > 0): ?>
        // L'utilisateur a déjà un abonnement actif et des services configurés
        // On ouvre directement à l'étape 2 (catégories) pour modifier ses services
        openSubPopup(2);
        <?php elseif(Auth::user()->is_service_provider): ?>
        // Prestataire mais sans abonnement ou sans services - étape 2
        openSubPopup(2);
        <?php else: ?>
        openSubPopup(1);
        <?php endif; ?>
        <?php else: ?>
        window.location.href = '<?php echo e(route("login")); ?>';
        <?php endif; ?>
    }

    function openSubPopup(startStep) {
        currentWizardStep = startStep || 1;

        // Build category tree if not built yet
        if (!wizCatTreeBuilt) {
            buildCategoryTree();
            wizCatTreeBuilt = true;
        }

        // Pre-select user's existing service categories
        <?php if(auth()->guard()->check()): ?>
        <?php if(Auth::user()->pro_service_categories): ?>
        const existingCats = <?php echo json_encode(Auth::user()->pro_service_categories ?? [], 15, 512) ?>;
        if (Array.isArray(existingCats)) {
            existingCats.forEach(cat => {
                // Try to find as subcategory
                for (const [catName, catData] of Object.entries(categoriesData)) {
                    if (catData.subcategories && catData.subcategories.includes(cat)) {
                        if (!wizardSelectedSubcats[catName]) wizardSelectedSubcats[catName] = [];
                        if (!wizardSelectedSubcats[catName].includes(cat)) {
                            wizardSelectedSubcats[catName].push(cat);
                            const chip = document.querySelector(`.wiz-cat-subcat[data-subcat="${CSS.escape(cat)}"][data-parent="${CSS.escape(catName)}"]`);
                            if (chip) chip.classList.add('selected');
                        }
                    }
                }
            });
            refreshCatGroupStates();
            updateWizSelectedCount();
        }
        <?php endif; ?>

        // Pre-fill notification toggles
        const notifEmail = document.getElementById('notifEmail');
        const notifRealtime = document.getElementById('notifRealtime');
        const notifSms = document.getElementById('notifSms');
        if (notifEmail) notifEmail.checked = <?php echo e(Auth::user()->pro_notifications_email ? 'true' : 'false'); ?>;
        if (notifRealtime) notifRealtime.checked = <?php echo e(Auth::user()->pro_notifications_realtime ? 'true' : 'false'); ?>;
        if (notifSms) notifSms.checked = <?php echo e(Auth::user()->pro_notifications_sms ? 'true' : 'false'); ?>;
        <?php endif; ?>

        showWizardStep(currentWizardStep);
        updateSubmitLabel();
        document.getElementById('subPopupOverlay').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSubPopup() {
        document.getElementById('subPopupOverlay').classList.remove('active');
        document.body.style.overflow = '';
    }

    function goToWizardStep(step) {
        if (step < 1) step = 1;
        if (step > totalWizardSteps) step = totalWizardSteps;
        currentWizardStep = step;
        showWizardStep(step);
        if (step === 4) {
            updateSubmitLabel();
            updateWizRecap();
        }
    }

    function showWizardStep(step) {
        for (let i = 1; i <= totalWizardSteps; i++) {
            const el = document.getElementById('subPopupStep' + i);
            if (el) el.style.display = (i === step) ? 'block' : 'none';
        }
        document.querySelectorAll('.sub-popup-step-dot').forEach(dot => {
            const dotStep = parseInt(dot.getAttribute('data-step'));
            dot.classList.remove('active', 'done');
            if (dotStep === step) dot.classList.add('active');
            else if (dotStep < step) dot.classList.add('done');
        });
        const cfg = wizardStepConfig[step];
        if (cfg) {
            const iconEl = document.getElementById('subPopupHeroIcon');
            const titleEl = document.getElementById('subPopupHeroTitle');
            const descEl = document.getElementById('subPopupHeroDesc');
            if (iconEl) iconEl.className = cfg.icon;
            if (titleEl) titleEl.textContent = cfg.title;
            if (descEl) descEl.textContent = cfg.desc;
        }
    }

    // ---- Step 1: Save profile inline ----
    function previewWizardAvatar(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('wizProfileAvatarPreview');
                preview.innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function detectWizardGps() {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(pos => {
            const statusEl = document.getElementById('wizGpsStatus');
            if (statusEl) {
                statusEl.style.color = '#10b981';
                statusEl.textContent = '✓ Activée';
            }
            // Store for later save
            window._wizGpsLat = pos.coords.latitude;
            window._wizGpsLng = pos.coords.longitude;
        }, () => {
            showToast('Impossible d\'activer la localisation', 'error');
        });
    }

    function saveWizardProfileAndNext() {
        const name = document.getElementById('wizProfileName')?.value.trim();
        if (!name) {
            showToast('Le nom est obligatoire', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('name', name);
        formData.append('email', '<?php echo e(Auth::user()->email ?? ''); ?>');
        const phone = document.getElementById('wizProfilePhone')?.value.trim();
        if (phone) formData.append('phone', phone);
        const bio = document.getElementById('wizProfileBio')?.value.trim();
        if (bio) formData.append('bio', bio);

        // City & address → location field
        const city = document.getElementById('wizProfileCity')?.value.trim();
        const address = document.getElementById('wizProfileAddress')?.value.trim();
        if (city || address) formData.append('location', address || city);

        // Avatar
        const avatarInput = document.getElementById('wizProfileAvatar');
        if (avatarInput?.files?.length) {
            formData.append('avatar', avatarInput.files[0]);
        }

        fetch('<?php echo e(route("profile.update")); ?>', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: formData
        })
        .then(r => {
            if (!r.ok && r.status !== 302) throw new Error('HTTP ' + r.status);
            return r.text();
        })
        .then(() => {
            // Also save city separately if needed
            const cityVal = document.getElementById('wizProfileCity')?.value.trim();
            if (cityVal) {
                fetch('/service-provider/update-profile-fields', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        city: cityVal,
                        address: document.getElementById('wizProfileAddress')?.value.trim() || null,
                        latitude: window._wizGpsLat || null,
                        longitude: window._wizGpsLng || null
                    })
                }).catch(() => {});
            }

            const msg = document.getElementById('wizardProfileSaveMsg');
            if (msg) {
                msg.querySelector('span').textContent = 'Profil enregistré avec succès !';
                msg.style.display = 'flex';
                setTimeout(() => { msg.style.display = 'none'; }, 2000);
            }
            setTimeout(() => goToWizardStep(2), 600);
        })
        .catch(() => {
            showToast('Erreur lors de la sauvegarde du profil', 'error');
        });
    }

    // ---- Step 2: Category tree with subcategories ----
    function buildCategoryTree() {
        const container = document.getElementById('subPopupCategoriesTree');
        if (!container) return;
        container.innerHTML = '';

        // Only use service categories (not marketplace)
        const serviceCats = <?php echo json_encode(config('categories.services'), 15, 512) ?>;

        for (const [catName, catData] of Object.entries(serviceCats)) {
            const group = document.createElement('div');
            group.className = 'wiz-cat-group';
            group.setAttribute('data-cat', catName);

            const subcats = catData.subcategories || [];
            const bgColor = (catData.color || '#6366f1') + '15';
            const fgColor = catData.color || '#6366f1';

            group.innerHTML = `
                <div class="wiz-cat-header" onclick="toggleWizCatGroup(this.parentElement)">
                    <div class="wiz-cat-header-icon" style="background:${bgColor}; color:${fgColor};">
                        <i class="${catData.fa_icon || 'fas fa-folder'}"></i>
                    </div>
                    <div class="wiz-cat-header-name">${catName}</div>
                    <span class="wiz-cat-header-count">0</span>
                    <i class="fas fa-chevron-down wiz-cat-header-arrow"></i>
                </div>
                <div class="wiz-cat-subcats">
                    ${subcats.map(sc => `<span class="wiz-cat-subcat" data-subcat="${sc}" data-parent="${catName}" onclick="toggleWizSubcat(this, '${catName.replace(/'/g, "\\'")}', '${sc.replace(/'/g, "\\'")}')">${sc}</span>`).join('')}
                </div>
            `;
            container.appendChild(group);
        }
    }

    function toggleWizCatGroup(groupEl) {
        groupEl.classList.toggle('open');
    }

    function toggleWizSubcat(chip, catName, subcat) {
        chip.classList.toggle('selected');
        if (!wizardSelectedSubcats[catName]) wizardSelectedSubcats[catName] = [];

        if (chip.classList.contains('selected')) {
            if (!wizardSelectedSubcats[catName].includes(subcat)) {
                wizardSelectedSubcats[catName].push(subcat);
            }
        } else {
            wizardSelectedSubcats[catName] = wizardSelectedSubcats[catName].filter(s => s !== subcat);
            if (wizardSelectedSubcats[catName].length === 0) delete wizardSelectedSubcats[catName];
        }

        refreshCatGroupStates();
        updateWizSelectedCount();
    }

    function refreshCatGroupStates() {
        document.querySelectorAll('.wiz-cat-group').forEach(group => {
            const catName = group.getAttribute('data-cat');
            const count = (wizardSelectedSubcats[catName] || []).length;
            const countEl = group.querySelector('.wiz-cat-header-count');
            if (countEl) {
                countEl.style.display = count > 0 ? 'inline' : 'none';
                countEl.textContent = count;
            }
            group.classList.toggle('has-selected', count > 0);
        });
    }

    function updateWizSelectedCount() {
        const total = Object.values(wizardSelectedSubcats).reduce((sum, arr) => sum + arr.length, 0);
        const countEl = document.getElementById('subPopupSelectedCount');
        if (countEl) {
            countEl.style.display = total > 0 ? 'block' : 'none';
            countEl.textContent = total + ' sous-catégorie(s) sélectionnée(s)';
        }
    }

    function filterWizardCategories(query) {
        const q = query.toLowerCase().trim();
        document.querySelectorAll('.wiz-cat-group').forEach(group => {
            const catName = group.getAttribute('data-cat').toLowerCase();
            const subcats = group.querySelectorAll('.wiz-cat-subcat');
            let visible = false;

            if (!q || catName.includes(q)) {
                visible = true;
                subcats.forEach(sc => sc.style.display = '');
            } else {
                subcats.forEach(sc => {
                    const match = sc.getAttribute('data-subcat').toLowerCase().includes(q);
                    sc.style.display = match ? '' : 'none';
                    if (match) visible = true;
                });
            }

            group.style.display = visible ? '' : 'none';
            if (q && visible && !catName.includes(q)) {
                group.classList.add('open');
            }
        });
    }

    function saveWizardCategoriesAndNext() {
        const total = Object.values(wizardSelectedSubcats).reduce((sum, arr) => sum + arr.length, 0);
        if (total === 0) {
            showToast('Sélectionnez au moins une sous-catégorie', 'error');
            return;
        }

        // Save categories via AJAX
        const allSubcats = [];
        const mainCat = Object.keys(wizardSelectedSubcats)[0] || '';
        Object.values(wizardSelectedSubcats).forEach(arr => allSubcats.push(...arr));

        fetch('<?php echo e(route("profile.save-categories")); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({
                service_category: mainCat,
                service_subcategories: allSubcats,
                city: document.getElementById('wizProfileCity')?.value.trim() || null
            })
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(() => {
            goToWizardStep(3);
        })
        .catch(() => {
            // Continue anyway, save will retry at final step
            goToWizardStep(3);
        });
    }

    // ---- Step 3: Notification settings ----
    function saveWizardNotifsAndNext() {
        const prefs = {
            pro_notifications_email: document.getElementById('notifEmail')?.checked ?? true,
            pro_notifications_realtime: document.getElementById('notifRealtime')?.checked ?? true,
            pro_notifications_sms: document.getElementById('notifSms')?.checked ?? false
        };

        fetch('/service-provider/update-profile-fields', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(prefs)
        }).catch(() => {});

        goToWizardStep(4);
    }

    // ---- Step 4: Plan selection & subscription ----
    function updateWizRecap() {
        const nameEl = document.getElementById('wizRecapName');
        const cityEl = document.getElementById('wizRecapCity');
        const catsEl = document.getElementById('wizRecapCats');
        if (nameEl) nameEl.textContent = document.getElementById('wizProfileName')?.value || '—';
        if (cityEl) cityEl.textContent = document.getElementById('wizProfileCity')?.value || '—';
        const totalCats = Object.values(wizardSelectedSubcats).reduce((sum, arr) => sum + arr.length, 0);
        if (catsEl) catsEl.textContent = totalCats > 0 ? totalCats + ' spécialité(s)' : '—';
    }

    function selectProPlan(plan) {
        selectedProPlan = plan;
        document.querySelectorAll('.sub-popup-plan').forEach(el => el.classList.remove('selected'));
        if (plan === 'free') document.getElementById('planFree').classList.add('selected');
        else if (plan === 'monthly') document.getElementById('planMonthly').classList.add('selected');
        else document.getElementById('planAnnual').classList.add('selected');
        updateSubmitLabel();
    }

    function updateSubmitLabel() {
        const label = document.getElementById('subPopupSubmitLabel');
        const icon = document.querySelector('#subPopupSubmitBtn i');
        if (!label || !icon) return;
        if (selectedProPlan === 'free') {
            label.textContent = 'Continuer gratuitement';
            icon.className = 'fas fa-arrow-right';
        } else {
            label.textContent = selectedProPlan === 'annual' ? 'S\'abonner — 85€/an' : 'S\'abonner — 9,99€/mois';
            icon.className = 'fas fa-rocket';
        }
    }

    function submitProRegistration() {
        const btn = document.getElementById('subPopupSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';

        // Si l'utilisateur a déjà un abonnement, ne pas envoyer de plan
        const userHasSubscription = <?php echo e(Auth::check() && Auth::user()->hasActiveProSubscription() ? 'true' : 'false'); ?>;

        // Build services from selected subcategories
        const services = [];
        for (const [catName, subcats] of Object.entries(wizardSelectedSubcats)) {
            subcats.forEach(sc => {
                services.push({ main_category: catName, subcategory: sc, experience_years: 0, description: '' });
            });
        }

        if (services.length === 0) {
            const firstCat = Object.keys(categoriesData)[0];
            services.push({ main_category: firstCat, subcategory: firstCat, experience_years: 0, description: '' });
        }

        const notifPrefs = {
            pro_notifications_email: document.getElementById('notifEmail')?.checked ?? true,
            pro_notifications_realtime: document.getElementById('notifRealtime')?.checked ?? true,
            pro_notifications_sms: document.getElementById('notifSms')?.checked ?? false
        };

        fetch('<?php echo e(route("service-provider.register")); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({
                services: services,
                plan: userHasSubscription ? null : (selectedProPlan === 'free' ? null : selectedProPlan),
                notification_preferences: notifPrefs
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (data.already_subscribed) {
                    closeSubPopup();
                    showToast(data.message || 'Vos nouveaux services ont été ajoutés à votre profil ! Les informations précédentes ont été conservées.', 'success');
                    setTimeout(() => location.reload(), 2500);
                } else if (data.requires_payment && data.checkout_url) {
                    window.location.href = data.checkout_url;
                } else {
                    closeSubPopup();
                    showToast('Vos nouvelles informations ont été ajoutées à votre profil ! Vos données précédentes ont été conservées. Vous pouvez les modifier depuis votre profil.', 'success');
                    setTimeout(() => location.reload(), 2500);
                }
            } else {
                showToast(data.message || 'Une erreur est survenue.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-rocket"></i> <span id="subPopupSubmitLabel">Réessayer</span>';
            }
        })
        .catch(err => {
            console.error('Pro registration error:', err);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-rocket"></i> <span id="subPopupSubmitLabel">Réessayer</span>';
        });
    }

    // =========================================
    // TOGGLE MORE FILTERS
    // =========================================
    function toggleMoreFilters() {
        const filterBar = document.querySelector('.filter-bar');
        filterBar.classList.toggle('show-all-filters');
        // Toggle mobile visibility for secondary filters
        document.querySelectorAll('.filter-dropdown.secondary-filter').forEach(el => {
            el.classList.toggle('mobile-visible');
        });
        updateActiveFilterCount();
    }

    function updateActiveFilterCount() {
        let count = 0;
        if (currentFilters.country || currentFilters.city) count++;
        if (currentFilters.priceMin || currentFilters.priceMax) count++;
        const badge = document.getElementById('activeFilterCount');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
    }

    // État actuel des filtres
    let currentFilters = {
        mode: 'missions', // 'missions' ou 'providers'
        category: '',
        subcategory: '',
        sort: 'recommended',
        country: '',
        city: '',
        priceMin: null,
        priceMax: null,
        lat: <?php echo e($userLat ?? 'null'); ?>,
        lng: <?php echo e($userLng ?? 'null'); ?>,
        radius: <?php echo e($userRadius ?? 50); ?>,
        geoEnabled: <?php echo e(($geoEnabled ?? false) ? 'true' : 'false'); ?>

    };

    // Données des catégories
    const categoriesData = <?php echo json_encode($missionCategories, 15, 512) ?>;

    /**
     * Toggle dropdown menu
     */
    function toggleDropdown(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        const isOpen = dropdown.classList.contains('open');
        
        // Fermer tous les autres dropdowns
        document.querySelectorAll('.filter-dropdown').forEach(d => d.classList.remove('open'));
        
        if (!isOpen) {
            dropdown.classList.add('open');
        }
    }

    // Fermer dropdowns en cliquant ailleurs
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.filter-dropdown')) {
            document.querySelectorAll('.filter-dropdown').forEach(d => d.classList.remove('open'));
        }
    });

    /**
     * Changer le mode de vue (prestataires / missions)
     */
    function setViewMode(mode) {
        currentFilters.mode = mode;
        
        // Mettre à jour les boutons toggle
        document.getElementById('togglePro').classList.toggle('active', mode === 'providers');
        document.getElementById('toggleOffres').classList.toggle('active', mode === 'missions');
        document.getElementById('toggleDemandes').classList.remove('active');
        
        // Afficher/Masquer les sections
        document.getElementById('providersSection').style.display = mode === 'providers' ? 'block' : 'none';
        document.getElementById('missionsSection').style.display = mode === 'missions' ? 'block' : 'none';
        
        // Recharger les données
        loadData();
    }

    /**
     * Sélectionner une catégorie
     */
    function selectCategory(category) {
        currentFilters.category = category;
        currentFilters.subcategory = '';
        
        // Mettre à jour le label
        const label = document.getElementById('categoryLabel');
        label.textContent = category || 'Catégorie';
        
        // Mettre à jour les items sélectionnés
        document.querySelectorAll('#categoryMenu .filter-menu-item').forEach(item => {
            item.classList.toggle('selected', item.dataset.value === category);
        });
        
        // Afficher le dropdown des sous-catégories si une catégorie est sélectionnée
        const subcatDropdown = document.getElementById('subcategoryDropdown');
        if (category && categoriesData[category] && categoriesData[category].subs) {
            subcatDropdown.style.display = 'block';
            populateSubcategories(categoriesData[category].subs);
        } else {
            subcatDropdown.style.display = 'none';
        }
        
        // Fermer le dropdown
        document.getElementById('categoryDropdown').classList.remove('open');
        
        // Recharger les données
        loadData();
    }

    /**
     * Remplir les sous-catégories
     */
    function populateSubcategories(subs) {
        const menu = document.getElementById('subcategoryMenu');
        let html = '<div class="filter-menu-item selected" data-value="" onclick="selectSubcategory(\'\')"><i class="fas fa-check"></i> Toutes</div>';
        
        subs.forEach(sub => {
            html += `<div class="filter-menu-item" data-value="${sub.name}" onclick="selectSubcategory('${sub.name}')">
                <i class="${sub.icon || 'fas fa-tag'}"></i> ${sub.name}
                <span class="ms-auto text-muted small">${sub.count || 0}</span>
            </div>`;
        });
        
        menu.innerHTML = html;
        document.getElementById('subcategoryLabel').textContent = 'Sous-catégorie';
    }

    /**
     * Sélectionner une sous-catégorie
     */
    function selectSubcategory(subcategory) {
        currentFilters.subcategory = subcategory;
        
        document.getElementById('subcategoryLabel').textContent = subcategory || 'Sous-catégorie';
        
        document.querySelectorAll('#subcategoryMenu .filter-menu-item').forEach(item => {
            item.classList.toggle('selected', item.dataset.value === subcategory);
        });
        
        document.getElementById('subcategoryDropdown').classList.remove('open');
        loadData();
    }

    /**
     * Sélectionner le tri
     */
    function selectSort(sort) {
        currentFilters.sort = sort;
        
        const labels = {
            'recommended': 'Recommandé',
            'recent': 'Plus récent',
            'urgent': 'Urgent'
        };
        
        document.getElementById('sortLabel').textContent = labels[sort] || 'Trier';
        
        document.querySelectorAll('#sortDropdown .filter-menu-item').forEach(item => {
            item.classList.toggle('selected', item.dataset.value === sort);
        });
        
        document.getElementById('sortDropdown').classList.remove('open');
        loadData();
    }

    /**
     * Définir la tranche de prix
     */
    function setPriceRange(min, max) {
        document.getElementById('priceMin').value = min || '';
        document.getElementById('priceMax').value = max || '';
    }

    /**
     * Réinitialiser le filtre de prix
     */
    function resetPriceFilter() {
        document.getElementById('priceMin').value = '';
        document.getElementById('priceMax').value = '';
        currentFilters.priceMin = null;
        currentFilters.priceMax = null;
        document.getElementById('priceLabel').textContent = 'Prix';
        document.getElementById('priceDropdown').classList.remove('open');
        loadData();
    }

    /**
     * Appliquer le filtre de prix
     */
    function applyPriceFilter() {
        currentFilters.priceMin = document.getElementById('priceMin').value || null;
        currentFilters.priceMax = document.getElementById('priceMax').value || null;
        
        let label = 'Prix';
        if (currentFilters.priceMin && currentFilters.priceMax) {
            label = `${currentFilters.priceMin}€ - ${currentFilters.priceMax}€`;
        } else if (currentFilters.priceMin) {
            label = `${currentFilters.priceMin}€+`;
        } else if (currentFilters.priceMax) {
            label = `0 - ${currentFilters.priceMax}€`;
        }
        
        document.getElementById('priceLabel').textContent = label;
        document.getElementById('priceDropdown').classList.remove('open');
        loadData();
    }

    /**
     * Suggestions villes / codes postaux selon le pays sélectionné
     */
    function getSelectedCountryCode() {
        const select = document.getElementById('countrySelect');
        if (!select) return '';
        const option = select.options[select.selectedIndex];
        return option?.dataset?.code || '';
    }

    function resetCitySuggestions() {
        const list = document.getElementById('citySuggestions');
        if (list) list.innerHTML = '';
    }

    function buildLocationLabel(item) {
        const address = item.address || {};
        const city = address.city || address.town || address.village || address.municipality || address.county || '';
        const postcode = address.postcode || '';
        if (city && postcode) return `${city} ${postcode}`;
        if (city) return city;
        if (postcode) return postcode;
        return item.display_name || '';
    }

    const debouncedCitySearch = (() => {
        let timeoutId;
        return (query) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => fetchCitySuggestions(query), 350);
        };
    })();

    function fetchCitySuggestions(query) {
        const code = getSelectedCountryCode();
        const list = document.getElementById('citySuggestions');
        if (!list) return;

        if (!code || !query || query.length < 2) {
            list.innerHTML = '';
            return;
        }

        const url = `https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=10&countrycodes=${code}&q=${encodeURIComponent(query)}`;

        fetch(url)
            .then(response => response.json())
            .then(results => {
                const labels = new Set();
                results.forEach(item => {
                    const label = buildLocationLabel(item);
                    if (label) labels.add(label);
                });

                list.innerHTML = Array.from(labels)
                    .map(label => `<option value="${label}"></option>`)
                    .join('');
            })
            .catch(() => {
                list.innerHTML = '';
            });
    }

    // ===== CITIES DATA BY COUNTRY FOR FILTER =====
    const filterCitiesByCountry = {
        "France": ["Paris", "Marseille", "Lyon", "Toulouse", "Nice", "Nantes", "Strasbourg", "Montpellier", "Bordeaux", "Lille", "Rennes", "Reims", "Le Havre", "Saint-Étienne", "Toulon", "Grenoble", "Dijon", "Angers", "Nîmes", "Villeurbanne", "Clermont-Ferrand", "Le Mans", "Aix-en-Provence", "Brest", "Tours", "Amiens", "Limoges", "Perpignan", "Metz", "Besançon", "Orléans", "Rouen", "Mulhouse", "Caen", "Nancy"],
        "Mayotte": ["Mamoudzou", "Koungou", "Dzaoudzi", "Dembeni", "Bandraboua", "Tsingoni", "Sada", "Ouangani", "Chiconi", "Pamandzi", "Mtsamboro", "Acoua", "Chirongui", "Bouéni", "Kani-Kéli", "Bandrélé", "M'Tsangamouji"],
        "Madagascar": ["Antananarivo", "Toamasina", "Antsirabe", "Fianarantsoa", "Mahajanga", "Toliara", "Antsiranana", "Ambatondrazaka", "Antalaha", "Nosy Be", "Sainte-Marie", "Morondava", "Ambositra", "Mananjary", "Sambava"],
        "La Réunion": ["Saint-Denis", "Saint-Paul", "Saint-Pierre", "Le Tampon", "Saint-André", "Saint-Louis", "Saint-Benoît", "Le Port", "Saint-Joseph", "Sainte-Marie", "Sainte-Suzanne", "Saint-Leu", "La Possession", "Bras-Panon", "Cilaos", "Salazie"],
        "Maurice": ["Port-Louis", "Beau Bassin-Rose Hill", "Vacoas-Phoenix", "Curepipe", "Quatre Bornes", "Triolet", "Goodlands", "Centre de Flacq", "Mahébourg", "Grand Baie", "Flic en Flac", "Tamarin"],
        "Belgique": ["Bruxelles", "Anvers", "Gand", "Charleroi", "Liège", "Bruges", "Namur", "Louvain", "Mons", "Alost", "Malines", "La Louvière", "Courtrai", "Ostende", "Hasselt", "Tournai", "Genk", "Seraing", "Verviers", "Mouscron"],
        "Suisse": ["Zurich", "Genève", "Bâle", "Lausanne", "Berne", "Winterthour", "Lucerne", "Saint-Gall", "Lugano", "Bienne", "Thoune", "Köniz", "Fribourg", "Schaffhouse", "Neuchâtel", "Sion"],
        "Canada": ["Toronto", "Montréal", "Vancouver", "Calgary", "Edmonton", "Ottawa", "Winnipeg", "Québec", "Hamilton", "Kitchener", "London", "Victoria", "Halifax", "Oshawa", "Windsor", "Saskatoon", "Regina", "Sherbrooke", "Laval", "Gatineau"],
        "Sénégal": ["Dakar", "Thiès", "Rufisque", "Kaolack", "M'Bour", "Saint-Louis", "Ziguinchor", "Diourbel", "Louga", "Tambacounda", "Kolda", "Richard-Toll", "Tivaouane", "Touba", "Kédougou"],
        "Côte d'Ivoire": ["Abidjan", "Bouaké", "Yamoussoukro", "Korhogo", "San-Pédro", "Man", "Divo", "Daloa", "Gagnoa", "Abengourou", "Anyama", "Agboville", "Dabou", "Grand-Bassam", "Bingerville"],
        "Maroc": ["Casablanca", "Rabat", "Fès", "Marrakech", "Tanger", "Agadir", "Meknès", "Oujda", "Kénitra", "Tétouan", "Safi", "El Jadida", "Nador", "Béni Mellal", "Essaouira", "Ouarzazate"],
        "Tunisie": ["Tunis", "Sfax", "Sousse", "Kairouan", "Bizerte", "Gabès", "Ariana", "Gafsa", "El Mourouj", "Kasserine", "Monastir", "La Marsa", "Hammamet", "Djerba", "Tozeur"],
        "Algérie": ["Alger", "Oran", "Constantine", "Annaba", "Blida", "Batna", "Sétif", "Djelfa", "Sidi Bel Abbès", "Biskra", "Tébessa", "Skikda", "Tiaret", "Béjaïa", "Tlemcen", "Ouargla"],
        "Guadeloupe": ["Pointe-à-Pitre", "Les Abymes", "Baie-Mahault", "Le Gosier", "Petit-Bourg", "Sainte-Anne", "Le Moule", "Sainte-Rose", "Capesterre-Belle-Eau", "Basse-Terre"],
        "Martinique": ["Fort-de-France", "Le Lamentin", "Le Robert", "Schoelcher", "Sainte-Marie", "Le François", "Ducos", "Saint-Joseph", "La Trinité", "Rivière-Pilote"],
        "Guyane": ["Cayenne", "Saint-Laurent-du-Maroni", "Kourou", "Matoury", "Rémire-Montjoly", "Macouria", "Mana", "Maripasoula", "Apatou", "Iracoubo"]
    };

    function updateFilterCities() {
        const countrySelect = document.getElementById('countrySelect');
        const citySelect = document.getElementById('citySelect');
        const customCityWrapper = document.getElementById('customCityWrapper');
        const cityInput = document.getElementById('cityInput');
        const country = countrySelect.value;
        
        citySelect.innerHTML = '<option value="">-- Sélectionner une ville --</option>';
        customCityWrapper.style.display = 'none';
        if (cityInput) cityInput.value = '';
        
        if (country && filterCitiesByCountry[country]) {
            citySelect.disabled = false;
            filterCitiesByCountry[country].forEach(city => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            });
            // Add "Autre" option for manual input
            const otherOption = document.createElement('option');
            otherOption.value = "__other__";
            otherOption.textContent = "🔤 Autre ville (saisir manuellement)";
            citySelect.appendChild(otherOption);
        } else {
            citySelect.disabled = true;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Ajuster la position sticky du sidebar en fonction du geo-banner
        const geoBanner = document.getElementById('geoBanner');
        const sidebarLeft = document.querySelector('.feed-sidebar-left');
        const sidebarRight = document.querySelector('.feed-sidebar-right');
        const baseTop = 170;

        function adjustSidebarPosition() {
            if (geoBanner) {
                const bannerRect = geoBanner.getBoundingClientRect();
                const bannerBottom = bannerRect.bottom;
                const newTop = bannerBottom > baseTop ? (bannerBottom + 10) + 'px' : baseTop + 'px';
                const newMaxH = bannerBottom > baseTop ? 'calc(100vh - ' + (bannerBottom + 30) + 'px)' : 'calc(100vh - 190px)';
                if (sidebarLeft) { sidebarLeft.style.top = newTop; sidebarLeft.style.maxHeight = newMaxH; }
                if (sidebarRight) { sidebarRight.style.top = newTop; sidebarRight.style.maxHeight = newMaxH; }
            }
        }

        adjustSidebarPosition();
        window.addEventListener('scroll', adjustSidebarPosition, { passive: true });
        window.addEventListener('resize', adjustSidebarPosition, { passive: true });

        const citySelect = document.getElementById('citySelect');
        const customCityWrapper = document.getElementById('customCityWrapper');
        const cityInput = document.getElementById('cityInput');

        if (citySelect) {
            citySelect.addEventListener('change', function() {
                if (this.value === "__other__") {
                    customCityWrapper.style.display = 'block';
                    cityInput.focus();
                } else {
                    customCityWrapper.style.display = 'none';
                    if (cityInput) cityInput.value = '';
                }
            });
        }
    });

    /**
     * Réinitialiser le filtre de localisation
     */
    function resetLocationFilter() {
        document.getElementById('countrySelect').value = '';
        document.getElementById('citySelect').innerHTML = '<option value="">-- Sélectionner une ville --</option>';
        document.getElementById('citySelect').disabled = true;
        document.getElementById('customCityWrapper').style.display = 'none';
        document.getElementById('cityInput').value = '';
        currentFilters.country = '';
        currentFilters.city = '';
        document.getElementById('locationLabel').textContent = 'Localisation';
        document.getElementById('locationDropdown').classList.remove('open');
        loadData();
    }

    /**
     * Appliquer le filtre de localisation
     */
    function applyLocationFilter() {
        const countrySelect = document.getElementById('countrySelect');
        const citySelect = document.getElementById('citySelect');
        const cityInput = document.getElementById('cityInput');
        
        currentFilters.country = countrySelect ? countrySelect.value : '';
        
        // Si "Autre" est sélectionné, utiliser le champ de saisie manuelle
        if (citySelect.value === "__other__") {
            currentFilters.city = cityInput.value || '';
        } else {
            currentFilters.city = citySelect.value || '';
        }
        
        let label = 'Localisation';
        if (currentFilters.city) {
            label = currentFilters.city;
        } else if (currentFilters.country) {
            label = currentFilters.country;
        }
        
        document.getElementById('locationLabel').textContent = label;
        document.getElementById('locationDropdown').classList.remove('open');
        loadData();
    }

    /**
     * Charger les données avec les filtres
     */
    function loadData() {
        const overlay = document.getElementById('loadingOverlay');
        overlay.classList.add('active');
        
        // Construire l'URL avec les filtres
        let url = currentFilters.mode === 'providers' 
            ? '<?php echo e(route("feed.professionals")); ?>' 
            : '<?php echo e(route("feed.filter-ads")); ?>';
        
        const params = new URLSearchParams();
        
        if (currentFilters.category) params.append('category', currentFilters.category);
        if (currentFilters.subcategory) params.append('subcategory', currentFilters.subcategory);
        if (currentFilters.sort) params.append('sort', currentFilters.sort);
        if (currentFilters.city) {
            params.append('location', currentFilters.city);
        } else if (currentFilters.country) {
            params.append('location', currentFilters.country);
        }
        if (currentFilters.priceMin) params.append('price_min', currentFilters.priceMin);
        if (currentFilters.priceMax) params.append('price_max', currentFilters.priceMax);
        // Geo params
        if (currentFilters.geoEnabled && currentFilters.lat && currentFilters.lng) {
            params.append('lat', currentFilters.lat);
            params.append('lng', currentFilters.lng);
            params.append('radius', currentFilters.radius);
        }
        params.append('format', 'json');
        
        fetch(`${url}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                overlay.classList.remove('active');
                
                if (currentFilters.mode === 'providers') {
                    renderProviders(data.professionals || data);
                } else {
                    renderMissions(data.ads || data);
                }
            })
            .catch(err => {
                console.error('Erreur chargement:', err);
                overlay.classList.remove('active');
            });
    }

    /**
     * Rendu des prestataires
     */
    function renderProviders(providers) {
        const grid = document.getElementById('providersGrid');
        
        if (!providers || providers.length === 0) {
            grid.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="fas fa-users"></i>
                    <h3>Aucun prestataire trouvé</h3>
                    <p>Essayez d'autres critères de recherche</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        providers.forEach(pro => {
            const avatar = pro.avatar 
                ? `<img src="/storage/${pro.avatar}" alt="${pro.name}">`
                : `<div class="provider-image-placeholder">${pro.name.charAt(0).toUpperCase()}</div>`;
            
            const badge = pro.profession 
                ? `<div class="provider-badge-top"><i class="fas fa-briefcase"></i> ${pro.profession.substring(0, 25)}</div>` 
                : (pro.service_category ? `<div class="provider-badge-top"><i class="fas fa-briefcase"></i> ${pro.service_category.substring(0, 25)}</div>` : '');
            
            const proBadge = (pro.user_type === 'professionnel' || pro.pro_onboarding_completed || pro.has_active_pro_subscription)
                ? '<span class="badge-pro">PRO</span>' 
                : '';
            
            const price = pro.hourly_rate 
                ? `<span class="provider-price">${pro.hourly_rate} €/h</span>` 
                : '';
            
            html += `
                <a href="/user/${pro.id}" class="provider-card">
                    <div class="provider-image-wrapper">
                        ${avatar}
                        ${badge}
                    </div>
                    <div class="provider-card-info">
                        <div class="provider-info-header">
                            <h3 class="provider-name">${pro.name.substring(0, 18)} ${proBadge}</h3>
                            ${price}
                        </div>
                        <div class="provider-rating">
                            <span class="star-icon">★</span>
                            <span class="rating-value">${(pro.rating || (Math.random() * 0.5 + 4.5)).toFixed(1).replace('.', ',')}</span>
                            <span class="reviews-count">(${pro.reviews_count || Math.floor(Math.random() * 500 + 50)} avis)</span>
                        </div>
                        ${pro.bio ? `<p class="provider-category">${pro.bio.substring(0, 40)}</p>` : ''}
                    </div>
                </a>
            `;
        });
        
        grid.innerHTML = html;
    }

    /**
     * Construire l'URL d'une photo
     */
    function buildPhotoUrl(photo) {
        if (!photo) return '';
        if (photo.startsWith('http://') || photo.startsWith('https://')) return photo;
        if (photo.startsWith('/storage/')) return photo;
        if (photo.startsWith('storage/')) return '/' + photo;
        if (photo.startsWith('public/')) return '/storage/' + photo.replace('public/', '');
        return '/storage/' + photo.replace(/^\/+/, '');
    }

    /**
     * Générer le HTML du bloc Professionnels à la une
     */
    function buildFeaturedProsBlockHtml() {
        if (!featuredProsData || featuredProsData.length === 0) return '';
        let prosCards = '';
        featuredProsData.forEach(pro => {
            const imageHtml = pro.avatar
                ? `<img src="/storage/${pro.avatar}" alt="${(pro.name||'').replace(/"/g,'&quot;')}">`
                : `<div class="featured-pro-image-placeholder">${(pro.name||'P').charAt(0).toUpperCase()}</div>`;
            const proBadge = pro.is_pro ? ' <span class="badge-pro-featured">PRO</span>' : '';
            const ratingVal = pro.verified_reviews_avg ? parseFloat(pro.verified_reviews_avg).toFixed(1).replace('.', ',') : null;
            const reviewsCount = pro.verified_reviews_count || 0;
            const profession = (pro.profession || 'Professionnel').substring(0, 25);

            let ratingHtml = '';
            if (ratingVal) {
                const stars = Math.round(parseFloat(pro.verified_reviews_avg) * 2) / 2;
                let starsHtml = '';
                for (let i = 1; i <= 5; i++) {
                    if (i <= Math.floor(stars)) starsHtml += '<i class="fas fa-star"></i>';
                    else if (i - 0.5 <= stars) starsHtml += '<i class="fas fa-star-half-alt"></i>';
                    else starsHtml += '<i class="far fa-star" style="color:#cbd5e1;"></i>';
                }
                ratingHtml = `<div class="featured-pro-rating">${starsHtml} <span>${ratingVal} (${reviewsCount} avis)</span></div>`;
            }

            prosCards += `
                <a href="/user/${pro.id}" class="featured-pro-card">
                    <div class="featured-pro-image">${imageHtml}</div>
                    <div class="featured-pro-info">
                        <div class="featured-pro-name">${(pro.name||'').substring(0, 18)}${proBadge}</div>
                        ${ratingHtml}
                        <div class="featured-pro-category">${profession}</div>
                    </div>
                </a>
            `;
        });

        return `
            <div class="featured-pros-section">
                <div class="featured-pros-header">
                    <h2><i class="fas fa-user-shield"></i> Professionnels à la une</h2>
                    <a href="javascript:void(0)" onclick="setViewMode('providers'); window.scrollTo({top: 0, behavior: 'smooth'});" class="featured-pros-viewall">Voir tout <i class="fas fa-chevron-right"></i></a>
                </div>
                <div class="featured-pros-grid">${prosCards}</div>
            </div>
        `;
    }

    /**
     * Rendu des publications - Format Facebook
     */
    function renderMissions(missions) {
        const grid = document.getElementById('missionsGrid');
        
        if (!missions || missions.length === 0) {
            grid.innerHTML = `
                <div style="text-align: center; padding: 60px 20px; color: #65676b;">
                    <i class="fas fa-briefcase" style="font-size: 2.5rem; color: #bec3c9; margin-bottom: 12px;"></i>
                    <h3 style="font-weight: 600; color: #050505;">Aucune publication trouvée</h3>
                    <p>Essayez d'autres critères de recherche</p>
                </div>
            `;
            return;
        }
        
        const isAuth = <?php echo e(Auth::check() ? 'true' : 'false'); ?>;
        const authUser = isAuth ? <?php echo json_encode(['name' => Auth::user()?->name, 'avatar' => Auth::user()?->avatar]); ?> : null;
        
        let html = '';
        let featuredProsInserted = false;
        missions.forEach((ad, loopIndex) => {

            // Les publications urgentes s'affichent uniquement dans le carousel horizontal
            if (ad.is_urgent) return;

            // Injecter le bloc Professionnels à la une après les 2 premières cartes
            if (loopIndex === 2 && !featuredProsInserted) {
                html += buildFeaturedProsBlockHtml();
                featuredProsInserted = true;
            }

            const userAvatar = ad.user && ad.user.avatar 
                ? `<img src="/storage/${ad.user.avatar}" alt="${(ad.user.name||'').replace(/"/g,'')}">`
                : `<div class="fb-post-avatar-placeholder">${(ad.user?.name || 'U').charAt(0).toUpperCase()}</div>`;
            
            let photos = [];
            if (ad.photos) {
                if (Array.isArray(ad.photos)) photos = ad.photos;
                else if (typeof ad.photos === 'string') { try { photos = JSON.parse(ad.photos); } catch { photos = [ad.photos]; } }
            }
            photos = photos.filter(p => p).map(p => buildPhotoUrl(p));
            const photoCount = photos.length;
            
            let photosHtml = '';
            if (photoCount === 1) {
                photosHtml = `<div class="fb-post-photos single" onclick="openAdDetail(this.closest('.fb-post'))"><img src="${photos[0]}" alt="" onerror="this.parentElement.style.display='none';"></div>`;
            } else if (photoCount >= 2) {
                const cls = photoCount === 2 ? 'two' : 'three-plus';
                const show = photos.slice(0, Math.min(photoCount, 4));
                photosHtml = `<div class="fb-post-photos multi ${cls}" onclick="openAdDetail(this.closest('.fb-post'))">`;
                show.forEach((url, i) => {
                    photosHtml += `<div class="fb-photo-item"><img src="${url}" alt="" onerror="this.parentElement.style.display='none';">`;
                    if (i === 3 && photoCount > 4) photosHtml += `<div class="fb-photo-more-overlay">+${photoCount - 4}</div>`;
                    photosHtml += `</div>`;
                });
                photosHtml += `</div>`;
            }

            const priceTag = ad.price 
                ? `<span class="fb-post-tag price"><i class="fas fa-tag"></i> ${new Intl.NumberFormat('fr-FR').format(ad.price)} €</span>` 
                : `<span class="fb-post-tag"><i class="fas fa-tag"></i> À discuter</span>`;
            const catTag = ad.category ? `<span class="fb-post-tag"><i class="fas fa-folder"></i> ${ad.category}</span>` : '';
            const urgentMeta = ad.is_urgent ? `<span>·</span><span style="color:#dc2626;font-weight:600;"><i class="fas fa-bolt"></i> Urgent</span>` : '';
            const boostMeta = (ad.is_boosted && ad.boost_end && new Date(ad.boost_end) > new Date()) ? `<span class="fb-post-sponsored-tag boost"><i class="fas fa-rocket"></i> Sponsorisé</span>` : '';

            const safeJson = JSON.stringify(ad).replace(/'/g, '&#39;').replace(/"/g, '&quot;');

            let commentFormHtml = '';
            if (isAuth) {
                const authAvatar = authUser.avatar 
                    ? `<img src="/storage/${authUser.avatar}" alt="">`
                    : `<div class="fb-post-avatar-placeholder" style="width:32px;height:32px;font-size:0.8rem;">${authUser.name.charAt(0).toUpperCase()}</div>`;
                commentFormHtml = `
                    <form class="fb-comment-form" onsubmit="submitComment(event, ${ad.id})">
                        <div class="fb-comment-avatar">${authAvatar}</div>
                        <div class="fb-comment-input-wrap">
                            <input type="text" id="comment-input-${ad.id}" placeholder="Écrire un commentaire..." autocomplete="off">
                            <button type="submit" class="fb-comment-send-btn" id="comment-submit-${ad.id}"><i class="fas fa-paper-plane"></i></button>
                        </div>
                    </form>`;
            } else {
                commentFormHtml = `<div style="text-align:center;padding:8px;font-size:0.85rem;color:#65676b;"><a href="/login" style="color:#1877f2;text-decoration:none;font-weight:600;">Connectez-vous</a> pour commenter</div>`;
            }

            const searchBoostClass = (ad.is_boosted && ad.boost_end && new Date(ad.boost_end) > new Date()) ? ' boosted-post' : '';

            html += `
                <div class="fb-post${ad.is_urgent ? ' urgent-flow' : ''}${searchBoostClass}" data-ad-id="${ad.id}" data-ad-json="${safeJson}">
                    <div class="fb-post-header">
                        <a href="/profile/${ad.user?.id || ''}" class="fb-post-avatar">${userAvatar}</a>
                        <div class="fb-post-header-info">
                            <a href="/profile/${ad.user?.id || ''}" class="fb-post-author">
                                ${ad.user?.name || 'Utilisateur'}
                                ${ad.user?.is_verified ? '<i class="fas fa-check-circle" style="color: #1877f2; font-size: 0.75rem;"></i>' : ''}
                            </a>
                            <div class="fb-post-meta">
                                <span>${ad.created_at_human || 'Récemment'}</span>
                                <span>·</span>
                                <span>${(ad.location || 'France').substring(0, 25)}</span>
                                ${urgentMeta}${boostMeta}
                            </div>
                        </div>
                        <div class="fb-post-options">
                            <button class="fb-post-options-btn" onclick="event.stopPropagation(); togglePostMenu(this)" title="Plus d'options"><i class="fas fa-ellipsis-h"></i></button>
                            <div class="fb-post-options-menu">
                                <button class="fb-post-menu-item" onclick="event.stopPropagation(); window.location.href='/profile/'+(ad.user?.id||ad.user_id||'')"><i class="fas fa-user"></i><span class="menu-item-text">Afficher le profil<small>Voir le profil de l'annonceur</small></span></button>
                                <button class="fb-post-menu-item" onclick="event.stopPropagation(); savePost(${ad.id}, this)"><i class="far fa-bookmark"></i><span class="menu-item-text">Enregistrer la publication<small>Ajouter à vos éléments enregistrés</small></span></button>
                                <button class="fb-post-menu-item" onclick="event.stopPropagation(); copyPostLink(${ad.id})"><i class="fas fa-link"></i><span class="menu-item-text">Copier le lien</span></button>
                                <div class="fb-post-menu-divider"></div>
                                <button class="fb-post-menu-item" onclick="event.stopPropagation(); hidePost(${ad.id}, this)"><i class="fas fa-eye-slash"></i><span class="menu-item-text">Masquer la publication<small>Voir moins de publications de ce type</small></span></button>
                                <div class="fb-post-menu-divider"></div>
                                <button class="fb-post-menu-item danger" onclick="event.stopPropagation(); reportPost(${ad.id})"><i class="fas fa-flag"></i><span class="menu-item-text">Signaler la publication<small>Cette publication pose problème</small></span></button>
                            </div>
                        </div>
                    </div>
                    <div class="fb-post-body">
                        <div class="fb-post-title">${ad.title}</div>
                        <div class="fb-post-text">${(ad.description || '').substring(0, 300)}${(ad.description||'').length > 300 ? '...' : ''}</div>
                        <div class="fb-post-tags">${priceTag}${catTag}</div>
                    </div>
                    ${photosHtml}
                    <div class="fb-post-reactions-bar">
                        <div class="reactions-left"><span>👍</span> <span class="likes-count-${ad.id}">0</span></div>
                        <div class="reactions-right">
                            <span onclick="toggleComments(${ad.id})"><span class="comments-count-${ad.id}">${ad.comments_count || 0}</span> commentaires</span>
                            <span>${ad.shares_count || 0} partages</span>
                        </div>
                    </div>
                    <div class="fb-post-actions">
                        <button class="fb-action-btn" onclick="toggleLike(${ad.id}, this)"><i class="far fa-thumbs-up"></i> J'aime</button>
                        <button class="fb-action-btn" onclick="toggleComments(${ad.id})"><i class="far fa-comment"></i> Commenter</button>
                        <button class="fb-action-btn" onclick="sharePost(${ad.id})"><i class="fas fa-share"></i> Partager</button>
                        <button class="fb-action-btn contact-btn" onclick="window.location.href='/ads/${ad.id}'"><i class="fas fa-envelope"></i> Contacter</button>
                    </div>
                    <div class="fb-post-comments" id="comments-section-${ad.id}" style="display: none;">
                        <div class="fb-comments-list" id="comments-list-${ad.id}">
                            <div class="no-comments-msg" id="no-comments-${ad.id}"><i class="far fa-comment-dots"></i> Soyez le premier à commenter</div>
                        </div>
                        ${commentFormHtml}
                    </div>
                </div>
            `;
        });
        
        grid.innerHTML = html;
    }
    
    // Fonction pour afficher le modal de signalement
    function showReportModal(adId) {
        alert("Fonctionnalité de signalement pour l'annonce #" + adId + ". Cette fonctionnalité sera bientôt disponible.");
    }
    
    // Fonction pour masquer une annonce
    function hideAd(adId) {
        const card = document.querySelector(`.fb-post[data-ad-id="${adId}"]`);
        if (card) {
            card.style.display = 'none';
        }
    }

    // =========================================
    // POPUP DÉTAIL PUBLICATION
    // =========================================
    let currentPopupAdId = null;

    function openAdDetail(el) {
        const overlay = document.getElementById('adDetailOverlay');
        if (!overlay) return;

        let ad;
        const jsonAttr = el.getAttribute('data-ad-json');
        if (jsonAttr) {
            try {
                const decoded = jsonAttr.replace(/&quot;/g, '"').replace(/&#39;/g, "'");
                ad = JSON.parse(decoded);
            } catch(e) {
                console.error('Erreur parsing JSON ad:', e);
                return;
            }
        } else {
            return;
        }

        currentPopupAdId = ad.id;

        // Photos
        const photosEl = document.getElementById('adDetailPhotos');
        let photos = [];
        if (ad.photos) {
            if (Array.isArray(ad.photos)) photos = ad.photos;
            else if (typeof ad.photos === 'string') {
                try { photos = JSON.parse(ad.photos); } catch { photos = [ad.photos]; }
            }
        }
        photos = photos.filter(p => p);
        if (photos.length > 0) {
            photosEl.innerHTML = photos.map(p => {
                const url = buildPhotoUrl(p);
                return `<img src="${url}" alt="" onclick="openPhotoLightbox('${url}', '${(ad.title||'').replace(/'/g, "\\'")}')"
                         style="cursor:pointer; max-height:400px; width:100%; object-fit:cover; border-radius:8px; margin-bottom:6px;">`;
            }).join('');
            photosEl.style.display = 'block';
        } else {
            photosEl.style.display = 'none';
            photosEl.innerHTML = '';
        }

        // User
        const userEl = document.getElementById('adDetailUser');
        const avatarHtml = ad.user?.avatar
            ? `<img src="/storage/${ad.user.avatar}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">`
            : `<div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#a78bfa);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;">${(ad.user?.name||'U').charAt(0).toUpperCase()}</div>`;
        const verifiedIcon = ad.user?.is_verified
            ? '<i class="fas fa-check-circle" style="color:#10b981;font-size:0.7rem;margin-left:4px;"></i>'
            : '';
        userEl.innerHTML = `
            <div style="display:flex;align-items:center;gap:10px;">
                <a href="/user/${ad.user_id}">${avatarHtml}</a>
                <div>
                    <a href="/user/${ad.user_id}" class="text-decoration-none" style="font-weight:600;color:#1e293b;">${ad.user?.name||'Utilisateur'}${verifiedIcon}</a>
                    <div style="font-size:0.8rem;color:#94a3b8;">${ad.created_at_human||'Récemment'} · <i class="fas fa-map-marker-alt me-1"></i>${ad.location||'France'}</div>
                </div>
            </div>
        `;

        // Title
        document.getElementById('adDetailTitle').textContent = ad.title || '';

        // Badges
        const badgesEl = document.getElementById('adDetailBadges');
        let badgesHtml = `<span class="mission-badge mission-badge-category">${ad.category||''}</span>`;
        if (ad.is_urgent) badgesHtml += '<span class="mission-badge mission-badge-urgent"><i class="fas fa-bolt me-1"></i>Urgent</span>';
        if (ad.reply_restriction === 'pro_only') badgesHtml += '<span class="mission-badge" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-briefcase me-1"></i>PRO uniquement</span>';
        if (ad.reply_restriction === 'verified_only') badgesHtml += '<span class="mission-badge" style="background:#d1fae5;color:#059669;"><i class="fas fa-check-circle me-1"></i>Vérifiés uniquement</span>';
        badgesEl.innerHTML = badgesHtml;

        // Price
        document.getElementById('adDetailPrice').textContent = ad.price
            ? new Intl.NumberFormat('fr-FR').format(ad.price) + ' €'
            : 'Prix à discuter';

        // Description
        document.getElementById('adDetailDescription').textContent = ad.description || '';

        // Actions
        const actionsEl = document.getElementById('adDetailActions');
        actionsEl.innerHTML = `
            <button class="mission-action-btn" id="popup-like-btn" onclick="event.stopPropagation(); toggleLike(${ad.id})">
                <i class="far fa-heart"></i> <span>J'aime</span>
            </button>
            <div class="share-wrapper">
                <button class="mission-action-btn" onclick="event.stopPropagation(); toggleShareMenu(${ad.id}, this)">
                    <i class="fas fa-share"></i> <span>${ad.shares_count||0} Partager</span>
                </button>
                <div class="share-menu" id="share-menu-${ad.id}">
                    <div class="share-menu-header">Partager</div>
                    <div class="share-option copy" onclick="copyLink(${ad.id})"><i class="fas fa-link"></i><span>Copier le lien</span></div>
                    <div class="share-option facebook" onclick="shareTo('facebook', ${ad.id})"><i class="fab fa-facebook-f"></i><span>Facebook</span></div>
                    <div class="share-option whatsapp" onclick="shareTo('whatsapp', ${ad.id}, '${(ad.title||'').replace(/'/g, "\\\'")}')">
                        <i class="fab fa-whatsapp"></i><span>WhatsApp</span>
                    </div>
                </div>
            </div>
            <a href="/ads/${ad.id}" class="mission-action-btn" style="text-decoration:none;">
                <i class="fas fa-external-link-alt"></i> <span>Page complète</span>
            </a>
        `;

        // Candidature section: reset and show/hide based on ownership
        const candidatureDiv = document.getElementById('adDetailCandidature');
        if (candidatureDiv) {
            const isOwnAd = ad.user_id == <?php echo e(Auth::id() ?? 'null'); ?>;
            if (isOwnAd) {
                candidatureDiv.style.display = 'none';
            } else {
                candidatureDiv.style.display = 'block';
                const collapsed = document.getElementById('candidatureCollapsed');
                const form = document.getElementById('candidatureForm');
                if (collapsed) collapsed.style.display = 'block';
                if (form) form.style.display = 'none';
                const msgInput = document.getElementById('candidatureMessage');
                if (msgInput) msgInput.value = '';
                // Reset collapsed button content
                if (collapsed) {
                    collapsed.innerHTML = `<button type="button" class="btn-candidature" onclick="toggleCandidatureForm()">
                        <i class="fas fa-hand-paper"></i> Je suis intéressé(e) — Envoyer ma candidature
                    </button>`;
                }
            }
        }

        // Load comments
        loadPopupComments(ad.id);

        // Show overlay
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeAdDetail() {
        const overlay = document.getElementById('adDetailOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        currentPopupAdId = null;
    }

    // Fermer avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('adDetailOverlay')?.classList.contains('active')) {
            closeAdDetail();
        }
    });

    async function loadPopupComments(adId) {
        const commentsList = document.getElementById('adDetailCommentsList');
        const titleEl = document.getElementById('adDetailCommentsTitle');
        if (!commentsList) return;

        commentsList.innerHTML = '<div style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';

        try {
            const response = await fetch(`/ads/${adId}/comments`, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();

            if (titleEl) titleEl.textContent = `Commentaires (${data.total || 0})`;

            if (data.success && data.comments && data.comments.length > 0) {
                let html = '';
                data.comments.forEach(comment => {
                    const avatarHtml = comment.user.avatar
                        ? `<img src="${comment.user.avatar}" alt="${comment.user.name}">`
                        : `<div class="comment-avatar-placeholder-inline">${comment.user.initial}</div>`;
                    const deleteBtn = comment.user.id == <?php echo e(Auth::id() ?? 'null'); ?>

                        ? `<button class="comment-action-btn" onclick="deletePopupComment(${comment.id}, ${adId})"><i class="fas fa-trash-alt"></i></button>`
                        : '';
                    html += `
                        <div class="comment-item-inline" data-comment-id="${comment.id}">
                            <div class="comment-avatar-inline">${avatarHtml}</div>
                            <div class="comment-content-inline">
                                <div class="comment-bubble">
                                    <a href="/profile/${comment.user.id}" class="comment-author-inline">${comment.user.name}</a>
                                    <p class="comment-text-inline">${escapeHtml(comment.content)}</p>
                                </div>
                                <div class="comment-actions-inline">
                                    <span class="comment-time-inline">${comment.created_at}</span>
                                    ${deleteBtn}
                                </div>
                            </div>
                        </div>
                    `;
                });
                commentsList.innerHTML = html;
            } else {
                commentsList.innerHTML = '<div class="no-comments-inline"><i class="far fa-comment-dots"></i> Aucun commentaire. Soyez le premier !</div>';
            }
        } catch (error) {
            console.error('Erreur chargement commentaires popup:', error);
            commentsList.innerHTML = '<div class="no-comments-inline">Erreur de chargement</div>';
        }
    }

    async function submitPopupComment() {
        if (!currentPopupAdId) return;
        const input = document.getElementById('adDetailCommentInput');
        const content = input?.value?.trim();
        if (!content) return;

        try {
            const response = await fetch(`/ads/${currentPopupAdId}/comments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ content })
            });
            if (!response.ok) {
                if (response.status === 401) { showToast('Connectez-vous pour commenter', 'error'); return; }
                showToast('Erreur lors de l\'ajout', 'error'); return;
            }
            const data = await response.json();
            if (data.success) {
                input.value = '';
                showToast('Commentaire ajouté !', 'success');
                loadPopupComments(currentPopupAdId);
            }
        } catch (error) {
            console.error('Erreur:', error);
            showToast('Erreur', 'error');
        }
    }

    async function deletePopupComment(commentId, adId) {
        if (!confirm('Supprimer ce commentaire ?')) return;
        try {
            const response = await fetch(`/comments/${commentId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' }
            });
            if (response.ok) {
                showToast('Commentaire supprimé', 'success');
                loadPopupComments(adId);
            }
        } catch (error) { console.error(error); }
    }

    // Ouvrir popup depuis le sidebar
    function openSidebarAdDetail(adData) {
        // Créer un faux élément avec data-ad-json pour réutiliser openAdDetail
        const fakeEl = document.createElement('div');
        fakeEl.setAttribute('data-ad-json', JSON.stringify(adData).replace(/"/g, '&quot;').replace(/'/g, '&#39;'));
        openAdDetail(fakeEl);
    }

    // =========================================
    // POPUP DEPUIS CAROUSEL / HIGHLIGHTED ADS
    // =========================================
    async function openAdDetailPopup(adId) {
        // Chercher d'abord dans le DOM (depuis les cartes du feed)
        const existingEl = document.querySelector(`.fb-post[data-ad-id="${adId}"]`);
        if (existingEl) {
            openAdDetail(existingEl);
            return;
        }

        // Sinon charger via API
        try {
            const resp = await fetch(`/ads/${adId}`, { headers: { 'Accept': 'application/json' } });
            if (!resp.ok) {
                window.location.href = `/ads/${adId}`;
                return;
            }
            const data = await resp.json();
            const ad = data.ad || data;
            const fakeEl = document.createElement('div');
            fakeEl.setAttribute('data-ad-json', JSON.stringify({
                id: ad.id, title: ad.title, description: ad.description,
                category: ad.category, price: ad.price, location: ad.location,
                photos: ad.photos || [], is_urgent: !!ad.is_urgent,
                reply_restriction: ad.reply_restriction || 'everyone',
                visibility: ad.visibility || 'public',
                created_at_human: ad.created_at_human || ad.created_at || 'Récemment',
                user_id: ad.user_id, comments_count: ad.comments_count || 0,
                shares_count: ad.shares_count || 0,
                user: ad.user || null,
            }).replace(/"/g, '&quot;').replace(/'/g, '&#39;'));
            openAdDetail(fakeEl);
        } catch (e) {
            console.error('openAdDetailPopup error:', e);
            window.location.href = `/ads/${adId}`;
        }
    }

    // =========================================
    // CANDIDATURE FUNCTIONS
    // =========================================
    function toggleCandidatureForm() {
        const collapsed = document.getElementById('candidatureCollapsed');
        const form = document.getElementById('candidatureForm');
        if (!collapsed || !form) return;
        if (form.style.display === 'none') {
            form.style.display = 'block';
            collapsed.style.display = 'none';
        } else {
            form.style.display = 'none';
            collapsed.style.display = 'block';
        }
    }

    async function submitCandidature() {
        if (!currentPopupAdId) return;
        const btn = document.getElementById('btnSendCandidature');
        const msgInput = document.getElementById('candidatureMessage');
        const message = msgInput?.value?.trim() || '';

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';

        try {
            const response = await fetch(`/ads/${currentPopupAdId}/candidature`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: message })
            });
            const data = await response.json();
            if (data.success) {
                showToast(data.message || 'Candidature envoyée !', 'success');
                // Remplacer le formulaire par un message de confirmation
                const candidatureDiv = document.getElementById('adDetailCandidature');
                if (candidatureDiv) {
                    candidatureDiv.innerHTML = `
                        <div style="padding:14px;background:#ecfdf5;border:1px solid #86efac;border-radius:10px;text-align:center;">
                            <i class="fas fa-check-circle" style="color:#059669;font-size:1.2rem;margin-bottom:4px;"></i>
                            <div style="font-weight:600;color:#065f46;font-size:0.9rem;">Candidature envoyée !</div>
                            <div style="font-size:0.78rem;color:#047857;">L'annonceur a été notifié et recevra un e-mail.</div>
                        </div>
                    `;
                }
            } else {
                showToast(data.message || 'Erreur lors de l\'envoi', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer';
            }
        } catch (error) {
            console.error('submitCandidature error:', error);
            showToast('Erreur lors de l\'envoi de la candidature', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer';
        }
    }

    // =========================================
    // GEOLOCATION FUNCTIONS
    // =========================================

    /**
     * Change le rayon de recherche géo
     */
    function changeGeoRadius(radius) {
        currentFilters.radius = parseInt(radius);
        
        // Sauvegarder côté serveur
        fetch('<?php echo e(route("feed.update-radius")); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ radius: parseInt(radius) })
        }).catch(err => console.error('Erreur mise à jour rayon:', err));

        // Recharger les données
        loadData();
    }

    /**
     * Demander la géolocalisation précise du navigateur
     */
    function requestBrowserGeolocation() {
        const btn = document.getElementById('geoPreciseBtn');
        if (!btn) return;

        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Détection...';
        btn.disabled = true;

        if (!navigator.geolocation) {
            btn.innerHTML = '<i class="fas fa-crosshairs"></i> Préciser';
            btn.disabled = false;
            alert('Votre navigateur ne supporte pas la géolocalisation.');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                // Mettre à jour les filtres locaux
                currentFilters.lat = lat;
                currentFilters.lng = lng;
                currentFilters.geoEnabled = true;

                // Envoyer au serveur
                fetch('<?php echo e(route("feed.store-browser-location")); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ latitude: lat, longitude: lng })
                })
                .then(r => r.json())
                .then(data => {
                    // Mettre à jour le label ville
                    const cityLabel = document.getElementById('geoCityLabel');
                    if (cityLabel && data.city) {
                        cityLabel.textContent = data.city + (data.country ? ', ' + data.country : '');
                    }
                    // Mettre à jour le badge source
                    const badge = document.getElementById('geoSourceBadge');
                    if (badge) badge.textContent = 'GPS';

                    btn.innerHTML = '<i class="fas fa-check"></i> Localisé';
                    btn.disabled = false;

                    // Recharger les données avec la nouvelle position
                    loadData();
                })
                .catch(err => {
                    console.error('Erreur envoi position:', err);
                    btn.innerHTML = '<i class="fas fa-crosshairs"></i> Préciser';
                    btn.disabled = false;
                    // Recharger quand même avec les coords locales
                    loadData();
                });
            },
            function(error) {
                btn.innerHTML = '<i class="fas fa-crosshairs"></i> Préciser';
                btn.disabled = false;
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        alert('Vous avez refusé la géolocalisation. Activez-la dans les paramètres de votre navigateur.');
                        break;
                    case error.POSITION_UNAVAILABLE:
                        alert('Position indisponible. Vérifiez votre connexion.');
                        break;
                    default:
                        alert('Impossible de détecter votre position.');
                }
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
        );
    }

    /**
     * Désactiver le filtrage géographique
     */
    function disableGeoFiltering() {
        currentFilters.geoEnabled = false;
        currentFilters.lat = null;
        currentFilters.lng = null;

        // Masquer le banner
        const banner = document.getElementById('geoBanner');
        if (banner) banner.style.display = 'none';

        // Recharger sans filtre géo
        loadData();
    }

    /**
     * Réactiver le filtrage géographique
     */
    function enableGeoFiltering() {
        currentFilters.geoEnabled = true;
        currentFilters.lat = <?php echo e($userLat ?? 'null'); ?>;
        currentFilters.lng = <?php echo e($userLng ?? 'null'); ?>;
        currentFilters.radius = <?php echo e($userRadius ?? 50); ?>;

        const banner = document.getElementById('geoBanner');
        if (banner) banner.style.display = 'flex';

        loadData();
    }

    // Initialisation
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM chargé, initialisation des boutons...');
        
        // La vue par défaut est "missions"
        setViewMode('missions');

        // ===== INFINITE SCROLL =====
        let isLoadingMore = false;
        const scrollTrigger = document.getElementById('infiniteScrollTrigger');
        const spinner = document.getElementById('infiniteSpinner');
        let currentPage = parseInt(document.getElementById('currentPage')?.value || '1');
        const lastPage = parseInt(document.getElementById('lastPage')?.value || '1');

        if (scrollTrigger && currentPage < lastPage) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !isLoadingMore && currentPage < lastPage) {
                        loadMorePosts();
                    }
                });
            }, { rootMargin: '300px' });
            observer.observe(scrollTrigger);
        } else if (scrollTrigger && currentPage >= lastPage) {
            scrollTrigger.innerHTML = '<div class="infinite-scroll-end">Vous avez tout vu !</div>';
        }

        async function loadMorePosts() {
            isLoadingMore = true;
            currentPage++;
            if (spinner) spinner.style.display = 'inline-block';

            try {
                const params = new URLSearchParams();
                params.append('page', currentPage);
                if (currentFilters.category) params.append('category', currentFilters.category);
                if (currentFilters.subcategory) params.append('subcategory', currentFilters.subcategory);
                if (currentFilters.sort) params.append('sort', currentFilters.sort);
                if (currentFilters.city) params.append('location', currentFilters.city);
                else if (currentFilters.country) params.append('location', currentFilters.country);
                if (currentFilters.priceMin) params.append('price_min', currentFilters.priceMin);
                if (currentFilters.priceMax) params.append('price_max', currentFilters.priceMax);
                if (currentFilters.geoEnabled && currentFilters.lat && currentFilters.lng) {
                    params.append('lat', currentFilters.lat);
                    params.append('lng', currentFilters.lng);
                    params.append('radius', currentFilters.radius);
                }

                const response = await fetch(`<?php echo e(route('feed.filter-ads')); ?>?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                const ads = data.ads?.data || data.ads || [];

                if (ads && ads.length > 0) {
                    const grid = document.getElementById('missionsGrid');
                    ads.forEach(ad => {
                        const postEl = buildInfiniteScrollPost(ad);
                        if (postEl) grid.appendChild(postEl);
                    });
                }

                if (currentPage >= lastPage) {
                    if (scrollTrigger) scrollTrigger.innerHTML = '<div class="infinite-scroll-end">Vous avez tout vu !</div>';
                }
            } catch (err) {
                console.error('Erreur infinite scroll:', err);
                currentPage--;
            } finally {
                isLoadingMore = false;
                if (spinner && currentPage < lastPage) spinner.style.display = 'inline-block';
                else if (spinner) spinner.style.display = 'none';
            }
        }

        // Construire un élément post simplifié pour le scroll infini
        function buildInfiniteScrollPost(ad) {
            // Les publications urgentes s'affichent uniquement dans le carousel horizontal
            if (ad.is_urgent) return null;

            const div = document.createElement('div');
            div.className = 'fb-post' + (ad.is_boosted && ad.boost_end && new Date(ad.boost_end) > new Date() ? ' boosted-post' : '');
            div.setAttribute('data-ad-id', ad.id);
            try { div.setAttribute('data-ad-json', JSON.stringify(ad)); } catch(e) {}

            const userAvatar = ad.user && ad.user.avatar
                ? `<img src="/storage/${ad.user.avatar}" alt="">`
                : `<div class="fb-post-avatar-placeholder">${(ad.user?.name || 'U').charAt(0).toUpperCase()}</div>`;

            let photos = [];
            if (ad.photos) {
                if (Array.isArray(ad.photos)) photos = ad.photos;
                else if (typeof ad.photos === 'string') { try { photos = JSON.parse(ad.photos); } catch(e) { photos = [ad.photos]; } }
            }
            photos = photos.filter(p => p).map(p => buildPhotoUrl(p));

            let photosHtml = '';
            if (photos.length === 1) {
                photosHtml = `<div class="fb-post-photos single" onclick="openAdDetail(this.closest('.fb-post'))">
                    <img src="${photos[0]}" alt="" onerror="this.parentElement.style.display='none';">
                    ${ad.price ? `<div class="price-overlay-badge">${new Intl.NumberFormat('fr-FR').format(ad.price)} €</div>` : ''}
                </div>`;
            } else if (photos.length >= 2) {
                const cls = photos.length === 2 ? 'two' : 'three-plus';
                photosHtml = `<div class="fb-post-photos multi ${cls}" onclick="openAdDetail(this.closest('.fb-post'))">`;
                photos.slice(0, 4).forEach((url, i) => {
                    photosHtml += `<div class="fb-photo-item"><img src="${url}" alt="" onerror="this.parentElement.style.display='none';">`;
                    if (i === 3 && photos.length > 4) photosHtml += `<div class="fb-photo-more-overlay">+${photos.length - 4}</div>`;
                    photosHtml += `</div>`;
                });
                if (ad.price) photosHtml += `<div class="price-overlay-badge">${new Intl.NumberFormat('fr-FR').format(ad.price)} €</div>`;
                photosHtml += `</div>`;
            }

            const priceTag = ad.price
                ? `<span class="fb-post-tag price"><i class="fas fa-tag"></i> ${new Intl.NumberFormat('fr-FR').format(ad.price)} €</span>`
                : `<span class="fb-post-tag"><i class="fas fa-tag"></i> À discuter</span>`;
            const catTag = ad.category ? `<span class="fb-post-tag"><i class="fas fa-folder"></i> ${ad.category}</span>` : '';
            const urgentMeta = ad.is_urgent ? `<span>·</span><span style="color:#dc2626;font-weight:600;"><i class="fas fa-bolt"></i> Urgent</span>` : '';
            const boostMeta = (ad.is_boosted && ad.boost_end && new Date(ad.boost_end) > new Date()) ? `<span class="fb-post-sponsored-tag boost"><i class="fas fa-rocket"></i> Sponsorisé</span>` : '';

            div.innerHTML = `
                <div class="fb-post-header">
                    <a href="/profile/${ad.user?.id || ''}" class="fb-post-avatar">${userAvatar}</a>
                    <div class="fb-post-header-info">
                        <a href="/profile/${ad.user?.id || ''}" class="fb-post-author">${ad.user?.name || 'Utilisateur'}</a>
                        <div class="fb-post-meta"><span>${ad.created_at_human || 'Récemment'}</span><span>·</span><span>${(ad.location || 'France').substring(0, 25)}</span>${urgentMeta}${boostMeta}</div>
                    </div>
                    <div class="fb-post-options">
                        <button class="fb-post-options-btn" onclick="event.stopPropagation(); togglePostMenu(this)" title="Plus d'options"><i class="fas fa-ellipsis-h"></i></button>
                        <div class="fb-post-options-menu">
                            <button class="fb-post-menu-item" onclick="event.stopPropagation(); window.location.href='/profile/'+(ad.user?.id||ad.user_id||'')"><i class="fas fa-user"></i><span class="menu-item-text">Afficher le profil<small>Voir le profil de l'annonceur</small></span></button>
                            <button class="fb-post-menu-item" onclick="event.stopPropagation(); savePost(${ad.id}, this)"><i class="far fa-bookmark"></i><span class="menu-item-text">Enregistrer la publication<small>Ajouter à vos éléments enregistrés</small></span></button>
                            <button class="fb-post-menu-item" onclick="event.stopPropagation(); copyPostLink(${ad.id})"><i class="fas fa-link"></i><span class="menu-item-text">Copier le lien</span></button>
                            <div class="fb-post-menu-divider"></div>
                            <button class="fb-post-menu-item" onclick="event.stopPropagation(); hidePost(${ad.id}, this)"><i class="fas fa-eye-slash"></i><span class="menu-item-text">Masquer la publication<small>Voir moins de publications de ce type</small></span></button>
                            <div class="fb-post-menu-divider"></div>
                            <button class="fb-post-menu-item danger" onclick="event.stopPropagation(); reportPost(${ad.id})"><i class="fas fa-flag"></i><span class="menu-item-text">Signaler la publication<small>Cette publication pose problème</small></span></button>
                        </div>
                    </div>
                </div>
                <div class="fb-post-body">
                    <div class="fb-post-title">${ad.title || ''}</div>
                    <div class="fb-post-text">${(ad.description || '').substring(0, 300)}${(ad.description||'').length > 300 ? '...' : ''}</div>
                    <div class="fb-post-tags">${priceTag}${catTag}</div>
                </div>
                ${photosHtml}
                <div class="fb-post-reactions-bar">
                    <div class="reactions-left"><span>👍</span> <span class="likes-count-${ad.id}">0</span></div>
                    <div class="reactions-right">
                        <span onclick="toggleComments(${ad.id})"><span class="comments-count-${ad.id}">${ad.comments_count || 0}</span> commentaires</span>
                        <span>${ad.shares_count || 0} partages</span>
                    </div>
                </div>
                <div class="fb-post-actions">
                    <button class="fb-action-btn" onclick="toggleLike(${ad.id}, this)"><i class="far fa-thumbs-up"></i> J'aime</button>
                    <button class="fb-action-btn" onclick="toggleComments(${ad.id})"><i class="far fa-comment"></i> Commenter</button>
                    <button class="fb-action-btn" onclick="sharePost(${ad.id})"><i class="fas fa-share"></i> Partager</button>
                    <button class="fb-action-btn contact-btn" onclick="window.location.href='/ads/${ad.id}'"><i class="fas fa-envelope"></i> Contacter</button>
                </div>
                <div class="fb-post-comments" id="comments-section-${ad.id}" style="display: none;">
                    <div class="fb-comments-list" id="comments-list-${ad.id}">
                        <div class="no-comments-msg" id="no-comments-${ad.id}"><i class="far fa-comment-dots"></i> Soyez le premier à commenter</div>
                    </div>
                </div>
            `;
            return div;
        }

        // Auto-demander la géolocalisation navigateur si source IP ou défaut
        <?php if(($geoEnabled ?? false) && in_array($geoSource ?? '', ['ip', 'default'])): ?>
        if (navigator.geolocation && !sessionStorage.getItem('geo_asked')) {
            sessionStorage.setItem('geo_asked', '1');
            // Demander discrètement en arrière-plan
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    currentFilters.lat = pos.coords.latitude;
                    currentFilters.lng = pos.coords.longitude;
                    // Envoyer au serveur silencieusement
                    fetch('<?php echo e(route("feed.store-browser-location")); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ latitude: pos.coords.latitude, longitude: pos.coords.longitude })
                    })
                    .then(r => r.json())
                    .then(data => {
                        const cityLabel = document.getElementById('geoCityLabel');
                        if (cityLabel && data.city) {
                            cityLabel.textContent = data.city + (data.country ? ', ' + data.country : '');
                        }
                        const badge = document.getElementById('geoSourceBadge');
                        if (badge) badge.textContent = 'GPS';
                        loadData();
                    })
                    .catch(() => {});
                },
                function() { /* silently fail */ },
                { enableHighAccuracy: false, timeout: 5000, maximumAge: 600000 }
            );
        }
        <?php endif; ?>
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\PC\Desktop\MASSIWANI V2\resources\views/feed/index.blade.php ENDPATH**/ ?>
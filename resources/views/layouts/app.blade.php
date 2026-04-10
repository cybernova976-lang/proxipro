<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'ProxiPro'))</title>

    <!-- Open Graph / Social Sharing Meta Tags -->
    @stack('meta')

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Inter:400,500,600,700,800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* =========================================
       HEADER NAVIGATION BUTTONS
       Boutons de navigation dans le header
       ========================================= */
    .header-nav-buttons {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .header-nav-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        color: #212529;
        background: transparent;
        border: 1px solid transparent;
    }

    .header-nav-btn:hover {
        background: #e9ecef;
        color: #212529;
        text-decoration: none;
    }

    .header-nav-btn.active {
        background: rgba(58, 134, 255, 0.1);
        color: #3a86ff;
        border-color: rgba(58, 134, 255, 0.2);
    }

    .header-nav-btn i {
        font-size: 0.9rem;
    }

    .header-nav-btn-primary {
        background: linear-gradient(135deg, #3a86ff, #2667cc);
        color: white !important;
        border: none;
        box-shadow: 0 2px 8px rgba(58, 134, 255, 0.3);
    }

    .header-nav-btn-primary:hover {
        background: linear-gradient(135deg, #2667cc, #1a4a99);
        color: white !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(58, 134, 255, 0.4);
    }

    .header-nav-btn-warning {
        color: #ea580c;
        background: #fff7ed;
        border: 1px solid #fed7aa;
    }

    .header-nav-btn-warning:hover {
        background: #ffedd5;
        color: #c2410c;
    }

    .header-nav-badge {
        background: #ea580c;
        color: white;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 10px;
        min-width: 18px;
        text-align: center;
    }

    @media (max-width: 991px) {
        .header-nav-buttons {
            display: none;
        }
    }

    /* Séparateur vertical header */
    .header-separator {
        width: 1px;
        height: 28px;
        background: #e2e8f0;
        flex-shrink: 0;
    }

    /* =========================================
       MOBILE RESPONSIVE — HEADER
       ========================================= */
    @media (max-width: 768px) {
        .header-modern {
            height: 64px !important;
            min-height: 64px !important;
        }
        .header-modern .container-fluid {
            padding: 0 10px 0 0 !important;
        }
        .navbar-brand-modern {
            margin-left: -2px !important;
            padding-left: 0 !important;
        }
        .header-home-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 8px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #334155;
            text-decoration: none;
            font-size: 0.74rem;
            font-weight: 600;
        }
        .header-modern .navbar-brand-modern .brand-logo {
            width: 34px;
            height: 34px;
            font-size: 1rem;
        }
        .header-modern .navbar-brand-modern .brand-text {
            font-size: 1.1rem;
        }
        .header-nav-btn {
            padding: 6px 10px;
            font-size: 0.8rem;
            gap: 4px;
        }
        .nav-messages-btn {
            padding: 6px 10px;
            font-size: 0.8rem;
            gap: 6px;
        }
        .header-points-badge {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        .header-notif-btn,
        .header-alert-btn {
            width: 34px;
            height: 34px;
        }
        .header-separator {
            height: 22px;
        }
        .user-menu-btn {
            padding: 3px 10px 3px 3px;
        }
    }

    @media (max-width: 576px) {
        .header-modern {
            height: 56px !important;
            min-height: 56px !important;
        }
        .header-modern .container-fluid {
            padding: 0 8px 0 0 !important;
        }
        .mobile-brand-group {
            gap: 10px !important;
            min-width: 0;
        }
        .mobile-actions-group {
            gap: 8px !important;
        }
        .nav-messages-btn span {
            display: none !important;
        }
        .nav-messages-btn {
            padding: 8px;
            border-radius: 10px;
        }
        .header-points-badge {
            padding: 5px 8px;
            font-size: 0.75rem;
        }
        .header-points-value {
            font-size: 0.8rem;
        }
        .header-separator {
            display: none !important;
        }
        .user-menu-btn {
            padding: 2px 6px 2px 2px;
            gap: 6px;
        }
        .user-name-text {
            font-size: 0.75rem;
        }
        .user-type-badge {
            font-size: 0.6rem;
            padding: 1px 6px;
        }
        .btn-become-provider,
        .btn-provider-badge {
            padding: 6px 8px !important;
            font-size: 0.75rem !important;
        }
        .mobile-header-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }
        .header-mobile-action {
            min-width: 40px;
            height: 36px;
            display: flex !important;
            align-items: center;
            justify-content: center;
            padding: 4px 6px !important;
        }
        .header-mobile-action i {
            font-size: 0.95rem;
            line-height: 1;
        }
        .mobile-header-label {
            font-size: 0.58rem;
            line-height: 1;
            font-weight: 600;
            color: #64748b;
        }
        .header-home-link,
        .header-mobile-action {
            border: 1px solid #e2e8f0 !important;
            background: #f8fafc !important;
            color: #334155 !important;
            border-radius: 12px !important;
            box-shadow: none !important;
        }
        .header-home-link {
            min-height: 36px;
            padding: 0 10px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .header-mobile-action {
            width: 40px;
            min-width: 40px;
            height: 36px;
            padding: 0 !important;
        }
        .header-mobile-action i {
            font-size: 0.95rem;
            color: #334155 !important;
        }
        .mobile-header-item {
            min-width: 40px;
        }
        .mobile-header-label {
            margin-top: 1px;
            text-align: center;
        }
        .header-home-link {
            padding: 6px 7px;
            font-size: 0.7rem;
            gap: 3px;
            margin-left: 0;
            flex-shrink: 0;
        }
        .dropdown-menu-modern {
            min-width: 220px !important;
            max-width: calc(100vw - 16px);
        }
        .notif-dropdown-menu {
            width: calc(100vw - 16px) !important;
            max-width: 380px;
        }
    }

    @media (max-width: 420px) {
        .header-points-badge .header-points-value {
            display: none;
        }
        .header-points-badge {
            padding: 6px;
            border-radius: 10px;
        }
    }
    </style>
    <style>

    /* =========================================
       PAGE TEST - ARCHITECTURE MODERNE
       Style ServicesPro
       ========================================= */
    
    :root {
        /* ðŸŽ¨ Palette ServicesPro */
        --primary: #3a86ff;
        --primary-dark: #2667cc;
        --primary-hover: #2667cc;
        --primary-light: rgba(58, 134, 255, 0.1);
        --secondary: #8338ec;
        --accent: #ffbe0b;
        --success: #06d6a0;
        --warning: #ffbe0b;
        --danger: #ef4444;
        
        /* Fonds - Style ServicesPro */
        --light: #f8f9fa;
        --dark: #212529;
        --gray: #6c757d;
        --light-gray: #e9ecef;
        --bg-main: #f5f7fb;
        --bg-body: #f5f7fb;
        --bg-card: #ffffff;
        
        /* Texte */
        --text-main: #212529;
        --text-dark: #212529;
        --text-muted: #6c757d;
        
        /* Bordures */
        --border: #e9ecef;
        --border-subtle: #e9ecef;
        --border-color: #e9ecef;
        --border-radius: 12px;
        
        /* Ombres - Style ServicesPro */
        --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        --shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.12);
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        
        /* Rayons */
        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 16px;
        --radius-xl: 24px;
        
        --transition: all 0.3s ease;
    }

    body {
        background-color: #f5f7fb;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        color: var(--dark);
        line-height: 1.6;
    }

    /* Header height override for test page */
    .header-modern {
        height: 90px !important;
        min-height: 90px !important;
    }

    /* =========================================
       HEADER PRINCIPAL - Style amÃ©liorÃ©
       Plus large avec effet d'ombre sÃ©duisant
       ========================================= */
    .header-modern {
        background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%) !important;
        box-shadow: 0 2px 20px -4px rgba(58, 134, 255, 0.15), 0 4px 12px -2px rgba(0, 0, 0, 0.08) !important;
        border-bottom: 1px solid rgba(58, 134, 255, 0.1) !important;
        height: 90px !important;
        z-index: 1001 !important;
    }

    .header-modern .container-fluid {
        max-width: 1600px !important;
        margin: 0 auto;
        padding: 0 32px !important;
    }

    .header-modern .navbar-brand-modern .brand-logo {
        width: 42px;
        height: 42px;
        font-size: 1.2rem;
        box-shadow: 0 4px 15px rgba(58, 134, 255, 0.35);
    }

    .header-modern .navbar-brand-modern .brand-text {
        font-size: 1.4rem;
    }

    /* Header search (test page) */
    .header-search-inline {
        flex: 1;
        display: flex;
        justify-content: center;
    }
    .header-search-inline .search-card {
        display: flex;
        align-items: center;
        gap: 10px;
        background: white;
        border: 1px solid var(--border-subtle);
        border-radius: 14px;
        padding: 8px 10px;
        box-shadow: var(--shadow-sm);
        max-width: 680px; /* Ã‰largi de 520px Ã  680px */
        width: 100%;
    }
    .header-search-inline .search-input-group {
        position: relative;
        flex: 1 1 0;
        min-width: 0;
        border-radius: 12px;
    }
    .header-search-inline .search-input-group input {
        width: 100%;
        border: none;
        background: transparent;
        padding: 10px 12px 10px 36px;
        font-size: 0.9rem;
        color: var(--text-main);
        border-radius: 12px;
    }
    .header-search-inline .search-input-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.95rem;
        pointer-events: none;
    }
    .header-search-inline .search-divider {
        width: 1px;
        height: 22px;
        background: var(--border-subtle);
        margin: 0 4px;
    }
    .header-search-inline .search-submit-btn {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 10px;
        border: none;
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        font-weight: 600;
        font-size: 0.85rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .header-search-inline .search-submit-btn:hover {
        transform: translateY(-1px);
    }
    .header-search-inline .search-submit-btn i {
        font-size: 0.85rem;
    }
    @media (max-width: 991px) {
        .header-search-inline {
            order: 3;
            width: 100%;
            margin-top: 10px;
        }
        .header-search-inline .search-card {
            max-width: none;
        }
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* =========================================
       HEADER - Style ServicesPro
       ========================================= */
    .sp-header {
        background-color: white;
        box-shadow: var(--shadow);
        position: sticky;
        top: 0;
        z-index: 1000;
        padding: 12px 0;
    }

    .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
    }

    .logo {
        font-size: 28px;
        font-weight: 700;
        color: var(--primary);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .logo i {
        color: var(--secondary);
    }

    /* Styles boutons "header fixe du dessus" */
    .nav-buttons-container {
        display: flex;
        align-items: center;
        gap: 4px;
        flex: 1;
        justify-content: center;
    }

    .nav-link-modern {
        font-weight: 500;
        font-size: 0.875rem;
        color: var(--text-muted);
        text-decoration: none;
        padding: 8px 14px;
        border-radius: 10px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .nav-link-modern:hover {
        color: var(--text-dark);
        background: rgba(58, 134, 255, 0.05); /* Utilisation de var(--primary) avec opacitÃ© */
    }
    
    .nav-link-modern.active {
        color: var(--primary);
        background: rgba(58, 134, 255, 0.1);
        font-weight: 600;
    }
    
    .nav-link-modern i {
        font-size: 0.9rem;
        opacity: 0.85;
    }

    .nav-link-lost {
        color: #ea580c !important;
        border: 1px solid #fed7aa;
        background: #fff7ed;
    }
    
    .nav-link-lost:hover {
        background: #ffedd5;
        border-color: #fdba74;
    }
    
    .nav-link-lost i {
        color: #ea580c;
    }
    
    .lost-badge {
        background: #ea580c;
        color: white;
        font-size: 0.65rem;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 4px;
        font-weight: 600;
    }

    /* Menu utilisateur header */
    .user-menu {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .notification-icon, .message-icon {
        position: relative;
        font-size: 20px;
        color: var(--gray);
        cursor: pointer;
        padding: 8px;
        transition: var(--transition);
    }

    .notification-icon:hover, .message-icon:hover {
        color: var(--primary);
    }

    .header-badge {
        position: absolute;
        top: 0;
        right: 0;
        background-color: var(--secondary);
        color: white;
        font-size: 12px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .user-avatar {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: var(--radius-md);
        transition: var(--transition);
        position: relative;
    }

    .user-avatar:hover {
        background-color: var(--light-gray);
    }

    .avatar-img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
        overflow: hidden;
    }

    .avatar-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .user-name {
        font-weight: 600;
        font-size: 16px;
    }

    .user-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background-color: white;
        box-shadow: var(--shadow-hover);
        border-radius: var(--radius-md);
        min-width: 200px;
        padding: 10px 0;
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: var(--transition);
        z-index: 100;
    }

    .user-avatar:hover .user-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .user-dropdown a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        text-decoration: none;
        color: var(--dark);
        transition: var(--transition);
    }

    .user-dropdown a:hover {
        background-color: var(--light-gray);
        color: var(--primary);
    }

    /* =========================================
       NAVIGATION PRINCIPALE (Sous-header)
       CollÃ© au header principal avec design moderne
       ========================================= */
    .main-nav {
        background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
        border-top: none;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        position: sticky;
        top: 90px;
        z-index: 998;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.08), 0 2px 8px -2px rgba(0, 0, 0, 0.04);
    }

    /* Container flex pour aligner le badge et le menu */
    .main-nav .container {
        display: flex;
        align-items: center;
        justify-content: center;
        max-width: 1600px; /* Plus large */
        padding: 0 24px;
    }

    .nav-links {
        display: flex;
        list-style: none;
        justify-content: center;
        gap: 5px;
        margin: 0;
        padding: 0;
        width: 100%;
    }

    .nav-item {
        position: relative;
    }

    .nav-link {
        display: block;
        padding: 16px 22px;
        text-decoration: none;
        color: var(--dark);
        font-weight: 600;
        font-size: 15px;
        transition: var(--transition);
        border-radius: 10px;
        margin: 4px 2px;
        position: relative;
    }

    .nav-link:hover {
        color: var(--primary);
        background: linear-gradient(135deg, rgba(58, 134, 255, 0.08) 0%, rgba(131, 56, 236, 0.05) 100%);
        transform: translateY(-1px);
    }

    .nav-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%) scaleX(0);
        width: 60%;
        height: 3px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border-radius: 3px 3px 0 0;
        transition: transform 0.3s ease;
    }

    .nav-link:hover::after {
        transform: translateX(-50%) scaleX(1);
    }

    /* Mega-menu */
    .mega-menu {
        position: absolute;
        left: -200px; /* DÃ©placÃ© davantage vers la gauche */
        top: 100%;
        width: 900px;
        max-width: calc(100vw - 40px);
        max-height: 450px;
        overflow-y: auto;
        background-color: white;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 8px 25px rgba(58, 134, 255, 0.1);
        border-radius: 0 var(--radius-md) var(--radius-md) var(--radius-md);
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 24px 28px;
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 20px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(15px);
        transition: var(--transition);
        z-index: 100;
    }

    /* Scrollbar personnalisÃ©e pour mega-menu */
    .mega-menu::-webkit-scrollbar {
        width: 6px;
    }
    .mega-menu::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }
    .mega-menu::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 10px;
    }

    .nav-item:hover .mega-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .nav-item-mission .mega-menu {
        left: 50%;
        transform: translate(-50%, 15px);
    }

    .nav-item-mission:hover .mega-menu {
        transform: translate(-50%, 0);
    }

    /* =========================================
       MEGA-MENU MISSION - Style Rectangulaire (Grid)
       ========================================= */
    .mega-menu-mission {
        position: absolute;
        left: 50%; /* CentrÃ© par rapport au parent nav-item-mission, mais voir ajustement global si besoin */
        transform: translateX(-35%) translateY(15px); /* DÃ©calÃ© lÃ©gÃ¨rement car le menu est large */
        top: 100%;
        width: 660px; /* RÃ©duit pour des boutons plus courts (stricte minimum) */
        background-color: #f8fafc; /* LÃ©gÃ¨rement grisÃ© pour contraste avec les cartes blanches */
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 8px 25px rgba(58, 134, 255, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 0;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: all 0.2s ease;
        z-index: 2000;
        overflow: visible !important;
    }

    .nav-item-mission:hover .mega-menu-mission {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateX(-35%) translateY(0);
    }

    .mega-menu-mission-header {
        display: none; 
    }

    .mega-menu-mission-body {
        padding: 16px; /* RÃ©duit de 24px */
        max-height: none;
        overflow: visible !important;
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* 3 colonnes */
        gap: 8px; /* RÃ©duit de 16px */
    }

    .mission-category {
        position: relative;
    }

    /* Style carte pour chaque catÃ©gorie */
    .mission-category-btn {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 8px 10px; /* RÃ©duit de 12px */
        background: white;
        border: 1px solid #f1f5f9;
        border-radius: 8px; /* LÃ©gÃ¨rement moins rond pour coller au compact */
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        height: 100%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }

    .mission-category-btn:hover {
        background: #f8fafc;
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(58, 134, 255, 0.15);
        transform: translateY(-2px);
    }

    .mission-category-left {
        display: flex;
        align-items: center;
        gap: 10px; /* RÃ©duit de 12px */
        flex: 1;
    }

    .mission-category-icon {
        width: 32px; /* RÃ©duit de 40px */
        height: 32px; /* RÃ©duit de 40px */
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem; /* RÃ©duit de 1.1rem */
        color: white;
        flex-shrink: 0;
    }

    .mission-category-info {
        flex: 1;
        overflow: hidden;
    }

    .mission-category-name {
        font-size: 0.9rem; /* RÃ©duit de 0.95rem */
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 0; /* RÃ©duit marge */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.2;
    }

    .mission-category-btn:hover .mission-category-name {
        color: var(--primary);
    }

    .mission-category-count {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .mission-category-arrow {
        display: none; /* Pas de flÃ¨che en mode grille pour plus de propretÃ© */
    }

    /* Sous-catÃ©gories (Popup) - Affichage Ã  DROITE */
    .mission-subcategories {
        position: absolute;
        left: 100%; /* PositionnÃ© Ã  droite de la catÃ©gorie */
        top: 0;
        margin-left: 4px; /* Ecart rÃ©duit entre la catÃ©gorie et le sous-menu */
        width: 280px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 0;
        opacity: 0;
        pointer-events: none;
        visibility: hidden;
        transition: all 0.2s ease;
        z-index: 2100;
        overflow: hidden;
    }

    .mission-category:hover .mission-subcategories {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        /* Peut ajouter un lÃ©ger slide horizontal si voulu */
    }

    /* Arrow tip pointing LEFT to the category */
    .mission-subcategories::before {
        content: '';
        position: absolute;
        top: 22px; /* AjustÃ© pour Ãªtre en face du centre visuel */
        left: -6px;
        transform: rotate(45deg);
        width: 12px;
        height: 12px;
        background: white;
        border-left: 1px solid rgba(0,0,0,0.08);
        border-bottom: 1px solid rgba(0,0,0,0.08);   
    }

    /* Zone "pont" pour ne pas perdre le hover lors du dÃ©placement vers la droite */
    .mission-category::after {
        content: '';
        position: absolute;
        right: -20px; /* Ã‰tend la zone active vers la droite */
        top: 0;
        width: 30px;
        height: 100%;
        background: transparent;
    }

    /* Style pour les sous-catÃ©gories qui remontent (vers le haut) */
    .mission-subcategories-up {
        top: auto !important;
        bottom: 0;
    }

    /* Ajustement de la flÃ¨che pour celles qui remontent */
    .mission-subcategories-up::before {
        top: auto;
        bottom: 22px; /* S'aligne avec le bouton en bas */
    }


    .mission-category:hover .mission-subcategories {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    /* Bridge invisible entre catÃ©gorie et sous-menu */
    .mission-category::after {
        content: '';
        position: absolute;
        right: -16px;
        top: 0;
        width: 20px;
        height: 100%;
        background: transparent;
    }

    .mission-subcategories-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 18px;
        background: linear-gradient(135deg, rgba(58, 134, 255, 0.08) 0%, rgba(131, 56, 236, 0.05) 100%);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .mission-subcategories-header-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        color: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .mission-subcategories-header h6 {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
    }

    .mission-subcategories-list {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding: 12px;
        max-height: 300px;
        overflow-y: auto;
    }

    .mission-subcat-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: white;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .mission-subcat-item:hover {
        background: linear-gradient(135deg, rgba(58, 134, 255, 0.06) 0%, rgba(131, 56, 236, 0.04) 100%);
        border-color: rgba(58, 134, 255, 0.2);
        transform: translateX(4px);
    }

    .mission-subcat-item-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .mission-subcat-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        box-shadow: 0 2px 6px rgba(58, 134, 255, 0.4);
    }

    .mission-subcat-name {
        font-size: 0.85rem;
        font-weight: 500;
        color: #4a5568;
    }

    .mission-subcat-item:hover .mission-subcat-name {
        color: var(--primary);
    }

    .mission-subcat-badge {
        min-width: 26px;
        height: 20px;
        padding: 0 6px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 0.7rem;
        font-weight: 700;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 3px 10px rgba(58, 134, 255, 0.3);
    }

    .mission-subcategories-footer {
        padding: 12px;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        background: rgba(248, 250, 252, 0.8);
    }

    .mission-subcategories-all {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(58, 134, 255, 0.3);
    }

    .mission-subcategories-all:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(58, 134, 255, 0.4);
        color: white;
    }

    .mega-menu-column h4 {
        font-size: 14px;
        margin-bottom: 10px;
        color: var(--primary);
        padding-bottom: 6px;
        border-bottom: 2px solid var(--light-gray);
        font-weight: 700;
    }

    .mega-menu-column a {
        display: flex;
        align-items: center;
        padding: 5px 0;
        text-decoration: none;
        color: var(--dark);
        transition: var(--transition);
        font-size: 0.85rem;
    }

    .mega-menu-group + .mega-menu-group {
        margin-top: 14px;
    }

    .mega-menu-column a:hover {
        color: var(--primary);
        padding-left: 6px;
    }

    .see-all {
        margin-top: 10px;
        font-weight: 600;
        color: var(--secondary) !important;
        font-size: 0.8rem !important;
    }

    /* Compteurs dans le mega-menu */
    .mega-menu-column .sub-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 16px;
        padding: 0 4px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 0.6rem;
        font-weight: 600;
        border-radius: 6px;
        margin-right: 5px;
    }

    .mega-menu-column .cat-total {
        font-size: 0.7rem;
        color: var(--text-muted);
        font-weight: 500;
    }

    .mega-menu-group {
        margin-bottom: 8px;
    }

    .mega-menu-group:last-child {
        margin-bottom: 0;
    }

    /* =========================================
       BADGE TEST (IntÃ©grÃ© dans la nav)
       ========================================= */
    .test-badge {
        background: var(--danger);
        color: white;
        padding: 4px 10px;
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-right: 15px;
    }

    .test-badge a {
        color: white;
        text-decoration: underline;
        margin-left: 4px;
        font-weight: 400;
    }

    /* =========================================
       MEGA-MENU DROPDOWN - Publier une offre
       Style identique Ã  la colonne gauche principale
       ========================================= */
    .nav-item-mission {
        position: relative;
    }

    .mega-dropdown-mission {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        top: 100%;
        width: 320px;
        background: white;
        border-radius: 18px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 8px 25px rgba(58, 134, 255, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 0;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 2000;
        margin-top: 10px;
        overflow: visible;
    }

    .nav-item-mission:hover .mega-dropdown-mission {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        margin-top: 0;
    }

    /* FlÃ¨che pointant vers le bouton */
    .mega-dropdown-mission::before {
        content: '';
        position: absolute;
        top: -8px;
        left: 50%;
        transform: translateX(-50%) rotate(45deg);
        width: 16px;
        height: 16px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary) 100%);
        border-left: 1px solid rgba(0, 0, 0, 0.08);
        border-top: 1px solid rgba(0, 0, 0, 0.08);
    }

    /* Bridge pour garder le menu ouvert */
    .nav-item-mission::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        top: 100%;
        height: 15px;
        background: transparent;
    }

    .mega-dropdown-mission-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-radius: 18px 18px 0 0;
    }

    .mega-dropdown-mission-header i {
        font-size: 1.2rem;
        color: white;
    }

    .mega-dropdown-mission-header h5 {
        color: white;
        font-weight: 700;
        margin: 0;
        font-size: 1rem;
    }

    .mega-dropdown-mission-body {
        padding: 8px;
        max-height: 400px;
        overflow-y: auto;
    }

    .mega-dropdown-mission-body::-webkit-scrollbar {
        width: 5px;
    }

    .mega-dropdown-mission-body::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }

    .mega-dropdown-mission-body::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 10px;
    }

    /* CatÃ©gorie principale */
    .mission-cat-item {
        position: relative;
        margin-bottom: 2px;
    }

    .mission-cat-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 10px 14px;
        background: transparent;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .mission-cat-btn:hover {
        background: rgba(58, 134, 255, 0.08);
    }

    .mission-cat-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .mission-cat-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        color: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .mission-cat-info {
        text-align: left;
    }

    .mission-cat-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 2px;
    }

    .mission-cat-btn:hover .mission-cat-name {
        color: var(--primary);
    }

    .mission-cat-count {
        font-size: 0.75rem;
        color: var(--text-muted);
        font-weight: 500;
    }

    .mission-cat-count strong {
        color: var(--primary);
    }

    .mission-cat-arrow {
        font-size: 0.7rem;
        color: var(--text-muted);
        transition: transform 0.2s ease;
    }

    .mission-cat-btn:hover .mission-cat-arrow {
        color: var(--primary);
        transform: translateX(3px);
    }

    /* Panneau sous-catÃ©gories */
    .mission-subcats-panel {
        position: absolute;
        left: 100%;
        top: 0;
        width: 260px;
        margin-left: 8px;
        background: white;
        border-radius: 14px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 0;
        opacity: 0;
        pointer-events: none;
        visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s ease;
        z-index: 2100;
        overflow: hidden;
    }

    .mission-cat-item:hover .mission-subcats-panel {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    /* Bridge invisible entre catÃ©gorie et panneau */
    .mission-cat-item::after {
        content: '';
        position: absolute;
        right: -12px;
        top: 0;
        width: 16px;
        height: 100%;
        background: transparent;
    }

    .mission-subcats-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        background: rgba(58, 134, 255, 0.05);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .mission-subcats-header-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        color: white;
    }

    .mission-subcats-header h6 {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0;
    }

    .mission-subcats-list {
        display: flex;
        flex-direction: column;
        gap: 3px;
        padding: 10px;
        max-height: 280px;
        overflow-y: auto;
    }

    .mission-subcat-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 9px 12px;
        background: #f8fafc;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .mission-subcat-link:hover {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transform: translateX(3px);
    }

    .mission-subcat-link-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .mission-subcat-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: var(--primary);
    }

    .mission-subcat-name {
        font-size: 0.8rem;
        font-weight: 500;
        color: #4a5568;
    }

    .mission-subcat-link:hover .mission-subcat-name {
        color: var(--primary);
    }

    .mission-subcat-badge {
        min-width: 22px;
        height: 18px;
        padding: 0 5px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 0.65rem;
        font-weight: 600;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .mission-subcats-footer {
        padding: 10px;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    .mission-subcats-all {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        width: 100%;
        padding: 9px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .mission-subcats-all:hover {
        box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
        color: white;
    }

    /* =========================================
       Responsive Header
       ========================================= */
    @media (max-width: 1200px) {
        .mega-menu {
            width: 750px;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            padding: 20px 24px;
        }
    }

    @media (max-width: 992px) {
        .nav-links {
            overflow-x: auto;
            justify-content: center;
            padding: 10px 0;
        }
        
        .mega-menu {
            width: 600px;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            padding: 18px 20px;
            max-height: 400px;
        }
    }

    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            gap: 15px;
        }
        
        .header-search-container {
            width: 100%;
            max-width: 100%;
        }
        
        .user-name {
            display: none;
        }
        
        .mega-menu {
            width: calc(100vw - 30px);
            grid-template-columns: repeat(2, 1fr);
            left: 50% !important;
            transform: translate(-50%, 0) !important;
            max-height: 350px;
            gap: 12px;
            padding: 15px;
        }

        .nav-item-mission .mega-menu {
            left: 50% !important;
        }
    }

    @media (max-width: 480px) {
        .mega-menu {
            grid-template-columns: 1fr;
            max-height: 320px;
        }
    }

    /* =========================================
       Suite des styles existants...
       ========================================= */

    /* =========================================
       MAIN LAYOUT - Grille principale
       ========================================= */
    .main-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px;
    }

    .page-grid {
        display: grid;
        grid-template-columns: 1fr 280px;
        gap: 24px;
        max-width: 1400px;
        margin: 0 auto;
    }

    @media (max-width: 1200px) {
        .page-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 900px) {
        .page-grid {
            grid-template-columns: 1fr;
        }
        .sidebar-right {
            display: none;
        }
    }

    /* =========================================
       SIDEBAR GAUCHE - Mega Menu Pros
       ========================================= */
    .sidebar-left {
        position: sticky;
        top: 140px;
        height: fit-content;
    }

    .mega-menu-card {
        background: white;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        overflow: hidden;
    }

    .mega-menu-header {
        padding: 16px 20px;
        background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%);
        color: white;
    }

    .mega-menu-header h3 {
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .mega-menu-list {
        padding: 8px 0;
    }

    .mega-item {
        position: relative;
    }

    .mega-item-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 20px;
        background: none;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        text-align: left;
    }

    .mega-item-btn:hover {
        background: var(--bg-main);
    }

    .mega-item-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .mega-item-icon {
        width: 36px;
        height: 36px;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.875rem;
    }

    .mega-item-info h4 {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0 0 2px 0;
    }

    .mega-item-info span {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .mega-item-arrow {
        color: var(--text-muted);
        font-size: 0.75rem;
        transition: transform 0.2s;
    }

    .mega-item:hover .mega-item-arrow {
        transform: translateX(3px);
        color: var(--primary);
    }

    /* Sous-menu au survol */
    .mega-submenu {
        display: none;
        position: absolute;
        left: 100%;
        top: 0;
        width: 280px;
        background: white;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-lg);
        padding: 12px 0;
        z-index: 1000;
        margin-left: 8px;
    }

    .mega-item:hover .mega-submenu {
        display: block;
    }

    .mega-sub-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        color: var(--text-dark);
        text-decoration: none;
        font-size: 0.875rem;
        transition: all 0.2s;
    }

    .mega-sub-link:hover {
        background: var(--primary-light);
        color: var(--primary);
    }

    .mega-sub-link i {
        margin-right: 10px;
        width: 16px;
        color: var(--text-muted);
    }

    .mega-sub-link:hover i {
        color: var(--primary);
    }

    .mega-sub-count {
        background: var(--bg-main);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    /* =========================================
       FEED CENTRAL - Contenu principal
       ========================================= */
    .feed-main {
        min-width: 0;
    }

    /* Onglets de filtrage */
    .feed-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        background: white;
        padding: 8px;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
    }

    .feed-tab {
        flex: 1;
        padding: 12px 16px;
        border: none;
        background: none;
        border-radius: var(--radius-md);
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        text-align: center;
    }

    .feed-tab:hover {
        background: var(--bg-main);
        color: var(--text-dark);
    }

    .feed-tab.active {
        background: var(--primary);
        color: white;
    }

    .feed-tab i {
        margin-right: 6px;
    }

    /* Section avec titre */
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .section-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title i {
        color: var(--primary);
    }

    .section-link {
        font-size: 0.875rem;
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
    }

    .section-link:hover {
        text-decoration: underline;
    }

    /* Carrousel horizontal */
    .horizontal-scroll {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 12px;
        scroll-snap-type: x mandatory;
        scrollbar-width: none;
    }

    .horizontal-scroll::-webkit-scrollbar {
        display: none;
    }

    /* Carte d'annonce compacte (pour carrousel) */
    .ad-card-compact {
        flex: 0 0 280px;
        scroll-snap-align: start;
        background: white;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        overflow: hidden;
        transition: all 0.3s;
        text-decoration: none;
        display: block;
    }

    .ad-card-compact:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .ad-card-img {
        height: 140px;
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .ad-card-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .ad-card-img .placeholder-icon {
        font-size: 2rem;
        color: var(--primary);
        opacity: 0.5;
    }

    .ad-badge-type {
        position: absolute;
        top: 10px;
        left: 10px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .ad-badge-type.offre {
        background: var(--secondary);
        color: white;
    }

    .ad-badge-type.demande {
        background: var(--accent);
        color: white;
    }

    .ad-card-body {
        padding: 14px;
    }

    .ad-card-category {
        font-size: 0.75rem;
        color: var(--primary);
        font-weight: 500;
        margin-bottom: 6px;
    }

    .ad-card-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 8px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .ad-card-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .ad-card-price {
        font-weight: 700;
        color: var(--secondary);
    }

    .ad-card-location {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    /* Grille d'annonces principales */
    .ads-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    @media (max-width: 700px) {
        .ads-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Carte d'annonce standard */
    .ad-card {
        background: white;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        overflow: hidden;
        transition: all 0.3s;
    }

    .ad-card:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow-md);
    }

    .ad-card-header {
        display: flex;
        align-items: center;
        padding: 14px;
        gap: 12px;
        border-bottom: 1px solid var(--border);
    }

    .ad-user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: var(--primary);
        overflow: hidden;
    }

    .ad-user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .ad-user-info h4 {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0;
    }

    .ad-user-info span {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .ad-card-content {
        padding: 14px;
    }

    .ad-card-content h3 {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 8px;
    }

    .ad-card-content h3 a {
        color: inherit;
        text-decoration: none;
    }

    .ad-card-content h3 a:hover {
        color: var(--primary);
    }

    .ad-card-content p {
        font-size: 0.875rem;
        color: var(--text-muted);
        line-height: 1.5;
        margin-bottom: 12px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .ad-card-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 12px;
    }

    .ad-tag {
        padding: 4px 10px;
        background: var(--bg-main);
        border-radius: 20px;
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .ad-tag.urgent {
        background: #fef2f2;
        color: var(--danger);
    }

    .ad-card-image {
        height: 180px;
        background: var(--bg-main);
        margin: 0 14px 14px;
        border-radius: var(--radius-md);
        overflow: hidden;
    }

    .ad-card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .ad-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px;
        border-top: 1px solid var(--border);
        background: var(--bg-main);
    }

    .ad-price {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--secondary);
    }

    .ad-actions {
        display: flex;
        gap: 8px;
    }

    .btn-action {
        padding: 8px 14px;
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        display: flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }

    .btn-action.primary {
        background: var(--primary);
        color: white;
    }

    .btn-action.primary:hover {
        background: var(--primary-dark);
    }

    .btn-action.secondary {
        background: white;
        color: var(--text-muted);
        border: 1px solid var(--border);
    }

    .btn-action.secondary:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    /* =========================================
       TOP PROS WIDGET
       ========================================= */
    .toppros-widget {
        background: #ffffff;
        border: 1px solid rgba(15,23,42,.10);
        border-radius: 18px;
        box-shadow: 0 10px 26px rgba(2,6,23,.06);
        overflow: hidden;
        margin-top: 20px;
    }
    
    .toppros__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 14px 10px;
    }
    
    .toppros__title {
        margin: 0;
        font-size: 14px;
        font-weight: 800;
        letter-spacing: -0.01em;
    }
    
    .toppros__score {
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 800;
        font-size: 13px;
        color: var(--text-main);
    }
    
    .toppros__star {
        color: #f59e0b;
        font-size: 14px;
        line-height: 1;
    }
    
    .toppros__more {
        margin-left: 4px;
        width: 22px;
        height: 22px;
        border-radius: 8px;
        border: 1px solid rgba(15,23,42,.10);
        background: #fff;
        color: #334155;
        cursor: pointer;
        display: grid;
        place-items: center;
        line-height: 1;
        transition: transform .15s ease, box-shadow .15s ease;
        text-decoration: none;
    }
    
    .toppros__more:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 18px rgba(2,6,23,.08);
    }
    
    .toppros__list {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .toppros__divider {
        height: 1px;
        background: rgba(15,23,42,.08);
        margin: 0 14px;
    }
    
    .pro-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
    }
    
    .pro__avatar {
        width: 40px;
        height: 40px;
        border-radius: 999px;
        object-fit: cover;
        border: 1px solid rgba(15,23,42,.10);
        flex: 0 0 auto;
        background: linear-gradient(135deg, #7c3aed, #9333ea);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 0.9rem;
    }
    
    .pro__avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 999px;
    }
    
    .pro__main {
        flex: 1;
        min-width: 0;
    }
    
    .pro__row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 10px;
    }
    
    .pro__name {
        font-weight: 800;
        font-size: 13px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--text-main);
        text-decoration: none;
    }
    
    .pro__name:hover {
        color: #7c3aed;
    }
    
    .pro__job {
        font-size: 12px;
        color: #64748b;
        white-space: nowrap;
    }
    
    .pro__rating {
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .stars-bar {
        position: relative;
        width: 72px;
        height: 12px;
        display: inline-block;
        border-radius: 999px;
        overflow: hidden;
        border: 1px solid rgba(245,158,11,.25);
        background: rgba(245,158,11,.12);
    }
    
    .stars-bar__fill {
        position: absolute;
        inset: 0;
        width: 0%;
        background: linear-gradient(90deg, rgba(245,158,11,.95), rgba(245,158,11,.75));
    }
    
    .pro__ratingText {
        font-size: 12px;
        font-weight: 800;
        color: #334155;
    }
    
    .pro__actions {
        position: relative;
        flex: 0 0 auto;
    }
    
    .pro__cta {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        border: 1px solid rgba(15,23,42,.10);
        background: #fff;
        cursor: pointer;
        display: grid;
        place-items: center;
        transition: transform .15s ease, box-shadow .15s ease;
        padding: 0;
    }
    
    .pro__cta:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 18px rgba(2,6,23,.08);
    }
    
    .pro__ctaIcon {
        font-size: 14px;
        color: #334155;
        line-height: 1;
    }
    
    .pro__menu {
        position: absolute;
        top: 38px;
        right: 0;
        width: 210px;
        background: #fff;
        border: 1px solid rgba(15,23,42,.12);
        border-radius: 14px;
        box-shadow: 0 18px 40px rgba(2,6,23,.14);
        padding: 6px;
        display: none;
        z-index: 50;
        transform-origin: top right;
    }
    
    .pro__menu::before {
        content: "";
        position: absolute;
        top: -7px;
        right: 10px;
        width: 12px;
        height: 12px;
        background: #fff;
        border-left: 1px solid rgba(15,23,42,.12);
        border-top: 1px solid rgba(15,23,42,.12);
        transform: rotate(45deg);
    }
    
    .pro__menu.open {
        display: block;
        animation: fadeScalePro .18s ease;
    }
    
    @keyframes fadeScalePro {
        from { opacity: 0; transform: translateY(-6px) scale(.96); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    
    .pro__menuItem {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        font-size: 13px;
        font-weight: 800;
        color: var(--text-main);
        text-decoration: none;
        border-radius: 10px;
        transition: background .15s ease, transform .15s ease;
    }
    
    .pro__menuItem:hover {
        background: #f1f5f9;
        transform: translateX(2px);
    }
    
    .pro__menuSep {
        height: 1px;
        background: rgba(15,23,42,.08);
        margin: 6px 8px;
    }
    
    .pro__menuItem--primary {
        background: linear-gradient(180deg, rgba(37,99,235,.98), rgba(29,78,216,.98));
        color: #fff;
        border: 1px solid rgba(37,99,235,.25);
        box-shadow: 0 12px 22px rgba(37,99,235,.18);
    }
    
    .pro__menuItem--primary:hover {
        background: linear-gradient(180deg, rgba(29,78,216,.98), rgba(30,64,175,.98));
        color: #fff;
    }

    /* =========================================
       SIDEBAR DROITE - Widgets
       ========================================= */
    .sidebar-right {
        position: relative;
        height: calc(100vh - 220px);
        overflow-y: auto;
        overflow-x: hidden;
    }

    .promo-card { background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%); border-radius: 20px; padding: 25px; text-align: center; margin-bottom: 20px; }
    .promo-card i { font-size: 2.5rem; color: white; margin-bottom: 15px; }
    .promo-card h5 { color: white; margin-bottom: 10px; }
    .promo-card p { color: rgba(255,255,255,0.8); font-size: 0.9rem; margin-bottom: 15px; }
    .promo-btn { background: white; color: #7c3aed; border: none; padding: 10px 25px; border-radius: 10px; font-weight: 600; }
    .promo-btn:hover { background: #f8f9fa; color: #7c3aed; }

    .sidebar-right .widgets-wrapper {
        position: sticky !important;
        top: 90px !important;
    }

    .widget {
        background: var(--bg-card) !important;
        border-radius: var(--radius-md) !important;
        padding: 20px !important;
        margin-bottom: 20px !important;
        border: 1px solid var(--border-color) !important;
        box-shadow: var(--shadow-sm) !important;
    }

    .dashboard-summary {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
        border: none !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08) !important;
    }

    .dashboard-summary h3 {
        font-size: 1rem !important;
        font-weight: 700 !important;
        color: var(--text-main) !important;
        margin-bottom: 20px !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }

    .dashboard-summary h3::before {
        content: 'ðŸ“Š';
        font-size: 1.2rem;
    }

    .stats-list {
        list-style: none !important;
        padding: 0 !important;
        margin: 0 0 20px 0 !important;
    }

    .stats-list li {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 12px 0 !important;
        border-bottom: 1px solid rgba(0,0,0,0.05) !important;
        font-size: 0.9rem !important;
    }

    .stats-list li:last-child {
        border-bottom: none !important;
    }

    .stats-list-link {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        width: 100% !important;
        text-decoration: none !important;
        padding: 4px 8px !important;
        margin: -4px -8px !important;
        border-radius: 8px !important;
        transition: background-color 0.2s, transform 0.2s !important;
    }
    
    .stats-list-link:hover {
        background-color: rgba(79, 70, 229, 0.08) !important;
        transform: translateX(4px) !important;
    }
    
    .stats-list-link:hover .stat-label {
        color: var(--primary-color) !important;
    }

    .stat-label {
        color: var(--text-muted) !important;
        font-weight: 500 !important;
    }

    .stat-value {
        font-weight: 700 !important;
        color: var(--primary-color) !important;
        background: rgba(79, 70, 229, 0.1) !important;
        padding: 4px 12px !important;
        border-radius: 20px !important;
        font-size: 0.85rem !important;
    }

    .btn-block {
        display: block !important;
        width: 100% !important;
        text-align: center !important;
    }

    .btn-secondary {
        background: #E5E7EB !important;
        color: var(--text-main) !important;
        padding: 10px 16px !important;
        border-radius: var(--radius-md) !important;
        font-weight: 600 !important;
        font-size: 0.9rem !important;
        text-decoration: none !important;
        transition: background 0.2s !important;
    }

    .btn-secondary:hover {
        background: #D1D5DB !important;
    }

    .subscription-box {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
        color: white !important;
        border: none !important;
    }

    .subscription-box .sub-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin-bottom: 10px !important;
    }

    .subscription-box p {
        font-size: 0.85rem !important;
        color: #94A3B8 !important;
        margin-bottom: 15px !important;
    }

    .btn-gold {
        background: var(--accent-gold) !important;
        color: white !important;
        padding: 10px 16px !important;
        border-radius: var(--radius-md) !important;
        font-weight: 700 !important;
        font-size: 0.9rem !important;
        text-decoration: none !important;
        transition: background 0.2s !important;
    }

    .btn-gold:hover {
        background: #D97706 !important;
    }

    .ad-space {
        padding: 0 !important;
        overflow: hidden !important;
        position: relative !important;
    }

    .ad-label {
        position: absolute !important;
        top: 10px !important;
        right: 10px !important;
        background: rgba(0,0,0,0.5) !important;
        color: white !important;
        font-size: 0.6rem !important;
        padding: 2px 6px !important;
        border-radius: 4px !important;
    }

    .ad-content img {
        width: 100% !important;
        display: block !important;
    }

    .ad-content h4 {
        margin-top: 10px !important;
        font-size: 0.95rem !important;
        padding: 0 15px !important;
    }

    .ad-content p {
        margin-bottom: 15px !important;
        font-size: 0.85rem !important;
        color: var(--text-muted);
        padding: 0 15px;
    }

    .widget-cta .btn-cta {
        display: inline-block;
        background: white;
        color: var(--primary);
        padding: 10px 24px;
        border-radius: var(--radius-md);
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }

    .widget-cta .btn-cta:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    /* Widget Pros */
    .pro-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .pro-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px;
        border-radius: var(--radius-md);
        transition: all 0.2s;
        text-decoration: none;
    }

    .pro-item:hover {
        background: var(--bg-main);
    }

    .pro-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: var(--primary-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: var(--primary);
        overflow: hidden;
    }

    .pro-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .pro-info {
        flex: 1;
        min-width: 0;
    }

    .pro-info h5 {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0 0 2px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pro-info span {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .pro-rating {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 0.8rem;
    }

    .pro-rating i {
        color: var(--accent);
    }

    /* Widget Stats */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .stat-item {
        text-align: center;
        padding: 12px;
        background: var(--bg-main);
        border-radius: var(--radius-md);
        text-decoration: none;
        transition: all 0.2s;
    }

    .stat-item:hover {
        background: var(--primary-light);
    }

    .stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary);
    }

    .stat-label {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 2px;
    }

    /* =========================================
       BOUTON FLOTTANT - CrÃ©er une annonce
       ========================================= */
    .fab-create {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 60px;
        height: 60px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 1.5rem;
        cursor: pointer;
        box-shadow: var(--shadow-lg);
        transition: all 0.3s;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .fab-create:hover {
        background: var(--primary-dark);
        transform: scale(1.1);
        color: white;
    }

    /* =========================================
       BADGE TEST
       ========================================= */
    .test-badge {
        position: fixed;
        top: 80px;
        right: 20px;
        background: var(--danger);
        color: white;
        padding: 8px 16px;
        border-radius: var(--radius-md);
        font-size: 0.8rem;
        font-weight: 600;
        z-index: 1001;
        box-shadow: var(--shadow-md);
    }

    .test-badge a {
        color: white;
        text-decoration: underline;
        margin-left: 8px;
    }

    /* =========================================
       RESPONSIVE
       ========================================= */
    @media (max-width: 768px) {
        .hero-search {
            padding: 24px 0;
        }

        .hero-title {
            font-size: 1.25rem;
        }

        .search-container {
            flex-direction: column;
        }

        .search-btn {
            width: 100%;
            justify-content: center;
        }

        .main-container {
            padding: 16px;
        }

        .feed-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
        }

        .feed-tab {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .ads-grid {
            grid-template-columns: 1fr;
        }
    }



    /* =========================================
       PAGE TEST - ARCHITECTURE MODERNE
       ========================================= */
    :root {
        /* ðŸŽ¨ Palette ServicesPro */
        --primary: #3a86ff;
        --primary-dark: #2667cc;
        --primary-hover: #2667cc;
        --primary-light: rgba(58, 134, 255, 0.1);
        --secondary: #8338ec;
        --accent: #ffbe0b;
        
        /* Funds */
        --bg-body: #f8fafc;
        --text-dark: #1e293b;
        --text-light: #64748b;
        --white: #ffffff;
        --border: #e2e8f0;
    }

    body {
        background-color: var(--bg-body);
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }

    /* =========================================
       HEADER STYLES RESTORATION (Pour "Ne pas toucher au header")
       ========================================= */
    .header-modern {
        background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%) !important;
        box-shadow: 0 2px 20px -4px rgba(58, 134, 255, 0.15), 0 4px 12px -2px rgba(0, 0, 0, 0.08) !important;
        border-bottom: 1px solid rgba(58, 134, 255, 0.1) !important;
        /* height: 90px !important; REMOVED to match standard nav */
        z-index: 1001 !important;
    }
    .header-modern .container-fluid {
        max-width: 1600px !important;
        margin: 0 auto;
        padding: 0 32px !important;
    }
    .header-modern .navbar-brand-modern .brand-logo {
        width: 42px; height: 42px; font-size: 1.2rem;
        box-shadow: 0 4px 15px rgba(58, 134, 255, 0.35);
    }
    .header-modern .navbar-brand-modern .brand-text { font-size: 1.4rem; }
    
    /* Search bar styles removed */
    
    .nav-link-modern {
        font-weight: 500; color: #6c757d; padding: 8px 14px; border-radius: 10px;
        display: flex; align-items: center; gap: 6px; text-decoration: none;
    }
    .nav-link-modern:hover { color: var(--text-dark); background: rgba(58, 134, 255, 0.05); }
    .nav-link-modern.active { color: var(--primary); background: rgba(58, 134, 255, 0.1); font-weight: 600; }
    .nav-link-lost { color: #ea580c !important; border: 1px solid #fed7aa; background: #fff7ed; }
    .lost-badge { background: #ea580c; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.65rem; margin-left: 4px; }
    
    /* =========================================
       NEW HERO & CONTENT STYLES
       ========================================= */
    
    /* HERO SECTION */
    .hero-section {
        background-color: #312e81; /* Indigo 900 */
        padding-top: 2rem;
        padding-bottom: 7rem;
        position: relative;
        overflow: hidden;
    }

    /* Background Shapes */
    .hero-shape-1 {
        position: absolute; top: 0; right: 0; width: 24rem; height: 24rem;
        border-radius: 9999px; background-color: #3730a3; opacity: 0.5;
        filter: blur(64px); transform: translate(5rem, -5rem);
    }
    .hero-shape-2 {
        position: absolute; bottom: 0; left: 0; width: 20rem; height: 20rem;
        border-radius: 9999px; background-color: #701a75; opacity: 0.3;
        filter: blur(64px); transform: translate(-5rem, 5rem);
    }
    
    /* Hero Search Bar (Larger & More Prominent) */
    .hero-search-wrapper {
        background: white; border-radius: 16px; padding: 8px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        max-width: 800px; margin: 0 auto;
        display: flex; gap: 10px; flex-wrap: wrap;
    }
    @media (min-width: 768px) { .hero-search-wrapper { flex-wrap: nowrap; } }
    
    .hero-input-group {
        flex: 1; position: relative; min-width: 200px;
    }
    .hero-input-group input {
        width: 100%; height: 56px; border: none; background: transparent;
        padding-left: 48px; font-size: 1.05rem; outline: none; border-radius: 12px;
    }
    .hero-input-group input:focus { background: #f8fafc; }
    .hero-input-group i {
        position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
        font-size: 1.2rem; color: #94a3b8;
    }
    
    .hero-search-btn {
        background: var(--primary); color: white; font-weight: 700;
        border: none; border-radius: 12px; padding: 0 32px; height: 56px;
        font-size: 1.1rem; cursor: pointer; transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
    }
    .hero-search-btn:hover { background: var(--primary-dark); transform: translateY(-2px); }

    /* Trust Badges */
    .trust-badges {
        margin-top: 2rem; display: flex; justify-content: center; gap: 2rem;
        color: #e0e7ff; font-weight: 500; font-size: 0.95rem;
    }
    .trust-badges i { color: #34d399; margin-right: 8px; }

    /* CATEGORIES FLOATING GRID */
    .categories-floating {
        margin-top: -60px; /* Overlap hero */
        position: relative; z-index: 10;
        margin-bottom: 2rem;
    }
    .cat-card-modern {
        background: white; border-radius: 16px; padding: 24px;
        text-align: center; border: 1px solid #f1f5f9;
        box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        transition: all 0.3s; height: 100%;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        text-decoration: none; color: var(--text-dark);
        position: relative; overflow: hidden;
    }
    .cat-card-modern:hover, .cat-card-modern.active {
        transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        border-color: var(--primary);
    }
    .cat-card-modern.active { background-color: #f0f9ff; border: 2px solid var(--primary); }
    .cat-card-modern i { font-size: 2.5rem; margin-bottom: 12px; transition: transform 0.3s; }
    .cat-card-modern:hover i { transform: scale(1.1); }
    .cat-card-modern span { font-weight: 700; font-size: 1.05rem; }

    /* SUBCATEGORIES BAR */
    .subcat-bar {
        background: #ffffff; 
        border-bottom: 1px solid #e2e8f0; 
        padding: 20px 0;
        margin-bottom: 40px; 
        white-space: nowrap; 
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        position: relative;
        z-index: 20; /* Ensure visual priority */
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); /* Slight shadow to prove visibility */
        display: block !important; /* Force display if condition met */
    }
    .subcat-pill {
        display: inline-block; padding: 8px 16px; border-radius: 50px;
        background: #f1f5f9; color: var(--text-dark); font-weight: 500;
        margin-right: 10px; text-decoration: none; transition: all 0.2s;
        border: 1px solid transparent;
    }
    .subcat-pill:hover, .subcat-pill.active {
        background: var(--primary); color: white;
        box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
    }

    /* ADVERTISEMENT SPACES */
    .ad-space-banner {
        width: 100%; height: 120px;
        background: linear-gradient(45deg, #f3f4f6 25%, #e5e7eb 25%, #e5e7eb 50%, #f3f4f6 50%, #f3f4f6 75%, #e5e7eb 75%, #e5e7eb 100%);
        background-size: 20px 20px;
        border-radius: 12px; display: flex; align-items: center; justify-content: center;
        color: #9ca3af; font-weight: 600; letter-spacing: 1px;
        border: 2px dashed #d1d5db; margin: 2rem 0;
        position: relative; overflow: hidden;
    }
    .ad-label {
        position: absolute; top: 5px; right: 8px; font-size: 0.65rem;
        background: rgba(0,0,0,0.1); padding: 2px 6px; border-radius: 4px;
    }

    /* LISTINGS GRID */
    .listing-card {
        background: white; border-radius: 16px; overflow: hidden;
        border: 1px solid #f1f5f9; transition: all 0.3s;
        height: 100%; display: flex; flex-direction: column;
    }
    .listing-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.08);
    }
    .listing-img-box {
        height: 180px; background: #e2e8f0; position: relative;
        overflow: hidden;
    }
    .listing-img-box img { width: 100%; height: 100%; object-fit: cover; }
    .listing-badge {
        position: absolute; top: 12px; left: 12px;
        background: rgba(255,255,255,0.9); padding: 4px 10px;
        border-radius: 8px; font-size: 0.75rem; font-weight: 700;
        color: var(--primary); box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .listing-body { padding: 20px; flex: 1; display: flex; flex-direction: column; }
    .listing-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 8px; color: var(--text-dark); line-height: 1.4; }
    .listing-meta { font-size: 0.85rem; color: var(--text-light); margin-bottom: 15px; display: flex; gap: 10px; align-items: center; }
    .listing-price { font-size: 1.2rem; font-weight: 800; color: var(--primary); margin-top: auto; }
    .listing-footer {
        padding: 12px 20px; border-top: 1px solid #f1f5f9; background: #f8fafc;
        display: flex; justify-content: space-between; align-items: center;
    }
    .user-mini { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600; color: #475569; }
    .user-avatar-sm { width: 40px; height: 40px; border-radius: 8px; background: #cbd5e1; }

    /* SECTION TITLES */
    .modern-title-block { text-align: center; margin-bottom: 3rem; }
    .modern-subtitle {
        color: var(--primary); font-weight: 700; text-transform: uppercase;
        letter-spacing: 1px; font-size: 0.85rem; display: block; margin-bottom: 8px;
    }
    .modern-title { font-size: 2rem; font-weight: 800; color: var(--text-dark); margin: 0; }

    /* STEPS */
    .step-box {
        text-align: center; padding: 20px;
    }
    .step-icon-circle {
        width: 80px; height: 80px; border-radius: 24px;
        display: flex; align-items: center; justify-content: center;
        font-size: 32px; margin: 0 auto 20px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }

    /* CTA BLOCK */
    .pro-cta-block {
        background: #1e293b; border-radius: 24px; overflow: hidden;
        position: relative; color: white;
    }
    .pro-cta-content { padding: 48px; z-index: 2; position: relative; }
    .pro-list li { margin-bottom: 12px; display: flex; align-items: center; gap: 10px; color: #cbd5e1; }
    .pro-list i { color: #34d399; }


        :root {
            /* ðŸŽ¨ Palette ServicesPro - Couleurs de fond */
            /* Couleur principale - Bleu vif (ServicesPro) */
            --primary: #3a86ff;
            --primary-hover: #2667cc;
            --primary-dark: #2667cc;
            --primary-light: rgba(58, 134, 255, 0.1);
            
            /* Secondaire - Violet */
            --secondary: #8338ec;
            
            /* Accent */
            --accent: #0ea5e9;
            
            /* Fonds - Style ServicesPro */
            --bg-body: #f5f7fb;
            --bg-card: #ffffff;
            --bg-header: rgba(255, 255, 255, 0.95);
            --light: #f8f9fa;
            --light-gray: #e9ecef;
            
            /* Texte */
            --text-main: #212529;
            --text-secondary: #6c757d;
            --dark: #212529;
            --gray: #6c757d;
            
            /* Ã‰tats */
            --success: #06d6a0;
            --warning: #ffbe0b;
            --danger: #ef4444;
            
            /* Bordures */
            --border-subtle: #e9ecef;
            --border-radius: 12px;
            
            /* Ombres - Style ServicesPro */
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.12);
            --shadow-soft: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-action: 0 4px 14px rgba(58, 134, 255, 0.3);
            
            /* CompatibilitÃ© avec l'existant */
            --gradient-primary: linear-gradient(135deg, #3a86ff, #8338ec);
            --gradient-warm: linear-gradient(135deg, #f59e0b, #f97316);
            --text-dark: var(--text-main);
            --text-muted: var(--text-secondary);
            --bg-light: var(--bg-body);
            --border-light: var(--border-subtle);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: var(--shadow-soft);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --transition: all 0.3s ease;
        }
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        body {
            background: var(--bg-body);
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        /* ===== HEADER PROFESSIONNEL ===== */
        .header-modern {
            background: var(--bg-header);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.05); /* Bordure trÃ¨s discrÃ¨te */
            height: 70px; /* Un peu plus haut pour l'importance */
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.15); /* Ombre portÃ©e marquÃ©e */
            position: relative;
            z-index: 2000; /* TrÃ¨s haut pour passer au-dessus de tout */
        }
        
        /* ===== SUB-HEADER ===== */
        .subheader-modern {
            background: #f8fafc; /* LÃ©gÃ¨rement gris pour le contraste ou blanc */
            border-bottom: 1px solid var(--border-subtle);
            height: 50px;
            display: flex;
            align-items: center;
            position: relative;
            z-index: 1000;
            margin-top: 0; /* CollÃ© au header */
        }
        .subheader-modern .container-fluid {
            height: 100%;
        }

        .header-modern .container-fluid {
            height: 100%;
        }
        .navbar-brand-modern {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .brand-logo {
            width: 38px;
            height: 38px;
            background: var(--gradient-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 1.1rem;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .navbar-brand-modern:hover .brand-logo {
            transform: scale(1.08) rotate(-3deg);
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.45);
        }
        .brand-text {
            font-weight: 800;
            font-size: 1.3rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }
        
        /* Navigation Links */
        .nav-link-modern {
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--text-muted) !important;
            padding: 8px 14px !important;
            border-radius: 10px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .nav-link-modern:hover {
            color: var(--text-dark) !important;
            background: var(--primary-50);
        }
        .nav-link-modern.active {
            color: var(--primary) !important;
            background: var(--primary-100);
            font-weight: 600;
        }
        .nav-link-modern i {
            font-size: 0.9rem;
            opacity: 0.85;
        }
        .nav-back-link {
            margin-right: 8px;
        }
        
        /* Points Badge */
        .points-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #fffbeb;
            padding: 6px 14px;
            border-radius: 20px;
            border: 1px solid #fcd34d;
            font-size: 0.8rem;
            font-weight: 600;
            color: #b45309;
            transition: all 0.2s;
        }
        .points-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);
            background: #fff7ed;
        }
        
        /* Auth Buttons */
        .btn-auth {
            font-weight: 600;
            font-size: 0.875rem;
            padding: 9px 18px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        .btn-login {
            color: var(--text-main);
            border: 1px solid var(--border-subtle);
            background: white;
        }
        .btn-login:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
        }
        .btn-register-simple {
            background: var(--primary);
            color: white;
            border: none;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
        }
        .btn-register-simple:hover {
            background: var(--primary-hover);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            transform: translateY(-1px);
        }
        
        /* Bouton Objets perdus sobre */
        .nav-link-lost {
            color: #ea580c !important;
            border: 1px solid #fed7aa;
            background: #fff7ed;
        }
        .nav-link-lost:hover {
            background: #ffedd5;
            border-color: #fdba74;
        }
        .nav-link-lost i {
            color: #ea580c;
        }
        .lost-badge {
            background: #ea580c;
            color: white;
            font-size: 0.65rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 4px;
            font-weight: 600;
        }
        
        /* Bouton Publier - Professionnel & Visible */
        .btn-publish {
            background: var(--primary);
            color: white !important;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
        }
        .btn-publish:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(79, 70, 229, 0.3);
            color: white !important;
        }
        
        /* User Menu Button - Style "Carte Pro" */
        .user-menu-btn {
            background: white;
            padding: 4px 18px 4px 4px;
            border-radius: 50px;
            border: 1px solid var(--border-subtle);
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: auto;
        }
        .user-menu-btn:hover {
            border-color: var(--primary-light);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transform: translateY(-1px);
        }
        .user-menu-btn::after {
            display: none;
        }
        .user-name-text {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-main);
            line-height: 1.2;
        }
        .user-type-badge {
            font-size: 0.65rem;
            font-weight: 600;
            color: #6366f1;
            background: #eef2ff;
            padding: 1px 8px;
            border-radius: 10px;
            display: inline-block;
            margin-top: 2px;
        }
        .dropdown-chevron {
            font-size: 0.65rem;
            color: var(--text-muted);
            transition: transform 0.2s;
            margin-left: 4px;
        }
        .dropdown.show .dropdown-chevron {
            transform: rotate(180deg);
        }
        
        /* Bouton Messages amÃ©liorÃ© */
        .nav-messages-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 10px;
            background: #f1f5f9;
            color: #475569;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
            position: relative;
        }
        .nav-messages-btn:hover {
            background: #e2e8f0;
            color: var(--primary);
        }
        .nav-messages-btn i {
            font-size: 1rem;
        }
        .messages-counter {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 9999px;
            min-width: 20px;
            text-align: center;
            margin-left: 4px;
        }
        
        /* Points Badge in Header */
        .header-points-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 10px;
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            border: 2px solid #fcd34d;
            color: #92400e;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 700;
            transition: all 0.25s ease;
        }
        .header-points-badge:hover {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(252, 211, 77, 0.4);
            color: #78350f;
        }
        .header-points-badge i {
            color: #f59e0b;
            font-size: 0.95rem;
        }
        .header-points-value {
            font-weight: 800;
            font-size: 0.9rem;
        }
        
        /* Alert Bell in Header (boost alerts) */
        .header-alert-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #fef2f2;
            border: 2px solid #fca5a5;
            color: #ef4444;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
        }
        .header-alert-btn:hover {
            background: #fee2e2;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        .header-alert-btn i {
            font-size: 0.95rem;
        }
        .alert-counter {
            position: absolute;
            top: -4px;
            right: -4px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 9999px;
            min-width: 16px;
            text-align: center;
            line-height: 1.3;
        }

        /* ===== Notification Bell Button ===== */
        .header-notif-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #f1f5f9;
            border: 2px solid #e2e8f0;
            color: #475569;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
        }
        .header-notif-btn:hover {
            background: #e2e8f0;
            color: #1e293b;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header-notif-btn i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }
        .header-notif-btn:hover i {
            animation: bellRing 0.6s ease;
        }
        @keyframes bellRing {
            0% { transform: rotate(0); }
            15% { transform: rotate(14deg); }
            30% { transform: rotate(-12deg); }
            45% { transform: rotate(10deg); }
            60% { transform: rotate(-8deg); }
            75% { transform: rotate(4deg); }
            100% { transform: rotate(0); }
        }
        .notif-counter {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            font-size: 0.6rem;
            font-weight: 700;
            padding: 2px 5px;
            border-radius: 9999px;
            min-width: 18px;
            text-align: center;
            line-height: 1.3;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
            animation: counterPulse 2s ease-in-out infinite;
        }
        @keyframes counterPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* ===== Notification Dropdown ===== */
        .notif-dropdown-menu {
            width: 380px;
            max-width: 95vw;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.15), 0 8px 16px -8px rgba(0,0,0,0.1);
            border-radius: 16px;
            padding: 0;
            overflow: hidden;
            animation: dropdownSlide 0.25s ease;
        }
        @keyframes dropdownSlide {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .notif-dropdown-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-bottom: 1px solid #e2e8f0;
        }
        .notif-dropdown-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: #1e293b;
        }
        .notif-mark-all-btn {
            background: none;
            border: none;
            color: #6366f1;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .notif-mark-all-btn:hover {
            background: #eef2ff;
            color: #4f46e5;
        }
        .notif-dropdown-body {
            max-height: 400px;
            overflow-y: auto;
            overscroll-behavior: contain;
        }
        .notif-dropdown-body::-webkit-scrollbar {
            width: 4px;
        }
        .notif-dropdown-body::-webkit-scrollbar-track {
            background: transparent;
        }
        .notif-dropdown-body::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        /* Notification Item */
        .notif-dropdown-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 16px;
            text-decoration: none !important;
            color: inherit;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s ease;
            position: relative;
        }
        .notif-dropdown-item:hover {
            background: #f8fafc;
            color: inherit;
        }
        .notif-dropdown-item.notif-unread {
            background: #fafbff;
        }
        .notif-dropdown-item:last-child {
            border-bottom: none;
        }
        .notif-item-icon {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }
        .notif-item-content {
            flex: 1;
            min-width: 0;
        }
        .notif-item-title {
            font-weight: 600;
            font-size: 0.82rem;
            color: #1e293b;
            line-height: 1.3;
            margin-bottom: 2px;
        }
        .notif-unread .notif-item-title {
            color: #0f172a;
        }
        .notif-item-message {
            font-size: 0.76rem;
            color: #64748b;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .notif-item-time {
            font-size: 0.68rem;
            color: #94a3b8;
            margin-top: 4px;
        }
        .notif-item-time i {
            font-size: 0.62rem;
            margin-right: 2px;
        }
        .notif-unread-dot {
            flex-shrink: 0;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-top: 6px;
        }
        .notif-empty {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }
        .notif-empty i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: block;
        }
        .notif-empty p {
            font-size: 0.85rem;
            margin: 0;
        }
        .notif-dropdown-footer {
            text-align: center;
            padding: 10px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .notif-dropdown-footer a {
            color: #6366f1;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
        }
        .notif-dropdown-footer a:hover {
            color: #4f46e5;
            text-decoration: underline;
        }
        
        /* Dropdown Menu */
        .dropdown-menu-modern {
            border: 1px solid var(--border-subtle);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            border-radius: 16px;
            padding: 8px;
            margin-top: 12px;
            animation: dropdownFade 0.2s ease;
        }
        @keyframes dropdownFade {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .dropdown-item-modern {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dropdown-item-modern:hover {
            background: var(--bg-body);
            color: var(--primary);
        }
        .dropdown-item-modern i {
            width: 18px;
            font-size: 0.9rem;
        }
        .dropdown-item-modern.text-danger:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        /* Dropdown scrollable on mobile */
        @media (max-width: 767.98px) {
            .dropdown-menu-modern {
                max-height: 80vh;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
        
        /* User Avatar */
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .user-avatar-placeholder {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 600;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(79, 70, 229, 0.2);
        }
        
        /* Toggler */
        .navbar-toggler-modern {
            border: none;
            padding: 8px;
            border-radius: 8px;
        }
        .navbar-toggler-modern:focus {
            box-shadow: none;
            background: var(--primary-50);
        }
        
        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--gradient-warm);
            color: white;
            font-size: 0.6rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 9999px;
            border: 2px solid white;
            min-width: 18px;
            text-align: center;
        }
        
        /* Icon Button */
        .nav-icon-btn {
            position: relative;
            padding: 8px 10px !important;
            border-radius: 10px;
            transition: all 0.2s;
        }
        .nav-icon-btn:hover {
            background: var(--primary-50);
        }
        .nav-icon-btn i {
            transition: transform 0.2s;
        }
        .nav-icon-btn:hover i {
            transform: scale(1.1);
        }
        
        /* ===== GLOBAL UTILITY CLASSES ===== */
        .btn-primary-gradient {
            background: linear-gradient(135deg, #7c3aed, #9333ea);
            color: white;
            border: none;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.4);
            transition: all 0.3s;
        }
        .btn-primary-gradient:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.5);
        }
        
        .card-modern {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        }
        .card-modern:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }
        
        .badge-primary {
            background: var(--gradient-primary);
            color: white;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary-light), var(--accent));
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }
    </style>
    @stack('styles')
<style>
    /* =========================================
       PAGE TEST - ARCHITECTURE MODERNE
       Style ServicesPro
       ========================================= */
    
    :root {
        /* ðŸŽ¨ Palette ServicesPro */
        --primary: #3a86ff;
        --primary-dark: #2667cc;
        --primary-hover: #2667cc;
        --primary-light: rgba(58, 134, 255, 0.1);
        --secondary: #8338ec;
        --accent: #ffbe0b;
        --success: #06d6a0;
        --warning: #ffbe0b;
        --danger: #ef4444;
        
        /* Fonds - Style ServicesPro */
        --light: #f8f9fa;
        --dark: #212529;
        --gray: #6c757d;
        --light-gray: #e9ecef;
        --bg-main: #f5f7fb;
        --bg-body: #f5f7fb;
        --bg-card: #ffffff;
        
        /* Texte */
        --text-main: #212529;
        --text-dark: #212529;
        --text-muted: #6c757d;
        
        /* Bordures */
        --border: #e9ecef;
        --border-subtle: #e9ecef;
        --border-color: #e9ecef;
        --border-radius: 12px;
        
        /* Ombres - Style ServicesPro */
        --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        --shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.12);
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        
        /* Rayons */
        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 16px;
        --radius-xl: 24px;
        
        --transition: all 0.3s ease;
    }

    body {
        background-color: #f5f7fb;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        color: var(--dark);
        line-height: 1.6;
    }

    /* Header height override for test page */
    .header-modern {
        height: 90px !important;
        min-height: 90px !important;
    }

    /* =========================================
       HEADER PRINCIPAL - Style amÃ©liorÃ©
       Plus large avec effet d'ombre sÃ©duisant
       ========================================= */
    .header-modern {
        background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%) !important;
        box-shadow: 0 2px 20px -4px rgba(58, 134, 255, 0.15), 0 4px 12px -2px rgba(0, 0, 0, 0.08) !important;
        border-bottom: 1px solid rgba(58, 134, 255, 0.1) !important;
        height: 90px !important;
        z-index: 1001 !important;
    }

    .header-modern .container-fluid {
        max-width: 1600px !important;
        margin: 0 auto;
        padding: 0 32px !important;
    }

    .header-modern .navbar-brand-modern .brand-logo {
        width: 42px;
        height: 42px;
        font-size: 1.2rem;
        box-shadow: 0 4px 15px rgba(58, 134, 255, 0.35);
    }

    .header-modern .navbar-brand-modern .brand-text {
        font-size: 1.4rem;
    }

    /* Header search (test page) */
    .header-search-inline {
        flex: 1;
        display: flex;
        justify-content: center;
    }
    .header-search-inline .search-card {
        display: flex;
        align-items: center;
        gap: 10px;
        background: white;
        border: 1px solid var(--border-subtle);
        border-radius: 14px;
        padding: 8px 10px;
        box-shadow: var(--shadow-sm);
        max-width: 680px; /* Ã‰largi de 520px Ã  680px */
        width: 100%;
    }
    .header-search-inline .search-input-group {
        position: relative;
        flex: 1 1 0;
        min-width: 0;
        border-radius: 12px;
    }
    .header-search-inline .search-input-group input {
        width: 100%;
        border: none;
        background: transparent;
        padding: 10px 12px 10px 36px;
        font-size: 0.9rem;
        color: var(--text-main);
        border-radius: 12px;
    }
    .header-search-inline .search-input-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.95rem;
        pointer-events: none;
    }
    .header-search-inline .search-divider {
        width: 1px;
        height: 22px;
        background: var(--border-subtle);
        margin: 0 4px;
    }
    .header-search-inline .search-submit-btn {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 10px;
        border: none;
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        font-weight: 600;
        font-size: 0.85rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .header-search-inline .search-submit-btn:hover {
        transform: translateY(-1px);
    }
    .header-search-inline .search-submit-btn i {
        font-size: 0.85rem;
    }
    @media (max-width: 991px) {
        .header-search-inline {
            order: 3;
            width: 100%;
            margin-top: 10px;
        }
        .header-search-inline .search-card {
            max-width: none;
        }
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* =========================================
       HEADER - Style ServicesPro
       ========================================= */
    .sp-header {
        background-color: white;
        box-shadow: var(--shadow);
        position: sticky;
        top: 0;
        z-index: 1000;
        padding: 12px 0;
    }

    .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
    }

    .logo {
        font-size: 28px;
        font-weight: 700;
        color: var(--primary);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .logo i {
        color: var(--secondary);
    }

    /* Styles boutons "header fixe du dessus" */
    .nav-buttons-container {
        display: flex;
        align-items: center;
        gap: 4px;
        flex: 1;
        justify-content: center;
    }

    .nav-link-modern {
        font-weight: 500;
        font-size: 0.875rem;
        color: var(--text-muted);
        text-decoration: none;
        padding: 8px 14px;
        border-radius: 10px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .nav-link-modern:hover {
        color: var(--text-dark);
        background: rgba(58, 134, 255, 0.05); /* Utilisation de var(--primary) avec opacitÃ© */
    }
    
    .nav-link-modern.active {
        color: var(--primary);
        background: rgba(58, 134, 255, 0.1);
        font-weight: 600;
    }
    
    .nav-link-modern i {
        font-size: 0.9rem;
        opacity: 0.85;
    }

    .nav-link-lost {
        color: #ea580c !important;
        border: 1px solid #fed7aa;
        background: #fff7ed;
    }
    
    .nav-link-lost:hover {
        background: #ffedd5;
        border-color: #fdba74;
    }
    
    .nav-link-lost i {
        color: #ea580c;
    }
    
    .lost-badge {
        background: #ea580c;
        color: white;
        font-size: 0.65rem;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 4px;
        font-weight: 600;
    }

    /* Menu utilisateur header */
    .user-menu {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .notification-icon, .message-icon {
        position: relative;
        font-size: 20px;
        color: var(--gray);
        cursor: pointer;
        padding: 8px;
        transition: var(--transition);
    }

    .notification-icon:hover, .message-icon:hover {
        color: var(--primary);
    }

    .header-badge {
        position: absolute;
        top: 0;
        right: 0;
        background-color: var(--secondary);
        color: white;
        font-size: 12px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .user-avatar {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: var(--radius-md);
        transition: var(--transition);
        position: relative;
    }

    .user-avatar:hover {
        background-color: var(--light-gray);
    }

    .avatar-img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
        overflow: hidden;
    }

    .avatar-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .user-name {
        font-weight: 600;
        font-size: 16px;
    }

    .user-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background-color: white;
        box-shadow: var(--shadow-hover);
        border-radius: var(--radius-md);
        min-width: 200px;
        padding: 10px 0;
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: var(--transition);
        z-index: 100;
    }

    .user-avatar:hover .user-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .user-dropdown a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        text-decoration: none;
        color: var(--dark);
        transition: var(--transition);
    }

    .user-dropdown a:hover {
        background-color: var(--light-gray);
        color: var(--primary);
    }

    /* =========================================
       NAVIGATION PRINCIPALE (Sous-header)
       CollÃ© au header principal avec design moderne
       ========================================= */
    .main-nav {
        background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
        border-top: none;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        position: sticky;
        top: 90px;
        z-index: 998;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.08), 0 2px 8px -2px rgba(0, 0, 0, 0.04);
    }

    /* Container flex pour aligner le badge et le menu */
    .main-nav .container {
        display: flex;
        align-items: center;
        justify-content: center;
        max-width: 1600px; /* Plus large */
        padding: 0 24px;
    }

    .nav-links {
        display: flex;
        list-style: none;
        justify-content: center;
        gap: 5px;
        margin: 0;
        padding: 0;
        width: 100%;
    }

    .nav-item {
        position: relative;
    }

    .nav-link {
        display: block;
        padding: 16px 22px;
        text-decoration: none;
        color: var(--dark);
        font-weight: 600;
        font-size: 15px;
        transition: var(--transition);
        border-radius: 10px;
        margin: 4px 2px;
        position: relative;
    }

    .nav-link:hover {
        color: var(--primary);
        background: linear-gradient(135deg, rgba(58, 134, 255, 0.08) 0%, rgba(131, 56, 236, 0.05) 100%);
        transform: translateY(-1px);
    }

    .nav-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%) scaleX(0);
        width: 60%;
        height: 3px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border-radius: 3px 3px 0 0;
        transition: transform 0.3s ease;
    }

    .nav-link:hover::after {
        transform: translateX(-50%) scaleX(1);
    }

    /* Mega-menu */
    .mega-menu {
        position: absolute;
        left: -200px; /* DÃ©placÃ© davantage vers la gauche */
        top: 100%;
        width: 900px;
        max-width: calc(100vw - 40px);
        max-height: 450px;
        overflow-y: auto;
        background-color: white;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 8px 25px rgba(58, 134, 255, 0.1);
        border-radius: 0 var(--radius-md) var(--radius-md) var(--radius-md);
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 24px 28px;
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 20px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(15px);
        transition: var(--transition);
        z-index: 100;
    }

    /* Scrollbar personnalisÃ©e pour mega-menu */
    .mega-menu::-webkit-scrollbar {
        width: 6px;
    }
    .mega-menu::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }
    .mega-menu::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 10px;
    }

    .nav-item:hover .mega-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .nav-item-mission .mega-menu {
        left: 50%;
        transform: translate(-50%, 15px);
    }

    .nav-item-mission:hover .mega-menu {
        transform: translate(-50%, 0);
    }

    /* =========================================
       MEGA-MENU MISSION - Style Rectangulaire (Grid)
       ========================================= */
    .mega-menu-mission {
        position: absolute;
        left: 50%; /* CentrÃ© par rapport au parent nav-item-mission, mais voir ajustement global si besoin */
        transform: translateX(-35%) translateY(15px); /* DÃ©calÃ© lÃ©gÃ¨rement car le menu est large */
        top: 100%;
        width: 660px; /* RÃ©duit pour des boutons plus courts (stricte minimum) */
        background-color: #f8fafc; /* LÃ©gÃ¨rement grisÃ© pour contraste avec les cartes blanches */
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 8px 25px rgba(58, 134, 255, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 0;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: all 0.2s ease;
        z-index: 2000;
        overflow: visible !important;
    }

    .nav-item-mission:hover .mega-menu-mission {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateX(-35%) translateY(0);
    }

    .mega-menu-mission-header {
        display: none; 
    }

    .mega-menu-mission-body {
        padding: 16px; /* RÃ©duit de 24px */
        max-height: none;
        overflow: visible !important;
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* 3 colonnes */
        gap: 8px; /* RÃ©duit de 16px */
    }

    .mission-category {
        position: relative;
    }

    /* Style carte pour chaque catÃ©gorie */
    .mission-category-btn {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 8px 10px; /* RÃ©duit de 12px */
        background: white;
        border: 1px solid #f1f5f9;
        border-radius: 8px; /* LÃ©gÃ¨rement moins rond pour coller au compact */
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        height: 100%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }

    .mission-category-btn:hover {
        background: #f8fafc;
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(58, 134, 255, 0.15);
        transform: translateY(-2px);
    }

    .mission-category-left {
        display: flex;
        align-items: center;
        gap: 10px; /* RÃ©duit de 12px */
        flex: 1;
    }

    .mission-category-icon {
        width: 32px; /* RÃ©duit de 40px */
        height: 32px; /* RÃ©duit de 40px */
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem; /* RÃ©duit de 1.1rem */
        color: white;
        flex-shrink: 0;
    }

    .mission-category-info {
        flex: 1;
        overflow: hidden;
    }

    .mission-category-name {
        font-size: 0.9rem; /* RÃ©duit de 0.95rem */
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 0; /* RÃ©duit marge */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.2;
    }

    .mission-category-btn:hover .mission-category-name {
        color: var(--primary);
    }

    .mission-category-count {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .mission-category-arrow {
        display: none; /* Pas de flÃ¨che en mode grille pour plus de propretÃ© */
    }

    /* Sous-catÃ©gories (Popup) - Affichage Ã  DROITE */
    .mission-subcategories {
        position: absolute;
        left: 100%; /* PositionnÃ© Ã  droite de la catÃ©gorie */
        top: 0;
        margin-left: 4px; /* Ecart rÃ©duit entre la catÃ©gorie et le sous-menu */
        width: 280px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 0;
        opacity: 0;
        pointer-events: none;
        visibility: hidden;
        transition: all 0.2s ease;
        z-index: 2100;
        overflow: hidden;
    }

    .mission-category:hover .mission-subcategories {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        /* Peut ajouter un lÃ©ger slide horizontal si voulu */
    }

    /* Arrow tip pointing LEFT to the category */
    .mission-subcategories::before {
        content: '';
        position: absolute;
        top: 22px; /* AjustÃ© pour Ãªtre en face du centre visuel */
        left: -6px;
        transform: rotate(45deg);
        width: 12px;
        height: 12px;
        background: white;
        border-left: 1px solid rgba(0,0,0,0.08);
        border-bottom: 1px solid rgba(0,0,0,0.08);   
    }

    /* Zone "pont" pour ne pas perdre le hover lors du dÃ©placement vers la droite */
    .mission-category::after {
        content: '';
        position: absolute;
        right: -20px; /* Ã‰tend la zone active vers la droite */
        top: 0;
        width: 30px;
        height: 100%;
        background: transparent;
    }

    /* Style pour les sous-catÃ©gories qui remontent (vers le haut) */
    .mission-subcategories-up {
        top: auto !important;
        bottom: 0;
    }

    /* Ajustement de la flÃ¨che pour celles qui remontent */
    .mission-subcategories-up::before {
        top: auto;
        bottom: 22px; /* S'aligne avec le bouton en bas */
    }


    .mission-category:hover .mission-subcategories {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    /* Bridge invisible entre catÃ©gorie et sous-menu */
    .mission-category::after {
        content: '';
        position: absolute;
        right: -16px;
        top: 0;
        width: 20px;
        height: 100%;
        background: transparent;
    }

    .mission-subcategories-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 18px;
        background: linear-gradient(135deg, rgba(58, 134, 255, 0.08) 0%, rgba(131, 56, 236, 0.05) 100%);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .mission-subcategories-header-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        color: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .mission-subcategories-header h6 {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
    }

    .mission-subcategories-list {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding: 12px;
        max-height: 300px;
        overflow-y: auto;
    }

    .mission-subcat-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: white;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .mission-subcat-item:hover {
        background: linear-gradient(135deg, rgba(58, 134, 255, 0.06) 0%, rgba(131, 56, 236, 0.04) 100%);
        border-color: rgba(58, 134, 255, 0.2);
        transform: translateX(4px);
    }

    .mission-subcat-item-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .mission-subcat-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        box-shadow: 0 2px 6px rgba(58, 134, 255, 0.4);
    }

    .mission-subcat-name {
        font-size: 0.85rem;
        font-weight: 500;
        color: #4a5568;
    }

    .mission-subcat-item:hover .mission-subcat-name {
        color: var(--primary);
    }

    .mission-subcat-badge {
        min-width: 26px;
        height: 20px;
        padding: 0 6px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 0.7rem;
        font-weight: 700;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 3px 10px rgba(58, 134, 255, 0.3);
    }

    .mission-subcategories-footer {
        padding: 12px;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        background: rgba(248, 250, 252, 0.8);
    }

    .mission-subcategories-all {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(58, 134, 255, 0.3);
    }

    .mission-subcategories-all:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(58, 134, 255, 0.4);
        color: white;
    }

    .mega-menu-column h4 {
        font-size: 14px;
        margin-bottom: 10px;
        color: var(--primary);
        padding-bottom: 6px;
        border-bottom: 2px solid var(--light-gray);
        font-weight: 700;
    }

    .mega-menu-column a {
        display: flex;
        align-items: center;
        padding: 5px 0;
        text-decoration: none;
        color: var(--dark);
        transition: var(--transition);
        font-size: 0.85rem;
    }

    .mega-menu-group + .mega-menu-group {
        margin-top: 14px;
    }

    .mega-menu-column a:hover {
        color: var(--primary);
        padding-left: 6px;
    }

    .see-all {
        margin-top: 10px;
        font-weight: 600;
        color: var(--secondary) !important;
        font-size: 0.8rem !important;
    }

    /* Compteurs dans le mega-menu */
    .mega-menu-column .sub-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 16px;
        padding: 0 4px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 0.6rem;
        font-weight: 600;
        border-radius: 6px;
        margin-right: 5px;
    }

    .mega-menu-column .cat-total {
        font-size: 0.7rem;
        color: var(--text-muted);
        font-weight: 500;
    }

    .mega-menu-group {
        margin-bottom: 8px;
    }

    .mega-menu-group:last-child {
        margin-bottom: 0;
    }

    /* =========================================
       BADGE TEST (IntÃ©grÃ© dans la nav)
       ========================================= */
    .test-badge {
        background: var(--danger);
        color: white;
        padding: 4px 10px;
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-right: 15px;
    }

    .test-badge a {
        color: white;
        text-decoration: underline;
        margin-left: 4px;
        font-weight: 400;
    }

    /* =========================================
       MEGA-MENU DROPDOWN - Publier une offre
       Style identique Ã  la colonne gauche principale
       ========================================= */
    .nav-item-mission {
        position: relative;
    }

    .mega-dropdown-mission {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        top: 100%;
        width: 320px;
        background: white;
        border-radius: 18px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 8px 25px rgba(58, 134, 255, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 0;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 2000;
        margin-top: 10px;
        overflow: visible;
    }

    .nav-item-mission:hover .mega-dropdown-mission {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        margin-top: 0;
    }

    /* FlÃ¨che pointant vers le bouton */
    .mega-dropdown-mission::before {
        content: '';
        position: absolute;
        top: -8px;
        left: 50%;
        transform: translateX(-50%) rotate(45deg);
        width: 16px;
        height: 16px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary) 100%);
        border-left: 1px solid rgba(0, 0, 0, 0.08);
        border-top: 1px solid rgba(0, 0, 0, 0.08);
    }

    /* Bridge pour garder le menu ouvert */
    .nav-item-mission::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        top: 100%;
        height: 15px;
        background: transparent;
    }

    .mega-dropdown-mission-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-radius: 18px 18px 0 0;
    }

    .mega-dropdown-mission-header i {
        font-size: 1.2rem;
        color: white;
    }

    .mega-dropdown-mission-header h5 {
        color: white;
        font-weight: 700;
        margin: 0;
        font-size: 1rem;
    }

    .mega-dropdown-mission-body {
        padding: 8px;
        max-height: 400px;
        overflow-y: auto;
    }

    .mega-dropdown-mission-body::-webkit-scrollbar {
        width: 5px;
    }

    .mega-dropdown-mission-body::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }

    .mega-dropdown-mission-body::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 10px;
    }

    /* CatÃ©gorie principale */
    .mission-cat-item {
        position: relative;
        margin-bottom: 2px;
    }

    .mission-cat-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 10px 14px;
        background: transparent;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .mission-cat-btn:hover {
        background: rgba(58, 134, 255, 0.08);
    }

    .mission-cat-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .mission-cat-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        color: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .mission-cat-info {
        text-align: left;
    }

    .mission-cat-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 2px;
    }

    .mission-cat-btn:hover .mission-cat-name {
        color: var(--primary);
    }

    .mission-cat-count {
        font-size: 0.75rem;
        color: var(--text-muted);
        font-weight: 500;
    }

    .mission-cat-count strong {
        color: var(--primary);
    }

    .mission-cat-arrow {
        font-size: 0.7rem;
        color: var(--text-muted);
        transition: transform 0.2s ease;
    }

    .mission-cat-btn:hover .mission-cat-arrow {
        color: var(--primary);
        transform: translateX(3px);
    }

    /* Panneau sous-catÃ©gories */
    .mission-subcats-panel {
        position: absolute;
        left: 100%;
        top: 0;
        width: 260px;
        margin-left: 8px;
        background: white;
        border-radius: 14px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 0;
        opacity: 0;
        pointer-events: none;
        visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s ease;
        z-index: 2100;
        overflow: hidden;
    }

    .mission-cat-item:hover .mission-subcats-panel {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    /* Bridge invisible entre catÃ©gorie et panneau */
    .mission-cat-item::after {
        content: '';
        position: absolute;
        right: -12px;
        top: 0;
        width: 16px;
        height: 100%;
        background: transparent;
    }

    .mission-subcats-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        background: rgba(58, 134, 255, 0.05);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .mission-subcats-header-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        color: white;
    }

    .mission-subcats-header h6 {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0;
    }

    .mission-subcats-list {
        display: flex;
        flex-direction: column;
        gap: 3px;
        padding: 10px;
        max-height: 280px;
        overflow-y: auto;
    }

    .mission-subcat-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 9px 12px;
        background: #f8fafc;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .mission-subcat-link:hover {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transform: translateX(3px);
    }

    .mission-subcat-link-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .mission-subcat-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: var(--primary);
    }

    .mission-subcat-name {
        font-size: 0.8rem;
        font-weight: 500;
        color: #4a5568;
    }

    .mission-subcat-link:hover .mission-subcat-name {
        color: var(--primary);
    }

    .mission-subcat-badge {
        min-width: 22px;
        height: 18px;
        padding: 0 5px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 0.65rem;
        font-weight: 600;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .mission-subcats-footer {
        padding: 10px;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    .mission-subcats-all {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        width: 100%;
        padding: 9px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .mission-subcats-all:hover {
        box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
        color: white;
    }

    /* =========================================
       Responsive Header
       ========================================= */
    @media (max-width: 1200px) {
        .mega-menu {
            width: 750px;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            padding: 20px 24px;
        }
    }

    @media (max-width: 992px) {
        .nav-links {
            overflow-x: auto;
            justify-content: center;
            padding: 10px 0;
        }
        
        .mega-menu {
            width: 600px;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            padding: 18px 20px;
            max-height: 400px;
        }
    }

    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            gap: 15px;
        }
        
        .header-search-container {
            width: 100%;
            max-width: 100%;
        }
        
        .user-name {
            display: none;
        }
        
        .mega-menu {
            width: calc(100vw - 30px);
            grid-template-columns: repeat(2, 1fr);
            left: 50% !important;
            transform: translate(-50%, 0) !important;
            max-height: 350px;
            gap: 12px;
            padding: 15px;
        }

        .nav-item-mission .mega-menu {
            left: 50% !important;
        }
    }

    @media (max-width: 480px) {
        .mega-menu {
            grid-template-columns: 1fr;
            max-height: 320px;
        }
    }

    /* =========================================
       Suite des styles existants...
       ========================================= */

    /* =========================================
       MAIN LAYOUT - Grille principale
       ========================================= */
    .main-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px;
    }

    .page-grid {
        display: grid;
        grid-template-columns: 1fr 280px;
        gap: 24px;
        max-width: 1400px;
        margin: 0 auto;
    }

    @media (max-width: 1200px) {
        .page-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 900px) {
        .page-grid {
            grid-template-columns: 1fr;
        }
        .sidebar-right {
            display: none;
        }
    }

    /* =========================================
       SIDEBAR GAUCHE - Mega Menu Pros
       ========================================= */
    .sidebar-left {
        position: sticky;
        top: 140px;
        height: fit-content;
    }

    .mega-menu-card {
        background: white;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        overflow: hidden;
    }

    .mega-menu-header {
        padding: 16px 20px;
        background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%);
        color: white;
    }

    .mega-menu-header h3 {
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .mega-menu-list {
        padding: 8px 0;
    }

    .mega-item {
        position: relative;
    }

    .mega-item-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 20px;
        background: none;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        text-align: left;
    }

    .mega-item-btn:hover {
        background: var(--bg-main);
    }

    .mega-item-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .mega-item-icon {
        width: 36px;
        height: 36px;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.875rem;
    }

    .mega-item-info h4 {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0 0 2px 0;
    }

    .mega-item-info span {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .mega-item-arrow {
        color: var(--text-muted);
        font-size: 0.75rem;
        transition: transform 0.2s;
    }

    .mega-item:hover .mega-item-arrow {
        transform: translateX(3px);
        color: var(--primary);
    }

    /* Sous-menu au survol */
    .mega-submenu {
        display: none;
        position: absolute;
        left: 100%;
        top: 0;
        width: 280px;
        background: white;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-lg);
        padding: 12px 0;
        z-index: 1000;
        margin-left: 8px;
    }

    .mega-item:hover .mega-submenu {
        display: block;
    }

    .mega-sub-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        color: var(--text-dark);
        text-decoration: none;
        font-size: 0.875rem;
        transition: all 0.2s;
    }

    .mega-sub-link:hover {
        background: var(--primary-light);
        color: var(--primary);
    }

    .mega-sub-link i {
        margin-right: 10px;
        width: 16px;
        color: var(--text-muted);
    }

    .mega-sub-link:hover i {
        color: var(--primary);
    }

    .mega-sub-count {
        background: var(--bg-main);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    /* =========================================
       FEED CENTRAL - Contenu principal
       ========================================= */
    .feed-main {
        min-width: 0;
    }

    /* Onglets de filtrage */
    .feed-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        background: white;
        padding: 8px;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
    }

    .feed-tab {
        flex: 1;
        padding: 12px 16px;
        border: none;
        background: none;
        border-radius: var(--radius-md);
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        text-align: center;
    }

    .feed-tab:hover {
        background: var(--bg-main);
        color: var(--text-dark);
    }

    .feed-tab.active {
        background: var(--primary);
        color: white;
    }

    .feed-tab i {
        margin-right: 6px;
    }

    /* Section avec titre */
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .section-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title i {
        color: var(--primary);
    }

    .section-link {
        font-size: 0.875rem;
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
    }

    .section-link:hover {
        text-decoration: underline;
    }

    /* Carrousel horizontal */
    .horizontal-scroll {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 12px;
        scroll-snap-type: x mandatory;
        scrollbar-width: none;
    }

    .horizontal-scroll::-webkit-scrollbar {
        display: none;
    }

    /* Carte d'annonce compacte (pour carrousel) */
    .ad-card-compact {
        flex: 0 0 280px;
        scroll-snap-align: start;
        background: white;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        overflow: hidden;
        transition: all 0.3s;
        text-decoration: none;
        display: block;
    }

    .ad-card-compact:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .ad-card-img {
        height: 140px;
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .ad-card-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .ad-card-img .placeholder-icon {
        font-size: 2rem;
        color: var(--primary);
        opacity: 0.5;
    }

    .ad-badge-type {
        position: absolute;
        top: 10px;
        left: 10px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .ad-badge-type.offre {
        background: var(--secondary);
        color: white;
    }

    .ad-badge-type.demande {
        background: var(--accent);
        color: white;
    }

    .ad-card-body {
        padding: 14px;
    }

    .ad-card-category {
        font-size: 0.75rem;
        color: var(--primary);
        font-weight: 500;
        margin-bottom: 6px;
    }

    .ad-card-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 8px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .ad-card-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .ad-card-price {
        font-weight: 700;
        color: var(--secondary);
    }

    .ad-card-location {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    /* Grille d'annonces principales */
    .ads-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    @media (max-width: 700px) {
        .ads-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Carte d'annonce standard */
    .ad-card {
        background: white;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        overflow: hidden;
        transition: all 0.3s;
    }

    .ad-card:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow-md);
    }

    .ad-card-header {
        display: flex;
        align-items: center;
        padding: 14px;
        gap: 12px;
        border-bottom: 1px solid var(--border);
    }

    .ad-user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: var(--primary);
        overflow: hidden;
    }

    .ad-user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .ad-user-info h4 {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0;
    }

    .ad-user-info span {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .ad-card-content {
        padding: 14px;
    }

    .ad-card-content h3 {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 8px;
    }

    .ad-card-content h3 a {
        color: inherit;
        text-decoration: none;
    }

    .ad-card-content h3 a:hover {
        color: var(--primary);
    }

    .ad-card-content p {
        font-size: 0.875rem;
        color: var(--text-muted);
        line-height: 1.5;
        margin-bottom: 12px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .ad-card-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 12px;
    }

    .ad-tag {
        padding: 4px 10px;
        background: var(--bg-main);
        border-radius: 20px;
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .ad-tag.urgent {
        background: #fef2f2;
        color: var(--danger);
    }

    .ad-card-image {
        height: 180px;
        background: var(--bg-main);
        margin: 0 14px 14px;
        border-radius: var(--radius-md);
        overflow: hidden;
    }

    .ad-card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .ad-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px;
        border-top: 1px solid var(--border);
        background: var(--bg-main);
    }

    .ad-price {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--secondary);
    }

    .ad-actions {
        display: flex;
        gap: 8px;
    }

    .btn-action {
        padding: 8px 14px;
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        display: flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }

    .btn-action.primary {
        background: var(--primary);
        color: white;
    }

    .btn-action.primary:hover {
        background: var(--primary-dark);
    }

    .btn-action.secondary {
        background: white;
        color: var(--text-muted);
        border: 1px solid var(--border);
    }

    .btn-action.secondary:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    /* =========================================
       TOP PROS WIDGET
       ========================================= */
    .toppros-widget {
        background: #ffffff;
        border: 1px solid rgba(15,23,42,.10);
        border-radius: 18px;
        box-shadow: 0 10px 26px rgba(2,6,23,.06);
        overflow: hidden;
        margin-top: 20px;
    }
    
    .toppros__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 14px 10px;
    }
    
    .toppros__title {
        margin: 0;
        font-size: 14px;
        font-weight: 800;
        letter-spacing: -0.01em;
    }
    
    .toppros__score {
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 800;
        font-size: 13px;
        color: var(--text-main);
    }
    
    .toppros__star {
        color: #f59e0b;
        font-size: 14px;
        line-height: 1;
    }
    
    .toppros__more {
        margin-left: 4px;
        width: 22px;
        height: 22px;
        border-radius: 8px;
        border: 1px solid rgba(15,23,42,.10);
        background: #fff;
        color: #334155;
        cursor: pointer;
        display: grid;
        place-items: center;
        line-height: 1;
        transition: transform .15s ease, box-shadow .15s ease;
        text-decoration: none;
    }
    
    .toppros__more:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 18px rgba(2,6,23,.08);
    }
    
    .toppros__list {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .toppros__divider {
        height: 1px;
        background: rgba(15,23,42,.08);
        margin: 0 14px;
    }
    
    .pro-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
    }
    
    .pro__avatar {
        width: 40px;
        height: 40px;
        border-radius: 999px;
        object-fit: cover;
        border: 1px solid rgba(15,23,42,.10);
        flex: 0 0 auto;
        background: linear-gradient(135deg, #7c3aed, #9333ea);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 0.9rem;
    }
    
    .pro__avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 999px;
    }
    
    .pro__main {
        flex: 1;
        min-width: 0;
    }
    
    .pro__row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 10px;
    }
    
    .pro__name {
        font-weight: 800;
        font-size: 13px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--text-main);
        text-decoration: none;
    }
    
    .pro__name:hover {
        color: #7c3aed;
    }
    
    .pro__job {
        font-size: 12px;
        color: #64748b;
        white-space: nowrap;
    }
    
    .pro__rating {
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .stars-bar {
        position: relative;
        width: 72px;
        height: 12px;
        display: inline-block;
        border-radius: 999px;
        overflow: hidden;
        border: 1px solid rgba(245,158,11,.25);
        background: rgba(245,158,11,.12);
    }
    
    .stars-bar__fill {
        position: absolute;
        inset: 0;
        width: 0%;
        background: linear-gradient(90deg, rgba(245,158,11,.95), rgba(245,158,11,.75));
    }
    
    .pro__ratingText {
        font-size: 12px;
        font-weight: 800;
        color: #334155;
    }
    
    .pro__actions {
        position: relative;
        flex: 0 0 auto;
    }
    
    .pro__cta {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        border: 1px solid rgba(15,23,42,.10);
        background: #fff;
        cursor: pointer;
        display: grid;
        place-items: center;
        transition: transform .15s ease, box-shadow .15s ease;
        padding: 0;
    }
    
    .pro__cta:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 18px rgba(2,6,23,.08);
    }
    
    .pro__ctaIcon {
        font-size: 14px;
        color: #334155;
        line-height: 1;
    }
    
    .pro__menu {
        position: absolute;
        top: 38px;
        right: 0;
        width: 210px;
        background: #fff;
        border: 1px solid rgba(15,23,42,.12);
        border-radius: 14px;
        box-shadow: 0 18px 40px rgba(2,6,23,.14);
        padding: 6px;
        display: none;
        z-index: 50;
        transform-origin: top right;
    }
    
    .pro__menu::before {
        content: "";
        position: absolute;
        top: -7px;
        right: 10px;
        width: 12px;
        height: 12px;
        background: #fff;
        border-left: 1px solid rgba(15,23,42,.12);
        border-top: 1px solid rgba(15,23,42,.12);
        transform: rotate(45deg);
    }
    
    .pro__menu.open {
        display: block;
        animation: fadeScalePro .18s ease;
    }
    
    @keyframes fadeScalePro {
        from { opacity: 0; transform: translateY(-6px) scale(.96); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    
    .pro__menuItem {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        font-size: 13px;
        font-weight: 800;
        color: var(--text-main);
        text-decoration: none;
        border-radius: 10px;
        transition: background .15s ease, transform .15s ease;
    }
    
    .pro__menuItem:hover {
        background: #f1f5f9;
        transform: translateX(2px);
    }
    
    .pro__menuSep {
        height: 1px;
        background: rgba(15,23,42,.08);
        margin: 6px 8px;
    }
    
    .pro__menuItem--primary {
        background: linear-gradient(180deg, rgba(37,99,235,.98), rgba(29,78,216,.98));
        color: #fff;
        border: 1px solid rgba(37,99,235,.25);
        box-shadow: 0 12px 22px rgba(37,99,235,.18);
    }
    
    .pro__menuItem--primary:hover {
        background: linear-gradient(180deg, rgba(29,78,216,.98), rgba(30,64,175,.98));
        color: #fff;
    }

    /* =========================================
       SIDEBAR DROITE - Widgets
       ========================================= */
    .sidebar-right {
        position: relative;
        height: calc(100vh - 220px);
        overflow-y: auto;
        overflow-x: hidden;
    }

    .promo-card { background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%); border-radius: 20px; padding: 25px; text-align: center; margin-bottom: 20px; }
    .promo-card i { font-size: 2.5rem; color: white; margin-bottom: 15px; }
    .promo-card h5 { color: white; margin-bottom: 10px; }
    .promo-card p { color: rgba(255,255,255,0.8); font-size: 0.9rem; margin-bottom: 15px; }
    .promo-btn { background: white; color: #7c3aed; border: none; padding: 10px 25px; border-radius: 10px; font-weight: 600; }
    .promo-btn:hover { background: #f8f9fa; color: #7c3aed; }

    .sidebar-right .widgets-wrapper {
        position: sticky !important;
        top: 90px !important;
    }

    .widget {
        background: var(--bg-card) !important;
        border-radius: var(--radius-md) !important;
        padding: 20px !important;
        margin-bottom: 20px !important;
        border: 1px solid var(--border-color) !important;
        box-shadow: var(--shadow-sm) !important;
    }

    .dashboard-summary {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
        border: none !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08) !important;
    }

    .dashboard-summary h3 {
        font-size: 1rem !important;
        font-weight: 700 !important;
        color: var(--text-main) !important;
        margin-bottom: 20px !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }

    .dashboard-summary h3::before {
        content: 'ðŸ“Š';
        font-size: 1.2rem;
    }

    .stats-list {
        list-style: none !important;
        padding: 0 !important;
        margin: 0 0 20px 0 !important;
    }

    .stats-list li {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 12px 0 !important;
        border-bottom: 1px solid rgba(0,0,0,0.05) !important;
        font-size: 0.9rem !important;
    }

    .stats-list li:last-child {
        border-bottom: none !important;
    }

    .stats-list-link {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        width: 100% !important;
        text-decoration: none !important;
        padding: 4px 8px !important;
        margin: -4px -8px !important;
        border-radius: 8px !important;
        transition: background-color 0.2s, transform 0.2s !important;
    }
    
    .stats-list-link:hover {
        background-color: rgba(79, 70, 229, 0.08) !important;
        transform: translateX(4px) !important;
    }
    
    .stats-list-link:hover .stat-label {
        color: var(--primary-color) !important;
    }

    .stat-label {
        color: var(--text-muted) !important;
        font-weight: 500 !important;
    }

    .stat-value {
        font-weight: 700 !important;
        color: var(--primary-color) !important;
        background: rgba(79, 70, 229, 0.1) !important;
        padding: 4px 12px !important;
        border-radius: 20px !important;
        font-size: 0.85rem !important;
    }

    .btn-block {
        display: block !important;
        width: 100% !important;
        text-align: center !important;
    }

    .btn-secondary {
        background: #E5E7EB !important;
        color: var(--text-main) !important;
        padding: 10px 16px !important;
        border-radius: var(--radius-md) !important;
        font-weight: 600 !important;
        font-size: 0.9rem !important;
        text-decoration: none !important;
        transition: background 0.2s !important;
    }

    .btn-secondary:hover {
        background: #D1D5DB !important;
    }

    .subscription-box {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
        color: white !important;
        border: none !important;
    }

    .subscription-box .sub-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin-bottom: 10px !important;
    }

    .subscription-box p {
        font-size: 0.85rem !important;
        color: #94A3B8 !important;
        margin-bottom: 15px !important;
    }

    .btn-gold {
        background: var(--accent-gold) !important;
        color: white !important;
        padding: 10px 16px !important;
        border-radius: var(--radius-md) !important;
        font-weight: 700 !important;
        font-size: 0.9rem !important;
        text-decoration: none !important;
        transition: background 0.2s !important;
    }

    .btn-gold:hover {
        background: #D97706 !important;
    }

    .ad-space {
        padding: 0 !important;
        overflow: hidden !important;
        position: relative !important;
    }

    .ad-label {
        position: absolute !important;
        top: 10px !important;
        right: 10px !important;
        background: rgba(0,0,0,0.5) !important;
        color: white !important;
        font-size: 0.6rem !important;
        padding: 2px 6px !important;
        border-radius: 4px !important;
    }

    .ad-content img {
        width: 100% !important;
        display: block !important;
    }

    .ad-content h4 {
        margin-top: 10px !important;
        font-size: 0.95rem !important;
        padding: 0 15px !important;
    }

    .ad-content p {
        margin-bottom: 15px !important;
        font-size: 0.85rem !important;
        color: var(--text-muted);
        padding: 0 15px;
    }

    .widget-cta .btn-cta {
        display: inline-block;
        background: white;
        color: var(--primary);
        padding: 10px 24px;
        border-radius: var(--radius-md);
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }

    .widget-cta .btn-cta:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    /* Widget Pros */
    .pro-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .pro-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px;
        border-radius: var(--radius-md);
        transition: all 0.2s;
        text-decoration: none;
    }

    .pro-item:hover {
        background: var(--bg-main);
    }

    .pro-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: var(--primary-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: var(--primary);
        overflow: hidden;
    }

    .pro-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .pro-info {
        flex: 1;
        min-width: 0;
    }

    .pro-info h5 {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0 0 2px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pro-info span {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .pro-rating {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 0.8rem;
    }

    .pro-rating i {
        color: var(--accent);
    }

    /* Widget Stats */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .stat-item {
        text-align: center;
        padding: 12px;
        background: var(--bg-main);
        border-radius: var(--radius-md);
        text-decoration: none;
        transition: all 0.2s;
    }

    .stat-item:hover {
        background: var(--primary-light);
    }

    .stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary);
    }

    .stat-label {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 2px;
    }

    /* =========================================
       BOUTON FLOTTANT - CrÃ©er une annonce
       ========================================= */
    .fab-create {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 60px;
        height: 60px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 1.5rem;
        cursor: pointer;
        box-shadow: var(--shadow-lg);
        transition: all 0.3s;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .fab-create:hover {
        background: var(--primary-dark);
        transform: scale(1.1);
        color: white;
    }

    /* =========================================
       BADGE TEST
       ========================================= */
    .test-badge {
        position: fixed;
        top: 80px;
        right: 20px;
        background: var(--danger);
        color: white;
        padding: 8px 16px;
        border-radius: var(--radius-md);
        font-size: 0.8rem;
        font-weight: 600;
        z-index: 1001;
        box-shadow: var(--shadow-md);
    }

    .test-badge a {
        color: white;
        text-decoration: underline;
        margin-left: 8px;
    }

    /* =========================================
       RESPONSIVE
       ========================================= */
    @media (max-width: 768px) {
        .hero-search {
            padding: 24px 0;
        }

        .hero-title {
            font-size: 1.25rem;
        }

        .search-container {
            flex-direction: column;
        }

        .search-btn {
            width: 100%;
            justify-content: center;
        }

        .main-container {
            padding: 16px;
        }

        .feed-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
        }

        .feed-tab {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .ads-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
</head>
<body class="device-{{ $deviceType ?? 'desktop' }}{{ ($isMobile ?? false) ? ' is-mobile' : '' }}{{ ($isTablet ?? false) ? ' is-tablet' : '' }}">
    {{-- Variables JS pour détection appareil côté client --}}
    <script>
        window.__device = {
            type: '{{ $deviceType ?? "desktop" }}',
            isMobile: {{ ($isMobile ?? false) ? 'true' : 'false' }},
            isTablet: {{ ($isTablet ?? false) ? 'true' : 'false' }},
            isDesktop: {{ ($isDesktop ?? true) ? 'true' : 'false' }},
            browser: '{{ $deviceBrowser ?? "unknown" }}',
            platform: '{{ $devicePlatform ?? "unknown" }}'
        };
    </script>
    <div id="app">
        <header class="header-modern sticky-top">
            <div class="container-fluid px-4">
                <div class="d-flex justify-content-between align-items-center h-100">
                    <!-- Logo à gauche -->
                    <div class="d-flex align-items-center gap-2 mobile-brand-group flex-grow-1" style="min-width:0;">
                        <a class="navbar-brand-modern me-2" href="{{ Auth::check() ? route('feed') : url('/') }}">
                            <div class="brand-logo">P</div>
                            <span class="brand-text d-none d-sm-inline">ProxiPro</span>
                        </a>
                        <a href="{{ route('feed') }}" class="header-home-link d-lg-none">
                            <i class="fas fa-home"></i>
                            <span>Accueil</span>
                        </a>
                    </div>
                    @auth
                    @if(!request()->routeIs('feed.test'))
                    <!-- Navigation principale + Contact à droite -->
                    <div class="d-none d-lg-flex align-items-center gap-1 ms-auto">
                        <a href="{{ route('feed') }}" class="header-nav-btn {{ request()->routeIs('feed') ? 'active' : '' }}">
                            <i class="fas fa-home"></i><span>Accueil</span>
                        </a>
                        <a href="{{ route('home') }}" class="header-nav-btn {{ request()->routeIs('home') ? 'active' : '' }}">
                            <i class="fas fa-th-large"></i><span>Tableau de bord</span>
                        </a>
                        <a href="{{ route('ads.create') }}" class="header-nav-btn header-nav-btn-primary">
                            <i class="fas fa-plus-circle"></i><span>Publier une offre</span>
                        </a>
                        <a href="{{ route('contact.index') }}" class="header-nav-btn">
                            <i class="fas fa-headset"></i><span>Contact</span>
                        </a>
                    </div>
                    <!-- Actions droite -->
                    <div class="d-flex align-items-center gap-3 mobile-actions-group ms-2">

                        <!-- Bouton Devenir Prestataire / Espace Pro -->
                        @php
                            $currentUser = Auth::user();
                            $isOAuthWithoutProfile = $currentUser->isOAuthUser() && !$currentUser->profile_completed && !$currentUser->is_service_provider;
                            $isRegularParticulier = !$currentUser->isOAuthUser() && (!$currentUser->user_type || $currentUser->user_type === 'particulier') && !$currentUser->is_service_provider;
                            $isActiveParticulierProvider = $currentUser->is_service_provider && (!$currentUser->user_type || $currentUser->user_type === 'particulier');
                            $hasProSpace = $currentUser->hasCompletedProOnboarding() || $currentUser->user_type === 'professionnel' || $currentUser->isProfessionnel();
                        @endphp
                        @if($hasProSpace)
                            <div class="mobile-header-item">
                                <a href="{{ route('pro.dashboard') }}" class="btn-provider-badge header-mobile-action" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border: none;" title="Espace Pro">
                                    <i class="fas fa-briefcase"></i>
                                    <span class="d-none d-sm-inline">Espace Pro</span>
                                </a>
                                <span class="mobile-header-label d-sm-none">Pro</span>
                            </div>
                        @elseif($isOAuthWithoutProfile)
                            <div class="mobile-header-item">
                                <button type="button" class="btn-become-provider header-mobile-action" data-bs-toggle="modal" data-bs-target="#becomeProviderOAuthModal">
                                    <i class="fas fa-rocket"></i>
                                    <span class="d-none d-sm-inline">Devenir prestataire</span>
                                </button>
                                <span class="mobile-header-label d-sm-none">Pro</span>
                            </div>
                        @elseif($isRegularParticulier)
                            <div class="mobile-header-item">
                                <button type="button" class="btn-become-provider header-mobile-action" data-bs-toggle="modal" data-bs-target="#becomeProviderModal">
                                    <i class="fas fa-user-plus"></i>
                                    <span class="d-none d-sm-inline">Devenir prestataire</span>
                                </button>
                                <span class="mobile-header-label d-sm-none">Pro</span>
                            </div>
                        @elseif($isActiveParticulierProvider)
                            <div class="mobile-header-item">
                                <button type="button" class="btn-provider-badge header-mobile-action" data-bs-toggle="modal" data-bs-target="#becomeProviderModal" title="Gérer mes services">
                                    <i class="fas fa-check-circle"></i>
                                    <span class="d-none d-sm-inline">Prestataire</span>
                                </button>
                                <span class="mobile-header-label d-sm-none">Pro</span>
                            </div>
                        @endif

                        <!-- Séparateur -->
                        <div class="header-separator d-none d-md-block"></div>

                        <!-- Groupe icônes : Messages, Points, Alertes boost, Notifications -->
                        <div class="d-flex align-items-center gap-2">
                            <!-- Messages -->
                            <a href="{{ route('messages.index') }}" class="nav-messages-btn position-relative d-none d-md-flex" title="Messages">
                                <i class="fas fa-envelope"></i>
                                <span class="d-none d-md-inline">Messages</span>
                                @php
                                    $unreadCount = Auth::user()->unreadMessagesCount();
                                @endphp
                                @if($unreadCount > 0)
                                    <span class="messages-counter">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                                @endif
                            </a>

                            <!-- Points -->
                            <a href="{{ route('points.dashboard') }}" class="header-points-badge d-none d-md-flex" title="Mes points">
                                <i class="fas fa-coins"></i>
                                <span class="header-points-value">{{ Auth::user()->available_points ?? 0 }}</span>
                            </a>

                            <!-- Boost Expiration Alerts -->
                            @php
                                $boostAlerts = \App\Http\Controllers\BoostController::getExpiringAlerts(Auth::user());
                            @endphp
                            @if(count($boostAlerts) > 0)
                            <div class="dropdown mobile-header-item">
                                <button class="header-alert-btn header-mobile-action position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Alertes boost">
                                    <i class="fas fa-bell"></i>
                                    <span class="alert-counter">{{ count($boostAlerts) }}</span>
                                </button>
                                <span class="mobile-header-label d-md-none">Alertes</span>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-modern mt-2" style="min-width: 320px; max-height: 400px; overflow-y: auto;">
                                    <li class="px-3 py-2 border-bottom">
                                        <strong style="color: #1e293b; font-size: 0.9rem;"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Alertes d'expiration</strong>
                                    </li>
                                    @foreach($boostAlerts as $alert)
                                    <li>
                                        <a class="dropdown-item py-2 px-3" href="{{ route('boost.show', $alert['ad_id']) }}" style="white-space: normal; line-height: 1.4;">
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="{{ $alert['icon'] }}" style="color: {{ $alert['color'] }}; margin-top: 3px;"></i>
                                                <div>
                                                    <div style="font-size: 0.85rem; color: #1e293b; font-weight: 600;">{{ Str::limit($alert['ad_title'], 30) }}</div>
                                                    <small style="color: #ef4444;">{{ $alert['message'] }}</small>
                                                    <div class="mt-1"><span class="badge bg-warning text-dark" style="font-size: 0.7rem;">Prolonger</span></div>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            <!-- Notification Bell Dropdown -->
                            @php
                                $navVerification = \App\Models\IdentityVerification::where('user_id', Auth::id())->latest()->first();
                                $dbNotifications = Auth::user()->notifications()->latest()->take(20)->get();
                                $unreadNotifCount = Auth::user()->unreadNotifications()->count();
                                $navNotifications = collect();
                                if ($navVerification) {
                                    $verifStatus = $navVerification->status;
                                    if ($verifStatus === 'returned') {
                                        $rejectedCount = collect(['document_front_status','document_back_status','selfie_status','professional_document_status'])
                                            ->filter(fn($f) => $navVerification->$f === 'rejected')->count();
                                        $navNotifications->push((object)[
                                            'id' => 'verif-status', 'type' => 'verification_returned',
                                            'icon' => 'fas fa-exclamation-triangle', 'color' => '#f59e0b', 'bg' => '#fef3c7',
                                            'title' => 'Corrections requises',
                                            'message' => $navVerification->admin_message ?? ($rejectedCount . ' document(s) à corriger'),
                                            'url' => route('verification.index'), 'time' => $navVerification->updated_at,
                                            'is_read' => false, 'is_live' => true,
                                        ]);
                                    } elseif ($verifStatus === 'pending') {
                                        $navNotifications->push((object)[
                                            'id' => 'verif-status', 'type' => 'verification_pending',
                                            'icon' => 'fas fa-clock', 'color' => '#3b82f6', 'bg' => '#dbeafe',
                                            'title' => 'Vérification en cours',
                                            'message' => 'Votre demande est en cours d\'examen par notre équipe.',
                                            'url' => route('verification.index'), 'time' => $navVerification->updated_at,
                                            'is_read' => true, 'is_live' => true,
                                        ]);
                                    } elseif ($verifStatus === 'rejected') {
                                        $navNotifications->push((object)[
                                            'id' => 'verif-status', 'type' => 'verification_rejected',
                                            'icon' => 'fas fa-times-circle', 'color' => '#ef4444', 'bg' => '#fee2e2',
                                            'title' => 'Vérification refusée',
                                            'message' => $navVerification->rejection_reason ?? 'Votre demande de vérification a été refusée.',
                                            'url' => route('verification.index'), 'time' => $navVerification->updated_at,
                                            'is_read' => false, 'is_live' => true,
                                        ]);
                                    }
                                }
                                foreach ($dbNotifications as $notif) {
                                    $data = $notif->data;
                                    $navNotifications->push((object)[
                                        'id' => $notif->id, 'type' => $data['type'] ?? 'general',
                                        'icon' => $data['icon'] ?? 'fas fa-bell', 'color' => $data['color'] ?? '#6366f1',
                                        'bg' => ($notif->read_at ? '#f8fafc' : '#eff6ff'),
                                        'title' => $data['title'] ?? 'Notification',
                                        'message' => \Illuminate\Support\Str::limit($data['message'] ?? '', 80),
                                        'url' => $data['action_url'] ?? '#', 'time' => $notif->created_at,
                                        'is_read' => !is_null($notif->read_at), 'is_live' => false, 'db_id' => $notif->id,
                                    ]);
                                }
                                $liveUnread = $navNotifications->filter(fn($n) => $n->is_live && !$n->is_read)->count();
                                $totalUnread = $liveUnread + $unreadNotifCount;
                            @endphp
                            <div class="dropdown mobile-header-item" id="notificationDropdown">
                                <button class="header-notif-btn header-mobile-action position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                                    <i class="fas fa-bell"></i>
                                    @if($totalUnread > 0)
                                        <span class="notif-counter">{{ $totalUnread > 99 ? '99+' : $totalUnread }}</span>
                                    @endif
                                </button>
                                <span class="mobile-header-label d-md-none">Notif</span>
                                <div class="dropdown-menu dropdown-menu-end notif-dropdown-menu mt-2">
                                    <div class="notif-dropdown-header">
                                        <span class="notif-dropdown-title">
                                            <i class="fas fa-bell me-1"></i> Notifications
                                            @if($totalUnread > 0)
                                                <span class="badge bg-primary ms-1" style="font-size: 0.65rem;">{{ $totalUnread }}</span>
                                            @endif
                                        </span>
                                        @if($unreadNotifCount > 0)
                                        <button type="button" class="notif-mark-all-btn" id="markAllReadBtn" title="Tout marquer comme lu">
                                            <i class="fas fa-check-double"></i> Tout lire
                                        </button>
                                        @endif
                                    </div>
                                    <div class="notif-dropdown-body">
                                        @if($navNotifications->isEmpty())
                                            <div class="notif-empty">
                                                <i class="far fa-bell-slash"></i>
                                                <p>Aucune notification</p>
                                            </div>
                                        @else
                                            @foreach($navNotifications as $notifItem)
                                            <a href="{{ $notifItem->url }}"
                                               class="notif-dropdown-item {{ !$notifItem->is_read ? 'notif-unread' : '' }}"
                                               @if(!$notifItem->is_read && isset($notifItem->db_id)) data-notif-id="{{ $notifItem->db_id }}" @endif
                                            >
                                                <div class="notif-item-icon" style="background: {{ $notifItem->bg }}; color: {{ $notifItem->color }};"><i class="{{ $notifItem->icon }}"></i></div>
                                                <div class="notif-item-content">
                                                    <div class="notif-item-title">{{ $notifItem->title }}</div>
                                                    <div class="notif-item-message">{{ $notifItem->message }}</div>
                                                    <div class="notif-item-time"><i class="far fa-clock"></i> {{ $notifItem->time->diffForHumans() }}</div>
                                                </div>
                                                @if(!$notifItem->is_read)
                                                    <span class="notif-unread-dot" style="background: {{ $notifItem->color }};"></span>
                                                @endif
                                            </a>
                                            @endforeach
                                        @endif
                                    </div>
                                    @if($navNotifications->count() > 3)
                                    <div class="notif-dropdown-footer">
                                        <a href="{{ route('verification.index') }}">Voir tout</a>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Séparateur -->
                        <div class="header-separator d-none d-md-block"></div>

                        <!-- User Dropdown -->
                        <div class="dropdown">
                            <button class="user-menu-btn d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                @if(Auth::user()->avatar)
                                    <img src="{{ storage_url(Auth::user()->avatar) }}" alt="Avatar" class="user-avatar" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                @else
                                    <div class="user-avatar-placeholder" style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #9333ea); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 700; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="d-none d-md-flex flex-column align-items-start">
                                    <span class="user-name-text">{{ Str::limit(Auth::user()->name, 15) }}</span>
                                    <span class="user-type-badge">{{ ucfirst(Auth::user()->user_type ?? 'Utilisateur') }}</span>
                                </div>
                                <i class="fas fa-chevron-down dropdown-chevron"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-modern mt-2" style="min-width: 240px;">
                                <li class="px-3 py-2 border-bottom">
                                    <div class="d-flex align-items-center">
                                        @if(Auth::user()->avatar)
                                            <img src="{{ storage_url(Auth::user()->avatar) }}" alt="" class="user-avatar me-3" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid #f1f5f9; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                        @else
                                            <div class="user-avatar-placeholder me-3" style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #7c3aed, #9333ea); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; font-weight: 700; border: 2px solid #f1f5f9; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                                        @endif
                                        <div>
                                            <div style="font-weight: 600; color: #1e293b;">{{ Auth::user()->name }}</div>
                                            <small style="color: #64748b;">{{ Auth::user()->email }}</small>
                                        </div>
                                    </div>
                                </li>
                                <li><a class="dropdown-item dropdown-item-modern" href="{{ route('profile.show') }}"><i class="fas fa-user" style="color: var(--primary);"></i>Mon Profil</a></li>
                                <li><a class="dropdown-item dropdown-item-modern" href="{{ route('home') }}"><i class="fas fa-th-large text-secondary"></i>Tableau de bord</a></li>
                                <li><a class="dropdown-item dropdown-item-modern" href="{{ route('ads.index') }}"><i class="fas fa-bullhorn" style="color: #6366f1;"></i>Annonces</a></li>
                                <li><a class="dropdown-item dropdown-item-modern" href="{{ route('messages.index') }}"><i class="fas fa-envelope" style="color: var(--accent);"></i>Messages @if($unreadCount > 0)<span class="badge bg-danger ms-auto" style="font-size: 0.65rem;">{{ $unreadCount }}</span>@endif</a></li>
                                <li><a class="dropdown-item dropdown-item-modern" href="{{ route('points.dashboard') }}"><i class="fas fa-coins text-warning"></i>Mes Points <span class="badge bg-success ms-auto">{{ Auth::user()->available_points ?? 0 }}</span></a></li>
                                <li><hr class="dropdown-divider my-2"></li>
                                <li><a class="dropdown-item dropdown-item-modern" href="{{ route('contact.index') }}"><i class="fas fa-headset" style="color: #3b82f6;"></i>Contact</a></li>
                                @if(Auth::user()->isProfessionnel() || Auth::user()->isServiceProvider() || Auth::user()->hasCompletedProOnboarding())
                                <li><hr class="dropdown-divider my-2"></li>
                                <li style="padding: 4px 16px 2px;"><small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Espace Pro</small></li>
                                <li><a class="dropdown-item dropdown-item-modern" href="{{ route('pro.dashboard') }}"><i class="fas fa-briefcase" style="color: #6366f1;"></i>Tableau de bord Pro</a></li>
                                <li><a class="dropdown-item dropdown-item-modern" href="{{ route('pro.quotes.create') }}"><i class="fas fa-file-alt" style="color: #f59e0b;"></i>Nouveau devis</a></li>
                                <li><a class="dropdown-item dropdown-item-modern" href="{{ route('pro.invoices.create') }}"><i class="fas fa-file-invoice-dollar" style="color: #ec4899;"></i>Nouvelle facture</a></li>
                                <li><a class="dropdown-item dropdown-item-modern" href="{{ route('pro.clients') }}"><i class="fas fa-users" style="color: #3b82f6;"></i>Mes clients</a></li>
                                @endif
                                <li><hr class="dropdown-divider my-2"></li>
                                <li><a class="dropdown-item dropdown-item-modern" href="{{ route('pricing.index') }}"><i class="fas fa-crown text-warning"></i>Tarifs</a></li>
                                <li><a class="dropdown-item dropdown-item-modern" href="{{ route('settings.index') }}"><i class="fas fa-cog text-secondary"></i>Paramètres</a></li>
                                @if(Auth::user()->role === 'admin')
                                <li><hr class="dropdown-divider my-2"></li>
                                <li><a class="dropdown-item dropdown-item-modern" href="{{ route('admin.dashboard') }}"><i class="fas fa-shield-alt text-danger"></i>Administration</a></li>
                                @endif
                                <li><hr class="dropdown-divider my-2"></li>
                                <li>
                                    <a class="dropdown-item dropdown-item-modern text-danger" href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt"></i>Déconnexion
                                    </a>
                                </li>
                            </ul>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                        </div>
                    </div>
                    @endif
                    @endauth
                </div>
            </div>
        </header>

        {{-- Navigation secondaire supprimée --}}

        {{-- Sidebar de navigation --}}
        @auth
            @if(!request()->routeIs('feed') && !request()->routeIs('feed.test') && !request()->routeIs('profile.*') && !request()->is('/') && !request()->is('ads*'))
                @include('partials.sidebar')
            @endif
        @endauth

        <main class="@auth @if(!request()->routeIs('feed') && !request()->routeIs('feed.test') && !request()->routeIs('profile.*') && !request()->is('/') && !request()->is('ads*')) main-content-with-sidebar @endif @endauth">
            @yield('content')
        </main>
    </div>

    <style>

    /* =========================================
       PAGE TEST - ARCHITECTURE MODERNE
       Style ServicesPro
       ========================================= */
    
    :root {
        /* ðŸŽ¨ Palette ServicesPro */
        --primary: #3a86ff;
        --primary-dark: #2667cc;
        --primary-hover: #2667cc;
        --primary-light: rgba(58, 134, 255, 0.1);
        --secondary: #8338ec;
        --accent: #ffbe0b;
        --success: #06d6a0;
        --warning: #ffbe0b;
        --danger: #ef4444;
        
        /* Fonds - Style ServicesPro */
        --light: #f8f9fa;
        --dark: #212529;
        --gray: #6c757d;
        --light-gray: #e9ecef;
        --bg-main: #f5f7fb;
        --bg-body: #f5f7fb;
        --bg-card: #ffffff;
        
        /* Texte */
        --text-main: #212529;
        --text-dark: #212529;
        --text-muted: #6c757d;
        
        /* Bordures */
        --border: #e9ecef;
        --border-subtle: #e9ecef;
        --border-color: #e9ecef;
        --border-radius: 12px;
        
        /* Ombres - Style ServicesPro */
        --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        --shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.12);
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        
        /* Rayons */
        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 16px;
        --radius-xl: 24px;
        
        --transition: all 0.3s ease;
    }

    body {
        background-color: #f5f7fb;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        color: var(--dark);
        line-height: 1.6;
    }

    /* Header height override for test page */
    .header-modern {
        height: 90px !important;
        min-height: 90px !important;
    }

    /* =========================================
       HEADER PRINCIPAL - Style amÃ©liorÃ©
       Plus large avec effet d'ombre sÃ©duisant
       ========================================= */
    .header-modern {
        background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%) !important;
        box-shadow: 0 2px 20px -4px rgba(58, 134, 255, 0.15), 0 4px 12px -2px rgba(0, 0, 0, 0.08) !important;
        border-bottom: 1px solid rgba(58, 134, 255, 0.1) !important;
        height: 90px !important;
        z-index: 1001 !important;
    }

    .header-modern .container-fluid {
        max-width: 1600px !important;
        margin: 0 auto;
        padding: 0 32px !important;
    }

    .header-modern .navbar-brand-modern .brand-logo {
        width: 42px;
        height: 42px;
        font-size: 1.2rem;
        box-shadow: 0 4px 15px rgba(58, 134, 255, 0.35);
    }

    .header-modern .navbar-brand-modern .brand-text {
        font-size: 1.4rem;
    }

    /* Header search (test page) */
    .header-search-inline {
        flex: 1;
        display: flex;
        justify-content: center;
    }
    .header-search-inline .search-card {
        display: flex;
        align-items: center;
        gap: 10px;
        background: white;
        border: 1px solid var(--border-subtle);
        border-radius: 14px;
        padding: 8px 10px;
        box-shadow: var(--shadow-sm);
        max-width: 680px; /* Ã‰largi de 520px Ã  680px */
        width: 100%;
    }
    .header-search-inline .search-input-group {
        position: relative;
        flex: 1 1 0;
        min-width: 0;
        border-radius: 12px;
    }
    .header-search-inline .search-input-group input {
        width: 100%;
        border: none;
        background: transparent;
        padding: 10px 12px 10px 36px;
        font-size: 0.9rem;
        color: var(--text-main);
        border-radius: 12px;
    }
    .header-search-inline .search-input-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.95rem;
        pointer-events: none;
    }
    .header-search-inline .search-divider {
        width: 1px;
        height: 22px;
        background: var(--border-subtle);
        margin: 0 4px;
    }
    .header-search-inline .search-submit-btn {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 10px;
        border: none;
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        font-weight: 600;
        font-size: 0.85rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .header-search-inline .search-submit-btn:hover {
        transform: translateY(-1px);
    }
    .header-search-inline .search-submit-btn i {
        font-size: 0.85rem;
    }
    @media (max-width: 991px) {
        .header-search-inline {
            order: 3;
            width: 100%;
            margin-top: 10px;
        }
        .header-search-inline .search-card {
            max-width: none;
        }
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* =========================================
       HEADER - Style ServicesPro
       ========================================= */
    .sp-header {
        background-color: white;
        box-shadow: var(--shadow);
        position: sticky;
        top: 0;
        z-index: 1000;
        padding: 12px 0;
    }

    .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
    }

    .logo {
        font-size: 28px;
        font-weight: 700;
        color: var(--primary);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .logo i {
        color: var(--secondary);
    }

    /* Styles boutons "header fixe du dessus" */
    .nav-buttons-container {
        display: flex;
        align-items: center;
        gap: 4px;
        flex: 1;
        justify-content: center;
    }

    .nav-link-modern {
        font-weight: 500;
        font-size: 0.875rem;
        color: var(--text-muted);
        text-decoration: none;
        padding: 8px 14px;
        border-radius: 10px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .nav-link-modern:hover {
        color: var(--text-dark);
        background: rgba(58, 134, 255, 0.05); /* Utilisation de var(--primary) avec opacitÃ© */
    }
    
    .nav-link-modern.active {
        color: var(--primary);
        background: rgba(58, 134, 255, 0.1);
        font-weight: 600;
    }
    
    .nav-link-modern i {
        font-size: 0.9rem;
        opacity: 0.85;
    }

    .nav-link-lost {
        color: #ea580c !important;
        border: 1px solid #fed7aa;
        background: #fff7ed;
    }
    
    .nav-link-lost:hover {
        background: #ffedd5;
        border-color: #fdba74;
    }
    
    .nav-link-lost i {
        color: #ea580c;
    }
    
    .lost-badge {
        background: #ea580c;
        color: white;
        font-size: 0.65rem;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 4px;
        font-weight: 600;
    }

    /* Menu utilisateur header */
    .user-menu {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .notification-icon, .message-icon {
        position: relative;
        font-size: 20px;
        color: var(--gray);
        cursor: pointer;
        padding: 8px;
        transition: var(--transition);
    }

    .notification-icon:hover, .message-icon:hover {
        color: var(--primary);
    }

    .header-badge {
        position: absolute;
        top: 0;
        right: 0;
        background-color: var(--secondary);
        color: white;
        font-size: 12px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .user-avatar {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: var(--radius-md);
        transition: var(--transition);
        position: relative;
    }

    .user-avatar:hover {
        background-color: var(--light-gray);
    }

    .avatar-img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
        overflow: hidden;
    }

    .avatar-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .user-name {
        font-weight: 600;
        font-size: 16px;
    }

    .user-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background-color: white;
        box-shadow: var(--shadow-hover);
        border-radius: var(--radius-md);
        min-width: 200px;
        padding: 10px 0;
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: var(--transition);
        z-index: 100;
    }

    .user-avatar:hover .user-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .user-dropdown a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        text-decoration: none;
        color: var(--dark);
        transition: var(--transition);
    }

    .user-dropdown a:hover {
        background-color: var(--light-gray);
        color: var(--primary);
    }

    /* =========================================
       NAVIGATION PRINCIPALE (Sous-header)
       CollÃ© au header principal avec design moderne
       ========================================= */
    .main-nav {
        background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
        border-top: none;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        position: sticky;
        top: 90px;
        z-index: 998;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.08), 0 2px 8px -2px rgba(0, 0, 0, 0.04);
    }

    /* Container flex pour aligner le badge et le menu */
    .main-nav .container {
        display: flex;
        align-items: center;
        justify-content: center;
        max-width: 1600px; /* Plus large */
        padding: 0 24px;
    }

    .nav-links {
        display: flex;
        list-style: none;
        justify-content: center;
        gap: 5px;
        margin: 0;
        padding: 0;
        width: 100%;
    }

    .nav-item {
        position: relative;
    }

    .nav-link {
        display: block;
        padding: 16px 22px;
        text-decoration: none;
        color: var(--dark);
        font-weight: 600;
        font-size: 15px;
        transition: var(--transition);
        border-radius: 10px;
        margin: 4px 2px;
        position: relative;
    }

    .nav-link:hover {
        color: var(--primary);
        background: linear-gradient(135deg, rgba(58, 134, 255, 0.08) 0%, rgba(131, 56, 236, 0.05) 100%);
        transform: translateY(-1px);
    }

    .nav-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%) scaleX(0);
        width: 60%;
        height: 3px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border-radius: 3px 3px 0 0;
        transition: transform 0.3s ease;
    }

    .nav-link:hover::after {
        transform: translateX(-50%) scaleX(1);
    }

    /* Mega-menu */
    .mega-menu {
        position: absolute;
        left: -200px; /* DÃ©placÃ© davantage vers la gauche */
        top: 100%;
        width: 900px;
        max-width: calc(100vw - 40px);
        max-height: 450px;
        overflow-y: auto;
        background-color: white;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 8px 25px rgba(58, 134, 255, 0.1);
        border-radius: 0 var(--radius-md) var(--radius-md) var(--radius-md);
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 24px 28px;
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 20px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(15px);
        transition: var(--transition);
        z-index: 100;
    }

    /* Scrollbar personnalisÃ©e pour mega-menu */
    .mega-menu::-webkit-scrollbar {
        width: 6px;
    }
    .mega-menu::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }
    .mega-menu::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 10px;
    }

    .nav-item:hover .mega-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .nav-item-mission .mega-menu {
        left: 50%;
        transform: translate(-50%, 15px);
    }

    .nav-item-mission:hover .mega-menu {
        transform: translate(-50%, 0);
    }

    /* =========================================
       MEGA-MENU MISSION - Style Rectangulaire (Grid)
       ========================================= */
    .mega-menu-mission {
        position: absolute;
        left: 50%; /* CentrÃ© par rapport au parent nav-item-mission, mais voir ajustement global si besoin */
        transform: translateX(-35%) translateY(15px); /* DÃ©calÃ© lÃ©gÃ¨rement car le menu est large */
        top: 100%;
        width: 660px; /* RÃ©duit pour des boutons plus courts (stricte minimum) */
        background-color: #f8fafc; /* LÃ©gÃ¨rement grisÃ© pour contraste avec les cartes blanches */
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 8px 25px rgba(58, 134, 255, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 0;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: all 0.2s ease;
        z-index: 2000;
        overflow: visible !important;
    }

    .nav-item-mission:hover .mega-menu-mission {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateX(-35%) translateY(0);
    }

    .mega-menu-mission-header {
        display: none; 
    }

    .mega-menu-mission-body {
        padding: 16px; /* RÃ©duit de 24px */
        max-height: none;
        overflow: visible !important;
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* 3 colonnes */
        gap: 8px; /* RÃ©duit de 16px */
    }

    .mission-category {
        position: relative;
    }

    /* Style carte pour chaque catÃ©gorie */
    .mission-category-btn {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 8px 10px; /* RÃ©duit de 12px */
        background: white;
        border: 1px solid #f1f5f9;
        border-radius: 8px; /* LÃ©gÃ¨rement moins rond pour coller au compact */
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        height: 100%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }

    .mission-category-btn:hover {
        background: #f8fafc;
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(58, 134, 255, 0.15);
        transform: translateY(-2px);
    }

    .mission-category-left {
        display: flex;
        align-items: center;
        gap: 10px; /* RÃ©duit de 12px */
        flex: 1;
    }

    .mission-category-icon {
        width: 32px; /* RÃ©duit de 40px */
        height: 32px; /* RÃ©duit de 40px */
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem; /* RÃ©duit de 1.1rem */
        color: white;
        flex-shrink: 0;
    }

    .mission-category-info {
        flex: 1;
        overflow: hidden;
    }

    .mission-category-name {
        font-size: 0.9rem; /* RÃ©duit de 0.95rem */
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 0; /* RÃ©duit marge */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.2;
    }

    .mission-category-btn:hover .mission-category-name {
        color: var(--primary);
    }

    .mission-category-count {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .mission-category-arrow {
        display: none; /* Pas de flÃ¨che en mode grille pour plus de propretÃ© */
    }

    /* Sous-catÃ©gories (Popup) - Affichage Ã  DROITE */
    .mission-subcategories {
        position: absolute;
        left: 100%; /* PositionnÃ© Ã  droite de la catÃ©gorie */
        top: 0;
        margin-left: 4px; /* Ecart rÃ©duit entre la catÃ©gorie et le sous-menu */
        width: 280px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 0;
        opacity: 0;
        pointer-events: none;
        visibility: hidden;
        transition: all 0.2s ease;
        z-index: 2100;
        overflow: hidden;
    }

    .mission-category:hover .mission-subcategories {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        /* Peut ajouter un lÃ©ger slide horizontal si voulu */
    }

    /* Arrow tip pointing LEFT to the category */
    .mission-subcategories::before {
        content: '';
        position: absolute;
        top: 22px; /* AjustÃ© pour Ãªtre en face du centre visuel */
        left: -6px;
        transform: rotate(45deg);
        width: 12px;
        height: 12px;
        background: white;
        border-left: 1px solid rgba(0,0,0,0.08);
        border-bottom: 1px solid rgba(0,0,0,0.08);   
    }

    /* Zone "pont" pour ne pas perdre le hover lors du dÃ©placement vers la droite */
    .mission-category::after {
        content: '';
        position: absolute;
        right: -20px; /* Ã‰tend la zone active vers la droite */
        top: 0;
        width: 30px;
        height: 100%;
        background: transparent;
    }

    /* Style pour les sous-catÃ©gories qui remontent (vers le haut) */
    .mission-subcategories-up {
        top: auto !important;
        bottom: 0;
    }

    /* Ajustement de la flÃ¨che pour celles qui remontent */
    .mission-subcategories-up::before {
        top: auto;
        bottom: 22px; /* S'aligne avec le bouton en bas */
    }


    .mission-category:hover .mission-subcategories {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    /* Bridge invisible entre catÃ©gorie et sous-menu */
    .mission-category::after {
        content: '';
        position: absolute;
        right: -16px;
        top: 0;
        width: 20px;
        height: 100%;
        background: transparent;
    }

    .mission-subcategories-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 18px;
        background: linear-gradient(135deg, rgba(58, 134, 255, 0.08) 0%, rgba(131, 56, 236, 0.05) 100%);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .mission-subcategories-header-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        color: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .mission-subcategories-header h6 {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0;
    }

    .mission-subcategories-list {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding: 12px;
        max-height: 300px;
        overflow-y: auto;
    }

    .mission-subcat-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: white;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .mission-subcat-item:hover {
        background: linear-gradient(135deg, rgba(58, 134, 255, 0.06) 0%, rgba(131, 56, 236, 0.04) 100%);
        border-color: rgba(58, 134, 255, 0.2);
        transform: translateX(4px);
    }

    .mission-subcat-item-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .mission-subcat-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        box-shadow: 0 2px 6px rgba(58, 134, 255, 0.4);
    }

    .mission-subcat-name {
        font-size: 0.85rem;
        font-weight: 500;
        color: #4a5568;
    }

    .mission-subcat-item:hover .mission-subcat-name {
        color: var(--primary);
    }

    .mission-subcat-badge {
        min-width: 26px;
        height: 20px;
        padding: 0 6px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 0.7rem;
        font-weight: 700;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 3px 10px rgba(58, 134, 255, 0.3);
    }

    .mission-subcategories-footer {
        padding: 12px;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        background: rgba(248, 250, 252, 0.8);
    }

    .mission-subcategories-all {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(58, 134, 255, 0.3);
    }

    .mission-subcategories-all:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(58, 134, 255, 0.4);
        color: white;
    }

    .mega-menu-column h4 {
        font-size: 14px;
        margin-bottom: 10px;
        color: var(--primary);
        padding-bottom: 6px;
        border-bottom: 2px solid var(--light-gray);
        font-weight: 700;
    }

    .mega-menu-column a {
        display: flex;
        align-items: center;
        padding: 5px 0;
        text-decoration: none;
        color: var(--dark);
        transition: var(--transition);
        font-size: 0.85rem;
    }

    .mega-menu-group + .mega-menu-group {
        margin-top: 14px;
    }

    .mega-menu-column a:hover {
        color: var(--primary);
        padding-left: 6px;
    }

    .see-all {
        margin-top: 10px;
        font-weight: 600;
        color: var(--secondary) !important;
        font-size: 0.8rem !important;
    }

    /* Compteurs dans le mega-menu */
    .mega-menu-column .sub-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 16px;
        padding: 0 4px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 0.6rem;
        font-weight: 600;
        border-radius: 6px;
        margin-right: 5px;
    }

    .mega-menu-column .cat-total {
        font-size: 0.7rem;
        color: var(--text-muted);
        font-weight: 500;
    }

    .mega-menu-group {
        margin-bottom: 8px;
    }

    .mega-menu-group:last-child {
        margin-bottom: 0;
    }

    /* =========================================
       BADGE TEST (IntÃ©grÃ© dans la nav)
       ========================================= */
    .test-badge {
        background: var(--danger);
        color: white;
        padding: 4px 10px;
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-right: 15px;
    }

    .test-badge a {
        color: white;
        text-decoration: underline;
        margin-left: 4px;
        font-weight: 400;
    }

    /* =========================================
       MEGA-MENU DROPDOWN - Publier une offre
       Style identique Ã  la colonne gauche principale
       ========================================= */
    .nav-item-mission {
        position: relative;
    }

    .mega-dropdown-mission {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        top: 100%;
        width: 320px;
        background: white;
        border-radius: 18px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 8px 25px rgba(58, 134, 255, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 0;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 2000;
        margin-top: 10px;
        overflow: visible;
    }

    .nav-item-mission:hover .mega-dropdown-mission {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        margin-top: 0;
    }

    /* FlÃ¨che pointant vers le bouton */
    .mega-dropdown-mission::before {
        content: '';
        position: absolute;
        top: -8px;
        left: 50%;
        transform: translateX(-50%) rotate(45deg);
        width: 16px;
        height: 16px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary) 100%);
        border-left: 1px solid rgba(0, 0, 0, 0.08);
        border-top: 1px solid rgba(0, 0, 0, 0.08);
    }

    /* Bridge pour garder le menu ouvert */
    .nav-item-mission::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        top: 100%;
        height: 15px;
        background: transparent;
    }

    .mega-dropdown-mission-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-radius: 18px 18px 0 0;
    }

    .mega-dropdown-mission-header i {
        font-size: 1.2rem;
        color: white;
    }

    .mega-dropdown-mission-header h5 {
        color: white;
        font-weight: 700;
        margin: 0;
        font-size: 1rem;
    }

    .mega-dropdown-mission-body {
        padding: 8px;
        max-height: 400px;
        overflow-y: auto;
    }

    .mega-dropdown-mission-body::-webkit-scrollbar {
        width: 5px;
    }

    .mega-dropdown-mission-body::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }

    .mega-dropdown-mission-body::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 10px;
    }

    /* CatÃ©gorie principale */
    .mission-cat-item {
        position: relative;
        margin-bottom: 2px;
    }

    .mission-cat-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 10px 14px;
        background: transparent;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .mission-cat-btn:hover {
        background: rgba(58, 134, 255, 0.08);
    }

    .mission-cat-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .mission-cat-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        color: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .mission-cat-info {
        text-align: left;
    }

    .mission-cat-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 2px;
    }

    .mission-cat-btn:hover .mission-cat-name {
        color: var(--primary);
    }

    .mission-cat-count {
        font-size: 0.75rem;
        color: var(--text-muted);
        font-weight: 500;
    }

    .mission-cat-count strong {
        color: var(--primary);
    }

    .mission-cat-arrow {
        font-size: 0.7rem;
        color: var(--text-muted);
        transition: transform 0.2s ease;
    }

    .mission-cat-btn:hover .mission-cat-arrow {
        color: var(--primary);
        transform: translateX(3px);
    }

    /* Panneau sous-catÃ©gories */
    .mission-subcats-panel {
        position: absolute;
        left: 100%;
        top: 0;
        width: 260px;
        margin-left: 8px;
        background: white;
        border-radius: 14px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(0, 0, 0, 0.08);
        padding: 0;
        opacity: 0;
        pointer-events: none;
        visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s ease;
        z-index: 2100;
        overflow: hidden;
    }

    .mission-cat-item:hover .mission-subcats-panel {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    /* Bridge invisible entre catÃ©gorie et panneau */
    .mission-cat-item::after {
        content: '';
        position: absolute;
        right: -12px;
        top: 0;
        width: 16px;
        height: 100%;
        background: transparent;
    }

    .mission-subcats-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        background: rgba(58, 134, 255, 0.05);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .mission-subcats-header-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        color: white;
    }

    .mission-subcats-header h6 {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0;
    }

    .mission-subcats-list {
        display: flex;
        flex-direction: column;
        gap: 3px;
        padding: 10px;
        max-height: 280px;
        overflow-y: auto;
    }

    .mission-subcat-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 9px 12px;
        background: #f8fafc;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .mission-subcat-link:hover {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transform: translateX(3px);
    }

    .mission-subcat-link-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .mission-subcat-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: var(--primary);
    }

    .mission-subcat-name {
        font-size: 0.8rem;
        font-weight: 500;
        color: #4a5568;
    }

    .mission-subcat-link:hover .mission-subcat-name {
        color: var(--primary);
    }

    .mission-subcat-badge {
        min-width: 22px;
        height: 18px;
        padding: 0 5px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 0.65rem;
        font-weight: 600;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .mission-subcats-footer {
        padding: 10px;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    .mission-subcats-all {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        width: 100%;
        padding: 9px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .mission-subcats-all:hover {
        box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
        color: white;
    }

    /* =========================================
       Responsive Header
       ========================================= */
    @media (max-width: 1200px) {
        .mega-menu {
            width: 750px;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            padding: 20px 24px;
        }
    }

    @media (max-width: 992px) {
        .nav-links {
            overflow-x: auto;
            justify-content: center;
            padding: 10px 0;
        }
        
        .mega-menu {
            width: 600px;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            padding: 18px 20px;
            max-height: 400px;
        }
    }

    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            gap: 15px;
        }
        
        .header-search-container {
            width: 100%;
            max-width: 100%;
        }
        
        .user-name {
            display: none;
        }
        
        .mega-menu {
            width: calc(100vw - 30px);
            grid-template-columns: repeat(2, 1fr);
            left: 50% !important;
            transform: translate(-50%, 0) !important;
            max-height: 350px;
            gap: 12px;
            padding: 15px;
        }

        .nav-item-mission .mega-menu {
            left: 50% !important;
        }
    }

    @media (max-width: 480px) {
        .mega-menu {
            grid-template-columns: 1fr;
            max-height: 320px;
        }
    }

    /* =========================================
       Suite des styles existants...
       ========================================= */

    /* =========================================
       MAIN LAYOUT - Grille principale
       ========================================= */
    .main-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px;
    }

    .page-grid {
        display: grid;
        grid-template-columns: 1fr 280px;
        gap: 24px;
        max-width: 1400px;
        margin: 0 auto;
    }

    @media (max-width: 1200px) {
        .page-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 900px) {
        .page-grid {
            grid-template-columns: 1fr;
        }
        .sidebar-right {
            display: none;
        }
    }

    /* =========================================
       SIDEBAR GAUCHE - Mega Menu Pros
       ========================================= */
    .sidebar-left {
        position: sticky;
        top: 140px;
        height: fit-content;
    }

    .mega-menu-card {
        background: white;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        overflow: hidden;
    }

    .mega-menu-header {
        padding: 16px 20px;
        background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%);
        color: white;
    }

    .mega-menu-header h3 {
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .mega-menu-list {
        padding: 8px 0;
    }

    .mega-item {
        position: relative;
    }

    .mega-item-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 20px;
        background: none;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        text-align: left;
    }

    .mega-item-btn:hover {
        background: var(--bg-main);
    }

    .mega-item-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .mega-item-icon {
        width: 36px;
        height: 36px;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.875rem;
    }

    .mega-item-info h4 {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0 0 2px 0;
    }

    .mega-item-info span {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .mega-item-arrow {
        color: var(--text-muted);
        font-size: 0.75rem;
        transition: transform 0.2s;
    }

    .mega-item:hover .mega-item-arrow {
        transform: translateX(3px);
        color: var(--primary);
    }

    /* Sous-menu au survol */
    .mega-submenu {
        display: none;
        position: absolute;
        left: 100%;
        top: 0;
        width: 280px;
        background: white;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-lg);
        padding: 12px 0;
        z-index: 1000;
        margin-left: 8px;
    }

    .mega-item:hover .mega-submenu {
        display: block;
    }

    .mega-sub-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        color: var(--text-dark);
        text-decoration: none;
        font-size: 0.875rem;
        transition: all 0.2s;
    }

    .mega-sub-link:hover {
        background: var(--primary-light);
        color: var(--primary);
    }

    .mega-sub-link i {
        margin-right: 10px;
        width: 16px;
        color: var(--text-muted);
    }

    .mega-sub-link:hover i {
        color: var(--primary);
    }

    .mega-sub-count {
        background: var(--bg-main);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    /* =========================================
       FEED CENTRAL - Contenu principal
       ========================================= */
    .feed-main {
        min-width: 0;
    }

    /* Onglets de filtrage */
    .feed-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        background: white;
        padding: 8px;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
    }

    .feed-tab {
        flex: 1;
        padding: 12px 16px;
        border: none;
        background: none;
        border-radius: var(--radius-md);
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        text-align: center;
    }

    .feed-tab:hover {
        background: var(--bg-main);
        color: var(--text-dark);
    }

    .feed-tab.active {
        background: var(--primary);
        color: white;
    }

    .feed-tab i {
        margin-right: 6px;
    }

    /* Section avec titre */
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .section-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title i {
        color: var(--primary);
    }

    .section-link {
        font-size: 0.875rem;
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
    }

    .section-link:hover {
        text-decoration: underline;
    }

    /* Carrousel horizontal */
    .horizontal-scroll {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 12px;
        scroll-snap-type: x mandatory;
        scrollbar-width: none;
    }

    .horizontal-scroll::-webkit-scrollbar {
        display: none;
    }

    /* Carte d'annonce compacte (pour carrousel) */
    .ad-card-compact {
        flex: 0 0 280px;
        scroll-snap-align: start;
        background: white;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        overflow: hidden;
        transition: all 0.3s;
        text-decoration: none;
        display: block;
    }

    .ad-card-compact:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .ad-card-img {
        height: 140px;
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .ad-card-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .ad-card-img .placeholder-icon {
        font-size: 2rem;
        color: var(--primary);
        opacity: 0.5;
    }

    .ad-badge-type {
        position: absolute;
        top: 10px;
        left: 10px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .ad-badge-type.offre {
        background: var(--secondary);
        color: white;
    }

    .ad-badge-type.demande {
        background: var(--accent);
        color: white;
    }

    .ad-card-body {
        padding: 14px;
    }

    .ad-card-category {
        font-size: 0.75rem;
        color: var(--primary);
        font-weight: 500;
        margin-bottom: 6px;
    }

    .ad-card-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 8px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .ad-card-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .ad-card-price {
        font-weight: 700;
        color: var(--secondary);
    }

    .ad-card-location {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    /* Grille d'annonces principales */
    .ads-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    @media (max-width: 700px) {
        .ads-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Carte d'annonce standard */
    .ad-card {
        background: white;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        overflow: hidden;
        transition: all 0.3s;
    }

    .ad-card:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow-md);
    }

    .ad-card-header {
        display: flex;
        align-items: center;
        padding: 14px;
        gap: 12px;
        border-bottom: 1px solid var(--border);
    }

    .ad-user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: var(--primary);
        overflow: hidden;
    }

    .ad-user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .ad-user-info h4 {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0;
    }

    .ad-user-info span {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .ad-card-content {
        padding: 14px;
    }

    .ad-card-content h3 {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 8px;
    }

    .ad-card-content h3 a {
        color: inherit;
        text-decoration: none;
    }

    .ad-card-content h3 a:hover {
        color: var(--primary);
    }

    .ad-card-content p {
        font-size: 0.875rem;
        color: var(--text-muted);
        line-height: 1.5;
        margin-bottom: 12px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .ad-card-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 12px;
    }

    .ad-tag {
        padding: 4px 10px;
        background: var(--bg-main);
        border-radius: 20px;
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .ad-tag.urgent {
        background: #fef2f2;
        color: var(--danger);
    }

    .ad-card-image {
        height: 180px;
        background: var(--bg-main);
        margin: 0 14px 14px;
        border-radius: var(--radius-md);
        overflow: hidden;
    }

    .ad-card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .ad-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px;
        border-top: 1px solid var(--border);
        background: var(--bg-main);
    }

    .ad-price {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--secondary);
    }

    .ad-actions {
        display: flex;
        gap: 8px;
    }

    .btn-action {
        padding: 8px 14px;
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        display: flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }

    .btn-action.primary {
        background: var(--primary);
        color: white;
    }

    .btn-action.primary:hover {
        background: var(--primary-dark);
    }

    .btn-action.secondary {
        background: white;
        color: var(--text-muted);
        border: 1px solid var(--border);
    }

    .btn-action.secondary:hover {
        border-color: var(--primary);
        color: var(--primary);
    }

    /* =========================================
       TOP PROS WIDGET
       ========================================= */
    .toppros-widget {
        background: #ffffff;
        border: 1px solid rgba(15,23,42,.10);
        border-radius: 18px;
        box-shadow: 0 10px 26px rgba(2,6,23,.06);
        overflow: hidden;
        margin-top: 20px;
    }
    
    .toppros__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 14px 10px;
    }
    
    .toppros__title {
        margin: 0;
        font-size: 14px;
        font-weight: 800;
        letter-spacing: -0.01em;
    }
    
    .toppros__score {
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 800;
        font-size: 13px;
        color: var(--text-main);
    }
    
    .toppros__star {
        color: #f59e0b;
        font-size: 14px;
        line-height: 1;
    }
    
    .toppros__more {
        margin-left: 4px;
        width: 22px;
        height: 22px;
        border-radius: 8px;
        border: 1px solid rgba(15,23,42,.10);
        background: #fff;
        color: #334155;
        cursor: pointer;
        display: grid;
        place-items: center;
        line-height: 1;
        transition: transform .15s ease, box-shadow .15s ease;
        text-decoration: none;
    }
    
    .toppros__more:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 18px rgba(2,6,23,.08);
    }
    
    .toppros__list {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .toppros__divider {
        height: 1px;
        background: rgba(15,23,42,.08);
        margin: 0 14px;
    }
    
    .pro-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
    }
    
    .pro__avatar {
        width: 40px;
        height: 40px;
        border-radius: 999px;
        object-fit: cover;
        border: 1px solid rgba(15,23,42,.10);
        flex: 0 0 auto;
        background: linear-gradient(135deg, #7c3aed, #9333ea);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 0.9rem;
    }
    
    .pro__avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 999px;
    }
    
    .pro__main {
        flex: 1;
        min-width: 0;
    }
    
    .pro__row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 10px;
    }
    
    .pro__name {
        font-weight: 800;
        font-size: 13px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--text-main);
        text-decoration: none;
    }
    
    .pro__name:hover {
        color: #7c3aed;
    }
    
    .pro__job {
        font-size: 12px;
        color: #64748b;
        white-space: nowrap;
    }
    
    .pro__rating {
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .stars-bar {
        position: relative;
        width: 72px;
        height: 12px;
        display: inline-block;
        border-radius: 999px;
        overflow: hidden;
        border: 1px solid rgba(245,158,11,.25);
        background: rgba(245,158,11,.12);
    }
    
    .stars-bar__fill {
        position: absolute;
        inset: 0;
        width: 0%;
        background: linear-gradient(90deg, rgba(245,158,11,.95), rgba(245,158,11,.75));
    }
    
    .pro__ratingText {
        font-size: 12px;
        font-weight: 800;
        color: #334155;
    }
    
    .pro__actions {
        position: relative;
        flex: 0 0 auto;
    }
    
    .pro__cta {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        border: 1px solid rgba(15,23,42,.10);
        background: #fff;
        cursor: pointer;
        display: grid;
        place-items: center;
        transition: transform .15s ease, box-shadow .15s ease;
        padding: 0;
    }
    
    .pro__cta:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 18px rgba(2,6,23,.08);
    }
    
    .pro__ctaIcon {
        font-size: 14px;
        color: #334155;
        line-height: 1;
    }
    
    .pro__menu {
        position: absolute;
        top: 38px;
        right: 0;
        width: 210px;
        background: #fff;
        border: 1px solid rgba(15,23,42,.12);
        border-radius: 14px;
        box-shadow: 0 18px 40px rgba(2,6,23,.14);
        padding: 6px;
        display: none;
        z-index: 50;
        transform-origin: top right;
    }
    
    .pro__menu::before {
        content: "";
        position: absolute;
        top: -7px;
        right: 10px;
        width: 12px;
        height: 12px;
        background: #fff;
        border-left: 1px solid rgba(15,23,42,.12);
        border-top: 1px solid rgba(15,23,42,.12);
        transform: rotate(45deg);
    }
    
    .pro__menu.open {
        display: block;
        animation: fadeScalePro .18s ease;
    }
    
    @keyframes fadeScalePro {
        from { opacity: 0; transform: translateY(-6px) scale(.96); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    
    .pro__menuItem {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        font-size: 13px;
        font-weight: 800;
        color: var(--text-main);
        text-decoration: none;
        border-radius: 10px;
        transition: background .15s ease, transform .15s ease;
    }
    
    .pro__menuItem:hover {
        background: #f1f5f9;
        transform: translateX(2px);
    }
    
    .pro__menuSep {
        height: 1px;
        background: rgba(15,23,42,.08);
        margin: 6px 8px;
    }
    
    .pro__menuItem--primary {
        background: linear-gradient(180deg, rgba(37,99,235,.98), rgba(29,78,216,.98));
        color: #fff;
        border: 1px solid rgba(37,99,235,.25);
        box-shadow: 0 12px 22px rgba(37,99,235,.18);
    }
    
    .pro__menuItem--primary:hover {
        background: linear-gradient(180deg, rgba(29,78,216,.98), rgba(30,64,175,.98));
        color: #fff;
    }

    /* =========================================
       SIDEBAR DROITE - Widgets
       ========================================= */
    .sidebar-right {
        position: relative;
        height: calc(100vh - 220px);
        overflow-y: auto;
        overflow-x: hidden;
    }

    .promo-card { background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%); border-radius: 20px; padding: 25px; text-align: center; margin-bottom: 20px; }
    .promo-card i { font-size: 2.5rem; color: white; margin-bottom: 15px; }
    .promo-card h5 { color: white; margin-bottom: 10px; }
    .promo-card p { color: rgba(255,255,255,0.8); font-size: 0.9rem; margin-bottom: 15px; }
    .promo-btn { background: white; color: #7c3aed; border: none; padding: 10px 25px; border-radius: 10px; font-weight: 600; }
    .promo-btn:hover { background: #f8f9fa; color: #7c3aed; }

    .sidebar-right .widgets-wrapper {
        position: sticky !important;
        top: 90px !important;
    }

    .widget {
        background: var(--bg-card) !important;
        border-radius: var(--radius-md) !important;
        padding: 20px !important;
        margin-bottom: 20px !important;
        border: 1px solid var(--border-color) !important;
        box-shadow: var(--shadow-sm) !important;
    }

    .dashboard-summary {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
        border: none !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08) !important;
    }

    .dashboard-summary h3 {
        font-size: 1rem !important;
        font-weight: 700 !important;
        color: var(--text-main) !important;
        margin-bottom: 20px !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }

    .dashboard-summary h3::before {
        content: 'ðŸ“Š';
        font-size: 1.2rem;
    }

    .stats-list {
        list-style: none !important;
        padding: 0 !important;
        margin: 0 0 20px 0 !important;
    }

    .stats-list li {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 12px 0 !important;
        border-bottom: 1px solid rgba(0,0,0,0.05) !important;
        font-size: 0.9rem !important;
    }

    .stats-list li:last-child {
        border-bottom: none !important;
    }

    .stats-list-link {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        width: 100% !important;
        text-decoration: none !important;
        padding: 4px 8px !important;
        margin: -4px -8px !important;
        border-radius: 8px !important;
        transition: background-color 0.2s, transform 0.2s !important;
    }
    
    .stats-list-link:hover {
        background-color: rgba(79, 70, 229, 0.08) !important;
        transform: translateX(4px) !important;
    }
    
    .stats-list-link:hover .stat-label {
        color: var(--primary-color) !important;
    }

    .stat-label {
        color: var(--text-muted) !important;
        font-weight: 500 !important;
    }

    .stat-value {
        font-weight: 700 !important;
        color: var(--primary-color) !important;
        background: rgba(79, 70, 229, 0.1) !important;
        padding: 4px 12px !important;
        border-radius: 20px !important;
        font-size: 0.85rem !important;
    }

    .btn-block {
        display: block !important;
        width: 100% !important;
        text-align: center !important;
    }

    .btn-secondary {
        background: #E5E7EB !important;
        color: var(--text-main) !important;
        padding: 10px 16px !important;
        border-radius: var(--radius-md) !important;
        font-weight: 600 !important;
        font-size: 0.9rem !important;
        text-decoration: none !important;
        transition: background 0.2s !important;
    }

    .btn-secondary:hover {
        background: #D1D5DB !important;
    }

    .subscription-box {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
        color: white !important;
        border: none !important;
    }

    .subscription-box .sub-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin-bottom: 10px !important;
    }

    .subscription-box p {
        font-size: 0.85rem !important;
        color: #94A3B8 !important;
        margin-bottom: 15px !important;
    }

    .btn-gold {
        background: var(--accent-gold) !important;
        color: white !important;
        padding: 10px 16px !important;
        border-radius: var(--radius-md) !important;
        font-weight: 700 !important;
        font-size: 0.9rem !important;
        text-decoration: none !important;
        transition: background 0.2s !important;
    }

    .btn-gold:hover {
        background: #D97706 !important;
    }

    .ad-space {
        padding: 0 !important;
        overflow: hidden !important;
        position: relative !important;
    }

    .ad-label {
        position: absolute !important;
        top: 10px !important;
        right: 10px !important;
        background: rgba(0,0,0,0.5) !important;
        color: white !important;
        font-size: 0.6rem !important;
        padding: 2px 6px !important;
        border-radius: 4px !important;
    }

    .ad-content img {
        width: 100% !important;
        display: block !important;
    }

    .ad-content h4 {
        margin-top: 10px !important;
        font-size: 0.95rem !important;
        padding: 0 15px !important;
    }

    .ad-content p {
        margin-bottom: 15px !important;
        font-size: 0.85rem !important;
        color: var(--text-muted);
        padding: 0 15px;
    }

    .widget-cta .btn-cta {
        display: inline-block;
        background: white;
        color: var(--primary);
        padding: 10px 24px;
        border-radius: var(--radius-md);
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }

    .widget-cta .btn-cta:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    /* Widget Pros */
    .pro-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .pro-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px;
        border-radius: var(--radius-md);
        transition: all 0.2s;
        text-decoration: none;
    }

    .pro-item:hover {
        background: var(--bg-main);
    }

    .pro-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: var(--primary-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: var(--primary);
        overflow: hidden;
    }

    .pro-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .pro-info {
        flex: 1;
        min-width: 0;
    }

    .pro-info h5 {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-dark);
        margin: 0 0 2px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pro-info span {
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    .pro-rating {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 0.8rem;
    }

    .pro-rating i {
        color: var(--accent);
    }

    /* Widget Stats */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .stat-item {
        text-align: center;
        padding: 12px;
        background: var(--bg-main);
        border-radius: var(--radius-md);
        text-decoration: none;
        transition: all 0.2s;
    }

    .stat-item:hover {
        background: var(--primary-light);
    }

    .stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary);
    }

    .stat-label {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 2px;
    }

    /* =========================================
       BOUTON FLOTTANT - CrÃ©er une annonce
       ========================================= */
    .fab-create {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 60px;
        height: 60px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 1.5rem;
        cursor: pointer;
        box-shadow: var(--shadow-lg);
        transition: all 0.3s;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .fab-create:hover {
        background: var(--primary-dark);
        transform: scale(1.1);
        color: white;
    }

    /* =========================================
       BADGE TEST
       ========================================= */
    .test-badge {
        position: fixed;
        top: 80px;
        right: 20px;
        background: var(--danger);
        color: white;
        padding: 8px 16px;
        border-radius: var(--radius-md);
        font-size: 0.8rem;
        font-weight: 600;
        z-index: 1001;
        box-shadow: var(--shadow-md);
    }

    .test-badge a {
        color: white;
        text-decoration: underline;
        margin-left: 8px;
    }

    /* =========================================
       RESPONSIVE
       ========================================= */
    @media (max-width: 768px) {
        .hero-search {
            padding: 24px 0;
        }

        .hero-title {
            font-size: 1.25rem;
        }

        .search-container {
            flex-direction: column;
        }

        .search-btn {
            width: 100%;
            justify-content: center;
        }

        .main-container {
            padding: 16px;
        }

        .feed-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
        }

        .feed-tab {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .ads-grid {
            grid-template-columns: 1fr;
        }
    }



    /* =========================================
       PAGE TEST - ARCHITECTURE MODERNE
       ========================================= */
    :root {
        /* ðŸŽ¨ Palette ServicesPro */
        --primary: #3a86ff;
        --primary-dark: #2667cc;
        --primary-hover: #2667cc;
        --primary-light: rgba(58, 134, 255, 0.1);
        --secondary: #8338ec;
        --accent: #ffbe0b;
        
        /* Funds */
        --bg-body: #f8fafc;
        --text-dark: #1e293b;
        --text-light: #64748b;
        --white: #ffffff;
        --border: #e2e8f0;
    }

    body {
        background-color: var(--bg-body);
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }

    /* =========================================
       HEADER STYLES RESTORATION (Pour "Ne pas toucher au header")
       ========================================= */
    .header-modern {
        background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%) !important;
        box-shadow: 0 2px 20px -4px rgba(58, 134, 255, 0.15), 0 4px 12px -2px rgba(0, 0, 0, 0.08) !important;
        border-bottom: 1px solid rgba(58, 134, 255, 0.1) !important;
        /* height: 90px !important; REMOVED to match standard nav */
        z-index: 1001 !important;
    }
    .header-modern .container-fluid {
        max-width: 1600px !important;
        margin: 0 auto;
        padding: 0 32px !important;
    }
    .header-modern .navbar-brand-modern .brand-logo {
        width: 42px; height: 42px; font-size: 1.2rem;
        box-shadow: 0 4px 15px rgba(58, 134, 255, 0.35);
    }
    .header-modern .navbar-brand-modern .brand-text { font-size: 1.4rem; }
    
    /* Search bar styles removed */
    
    .nav-link-modern {
        font-weight: 500; color: #6c757d; padding: 8px 14px; border-radius: 10px;
        display: flex; align-items: center; gap: 6px; text-decoration: none;
    }
    .nav-link-modern:hover { color: var(--text-dark); background: rgba(58, 134, 255, 0.05); }
    .nav-link-modern.active { color: var(--primary); background: rgba(58, 134, 255, 0.1); font-weight: 600; }
    .nav-link-lost { color: #ea580c !important; border: 1px solid #fed7aa; background: #fff7ed; }
    .lost-badge { background: #ea580c; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.65rem; margin-left: 4px; }
    
    /* =========================================
       NEW HERO & CONTENT STYLES
       ========================================= */
    
    /* HERO SECTION */
    .hero-section {
        background-color: #312e81; /* Indigo 900 */
        padding-top: 2rem;
        padding-bottom: 7rem;
        position: relative;
        overflow: hidden;
    }

    /* Background Shapes */
    .hero-shape-1 {
        position: absolute; top: 0; right: 0; width: 24rem; height: 24rem;
        border-radius: 9999px; background-color: #3730a3; opacity: 0.5;
        filter: blur(64px); transform: translate(5rem, -5rem);
    }
    .hero-shape-2 {
        position: absolute; bottom: 0; left: 0; width: 20rem; height: 20rem;
        border-radius: 9999px; background-color: #701a75; opacity: 0.3;
        filter: blur(64px); transform: translate(-5rem, 5rem);
    }
    
    /* Hero Search Bar (Larger & More Prominent) */
    .hero-search-wrapper {
        background: white; border-radius: 16px; padding: 8px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        max-width: 800px; margin: 0 auto;
        display: flex; gap: 10px; flex-wrap: wrap;
    }
    @media (min-width: 768px) { .hero-search-wrapper { flex-wrap: nowrap; } }
    
    .hero-input-group {
        flex: 1; position: relative; min-width: 200px;
    }
    .hero-input-group input {
        width: 100%; height: 56px; border: none; background: transparent;
        padding-left: 48px; font-size: 1.05rem; outline: none; border-radius: 12px;
    }
    .hero-input-group input:focus { background: #f8fafc; }
    .hero-input-group i {
        position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
        font-size: 1.2rem; color: #94a3b8;
    }
    
    .hero-search-btn {
        background: var(--primary); color: white; font-weight: 700;
        border: none; border-radius: 12px; padding: 0 32px; height: 56px;
        font-size: 1.1rem; cursor: pointer; transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
    }
    .hero-search-btn:hover { background: var(--primary-dark); transform: translateY(-2px); }

    /* Trust Badges */
    .trust-badges {
        margin-top: 2rem; display: flex; justify-content: center; gap: 2rem;
        color: #e0e7ff; font-weight: 500; font-size: 0.95rem;
    }
    .trust-badges i { color: #34d399; margin-right: 8px; }

    /* CATEGORIES FLOATING GRID */
    .categories-floating {
        margin-top: -60px; /* Overlap hero */
        position: relative; z-index: 10;
        margin-bottom: 2rem;
    }
    .cat-card-modern {
        background: white; border-radius: 16px; padding: 24px;
        text-align: center; border: 1px solid #f1f5f9;
        box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        transition: all 0.3s; height: 100%;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        text-decoration: none; color: var(--text-dark);
        position: relative; overflow: hidden;
    }
    .cat-card-modern:hover, .cat-card-modern.active {
        transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        border-color: var(--primary);
    }
    .cat-card-modern.active { background-color: #f0f9ff; border: 2px solid var(--primary); }
    .cat-card-modern i { font-size: 2.5rem; margin-bottom: 12px; transition: transform 0.3s; }
    .cat-card-modern:hover i { transform: scale(1.1); }
    .cat-card-modern span { font-weight: 700; font-size: 1.05rem; }

    /* SUBCATEGORIES BAR */
    .subcat-bar {
        background: #ffffff; 
        border-bottom: 1px solid #e2e8f0; 
        padding: 20px 0;
        margin-bottom: 40px; 
        white-space: nowrap; 
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        position: relative;
        z-index: 20; /* Ensure visual priority */
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); /* Slight shadow to prove visibility */
        display: block !important; /* Force display if condition met */
    }
    .subcat-pill {
        display: inline-block; padding: 8px 16px; border-radius: 50px;
        background: #f1f5f9; color: var(--text-dark); font-weight: 500;
        margin-right: 10px; text-decoration: none; transition: all 0.2s;
        border: 1px solid transparent;
    }
    .subcat-pill:hover, .subcat-pill.active {
        background: var(--primary); color: white;
        box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
    }

    /* ADVERTISEMENT SPACES */
    .ad-space-banner {
        width: 100%; height: 120px;
        background: linear-gradient(45deg, #f3f4f6 25%, #e5e7eb 25%, #e5e7eb 50%, #f3f4f6 50%, #f3f4f6 75%, #e5e7eb 75%, #e5e7eb 100%);
        background-size: 20px 20px;
        border-radius: 12px; display: flex; align-items: center; justify-content: center;
        color: #9ca3af; font-weight: 600; letter-spacing: 1px;
        border: 2px dashed #d1d5db; margin: 2rem 0;
        position: relative; overflow: hidden;
    }
    .ad-label {
        position: absolute; top: 5px; right: 8px; font-size: 0.65rem;
        background: rgba(0,0,0,0.1); padding: 2px 6px; border-radius: 4px;
    }

    /* LISTINGS GRID */
    .listing-card {
        background: white; border-radius: 16px; overflow: hidden;
        border: 1px solid #f1f5f9; transition: all 0.3s;
        height: 100%; display: flex; flex-direction: column;
    }
    .listing-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.08);
    }
    .listing-img-box {
        height: 180px; background: #e2e8f0; position: relative;
        overflow: hidden;
    }
    .listing-img-box img { width: 100%; height: 100%; object-fit: cover; }
    .listing-badge {
        position: absolute; top: 12px; left: 12px;
        background: rgba(255,255,255,0.9); padding: 4px 10px;
        border-radius: 8px; font-size: 0.75rem; font-weight: 700;
        color: var(--primary); box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .listing-body { padding: 20px; flex: 1; display: flex; flex-direction: column; }
    .listing-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 8px; color: var(--text-dark); line-height: 1.4; }
    .listing-meta { font-size: 0.85rem; color: var(--text-light); margin-bottom: 15px; display: flex; gap: 10px; align-items: center; }
    .listing-price { font-size: 1.2rem; font-weight: 800; color: var(--primary); margin-top: auto; }
    .listing-footer {
        padding: 12px 20px; border-top: 1px solid #f1f5f9; background: #f8fafc;
        display: flex; justify-content: space-between; align-items: center;
    }
    .user-mini { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600; color: #475569; }
    .user-avatar-sm { width: 28px; height: 28px; border-radius: 50%; background: #cbd5e1; }

    /* SECTION TITLES */
    .modern-title-block { text-align: center; margin-bottom: 3rem; }
    .modern-subtitle {
        color: var(--primary); font-weight: 700; text-transform: uppercase;
        letter-spacing: 1px; font-size: 0.85rem; display: block; margin-bottom: 8px;
    }
    .modern-title { font-size: 2rem; font-weight: 800; color: var(--text-dark); margin: 0; }

    /* STEPS */
    .step-box {
        text-align: center; padding: 20px;
    }
    .step-icon-circle {
        width: 80px; height: 80px; border-radius: 24px;
        display: flex; align-items: center; justify-content: center;
        font-size: 32px; margin: 0 auto 20px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }

    /* CTA BLOCK */
    .pro-cta-block {
        background: #1e293b; border-radius: 24px; overflow: hidden;
        position: relative; color: white;
    }
    .pro-cta-content { padding: 48px; z-index: 2; position: relative; }
    .pro-list li { margin-bottom: 12px; display: flex; align-items: center; gap: 10px; color: #cbd5e1; }
    .pro-list i { color: #34d399; }


        /* Offset pour le contenu principal quand la sidebar est prÃ©sente */
        .main-content-with-sidebar {
            margin-left: 260px;
            transition: margin-left 0.3s ease;
        }
        @media (max-width: 991.98px) {
            .main-content-with-sidebar {
                margin-left: 0;
            }
        }

        /* FIX: Header et Navigation Fixes/Sticky */
        .header-modern {
            position: sticky !important;
            top: 0 !important;
            z-index: 1040 !important; /* Above sidebar */
        }
        
        .main-nav {
            position: -webkit-sticky !important;
            position: sticky !important;
            top: 90px !important; /* Match header-modern height */
            z-index: 1010 !important; /* Just below header */
        }
        
        /* Hover effect for subcategories (Visual Feedback) */
        .mission-subcat-item:hover {
            background: linear-gradient(135deg, rgba(58, 134, 255, 0.15) 0%, rgba(131, 56, 236, 0.12) 100%) !important;
            border-color: rgba(58, 134, 255, 0.5) !important;
            transform: translateX(6px) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
            cursor: pointer;
        }

        .mission-subcat-item:hover .mission-subcat-name {
            color: var(--primary) !important; 
            font-weight: 700;
        }
        
        /* ===== BOUTON DEVENIR PRESTATAIRE ===== */
        .btn-become-provider {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 10px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
        }
        
        .btn-become-provider:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.4);
        }
        
        .btn-become-provider i {
            font-size: 0.9rem;
        }
        
        /* Badge Prestataire actif */
        .btn-provider-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            color: #166534;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid #86efac;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-provider-badge:hover {
            background: linear-gradient(135deg, #bbf7d0, #86efac);
            border-color: #4ade80;
        }
        
        .btn-provider-badge i {
            color: #22c55e;
        }
        
        /* Bouton de déconnexion visible */
        .btn-logout-visible {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #dc2626;
            font-size: 1rem;
            border: 1px solid #fca5a5;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn-logout-visible:hover {
            background: linear-gradient(135deg, #fecaca, #fca5a5);
            color: #b91c1c;
            transform: scale(1.05);
        }
        
        /* Badges de compétences */
        .competence-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 20px;
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            color: #0369a1;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid #bae6fd;
            transition: all 0.2s;
        }
        
        .competence-badge:hover {
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            transform: translateY(-1px);
        }
    </style>

    <!-- Bootstrap JS via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
    @stack('scripts')
    
    {{-- Notification bell: mark-as-read on click + mark all --}}
    @auth
    <script>
    (function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const headers = { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' };

        // Mark individual notification as read on click
        document.querySelectorAll('.notif-dropdown-item[data-notif-id]').forEach(el => {
            el.addEventListener('click', function() {
                const notifId = this.dataset.notifId;
                if (notifId) {
                    fetch('/notifications/' + notifId + '/mark-read', { method: 'POST', headers });
                    // Visually mark as read
                    this.classList.remove('notif-unread');
                    const dot = this.querySelector('.notif-unread-dot');
                    if (dot) dot.remove();
                    updateCounter(-1);
                }
            });
        });

        // Mark all as read
        const markAllBtn = document.getElementById('markAllReadBtn');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                fetch('{{ route("notifications.mark-all-read") }}', { method: 'POST', headers })
                    .then(() => {
                        // Visually update all items
                        document.querySelectorAll('.notif-dropdown-item.notif-unread').forEach(item => {
                            item.classList.remove('notif-unread');
                            const dot = item.querySelector('.notif-unread-dot');
                            if (dot) dot.remove();
                        });
                        updateCounter(0, true);
                        this.style.display = 'none';
                    });
            });
        }

        // Update the counter badge
        function updateCounter(change, reset) {
            const badge = document.querySelector('.header-notif-btn .notif-counter');
            if (!badge) return;
            if (reset) {
                // Keep live unread (verification returned/rejected) which can't be marked via DB
                const liveCount = document.querySelectorAll('.notif-dropdown-item.notif-unread').length;
                if (liveCount === 0) badge.style.display = 'none';
                else { badge.textContent = liveCount; }
                return;
            }
            let current = parseInt(badge.textContent) || 0;
            current = Math.max(0, current + change);
            if (current === 0) badge.style.display = 'none';
            else badge.textContent = current > 99 ? '99+' : current;
        }
    })();
    </script>
    @endauth

    {{-- Include modals for authenticated users --}}
    @auth
        @include('partials.provider-modal')
        @include('partials.verify-profile-modal')
        @include('partials.category-selection-modal')
        @if(Auth::user()->isOAuthUser() && !Auth::user()->profile_completed && !Auth::user()->hasCompletedProOnboarding() && !Auth::user()->hasActiveProSubscription())
            @include('partials.provider-oauth-modal')
        @endif
        @if(session('show_provider_welcome') && !Auth::user()->hasCompletedProOnboarding() && !Auth::user()->hasActiveProSubscription())
            @include('partials.provider-welcome-modal')
        @endif
    @endauth
</body>
</html>




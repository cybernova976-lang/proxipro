<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @include('partials.pwa-head')
    <title>{{ config('app.name', 'Prokejem') }} — Trouvez un prestataire pour vos services du quotidien</title>
    <meta name="description" content="Bricolage, ménage, jardinage, cours : trouvez un prestataire ou publiez gratuitement votre besoin sur {{ config('app.name', 'Prokejem') }}. Vous proposez vos services ? Créez votre profil.">
    <link rel="canonical" href="{{ url('/') }}">
    
    <!-- Open Graph / Social Sharing -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ config('app.name', 'Prokejem') }} — Trouvez un prestataire près de chez vous">
    <meta property="og:description" content="Décrivez votre besoin, comparez les propositions et choisissez le prestataire qui vous convient.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ asset('images/social-card.png') }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ config('app.name', 'Prokejem') }}, services de proximité">
    <meta property="og:site_name" content="{{ config('app.name', 'Prokejem') }}">
    <meta property="og:locale" content="fr_FR">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ config('app.name', 'Prokejem') }} — Services de proximité">
    <meta name="twitter:description" content="Publiez un besoin et comparez les propositions de prestataires.">
    <meta name="twitter:image" content="{{ asset('images/social-card.png') }}">
    <script type="application/ld+json">
        {!! json_encode([
            '@'.'context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name', 'Prokejem'),
            'url' => url('/'),
            'logo' => asset('images/brand/prokejem-logo.png'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/ads') . '?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/form-validation.css') }}?v=20260902">
    <script defer src="{{ asset('js/form-validation.js') }}?v=20260902"></script>

    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #eef2ff;
            --secondary: #7c3aed;
            --accent: #f59e0b;
            --success: #10b981;
            --dark: #0f172a;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-50);
            color: var(--gray-700);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }
        
        /* ===== NAVBAR ===== */
        .nav-main {
            padding: 0;
            z-index: 9999;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(0,0,0,0.06);
            transition: all 0.3s;
        }
        .nav-main.scrolled { box-shadow: 0 1px 20px rgba(0,0,0,0.08); }
        
        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 0;
        }
        .nav-brand {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none; font-weight: 900; font-size: 1.4rem;
            color: var(--primary) !important; letter-spacing: -0.5px;
        }
        .nav-brand-wordmark {
            width: auto !important;
            height: 40px !important;
            max-width: min(190px, 44vw);
        }
        .nav-links {
            display: flex; align-items: center; gap: 6px;
            list-style: none; margin: 0; padding: 0;
        }
        .nav-links a {
            padding: 8px 16px; font-weight: 600; font-size: 0.9rem;
            color: var(--gray-600); text-decoration: none;
            border-radius: 10px; transition: all 0.2s;
        }
        .nav-links a:hover { background: var(--gray-100); color: var(--gray-900); }
        .btn-nav-login {
            padding: 9px 20px !important; font-weight: 700 !important;
            color: var(--primary) !important; border: 2px solid var(--primary);
            border-radius: 12px !important;
        }
        .btn-nav-login:hover { background: var(--primary-light) !important; }
        .btn-nav-register {
            padding: 9px 22px !important; font-weight: 700 !important;
            color: white !important; background: var(--primary) !important;
            border-radius: 12px !important; border: 2px solid transparent;
            transition: all 0.3s !important;
        }
        .btn-nav-register:hover {
            background: var(--primary-dark) !important; transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79,70,229,0.35);
        }
        .nav-burger {
            display: none; background: none; border: none;
            font-size: 1.4rem; color: var(--gray-700); cursor: pointer; padding: 6px;
        }
        @media (max-width: 991px) {
            .nav-burger { display: block; }
            .nav-links { display: none; }
        }
        
        /* ===== HERO ===== */
        .hero {
            position: relative; padding: 64px 0;
            background: linear-gradient(125deg, #0f172a 0%, #23215c 65%, #243b63 100%);
            overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(ellipse at top right, rgba(94,234,212,0.12), transparent 60%);
            z-index: 1;
        }
        .hero::after {
            content: ''; position: absolute; bottom: -150px; left: -100px;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(168,85,247,0.15) 0%, transparent 65%);
            border-radius: 50%;
            z-index: 1;
        }
        
        .hero-grid { position: relative; z-index: 2; }
        .hero-label {
            display: inline-flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);
            border-radius: 50px; padding: 8px 20px; font-size: 0.85rem;
            font-weight: 600; color: rgba(255,255,255,0.9);
            margin-bottom: 28px; backdrop-filter: blur(10px);
        }
        .hero-label-dot {
            width: 8px; height: 8px; background: #34d399;
            border-radius: 50%;
        }
        
        .hero-title {
            font-size: clamp(2.2rem, 4.2vw, 3.3rem); font-weight: 800;
            line-height: 1.13; color: white; margin-bottom: 24px; letter-spacing: -1px;
        }
        .hero-title .accent {
            background: linear-gradient(135deg, #a5b4fc, #99f6e4);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .hero-desc {
            font-size: 1.05rem; color: #cbd5e1;
            max-width: 500px; line-height: 1.7; margin-bottom: 24px;
        }
        .hero-provider-link { color: #e0e7ff; font-size: .9rem; font-weight: 600; text-underline-offset: 5px; }
        .hero-provider-link:hover { color: white; }
        .hero-title-line { display: block; }
        
        /* Trust badges in hero */
        .hero-trust { display: flex; justify-content: center; gap: 32px; margin-top: 32px; flex-wrap: wrap; }
        .hero-trust-item {
            display: flex; align-items: center; gap: 10px;
            color: rgba(255,255,255,0.6); font-size: 0.85rem; font-weight: 500;
        }
        .hero-trust-icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: rgba(255,255,255,0.08); display: flex;
            align-items: center; justify-content: center;
            color: #a5b4fc; font-size: 0.95rem;
        }
        
        /* Search Card */
        .search-card {
            background: white; padding: 32px; border-radius: 24px; max-width: 500px; margin-left: auto;
            box-shadow: 0 35px 60px -15px rgba(0,0,0,0.2);
        }
        .search-card-title {
            font-size: 1.1rem; font-weight: 800; color: var(--gray-900);
            margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
        }
        .search-card-title i { color: var(--primary); }
        .search-field { position: relative; margin-bottom: 14px; }
        .search-field i {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            color: var(--gray-400); font-size: 0.9rem; z-index: 2;
        }
        .search-field input, .search-field select {
            width: 100%; border: 2px solid var(--gray-200); border-radius: 14px;
            padding: 14px 14px 14px 44px; font-size: 0.95rem;
            font-family: inherit; transition: all 0.2s; background: white; color: var(--gray-700);
            -webkit-appearance: none; appearance: none;
        }
        .search-field input:focus, .search-field select:focus {
            outline: none; border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79,70,229,0.1);
        }
        .search-field input::placeholder { color: var(--gray-400); }
        .btn-hero-search {
            width: 100%; padding: 15px; background: var(--primary);
            color: white; border: none; border-radius: 14px;
            font-weight: 700; font-size: 1rem; cursor: pointer;
            transition: all 0.3s; display: flex; align-items: center;
            justify-content: center; gap: 8px;
        }
        .btn-hero-search:hover {
            background: var(--primary-dark); transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(79,70,229,0.35);
        }
        
        /* Popular searches */
        .popular-searches {
            margin-top: 16px; display: flex; flex-wrap: wrap;
            gap: 8px; align-items: center;
        }
        .popular-searches span { font-size: 0.78rem; color: var(--gray-400); font-weight: 600; }
        .popular-tag {
            display: inline-block; padding: 5px 12px;
            background: var(--gray-100); border: 1px solid var(--gray-200);
            border-radius: 20px; font-size: 0.78rem; font-weight: 500;
            color: var(--gray-600); text-decoration: none; transition: all 0.2s;
        }
        .popular-tag:hover {
            background: var(--primary-light); border-color: var(--primary); color: var(--primary);
        }
        
        /* ===== SECTIONS ===== */
        .section { padding: 56px 0; }
        .section-bg { background: white; }
        .section-head { text-align: center; margin-bottom: 32px; }
        .section-pill {
            display: inline-block; background: var(--primary-light); color: var(--primary);
            font-size: 0.78rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1.5px; padding: 6px 18px; border-radius: 50px; margin-bottom: 14px;
        }
        .section-title {
            font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 800;
            color: var(--gray-900); letter-spacing: -0.5px; margin-bottom: 10px;
        }
        .section-desc {
            color: var(--gray-500); font-size: 1.05rem; max-width: 560px; margin: 0 auto;
        }
        
        /* ===== CATEGORIES ===== */
        .cat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .cat-card {
            display: flex; align-items: center; gap: 14px; padding: 18px 20px;
            background: white; border: 1px solid var(--gray-200); border-radius: 16px;
            text-decoration: none; color: var(--gray-700); transition: all 0.25s;
        }
        .cat-card:hover {
            transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            border-color: transparent; color: var(--primary);
        }
        .cat-icon {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: white; flex-shrink: 0; transition: transform 0.3s;
        }
        .cat-card:hover .cat-icon { transform: scale(1.1) rotate(-5deg); }
        .cat-info h5 { font-weight: 700; font-size: 0.9rem; margin: 0 0 2px; }
        .cat-info span { font-size: 0.78rem; color: var(--gray-400); font-weight: 500; }
        .btn-see-all {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 28px; border: 2px solid var(--gray-200);
            border-radius: 12px; font-weight: 700; font-size: 0.9rem;
            color: var(--gray-600); text-decoration: none; transition: all 0.2s;
            margin-top: 32px;
        }
        .btn-see-all:hover {
            border-color: var(--primary); color: var(--primary); background: var(--primary-light);
        }
        
        /* ===== AD CARDS ===== */
        .ad-card {
            background: white; border: 1px solid var(--gray-200); border-radius: 16px;
            overflow: hidden; transition: all 0.3s; text-decoration: none;
            color: inherit; display: block; height: 100%;
        }
        .ad-card:hover {
            transform: translateY(-5px); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15);
            border-color: transparent; color: inherit;
        }
        .ad-card-img {
            width: 100%; height: 200px; object-fit: cover; background: var(--gray-100);
            display: flex; align-items: center; justify-content: center;
            color: var(--gray-400); font-size: 2.5rem; position: relative; overflow: hidden;
        }
        .ad-card-img img { width: 100%; height: 200px; object-fit: cover; transition: transform 0.4s; }
        .ad-card:hover .ad-card-img img { transform: scale(1.05); }
        .ad-card-badge {
            position: absolute; top: 12px; left: 12px;
            background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);
            padding: 4px 10px; border-radius: 8px; font-size: 0.72rem;
            font-weight: 700; color: var(--primary); text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .ad-card-body { padding: 18px; }
        .ad-card h4 {
            font-weight: 700; font-size: 1rem; color: var(--gray-900);
            margin-bottom: 8px; line-height: 1.35;
        }
        .ad-card-location { font-size: 0.85rem; color: var(--gray-500); margin-bottom: 10px; }
        .ad-card-bottom { display: flex; justify-content: space-between; align-items: center; }
        .ad-card-price { font-weight: 800; font-size: 1.15rem; color: var(--primary); }
        .ad-card-time { font-size: 0.78rem; color: var(--gray-400); }
        
        /* ===== STEPS ===== */
        .steps-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px;
            position: relative;
        }
        .steps-grid::before {
            content: ''; position: absolute; top: 46px; left: 20%; right: 20%;
            height: 2px; background: linear-gradient(90deg, var(--primary), var(--secondary));
            opacity: 0.2; z-index: 0;
        }
        .step-card { text-align: center; padding: 40px 24px; position: relative; z-index: 1; }
        .step-num {
            width: 56px; height: 56px; border-radius: 18px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white; display: flex; align-items: center; justify-content: center;
            font-size: 22px; font-weight: 900; margin: 0 auto 24px;
            box-shadow: 0 8px 24px rgba(79,70,229,0.25);
        }
        .step-card h4 { font-weight: 800; font-size: 1.1rem; color: var(--gray-900); margin-bottom: 10px; }
        .step-card p { color: var(--gray-500); font-size: 0.95rem; line-height: 1.7; margin: 0; }
        
        /* ===== TESTIMONIALS ===== */
        .testimonials-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .testimonial-card {
            background: white; border: 1px solid var(--gray-200);
            border-radius: 20px; padding: 32px; transition: all 0.3s;
        }
        .testimonial-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .testimonial-stars { display: flex; gap: 2px; color: #fbbf24; font-size: 0.85rem; margin-bottom: 16px; }
        .testimonial-text {
            font-size: 0.95rem; color: var(--gray-600); line-height: 1.7;
            margin-bottom: 20px; font-style: italic;
        }
        .testimonial-author { display: flex; align-items: center; gap: 12px; }
        .testimonial-avatar {
            width: 44px; height: 44px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 1rem; flex-shrink: 0;
        }
        .testimonial-name { font-weight: 700; font-size: 0.9rem; color: var(--gray-900); }
        .testimonial-role { font-size: 0.78rem; color: var(--gray-400); }
        
        /* ===== DUAL CTA ===== */
        .dual-cta { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .cta-card {
            border-radius: 24px; padding: 48px 40px; position: relative; overflow: hidden;
        }
        .cta-card::after {
            content: ''; position: absolute; top: -50%; right: -30%;
            width: 300px; height: 300px; border-radius: 50%; opacity: 0.1;
        }
        .cta-client { background: linear-gradient(135deg, var(--primary), #6366f1); color: white; }
        .cta-client::after { background: white; }
        .cta-pro { background: linear-gradient(135deg, var(--gray-900), var(--gray-800)); color: white; }
        .cta-pro::after { background: white; }
        .cta-card-icon {
            width: 56px; height: 56px; border-radius: 16px;
            background: rgba(255,255,255,0.15); display: flex;
            align-items: center; justify-content: center;
            font-size: 24px; margin-bottom: 20px;
        }
        .cta-card h3 { font-weight: 800; font-size: 1.5rem; margin-bottom: 12px; }
        .cta-card p {
            opacity: 0.8; font-size: 1rem; line-height: 1.6;
            margin-bottom: 24px; max-width: 380px;
        }
        .btn-cta-white {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 14px 28px; background: white; color: var(--primary);
            border: none; border-radius: 14px; font-weight: 700; font-size: 0.95rem;
            text-decoration: none; transition: all 0.3s;
        }
        .btn-cta-white:hover {
            transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            color: var(--primary-dark);
        }
        .btn-cta-outline {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 14px 28px; background: transparent; color: white;
            border: 2px solid rgba(255,255,255,0.3); border-radius: 14px;
            font-weight: 700; font-size: 0.95rem; text-decoration: none; transition: all 0.3s;
        }
        .btn-cta-outline:hover {
            background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.6); color: white;
        }
        
        /* ===== PRESS BAR ===== */
        .press-bar { padding: 48px 0; text-align: center; }
        .press-label {
            font-size: 0.78rem; font-weight: 600; color: var(--gray-400);
            text-transform: uppercase; letter-spacing: 2px; margin-bottom: 24px;
        }
        .press-logos {
            display: flex; align-items: center; justify-content: center;
            gap: 48px; flex-wrap: wrap; opacity: 0.35;
        }
        .press-logo { font-size: 1.5rem; font-weight: 900; color: var(--gray-600); letter-spacing: -0.5px; }
        
        /* ===== FOOTER ===== */
        .footer {
            background: var(--gray-900); color: rgba(255,255,255,0.5); padding: 64px 0 0;
        }
        .footer-grid {
            display: grid; grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px; padding-bottom: 48px;
        }
        .footer-brand h4 {
            color: white; font-weight: 800; font-size: 1.3rem;
            margin-bottom: 14px; display: flex; align-items: center; gap: 10px;
        }
        .footer-brand-icon {
            width: 32px; height: 32px;
            background: white;
            border-radius: 8px; display: inline-flex; align-items: center;
            justify-content: center; padding: 2px; overflow: hidden;
        }
        .hero-title-line { display: block; text-wrap: balance; }
        .footer-brand-icon .prokejem-brand-mark {
            width: 100%; height: 100%; object-fit: contain; display: block;
        }
        .footer-brand p { font-size: 0.9rem; line-height: 1.7; margin-bottom: 20px; }
        .footer-brand > [data-site-share-trigger] { margin-bottom: 18px; }
        .footer .pwa-install-button { position: static; margin: 0 0 18px; box-shadow: none; }
        .footer-social { display: flex; gap: 10px; }
        .footer-social a {
            width: 38px; height: 38px; border-radius: 10px;
            background: rgba(255,255,255,0.06); display: flex;
            align-items: center; justify-content: center;
            color: rgba(255,255,255,0.5); text-decoration: none; transition: all 0.3s;
        }
        .footer-social a:hover { background: var(--primary); color: white; transform: translateY(-2px); }
        .footer-col h5 { color: white; font-weight: 700; font-size: 0.9rem; margin-bottom: 20px; }
        .footer-col ul { list-style: none; padding: 0; margin: 0; }
        .footer-col li { margin-bottom: 12px; }
        .footer-col a {
            color: rgba(255,255,255,0.5); text-decoration: none; font-size: 0.88rem; transition: all 0.2s;
        }
        .footer-col a:hover { color: white; }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.06); padding: 24px 0;
            display: flex; justify-content: space-between; align-items: center;
            font-size: 0.82rem;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 991px) {
            .search-card { max-width: none; }
            .cat-grid { grid-template-columns: repeat(2, 1fr); }
            .testimonials-grid { grid-template-columns: 1fr; }
            .dual-cta { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .hero { padding: 50px 0 70px; }
            .hero-trust { flex-wrap: wrap; gap: 16px; }
            .steps-grid::before { display: none; }
        }
        @media (max-width: 767px) {
            html, body { width: 100%; max-width: 100%; overflow-x: clip; }
            .hero .container {
                width: 100%;
                max-width: 100%;
                padding-left: 12px;
                padding-right: 12px;
            }
            .hero .row {
                width: 100%;
                max-width: 100%;
                margin-left: 0;
                margin-right: 0;
                --bs-gutter-x: 0;
            }
            .hero .row > * { width: 100%; max-width: 100%; min-width: 0; padding-left: 0; padding-right: 0; }
            .cat-grid { grid-template-columns: 1fr; }
            .steps-grid { grid-template-columns: 1fr; gap: 16px; }
            .section { padding: 40px 0; }
            .footer-grid { grid-template-columns: 1fr; gap: 32px; }
            .footer-bottom { flex-direction: column; gap: 8px; text-align: center; }
            .hero-title {
                max-width: 100%;
                font-size: clamp(1.85rem, 7.5vw, 2.5rem);
                line-height: 1.22;
                letter-spacing: -0.6px;
                overflow-wrap: normal;
            }
            .hero-title .accent { white-space: normal; }
            .hero-desc { max-width: 100%; font-size: 1rem; overflow-wrap: anywhere; }
            .hero-trust-item { min-width: 0; }
            .hero .mb-5 { margin-bottom: 24px !important; }
            .hero-trust { justify-content: flex-start; }
            .hero-trust-item { flex: 1 1 150px; }
            .hero-trust-icon { flex-shrink: 0; }
            .search-card { width: 100%; max-width: 100%; min-width: 0; padding: 24px; }
            .search-card form,
            .search-field,
            .search-field input,
            .search-field select { min-width: 0; max-width: 100%; }
            .press-logos { gap: 24px; }
            .cta-card { padding: 32px 24px; }
        }

        @media (max-width: 390px) {
            .hero { padding-top: 38px; }
            .hero-label { max-width: 100%; padding: 8px 14px; }
            .hero-trust { margin-top: 34px; }
            .search-card { padding: 20px 16px; border-radius: 20px; }
        }

        html.pwa-install-available #scrollTopBtn {
            bottom: calc(84px + env(safe-area-inset-bottom)) !important;
        }
        
        /* Keep content readable without JavaScript and respect reduced motion. */
        :where(a, button, select, input):focus-visible {
            outline: 3px solid #818cf8; outline-offset: 4px;
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
        }
    </style>
    <link rel="preload" as="image" type="image/webp" href="{{ asset('images/home/services-proximite-artisan-homme-1920.webp') }}" imagesrcset="{{ asset('images/home/services-proximite-artisan-homme-960.webp') }} 960w, {{ asset('images/home/services-proximite-artisan-homme-1920.webp') }} 1920w" imagesizes="100vw" fetchpriority="high">
    <link rel="stylesheet" href="{{ asset('css/public-home.css') }}?v=20261025-photo1">
    <script defer src="{{ asset('js/public-home.js') }}?v=20261024"></script>
</head>
<body class="public-home">
    <a href="#home-main" class="home-skip-link">Aller au contenu</a>
    <!-- ===== NAVBAR ===== -->
    <nav class="nav-main sticky-top" id="mainNav">
        <div class="container">
            <div class="nav-inner">
                <a href="{{ url('/') }}" class="nav-brand">
                    <x-brand-mark variant="wordmark" :size="40" class="nav-brand-wordmark" :decorative="false" />
                </a>
                <ul class="nav-links">
                    <li><a href="{{ url('/ads') }}">Annonces</a></li>
                    <li><a href="#comment-ca-marche">Comment ça marche</a></li>
                    <li><a href="#espace-prestataires">Proposer mes services</a></li>
                    @auth
                        <li><a href="{{ route('feed') }}" class="btn-nav-login">Accueil</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="btn-nav-login"><i class="fas fa-sign-in-alt me-1"></i>Connexion</a></li>
                        @if (Route::has('register'))
                            <li><a href="{{ route('demand.create') }}" class="btn-nav-register">Publier un besoin</a></li>
                        @endif
                    @endauth
                </ul>
                <a href="{{ route('demand.create') }}" class="home-mobile-publish">Publier un besoin</a>
                <button type="button" class="nav-burger" id="navBurger" onclick="toggleMobileMenu()" aria-label="Ouvrir le menu" aria-controls="mobileMenu" aria-expanded="false">
                    <i class="fas fa-bars" id="burgerIcon"></i>
                </button>
            </div>
            <div id="mobileMenu" aria-hidden="true" style="padding-bottom: 16px; display: none; opacity: 0; transition: opacity 0.25s ease;">
                <div class="d-flex flex-column gap-2">
                    <a href="#espace-prestataires" class="d-block py-2 text-decoration-none text-dark fw-semibold">Proposer mes services</a>
                    <a href="#comment-ca-marche" class="d-block py-2 text-decoration-none text-dark fw-semibold">Comment ça marche</a>
                    <a href="{{ url('/ads') }}" class="d-block py-2 text-decoration-none text-dark fw-semibold">
                        <i class="fas fa-bullhorn me-2 text-muted"></i>Annonces
                    </a>
                    <a href="{{ route('guides.index') }}" class="d-block py-2 text-decoration-none text-dark fw-semibold">
                        <i class="fas fa-compass me-2 text-muted"></i>Conseils
                    </a>
                    <a href="{{ route('contact.index') }}" class="d-block py-2 text-decoration-none text-dark fw-semibold">
                        <i class="fas fa-envelope me-2 text-muted"></i>Contact
                    </a>
                    <button type="button" class="btn btn-light text-start fw-semibold" data-site-share-trigger>
                        <i class="fas fa-share-nodes me-2 text-primary"></i>Partager {{ config('app.name', 'Prokejem') }}
                    </button>
                    @auth
                        <a href="{{ route('feed') }}" class="btn btn-primary rounded-3 mt-2 py-2">
                            <i class="fas fa-home me-2"></i>Accueil
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-primary rounded-3 mt-2 py-2">
                            <i class="fas fa-sign-in-alt me-2"></i>Connexion
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-primary rounded-3 py-2">
                                <i class="fas fa-user-plus me-2"></i>S'inscrire gratuitement
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- ===== HERO ===== -->
    <main id="home-main">
    <header class="hero" id="home-hero">
        <picture class="home-hero-media" aria-hidden="true">
            <img src="{{ asset('images/home/services-proximite-artisan-homme-1920.webp') }}"
                 srcset="{{ asset('images/home/services-proximite-artisan-homme-960.webp') }} 960w, {{ asset('images/home/services-proximite-artisan-homme-1920.webp') }} 1920w"
                 sizes="100vw" width="1920" height="960" alt="" fetchpriority="high" decoding="async">
        </picture>
        <div class="container hero-grid">
            <div class="home-hero-intro">
                    <div class="hero-label">
                        <span class="hero-label-dot"></span>
                        Services de proximité, simplement
                    </div>
                    <h1 class="hero-title">
                        <span class="hero-title-line">Trouvez le bon prestataire,</span>
                        <span class="hero-title-line accent">près de chez vous.</span>
                    </h1>
                    <p class="hero-desc">
                        Un robinet à réparer, un jardin à entretenir, un coup de main au quotidien ?
                        Décrivez votre besoin et échangez avec les prestataires intéressés.
                    </p>
                    <div class="home-hero-actions">
                        <a href="{{ route('demand.create') }}" class="home-primary-action">Publier mon besoin <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                        <span>Publication gratuite · Vous gardez le choix</span>
                    </div>
                    <a href="#espace-prestataires" class="hero-provider-link">Je propose mes services <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i></a>
            </div>
                    <div class="search-card home-discovery-card">
                        <div class="search-card-title">
                            <i class="fas fa-search"></i>
                            Rechercher un service
                        </div>
                        <form action="{{ route('ads.index') }}" method="GET" class="home-discovery-fields">
                            <input type="hidden" name="service_type" value="offre">
                            <div class="home-discovery-control">
                            <label class="home-field-label" for="home-service">Service recherché</label>
                            <div class="search-field">
                                <i class="fas fa-search" aria-hidden="true"></i>
                                <input id="home-service" name="q" type="search" placeholder="Plombier, ménage, cours…" maxlength="120">
                            </div>
                            </div>
                            <div class="home-discovery-control">
                            <label class="home-field-label" for="home-location">Ville ou code postal <span>(facultatif)</span></label>
                            <div class="search-field">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                <input id="home-location" type="text" name="location" placeholder="Ex. Mamoudzou" aria-label="Localisation" maxlength="120">
                            </div>
                            </div>
                            <button type="submit" class="btn-hero-search">
                                Voir les services <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </button>
                        </form>
                        <p class="home-search-note">Parcourez les offres sans créer de compte.</p>
                    </div>
        </div>
            <div class="container hero-grid">
                <div class="hero-trust">
                    <div class="hero-trust-item">
                        <div class="hero-trust-icon"><i class="fas fa-shield-alt" aria-hidden="true"></i></div>
                        <div><strong class="d-block text-white">Badge d'identité</strong><small>Visible après contrôle</small></div>
                    </div>
                    <div class="hero-trust-item">
                        <div class="hero-trust-icon"><i class="fas fa-lock" aria-hidden="true"></i></div>
                        <div><strong class="d-block text-white">Échanges directs</strong><small>Une messagerie dédiée</small></div>
                    </div>
                    <div class="hero-trust-item">
                        <div class="hero-trust-icon"><i class="fas fa-star" aria-hidden="true"></i></div>
                        <div><strong class="d-block text-white">Avis liés aux missions</strong><small>Après une commande terminée</small></div>
                    </div>
                </div>
            </div>
    </header>

    <!-- ===== CATEGORIES ===== -->
    <section class="section section-bg home-categories" id="services">
        <div class="container">
            <div class="section-head reveal">
                <span class="section-pill">Services</span>
                <h2 class="section-title">Quel service vous rendrait la vie plus simple ?</h2>
                <p class="section-desc">Une catégorie, puis les services qui vous correspondent.</p>
            </div>
            <div class="cat-grid">
                @php $shown = 0; @endphp
                @foreach($categoriesWithSubs as $catName => $catData)
                    @if($shown < 6)
                    <a href="{{ route('ads.index', ['category' => $catName, 'service_type' => 'offre']) }}" class="cat-card reveal">
                        <div class="cat-icon" style="background:{{ $catData['color'] }};"><i class="{{ $catData['icon'] }}"></i></div>
                        <div class="cat-info">
                            <h5>{{ $catName }}</h5>
                            <span>Voir les services <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i></span>
                        </div>
                    </a>
                    @php $shown++; @endphp
                    @endif
                @endforeach
            </div>
            <div class="text-center">
                <a href="{{ route('ads.index', ['service_type' => 'offre']) }}" class="btn-see-all">
                    Explorer tous les services <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- ===== FEATURED PROFESSIONALS ===== -->
    @if($featuredPros->count() > 0)
    <section class="section home-providers" id="prestataires">
        <div class="container">
            <div class="section-head reveal">
                <span class="section-pill">Prestataires</span>
                <h2 class="section-title">Des prestataires à découvrir</h2>
                <p class="section-desc">Un visage, un savoir-faire, une ville. Consultez les profils et trouvez à qui parler.</p>
            </div>
            <p class="home-swipe-hint">Faites défiler les profils <i class="fas fa-arrow-right" aria-hidden="true"></i></p>
            <div class="home-provider-grid" aria-label="Prestataires à découvrir">
                @foreach($featuredPros as $pro)
                <div class="home-provider-item">
                    <a href="{{ route('profile.public', $pro->id) }}" class="ad-card reveal" style="text-decoration:none;">
                        <div style="padding:24px 20px; text-align:center;">
                            @if($pro->avatar)
                                <img src="{{ storage_url($pro->avatar) }}" alt="{{ $pro->name }}" width="72" height="72" loading="lazy" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid var(--primary-light);margin-bottom:12px;">
                            @else
                                <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;font-weight:700;margin:0 auto 12px;">{{ strtoupper(substr($pro->name, 0, 1)) }}</div>
                            @endif
                            @if($pro->identity_verified)
                                <span style="display:inline-flex;align-items:center;gap:4px;background:#ecfdf5;color:#047857;padding:3px 10px;border-radius:8px;font-size:0.72rem;font-weight:700;margin-bottom:8px;"><i class="fas fa-check-circle" style="font-size:0.65rem;"></i> Identité vérifiée</span>
                            @endif
                            <h4 style="font-size:1rem;font-weight:700;color:var(--gray-900);margin-bottom:4px;">{{ $pro->name }}</h4>
                            @if($pro->profession)
                                <div style="font-size:0.85rem;color:var(--primary);font-weight:600;margin-bottom:6px;">{{ $pro->profession }}</div>
                            @endif
                            @if($pro->city || $pro->country)
                                <div style="font-size:0.82rem;color:var(--gray-500);"><i class="fas fa-map-marker-alt me-1"></i>{{ $pro->city ?? '' }}{{ $pro->city && $pro->country ? ', ' : '' }}{{ $pro->country ?? '' }}</div>
                            @endif
                            @if($pro->verified_reviews_count > 0)
                                <div style="font-size:0.82rem;color:#f59e0b;margin-top:7px;font-weight:700;">
                                    <i class="fas fa-star"></i> {{ number_format((float) $pro->verified_reviews_avg, 1, ',', ' ') }}
                                    <span style="color:var(--gray-400);font-weight:500;">({{ $pro->verified_reviews_count }} avis de mission)</span>
                                </div>
                            @endif
                            @if($pro->bio)
                                <p style="font-size:0.82rem;color:var(--gray-500);margin-top:8px;line-height:1.4;">{{ Str::limit($pro->bio, 80) }}</p>
                            @endif
                            <span class="d-inline-block mt-3 fw-semibold" style="font-size:.85rem;color:var(--primary);">Voir le profil <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i></span>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
            <div class="text-center" style="margin-top:24px;">
                <a href="{{ route('demand.create') }}" class="btn-see-all">Décrire mon besoin <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>
    @endif

    @include('pages.partials.home-latest-ads')

    <!-- ===== HOW IT WORKS ===== -->
    <section class="section section-bg" id="comment-ca-marche">
        <div class="container">
            <div class="section-head reveal">
                <span class="section-pill">Simple & rapide</span>
                <h2 class="section-title">Comment ça marche ?</h2>
                <p class="section-desc">Votre prochain pas, selon ce que vous cherchez.</p>
            </div>
            @include('pages.partials.home-journeys')
        </div>
    </section>

    <!-- ===== VERIFIED REVIEWS ===== -->
    @if($verifiedReviews->isNotEmpty())
    <section class="section">
        <div class="container">
            <div class="section-head reveal">
                <span class="section-pill">Avis de mission</span>
                <h2 class="section-title">Des retours issus de vraies commandes</h2>
                <p class="section-desc">Ces avis ne sont affichés qu'après une mission terminée et un paiement libéré.</p>
            </div>
            <div class="testimonials-grid">
                @foreach($verifiedReviews as $review)
                <div class="testimonial-card reveal">
                    <div class="testimonial-stars">
                        @for($star = 1; $star <= 5; $star++)
                            <i class="{{ $star <= $review->rating ? 'fas' : 'far' }} fa-star"></i>
                        @endfor
                    </div>
                    <p class="testimonial-text">“{{ Str::limit($review->comment, 220) }}”</p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">{{ Str::upper(Str::substr($review->reviewer?->name ?? 'M', 0, 1)) }}</div>
                        <div>
                            <div class="testimonial-name">{{ Str::before($review->reviewer?->name ?? 'Membre', ' ') }}</div>
                            <div class="testimonial-role">
                                Mission pour {{ $review->reviewedUser?->profession ?: $review->reviewedUser?->name }}
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- ===== DUAL CTA ===== -->
    <section class="section section-bg" id="espace-prestataires">
        <div class="container">
            <div class="dual-cta">
                <div class="cta-card cta-client reveal">
                    <div class="cta-card-icon"><i class="fas fa-search"></i></div>
                    <h3>Vous cherchez un prestataire ?</h3>
                    <p>Expliquez votre projet et recevez les propositions des prestataires intéressés.</p>
                    <a href="{{ route('demand.create') }}" class="btn-cta-white">
                        Publier ma demande <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="cta-card cta-pro reveal">
                    <div class="cta-card-icon"><i class="fas fa-briefcase"></i></div>
                    <h3>Votre savoir-faire mérite d’être découvert.</h3>
                    <p>Présentez vos services et vos réalisations. Pour les professionnels, l’espace PRO réunit aussi clients, devis et factures.</p>
                    <ul class="home-pro-benefits">
                        <li><i class="fas fa-user-circle" aria-hidden="true"></i> Un profil pour présenter votre activité</li>
                        <li><i class="fas fa-comments" aria-hidden="true"></i> Des échanges regroupés au même endroit</li>
                        <li><i class="fas fa-file-invoice" aria-hidden="true"></i> Des outils de devis et factures pour les pros</li>
                    </ul>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="{{ route('register') }}" class="btn-cta-white" style="color:var(--gray-900);">
                            Créer mon profil prestataire <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="{{ route('legal.pro-terms') }}" class="home-pro-conditions">Conditions de l’espace PRO</a>
                    </div>
                    <small class="d-block mt-3">L’émission de documents professionnels nécessite un statut et une identité d’émetteur complétés, ainsi que l’acceptation des conditions PRO.</small>
                </div>
            </div>
        </div>
    </section>
    @include('pages.partials.home-faq')
    </main>
    <!-- ===== FOOTER ===== -->
    <footer class="footer" id="home-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h4><x-brand-mark variant="wordmark" :size="42" :decorative="false" /></h4>
                    <p>La plateforme de mise en relation pour publier un besoin, comparer des propositions et travailler avec un prestataire près de chez vous.</p>
                    <button type="button" class="btn btn-outline-light rounded-3" data-site-share-trigger>
                        <i class="fas fa-share-nodes me-2"></i>Partager {{ config('app.name', 'Prokejem') }}
                    </button>
                    @include('partials.pwa-install')
                    @if(collect(config('site.social', []))->filter()->isNotEmpty())
                    <div class="footer-social">
                        @foreach(config('site.social') as $network => $url)
                            @if($url)
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ ucfirst($network) }}"><i class="fab fa-{{ $network }}{{ $network === 'linkedin' ? '-in' : ($network === 'facebook' ? '-f' : '') }}"></i></a>
                            @endif
                        @endforeach
                    </div>
                    @endif
                </div>
                <div class="footer-col">
                    <h5>Plateforme</h5>
                    <ul>
                        <li><a href="{{ route('demand.create') }}">Trouver un professionnel</a></li>
                        <li><a href="{{ route('guides.index') }}">Conseils pratiques</a></li>
                        <li><a href="{{ route('register') }}">Créer un compte</a></li>
                        <li><a href="{{ route('contact.index') }}">Nous contacter</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h5>Informations</h5>
                    <ul>
                        <li><a href="{{ route('legal.terms') }}">Conditions d'utilisation</a></li>
                        <li><a href="{{ route('legal.privacy') }}">Confidentialité</a></li>
                        <li><a href="{{ route('legal.cookies') }}">Cookies</a></li>
                        <li><a href="{{ route('legal.platform-rules') }}">Règles de la marketplace</a></li>
                        <li><a href="{{ route('legal.pro-terms') }}">Conditions Espace PRO</a></li>
                        <li><a href="{{ route('legal.mentions') }}">Mentions légales</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h5>Contact</h5>
                    <ul>
                        <li><a href="{{ route('contact.index') }}"><i class="fas fa-paper-plane me-2"></i>Formulaire de contact</a></li>
                        @if(config('site.support_email'))
                            <li><a href="mailto:{{ config('site.support_email') }}"><i class="fas fa-envelope me-2"></i>{{ config('site.support_email') }}</a></li>
                        @endif
                        @if(config('site.contact_phone'))
                            <li><a href="tel:{{ preg_replace('/\s+/', '', config('site.contact_phone')) }}"><i class="fas fa-phone me-2"></i>{{ config('site.contact_phone') }}</a></li>
                        @endif
                        @if(config('site.contact_location'))
                            <li><span><i class="fas fa-map-marker-alt me-2"></i>{{ config('site.contact_location') }}</span></li>
                        @endif
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} {{ config('app.name', 'Prokejem') }}. Tous droits réservés.</span>
                <span>Mise en relation de proximité</span>
            </div>
        </div>
    </footer>
    <aside class="home-mobile-cta" id="home-mobile-cta" aria-label="Publier une demande" hidden>
        <a href="{{ route('demand.create') }}" class="home-primary-action">Publier mon besoin <i class="fas fa-plus" aria-hidden="true"></i></a>
    </aside>

    @include('partials.site-share-modal')

    <!-- Scroll to Top Button -->
    <button type="button" id="scrollTopBtn" aria-label="Revenir en haut de la page" onclick="window.scrollTo({top:0,behavior:'smooth'})"
        style="display:none;position:fixed;bottom:30px;right:30px;width:48px;height:48px;border-radius:50%;background:var(--primary);color:white;border:none;cursor:pointer;box-shadow:0 4px 20px rgba(79,70,229,0.35);z-index:9999;font-size:1.1rem;transition:all 0.3s;opacity:0;transform:translateY(10px);">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Mobile menu toggle with animation
    function toggleMobileMenu() {
        var menu = document.getElementById('mobileMenu');
        var icon = document.getElementById('burgerIcon');
        var button = document.getElementById('navBurger');
        var willOpen = menu.style.display === 'none' || !menu.style.display;
        if (willOpen) {
            menu.style.display = 'block';
            requestAnimationFrame(function() { menu.style.opacity = '1'; });
            icon.className = 'fas fa-times';
        } else {
            menu.style.opacity = '0';
            icon.className = 'fas fa-bars';
            setTimeout(function() { menu.style.display = 'none'; }, 250);
        }
        button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        button.setAttribute('aria-label', willOpen ? 'Fermer le menu' : 'Ouvrir le menu');
        menu.setAttribute('aria-hidden', willOpen ? 'false' : 'true');
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Navbar scroll shadow + scroll-to-top button
        const nav = document.getElementById('mainNav');
        const scrollBtn = document.getElementById('scrollTopBtn');
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 20);
            if (window.scrollY > 400) {
                scrollBtn.style.display = 'flex';
                scrollBtn.style.alignItems = 'center';
                scrollBtn.style.justifyContent = 'center';
                requestAnimationFrame(() => { scrollBtn.style.opacity = '1'; scrollBtn.style.transform = 'translateY(0)'; });
            } else {
                scrollBtn.style.opacity = '0';
                scrollBtn.style.transform = 'translateY(10px)';
                setTimeout(() => { if (window.scrollY <= 400) scrollBtn.style.display = 'none'; }, 300);
            }
        });
        
    });
    </script>
    @include('partials.usage-analytics')
    @include('partials.push-notifications')
    @include('partials.session-inactivity')
</body>
</html>

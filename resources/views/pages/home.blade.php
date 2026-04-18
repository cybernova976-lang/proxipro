<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ProxiPro') }} - La plateforme de services entre particuliers et professionnels</title>
    <meta name="description" content="Trouvez le prestataire idéal près de chez vous. Bricolage, ménage, cours, déménagement... Des milliers de professionnels vérifiés à votre service.">
    
    <!-- Open Graph / Social Sharing -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ config('app.name', 'ProxiPro') }} - La plateforme de services entre particuliers et professionnels">
    <meta property="og:description" content="Trouvez le prestataire idéal près de chez vous. Bricolage, ménage, cours, déménagement... Des milliers de professionnels vérifiés à votre service.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:site_name" content="{{ config('app.name', 'ProxiPro') }}">
    <meta property="og:locale" content="fr_FR">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ config('app.name', 'ProxiPro') }} - Trouvez le bon prestataire en quelques clics">
    <meta name="twitter:description" content="Plateforme de mise en relation entre particuliers et professionnels. Trouvez le bon professionnel partout en France.">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
        .nav-brand-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 900; font-size: 1.1rem;
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
            position: relative; padding: 70px 0 90px;
            background: url('https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1920&q=80') center center / cover no-repeat;
            overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(160deg, rgba(15,23,42,0.88) 0%, rgba(30,27,75,0.82) 40%, rgba(49,46,129,0.75) 70%, rgba(55,48,163,0.70) 100%);
            z-index: 1;
        }
        .hero::after {
            content: ''; position: absolute; bottom: -150px; left: -100px;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(168,85,247,0.15) 0%, transparent 65%);
            border-radius: 50%; animation: heroFloat 15s ease-in-out infinite reverse;
            z-index: 1;
        }
        @keyframes heroFloat { 0%,100%{transform:translate(0,0)} 50%{transform:translate(30px,-30px)} }
        
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
            border-radius: 50%; animation: blink 2s ease-in-out infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
        
        .hero-title {
            font-size: clamp(2.2rem, 5vw, 3.4rem); font-weight: 900;
            line-height: 1.08; color: white; margin-bottom: 24px; letter-spacing: -1px;
        }
        .hero-title .accent {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .hero-desc {
            font-size: 1.1rem; color: rgba(255,255,255,0.6);
            max-width: 500px; line-height: 1.7; margin-bottom: 40px;
        }
        
        /* Trust badges in hero */
        .hero-trust { display: flex; gap: 28px; margin-top: 48px; }
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
            background: white; padding: 32px; border-radius: 24px;
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
        
        /* ===== TRUST BAR ===== */
        .trust-bar { background: white; border-bottom: 1px solid var(--gray-200); padding: 20px 0; }
        .trust-bar-inner {
            display: flex; justify-content: center; align-items: center;
            gap: 48px; flex-wrap: wrap;
        }
        .trust-stat { text-align: center; }
        .trust-stat-number {
            font-size: 1.8rem; font-weight: 900; color: var(--gray-900); line-height: 1;
        }
        .trust-stat-label {
            font-size: 0.78rem; font-weight: 600; color: var(--gray-400);
            text-transform: uppercase; letter-spacing: 1px; margin-top: 4px;
        }
        .trust-divider { width: 1px; height: 40px; background: var(--gray-200); }
        
        /* ===== SECTIONS ===== */
        .section { padding: 80px 0; }
        .section-bg { background: white; }
        .section-head { text-align: center; margin-bottom: 48px; }
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
        
        /* ===== VALUE PROPS ===== */
        .value-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
        .value-card {
            text-align: center; padding: 32px 20px; background: white;
            border: 1px solid var(--gray-200); border-radius: 20px; transition: all 0.3s;
        }
        .value-card:hover {
            transform: translateY(-4px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            border-color: transparent;
        }
        .value-icon {
            width: 64px; height: 64px; border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; margin: 0 auto 18px;
        }
        .value-card h4 { font-weight: 700; font-size: 1rem; color: var(--gray-900); margin-bottom: 8px; }
        .value-card p { font-size: 0.9rem; color: var(--gray-500); line-height: 1.6; margin: 0; }
        
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
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 8px; display: inline-flex; align-items: center;
            justify-content: center; color: white; font-weight: 900; font-size: 0.9rem;
        }
        .footer-brand p { font-size: 0.9rem; line-height: 1.7; margin-bottom: 20px; }
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
            .value-grid { grid-template-columns: repeat(2, 1fr); }
            .cat-grid { grid-template-columns: repeat(2, 1fr); }
            .testimonials-grid { grid-template-columns: 1fr; }
            .dual-cta { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .hero { padding: 50px 0 70px; }
            .hero-trust { flex-wrap: wrap; gap: 16px; }
            .steps-grid::before { display: none; }
        }
        @media (max-width: 767px) {
            .value-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .value-card { padding: 24px 16px; }
            .cat-grid { grid-template-columns: 1fr; }
            .steps-grid { grid-template-columns: 1fr; gap: 16px; }
            .trust-bar-inner { gap: 24px; }
            .footer-grid { grid-template-columns: 1fr; gap: 32px; }
            .footer-bottom { flex-direction: column; gap: 8px; text-align: center; }
            .hero-title { font-size: 2rem; }
            .search-card { padding: 24px; }
            .press-logos { gap: 24px; }
            .cta-card { padding: 32px 24px; }
        }
        
        /* ===== ANIMATIONS ===== */
        .reveal {
            opacity: 0; transform: translateY(24px);
            transition: all 0.7s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .reveal.visible { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body>
    <!-- ===== NAVBAR ===== -->
    <nav class="nav-main sticky-top" id="mainNav">
        <div class="container">
            <div class="nav-inner">
                <a href="{{ url('/') }}" class="nav-brand">
                    <span class="nav-brand-icon">P</span>
                    ProxiPro
                </a>
                <ul class="nav-links">
                    <li><a href="{{ url('/ads') }}">Annonces</a></li>
                    <li><a href="{{ route('contact.index') }}">Contact</a></li>
                    @auth
                        <li><a href="{{ route('feed') }}" class="btn-nav-login">Accueil</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="btn-nav-login"><i class="fas fa-sign-in-alt me-1"></i>Connexion</a></li>
                        @if (Route::has('register'))
                            <li><a href="{{ route('register') }}" class="btn-nav-register"><i class="fas fa-user-plus me-1"></i>S'inscrire gratuitement</a></li>
                        @endif
                    @endauth
                </ul>
                <button class="nav-burger" id="navBurger" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars" id="burgerIcon"></i>
                </button>
            </div>
            <div id="mobileMenu" style="padding-bottom: 16px; display: none; opacity: 0; transition: opacity 0.25s ease;">
                <div class="d-flex flex-column gap-2">
                    <a href="{{ url('/ads') }}" class="d-block py-2 text-decoration-none text-dark fw-semibold">
                        <i class="fas fa-bullhorn me-2 text-muted"></i>Annonces
                    </a>
                    <a href="{{ route('contact.index') }}" class="d-block py-2 text-decoration-none text-dark fw-semibold">
                        <i class="fas fa-envelope me-2 text-muted"></i>Contact
                    </a>
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
    <header class="hero">
        <div class="container hero-grid">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <div class="hero-label">
                        <span class="hero-label-dot"></span>
                        Plateforme N°1 de services entre particuliers
                    </div>
                    <h1 class="hero-title">
                        Trouvez le bon<br>
                        <span class="accent">prestataire</span><br>
                        en quelques clics
                    </h1>
                    <p class="hero-desc">
                        Bricolage, ménage, cours, déménagement... Connectez-vous 
                        avec des professionnels vérifiés partout en France et au-delà.
                    </p>
                    <div class="hero-trust">
                        <div class="hero-trust-item">
                            <div class="hero-trust-icon"><i class="fas fa-shield-alt"></i></div>
                            <div>
                                <div style="color:rgba(255,255,255,0.85);font-weight:700;font-size:0.88rem;">Profils vérifiés</div>
                                <div style="font-size:0.75rem;">Identité contrôlée</div>
                            </div>
                        </div>
                        <div class="hero-trust-item">
                            <div class="hero-trust-icon"><i class="fas fa-lock"></i></div>
                            <div>
                                <div style="color:rgba(255,255,255,0.85);font-weight:700;font-size:0.88rem;">Paiement sécurisé</div>
                                <div style="font-size:0.75rem;">Via Stripe</div>
                            </div>
                        </div>
                        <div class="hero-trust-item">
                            <div class="hero-trust-icon"><i class="fas fa-star"></i></div>
                            <div>
                                <div style="color:rgba(255,255,255,0.85);font-weight:700;font-size:0.88rem;">Avis vérifiés</div>
                                <div style="font-size:0.75rem;">100% authentiques</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="search-card">
                        <div class="search-card-title">
                            <i class="fas fa-search"></i>
                            De quoi avez-vous besoin ?
                        </div>
                        <form action="{{ route('demand.create') }}" method="GET">
                            <div class="search-field">
                                <i class="fas fa-th-large"></i>
                                <select name="category">
                                    <option value="">Choisissez un service</option>
                                    @foreach($categoriesWithSubs as $catName => $catData)
                                        <option value="{{ $catName }}">{{ $catName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn-hero-search">
                                <i class="fas fa-paper-plane"></i> Trouver un professionnel
                            </button>
                        </form>
                        <div class="popular-searches">
                            <span>Populaire :</span>
                            <a href="{{ route('demand.create') }}?category=Bricolage+%26+Travaux" class="popular-tag">Plombier</a>
                            <a href="{{ route('demand.create') }}?category=Nettoyage+%26+Foyer" class="popular-tag">Ménage</a>
                            <a href="{{ route('demand.create') }}?category=D%C3%A9m%C3%A9nagement+%26+Transport" class="popular-tag">Déménagement</a>
                            <a href="{{ route('demand.create') }}?category=Cours+particuliers" class="popular-tag">Cours particuliers</a>
                            <a href="{{ route('demand.create') }}?category=Jardinage" class="popular-tag">Jardinage</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ===== TRUST BAR ===== -->
    <div class="trust-bar">
        <div class="container">
            <div class="trust-bar-inner">
                <div class="trust-stat">
                    <div class="trust-stat-number" data-count="{{ $totalPros }}">0</div>
                    <div class="trust-stat-label">Professionnels inscrits</div>
                </div>
                <div class="trust-divider"></div>
                <div class="trust-stat">
                    <div class="trust-stat-number" data-count="{{ $totalAds }}">0</div>
                    <div class="trust-stat-label">Annonces actives</div>
                </div>
                <div class="trust-divider"></div>
                <div class="trust-stat">
                    <div class="trust-stat-number" data-count="{{ count($categoriesWithSubs) }}">0</div>
                    <div class="trust-stat-label">Catégories de services</div>
                </div>
                <div class="trust-divider"></div>
                <div class="trust-stat">
                    <div class="trust-stat-number" data-count="{{ $totalUsers }}">0</div>
                    <div class="trust-stat-label">Membres actifs</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== VALUE PROPOSITIONS ===== -->
    <section class="section">
        <div class="container">
            <div class="section-head reveal">
                <span class="section-pill">Pourquoi nous choisir</span>
                <h2 class="section-title">Une plateforme pensée pour vous</h2>
                <p class="section-desc">Nous simplifions la mise en relation entre particuliers et professionnels de confiance.</p>
            </div>
            <div class="value-grid">
                <div class="value-card reveal">
                    <div class="value-icon" style="background:#eef2ff;color:#4f46e5;"><i class="fas fa-user-check"></i></div>
                    <h4>Profils vérifiés</h4>
                    <p>Chaque prestataire est vérifié. Consultez les avis, notes et certifications avant de choisir.</p>
                </div>
                <div class="value-card reveal">
                    <div class="value-icon" style="background:#ecfdf5;color:#10b981;"><i class="fas fa-bolt"></i></div>
                    <h4>Réponse rapide</h4>
                    <p>Recevez des réponses en quelques minutes grâce à notre messagerie intégrée en temps réel.</p>
                </div>
                <div class="value-card reveal">
                    <div class="value-icon" style="background:#fef3c7;color:#f59e0b;"><i class="fas fa-shield-alt"></i></div>
                    <h4>Paiement sécurisé</h4>
                    <p>Vos transactions sont protégées par Stripe. Payez en ligne en toute sérénité.</p>
                </div>
                <div class="value-card reveal">
                    <div class="value-icon" style="background:#fce7f3;color:#ec4899;"><i class="fas fa-headset"></i></div>
                    <h4>Support réactif</h4>
                    <p>Notre équipe vous accompagne à chaque étape. Assistance disponible 7j/7.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CATEGORIES ===== -->
    <section class="section section-bg">
        <div class="container">
            <div class="section-head reveal">
                <span class="section-pill">Services</span>
                <h2 class="section-title">Explorez nos catégories</h2>
                <p class="section-desc">Trouvez rapidement le professionnel dont vous avez besoin</p>
            </div>
            <div class="cat-grid">
                @php $shown = 0; @endphp
                @foreach($categoriesWithSubs as $catName => $catData)
                    @if($shown < 8)
                    <a href="{{ route('demand.create') }}?category={{ urlencode($catName) }}" class="cat-card reveal">
                        <div class="cat-icon" style="background:{{ $catData['color'] }};"><i class="{{ $catData['icon'] }}"></i></div>
                        <div class="cat-info">
                            <h5>{{ $catName }}</h5>
                            <span>Trouver un pro</span>
                        </div>
                    </a>
                    @php $shown++; @endphp
                    @endif
                @endforeach
            </div>
            <div class="text-center">
                <a href="{{ route('demand.create') }}" class="btn-see-all">
                    Voir les {{ count($categoriesWithSubs) }} catégories <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- ===== FEATURED PROFESSIONALS ===== -->
    @if($featuredPros->count() > 0)
    <section class="section">
        <div class="container">
            <div class="section-head reveal">
                <span class="section-pill">Prestataires</span>
                <h2 class="section-title">Nos professionnels en vedette</h2>
                <p class="section-desc">Des prestataires vérifiés, prêts à répondre à vos besoins</p>
            </div>
            <div class="row g-4">
                @foreach($featuredPros as $pro)
                <div class="col-md-6 col-lg-4">
                    <a href="{{ route('profile.public', $pro->id) }}" class="ad-card reveal" style="text-decoration:none;">
                        <div style="padding:24px 20px; text-align:center;">
                            @if($pro->avatar)
                                <img src="{{ storage_url($pro->avatar) }}" alt="{{ $pro->name }}" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid var(--primary-light);margin-bottom:12px;">
                            @else
                                <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;font-weight:700;margin:0 auto 12px;">{{ strtoupper(substr($pro->name, 0, 1)) }}</div>
                            @endif
                            @if($pro->stripe_id)
                                <span style="display:inline-flex;align-items:center;gap:4px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;padding:2px 10px;border-radius:8px;font-size:0.72rem;font-weight:700;margin-bottom:8px;"><i class="fas fa-crown" style="font-size:0.65rem;"></i> PRO</span>
                            @endif
                            <h4 style="font-size:1rem;font-weight:700;color:var(--gray-900);margin-bottom:4px;">{{ $pro->name }}</h4>
                            @if($pro->profession)
                                <div style="font-size:0.85rem;color:var(--primary);font-weight:600;margin-bottom:6px;">{{ $pro->profession }}</div>
                            @endif
                            @if($pro->city || $pro->country)
                                <div style="font-size:0.82rem;color:var(--gray-500);"><i class="fas fa-map-marker-alt me-1"></i>{{ $pro->city ?? '' }}{{ $pro->city && $pro->country ? ', ' : '' }}{{ $pro->country ?? '' }}</div>
                            @endif
                            @if($pro->bio)
                                <p style="font-size:0.82rem;color:var(--gray-500);margin-top:8px;line-height:1.4;">{{ Str::limit($pro->bio, 80) }}</p>
                            @endif
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
            <div class="text-center" style="margin-top:24px;">
                <a href="{{ route('demand.create') }}" class="btn-see-all">Trouver un professionnel <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>
    @endif

    <!-- ===== HOW IT WORKS ===== -->
    <section class="section section-bg">
        <div class="container">
            <div class="section-head reveal">
                <span class="section-pill">Simple & rapide</span>
                <h2 class="section-title">Comment ça marche ?</h2>
                <p class="section-desc">Trouvez votre prestataire idéal en 3 étapes simples</p>
            </div>
            <div class="steps-grid">
                <div class="step-card reveal">
                    <div class="step-num">1</div>
                    <h4>Décrivez votre besoin</h4>
                    <p>Recherchez parmi nos {{ count($categoriesWithSubs) }} catégories ou publiez directement votre demande avec tous les détails.</p>
                </div>
                <div class="step-card reveal">
                    <div class="step-num">2</div>
                    <h4>Comparez les profils</h4>
                    <p>Consultez les avis, les tarifs et les portfolios des prestataires pour faire le meilleur choix.</p>
                </div>
                <div class="step-card reveal">
                    <div class="step-num">3</div>
                    <h4>Échangez & réservez</h4>
                    <p>Contactez le prestataire via notre messagerie sécurisée, convenez des détails et validez.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== TESTIMONIALS ===== -->
    <section class="section">
        <div class="container">
            <div class="section-head reveal">
                <span class="section-pill">Témoignages</span>
                <h2 class="section-title">Ils nous font confiance</h2>
                <p class="section-desc">Découvrez les retours de nos utilisateurs satisfaits</p>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card reveal">
                    <div class="testimonial-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">"J'ai trouvé un plombier en moins de 10 minutes. Intervention rapide, prix raisonnable. Je recommande vivement ProxiPro !"</p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">S</div>
                        <div>
                            <div class="testimonial-name">Sophie M.</div>
                            <div class="testimonial-role">Particulier - Paris</div>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card reveal">
                    <div class="testimonial-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">"En tant que prestataire, j'ai triplé ma clientèle grâce à la plateforme. L'interface est intuitive et les clients sont sérieux."</p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">K</div>
                        <div>
                            <div class="testimonial-name">Karim B.</div>
                            <div class="testimonial-role">Électricien - Mayotte</div>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card reveal">
                    <div class="testimonial-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="testimonial-text">"Super pratique pour trouver une nounou de confiance. Les avis vérifiés m'ont aidée à faire mon choix sereinement."</p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">L</div>
                        <div>
                            <div class="testimonial-name">Léa D.</div>
                            <div class="testimonial-role">Particulier - La Réunion</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== DUAL CTA ===== -->
    <section class="section section-bg">
        <div class="container">
            <div class="dual-cta">
                <div class="cta-card cta-client reveal">
                    <div class="cta-card-icon"><i class="fas fa-search"></i></div>
                    <h3>Vous cherchez un prestataire ?</h3>
                    <p>Décrivez votre besoin en 2 minutes et trouvez les professionnels disponibles près de chez vous.</p>
                    <a href="{{ route('demand.create') }}" class="btn-cta-white">
                        Trouver un professionnel <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="cta-card cta-pro reveal">
                    <div class="cta-card-icon"><i class="fas fa-briefcase"></i></div>
                    <h3>Vous êtes professionnel ?</h3>
                    <p>Rejoignez notre réseau de prestataires et développez votre activité en trouvant de nouveaux clients chaque jour.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="{{ route('register') }}" class="btn-cta-white" style="color:var(--gray-900);">
                            Devenir prestataire <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="{{ route('contact.index') }}" class="btn-cta-outline">En savoir plus</a>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h4><span class="footer-brand-icon">P</span> ProxiPro</h4>
                    <p>La plateforme de mise en relation entre particuliers et prestataires de services. Trouvez le bon professionnel en quelques clics, partout en France et à l'international.</p>
                    <div class="footer-social">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h5>Plateforme</h5>
                    <ul>
                        <li><a href="{{ route('demand.create') }}">Trouver un professionnel</a></li>
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
                        <li><a href="{{ route('legal.mentions') }}">Mentions légales</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h5>Contact</h5>
                    <ul>
                        <li><a href="mailto:contact@ProxiPro.com"><i class="fas fa-envelope me-2"></i>contact@ProxiPro.com</a></li>
                        <li><a href="#"><i class="fas fa-phone me-2"></i>+33 1 00 00 00 00</a></li>
                        <li><a href="#"><i class="fas fa-map-marker-alt me-2"></i>France</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} ProxiPro. Tous droits réservés.</span>
                <span>Fait avec <i class="fas fa-heart text-danger"></i> en France</span>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button id="scrollTopBtn" onclick="window.scrollTo({top:0,behavior:'smooth'})" 
        style="display:none;position:fixed;bottom:30px;right:30px;width:48px;height:48px;border-radius:50%;background:var(--primary);color:white;border:none;cursor:pointer;box-shadow:0 4px 20px rgba(79,70,229,0.35);z-index:9999;font-size:1.1rem;transition:all 0.3s;opacity:0;transform:translateY(10px);">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Mobile menu toggle with animation
    function toggleMobileMenu() {
        var menu = document.getElementById('mobileMenu');
        var icon = document.getElementById('burgerIcon');
        if (menu.style.display === 'none' || !menu.style.display) {
            menu.style.display = 'block';
            requestAnimationFrame(function() { menu.style.opacity = '1'; });
            icon.className = 'fas fa-times';
        } else {
            menu.style.opacity = '0';
            icon.className = 'fas fa-bars';
            setTimeout(function() { menu.style.display = 'none'; }, 250);
        }
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
        
        // Scroll reveal animations (staggered)
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    setTimeout(() => entry.target.classList.add('visible'), i * 80);
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
        document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));
        
        // Animated counters
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                const target = parseInt(el.dataset.count);
                const suffix = el.dataset.suffix || '';
                let current = 0;
                const duration = 1500;
                const step = target / (duration / 16);
                const timer = setInterval(() => {
                    current += step;
                    if (current >= target) { current = target; clearInterval(timer); }
                    let display = Math.floor(current);
                    if (target >= 100 && !suffix) display = display.toLocaleString('fr-FR') + '+';
                    else display = display + suffix;
                    el.textContent = display;
                }, 16);
                counterObserver.unobserve(el);
            });
        }, { threshold: 0.5 });
        document.querySelectorAll('[data-count]').forEach(el => counterObserver.observe(el));
    });
    </script>
</body>
</html>

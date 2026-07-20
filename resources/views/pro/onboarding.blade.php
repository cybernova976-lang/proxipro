<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bienvenue - Configuration de votre espace professionnel</title>
    <link href="https://fonts.bunny.net/css?family=Inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --ob-primary: #6366f1;
        --ob-primary-dark: #4f46e5;
        --ob-gradient: linear-gradient(135deg, #6366f1, #8b5cf6);
        --ob-success: #10b981;
        --ob-warning: #f59e0b;
        --ob-danger: #ef4444;
        --ob-text: #1e293b;
        --ob-muted: #64748b;
        --ob-border: #e2e8f0;
        --ob-bg: #f8fafc;
    }
    * { box-sizing: border-box; }
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--ob-bg);
        color: var(--ob-text);
        margin: 0;
        min-height: 100vh;
    }

    /* Top progress bar */
    .ob-progress-bar {
        position: fixed; top: 0; left: 0; right: 0; height: 4px;
        background: var(--ob-border); z-index: 100;
    }
    .ob-progress-fill {
        height: 100%; background: var(--ob-gradient);
        transition: width 0.5s ease; border-radius: 0 2px 2px 0;
    }

    /* Header */
    .ob-header {
        text-align: center; padding: 40px 20px 0;
    }
    .ob-logo {
        display: inline-flex; align-items: center; gap: 10px;
        font-size: 1.3rem; font-weight: 800; color: var(--ob-text);
        margin-bottom: 8px; text-decoration: none;
    }
    .ob-logo-icon {
        width: 40px; height: 40px; background: var(--ob-gradient);
        color: white; border-radius: 12px; display: flex;
        align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.1rem;
    }
    .ob-logo .badge { background: var(--ob-gradient); color: white;
        font-size: 0.6rem; padding: 3px 8px; border-radius: 20px;
        font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase;
    }

    /* Stepper */
    .ob-stepper {
        display: flex; align-items: center; justify-content: center;
        gap: 0; max-width: 700px; margin: 30px auto 40px; padding: 0 20px;
    }
    .ob-step {
        display: flex; flex-direction: column; align-items: center;
        gap: 6px; flex-shrink: 0; position: relative; z-index: 1;
    }
    .ob-step-circle {
        width: 40px; height: 40px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.85rem; transition: all 0.3s;
        border: 2px solid var(--ob-border); background: white; color: var(--ob-muted);
    }
    .ob-step.active .ob-step-circle {
        background: var(--ob-gradient); color: white; border-color: var(--ob-primary);
        box-shadow: 0 0 0 4px rgba(99,102,241,0.15);
    }
    .ob-step.done .ob-step-circle {
        background: var(--ob-success); color: white; border-color: var(--ob-success);
    }
    .ob-step-label {
        font-size: 0.7rem; font-weight: 600; color: var(--ob-muted);
        white-space: nowrap;
    }
    .ob-step.active .ob-step-label { color: var(--ob-primary); }
    .ob-step.done .ob-step-label { color: var(--ob-success); }
    .ob-step-line {
        flex: 1; height: 2px; background: var(--ob-border);
        margin: 0 -4px; margin-top: -20px; position: relative; z-index: 0;
    }
    .ob-step-line.done { background: var(--ob-success); }

    /* Container */
    .ob-container {
        max-width: 780px; margin: 0 auto; padding: 0 20px 60px;
    }

    /* Cards */
    .ob-card {
        background: white; border-radius: 18px; padding: 32px;
        border: 1px solid var(--ob-border);
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        margin-bottom: 20px;
    }
    .ob-card-title {
        font-size: 1.35rem; font-weight: 800; margin-bottom: 6px;
        color: var(--ob-text);
    }
    .ob-card-subtitle {
        font-size: 0.88rem; color: var(--ob-muted); margin-bottom: 24px;
        line-height: 1.5;
    }

    /* Welcome hero */
    .ob-welcome-hero {
        background: var(--ob-gradient); color: white;
        border-radius: 18px; padding: 48px 36px; text-align: center;
        margin-bottom: 24px; position: relative; overflow: hidden;
    }
    .ob-welcome-hero::before {
        content: ''; position: absolute; top: -50%; right: -30%;
        width: 300px; height: 300px; border-radius: 50%;
        background: rgba(255,255,255,0.08);
    }
    .ob-welcome-hero::after {
        content: ''; position: absolute; bottom: -40%; left: -20%;
        width: 250px; height: 250px; border-radius: 50%;
        background: rgba(255,255,255,0.05);
    }
    .ob-welcome-emoji { font-size: 3.5rem; margin-bottom: 16px; position: relative; z-index: 1; }
    .ob-welcome-title { font-size: 1.8rem; font-weight: 800; margin-bottom: 8px; position: relative; z-index: 1; }
    .ob-welcome-sub { font-size: 1rem; opacity: 0.9; max-width: 500px; margin: 0 auto; position: relative; z-index: 1; line-height: 1.5; }

    /* Info recap items */
    .ob-info-item {
        display: flex; align-items: center; gap: 14px; padding: 14px 16px;
        background: #f8fafc; border-radius: 12px; margin-bottom: 8px;
    }
    .ob-info-icon {
        width: 42px; height: 42px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .ob-info-label { font-size: 0.75rem; color: var(--ob-muted); font-weight: 600; }
    .ob-info-value { font-size: 0.92rem; font-weight: 600; color: var(--ob-text); }
    .ob-info-value.missing { color: var(--ob-warning); font-style: italic; font-weight: 500; }

    /* Category grid */
    .ob-cat-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
    }
    @media (max-width: 640px) { .ob-cat-grid { grid-template-columns: repeat(2, 1fr); } }
    .ob-cat-btn {
        display: flex; flex-direction: column; align-items: center;
        gap: 8px; padding: 18px 10px; border-radius: 14px;
        border: 2px solid var(--ob-border); background: white;
        cursor: pointer; transition: all 0.2s; text-align: center;
    }
    .ob-cat-btn:hover { border-color: #c7d2fe; background: #fafbff; transform: translateY(-1px); }
    .ob-cat-btn.selected { border-color: var(--ob-primary); background: rgba(99,102,241,0.06); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
    .ob-cat-icon { font-size: 1.8rem; }
    .ob-cat-name { font-size: 0.78rem; font-weight: 600; color: #374151; line-height: 1.3; }
    .ob-cat-btn.selected .ob-cat-name { color: var(--ob-primary); }
    .ob-cat-check {
        display: none; position: absolute; top: 6px; right: 6px;
        width: 22px; height: 22px; border-radius: 50%;
        background: var(--ob-primary); color: white;
        align-items: center; justify-content: center; font-size: 0.65rem;
    }
    .ob-cat-btn.selected { position: relative; }
    .ob-cat-btn.selected .ob-cat-check { display: flex; }

    /* Sub chips */
    .ob-sub-list { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
    .ob-sub-chip {
        padding: 8px 16px; border-radius: 24px; border: 2px solid var(--ob-border);
        font-size: 0.82rem; font-weight: 500; color: #6b7280; background: white;
        cursor: pointer; transition: all 0.2s;
    }
    .ob-sub-chip:hover { border-color: #93c5fd; background: #f0f7ff; }
    .ob-sub-chip.selected { border-color: var(--ob-primary); background: rgba(99,102,241,0.06); color: var(--ob-primary); font-weight: 600; }

    /* Notification toggles */
    .ob-notif-option {
        display: flex; align-items: center; gap: 14px;
        padding: 16px; background: #f8fafc; border-radius: 12px;
        margin-bottom: 8px; cursor: pointer;
    }
    .ob-notif-option:hover { background: #f1f5f9; }
    .ob-notif-icon {
        width: 44px; height: 44px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
    }
    .ob-notif-text { flex: 1; }
    .ob-notif-text strong { font-size: 0.88rem; display: block; }
    .ob-notif-text span { font-size: 0.78rem; color: var(--ob-muted); }

    /* Verification card */
    .ob-verify-card {
        background: linear-gradient(135deg, rgba(16,185,129,0.06), rgba(59,130,246,0.06));
        border: 2px solid rgba(16,185,129,0.2); border-radius: 16px; padding: 24px;
        margin-top: 16px;
    }
    .ob-verify-advantages { display: flex; flex-direction: column; gap: 10px; margin: 16px 0; }
    .ob-verify-adv {
        display: flex; align-items: center; gap: 10px;
        font-size: 0.85rem; font-weight: 500; color: var(--ob-text);
    }
    .ob-verify-adv i { color: var(--ob-success); width: 20px; text-align: center; }

    /* Plan cards */
    .ob-plan-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 640px) { .ob-plan-grid { grid-template-columns: 1fr; } }
    .ob-plan-card {
        border: 2px solid var(--ob-border); border-radius: 16px;
        padding: 28px 24px; text-align: center; cursor: pointer;
        transition: all 0.3s; position: relative; background: white;
    }
    .ob-plan-card:hover { border-color: #c7d2fe; transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.06);
    }
    .ob-plan-card.selected { border-color: var(--ob-primary); background: rgba(99,102,241,0.03);
        box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
    }
    .ob-plan-badge {
        position: absolute; top: -13px; right: 16px;
        background: linear-gradient(135deg, #f59e0b, #ef4444);
        color: white; padding: 4px 14px; border-radius: 20px;
        font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px;
    }
    .ob-plan-icon {
        width: 56px; height: 56px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; margin: 0 auto 14px;
    }
    .ob-plan-name { font-size: 1.1rem; font-weight: 700; margin-bottom: 4px; }
    .ob-plan-desc { font-size: 0.8rem; color: var(--ob-muted); margin-bottom: 12px; }
    .ob-plan-price { font-size: 2.2rem; font-weight: 800; color: var(--ob-primary); }
    .ob-plan-period { font-size: 0.85rem; color: var(--ob-muted); }
    .ob-plan-features { list-style: none; padding: 0; margin: 16px 0 0; text-align: left; }
    .ob-plan-features li {
        font-size: 0.82rem; padding: 5px 0;
        display: flex; align-items: center; gap: 8px;
    }
    .ob-plan-features li i { color: var(--ob-success); width: 16px; text-align: center; }

    /* Recap */
    .ob-recap-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.88rem;
    }
    .ob-recap-row:last-child { border-bottom: none; }
    .ob-recap-label { color: var(--ob-muted); }
    .ob-recap-value { font-weight: 600; }
    .ob-recap-total {
        display: flex; justify-content: space-between; align-items: center;
        padding: 16px 0; margin-top: 8px; border-top: 2px solid var(--ob-text);
        font-size: 1.1rem; font-weight: 800;
    }

    /* Footer */
    .ob-footer {
        display: flex; align-items: center; justify-content: space-between;
        padding: 20px 0; gap: 12px;
    }
    .ob-btn-back {
        background: none; border: none; color: var(--ob-muted);
        font-size: 0.88rem; font-weight: 500; cursor: pointer;
        display: flex; align-items: center; gap: 6px;
    }
    .ob-btn-back:hover { color: var(--ob-text); }
    .ob-btn-next {
        padding: 14px 32px; border-radius: 12px; border: none;
        background: var(--ob-gradient); color: white;
        font-size: 0.95rem; font-weight: 700; cursor: pointer;
        transition: all 0.2s; display: flex; align-items: center; gap: 8px;
    }
    .ob-btn-next:hover { opacity: 0.9; transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(99,102,241,0.3);
    }
    .ob-btn-next:disabled { opacity: 0.4; cursor: not-allowed; transform: none; box-shadow: none; }
    .ob-btn-skip {
        background: none; border: none; color: var(--ob-muted);
        font-size: 0.82rem; cursor: pointer; text-decoration: underline;
    }
    .ob-btn-skip:hover { color: var(--ob-text); }

    /* Inputs */
    .ob-input {
        width: 100%; padding: 12px 16px; border: 2px solid var(--ob-border);
        border-radius: 12px; font-size: 0.88rem; color: var(--ob-text);
        outline: none; transition: border-color 0.2s; font-family: inherit;
    }
    .ob-input:focus { border-color: var(--ob-primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.08); }
    .ob-label { display: block; font-size: 0.82rem; font-weight: 600; color: var(--ob-text); margin-bottom: 6px; }
    .ob-two-col { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 16px; margin-bottom: 16px; }
    .ob-footer-actions { display: flex; align-items: center; gap: 16px; }

    /* Geo button */
    .ob-geo-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 20px; border: 2px solid var(--ob-primary);
        border-radius: 12px; background: rgba(99,102,241,0.05);
        color: var(--ob-primary); font-size: 0.85rem; font-weight: 600;
        cursor: pointer; transition: all 0.2s;
    }
    .ob-geo-btn:hover { background: var(--ob-primary); color: white; }

    /* Animations */
    .ob-step-content { animation: obSlideIn 0.4s ease-out; }
    @keyframes obSlideIn {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    .ob-count-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;
        background: rgba(16,185,129,0.1); color: #059669; margin-top: 12px;
    }

    /* Responsive */
    @media (max-width: 640px) {
        html, body { width: 100%; max-width: 100%; overflow-x: clip; -webkit-text-size-adjust: 100%; }
        .ob-header { padding: 24px 12px 0; }
        .ob-container { padding: 0 12px 32px; }
        .ob-welcome-hero { padding: 32px 18px; border-radius: 14px; }
        .ob-welcome-title { font-size: 1.4rem; }
        .ob-card { padding: 22px 16px; border-radius: 14px; }
        .ob-stepper { gap: 0; margin: 20px auto 24px; padding: 0 12px; overflow: hidden; }
        .ob-step-circle { width: 32px; height: 32px; }
        .ob-step-line { margin-top: -18px; }
        .ob-step-label { display: none; }
        .ob-two-col { grid-template-columns: minmax(0, 1fr); gap: 12px; }
        .ob-cat-grid { gap: 8px; }
        .ob-cat-btn { padding: 14px 8px; }
        .ob-input { min-height: 46px; font-size: 16px; }
        .ob-geo-btn { width: 100%; min-height: 46px; justify-content: center; }
        .ob-recap-row { align-items: flex-start; gap: 14px; }
        .ob-recap-value { max-width: 52%; text-align: right; overflow-wrap: anywhere; }
        .ob-footer { flex-direction: column-reverse; align-items: stretch; padding: 12px 0; }
        .ob-footer > div:first-child { width: 100%; }
        .ob-footer-actions { width: 100%; flex-direction: column-reverse; gap: 8px; }
        .ob-btn-next, .ob-btn-skip, .ob-btn-back { width: 100%; min-height: 46px; justify-content: center; }
    }
    </style>
</head>
<body>
    <!-- Progress bar -->
    <div class="ob-progress-bar">
        <div class="ob-progress-fill" id="obProgressFill" style="width: 16.6%"></div>
    </div>

    <!-- Header -->
    <div class="ob-header">
        <a href="{{ route('feed') }}" class="ob-logo">
            <div class="ob-logo-icon">P</div>
            ProxiPro
            <span class="badge">PRO</span>
        </a>
    </div>

    <!-- Stepper -->
    <div class="ob-stepper" id="obStepper">
        <div class="ob-step active" data-step="1">
            <div class="ob-step-circle">1</div>
            <span class="ob-step-label">Bienvenue</span>
        </div>
        <div class="ob-step-line"></div>
        <div class="ob-step" data-step="2">
            <div class="ob-step-circle">2</div>
            <span class="ob-step-label">Localisation</span>
        </div>
        <div class="ob-step-line"></div>
        <div class="ob-step" data-step="3">
            <div class="ob-step-circle">3</div>
            <span class="ob-step-label">Catégories</span>
        </div>
        <div class="ob-step-line"></div>
        <div class="ob-step" data-step="4">
            <div class="ob-step-circle">4</div>
            <span class="ob-step-label">Notifications</span>
        </div>
        <div class="ob-step-line"></div>
        <div class="ob-step" data-step="5">
            <div class="ob-step-circle">5</div>
            <span class="ob-step-label">Abonnement</span>
        </div>
        <div class="ob-step-line"></div>
        <div class="ob-step" data-step="6">
            <div class="ob-step-circle">6</div>
            <span class="ob-step-label">Confirmation</span>
        </div>
    </div>

    <div class="ob-container">
        <!-- ========================================================= -->
        <!-- STEP 1: Bienvenue + Récapitulatif coordonnées             -->
        <!-- ========================================================= -->
        <div class="ob-step-content" id="obStep1">
            <div class="ob-welcome-hero">
                <div class="ob-welcome-emoji">🎉</div>
                <div class="ob-welcome-title">Bienvenue, {{ $user->company_name ?? $user->name }} !</div>
                <div class="ob-welcome-sub">
                    Nous sommes ravis de vous accompagner. Configurez votre espace en quelques étapes pour commencer à recevoir des demandes de clients près de chez vous.
                </div>
            </div>

            <div class="ob-card">
                <div class="ob-card-title">📋 Récapitulatif de vos informations</div>
                <div class="ob-card-subtitle">Voici les informations que nous avons enregistrées lors de votre inscription. Elles seront utilisées pour déterminer votre périmètre d'intervention.</div>

                <div class="ob-info-item">
                    <div class="ob-info-icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div class="ob-info-label">Nom / Raison sociale</div>
                        <div class="ob-info-value">{{ $user->company_name ?? $user->name }}</div>
                    </div>
                </div>

                <div class="ob-info-item">
                    <div class="ob-info-icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <div class="ob-info-label">Email de contact</div>
                        <div class="ob-info-value">{{ $user->email }}</div>
                    </div>
                </div>

                <div class="ob-info-item">
                    <div class="ob-info-icon" style="background: rgba(168,85,247,0.1); color: #a855f7;">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div>
                        <div class="ob-info-label">Téléphone</div>
                        <div class="ob-info-value {{ !$user->phone ? 'missing' : '' }}">
                            {{ $user->phone ?: 'Non renseigné — vous pourrez l\'ajouter plus tard' }}
                        </div>
                    </div>
                </div>

                <div class="ob-info-item">
                    <div class="ob-info-icon" style="background: rgba(239,68,68,0.1); color: #ef4444;">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <div class="ob-info-label">Localisation</div>
                        <div class="ob-info-value {{ !$user->getDisplayCity() ? 'missing' : '' }}">
                            {{ $user->getLocationLabel() }}
                        </div>
                    </div>
                </div>

                <div class="ob-info-item">
                    <div class="ob-info-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div>
                        <div class="ob-info-label">Statut professionnel</div>
                        <div class="ob-info-value">{{ $user->getAccountTypeLabel() }}</div>
                    </div>
                </div>

                @if($user->siret)
                <div class="ob-info-item">
                    <div class="ob-info-icon" style="background: rgba(20,184,166,0.1); color: #14b8a6;">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <div class="ob-info-label">SIRET</div>
                        <div class="ob-info-value">{{ $user->siret }}</div>
                    </div>
                </div>
                @endif

                <div style="margin-top: 16px; padding: 12px 16px; background: rgba(99,102,241,0.06); border-radius: 10px; font-size: 0.82rem; color: var(--ob-primary); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-info-circle"></i>
                    Vous pourrez modifier ces informations à tout moment depuis votre espace professionnel.
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- STEP 2: Localisation & périmètre d'intervention           -->
        <!-- ========================================================= -->
        <div class="ob-step-content" id="obStep2" style="display:none;">
            <div class="ob-card">
                <div class="ob-card-title">📍 Votre zone d'intervention</div>
                <div class="ob-card-subtitle">
                    Définissez votre localisation et votre rayon d'action. Plus votre périmètre est large, plus vous recevrez de demandes de clients potentiels.
                </div>

                <div style="margin-bottom: 20px;">
                    <button type="button" class="ob-geo-btn" id="obGeoBtn" onclick="obGeolocate()">
                        <i class="fas fa-crosshairs"></i> Me géolocaliser automatiquement
                    </button>
                    <div id="obGeoStatus" style="margin-top: 8px; font-size: 0.8rem; display: none;"></div>
                </div>

                <div class="ob-two-col">
                    <div>
                        <label class="ob-label">Adresse *</label>
                        <input type="text" class="ob-input" id="obAddress" placeholder="12 rue de la Paix, 75001 Paris" value="{{ $user->address ?? '' }}">
                    </div>
                    <div>
                        <label class="ob-label">Ville *</label>
                        <input type="text" class="ob-input" id="obCity" placeholder="Paris" value="{{ $user->city ?? $user->detected_city ?? '' }}">
                    </div>
                </div>

                <div class="ob-two-col">
                    <div>
                        <label class="ob-label">Pays</label>
                        <input type="text" class="ob-input" id="obCountry" placeholder="France" value="{{ $user->country ?? $user->detected_country ?? 'France' }}">
                    </div>
                    <div>
                        <label class="ob-label">Téléphone professionnel</label>
                        <input type="tel" class="ob-input" id="obPhone" placeholder="06 12 34 56 78" value="{{ $user->phone ?? '' }}">
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="ob-label">Rayon d'intervention : <strong id="obRadiusLabel">{{ $user->pro_intervention_radius ?? 30 }} km</strong></label>
                    <input type="range" id="obRadius" min="5" max="200" value="{{ $user->pro_intervention_radius ?? 30 }}" style="width: 100%; accent-color: var(--ob-primary);" oninput="document.getElementById('obRadiusLabel').textContent = this.value + ' km'">
                    <div style="display: flex; justify-content: space-between; font-size: 0.72rem; color: var(--ob-muted);">
                        <span>5 km</span><span>50 km</span><span>100 km</span><span>200 km</span>
                    </div>
                </div>

                <div style="padding: 14px 16px; background: rgba(245,158,11,0.06); border-radius: 10px; border-left: 3px solid var(--ob-warning); font-size: 0.82rem; color: #92400e;">
                    <strong><i class="fas fa-lightbulb me-1"></i>Conseil :</strong> Un rayon de 30 à 50 km est un bon compromis pour commencer. Vous pourrez l'ajuster plus tard.
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- STEP 3: Catégories de métier                              -->
        <!-- ========================================================= -->
        <div class="ob-step-content" id="obStep3" style="display:none;">
            <div class="ob-card">
                <div class="ob-card-title">🔧 Vos catégories de métier</div>
                <div class="ob-card-subtitle">
                    Sélectionnez les domaines dans lesquels vous proposez vos services. <strong style="color: var(--ob-primary);">Plus vous sélectionnez de catégories, plus vous recevrez de demandes clients !</strong>
                </div>

                <div class="ob-cat-grid" id="obCatGrid"></div>

                <div id="obSelectedCatsInfo" style="display: none; margin-top: 16px;">
                    <div class="ob-count-badge">
                        <i class="fas fa-check-circle"></i>
                        <span id="obCatCount">0</span> catégorie(s) sélectionnée(s)
                    </div>
                </div>

                {{-- Subcategories area --}}
                <div id="obSubArea" style="display: none; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--ob-border);">
                    <h6 style="font-weight: 700; font-size: 0.92rem; margin-bottom: 4px;">
                        <i class="fas fa-tags me-1 text-primary"></i>Choisissez vos spécialités
                    </h6>
                    <p style="font-size: 0.8rem; color: var(--ob-muted); margin-bottom: 12px;">
                        Sélectionnez les sous-catégories qui correspondent à votre expertise :
                    </p>
                    <div class="ob-sub-list" id="obSubList"></div>
                    <div class="ob-count-badge" id="obSubCount" style="display: none;">
                        <i class="fas fa-check-circle"></i>
                        <span id="obSubCountNum">0</span> spécialité(s) sélectionnée(s)
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- STEP 4: Notifications + Vérification du profil           -->
        <!-- ========================================================= -->
        <div class="ob-step-content" id="obStep4" style="display:none;">
            <div class="ob-card">
                <div class="ob-card-title">🔔 Alertes dans la plateforme</div>
                <div class="ob-card-subtitle">
                    Choisissez comment vous souhaitez être informé des nouvelles demandes de clients.
                </div>

                <label class="ob-notif-option" onclick="document.getElementById('notifRealtime').checked = !document.getElementById('notifRealtime').checked">
                    <div class="ob-notif-icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="ob-notif-text">
                        <strong>Alertes dans la plateforme</strong>
                        <span>Recevez une alerte lorsqu'un client potentiel publie une demande dans votre zone et votre catégorie.</span>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notifRealtime" checked style="width: 48px; height: 24px;">
                    </div>
                </label>

                <label class="ob-notif-option" onclick="document.getElementById('notifEmail').checked = !document.getElementById('notifEmail').checked">
                    <div class="ob-notif-icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="ob-notif-text">
                        <strong>Alertes par e-mail</strong>
                        <span>Recevez par e-mail les nouvelles demandes correspondant à votre secteur.</span>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notifEmail" checked style="width: 48px; height: 24px;">
                    </div>
                </label>

            </div>

            {{-- Verification block --}}
            <div class="ob-card">
                <div class="ob-card-title">✅ Vérification de votre profil</div>
                <div class="ob-card-subtitle">
                    Renforcez la confiance de vos futurs clients en vérifiant votre profil. La vérification est facultative mais fortement recommandée.
                </div>

                <div class="ob-verify-card">
                    <h6 style="font-weight: 700; color: #059669; margin-bottom: 4px;">
                        <i class="fas fa-shield-alt me-2"></i>Avantages d'un profil vérifié
                    </h6>
                    <div class="ob-verify-advantages">
                        <div class="ob-verify-adv"><i class="fas fa-check-circle"></i> Badge « Profil vérifié » visible sur votre profil et annonces</div>
                        <div class="ob-verify-adv"><i class="fas fa-check-circle"></i> Position prioritaire dans les résultats de recherche</div>
                        <div class="ob-verify-adv"><i class="fas fa-check-circle"></i> Taux de réponse des clients 3x supérieur</div>
                        <div class="ob-verify-adv"><i class="fas fa-check-circle"></i> Accès aux demandes de clients premium</div>
                        <div class="ob-verify-adv"><i class="fas fa-check-circle"></i> Badge de confiance qui rassure les clients</div>
                    </div>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <button type="button" class="ob-btn-next" style="padding: 10px 20px; font-size: 0.85rem;" onclick="window.open('{{ route('verification.index') }}', '_blank')">
                            <i class="fas fa-shield-alt"></i> Vérifier maintenant
                        </button>
                        <span style="font-size: 0.78rem; color: var(--ob-muted); display: flex; align-items: center;">
                            <i class="fas fa-clock me-1"></i> Vous pouvez aussi le faire plus tard depuis votre espace pro
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- STEP 5: Choix de l'abonnement                            -->
        <!-- ========================================================= -->
        <div class="ob-step-content" id="obStep5" style="display:none;">
            <div class="ob-card">
                <div class="ob-card-title">{{ $proSubscriptionsEnabled ? '👑 Choisissez votre abonnement' : '🚀 Accès prestataire pendant le lancement' }}</div>
                <div class="ob-card-subtitle">
                    {{ $proSubscriptionsEnabled
                        ? "L'abonnement ProxiPro vous donne accès à tous les outils professionnels : réception de demandes clients, devis, factures, gestion clientèle et visibilité accrue."
                        : "L'abonnement récurrent n'est pas encore commercialisé. Terminez votre configuration et utilisez l'espace prestataire sans abonnement pendant cette phase." }}
                </div>

                @if($proSubscriptionsEnabled)
                <div class="ob-plan-grid">
                    {{-- Monthly --}}
                    <div class="ob-plan-card" id="planMonthly" onclick="selectPlan('monthly')">
                        <div class="ob-plan-icon" style="background: linear-gradient(135deg, rgba(168,85,247,0.1), rgba(99,102,241,0.1)); color: #8b5cf6;">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="ob-plan-name">Mensuel</div>
                        <div class="ob-plan-desc">Flexibilité totale, sans engagement</div>
                        <div class="ob-plan-price">9,99€</div>
                        <div class="ob-plan-period">/mois</div>
                        <ul class="ob-plan-features">
                            <li><i class="fas fa-check"></i> Profil professionnel complet</li>
                            <li><i class="fas fa-check"></i> Devis & factures illimités</li>
                            <li><i class="fas fa-check"></i> Gestion de clientèle</li>
                            <li><i class="fas fa-check"></i> Badge Pro visible</li>
                            <li><i class="fas fa-check"></i> Jusqu'à 4 photos par annonce</li>
                            <li><i class="fas fa-check"></i> Notifications demandes clients</li>
                            <li><i class="fas fa-check"></i> Support prioritaire</li>
                        </ul>
                    </div>

                    {{-- Annual --}}
                    <div class="ob-plan-card selected" id="planAnnual" onclick="selectPlan('annual')">
                        <div class="ob-plan-badge">RECOMMANDÉ · -30%</div>
                        <div class="ob-plan-icon" style="background: linear-gradient(135deg, rgba(245,158,11,0.1), rgba(239,68,68,0.1)); color: #f59e0b;">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="ob-plan-name">Annuel</div>
                        <div class="ob-plan-desc">Meilleur rapport qualité-prix</div>
                        <div class="ob-plan-price">85€</div>
                        <div class="ob-plan-period">/an <span style="text-decoration: line-through; color: #94a3b8; font-size: 0.78rem;">119,88€</span></div>
                        <div style="font-size: 0.78rem; color: var(--ob-success); font-weight: 600; margin-top: 2px;">soit 7,08€/mois</div>
                        <ul class="ob-plan-features">
                            <li><i class="fas fa-check"></i> Tout le plan mensuel inclus</li>
                            <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Statistiques avancées</li>
                            <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Position prioritaire dans les recherches</li>
                            <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Badge « Pro Premium »</li>
                            <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Jusqu'à 4 photos par annonce</li>
                            <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Export comptable</li>
                            <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Assistance via formulaire de contact</li>
                        </ul>
                    </div>
                </div>
                @else
                <div style="padding: 22px; border-radius: 16px; background: rgba(16,185,129,.08); border: 1px solid rgba(16,185,129,.2); text-align: center;">
                    <i class="fas fa-check-circle" style="font-size: 2rem; color: #10b981; margin-bottom: 10px;"></i>
                    <h3 style="font-size: 1.05rem; margin-bottom: 6px;">Aucun paiement demandé</h3>
                    <p style="font-size: .84rem; color: var(--ob-muted); margin: 0;">Les offres Pro seront activées ultérieurement, lorsque leur facturation récurrente et le cadre administratif seront prêts.</p>
                </div>
                @endif
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- STEP 6: Récapitulatif & Confirmation                      -->
        <!-- ========================================================= -->
        <div class="ob-step-content" id="obStep6" style="display:none;">
            <div class="ob-card">
                <div class="ob-card-title">🧾 Récapitulatif de votre commande</div>
                <div class="ob-card-subtitle">Veuillez vérifier les informations ci-dessous avant de confirmer.</div>

                <div id="obRecapContent">
                    <div class="ob-recap-row">
                        <span class="ob-recap-label"><i class="fas fa-user me-2"></i>Professionnel</span>
                        <span class="ob-recap-value" id="recapName">{{ $user->company_name ?? $user->name }}</span>
                    </div>
                    <div class="ob-recap-row">
                        <span class="ob-recap-label"><i class="fas fa-map-marker-alt me-2"></i>Zone d'intervention</span>
                        <span class="ob-recap-value" id="recapZone">—</span>
                    </div>
                    <div class="ob-recap-row">
                        <span class="ob-recap-label"><i class="fas fa-expand me-2"></i>Rayon</span>
                        <span class="ob-recap-value" id="recapRadius">—</span>
                    </div>
                    <div class="ob-recap-row">
                        <span class="ob-recap-label"><i class="fas fa-tags me-2"></i>Catégories sélectionnées</span>
                        <span class="ob-recap-value" id="recapCategories">—</span>
                    </div>
                    <div class="ob-recap-row">
                        <span class="ob-recap-label"><i class="fas fa-bell me-2"></i>Alertes sur la plateforme</span>
                        <span class="ob-recap-value" id="recapNotif">—</span>
                    </div>
                    <div class="ob-recap-row" style="border-bottom: 2px solid var(--ob-border);">
                        <span class="ob-recap-label"><i class="fas fa-crown me-2"></i>Abonnement choisi</span>
                        <span class="ob-recap-value" id="recapPlan" style="color: var(--ob-primary);">—</span>
                    </div>
                    <div class="ob-recap-total">
                        <span>Total à payer</span>
                        <span id="recapTotal" style="color: var(--ob-primary);">—</span>
                    </div>
                </div>

                <div style="margin-top: 16px; padding: 14px; background: rgba(16,185,129,0.06); border-radius: 12px; border: 1px solid rgba(16,185,129,0.15);">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <i class="fas {{ $proSubscriptionsEnabled ? 'fa-lock' : 'fa-gift' }}" style="color: #059669;"></i>
                        <strong style="font-size: 0.88rem; color: #059669;">{{ $proSubscriptionsEnabled ? 'Paiement 100% sécurisé' : 'Accès gratuit pendant la phase de lancement' }}</strong>
                    </div>
                    <p style="font-size: 0.78rem; color: var(--ob-muted); margin: 0;">
                        {{ $proSubscriptionsEnabled
                            ? 'Votre paiement est crypté et traité par Stripe. Vous pouvez annuler votre abonnement à tout moment.'
                            : 'Vous serez informé avant toute future mise en place d’une offre payante. Aucun abonnement ne sera activé automatiquement.' }}
                    </p>
                </div>
            </div>

            {{-- Terms --}}
            <div style="margin-top: 12px;">
                <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 0.82rem; color: var(--ob-muted);">
                    <input type="checkbox" id="obAcceptTerms" style="margin-top: 3px; accent-color: var(--ob-primary);">
                    <span>J'accepte les <a href="{{ route('legal.terms') }}" target="_blank" style="color: var(--ob-primary);">conditions générales d'utilisation</a> et la <a href="{{ route('legal.privacy') }}" target="_blank" style="color: var(--ob-primary);">politique de confidentialité</a>.</span>
                </label>
            </div>
        </div>

        <!-- Footer navigation -->
        <div class="ob-footer" id="obFooter">
            <div>
                <button type="button" class="ob-btn-back" id="obBtnBack" onclick="obPrev()" style="display: none;">
                    <i class="fas fa-arrow-left"></i> Retour
                </button>
            </div>
            <div class="ob-footer-actions">
                <button type="button" class="ob-btn-skip" onclick="obSkipAll()">Passer pour le moment</button>
                <button type="button" class="ob-btn-next" id="obBtnNext" onclick="obNext()">
                    Commencer <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function() {
        // ---- DATA ----
        const catsData = @json($categories);
        const subscriptionsEnabled = @json($proSubscriptionsEnabled);
        let currentStep = 1;
        const totalSteps = 6;
        let selectedCats = [];
        let selectedSubs = [];
        let selectedPlan = subscriptionsEnabled ? 'annual' : null;

        // ---- STEPPER ----
        function updateStepper() {
            document.querySelectorAll('.ob-step').forEach(s => {
                const n = parseInt(s.dataset.step);
                const circle = s.querySelector('.ob-step-circle');
                s.classList.remove('active', 'done');
                if (n < currentStep) {
                    s.classList.add('done');
                    circle.innerHTML = '<i class="fas fa-check" style="font-size:0.75rem;"></i>';
                } else if (n === currentStep) {
                    s.classList.add('active');
                    circle.textContent = n;
                } else {
                    circle.textContent = n;
                }
            });
            document.querySelectorAll('.ob-step-line').forEach((line, i) => {
                line.classList.toggle('done', i + 1 < currentStep);
            });
            document.getElementById('obProgressFill').style.width = ((currentStep / totalSteps) * 100) + '%';
        }

        function showStep(n) {
            for (let i = 1; i <= totalSteps; i++) {
                const el = document.getElementById('obStep' + i);
                if (el) el.style.display = (i === n) ? '' : 'none';
            }
            currentStep = n;
            updateStepper();
            updateButtons();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function updateButtons() {
            const back = document.getElementById('obBtnBack');
            const next = document.getElementById('obBtnNext');
            back.style.display = currentStep > 1 ? '' : 'none';

            if (currentStep === totalSteps) {
                next.innerHTML = subscriptionsEnabled
                    ? '<i class="fas fa-credit-card me-1"></i> Confirmer et payer'
                    : '<i class="fas fa-check me-1"></i> Terminer la configuration';
            } else if (currentStep === 1) {
                next.innerHTML = 'Commencer <i class="fas fa-arrow-right"></i>';
            } else {
                next.innerHTML = 'Continuer <i class="fas fa-arrow-right"></i>';
            }
        }

        // ---- NAVIGATION ----
        window.obNext = function() {
            if (currentStep === 3 && selectedCats.length === 0) {
                alert('Veuillez sélectionner au moins une catégorie.');
                return;
            }
            if (currentStep === totalSteps) {
                submitOnboarding();
                return;
            }
            if (currentStep === 5) {
                buildRecap();
            }
            showStep(currentStep + 1);
        };

        window.obPrev = function() {
            if (currentStep > 1) showStep(currentStep - 1);
        };

        window.obSkipAll = function() {
            if (confirm('Êtes-vous sûr de vouloir passer la configuration ? Vous pourrez la compléter plus tard depuis votre espace pro.')) {
                // Mark onboarding as skipped
                fetch('{{ route("pro.onboarding.subscribe") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ skip: true })
                }).then(() => {
                    window.location.href = '{{ route("pro.dashboard") }}';
                }).catch(() => {
                    window.location.href = '{{ route("pro.dashboard") }}';
                });
            }
        };

        // ---- GEOLOCATION ----
        window.obGeolocate = function() {
            const btn = document.getElementById('obGeoBtn');
            const status = document.getElementById('obGeoStatus');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Localisation en cours...';
            status.style.display = '';
            status.style.color = 'var(--ob-primary)';
            status.textContent = 'Recherche de votre position...';

            if (!navigator.geolocation) {
                status.style.color = 'var(--ob-danger)';
                status.textContent = 'La géolocalisation n\'est pas supportée.';
                btn.innerHTML = '<i class="fas fa-crosshairs"></i> Me géolocaliser';
                return;
            }

            navigator.geolocation.getCurrentPosition(function(pos) {
                fetch('https://nominatim.openstreetmap.org/reverse?lat=' + pos.coords.latitude + '&lon=' + pos.coords.longitude + '&format=json&addressdetails=1')
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.address) {
                            const addr = data.address;
                            const city = addr.city || addr.town || addr.village || '';
                            const road = addr.road || '';
                            const num = addr.house_number || '';
                            const pc = addr.postcode || '';
                            document.getElementById('obAddress').value = ((num ? num + ' ' : '') + road + ', ' + pc + ' ' + city).trim().replace(/^,\s*/, '');
                            document.getElementById('obCity').value = city;
                            document.getElementById('obCountry').value = addr.country || 'France';
                            status.style.color = 'var(--ob-success)';
                            status.innerHTML = '<i class="fas fa-check-circle me-1"></i> Position trouvée : ' + city;
                        }
                        btn.innerHTML = '<i class="fas fa-crosshairs"></i> Me géolocaliser automatiquement';
                    }).catch(() => {
                        status.style.color = 'var(--ob-danger)';
                        status.textContent = 'Impossible de déterminer l\'adresse.';
                        btn.innerHTML = '<i class="fas fa-crosshairs"></i> Me géolocaliser automatiquement';
                    });
            }, function() {
                status.style.color = 'var(--ob-danger)';
                status.textContent = 'Accès à la localisation refusé.';
                btn.innerHTML = '<i class="fas fa-crosshairs"></i> Me géolocaliser automatiquement';
            }, { timeout: 10000 });
        };

        // ---- CATEGORIES ----
        function renderCategories() {
            const grid = document.getElementById('obCatGrid');
            grid.innerHTML = '';
            Object.entries(catsData).forEach(([name, data]) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ob-cat-btn';
                btn.innerHTML = `
                    <div class="ob-cat-check"><i class="fas fa-check"></i></div>
                    <span class="ob-cat-icon">${data.icon}</span>
                    <span class="ob-cat-name">${name}</span>
                `;
                btn.onclick = () => toggleCategory(name, btn);
                grid.appendChild(btn);
            });
        }

        function toggleCategory(name, btn) {
            const idx = selectedCats.indexOf(name);
            if (idx > -1) {
                selectedCats.splice(idx, 1);
                btn.classList.remove('selected');
            } else {
                selectedCats.push(name);
                btn.classList.add('selected');
            }

            const info = document.getElementById('obSelectedCatsInfo');
            const count = document.getElementById('obCatCount');
            if (selectedCats.length > 0) {
                info.style.display = '';
                count.textContent = selectedCats.length;
            } else {
                info.style.display = 'none';
            }

            renderSubcategories();
        }

        function renderSubcategories() {
            const area = document.getElementById('obSubArea');
            const list = document.getElementById('obSubList');
            if (selectedCats.length === 0) {
                area.style.display = 'none';
                return;
            }
            area.style.display = '';
            list.innerHTML = '';
            selectedSubs = [];

            selectedCats.forEach(catName => {
                const data = catsData[catName];
                if (!data || !data.subcategories) return;

                // Category header
                const header = document.createElement('div');
                header.style.cssText = 'width: 100%; font-size: 0.8rem; font-weight: 700; color: var(--ob-muted); margin-top: 8px; margin-bottom: 4px;';
                header.innerHTML = data.icon + ' ' + catName;
                list.appendChild(header);

                data.subcategories.forEach(sub => {
                    const chip = document.createElement('button');
                    chip.type = 'button';
                    chip.className = 'ob-sub-chip';
                    chip.textContent = sub;
                    chip.onclick = () => {
                        const i = selectedSubs.indexOf(sub);
                        if (i > -1) { selectedSubs.splice(i, 1); chip.classList.remove('selected'); }
                        else { selectedSubs.push(sub); chip.classList.add('selected'); }
                        const c = document.getElementById('obSubCount');
                        const n = document.getElementById('obSubCountNum');
                        if (selectedSubs.length > 0) { c.style.display = ''; n.textContent = selectedSubs.length; }
                        else { c.style.display = 'none'; }
                    };
                    list.appendChild(chip);
                });
            });
        }

        // ---- PLAN ----
        window.selectPlan = function(plan) {
            selectedPlan = plan;
            document.getElementById('planMonthly').classList.toggle('selected', plan === 'monthly');
            document.getElementById('planAnnual').classList.toggle('selected', plan === 'annual');
        };

        // ---- RECAP ----
        function buildRecap() {
            const city = document.getElementById('obCity').value || 'Non renseigné';
            const radius = document.getElementById('obRadius').value;
            const notifRT = document.getElementById('notifRealtime').checked;

            document.getElementById('recapZone').textContent = city;
            document.getElementById('recapRadius').textContent = radius + ' km';
            document.getElementById('recapCategories').textContent = selectedCats.length > 0 ? selectedCats.join(', ') : 'Non sélectionnées';
            document.getElementById('recapNotif').innerHTML = notifRT
                ? '<span style="color: var(--ob-success);"><i class="fas fa-check-circle me-1"></i>Activées</span>'
                : '<span style="color: var(--ob-muted);">Désactivées</span>';

            if (!subscriptionsEnabled) {
                document.getElementById('recapPlan').textContent = 'Aucun — accès lancement';
                document.getElementById('recapTotal').textContent = '0,00 €';
            } else if (selectedPlan === 'annual') {
                document.getElementById('recapPlan').textContent = 'Annuel (85€/an)';
                document.getElementById('recapTotal').textContent = '85,00 €';
            } else {
                document.getElementById('recapPlan').textContent = 'Mensuel (9,99€/mois)';
                document.getElementById('recapTotal').textContent = '9,99 €';
            }
        }

        // ---- SUBMIT ----
        function submitOnboarding() {
            const termsCheck = document.getElementById('obAcceptTerms');
            if (!termsCheck.checked) {
                alert('Veuillez accepter les conditions générales d\'utilisation.');
                return;
            }

            const btn = document.getElementById('obBtnNext');
            btn.disabled = true;
            btn.innerHTML = subscriptionsEnabled
                ? '<i class="fas fa-spinner fa-spin me-1"></i> Redirection vers le paiement...'
                : '<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...';

            const payload = {
                plan: selectedPlan,
                skip_subscription: !subscriptionsEnabled,
                categories: selectedCats,
                subcategories: selectedSubs,
                intervention_radius: parseInt(document.getElementById('obRadius').value) || 30,
                notifications_realtime: document.getElementById('notifRealtime').checked,
                address: document.getElementById('obAddress').value,
                city: document.getElementById('obCity').value,
                country: document.getElementById('obCountry').value,
                phone: document.getElementById('obPhone').value,
            };

            fetch('{{ route("pro.onboarding.subscribe") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.requires_payment && data.checkout_url) {
                    // Redirect to Stripe Checkout for payment
                    window.location.href = data.checkout_url;
                } else if (data.success && data.redirect) {
                    // Skip subscription case (no payment needed)
                    window.location.href = data.redirect;
                } else {
                    btn.disabled = false;
                    btn.innerHTML = subscriptionsEnabled
                        ? '<i class="fas fa-credit-card me-1"></i> Confirmer et payer'
                        : '<i class="fas fa-check me-1"></i> Terminer la configuration';
                    alert(data.message || 'Erreur lors de l\'activation. Veuillez réessayer.');
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = subscriptionsEnabled
                    ? '<i class="fas fa-credit-card me-1"></i> Confirmer et payer'
                    : '<i class="fas fa-check me-1"></i> Terminer la configuration';
                alert('Erreur de connexion. Veuillez réessayer.');
            });
        }

        // ---- INIT ----
        document.addEventListener('DOMContentLoaded', () => {
            renderCategories();
            showStep(1);
        });
    })();
    </script>
</body>
</html>

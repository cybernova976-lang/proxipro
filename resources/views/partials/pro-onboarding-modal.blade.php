{{-- ============================================== --}}
{{-- MODAL ONBOARDING PROFESSIONNEL                --}}
{{-- Pop-up 6 étapes sur la page feed              --}}
{{-- ============================================== --}}

@if(Auth::check() && (Auth::user()->isProfessionnel() || Auth::user()->isServiceProvider()))
<style>
/* ============================================
   ONBOARDING MODAL OVERLAY
   ============================================ */
.obm-overlay {
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(6px);
    display: none; align-items: center; justify-content: center;
    padding: 20px;
    opacity: 0; animation: obmFadeIn 0.35s ease forwards;
}
.obm-overlay.obm-visible {
    display: flex;
}
@keyframes obmFadeIn { to { opacity: 1; } }

.obm-modal {
    width: 100%; max-width: 820px; max-height: 92vh;
    background: #fff; border-radius: 20px;
    box-shadow: 0 25px 60px rgba(0,0,0,0.2);
    display: flex; flex-direction: column;
    overflow: hidden; position: relative;
    transform: translateY(20px) scale(0.97);
    animation: obmSlideUp 0.4s ease 0.1s forwards;
}
@keyframes obmSlideUp { to { transform: translateY(0) scale(1); } }

/* Close button */
.obm-close {
    position: absolute; top: 14px; right: 14px; z-index: 10;
    width: 36px; height: 36px; border-radius: 50%;
    border: none; background: rgba(0,0,0,0.06); color: #64748b;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all 0.2s; font-size: 1rem;
}
.obm-close:hover { background: rgba(239,68,68,0.1); color: #ef4444; }

/* Progress bar */
.obm-progress { height: 4px; background: #e2e8f0; flex-shrink: 0; }
.obm-progress-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #8b5cf6); transition: width 0.5s ease; border-radius: 0 2px 2px 0; }

/* Stepper */
.obm-stepper {
    display: flex; align-items: center; justify-content: center;
    gap: 0; padding: 18px 24px 0; flex-shrink: 0;
}
.obm-step {
    display: flex; flex-direction: column; align-items: center;
    gap: 4px; flex-shrink: 0; position: relative; z-index: 1;
}
.obm-step-circle {
    width: 34px; height: 34px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 0.78rem; transition: all 0.3s;
    border: 2px solid #e2e8f0; background: #fff; color: #94a3b8;
}
.obm-step.active .obm-step-circle {
    background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99,102,241,0.12);
}
.obm-step.done .obm-step-circle {
    background: #10b981; color: #fff; border-color: #10b981;
}
.obm-step-label { font-size: 0.62rem; font-weight: 600; color: #94a3b8; white-space: nowrap; }
.obm-step.active .obm-step-label { color: #6366f1; }
.obm-step.done .obm-step-label { color: #10b981; }
.obm-step-line {
    flex: 1; height: 2px; background: #e2e8f0;
    margin: 0 -2px; margin-top: -16px; position: relative; z-index: 0;
}
.obm-step-line.done { background: #10b981; }

/* Scrollable body */
.obm-body {
    flex: 1; overflow-y: auto; padding: 24px 32px;
    scroll-behavior: smooth;
}
.obm-body::-webkit-scrollbar { width: 5px; }
.obm-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

/* Cards */
.obm-card {
    background: #fff; border-radius: 18px; padding: 26px;
    border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    margin-bottom: 18px;
    transition: box-shadow 0.2s;
}
.obm-card:hover {
    box-shadow: 0 6px 24px rgba(0,0,0,0.06);
}
.obm-card-title {
    font-size: 1.2rem; font-weight: 800; margin-bottom: 6px; color: #1e293b;
    display: flex; align-items: center; gap: 8px;
}
.obm-card-subtitle {
    font-size: 0.84rem; color: #64748b; margin-bottom: 20px; line-height: 1.55;
}

/* Welcome hero (inside modal) */
.obm-welcome-hero {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #6366f1 100%); color: #fff;
    border-radius: 20px; padding: 44px 32px 38px; text-align: center;
    margin-bottom: 20px; position: relative; overflow: hidden;
    box-shadow: 0 12px 40px rgba(99,102,241,0.18);
}
.obm-welcome-hero::before {
    content: ''; position: absolute; top: -60%; right: -25%;
    width: 300px; height: 300px; border-radius: 50%; background: rgba(255,255,255,0.06);
}
.obm-welcome-hero::after {
    content: ''; position: absolute; bottom: -50%; left: -15%;
    width: 240px; height: 240px; border-radius: 50%; background: rgba(139,92,246,0.12);
}
.obm-welcome-emoji { font-size: 3.2rem; margin-bottom: 14px; position: relative; z-index: 1; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.15)); }
.obm-welcome-title { font-size: 1.6rem; font-weight: 800; margin-bottom: 8px; position: relative; z-index: 1; letter-spacing: -0.3px; }
.obm-welcome-sub { font-size: 0.9rem; opacity: 0.92; max-width: 480px; margin: 0 auto; position: relative; z-index: 1; line-height: 1.6; }

/* Info items */
.obm-info-item {
    display: flex; align-items: center; gap: 14px; padding: 14px 16px;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9); border-radius: 14px; margin-bottom: 8px;
    border: 1px solid rgba(226,232,240,0.6);
    transition: all 0.2s ease;
}
.obm-info-item:hover {
    background: linear-gradient(135deg, #eef2ff, #f0f4ff);
    border-color: rgba(99,102,241,0.15);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.04);
}
.obm-info-icon {
    width: 42px; height: 42px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.95rem; flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.obm-info-label { font-size: 0.72rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }
.obm-info-value { font-size: 0.88rem; font-weight: 700; color: #1e293b; }
.obm-info-value.missing { color: #f59e0b; font-style: italic; font-weight: 500; }

/* Category grid */
.obm-cat-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;
}
@media (max-width: 640px) { .obm-cat-grid { grid-template-columns: repeat(2, 1fr); } }
.obm-cat-btn {
    display: flex; flex-direction: column; align-items: center;
    gap: 6px; padding: 14px 8px; border-radius: 12px;
    border: 2px solid #e2e8f0; background: #fff;
    cursor: pointer; transition: all 0.2s; text-align: center;
    position: relative;
}
.obm-cat-btn:hover { border-color: #c7d2fe; background: #fafbff; transform: translateY(-1px); }
.obm-cat-btn.selected { border-color: #6366f1; background: rgba(99,102,241,0.05); box-shadow: 0 0 0 3px rgba(99,102,241,0.08); }
.obm-cat-icon { font-size: 1.5rem; }
.obm-cat-name { font-size: 0.72rem; font-weight: 600; color: #374151; line-height: 1.25; }
.obm-cat-btn.selected .obm-cat-name { color: #6366f1; }
.obm-cat-check {
    display: none; position: absolute; top: 5px; right: 5px;
    width: 20px; height: 20px; border-radius: 50%;
    background: #6366f1; color: #fff;
    align-items: center; justify-content: center; font-size: 0.6rem;
}
.obm-cat-btn.selected .obm-cat-check { display: flex; }

/* Sub chips */
.obm-sub-list { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
.obm-sub-chip {
    padding: 6px 14px; border-radius: 20px; border: 2px solid #e2e8f0;
    font-size: 0.76rem; font-weight: 500; color: #6b7280; background: #fff;
    cursor: pointer; transition: all 0.2s;
}
.obm-sub-chip:hover { border-color: #93c5fd; background: #f0f7ff; }
.obm-sub-chip.selected { border-color: #6366f1; background: rgba(99,102,241,0.05); color: #6366f1; font-weight: 600; }

/* Notification toggles */
.obm-notif-option {
    display: flex; align-items: center; gap: 12px;
    padding: 14px; background: #f8fafc; border-radius: 10px;
    margin-bottom: 6px; cursor: pointer;
}
.obm-notif-option:hover { background: #f1f5f9; }
.obm-notif-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0;
}
.obm-notif-text { flex: 1; }
.obm-notif-text strong { font-size: 0.82rem; display: block; }
.obm-notif-text span { font-size: 0.72rem; color: #64748b; }



/* Plan cards */
.obm-plan-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 640px) { .obm-plan-grid { grid-template-columns: 1fr; } }
.obm-plan-card {
    border: 2px solid #e2e8f0; border-radius: 14px;
    padding: 24px 20px; text-align: center; cursor: pointer;
    transition: all 0.3s; position: relative; background: #fff;
}
.obm-plan-card:hover { border-color: #c7d2fe; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.05); }
.obm-plan-card.selected { border-color: #6366f1; background: rgba(99,102,241,0.02); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.obm-plan-badge {
    position: absolute; top: -12px; right: 14px;
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    color: #fff; padding: 3px 12px; border-radius: 18px;
    font-size: 0.65rem; font-weight: 700; letter-spacing: 0.5px;
}
.obm-plan-icon {
    width: 50px; height: 50px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; margin: 0 auto 12px;
}
.obm-plan-name { font-size: 1rem; font-weight: 700; margin-bottom: 3px; }
.obm-plan-desc { font-size: 0.75rem; color: #64748b; margin-bottom: 10px; }
.obm-plan-price { font-size: 2rem; font-weight: 800; color: #6366f1; }
.obm-plan-period { font-size: 0.8rem; color: #64748b; }
.obm-plan-features { list-style: none; padding: 0; margin: 14px 0 0; text-align: left; }
.obm-plan-features li { font-size: 0.76rem; padding: 4px 0; display: flex; align-items: center; gap: 7px; }
.obm-plan-features li i { color: #10b981; width: 14px; text-align: center; }

/* Recap */
.obm-recap-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.82rem;
}
.obm-recap-row:last-child { border-bottom: none; }
.obm-recap-label { color: #64748b; }
.obm-recap-value { font-weight: 600; }
.obm-recap-total {
    display: flex; justify-content: space-between; align-items: center;
    padding: 14px 0; margin-top: 6px; border-top: 2px solid #1e293b;
    font-size: 1rem; font-weight: 800;
}

/* Footer sticky */
.obm-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 32px; border-top: 1px solid #e2e8f0;
    background: #fafbfc; flex-shrink: 0;
}
.obm-btn-back {
    background: none; border: none; color: #64748b;
    font-size: 0.82rem; font-weight: 500; cursor: pointer;
    display: flex; align-items: center; gap: 6px;
}
.obm-btn-back:hover { color: #1e293b; }
.obm-btn-next {
    padding: 12px 28px; border-radius: 10px; border: none;
    background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff;
    font-size: 0.88rem; font-weight: 700; cursor: pointer;
    transition: all 0.2s; display: flex; align-items: center; gap: 8px;
}
.obm-btn-next:hover { opacity: 0.9; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,0.25); }
.obm-btn-next:disabled { opacity: 0.4; cursor: not-allowed; transform: none; box-shadow: none; }
.obm-btn-skip {
    background: none; border: none; color: #94a3b8;
    font-size: 0.76rem; cursor: pointer; text-decoration: underline;
}
.obm-btn-skip:hover { color: #1e293b; }
.obm-btn-skip-sub {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 20px; border-radius: 10px; border: 2px solid #e2e8f0;
    background: transparent; color: #64748b; font-size: 0.82rem;
    font-weight: 600; cursor: pointer; transition: all 0.2s; margin-top: 14px;
}
.obm-btn-skip-sub:hover { border-color: #f59e0b; color: #f59e0b; background: rgba(245,158,11,0.04); }

/* Inputs */
.obm-input {
    width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0;
    border-radius: 10px; font-size: 0.82rem; color: #1e293b;
    outline: none; transition: border-color 0.2s; font-family: inherit;
}
.obm-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.06); }
.obm-label { display: block; font-size: 0.76rem; font-weight: 600; color: #1e293b; margin-bottom: 4px; }

/* Geo btn */
.obm-geo-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 16px; border: 2px solid #6366f1;
    border-radius: 10px; background: rgba(99,102,241,0.04);
    color: #6366f1; font-size: 0.8rem; font-weight: 600;
    cursor: pointer; transition: all 0.2s;
}
.obm-geo-btn:hover { background: #6366f1; color: #fff; }

/* Count badge */
.obm-count-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 18px; font-size: 0.75rem; font-weight: 600;
    background: rgba(16,185,129,0.08); color: #059669; margin-top: 10px;
}

/* Step content animation */
.obm-step-content { animation: obmContentSlide 0.35s ease-out; }
@keyframes obmContentSlide {
    from { opacity: 0; transform: translateX(16px); }
    to { opacity: 1; transform: translateX(0); }
}

/* Close warning overlay */
.obm-close-warning {
    position: absolute; inset: 0; z-index: 20;
    background: rgba(255,255,255,0.95); backdrop-filter: blur(3px);
    display: flex; align-items: center; justify-content: center;
    border-radius: 20px;
}
.obm-close-warning-card {
    text-align: center; padding: 40px 32px; max-width: 420px;
}
.obm-close-warning-card h3 { font-size: 1.15rem; font-weight: 800; margin-bottom: 8px; color: #1e293b; }
.obm-close-warning-card p { font-size: 0.85rem; color: #64748b; line-height: 1.5; margin-bottom: 20px; }
.obm-close-warning-btns { display: flex; gap: 10px; justify-content: center; }
.obm-close-warning-btns button {
    padding: 10px 22px; border-radius: 10px; border: none;
    font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.obm-close-warning-stay {
    background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff;
}
.obm-close-warning-stay:hover { opacity: 0.9; }
.obm-close-warning-leave {
    background: #f1f5f9; color: #64748b;
}
.obm-close-warning-leave:hover { background: #e2e8f0; color: #ef4444; }

/* Success animation */
.obm-success { text-align: center; padding: 40px 20px; }
.obm-success-icon { font-size: 3.5rem; margin-bottom: 16px; }
.obm-success h2 { font-size: 1.5rem; font-weight: 800; margin-bottom: 8px; color: #1e293b; }
.obm-success p { font-size: 0.88rem; color: #64748b; max-width: 420px; margin: 0 auto 20px; line-height: 1.5; }

/* Phone SMS field */
.obm-sms-phone {
    margin-top: 8px; padding: 10px 14px; display: none;
    background: rgba(168,85,247,0.04); border-radius: 10px;
}

@media (max-width: 640px) {
    .obm-modal { max-height: 96vh; border-radius: 14px; }
    .obm-body { padding: 16px 18px; }
    .obm-footer { padding: 12px 18px; }
    .obm-welcome-hero { padding: 28px 18px; }
    .obm-welcome-title { font-size: 1.2rem; }
    .obm-stepper { padding: 12px 12px 0; }
    .obm-step-label { font-size: 0.55rem; }
}
</style>

<div class="obm-overlay" id="obmOverlay">
    <div class="obm-modal" id="obmModal">

        {{-- Close button --}}
        <button class="obm-close" onclick="obmRequestClose()" title="Fermer">
            <i class="fas fa-times"></i>
        </button>

        {{-- Close warning overlay (hidden by default) --}}
        <div class="obm-close-warning" id="obmCloseWarning" style="display: none;">
            <div class="obm-close-warning-card">
                <div style="font-size: 2.5rem; margin-bottom: 12px;">⚠️</div>
                <h3>Êtes-vous sûr de vouloir fermer ?</h3>
                <p>La configuration de votre espace professionnel est nécessaire pour recevoir des demandes de clients. Votre progression sera sauvegardée.</p>
                <div class="obm-close-warning-btns">
                    <button class="obm-close-warning-stay" onclick="obmDismissCloseWarning()">
                        <i class="fas fa-arrow-left me-1"></i> Continuer
                    </button>
                    <button class="obm-close-warning-leave" onclick="obmForceClose()">
                        Fermer quand même
                    </button>
                </div>
            </div>
        </div>

        {{-- Progress bar --}}
        <div class="obm-progress">
            <div class="obm-progress-fill" id="obmProgressFill" style="width: 16.6%"></div>
        </div>

        {{-- Stepper --}}
        <div class="obm-stepper" id="obmStepper">
            <div class="obm-step active" data-step="1">
                <div class="obm-step-circle">1</div>
                <span class="obm-step-label">Bienvenue</span>
            </div>
            <div class="obm-step-line"></div>
            <div class="obm-step" data-step="2">
                <div class="obm-step-circle">2</div>
                <span class="obm-step-label">Localisation</span>
            </div>
            <div class="obm-step-line"></div>
            <div class="obm-step" data-step="3">
                <div class="obm-step-circle">3</div>
                <span class="obm-step-label">Catégories</span>
            </div>
            <div class="obm-step-line"></div>
            <div class="obm-step" data-step="4">
                <div class="obm-step-circle">4</div>
                <span class="obm-step-label">Notifications</span>
            </div>
            <div class="obm-step-line"></div>
            <div class="obm-step" data-step="5">
                <div class="obm-step-circle">5</div>
                <span class="obm-step-label">Abonnement</span>
            </div>
            <div class="obm-step-line"></div>
            <div class="obm-step" data-step="6">
                <div class="obm-step-circle">6</div>
                <span class="obm-step-label">Confirmation</span>
            </div>
        </div>

        {{-- Scrollable body --}}
        <div class="obm-body" id="obmBody">

            {{-- ======================== STEP 1: Bienvenue ======================== --}}
            <div class="obm-step-content" id="obmStep1">
                <div class="obm-welcome-hero">
                    <div class="obm-welcome-emoji">🎉</div>
                    <div class="obm-welcome-title">Bienvenue, {{ Auth::user()->company_name ?? Auth::user()->name }} !</div>
                    <div class="obm-welcome-sub">
                        Configurez votre espace en quelques étapes pour commencer à recevoir des demandes de clients près de chez vous.
                    </div>
                </div>

                <div class="obm-card">
                    <div class="obm-card-title">📋 Vos informations</div>
                    <div class="obm-card-subtitle">Voici les informations enregistrées lors de votre inscription.</div>

                    <div class="obm-info-item">
                        <div class="obm-info-icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <div class="obm-info-label">Nom / Raison sociale</div>
                            <div class="obm-info-value">{{ Auth::user()->company_name ?? Auth::user()->name }}</div>
                        </div>
                    </div>

                    <div class="obm-info-item">
                        <div class="obm-info-icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <div class="obm-info-label">Email</div>
                            <div class="obm-info-value">{{ Auth::user()->email }}</div>
                        </div>
                    </div>

                    <div class="obm-info-item">
                        <div class="obm-info-icon" style="background: rgba(168,85,247,0.1); color: #a855f7;">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <div class="obm-info-label">Téléphone</div>
                            <div class="obm-info-value {{ !Auth::user()->phone ? 'missing' : '' }}">
                                {{ Auth::user()->phone ?: 'Non renseigné' }}
                            </div>
                        </div>
                    </div>

                    <div class="obm-info-item">
                        <div class="obm-info-icon" style="background: rgba(239,68,68,0.1); color: #ef4444;">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <div class="obm-info-label">Localisation</div>
                            <div class="obm-info-value {{ !Auth::user()->getDisplayCity() ? 'missing' : '' }}">
                                {{ Auth::user()->getLocationLabel() }}
                            </div>
                        </div>
                    </div>

                    <div class="obm-info-item">
                        <div class="obm-info-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div>
                            <div class="obm-info-label">Statut professionnel</div>
                            <div class="obm-info-value">{{ Auth::user()->getAccountTypeLabel() }}</div>
                        </div>
                    </div>

                    @if(Auth::user()->siret)
                    <div class="obm-info-item">
                        <div class="obm-info-icon" style="background: rgba(20,184,166,0.1); color: #14b8a6;">
                            <i class="fas fa-building"></i>
                        </div>
                        <div>
                            <div class="obm-info-label">SIRET</div>
                            <div class="obm-info-value">{{ Auth::user()->siret }}</div>
                        </div>
                    </div>
                    @endif

                    <div style="margin-top: 12px; padding: 10px 14px; background: rgba(99,102,241,0.05); border-radius: 8px; font-size: 0.76rem; color: #6366f1; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-info-circle"></i>
                        Modifiable à tout moment depuis votre espace professionnel.
                    </div>
                </div>
            </div>

            {{-- ======================== STEP 2: Localisation ======================== --}}
            <div class="obm-step-content" id="obmStep2" style="display:none;">
                <div class="obm-card">
                    <div class="obm-card-title">📍 Zone d'intervention</div>
                    <div class="obm-card-subtitle">
                        Définissez votre localisation et votre rayon d'action pour recevoir des demandes de clients.
                    </div>

                    <div style="margin-bottom: 16px;">
                        <button type="button" class="obm-geo-btn" id="obmGeoBtn" onclick="obmGeolocate()">
                            <i class="fas fa-crosshairs"></i> Me géolocaliser
                        </button>
                        <div id="obmGeoStatus" style="margin-top: 6px; font-size: 0.75rem; display: none;"></div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="obm-label">Adresse *</label>
                            <input type="text" class="obm-input" id="obmAddress" placeholder="12 rue de la Paix" value="{{ Auth::user()->address ?? '' }}">
                        </div>
                        <div>
                            <label class="obm-label">Code postal</label>
                            <input type="text" class="obm-input" id="obmPostalCode" placeholder="97600" value="{{ Auth::user()->postal_code ?? '' }}" maxlength="10">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="obm-label">Ville *</label>
                            <input type="text" class="obm-input" id="obmCity" placeholder="Paris" value="{{ Auth::user()->city ?? Auth::user()->detected_city ?? '' }}">
                        </div>
                        <div>
                            <label class="obm-label">Pays</label>
                            <input type="text" class="obm-input" id="obmCountry" placeholder="France" value="{{ Auth::user()->country ?? Auth::user()->detected_country ?? 'France' }}">
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label class="obm-label">Téléphone professionnel</label>
                        <input type="tel" class="obm-input" id="obmPhone" placeholder="06 12 34 56 78" value="{{ Auth::user()->phone ?? '' }}">
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label class="obm-label">Rayon d'intervention : <strong id="obmRadiusLabel">{{ Auth::user()->pro_intervention_radius ?? 30 }} km</strong></label>
                        <input type="range" id="obmRadius" min="5" max="200" value="{{ Auth::user()->pro_intervention_radius ?? 30 }}" style="width: 100%; accent-color: #6366f1;" oninput="document.getElementById('obmRadiusLabel').textContent = this.value + ' km'">
                        <div style="display: flex; justify-content: space-between; font-size: 0.68rem; color: #94a3b8;">
                            <span>5 km</span><span>50 km</span><span>100 km</span><span>200 km</span>
                        </div>
                    </div>

                    <div style="padding: 12px 14px; background: rgba(245,158,11,0.05); border-radius: 8px; border-left: 3px solid #f59e0b; font-size: 0.76rem; color: #92400e;">
                        <strong><i class="fas fa-lightbulb me-1"></i>Conseil :</strong> 30 à 50 km est un bon compromis pour commencer.
                    </div>
                </div>
            </div>

            {{-- ======================== STEP 3: Catégories ======================== --}}
            <div class="obm-step-content" id="obmStep3" style="display:none;">
                <div class="obm-card">
                    <div class="obm-card-title">🔧 Catégories de métier</div>
                    <div class="obm-card-subtitle">
                        Sélectionnez vos domaines d'expertise. <strong style="color: #6366f1;">Plus vous sélectionnez, plus vous recevrez de demandes !</strong>
                    </div>

                    <div class="obm-cat-grid" id="obmCatGrid"></div>

                    <div id="obmSelectedCatsInfo" style="display: none; margin-top: 12px;">
                        <div class="obm-count-badge">
                            <i class="fas fa-check-circle"></i>
                            <span id="obmCatCount">0</span> catégorie(s) sélectionnée(s)
                        </div>
                    </div>

                    <div id="obmSubArea" style="display: none; margin-top: 20px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                        <h6 style="font-weight: 700; font-size: 0.85rem; margin-bottom: 3px;">
                            <i class="fas fa-tags me-1" style="color: #6366f1;"></i>Choisissez vos spécialités
                        </h6>
                        <p style="font-size: 0.74rem; color: #64748b; margin-bottom: 10px;">
                            Sélectionnez les sous-catégories correspondant à votre expertise :
                        </p>
                        <div class="obm-sub-list" id="obmSubList"></div>
                        <div class="obm-count-badge" id="obmSubCount" style="display: none;">
                            <i class="fas fa-check-circle"></i>
                            <span id="obmSubCountNum">0</span> spécialité(s)
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======================== STEP 4: Notifications ======================== --}}
            <div class="obm-step-content" id="obmStep4" style="display:none;">
                <div class="obm-card">
                    <div class="obm-card-title">🔔 Notifications</div>
                    <div class="obm-card-subtitle">
                        Choisissez comment être informé des nouvelles demandes de clients.
                    </div>

                    <label class="obm-notif-option" onclick="event.stopPropagation(); document.getElementById('obmNotifRealtime').checked = !document.getElementById('obmNotifRealtime').checked">
                        <div class="obm-notif-icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="obm-notif-text">
                            <strong>Alertes dans la plateforme</strong>
                            <span>Alerte dès qu'un client publie dans votre zone.</span>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="obmNotifRealtime" checked style="width: 44px; height: 22px;" onclick="event.stopPropagation()">
                        </div>
                    </label>

                    <label class="obm-notif-option" onclick="event.stopPropagation(); document.getElementById('obmNotifEmail').checked = !document.getElementById('obmNotifEmail').checked">
                        <div class="obm-notif-icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="obm-notif-text">
                            <strong>Alertes par e-mail</strong>
                            <span>Nouvelles demandes correspondant à votre activité.</span>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="obmNotifEmail" checked style="width: 44px; height: 22px;" onclick="event.stopPropagation()">
                        </div>
                    </label>

                </div>


            </div>

            {{-- ======================== STEP 5: Abonnement ======================== --}}
            <div class="obm-step-content" id="obmStep5" style="display:none;">
                <div class="obm-card">
                    <div class="obm-card-title">{{ $proSubscriptionsEnabled ? '👑 Choisissez votre abonnement' : '🚀 Accès prestataire pendant le lancement' }}</div>
                    <div class="obm-card-subtitle">
                        {{ $proSubscriptionsEnabled
                            ? "L'abonnement ProxiPro vous donne accès à tous les outils professionnels : demandes clients, devis, factures, clientèle et visibilité."
                            : "Aucun abonnement n'est commercialisé pour le moment. Vous pouvez terminer gratuitement la configuration de votre espace." }}
                    </div>

                    @if($proSubscriptionsEnabled)
                    <div class="obm-plan-grid">
                        {{-- Monthly --}}
                        <div class="obm-plan-card" id="obmPlanMonthly" onclick="obmSelectPlan('monthly')">
                            <div class="obm-plan-icon" style="background: linear-gradient(135deg, rgba(168,85,247,0.1), rgba(99,102,241,0.1)); color: #8b5cf6;">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="obm-plan-name">Mensuel</div>
                            <div class="obm-plan-desc">Flexible, sans engagement</div>
                            <div class="obm-plan-price">9,99€</div>
                            <div class="obm-plan-period">/mois</div>
                            <ul class="obm-plan-features">
                                <li><i class="fas fa-check"></i> Profil professionnel complet</li>
                                <li><i class="fas fa-check"></i> Devis & factures illimités</li>
                                <li><i class="fas fa-check"></i> Gestion de clientèle</li>
                                <li><i class="fas fa-check"></i> Badge Pro visible</li>
                                <li><i class="fas fa-check"></i> Jusqu'à 4 photos par annonce</li>
                                <li><i class="fas fa-check"></i> Notifications clients</li>
                                <li><i class="fas fa-check"></i> Support prioritaire</li>
                            </ul>
                        </div>

                        {{-- Annual --}}
                        <div class="obm-plan-card selected" id="obmPlanAnnual" onclick="obmSelectPlan('annual')">
                            <div class="obm-plan-badge">RECOMMANDÉ · -30%</div>
                            <div class="obm-plan-icon" style="background: linear-gradient(135deg, rgba(245,158,11,0.1), rgba(239,68,68,0.1)); color: #f59e0b;">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div class="obm-plan-name">Annuel</div>
                            <div class="obm-plan-desc">Meilleur rapport qualité-prix</div>
                            <div class="obm-plan-price">85€</div>
                            <div class="obm-plan-period">/an <span style="text-decoration: line-through; color: #94a3b8; font-size: 0.72rem;">119,88€</span></div>
                            <div style="font-size: 0.72rem; color: #10b981; font-weight: 600; margin-top: 2px;">soit 7,08€/mois</div>
                            <ul class="obm-plan-features">
                                <li><i class="fas fa-check"></i> Tout le plan mensuel</li>
                                <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Statistiques avancées</li>
                                <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Position prioritaire</li>
                                <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Badge « Pro Premium »</li>
                                <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Jusqu'à 4 photos par annonce</li>
                                <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Export comptable</li>
                                <li><i class="fas fa-star" style="color: #f59e0b !important;"></i> Assistance via formulaire</li>
                            </ul>
                        </div>
                    </div>
                    @else
                    <div style="padding: 18px; border-radius: 14px; background: rgba(16,185,129,.08); border: 1px solid rgba(16,185,129,.2); text-align:center;">
                        <i class="fas fa-check-circle" style="font-size:1.7rem;color:#10b981;margin-bottom:8px;"></i>
                        <strong style="display:block;margin-bottom:4px;">Aucun paiement demandé</strong>
                        <span style="font-size:.76rem;color:#64748b;">L’offre récurrente pourra être ajoutée ultérieurement sans modifier votre profil.</span>
                    </div>
                    @endif

                    <div style="text-align: center; margin-top: 16px;">
                        <button type="button" class="obm-btn-skip-sub" onclick="obmSkipSubscription()">
                            <i class="fas {{ $proSubscriptionsEnabled ? 'fa-forward' : 'fa-check' }}"></i> {{ $proSubscriptionsEnabled ? "Passer l'abonnement pour le moment" : 'Terminer gratuitement' }}
                        </button>
                        <p style="font-size: 0.7rem; color: #94a3b8; margin-top: 6px;">
                            {{ $proSubscriptionsEnabled ? 'Vous pourrez souscrire plus tard. Certaines fonctionnalités premium ne seront pas disponibles.' : 'Aucun abonnement ne sera activé automatiquement.' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- ======================== STEP 6: Récapitulatif ======================== --}}
            <div class="obm-step-content" id="obmStep6" style="display:none;">
                <div class="obm-card">
                    <div class="obm-card-title">🧾 Récapitulatif</div>
                    <div class="obm-card-subtitle">Vérifiez les informations avant de confirmer.</div>

                    <div id="obmRecapContent">
                        <div class="obm-recap-row">
                            <span class="obm-recap-label"><i class="fas fa-user me-2"></i>Professionnel</span>
                            <span class="obm-recap-value" id="obmRecapName">{{ Auth::user()->company_name ?? Auth::user()->name }}</span>
                        </div>
                        <div class="obm-recap-row">
                            <span class="obm-recap-label"><i class="fas fa-map-marker-alt me-2"></i>Zone</span>
                            <span class="obm-recap-value" id="obmRecapZone">—</span>
                        </div>
                        <div class="obm-recap-row">
                            <span class="obm-recap-label"><i class="fas fa-expand me-2"></i>Rayon</span>
                            <span class="obm-recap-value" id="obmRecapRadius">—</span>
                        </div>
                        <div class="obm-recap-row">
                            <span class="obm-recap-label"><i class="fas fa-tags me-2"></i>Catégories</span>
                            <span class="obm-recap-value" id="obmRecapCategories">—</span>
                        </div>
                        <div class="obm-recap-row">
                            <span class="obm-recap-label"><i class="fas fa-bell me-2"></i>Notifications</span>
                            <span class="obm-recap-value" id="obmRecapNotif">—</span>
                        </div>
                        <div class="obm-recap-row" style="border-bottom: 2px solid #e2e8f0;">
                            <span class="obm-recap-label"><i class="fas fa-crown me-2"></i>Abonnement</span>
                            <span class="obm-recap-value" id="obmRecapPlan" style="color: #6366f1;">—</span>
                        </div>
                        <div class="obm-recap-total">
                            <span>Total à payer</span>
                            <span id="obmRecapTotal" style="color: #6366f1;">—</span>
                        </div>
                    </div>

                    <div style="margin-top: 14px; padding: 12px; background: rgba(16,185,129,0.05); border-radius: 10px; border: 1px solid rgba(16,185,129,0.12);">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <i class="fas fa-lock" style="color: #059669;"></i>
                            <strong style="font-size: 0.82rem; color: #059669;">Paiement 100% sécurisé</strong>
                        </div>
                        <p style="font-size: 0.72rem; color: #64748b; margin: 0;">
                            Paiement crypté par Stripe. Annulation à tout moment. Satisfait ou remboursé 14 jours.
                        </p>
                    </div>
                </div>

                <div style="margin-top: 10px;">
                    <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer; font-size: 0.76rem; color: #64748b;">
                        <input type="checkbox" id="obmAcceptTerms" style="margin-top: 3px; accent-color: #6366f1;">
                        <span>J'accepte les <a href="{{ route('legal.terms') }}" target="_blank" style="color: #6366f1;">conditions générales</a> et la <a href="{{ route('legal.privacy') }}" target="_blank" style="color: #6366f1;">politique de confidentialité</a>.</span>
                    </label>
                </div>
            </div>

        </div>

        {{-- Footer --}}
        <div class="obm-footer" id="obmFooter">
            <div>
                <button type="button" class="obm-btn-back" id="obmBtnBack" onclick="obmPrev()" style="display: none;">
                    <i class="fas fa-arrow-left"></i> Retour
                </button>
            </div>
            <div style="display: flex; align-items: center; gap: 14px;">
                <button type="button" class="obm-btn-skip" onclick="obmRequestClose()">Passer</button>
                <button type="button" class="obm-btn-next" id="obmBtnNext" onclick="obmNext()">
                    Commencer <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

    </div>
</div>

<script>
(function() {
    'use strict';

    // ---- DATA ----
    const obmCatsData = @json($onboardingCategories ?? []);
    const obmSubscriptionsEnabled = @json($proSubscriptionsEnabled);
    const obmStartStep = {{ Auth::user()->pro_onboarding_step ?? 0 }};
    let obmCurrentStep = 1;
    const obmTotalSteps = 6;
    let obmSelectedCats = [];
    let obmSelectedSubs = [];
    let obmSelectedPlan = obmSubscriptionsEnabled ? 'annual' : null;
    let obmGeoLat = null;
    let obmGeoLng = null;

    // ---- STEPPER ----
    function obmUpdateStepper() {
        document.querySelectorAll('#obmStepper .obm-step').forEach(s => {
            const n = parseInt(s.dataset.step);
            const circle = s.querySelector('.obm-step-circle');
            s.classList.remove('active', 'done');
            if (n < obmCurrentStep) {
                s.classList.add('done');
                circle.innerHTML = '<i class="fas fa-check" style="font-size:0.65rem;"></i>';
            } else if (n === obmCurrentStep) {
                s.classList.add('active');
                circle.textContent = n;
            } else {
                circle.textContent = n;
            }
        });
        document.querySelectorAll('#obmStepper .obm-step-line').forEach((line, i) => {
            line.classList.toggle('done', i + 1 < obmCurrentStep);
        });
        document.getElementById('obmProgressFill').style.width = ((obmCurrentStep / obmTotalSteps) * 100) + '%';
    }

    function obmShowStep(n) {
        for (let i = 1; i <= obmTotalSteps; i++) {
            const el = document.getElementById('obmStep' + i);
            if (el) el.style.display = (i === n) ? '' : 'none';
        }
        obmCurrentStep = n;
        obmUpdateStepper();
        obmUpdateButtons();
        document.getElementById('obmBody').scrollTop = 0;
    }

    function obmUpdateButtons() {
        const back = document.getElementById('obmBtnBack');
        const next = document.getElementById('obmBtnNext');
        back.style.display = obmCurrentStep > 1 ? '' : 'none';

        if (obmCurrentStep === obmTotalSteps) {
            next.innerHTML = obmSubscriptionsEnabled
                ? '<i class="fas fa-credit-card me-1"></i> Confirmer et payer'
                : '<i class="fas fa-check me-1"></i> Terminer';
        } else if (obmCurrentStep === 1) {
            next.innerHTML = 'Commencer <i class="fas fa-arrow-right"></i>';
        } else {
            next.innerHTML = 'Continuer <i class="fas fa-arrow-right"></i>';
        }
    }

    // ---- NAVIGATION ----
    window.obmNext = function() {
        if (obmCurrentStep === 3 && obmSelectedCats.length === 0) {
            alert('Veuillez sélectionner au moins une catégorie.');
            return;
        }
        if (obmCurrentStep === obmTotalSteps) {
            obmSubmit();
            return;
        }

        // Save step progress in background
        obmSaveStepProgress(obmCurrentStep);

        if (obmCurrentStep === 5) {
            obmBuildRecap();
        }
        obmShowStep(obmCurrentStep + 1);
    };

    window.obmPrev = function() {
        if (obmCurrentStep > 1) obmShowStep(obmCurrentStep - 1);
    };

    // ---- SAVE STEP PROGRESS ----
    function obmSaveStepProgress(step) {
        const payload = { save_step: step };

        if (step >= 2) {
            payload.address = document.getElementById('obmAddress')?.value || '';
            payload.postal_code = document.getElementById('obmPostalCode')?.value || '';
            payload.city = document.getElementById('obmCity')?.value || '';
            payload.country = document.getElementById('obmCountry')?.value || '';
            payload.phone = document.getElementById('obmPhone')?.value || '';
            payload.intervention_radius = parseInt(document.getElementById('obmRadius')?.value) || 30;
        }
        if (step >= 3) {
            payload.categories = obmSelectedCats;
            payload.subcategories = obmSelectedSubs;
        }
        if (step >= 4) {
            payload.notifications_realtime = document.getElementById('obmNotifRealtime')?.checked ?? true;
            payload.notifications_email = document.getElementById('obmNotifEmail')?.checked ?? true;
            payload.notifications_sms = document.getElementById('obmNotifSms')?.checked ?? false;
            const phoneSms = document.getElementById('obmPhoneSms')?.value;
            if (phoneSms) payload.phone_sms = phoneSms;
        }

        fetch('{{ route("pro.onboarding.subscribe") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        }).catch(() => {}); // silent save
    }

    // ---- CLOSE MODAL ----
    window.obmRequestClose = function() {
        document.getElementById('obmCloseWarning').style.display = 'flex';
    };

    window.obmDismissCloseWarning = function() {
        document.getElementById('obmCloseWarning').style.display = 'none';
    };

    window.obmForceClose = function() {
        // Save current progress before closing
        fetch('{{ route("pro.onboarding.subscribe") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ skip: true, current_step: obmCurrentStep })
        }).then(() => {
            document.getElementById('obmOverlay').classList.remove('obm-visible');
            document.body.style.overflow = '';
        }).catch(() => {
            document.getElementById('obmOverlay').classList.remove('obm-visible');
            document.body.style.overflow = '';
        });
    };

    // ---- OPEN MODAL (can be called externally) ----
    window.openOnboardingModal = function(step) {
        step = step || 1;
        const overlay = document.getElementById('obmOverlay');
        if (overlay) {
            overlay.classList.add('obm-visible');
            document.body.style.overflow = 'hidden';
            obmShowStep(step);
        }
    };

    // ---- GEOLOCATION ----
    window.obmGeolocate = function() {
        const btn = document.getElementById('obmGeoBtn');
        const status = document.getElementById('obmGeoStatus');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Localisation...';
        status.style.display = '';
        status.style.color = '#6366f1';
        status.textContent = 'Recherche de votre position...';

        if (!navigator.geolocation) {
            status.style.color = '#ef4444';
            status.textContent = 'Géolocalisation non supportée.';
            btn.innerHTML = '<i class="fas fa-crosshairs"></i> Me géolocaliser';
            return;
        }

        navigator.geolocation.getCurrentPosition(function(pos) {
            obmGeoLat = pos.coords.latitude;
            obmGeoLng = pos.coords.longitude;
            fetch('https://nominatim.openstreetmap.org/reverse?lat=' + pos.coords.latitude + '&lon=' + pos.coords.longitude + '&format=json&addressdetails=1')
                .then(r => r.json())
                .then(data => {
                    if (data && data.address) {
                        const addr = data.address;
                        const city = addr.city || addr.town || addr.village || '';
                        const road = addr.road || '';
                        const num = addr.house_number || '';
                        const pc = addr.postcode || '';
                        document.getElementById('obmAddress').value = ((num ? num + ' ' : '') + road).trim().replace(/^,\s*/, '');
                        document.getElementById('obmPostalCode').value = pc;
                        document.getElementById('obmCity').value = city;
                        document.getElementById('obmCountry').value = addr.country || 'France';
                        status.style.color = '#10b981';
                        status.innerHTML = '<i class="fas fa-check-circle me-1"></i> ' + city;
                    }
                    btn.innerHTML = '<i class="fas fa-crosshairs"></i> Me géolocaliser';
                }).catch(() => {
                    status.style.color = '#ef4444';
                    status.textContent = 'Impossible de déterminer l\'adresse.';
                    btn.innerHTML = '<i class="fas fa-crosshairs"></i> Me géolocaliser';
                });
        }, function() {
            status.style.color = '#ef4444';
            status.textContent = 'Accès refusé.';
            btn.innerHTML = '<i class="fas fa-crosshairs"></i> Me géolocaliser';
        }, { timeout: 10000 });
    };

    // ---- CATEGORIES ----
    function obmRenderCategories() {
        const grid = document.getElementById('obmCatGrid');
        if (!grid) return;
        grid.innerHTML = '';
        Object.entries(obmCatsData).forEach(([name, data]) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'obm-cat-btn';
            btn.innerHTML = `
                <div class="obm-cat-check"><i class="fas fa-check"></i></div>
                <span class="obm-cat-icon">${data.icon}</span>
                <span class="obm-cat-name">${name}</span>
            `;
            btn.onclick = () => obmToggleCategory(name, btn);
            grid.appendChild(btn);
        });
    }

    function obmToggleCategory(name, btn) {
        const idx = obmSelectedCats.indexOf(name);
        if (idx > -1) {
            obmSelectedCats.splice(idx, 1);
            btn.classList.remove('selected');
        } else {
            obmSelectedCats.push(name);
            btn.classList.add('selected');
        }

        const info = document.getElementById('obmSelectedCatsInfo');
        const count = document.getElementById('obmCatCount');
        if (obmSelectedCats.length > 0) {
            info.style.display = '';
            count.textContent = obmSelectedCats.length;
        } else {
            info.style.display = 'none';
        }
        obmRenderSubcategories();
    }

    function obmRenderSubcategories() {
        const area = document.getElementById('obmSubArea');
        const list = document.getElementById('obmSubList');
        if (obmSelectedCats.length === 0) {
            area.style.display = 'none';
            return;
        }
        area.style.display = '';
        list.innerHTML = '';
        obmSelectedSubs = [];

        obmSelectedCats.forEach(catName => {
            const data = obmCatsData[catName];
            if (!data || !data.subcategories) return;

            const header = document.createElement('div');
            header.style.cssText = 'width: 100%; font-size: 0.74rem; font-weight: 700; color: #64748b; margin-top: 6px; margin-bottom: 3px;';
            header.innerHTML = data.icon + ' ' + catName;
            list.appendChild(header);

            data.subcategories.forEach(sub => {
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'obm-sub-chip';
                chip.textContent = sub;
                chip.onclick = () => {
                    const i = obmSelectedSubs.indexOf(sub);
                    if (i > -1) { obmSelectedSubs.splice(i, 1); chip.classList.remove('selected'); }
                    else { obmSelectedSubs.push(sub); chip.classList.add('selected'); }
                    const c = document.getElementById('obmSubCount');
                    const n = document.getElementById('obmSubCountNum');
                    if (obmSelectedSubs.length > 0) { c.style.display = ''; n.textContent = obmSelectedSubs.length; }
                    else { c.style.display = 'none'; }
                };
                list.appendChild(chip);
            });
        });
    }

    // ---- PLAN ----
    window.obmSelectPlan = function(plan) {
        obmSelectedPlan = plan;
        document.getElementById('obmPlanMonthly').classList.toggle('selected', plan === 'monthly');
        document.getElementById('obmPlanAnnual').classList.toggle('selected', plan === 'annual');
    };

    // ---- SKIP SUBSCRIPTION ----
    window.obmSkipSubscription = function() {
        if (obmSubscriptionsEnabled && !confirm('En passant l\'abonnement, certaines fonctionnalités premium (statistiques avancées, badge Pro Premium, position prioritaire) ne seront pas disponibles.\n\nÊtes-vous sûr de vouloir continuer sans abonnement ?')) {
            return;
        }

        const btn = document.getElementById('obmBtnNext');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Finalisation...';

        const payload = {
            skip_subscription: true,
            categories: obmSelectedCats,
            subcategories: obmSelectedSubs,
            intervention_radius: parseInt(document.getElementById('obmRadius')?.value) || 30,
            notifications_realtime: document.getElementById('obmNotifRealtime')?.checked ?? true,
            notifications_email: document.getElementById('obmNotifEmail')?.checked ?? true,
            notifications_sms: document.getElementById('obmNotifSms')?.checked ?? false,
            phone_sms: document.getElementById('obmPhoneSms')?.value || '',
            address: document.getElementById('obmAddress')?.value || '',
            postal_code: document.getElementById('obmPostalCode')?.value || '',
            city: document.getElementById('obmCity')?.value || '',
            country: document.getElementById('obmCountry')?.value || '',
            phone: document.getElementById('obmPhone')?.value || '',
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
            if (data.success) {
                obmShowSuccess(false);
            } else {
                btn.disabled = false;
                btn.innerHTML = 'Continuer <i class="fas fa-arrow-right"></i>';
                alert(data.message || 'Erreur, veuillez réessayer.');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = 'Continuer <i class="fas fa-arrow-right"></i>';
            alert('Erreur de connexion.');
        });
    };

    // ---- RECAP ----
    function obmBuildRecap() {
        const city = document.getElementById('obmCity')?.value || 'Non renseigné';
        const radius = document.getElementById('obmRadius')?.value || 30;
        const notifRT = document.getElementById('obmNotifRealtime')?.checked;
        const notifEmail = document.getElementById('obmNotifEmail')?.checked;

        document.getElementById('obmRecapZone').textContent = city;
        document.getElementById('obmRecapRadius').textContent = radius + ' km';
        document.getElementById('obmRecapCategories').textContent =
            obmSelectedCats.length > 0 ? obmSelectedCats.join(', ') : 'Non sélectionnées';

        let notifText = [];
        if (notifRT) notifText.push('Instantanées');
        if (notifEmail) notifText.push('Email');
        if (document.getElementById('obmNotifSms')?.checked) notifText.push('SMS');
        document.getElementById('obmRecapNotif').innerHTML = notifText.length > 0
            ? '<span style="color: #10b981;"><i class="fas fa-check-circle me-1"></i>' + notifText.join(', ') + '</span>'
            : '<span style="color: #94a3b8;">Aucune</span>';

        if (!obmSubscriptionsEnabled) {
            document.getElementById('obmRecapPlan').textContent = 'Aucun — accès lancement';
            document.getElementById('obmRecapTotal').textContent = '0,00 €';
        } else if (obmSelectedPlan === 'annual') {
            document.getElementById('obmRecapPlan').textContent = 'Annuel (85€/an)';
            document.getElementById('obmRecapTotal').textContent = '85,00 €';
        } else {
            document.getElementById('obmRecapPlan').textContent = 'Mensuel (9,99€/mois)';
            document.getElementById('obmRecapTotal').textContent = '9,99 €';
        }
    }

    // ---- SUBMIT ----
    function obmSubmit() {
        const termsCheck = document.getElementById('obmAcceptTerms');
        if (!termsCheck || !termsCheck.checked) {
            alert('Veuillez accepter les conditions générales d\'utilisation.');
            return;
        }

        const btn = document.getElementById('obmBtnNext');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Traitement...';

        const payload = {
            plan: obmSelectedPlan,
            skip_subscription: !obmSubscriptionsEnabled,
            categories: obmSelectedCats,
            subcategories: obmSelectedSubs,
            intervention_radius: parseInt(document.getElementById('obmRadius')?.value) || 30,
            notifications_realtime: document.getElementById('obmNotifRealtime')?.checked ?? true,
            notifications_email: document.getElementById('obmNotifEmail')?.checked ?? true,
            notifications_sms: document.getElementById('obmNotifSms')?.checked ?? false,
            phone_sms: document.getElementById('obmPhoneSms')?.value || '',
            address: document.getElementById('obmAddress')?.value || '',
            postal_code: document.getElementById('obmPostalCode')?.value || '',
            city: document.getElementById('obmCity')?.value || '',
            country: document.getElementById('obmCountry')?.value || '',
            phone: document.getElementById('obmPhone')?.value || '',
        };

        if (obmGeoLat && obmGeoLng) {
            payload.latitude = obmGeoLat;
            payload.longitude = obmGeoLng;
        }

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
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Redirection vers le paiement...';
                window.location.href = data.checkout_url;
            } else if (data.success && data.requires_payment && data.checkout_url) {
                // Redirect to Stripe Checkout for payment
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Redirection vers le paiement...';
                window.location.href = data.checkout_url;
            } else if (data.success) {
                obmShowSuccess(true, data);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-credit-card me-1"></i> Confirmer et payer';
                alert(data.message || 'Erreur lors de l\'activation. Veuillez réessayer.');
            }
        })
        .catch((err) => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-credit-card me-1"></i> Confirmer et payer';
            alert('Erreur de connexion. Veuillez réessayer.');
        });
    }

    // ---- SUCCESS SCREEN ----
    function obmShowSuccess(withSubscription, data) {
        const body = document.getElementById('obmBody');
        const footer = document.getElementById('obmFooter');
        const stepper = document.getElementById('obmStepper');

        if (footer) footer.style.display = 'none';
        if (stepper) stepper.style.display = 'none';
        document.getElementById('obmProgressFill').style.width = '100%';

        let subInfo = '';
        if (withSubscription && data && data.subscription) {
            subInfo = `
                <div style="background: rgba(16,185,129,0.06); border-radius: 12px; padding: 16px; max-width: 380px; margin: 0 auto 20px;">
                    <div style="font-weight: 700; color: #059669; margin-bottom: 3px;">${data.subscription.plan}</div>
                    <div style="font-size: 0.8rem; color: #64748b;">Valide jusqu'au ${data.subscription.ends_at}</div>
                </div>
            `;
        } else {
            subInfo = `
                <div style="background: rgba(245,158,11,0.06); border-radius: 12px; padding: 16px; max-width: 380px; margin: 0 auto 20px;">
                    <div style="font-weight: 700; color: #d97706; margin-bottom: 3px;">Mode gratuit</div>
                    <div style="font-size: 0.8rem; color: #64748b;">Vous pourrez souscrire un abonnement à tout moment.</div>
                </div>
            `;
        }

        body.innerHTML = `
            <div class="obm-success">
                <div class="obm-success-icon">🚀</div>
                <h2>Félicitations !</h2>
                <p>Votre espace professionnel est configuré. Vous pouvez maintenant recevoir des demandes de clients et gérer votre activité.</p>
                ${subInfo}
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <a href="{{ route('pro.dashboard') }}" class="obm-btn-next" style="text-decoration: none; display: inline-flex;">
                        <i class="fas fa-th-large me-1"></i> Mon espace pro
                    </a>
                    <button onclick="document.getElementById('obmOverlay').classList.remove('obm-visible'); document.body.style.overflow='';"
                        class="obm-btn-skip-sub" style="margin-top: 0;">
                        <i class="fas fa-home me-1"></i> Rester sur le feed
                    </button>
                </div>
            </div>
        `;
    }

    // ---- INIT ----
    document.addEventListener('DOMContentLoaded', function() {
        obmRenderCategories();

        // Resume at saved step (if > 0, start at that step, else start at 1)
        const startAt = obmStartStep > 0 && obmStartStep < obmTotalSteps ? obmStartStep : 1;
        obmShowStep(startAt);

        // Auto-show modal if onboarding is required
        @if($showOnboardingModal ?? false)
        document.getElementById('obmOverlay').classList.add('obm-visible');
        document.body.style.overflow = 'hidden';
        @endif

        // Close on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const overlay = document.getElementById('obmOverlay');
                if (overlay && overlay.classList.contains('obm-visible')) {
                    obmRequestClose();
                }
            }
        });

        // Prevent clicks outside modal from doing anything (overlay click = close warning)
        document.getElementById('obmOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                obmRequestClose();
            }
        });
    });

})();
</script>
@endif

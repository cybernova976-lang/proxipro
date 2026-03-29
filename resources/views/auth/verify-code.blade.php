@extends('layouts.auth')

@section('title', 'Vérification e-mail')

@section('content')
<style>
    .vc-page {
        flex: 1; display: flex; align-items: center; justify-content: center;
        padding: 24px 16px;
        background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 30%, #f0f4ff 60%, #ede9fe 100%);
        position: relative; overflow: hidden;
    }
    .vc-page::before {
        content: ''; position: absolute; top: -120px; right: -80px;
        width: 400px; height: 400px;
        background: radial-gradient(circle, rgba(99,102,241,0.12) 0%, transparent 70%);
        border-radius: 50%;
    }
    .vc-page::after {
        content: ''; position: absolute; bottom: -80px; left: -60px;
        width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(168,85,247,0.08) 0%, transparent 70%);
        border-radius: 50%;
    }

    .vc-wrapper {
        width: 100%; max-width: 440px; position: relative; z-index: 2;
        animation: vcSlideUp 0.5s cubic-bezier(0.16,1,0.3,1);
    }
    @keyframes vcSlideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ── Steps indicator ── */
    .vc-steps {
        display: flex; align-items: center; justify-content: center;
        gap: 8px; margin-bottom: 32px;
    }
    .vc-step {
        width: 10px; height: 10px; border-radius: 50%;
        background: #c7d2fe; transition: all 0.3s;
    }
    .vc-step.done { background: #4f46e5; }
    .vc-step.active {
        width: 32px; border-radius: 10px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
    }
    .vc-step-line {
        width: 24px; height: 2px; background: #e0e7ff; border-radius: 2px;
    }
    .vc-step-line.done { background: #a5b4fc; }

    /* ── Card ── */
    .vc-card {
        background: white; border-radius: 28px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04), 0 20px 50px -12px rgba(79,70,229,0.15);
        padding: 40px 36px 36px; position: relative; overflow: hidden;
    }
    .vc-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(90deg, #4f46e5, #7c3aed, #a78bfa);
    }

    /* ── Animated envelope icon ── */
    .vc-icon-wrap {
        width: 80px; height: 80px; margin: 0 auto 24px;
        background: linear-gradient(135deg, #eef2ff, #e0e7ff);
        border-radius: 24px; display: flex; align-items: center; justify-content: center;
        position: relative;
        animation: vcIconPulse 3s ease-in-out infinite;
    }
    @keyframes vcIconPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(79,70,229,0.15); }
        50% { box-shadow: 0 0 0 12px rgba(79,70,229,0); }
    }
    .vc-icon-wrap svg { width: 36px; height: 36px; color: #4f46e5; }
    .vc-icon-badge {
        position: absolute; top: -4px; right: -4px;
        width: 24px; height: 24px; border-radius: 50%;
        background: linear-gradient(135deg, #10b981, #059669);
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 2px 8px rgba(16,185,129,0.3);
        animation: vcBadgeBounce 0.6s 0.4s cubic-bezier(0.34,1.56,0.64,1) both;
    }
    @keyframes vcBadgeBounce {
        from { transform: scale(0); } to { transform: scale(1); }
    }
    .vc-icon-badge svg { width: 14px; height: 14px; color: white; }

    /* ── Title ── */
    .vc-title {
        font-size: 1.5rem; font-weight: 800; color: #1e293b;
        text-align: center; margin-bottom: 6px; letter-spacing: -0.3px;
    }
    .vc-subtitle {
        font-size: 0.92rem; color: #94a3b8; text-align: center;
        line-height: 1.5; margin-bottom: 4px;
    }
    .vc-email {
        text-align: center; margin-bottom: 28px;
    }
    .vc-email span {
        display: inline-flex; align-items: center; gap: 6px;
        background: #f1f5f9; padding: 6px 16px; border-radius: 50px;
        font-size: 0.88rem; font-weight: 600; color: #334155;
    }
    .vc-email span svg { width: 14px; height: 14px; color: #4f46e5; }

    /* ── Alerts ── */
    .vc-alert {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 12px 16px; border-radius: 14px; margin-bottom: 20px;
        font-size: 0.87rem; font-weight: 500;
        animation: vcAlertIn 0.3s ease-out;
    }
    @keyframes vcAlertIn { from { opacity: 0; transform: translateY(-6px); } }
    .vc-alert svg { width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px; }
    .vc-alert-error {
        background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
    }
    .vc-alert-success {
        background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a;
    }

    /* ── Code inputs ── */
    .vc-code-label {
        display: block; text-align: center; font-size: 0.82rem;
        font-weight: 600; color: #64748b; margin-bottom: 14px;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    .vc-code-row {
        display: flex; justify-content: center; align-items: center; gap: 8px;
        margin-bottom: 28px;
    }
    .vc-digit {
        width: 52px; height: 62px; border: 2px solid #e2e8f0; border-radius: 16px;
        text-align: center; font-size: 1.5rem; font-weight: 800; color: #1e293b;
        background: #f8fafc; caret-color: #4f46e5;
        transition: all 0.2s cubic-bezier(0.4,0,0.2,1);
        outline: none; font-family: inherit;
    }
    .vc-digit:focus {
        border-color: #4f46e5; background: white;
        box-shadow: 0 0 0 4px rgba(79,70,229,0.1), 0 4px 12px rgba(79,70,229,0.08);
        transform: translateY(-2px);
    }
    .vc-digit.filled {
        border-color: #a5b4fc; background: #eef2ff;
    }
    .vc-digit.success {
        border-color: #34d399; background: #ecfdf5;
        animation: vcDigitPop 0.3s cubic-bezier(0.34,1.56,0.64,1);
    }
    @keyframes vcDigitPop {
        0% { transform: scale(1); } 50% { transform: scale(1.08); } 100% { transform: scale(1); }
    }
    .vc-code-sep {
        width: 16px; height: 3px; background: #cbd5e1; border-radius: 3px;
        flex-shrink: 0;
    }

    /* ── Submit button ── */
    .vc-submit {
        width: 100%; padding: 15px 20px; border: none; border-radius: 16px;
        font-size: 0.95rem; font-weight: 700; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
        font-family: inherit; position: relative; overflow: hidden;
    }
    .vc-submit:disabled {
        background: #e2e8f0; color: #94a3b8; cursor: not-allowed;
        box-shadow: none;
    }
    .vc-submit:not(:disabled) {
        background: linear-gradient(135deg, #4f46e5, #6d28d9);
        color: white;
        box-shadow: 0 8px 24px -4px rgba(79,70,229,0.4);
    }
    .vc-submit:not(:disabled):hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 32px -4px rgba(79,70,229,0.5);
    }
    .vc-submit:not(:disabled):active { transform: translateY(0); }
    .vc-submit svg { width: 18px; height: 18px; }

    /* ── Timer section ── */
    .vc-timer-section {
        margin-top: 24px; text-align: center;
        padding-top: 20px; border-top: 1px solid #f1f5f9;
    }
    .vc-timer {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 8px 18px; border-radius: 50px;
        background: #f8fafc; font-size: 0.82rem; color: #64748b;
        margin-bottom: 14px;
    }
    .vc-timer svg { width: 14px; height: 14px; }
    .vc-timer-value { font-weight: 700; color: #334155; font-variant-numeric: tabular-nums; }
    .vc-timer.urgent { background: #fef2f2; color: #dc2626; }
    .vc-timer.urgent .vc-timer-value { color: #dc2626; }
    .vc-timer.expired { background: #fef2f2; }

    .vc-resend-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 10px 24px; border-radius: 12px; border: 2px solid #e0e7ff;
        background: transparent; font-size: 0.88rem; font-weight: 600;
        color: #4f46e5; cursor: pointer; font-family: inherit;
        transition: all 0.2s;
    }
    .vc-resend-btn:disabled {
        color: #94a3b8; border-color: #f1f5f9; cursor: not-allowed;
    }
    .vc-resend-btn:not(:disabled):hover {
        background: #eef2ff; border-color: #a5b4fc;
    }
    .vc-resend-btn svg { width: 15px; height: 15px; }
    .vc-resend-cooldown {
        font-size: 0.78rem; color: #94a3b8; margin-top: 6px;
    }

    /* ── Footer link ── */
    .vc-footer {
        text-align: center; margin-top: 24px;
    }
    .vc-footer a {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 0.88rem; font-weight: 600; color: #64748b;
        text-decoration: none; padding: 8px 16px; border-radius: 10px;
        transition: all 0.2s;
    }
    .vc-footer a:hover { color: #4f46e5; background: rgba(255,255,255,0.6); }
    .vc-footer a svg { width: 16px; height: 16px; }

    @media (max-width: 480px) {
        .vc-card { padding: 32px 20px 28px; border-radius: 24px; }
        .vc-digit { width: 44px; height: 54px; font-size: 1.25rem; border-radius: 12px; }
        .vc-code-row { gap: 6px; }
        .vc-title { font-size: 1.3rem; }
    }
</style>

<div class="vc-page">
    <div class="vc-wrapper">

        {{-- ── Steps: Inscription > Vérification > Terminé ── --}}
        <div class="vc-steps">
            <div class="vc-step done"></div>
            <div class="vc-step-line done"></div>
            <div class="vc-step active"></div>
            <div class="vc-step-line"></div>
            <div class="vc-step"></div>
        </div>

        {{-- ── Main card ── --}}
        <div class="vc-card">

            {{-- Animated envelope icon --}}
            <div class="vc-icon-wrap">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
                <div class="vc-icon-badge">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </div>
            </div>

            <h1 class="vc-title">Vérifiez votre e-mail</h1>
            <p class="vc-subtitle">Un code à 6 chiffres a été envoyé à</p>
            <div class="vc-email">
                <span>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75"/></svg>
                    {{ $email }}
                </span>
            </div>

            {{-- ── Alerts ── --}}
            @if (session('error'))
            <div class="vc-alert vc-alert-error">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
            @endif

            @if (session('success'))
            <div class="vc-alert vc-alert-success">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            @endif

            @if ($errors->any())
            <div class="vc-alert vc-alert-error">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                <span>@foreach ($errors->all() as $error){{ $error }}@endforeach</span>
            </div>
            @endif

            {{-- ── Code form ── --}}
            <form method="POST" action="{{ route('verification.code.verify') }}" id="verifyForm">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <input type="hidden" name="code" id="verification-code">

                <label class="vc-code-label">Entrez votre code de vérification</label>
                <div class="vc-code-row" id="code-inputs">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="vc-digit" data-index="0" autocomplete="off">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="vc-digit" data-index="1" autocomplete="off">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="vc-digit" data-index="2" autocomplete="off">
                    <div class="vc-code-sep"></div>
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="vc-digit" data-index="3" autocomplete="off">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="vc-digit" data-index="4" autocomplete="off">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="vc-digit" data-index="5" autocomplete="off">
                </div>

                <button type="submit" id="verifyBtn" class="vc-submit" disabled>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75"/>
                    </svg>
                    Vérifier mon compte
                </button>
            </form>

            {{-- ── Timer & Resend ── --}}
            <div class="vc-timer-section">
                <div class="vc-timer" id="timer-wrap">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span id="timer-text">Expire dans <span class="vc-timer-value" id="countdown">15:00</span></span>
                </div>

                <div>
                    <form method="POST" action="{{ route('verification.code.resend') }}" id="resendForm" style="display:inline">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        <button type="submit" id="resendBtn" class="vc-resend-btn" disabled>
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/>
                            </svg>
                            Renvoyer le code
                        </button>
                    </form>
                    <p class="vc-resend-cooldown" id="resend-timer">Renvoi possible dans <span id="resend-countdown">60s</span></p>
                </div>
            </div>
        </div>

        {{-- ── Footer link ── --}}
        <div class="vc-footer">
            <a href="{{ route('register') }}">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                Retour à l'inscription
            </a>
        </div>
    </div>
</div>

<script>
    // ── Code input handling ──
    const digits = document.querySelectorAll('.vc-digit');
    const codeInput = document.getElementById('verification-code');
    const verifyBtn = document.getElementById('verifyBtn');

    function updateHiddenCode() {
        let code = '';
        digits.forEach(d => code += d.value);
        codeInput.value = code;
        verifyBtn.disabled = code.length !== 6;

        digits.forEach((d, i) => {
            d.classList.remove('filled', 'success');
            if (d.value) {
                d.classList.add(code.length === 6 ? 'success' : 'filled');
            }
        });
    }

    digits.forEach((input, idx) => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value && idx < 5) digits[idx + 1].focus();
            updateHiddenCode();
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && idx > 0) {
                digits[idx - 1].focus();
                digits[idx - 1].value = '';
                updateHiddenCode();
            }
        });

        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 6);
            if (paste) {
                for (let i = 0; i < paste.length && i < 6; i++) digits[i].value = paste[i];
                digits[Math.min(paste.length, 5)].focus();
                updateHiddenCode();
            }
        });

        input.addEventListener('focus', function() { this.select(); });
    });

    digits[0].focus();

    // ── Countdown timer (15 min) ──
    let timeLeft = 15 * 60;
    const countdownEl = document.getElementById('countdown');
    const timerWrap = document.getElementById('timer-wrap');
    const timerText = document.getElementById('timer-text');

    const timerInterval = setInterval(() => {
        timeLeft--;
        const min = Math.floor(timeLeft / 60);
        const sec = timeLeft % 60;
        countdownEl.textContent = `${min}:${sec.toString().padStart(2, '0')}`;

        if (timeLeft <= 60) timerWrap.classList.add('urgent');

        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            timerWrap.classList.add('expired');
            timerText.innerHTML = '<span style="font-weight:700;color:#dc2626">Code expiré — renvoyez un nouveau code</span>';
            verifyBtn.disabled = true;
        }
    }, 1000);

    // ── Resend cooldown (60s) ──
    const resendBtn = document.getElementById('resendBtn');
    const resendTimerEl = document.getElementById('resend-timer');
    const resendCountdown = document.getElementById('resend-countdown');
    let resendCooldown = 60;

    const resendInterval = setInterval(() => {
        resendCooldown--;
        resendCountdown.textContent = `${resendCooldown}s`;
        if (resendCooldown <= 0) {
            clearInterval(resendInterval);
            resendBtn.disabled = false;
            resendTimerEl.style.display = 'none';
        }
    }, 1000);
</script>
@endsection

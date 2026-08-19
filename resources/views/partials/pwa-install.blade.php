<style>
    .pwa-install-button {
        position: fixed;
        right: 1rem;
        bottom: calc(1rem + env(safe-area-inset-bottom));
        z-index: 10020;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .6rem;
        min-height: 48px;
        padding: .75rem 1rem;
        border: 0;
        border-radius: 999px;
        color: #fff;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        box-shadow: 0 12px 30px rgba(79, 70, 229, .32);
        font: 700 .9rem/1.2 Inter, ui-sans-serif, system-ui, -apple-system, sans-serif;
        cursor: pointer;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .pwa-install-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 36px rgba(79, 70, 229, .4);
    }

    .pwa-install-button:focus-visible,
    .pwa-ios-close:focus-visible {
        outline: 3px solid rgba(99, 102, 241, .35);
        outline-offset: 3px;
    }

    .pwa-install-button[hidden],
    .pwa-ios-dialog[hidden] {
        display: none !important;
    }

    .pwa-install-button svg {
        width: 20px;
        height: 20px;
        flex: 0 0 auto;
    }

    .pwa-ios-dialog {
        position: fixed;
        inset: 0;
        z-index: 10030;
        display: grid;
        place-items: end center;
        padding: 1rem;
        padding-bottom: calc(1rem + env(safe-area-inset-bottom));
        background: rgba(15, 23, 42, .52);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }

    .pwa-ios-dialog-card {
        width: min(100%, 430px);
        padding: 1.25rem;
        border-radius: 22px;
        color: #1e293b;
        background: #fff;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .28);
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, sans-serif;
    }

    .pwa-ios-dialog-card h2 {
        margin: 0 0 .65rem;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 800;
    }

    .pwa-ios-dialog-card p {
        margin: 0 0 .85rem;
        color: #475569;
        line-height: 1.55;
    }

    .pwa-ios-steps {
        margin: 0 0 1rem;
        padding-left: 1.3rem;
        color: #334155;
        line-height: 1.55;
    }

    .pwa-ios-close {
        width: 100%;
        min-height: 44px;
        border: 0;
        border-radius: 12px;
        color: #fff;
        background: #4f46e5;
        font: 700 .95rem/1 Inter, ui-sans-serif, system-ui, -apple-system, sans-serif;
        cursor: pointer;
    }

    @media (max-width: 480px) {
        .pwa-install-button {
            right: .75rem;
            bottom: calc(.75rem + env(safe-area-inset-bottom));
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .pwa-install-button { transition: none; }
    }
</style>

<button
    type="button"
    id="pwaInstallButton"
    class="pwa-install-button"
    aria-label="Installer l’application Prokejem"
    hidden
>
    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M12 3v11m0 0 4-4m-4 4-4-4M5 17v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span>Installer Prokejem</span>
</button>

<div
    id="pwaIosDialog"
    class="pwa-ios-dialog"
    role="dialog"
    aria-modal="true"
    aria-labelledby="pwaIosDialogTitle"
    hidden
>
    <div class="pwa-ios-dialog-card">
        <h2 id="pwaIosDialogTitle">Installer Prokejem sur l’iPhone</h2>
        <p>L’installation se fait depuis le menu de partage du navigateur.</p>
        <ol class="pwa-ios-steps">
            <li>Touchez l’icône <strong>Partager</strong>.</li>
            <li>Choisissez <strong>Sur l’écran d’accueil</strong>.</li>
            <li>Confirmez avec <strong>Ajouter</strong>.</li>
        </ol>
        <button type="button" id="pwaIosDialogClose" class="pwa-ios-close">J’ai compris</button>
    </div>
</div>

<script>
(() => {
    'use strict';

    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/service-worker.js', { scope: '/' }).catch(() => {});
        });
    }

    const installButton = document.getElementById('pwaInstallButton');
    const iosDialog = document.getElementById('pwaIosDialog');
    const iosDialogClose = document.getElementById('pwaIosDialogClose');
    const installSurfaceAllowed = @json(!request()->routeIs('messages.*'));

    if (!installButton || !iosDialog || !iosDialogClose) return;

    const standalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
    const iosDevice = /iphone|ipad|ipod/i.test(window.navigator.userAgent)
        || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);

    if (standalone || !installSurfaceAllowed) return;

    let deferredInstallPrompt = null;

    const showInstallButton = () => {
        installButton.hidden = false;
        document.documentElement.classList.add('pwa-install-available');
    };

    const hideInstallButton = () => {
        installButton.hidden = true;
        document.documentElement.classList.remove('pwa-install-available');
    };

    const hideIosDialog = () => {
        iosDialog.hidden = true;
        document.body.style.overflow = '';
        installButton.focus();
    };

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredInstallPrompt = event;
        showInstallButton();
    });

    if (iosDevice) showInstallButton();

    installButton.addEventListener('click', async () => {
        if (deferredInstallPrompt) {
            deferredInstallPrompt.prompt();
            await deferredInstallPrompt.userChoice;
            deferredInstallPrompt = null;
            hideInstallButton();
            return;
        }

        if (iosDevice) {
            iosDialog.hidden = false;
            document.body.style.overflow = 'hidden';
            iosDialogClose.focus();
        }
    });

    iosDialogClose.addEventListener('click', hideIosDialog);
    iosDialog.addEventListener('click', (event) => {
        if (event.target === iosDialog) hideIosDialog();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !iosDialog.hidden) hideIosDialog();
    });

    window.addEventListener('appinstalled', () => {
        deferredInstallPrompt = null;
        hideInstallButton();
        iosDialog.hidden = true;
    });
})();
</script>

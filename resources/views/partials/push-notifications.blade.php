@auth
<style>
    .push-notification-prompt {
        position: fixed;
        right: 1rem;
        bottom: calc(1rem + env(safe-area-inset-bottom));
        z-index: 10010;
        width: min(390px, calc(100vw - 2rem));
        padding: 1rem;
        border: 1px solid rgba(79, 70, 229, .2);
        border-radius: 18px;
        color: #1e293b;
        background: rgba(255, 255, 255, .97);
        box-shadow: 0 18px 48px rgba(15, 23, 42, .22);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, sans-serif;
    }

    .push-notification-prompt[hidden] { display: none !important; }
    .push-notification-prompt-title { margin: 0 0 .35rem; font-size: 1rem; font-weight: 800; }
    .push-notification-prompt-text { margin: 0 0 .85rem; color: #475569; font-size: .88rem; line-height: 1.45; }
    .push-notification-prompt-actions { display: flex; gap: .6rem; }
    .push-notification-prompt-actions button {
        min-height: 42px;
        padding: .6rem .85rem;
        border-radius: 11px;
        font: 700 .86rem/1 Inter, ui-sans-serif, system-ui, -apple-system, sans-serif;
        cursor: pointer;
    }
    .push-notification-accept { border: 0; color: #fff; background: #4f46e5; }
    .push-notification-dismiss { border: 1px solid #cbd5e1; color: #475569; background: #fff; }

    @media (max-width: 480px) {
        .push-notification-prompt {
            right: .75rem;
            bottom: calc(.75rem + env(safe-area-inset-bottom));
            width: calc(100vw - 1.5rem);
        }
    }
</style>

@if(! request()->routeIs('messages.*'))
<aside id="pushNotificationPrompt" class="push-notification-prompt" aria-labelledby="pushNotificationPromptTitle" hidden>
    <h2 id="pushNotificationPromptTitle" class="push-notification-prompt-title">Ne manquez aucune réponse</h2>
    <p class="push-notification-prompt-text">
        Activez les notifications pour vos messages, propositions et nouvelles demandes.
    </p>
    <div class="push-notification-prompt-actions">
        <button type="button" class="push-notification-accept" data-push-enable>Activer</button>
        <button type="button" class="push-notification-dismiss" data-push-dismiss>Plus tard</button>
    </div>
</aside>
@endif

<script>
(() => {
    'use strict';

    const publicKey = @json(config('webpush.vapid.public_key'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
    const endpoints = {
        store: @json(route('push-subscriptions.store')),
        destroy: @json(route('push-subscriptions.destroy')),
        test: @json(route('push-subscriptions.test')),
    };
    const prompt = document.getElementById('pushNotificationPrompt');
    const dismissStorageKey = 'prokejem_push_prompt_dismissed_until';

    const supportsPush = 'serviceWorker' in navigator
        && 'PushManager' in window
        && 'Notification' in window
        && window.isSecureContext
        && Boolean(publicKey);

    const controls = () => Array.from(document.querySelectorAll('[data-push-notification-controls]'));

    const base64UrlToUint8Array = (value) => {
        const padding = '='.repeat((4 - value.length % 4) % 4);
        const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = window.atob(base64);
        return Uint8Array.from([...raw].map((character) => character.charCodeAt(0)));
    };

    const contentEncoding = () => {
        const supported = window.PushManager?.supportedContentEncodings || [];
        return supported.includes('aes128gcm') ? 'aes128gcm' : (supported[0] || 'aes128gcm');
    };

    const request = async (url, method, payload = {}) => {
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'Une erreur est survenue.');
        return data;
    };

    const subscriptionPayload = (subscription) => {
        const serialized = subscription.toJSON();
        return {
            endpoint: subscription.endpoint,
            keys: serialized.keys || {},
            content_encoding: contentEncoding(),
        };
    };

    const setState = (state, message) => {
        controls().forEach((root) => {
            const status = root.querySelector('[data-push-status]');
            const enable = root.querySelector('[data-push-enable]');
            const disable = root.querySelector('[data-push-disable]');
            const test = root.querySelector('[data-push-test]');

            if (status) {
                status.textContent = message;
                status.className = `mb-0 small ${state === 'active' ? 'text-success' : state === 'error' ? 'text-danger' : 'text-muted'}`;
            }
            if (enable) enable.hidden = !['inactive', 'error'].includes(state);
            if (disable) disable.hidden = state !== 'active';
            if (test) test.hidden = state !== 'active';
        });

        if (prompt) prompt.hidden = true;
    };

    const maybeShowPrompt = () => {
        if (!prompt || Notification.permission === 'denied') return;
        const dismissedUntil = Number(localStorage.getItem(dismissStorageKey) || 0);
        if (dismissedUntil > Date.now()) return;
        window.setTimeout(() => { prompt.hidden = false; }, 1200);
    };

    const registration = async () => {
        const existing = await navigator.serviceWorker.getRegistration('/');
        if (!existing) await navigator.serviceWorker.register('/service-worker.js', { scope: '/' });
        return navigator.serviceWorker.ready;
    };

    const activate = async () => {
        setState('loading', 'Activation en cours…');

        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                setState('error', permission === 'denied'
                    ? 'Autorisation bloquée dans les réglages du navigateur.'
                    : 'Autorisation non accordée.');
                return;
            }

            const worker = await registration();
            let subscription = await worker.pushManager.getSubscription();
            if (!subscription) {
                subscription = await worker.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: base64UrlToUint8Array(publicKey),
                });
            }

            await request(endpoints.store, 'POST', subscriptionPayload(subscription));
            localStorage.removeItem(dismissStorageKey);
            setState('active', 'Activées sur cet appareil.');

            await worker.showNotification('Notifications Prokejem activées', {
                body: 'Vous recevrez ici vos nouveaux messages, propositions et demandes.',
                icon: '/pwa/icon-192.png',
                badge: '/pwa/icon-192.png',
                tag: 'prokejem-push-enabled',
                data: { url: window.location.href },
            });
        } catch (error) {
            setState('error', error.message || 'Impossible d’activer les notifications.');
        }
    };

    const deactivate = async () => {
        setState('loading', 'Désactivation en cours…');

        try {
            const worker = await registration();
            const subscription = await worker.pushManager.getSubscription();
            if (subscription) {
                await request(endpoints.destroy, 'DELETE', { endpoint: subscription.endpoint });
                await subscription.unsubscribe();
            }
            setState('inactive', 'Désactivées sur cet appareil.');
        } catch (error) {
            setState('error', error.message || 'Impossible de désactiver les notifications.');
        }
    };

    const sendTest = async () => {
        try {
            const data = await request(endpoints.test, 'POST');
            setState('active', data.message || 'Notification de test envoyée.');
        } catch (error) {
            setState('error', error.message || 'Le test a échoué.');
        }
    };

    document.addEventListener('click', (event) => {
        const enable = event.target.closest('[data-push-enable]');
        const disable = event.target.closest('[data-push-disable]');
        const test = event.target.closest('[data-push-test]');
        const dismiss = event.target.closest('[data-push-dismiss]');

        if (enable) { event.preventDefault(); activate(); }
        if (disable) { event.preventDefault(); deactivate(); }
        if (test) { event.preventDefault(); sendTest(); }
        if (dismiss) {
            event.preventDefault();
            localStorage.setItem(dismissStorageKey, String(Date.now() + 7 * 24 * 60 * 60 * 1000));
            if (prompt) prompt.hidden = true;
        }
    });

    const initialize = async () => {
        if (!supportsPush) {
            setState('unsupported', 'Non disponible sur ce navigateur ou dans cette configuration.');
            return;
        }
        if (Notification.permission === 'denied') {
            setState('error', 'Notifications bloquées dans les réglages du navigateur.');
            return;
        }

        try {
            const worker = await registration();
            const subscription = await worker.pushManager.getSubscription();
            if (subscription) {
                await request(endpoints.store, 'POST', subscriptionPayload(subscription));
                setState('active', 'Activées sur cet appareil.');
                return;
            }

            setState('inactive', 'Non activées sur cet appareil.');
            maybeShowPrompt();
        } catch (error) {
            setState('error', 'Impossible de vérifier les notifications pour le moment.');
        }
    };

    initialize();
})();
</script>
@endauth

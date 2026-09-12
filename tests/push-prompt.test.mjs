import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
import { test } from 'node:test';

const source = readFileSync(new URL('../resources/views/partials/push-notifications.blade.php', import.meta.url), 'utf8');
const script = source.match(/<script>([\s\S]*?)<\/script>/)[1]
    .replace(/@json\([^\n]*\)/g, '"test-value"');
const settle = async () => { for (let i = 0; i < 25; i++) await Promise.resolve(); };

function setup({ blockedStorage = false } = {}) {
    const feedback = {};
    const button = {};
    const prompt = { hidden: true, querySelector: s => s === '[data-push-feedback]' ? feedback : button };
    let click, timer, calls = 0, fail = true;
    const worker = { pushManager: {
        getSubscription: async () => null,
        subscribe: async () => ({ endpoint: 'https://example.test/push', toJSON: () => ({ keys: {} }) }),
    }, showNotification: async () => { throw new Error('Local confirmation unavailable'); } };
    const window = { isSecureContext: true, PushManager: {}, Notification: {},
        setTimeout: fn => { timer = fn; return 1; }, clearTimeout: () => { timer = null; },
        atob: () => 'abc' };
    const Notification = { permission: 'default', requestPermission: async () => { calls++; return 'granted'; } };
    vm.runInNewContext(script, {
        window, Notification, Uint8Array,
        document: { getElementById: () => prompt, querySelector: () => null, querySelectorAll: () => [],
            addEventListener: (_, fn) => { click = fn; } },
        navigator: { serviceWorker: { getRegistration: async () => worker, ready: Promise.resolve(worker) } },
        localStorage: { getItem: () => { if (blockedStorage) throw Error('blocked'); return null; },
            setItem: () => { if (blockedStorage) throw Error('blocked'); },
            removeItem: () => { if (blockedStorage) throw Error('blocked'); } },
        fetch: async () => ({ ok: !fail, json: async () => ({ message: 'Réseau indisponible' }) }),
    });
    return { prompt, feedback, button, Notification,
        show: () => timer?.(), succeed: () => { fail = false; }, calls: () => calls,
        click: selector => click({ preventDefault() {}, target: { closest: s => s === selector ? {} : null } }),
    };
}

test('activation failure stays visible, retry succeeds and duplicate clicks are ignored', async () => {
    const ui = setup(); await settle(); ui.show();
    assert.equal(ui.prompt.hidden, false);
    ui.click('[data-push-enable]'); ui.click('[data-push-enable]'); await settle();
    assert.equal(ui.calls(), 1);
    assert.equal(ui.prompt.hidden, false);
    assert.equal(ui.feedback.textContent, 'Réseau indisponible');
    assert.equal(ui.button.textContent, 'Réessayer');
    ui.succeed(); ui.click('[data-push-enable]'); await settle();
    assert.equal(ui.prompt.hidden, true);
});

test('blocked storage does not break prompt or dismissal', async () => {
    const ui = setup({ blockedStorage: true }); await settle(); ui.show();
    assert.equal(ui.prompt.hidden, false);
    ui.click('[data-push-dismiss]');
    assert.equal(ui.prompt.hidden, true);
});

test('denied permission explains browser settings without repeatedly requesting permission', async () => {
    const ui = setup(); await settle(); ui.show(); ui.Notification.permission = 'denied';
    ui.Notification.requestPermission = async () => 'denied';
    ui.click('[data-push-enable]'); await settle();
    assert.match(ui.feedback.textContent, /réglages du navigateur/);
    assert.equal(ui.prompt.hidden, false);
    assert.equal(ui.button.disabled, true);
});

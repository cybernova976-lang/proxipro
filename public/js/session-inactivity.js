/* One hour of human inactivity, shared by all tabs through the server session. */
(function () {
  'use strict';
  const script = document.querySelector('script[data-session-inactivity]');
  if (!script) return;
  let deadline = Date.now() + Number(script.dataset.remaining || 0) * 1000;
  let pendingInteraction = null;
  let lastSent = 0;
  let busy = false;
  let stopped = false;

  function leave() {
    if (stopped) return;
    stopped = true;
    // Also conceal private content when returning from the browser's back cache.
    document.documentElement.style.visibility = 'hidden';
    window.location.replace(script.dataset.loginUrl);
  }

  async function synchronize() {
    if (busy || stopped) return;
    const started = Date.now();
    if (started >= deadline) document.documentElement.style.visibility = 'hidden';
    const interaction = pendingInteraction;
    const canReport = interaction !== null && started < deadline && started - lastSent >= 15000;
    if (!navigator.onLine) {
      if (started >= deadline) leave();
      return;
    }
    busy = true;
    if (canReport) lastSent = started;
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 10000);
    try {
      const response = await fetch(script.dataset.activityUrl, {
        method: canReport ? 'POST' : 'GET', credentials: 'same-origin', cache: 'no-store',
        keepalive: canReport,
        signal: controller.signal,
        headers: { Accept: 'application/json', 'Content-Type': 'application/json',
          'X-CSRF-TOKEN': script.dataset.csrf || document.querySelector('meta[name="csrf-token"]')?.content || '' },
        ...(canReport ? {body: JSON.stringify({age_ms: Math.max(0, started - interaction)})} : {})
      });
      if ([401, 403, 419].includes(response.status)) { leave(); return; }
      if (!response.ok) throw new Error('Session check unavailable');
      const data = await response.json();
      if (!Number.isFinite(data.remaining_seconds) || data.remaining_seconds <= 0) { leave(); return; }
      deadline = started + data.remaining_seconds * 1000;
      document.documentElement.style.visibility = '';
      if (canReport) {
        if (pendingInteraction === interaction) pendingInteraction = null;
      }
    } catch (_) {
      if (Date.now() >= deadline) leave();
    } finally {
      clearTimeout(timer);
      busy = false;
    }
  }

  function interact(event) {
    if (!event.isTrusted || document.hidden || stopped) return;
    pendingInteraction = Date.now();
    // Never revive an expired session with the first click after a long absence.
    if (Date.now() >= deadline) { synchronize(); return; }
    if (Date.now() - lastSent >= 15000) synchronize();
  }

  ['pointerdown', 'pointermove', 'keydown', 'input', 'wheel', 'touchmove'].forEach(name => {
    document.addEventListener(name, interact, {passive: true, capture: true});
  });
  // Polling checks expiry only; it never reports an interaction on its own.
  setInterval(() => {
    if (Date.now() >= deadline || (!document.hidden && pendingInteraction !== null && Date.now() - lastSent >= 15000)) synchronize();
  }, 1000);
  setInterval(synchronize, 30000);
  window.addEventListener('pageshow', synchronize);
  window.addEventListener('online', synchronize);
  window.addEventListener('pagehide', () => {
    if (stopped || pendingInteraction === null || Date.now() >= deadline) return;
    const body = new FormData();
    body.set('_token', script.dataset.csrf || document.querySelector('meta[name="csrf-token"]')?.content || '');
    body.set('age_ms', String(Math.max(0, Date.now() - pendingInteraction)));
    navigator.sendBeacon?.(script.dataset.activityUrl, body);
  });
  document.addEventListener('visibilitychange', () => { if (!document.hidden) synchronize(); });
  synchronize();
})();

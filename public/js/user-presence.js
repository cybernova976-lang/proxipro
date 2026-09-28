/* Présence réelle : signaux courts par onglet, réservés aux membres connectés. */
(function () {
  'use strict';
  const script = document.querySelector('script[data-user-presence]');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  if (!script || !csrf || !crypto.randomUUID) return;
  // Un nouvel identifiant à chaque document évite les collisions entre onglets
  // dupliqués et les remises à zéro de séquence après un rechargement.
  const tabId = crypto.randomUUID();
  const interval = Math.max(15, Number(script.dataset.interval) || 25) * 1000;
  let sequence = 0;
  let busy = false;
  let pending = false;
  let stopped = false;
  let paused = false;
  const freshness = new WeakMap();
  const checkedAt = new WeakMap();
  const indicators = () => Array.from(document.querySelectorAll('[data-presence-conversation]'));
  function paint(el, state, label) {
    el.dataset.state = state;
    el.querySelector('span').textContent = label;
  }
  function unknown() {
    indicators().forEach(el => {
      if (['hidden', 'unavailable'].includes(el.dataset.state)) return;
      paint(el, 'unknown', 'Présence indisponible');
      freshness.set(el, 0);
    });
  }
  function update(presences) {
    if (!navigator.onLine) return;
    indicators().forEach(el => {
      const status = presences[el.dataset.presenceConversation];
      if (!status || !['online', 'offline', 'hidden', 'unavailable'].includes(status.state)) return;
      const timestamp = Number(status.checked_at || 0);
      if (timestamp && timestamp < (checkedAt.get(el) || 0)) return;
      checkedAt.set(el, timestamp);
      const labels = {online: 'En ligne', offline: 'Hors ligne', hidden: 'Présence masquée', unavailable: 'Compte indisponible'};
      paint(el, status.state, labels[status.state]);
      freshness.set(el, Date.now() + Math.min(75, Math.max(0, Number(status.valid_for_seconds))) * 1000);
    });
  }
  indicators().forEach(el => freshness.set(el, Date.now() + Math.max(0, Number(el.dataset.validFor)) * 1000));
  window.addEventListener('pk:presence', event => update(event.detail || {}));
  function payload(active) {
    return {tab_id: tabId, sequence: ++sequence, active,
      conversation_ids: [...new Set(indicators().map(el => Number(el.dataset.presenceConversation)))].slice(0, 30)};
  }
  function release() {
    if (stopped) return;
    const data = payload(false);
    const body = new FormData();
    body.set('_token', csrf); body.set('tab_id', tabId); body.set('sequence', data.sequence); body.set('active', '0');
    // Le navigateur transmet ce petit signal même pendant la fermeture.
    if (navigator.sendBeacon?.(script.dataset.heartbeatUrl, body)) return;
    fetch(script.dataset.heartbeatUrl, {method: 'POST', credentials: 'same-origin', body, keepalive: true}).catch(() => {});
  }
  async function heartbeat() {
    if (stopped || paused || document.hidden || !navigator.onLine) return;
    if (busy) { pending = true; return; }
    busy = true;
    const data = payload(true);
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 10000);
    try {
      const response = await fetch(script.dataset.heartbeatUrl, {method: 'POST', credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
        headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf}, body: JSON.stringify(data)});
      if ([401, 403, 419].includes(response.status)) stopped = true;
      if (!response.ok) throw new Error('Presence unavailable');
      const result = await response.json();
      if (data.sequence === sequence && !document.hidden && !paused) update(result.presences || {});
    } catch (_) { if (!document.hidden) unknown(); }
    finally {
      clearTimeout(timeout); busy = false;
      if (pending) { pending = false; heartbeat(); }
    }
  }
  // Ne jamais conserver une pastille verte si la vérification est devenue périmée.
  setInterval(() => indicators().forEach(el => {
    if (['online', 'offline'].includes(el.dataset.state) && Date.now() >= (freshness.get(el) || 0)) paint(el, 'unknown', 'Présence indisponible');
  }), 1000);
  setInterval(heartbeat, interval);
  document.addEventListener('visibilitychange', () => { if (document.hidden) release(); else heartbeat(); });
  window.addEventListener('pagehide', () => { paused = true; release(); });
  window.addEventListener('pageshow', event => { if (event.persisted) { paused = false; heartbeat(); } });
  window.addEventListener('online', heartbeat);
  window.addEventListener('offline', unknown);
  heartbeat();
})();

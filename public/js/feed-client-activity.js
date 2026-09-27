/* Actualise uniquement le suivi personnel, sans recharger le feed ni son formulaire. */
(function () {
  'use strict';
  var root = document.getElementById('pkClientActivity');
  if (!root) return;
  var pending = false;
  var lastCheck = Date.now();
  var stopped = false;
  async function refresh() {
    if (stopped || pending || document.hidden || !navigator.onLine || Date.now() - lastCheck < 5000) return;
    // Ne pas retirer un lien actuellement utilise au clavier.
    if (root.contains(document.activeElement)) return;
    pending = true;
    lastCheck = Date.now();
    var controller = new AbortController();
    var timeout = setTimeout(function () { controller.abort(); }, 10000);
    try {
      var url = new URL(root.dataset.refreshUrl, location.origin);
      url.searchParams.set('revision', root.dataset.revision);
      var response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', signal: controller.signal, headers: { Accept: 'application/json' } });
      if (response.status === 401 || response.status === 403) { stopped = true; return; }
      if (!response.ok || !(response.headers.get('content-type') || '').includes('application/json')) return;
      var data = await response.json();
      if (data.changed && typeof data.html === 'string') {
        root.innerHTML = data.html;
        root.dataset.revision = data.revision;
      }
    } catch (error) {
      // En cas de coupure reseau, conserver le dernier suivi et reessayer plus tard.
    } finally {
      clearTimeout(timeout);
      pending = false;
    }
  }
  setInterval(refresh, 60000);
  document.addEventListener('visibilitychange', refresh);
  window.addEventListener('focus', refresh);
  window.addEventListener('pageshow', refresh);
  window.addEventListener('online', refresh);
})();

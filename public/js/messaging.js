/* Messagerie : ne modifie que la conversation active, jamais les autres pages. */
(function () {
  'use strict';
  const root = document.querySelector('[data-messaging]');
  if (!root) return;
  document.documentElement.classList.add('pk-messaging-page');
  window.scrollTo(0, 0);
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const form = document.getElementById('messageForm');
  document.querySelector('#newConversationModal form')?.addEventListener('submit', function () {
    this.querySelector('[type="submit"]').disabled = true;
  });
  if (!form) return;
  const input = document.getElementById('messageInput');
  const send = document.getElementById('sendMessage');
  const thread = document.getElementById('chatMessages');
  const feedback = document.getElementById('messageFeedback');
  const connection = document.getElementById('messageConnection');
  const newMessages = document.getElementById('newMessages');
  const me = Number(root.dataset.user);
  const draftKey = 'prokejem-message-' + me + '-' + root.dataset.conversation;
  let sending = false;
  let polling = false;
  let stopped = false;
  let lastPoll = 0;
  let attempt = null;
  let pinned = true;
  let lastId = Math.max(0, ...Array.from(thread.querySelectorAll('[data-message-id]'), el => Number(el.dataset.messageId)));
  let blocked = input.disabled;
  function notice(text, success) {
    feedback.textContent = text;
    feedback.hidden = !text;
    feedback.classList.toggle('is-success', !!success);
  }
  function resize() {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    document.getElementById('charCounter').textContent = input.value.length + ' / 3000';
  }
  try { input.value = sessionStorage.getItem(draftKey) || ''; } catch (_) {}
  resize();
  input.addEventListener('input', function () {
    resize();
    try { sessionStorage.setItem(draftKey, input.value); } catch (_) {}
  });
  const emojiPanel = document.getElementById('messageEmojis');
  const emojiToggle = document.getElementById('emojiToggle');
  emojiToggle.addEventListener('click', () => {
    emojiPanel.hidden = !emojiPanel.hidden;
    emojiToggle.setAttribute('aria-expanded', String(!emojiPanel.hidden));
  });
  emojiPanel.addEventListener('click', event => {
    const btn = event.target.closest('[data-emoji]');
    if (!btn || blocked || sending || input.value.length + btn.dataset.emoji.length > 3000) return;
    input.setRangeText(btn.dataset.emoji, input.selectionStart, input.selectionEnd, 'end');
    input.dispatchEvent(new Event('input'));
    input.focus();
    emojiPanel.hidden = true; emojiToggle.setAttribute('aria-expanded', 'false');
  });
  input.addEventListener('keydown', function (event) {
    // Sur téléphone, Entrée garde une nouvelle ligne ; bouton pour envoyer.
    if (event.key === 'Enter' && !event.shiftKey && !event.isComposing && matchMedia('(min-width: 992px) and (pointer: fine)').matches) {
      event.preventDefault();
      if (!sending) form.requestSubmit();
    }
  });
  async function request(url, method, body) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 15000);
    try {
      const response = await fetch(url, {method: method || 'GET', credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
        headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, ...(body ? {'Content-Type': 'application/json'} : {})},
        ...(body ? {body: JSON.stringify(body)} : {})});
      const data = (response.headers.get('content-type') || '').includes('application/json') ? await response.json() : {};
      if (!response.ok) {
        if (response.status === 401 || response.status === 419) {
          stopped = true;
          throw new Error('Votre session a expiré. Reconnectez-vous ; votre brouillon est conservé sur cet onglet.');
        }
        throw new Error(Object.values(data.errors || {}).flat()[0] || data.error || (response.status === 403 ? 'Cette action n’est pas autorisée ou la conversation est bloquée.' : 'L’action a échoué. Réessayez dans un instant.'));
      }
      return data;
    } finally { clearTimeout(timeout); }
  }
  const time = value => new Date(value).toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'});
  function element(tag, className, text) {
    const el = document.createElement(tag);
    el.className = className;
    if (text !== undefined) el.textContent = text;
    return el;
  }
  function syncMessage(el, message) {
    if (!el.classList.contains('is-editing')) el.querySelector('.msg-text').textContent = message.content;
    el.querySelector('.msg-edited').textContent = message.edited_at ? 'Modifié' : '';
    const delivery = el.querySelector('.msg-delivery');
    if (delivery) delivery.textContent = message.is_read ? 'Lu' : 'Envoyé';
  }
  function dates() {
    thread.querySelectorAll('.msg-date').forEach(el => el.remove());
    let previous;
    thread.querySelectorAll('[data-message-id]').forEach(el => {
      const date = new Date(el.dataset.createdAt);
      const label = date.toLocaleDateString('fr-FR');
      if (label !== previous) {
        const today = new Date().toLocaleDateString('fr-FR');
        el.before(element('div', 'msg-date', label === today ? 'Aujourd’hui' : label));
        previous = label;
      }
    });
  }
  function render(message, prepend) {
    const existing = thread.querySelector('[data-message-id="' + Number(message.id) + '"]');
    if (existing) { syncMessage(existing, message); return false; }
    const own = Number(message.sender_id) === me;
    const article = element('article', 'msg-message' + (own ? ' is-own' : ''));
    article.dataset.messageId = message.id;
    article.dataset.createdAt = message.created_at;
    const bubble = element('div', 'msg-bubble');
    bubble.append(element('div', 'msg-text', message.content));
    const meta = element('div', 'msg-meta');
    meta.append(element('time', '', time(message.created_at)), element('span', 'msg-edited', message.edited_at ? 'Modifié' : ''));
    if (own) meta.append(element('span', 'msg-delivery', message.is_read ? 'Lu' : 'Envoyé'));
    bubble.append(meta);
    if (own && Date.now() - new Date(message.created_at).getTime() < 300000) {
      const actions = element('div', 'msg-message-actions');
      ['edit', 'delete'].forEach(action => {
        const btn = element('button', '', action === 'edit' ? 'Modifier' : 'Supprimer');
        btn.type = 'button'; btn.dataset.messageAction = action; actions.append(btn);
      });
      bubble.append(actions);
    }
    article.append(bubble);
    if (prepend) {
      thread.insertBefore(article, thread.querySelector('.msg-message, .msg-date'));
    } else {
      const next = Array.from(thread.querySelectorAll('[data-message-id]')).find(el => Number(el.dataset.messageId) > Number(message.id));
      thread.insertBefore(article, next || null);
    }
    return true;
  }
  function bottom() { thread.scrollTop = thread.scrollHeight; newMessages.hidden = true; }
  bottom();
  thread.addEventListener('scroll', () => { pinned = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 100; });
  newMessages.addEventListener('click', bottom);
  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    const content = input.value.trim();
    if (!content || sending || blocked) return;
    if (!navigator.onLine) { notice('Vous êtes hors connexion. Votre message est conservé ; envoyez-le lorsque le réseau revient.'); return; }
    if (!attempt || attempt.content !== content) attempt = {content, token: crypto.randomUUID()};
    sending = true; send.disabled = true; input.readOnly = true;
    notice('Envoi en cours…');
    try {
      const data = await request(form.action, 'POST', {conversation_id: Number(root.dataset.conversation), content, client_token: attempt.token});
      render(data.message); dates(); bottom();
      input.value = ''; attempt = null; resize();
      try { sessionStorage.removeItem(draftKey); } catch (_) {}
      notice('Message envoyé.', true);
      // Ne pas avancer le curseur ici : un message reçu pendant l’envoi pourrait être sauté.
    } catch (error) {
      notice(error.name === 'AbortError' ? 'La confirmation prend trop de temps. Vérifiez les derniers messages, puis réessayez si nécessaire : une nouvelle tentative identique ne crée pas de doublon.' : error.message || 'Envoi non confirmé. Votre texte est conservé.');
    } finally { sending = false; input.readOnly = false; send.disabled = blocked || stopped; }
  });
  async function poll() {
    if (stopped || polling || document.hidden || !navigator.onLine || Date.now() - lastPoll < 2500) return;
    polling = true; lastPoll = Date.now();
    const url = new URL(root.dataset.pollUrl, location.origin);
    url.searchParams.set('last_id', lastId);
    const visible = Array.from(thread.querySelectorAll('[data-message-id]')).slice(-200).map(el => Number(el.dataset.messageId));
    visible.forEach(id => url.searchParams.append('visible_ids[]', id));
    try {
      const data = await request(url);
      if (data.presence) window.dispatchEvent(new CustomEvent('pk:presence', {detail: {[root.dataset.conversation]: data.presence}}));
      const nearBottom = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 100;
      let added = false;
      data.messages.forEach(message => { added = render(message) || added; lastId = Math.max(lastId, message.id); });
      data.visible_messages.forEach(message => {
        const el = thread.querySelector('[data-message-id="' + Number(message.id) + '"]');
        if (el) syncMessage(el, message);
      });
      const remaining = new Set(data.visible_ids.map(Number));
      const removed = visible.filter(id => !remaining.has(id));
      removed.forEach(id => thread.querySelector('[data-message-id="' + id + '"]')?.remove());
      if (added || removed.length) dates();
      blocked = data.is_blocked || root.dataset.unavailable === 'true';
      input.disabled = blocked; send.disabled = blocked || sending || stopped;
      document.getElementById('blockedNotice').hidden = !data.is_blocked;
      if (added) { if (nearBottom) bottom(); else newMessages.hidden = false; }
      connection.hidden = true;
    } catch (error) {
      if (stopped) send.disabled = true;
      connection.textContent = stopped ? error.message : 'Actualisation momentanément indisponible. Les échanges affichés sont conservés.';
      connection.hidden = false;
    } finally { polling = false; }
  }
  document.getElementById('loadOlder')?.addEventListener('click', async function () {
    this.disabled = true;
    const url = new URL(root.dataset.pollUrl, location.origin);
    url.searchParams.set('before_id', thread.querySelector('[data-message-id]')?.dataset.messageId || 1);
    const oldHeight = thread.scrollHeight;
    try {
      const data = await request(url);
      data.messages.slice().reverse().forEach(message => render(message, true));
      dates();
      thread.scrollTop += thread.scrollHeight - oldHeight;
      this.hidden = !data.has_more;
    } catch (error) { notice(error.message); } finally { this.disabled = false; }
  });
  thread.addEventListener('click', async function (event) {
    const btn = event.target.closest('[data-message-action]');
    if (!btn) return;
    const article = btn.closest('[data-message-id]');
    if (Date.now() - new Date(article.dataset.createdAt).getTime() >= 300000) {
      article.querySelector('.msg-message-actions')?.remove(); notice('Le délai de modification de cinq minutes est dépassé.'); return;
    }
    const id = article.dataset.messageId;
    if (btn.dataset.messageAction === 'delete') {
      if (!confirm('Supprimer ce message pour les deux participants ?')) return;
      btn.disabled = true;
      try { await request(root.dataset.deleteUrl.replace('__ID__', id), 'DELETE'); article.remove(); }
      catch (error) { notice(error.message); btn.disabled = false; }
      return;
    }
    if (article.classList.contains('is-editing')) return;
    article.classList.add('is-editing');
    const text = article.querySelector('.msg-text');
    text.hidden = true;
    const editor = element('textarea', 'msg-edit-input'); editor.value = text.textContent; editor.maxLength = 3000; editor.setAttribute('aria-label', 'Modifier votre message');
    const actions = element('div', 'msg-edit-actions');
    const save = element('button', '', 'Enregistrer'); save.type = 'button';
    const cancel = element('button', '', 'Annuler'); cancel.type = 'button';
    actions.append(save, cancel); text.after(editor, actions); editor.focus();
    function close() { editor.remove(); actions.remove(); text.hidden = false; article.classList.remove('is-editing'); }
    cancel.addEventListener('click', close);
    save.addEventListener('click', async function () {
      if (!editor.value.trim()) { notice('Le message ne peut pas être vide.'); return; }
      save.disabled = true;
      try { const data = await request(root.dataset.updateUrl.replace('__ID__', id), 'PUT', {content: editor.value.trim()}); close(); syncMessage(article, data.message); }
      catch (error) { notice(error.message); save.disabled = false; }
    });
  });
  root.querySelectorAll('[data-conversation-action]').forEach(btn => btn.addEventListener('click', async function () {
    if (btn.dataset.confirm && !confirm(btn.dataset.confirm)) return;
    btn.disabled = true;
    try { await request(btn.dataset.conversationAction, 'POST'); location.reload(); }
    catch (error) { notice(error.message); btn.disabled = false; }
  }));
  setInterval(poll, 5000);
  setInterval(() => thread.querySelectorAll('[data-message-id]').forEach(el => {
    if (Date.now() - new Date(el.dataset.createdAt).getTime() >= 300000) el.querySelector('.msg-message-actions')?.remove();
  }), 15000);
  document.addEventListener('visibilitychange', poll);
  window.addEventListener('focus', poll);
  window.addEventListener('online', () => { notice('Connexion rétablie. Vous pouvez envoyer votre brouillon.'); poll(); });
  window.addEventListener('offline', () => { connection.textContent = 'Hors connexion — votre brouillon est conservé.'; connection.hidden = false; });
  // Le clavier mobile réduit visualViewport sans changer systématiquement 100dvh.
  function viewport() {
    if (window.visualViewport && innerWidth < 768) {
      const headerHeight = document.querySelector('header')?.getBoundingClientRect().height || 68;
      root.style.height = Math.max(220, window.visualViewport.height - headerHeight) + 'px';
    } else root.style.height = '';
    if (pinned) requestAnimationFrame(bottom);
  }
  window.visualViewport?.addEventListener('resize', viewport);
  window.addEventListener('resize', viewport);
  viewport();
})();

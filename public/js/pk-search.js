/*
 * PROKEJEM — pilule « Quel est votre problème ? » de l'accueil client.
 * Recherche tolérante (accents, pluriels, lettres doublées, une faute de
 * frappe) dans les situations du parcours guidé puis dans les métiers actifs.
 * Le texte tapé n'est jamais mis dans une URL ni envoyé en mesure d'usage.
 * Exporté sous globalThis.ProkejemSearch pour les tests node:test.
 */
(function (root) {
  'use strict';

  function normalize(value) {
    return String(value || '')
      .toLowerCase()
      .replace(/œ/g, 'oe').replace(/æ/g, 'ae')
      .normalize('NFD').replace(/[̀-ͯ]/g, '')
      .replace(/[’'\-_/.,;:!?()]+/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function simplify(word) {
    var w = word.replace(/(.)\1+/g, '$1');
    if (w.length > 3) w = w.replace(/[sx]$/, '');
    return w;
  }

  // Distance de Damerau-Levenshtein (transpositions adjacentes), bornée.
  function distance(a, b, max) {
    if (Math.abs(a.length - b.length) > max) return max + 1;
    var d = [];
    for (var i = 0; i <= a.length; i++) { d[i] = [i]; }
    for (var j = 0; j <= b.length; j++) { d[0][j] = j; }
    for (i = 1; i <= a.length; i++) {
      for (j = 1; j <= b.length; j++) {
        var cost = a[i - 1] === b[j - 1] ? 0 : 1;
        d[i][j] = Math.min(d[i - 1][j] + 1, d[i][j - 1] + 1, d[i - 1][j - 1] + cost);
        if (i > 1 && j > 1 && a[i - 1] === b[j - 2] && a[i - 2] === b[j - 1]) {
          d[i][j] = Math.min(d[i][j], d[i - 2][j - 2] + 1);
        }
      }
    }
    return d[a.length][b.length];
  }

  function wordMatches(term, word) {
    var t = simplify(term);
    var w = simplify(word);
    if (w.indexOf(t) === 0) return true;
    if (t.length < 4) return false;
    var max = t.length >= 8 ? 2 : 1;
    return distance(t, w, max) <= max || distance(t, w.slice(0, t.length), max) <= max;
  }

  // Vrai si chaque mot tapé correspond à un mot d'un des libellés cibles.
  function match(query, targets) {
    var terms = normalize(query).split(' ').filter(Boolean);
    if (!terms.length) return false;
    return (targets || []).some(function (target) {
      var words = normalize(target).split(' ').filter(Boolean);
      return terms.every(function (term) {
        return words.some(function (word) { return wordMatches(term, word); });
      });
    });
  }

  function suggest(query, index) {
    index = index || {};
    if (normalize(query).length < 2) return [];
    var rows = [];
    (index.situations || []).forEach(function (s) {
      if (match(query, [s.label].concat(s.search || []))) rows.push({ kind: 'situation', key: s.key, label: s.label, url: s.url });
    });
    (index.trades || []).forEach(function (t) {
      if (match(query, [t.label])) rows.push({ kind: 'trade', label: t.label, hint: t.parent, url: t.url });
    });
    var found = rows.length > 0;
    rows = rows.slice(0, 4);
    if (found && index.providersUrl) rows.push({ kind: 'providers', label: index.providersLabel || 'Voir les prestataires', url: index.providersUrl });
    rows.push({ kind: 'fallback', label: index.fallbackLabel || 'Décrire mon besoin', url: index.fallbackUrl || '/demande' });
    return rows.slice(0, 6);
  }

  var api = { normalize: normalize, simplify: simplify, distance: distance, match: match, suggest: suggest };
  root.ProkejemSearch = api;

  if (typeof document === 'undefined') return;

  function track(name) {
    if (root.prokejemUsage && typeof root.prokejemUsage.track === 'function') {
      try { root.prokejemUsage.track(name); } catch (e) { /* mesure facultative */ }
    }
  }

  function init() {
    var form = document.getElementById('pkSearchForm');
    var field = document.getElementById('pkSearchField');
    var list = document.getElementById('pkSearchSuggest');
    var configNode = document.getElementById('pkSearchConfig');
    if (!form || !field || !list) return;

    var index = {};
    try { index = JSON.parse(configNode ? configNode.textContent : '{}') || {}; } catch (e) { index = {}; }
    var rows = [];
    var active = -1;

    function close() {
      list.hidden = true;
      field.setAttribute('aria-expanded', 'false');
      field.removeAttribute('aria-activedescendant');
      active = -1;
    }

    function render() {
      rows = suggest(field.value, index);
      list.textContent = '';
      if (!rows.length) { close(); return; }
      rows.forEach(function (row, i) {
        var li = document.createElement('li');
        li.id = 'pkSearchOption' + i;
        li.setAttribute('role', 'option');
        li.className = 'pk-search__option pk-search__option--' + row.kind;
        var label = document.createElement('span');
        label.textContent = row.label;
        li.appendChild(label);
        if (row.hint) {
          var hint = document.createElement('small');
          hint.textContent = row.hint;
          li.appendChild(hint);
        }
        li.addEventListener('mousedown', function (event) { event.preventDefault(); go(i, 'feed_search_suggestion'); });
        list.appendChild(li);
      });
      list.hidden = false;
      field.setAttribute('aria-expanded', 'true');
      highlight(-1);
    }

    function highlight(i) {
      active = i;
      Array.prototype.forEach.call(list.children, function (li, n) {
        li.setAttribute('aria-selected', n === i ? 'true' : 'false');
      });
      if (i >= 0) field.setAttribute('aria-activedescendant', 'pkSearchOption' + i);
      else field.removeAttribute('aria-activedescendant');
    }

    function go(i, eventName) {
      var row = rows[i];
      if (!row) return;
      if (row.kind === 'fallback') {
        try { root.sessionStorage.setItem('prokejem.search.handoff', field.value.trim()); } catch (e) { /* stockage indisponible */ }
      }
      track(eventName);
      root.location.assign(row.url);
    }

    field.addEventListener('input', render);
    field.addEventListener('blur', function () { root.setTimeout(close, 120); });
    field.addEventListener('keydown', function (event) {
      if (list.hidden && (event.key === 'ArrowDown' || event.key === 'ArrowUp')) render();
      if (event.key === 'ArrowDown') { event.preventDefault(); highlight(Math.min(active + 1, rows.length - 1)); }
      else if (event.key === 'ArrowUp') { event.preventDefault(); highlight(Math.max(active - 1, -1)); }
      else if (event.key === 'Escape') { close(); }
    });

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      rows = suggest(field.value, index);
      if (!rows.length) { track('feed_search_submit'); root.location.assign(form.getAttribute('action')); return; }
      var pick = active >= 0 ? active : 0;
      if (rows[pick].kind === 'fallback') track('feed_search_no_match');
      go(pick, 'feed_search_submit');
    });

    document.querySelectorAll('.pk-tile[data-pk-situation]').forEach(function (tile) {
      tile.addEventListener('click', function () { track('feed_situation_click'); });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})(typeof globalThis !== 'undefined' ? globalThis : this);

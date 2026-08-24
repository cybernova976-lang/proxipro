/* ==========================================================================
   PROKEJEM — Page d'accueil feed
   --------------------------------------------------------------------------
   Trois comportements seulement :
     1. le champ d'intention (suggestions de categories + envoi vers /demande)
     2. l'enregistrement d'une annonce en favori
     3. une notification legere partagee par les deux

   Toute la publication passe par les pages dediees. Aucune modale ici.
   ========================================================================== */

(function () {
  'use strict';

  var root = document.getElementById('pkFeed');
  if (!root) return;

  var config = {};
  try {
    config = JSON.parse(document.getElementById('pkFeedConfig').textContent);
  } catch (e) {
    config = { categories: [], demandUrl: '/demande', offerUrl: '/ads/create', saveUrl: '/ads/:id/toggle-save', role: 'client' };
  }

  var csrf = document.querySelector('meta[name="csrf-token"]');
  csrf = csrf ? csrf.getAttribute('content') : '';

  /* ---------------------------------------------------------------- toast */

  var toast = document.getElementById('pkToast');
  var toastTimer = null;

  function notify(message, type) {
    if (!toast) return;
    window.clearTimeout(toastTimer);
    toast.textContent = message;
    toast.dataset.type = type || 'success';
    toast.classList.add('is-visible');
    toastTimer = window.setTimeout(function () {
      toast.classList.remove('is-visible');
    }, 3000);
  }

  /* ------------------------------------------------- champ d'intention */

  var form = document.getElementById('pkIntentForm');
  var field = document.getElementById('pkIntentField');
  var panel = document.getElementById('pkSuggest');

  function normalize(value) {
    return (value || '')
      .toString()
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .trim();
  }

  // Destination de publication selon le role : une demande pour un client,
  // une offre de service pour un prestataire.
  function publishUrl(category, subcategory) {
    var base = config.role === 'provider' ? config.offerUrl : config.demandUrl;
    var params = [];
    if (config.role === 'provider') params.push('type=service');
    if (category) params.push('category=' + encodeURIComponent(category));
    if (subcategory) params.push('subcategory=' + encodeURIComponent(subcategory));
    return params.length ? base + (base.indexOf('?') === -1 ? '?' : '&') + params.join('&') : base;
  }

  if (form && field && panel) {
    var matches = [];
    var cursor = -1;

    function closePanel() {
      panel.hidden = true;
      cursor = -1;
      field.setAttribute('aria-expanded', 'false');
    }

    function highlight() {
      var buttons = panel.querySelectorAll('button');
      for (var i = 0; i < buttons.length; i++) {
        buttons[i].classList.toggle('is-active', i === cursor);
      }
      if (cursor >= 0 && buttons[cursor]) {
        buttons[cursor].scrollIntoView({ block: 'nearest' });
      }
    }

    function search(term) {
      var needle = normalize(term);
      if (needle.length < 2) return [];
      var found = [];
      for (var i = 0; i < config.categories.length && found.length < 8; i++) {
        var item = config.categories[i];
        if (item.search.indexOf(needle) !== -1) found.push(item);
      }
      return found;
    }

    function render(list) {
      if (!list.length) { closePanel(); return; }
      var html = '';
      for (var i = 0; i < list.length; i++) {
        var item = list[i];
        html += '<button type="button" data-category="' + item.parent.replace(/"/g, '&quot;') + '"' +
                ' data-subcategory="' + (item.sub || '').replace(/"/g, '&quot;') + '">' +
                '<i class="' + item.icon + '"></i>' +
                '<span>' + item.label + '</span>' +
                (item.sub ? '<small>' + item.parent + '</small>' : '') +
                '</button>';
      }
      panel.innerHTML = html;
      panel.hidden = false;
      cursor = -1;
      field.setAttribute('aria-expanded', 'true');
    }

    field.addEventListener('input', function () {
      matches = search(field.value);
      render(matches);
    });

    field.addEventListener('keydown', function (event) {
      if (panel.hidden) return;
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        cursor = Math.min(cursor + 1, matches.length - 1);
        highlight();
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        cursor = Math.max(cursor - 1, -1);
        highlight();
      } else if (event.key === 'Escape') {
        closePanel();
      } else if (event.key === 'Enter' && cursor >= 0) {
        event.preventDefault();
        var chosen = matches[cursor];
        window.location.href = publishUrl(chosen.parent, chosen.sub);
      }
    });

    panel.addEventListener('click', function (event) {
      var button = event.target.closest('button');
      if (!button) return;
      window.location.href = publishUrl(button.dataset.category, button.dataset.subcategory);
    });

    document.addEventListener('click', function (event) {
      if (!form.contains(event.target)) closePanel();
    });

    // Soumission au clavier sans suggestion selectionnee : on tente la
    // meilleure correspondance, sinon on ouvre le formulaire vierge.
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var best = search(field.value)[0];
      window.location.href = best ? publishUrl(best.parent, best.sub) : publishUrl();
    });
  }

  /* ------------------------------------------------- categories rapides */

  var quick = root.querySelectorAll('[data-pk-category]');
  for (var q = 0; q < quick.length; q++) {
    quick[q].addEventListener('click', function () {
      window.location.href = publishUrl(this.dataset.pkCategory);
    });
  }

  /* ------------------------------------------------------------ favoris */

  root.addEventListener('click', function (event) {
    var button = event.target.closest('[data-pk-save]');
    if (!button) return;
    event.preventDefault();

    var wasSaved = button.getAttribute('aria-pressed') === 'true';
    button.setAttribute('aria-busy', 'true');

    fetch(config.saveUrl.replace(':id', encodeURIComponent(button.dataset.pkSave)), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      credentials: 'same-origin'
    })
      .then(function (response) {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.json();
      })
      .then(function (data) {
        var saved = Boolean(data.saved);
        button.setAttribute('aria-pressed', saved ? 'true' : 'false');
        var icon = button.querySelector('i');
        if (icon) icon.className = (saved ? 'fas' : 'far') + ' fa-bookmark';
        button.setAttribute('aria-label', saved ? 'Retirer des favoris' : 'Enregistrer dans les favoris');
        notify(data.message || (saved ? 'Annonce enregistrée.' : 'Annonce retirée des favoris.'));
      })
      .catch(function () {
        button.setAttribute('aria-pressed', wasSaved ? 'true' : 'false');
        notify('Impossible de modifier ce favori. Réessayez.', 'error');
      })
      .then(function () {
        button.removeAttribute('aria-busy');
      });
  });
})();

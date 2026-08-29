/* ========================================================================== 
   PROKEJEM — Retour tactile immediat de la barre d'onglets mobile
   ========================================================================== */

(function () {
  'use strict';

  var nav = document.querySelector('.pk-tabbar');
  if (!nav || nav.dataset.pkTabbarReady === 'true') return;
  nav.dataset.pkTabbarReady = 'true';

  var links = nav.querySelectorAll('[data-pk-tab]');
  var feedbackTimer = null;

  function resetFeedback() {
    window.clearTimeout(feedbackTimer);
    for (var i = 0; i < links.length; i++) {
      links[i].classList.remove('is-pressed', 'is-pending', 'is-feedback');
      links[i].removeAttribute('aria-busy');
    }
  }

  function isExactCurrentUrl(link) {
    try {
      var target = new URL(link.href, window.location.href);
      return target.origin === window.location.origin
        && target.pathname.replace(/\/$/, '') === window.location.pathname.replace(/\/$/, '')
        && target.search === window.location.search;
    } catch (error) {
      return false;
    }
  }

  nav.addEventListener('pointerdown', function (event) {
    var link = event.target.closest('[data-pk-tab]');
    if (!link) return;
    link.classList.add('is-pressed');
  }, { passive: true });

  nav.addEventListener('pointerup', function (event) {
    var link = event.target.closest('[data-pk-tab]');
    if (link) link.classList.remove('is-pressed');
  }, { passive: true });

  nav.addEventListener('pointercancel', resetFeedback, { passive: true });

  nav.addEventListener('click', function (event) {
    var link = event.target.closest('[data-pk-tab]');
    if (!link || event.button > 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    resetFeedback();

    // Un second appui sur la page exacte remonte au debut sans recharger le
    // feed. Sur une sous-page (conversation, detail d'annonce), le lien garde
    // son comportement normal et revient bien a la rubrique principale.
    if (isExactCurrentUrl(link)) {
      event.preventDefault();
      link.classList.add('is-feedback');
      window.scrollTo({ top: 0, behavior: 'smooth' });
      feedbackTimer = window.setTimeout(function () {
        link.classList.remove('is-feedback');
      }, 320);
      return;
    }

    // La couleur change avant que le serveur reponde : l'utilisateur sait
    // immediatement que son toucher a bien ete pris en compte.
    link.classList.add('is-pending');
    link.setAttribute('aria-busy', 'true');
  });

  window.addEventListener('pageshow', resetFeedback);
}());

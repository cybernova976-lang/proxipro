/**
 * Panneau latéral du tableau de bord du particulier.
 *
 * À partir de 1180 px, le panneau est une colonne toujours visible : ce script
 * ne fait rien. En dessous, c'est un tiroir rétractable à gauche :
 *  - il s'ouvre avec le bouton « Menu » de la barre de repère, et avec l'icône
 *    « Tableau de bord » de l'en-tête (toujours à portée de main, même en bas
 *    de page) ;
 *  - il se referme avec la croix, un appui sur le fond, ou la touche Échap.
 * Sans script, les liens gardent leur adresse : l'icône de l'en-tête mène à la
 * vue d'ensemble.
 */
(function () {
    'use strict';

    var menu = document.querySelector('[data-space-menu]');
    if (!menu) { return; }

    var backdrop = document.querySelector('[data-space-backdrop]');
    var wide = window.matchMedia('(min-width: 1180px)');
    var lastTrigger = null;

    function isOpen() { return menu.classList.contains('is-open'); }

    function setOpen(open, trigger) {
        if (open && wide.matches) { return; }
        if (open === isOpen()) { return; }

        menu.classList.toggle('is-open', open);
        document.body.classList.toggle('pk-space-menu-open', open);
        if (backdrop) { backdrop.hidden = !open; }
        document.querySelectorAll('[data-space-menu-open]').forEach(function (button) {
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        if (open) {
            lastTrigger = trigger || document.activeElement;
            var close = menu.querySelector('.pk-space-menu__close');
            if (close) { close.focus({ preventScroll: true }); }
        } else if (lastTrigger && typeof lastTrigger.focus === 'function') {
            lastTrigger.focus({ preventScroll: true });
            lastTrigger = null;
        }
    }

    document.addEventListener('click', function (event) {
        var opener = event.target.closest('[data-space-menu-open]');
        if (opener) {
            event.preventDefault();
            setOpen(!isOpen(), opener);
            return;
        }
        if (event.target.closest('[data-space-menu-close]')) {
            setOpen(false);
            return;
        }
        // Téléphone et tablette : l'icône « Tableau de bord » de l'en-tête ouvre le panneau.
        var headerIcon = event.target.closest('[data-header-dashboard-mobile]');
        if (headerIcon && !wide.matches) {
            event.preventDefault();
            setOpen(!isOpen(), headerIcon);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && isOpen()) { setOpen(false); }
    });

    // Passage en grand écran, ou retour arrière du navigateur : le tiroir se referme.
    var onWide = function () { if (wide.matches) { setOpen(false); } };
    if (wide.addEventListener) { wide.addEventListener('change', onWide); } else if (wide.addListener) { wide.addListener(onWide); }
    window.addEventListener('pageshow', function (event) { if (event.persisted) { lastTrigger = null; setOpen(false); } });
})();

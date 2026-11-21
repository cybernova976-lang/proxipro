/*
 * « Mes demandes » : une suppression est toujours confirmée avant d'être envoyée.
 * Le texte de la question est porté par le formulaire (data-pk-confirm).
 */
(function () {
    'use strict';

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.matches || !form.matches('form[data-pk-confirm]')) {
            return;
        }
        if (!window.confirm(form.getAttribute('data-pk-confirm'))) {
            event.preventDefault();
        }
    });
})();

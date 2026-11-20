/* Page Facturation : confirmation avant une résiliation ou une reprise d'abonnement. */
(function () {
    'use strict';

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-pk-confirm')) {
            return;
        }
        if (!window.confirm(form.getAttribute('data-pk-confirm'))) {
            event.preventDefault();
            return;
        }
        // Un seul envoi : la demande part chez Stripe, un double clic ne doit pas la répéter.
        var button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
        }
    });
})();

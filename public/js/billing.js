/*
 * Page Facturation : un seul envoi pour une résiliation ou une reprise d'abonnement.
 * La question de confirmation (attribut data-pk-confirm) est posée par le script
 * commun du gabarit ; la poser ici aussi l'affichait deux fois.
 */
(function () {
    'use strict';

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-pk-confirm')) {
            return;
        }
        // Après tous les écouteurs : si l'envoi n'a pas été annulé, la demande part chez
        // Stripe, et un double clic ne doit pas la répéter.
        window.setTimeout(function () {
            if (event.defaultPrevented) {
                return;
            }
            var button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
            }
        }, 0);
    });
})();

/*
 * Demande une confirmation avant d'envoyer un formulaire qui engage
 * l'utilisateur ou qui supprime quelque chose (choisir une proposition,
 * annuler une commande, supprimer une alerte, abandonner un brouillon).
 * Le texte de la question est porté par l'attribut « data-pk-confirm ».
 *
 * Chargé une seule fois par le gabarit principal, pour toutes les pages :
 * un formulaire marqué « data-pk-confirm » est ainsi toujours protégé, sans
 * dépendre d'un script propre à sa page.
 */
(function () {
    'use strict';

    // Chargé deux fois, il poserait deux fois la question.
    if (window.pkConfirmSubmitReady) {
        return;
    }
    window.pkConfirmSubmitReady = true;

    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.dataset.pkConfirm) {
            return;
        }
        if (!window.confirm(form.dataset.pkConfirm)) {
            event.preventDefault();
        }
    });
})();

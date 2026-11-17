/*
 * Demande une confirmation avant d'envoyer un formulaire qui engage
 * l'utilisateur (choisir une proposition, annuler une commande).
 * Le texte de la question est porté par l'attribut « data-pk-confirm ».
 */
document.addEventListener('submit', function (event) {
    var form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.dataset.pkConfirm) {
        return;
    }
    if (!window.confirm(form.dataset.pkConfirm)) {
        event.preventDefault();
    }
});

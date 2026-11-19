// File d'envoi des e-mails : confirmation avant de valider ou d'annuler tout un lot.
(() => {
    document.querySelectorAll('form[data-confirm-batch]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirmBatch)) {
                event.preventDefault();
                return;
            }
            // Un second clic ne doit pas relancer l'envoi.
            form.querySelectorAll('button').forEach((button) => { button.disabled = true; });
        });
    });
})();

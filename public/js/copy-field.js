/*
 * Bouton « Copier » : <button data-pk-copy="#champ" data-pk-copied="Lien copié">.
 * Copie la valeur du champ visé, puis affiche la confirmation pendant 2 secondes.
 */
(function () {
    if (window.pkCopyFieldReady) return;
    window.pkCopyFieldReady = true;

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-pk-copy]');
        if (!button) return;
        var field = document.querySelector(button.getAttribute('data-pk-copy'));
        if (!field) return;

        var done = function () {
            var label = button.textContent;
            button.textContent = button.getAttribute('data-pk-copied') || 'Copié';
            setTimeout(function () { button.textContent = label; }, 2000);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(field.value).then(done, function () { field.select(); });
        } else {
            field.select();
            if (document.execCommand('copy')) done();
        }
    });
})();

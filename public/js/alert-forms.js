/*
 * Activer une alerte (« Me prévenir », cloche d'une offre) sans recharger la page.
 *
 * Le clic doit laisser une trace visible : le bouton passe aussitôt à son état
 * « alerte active », un message confirme l'action, et le nombre affiché sur
 * « Mes alertes » est mis à jour.
 *
 * Le formulaire de création placé dans une enveloppe « data-pk-alert » est
 * envoyé par ce script. Son état « actif » est écrit en Blade, dans le
 * <template data-pk-alert-done> de la même enveloppe : rien n'est fabriqué
 * ici. Sans script, ou si l'envoi échoue, le formulaire reste un formulaire
 * ordinaire.
 *
 * Chargé une seule fois par le gabarit principal, pour toutes les pages.
 */
(function () {
    'use strict';

    if (window.pkAlertFormsReady || !window.fetch || !window.FormData) {
        return;
    }
    window.pkAlertFormsReady = true;

    var toastTimer = null;

    function notify(message, isError) {
        // Le flux a déjà son message léger ; ailleurs, on en crée un.
        var toast = document.getElementById('pkToast') || document.getElementById('pkAlertToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'pkAlertToast';
            toast.className = 'pk-alert-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            document.body.appendChild(toast);
        }
        window.clearTimeout(toastTimer);
        toast.textContent = message;
        toast.dataset.type = isError ? 'error' : 'success';
        // Force le calcul du style de départ : un élément tout juste créé s'anime aussi.
        void toast.offsetWidth;
        toast.classList.add('is-visible');
        toastTimer = window.setTimeout(function () { toast.classList.remove('is-visible'); }, 4000);
    }

    function updateCounters(count) {
        document.querySelectorAll('[data-pk-alert-count]').forEach(function (badge) {
            var value = badge.querySelector('[data-pk-alert-count-value]') || badge;
            value.textContent = String(count);
            badge.hidden = count < 1;
        });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        var slot = form.closest('[data-pk-alert]');
        var done = slot ? slot.querySelector('template[data-pk-alert-done]') : null;
        // Seule la création passe par ici : l'arrêt d'une alerte (méthode DELETE,
        // avec confirmation) reste un envoi ordinaire.
        if (!slot || !done || form.querySelector('input[name="_method"]')) {
            return;
        }
        event.preventDefault();

        var button = form.querySelector('button[type="submit"]');
        if (button) {
            if (button.getAttribute('aria-busy') === 'true') {
                return;
            }
            button.setAttribute('aria-busy', 'true');
        }

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data || !data.ok || !data.active || !data.stop_url) {
                    throw new Error((data && data.message) || '');
                }
                slot.innerHTML = done.innerHTML.replace(/__STOP_URL__/g, data.stop_url);
                slot.classList.add('pk-alert-confirmed');
                if (typeof data.count === 'number') {
                    updateCounters(data.count);
                }
                notify(data.message || 'Alerte activée.');
            })
            .catch(function (error) {
                if (button) {
                    button.removeAttribute('aria-busy');
                }
                notify((error && error.message) || 'L’alerte n’a pas pu être activée. Réessayez.', true);
            });
    });
})();

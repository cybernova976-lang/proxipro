/*
 * Navigation entre les rubriques de « Mes demandes » (/home) sans recharger la page.
 *
 * Ce script est chargé par le gabarit principal pour tout membre connecté.
 * Il vivait auparavant dans le menu latéral : là où ce menu n'est pas affiché
 * (espace particulier, avec ses onglets), les liens « Mon compte » ou
 * « Historique et factures » appelaient une fonction qui n'existait pas.
 */
(function () {
    'use strict';

    var sections = ['overview', 'account', 'profile', 'profile-edit', 'settings', 'my-ads', 'messages', 'transactions', 'create-ad'];
    var isNavigating = false;

    function removeLoader() {
        var loader = document.getElementById('dashboardLoader');
        if (loader) loader.remove();
    }

    function markActive(section) {
        document.querySelectorAll('.menu-item[data-dashboard-section], .footer-link[data-dashboard-section]').forEach(function (item) {
            item.classList.remove('active');
        });
        var activeItem = document.querySelector('[data-dashboard-section="' + section + '"]');
        if (activeItem) activeItem.classList.add('active');
    }

    function showLoadError(contentArea, section) {
        contentArea.textContent = '';

        var box = document.createElement('div');
        box.className = 'container py-5 text-center';
        var alertBox = document.createElement('div');
        alertBox.className = 'alert alert-danger';
        alertBox.style.borderRadius = '14px';
        alertBox.appendChild(document.createTextNode('Erreur de chargement. '));

        var retry = document.createElement('a');
        retry.href = '/home#' + section;
        retry.style.fontWeight = '600';
        retry.textContent = 'Réessayer';
        retry.addEventListener('click', function (event) {
            event.preventDefault();
            dashboardNav(section);
        });

        alertBox.appendChild(retry);
        box.appendChild(alertBox);
        contentArea.appendChild(box);
    }

    function dashboardNav(section, restoreHistory) {
        if (!section || sections.indexOf(section) === -1) return;
        // Un seul chargement à la fois : évite les doubles clics.
        if (isNavigating) return;

        // Sur téléphone, le menu latéral se referme (il n'existe pas sur toutes les pages).
        if (window.innerWidth <= 1024 && typeof window.closeDashboardSidebar === 'function') {
            window.closeDashboardSidebar();
        }

        var contentArea = document.getElementById('dashboardContent');
        if (!contentArea) {
            // Hors de « Mes demandes » : on y va, la rubrique est lue dans l'adresse à l'arrivée.
            window.location.href = '/home#' + section;
            return;
        }

        markActive(section);

        var query = section === 'overview' ? window.location.search : '';
        if (!restoreHistory) history.pushState({ section: section }, '', '/home' + query + '#' + section);

        isNavigating = true;

        // Garde-fou : on libère le verrou si le chargement reste bloqué.
        var safetyTimer = setTimeout(function () {
            isNavigating = false;
            removeLoader();
        }, 15000);

        contentArea.style.position = 'relative';
        var loader = document.createElement('div');
        loader.id = 'dashboardLoader';
        loader.style.cssText = 'position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.7);display:flex;align-items:center;justify-content:center;z-index:10;min-height:200px;';
        loader.innerHTML = '<div style="text-align:center;"><div class="spinner-border text-primary" role="status" style="width:2.5rem;height:2.5rem;"></div><p style="margin-top:12px;color:#64748b;font-size:0.9rem;">Chargement...</p></div>';
        contentArea.appendChild(loader);

        fetch('/dashboard/' + encodeURIComponent(section) + query, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.text();
            })
            .then(function (html) {
                clearTimeout(safetyTimer);
                removeLoader();
                contentArea.innerHTML = html;

                // Les scripts d'un contenu inséré ne s'exécutent pas seuls : on les recrée.
                contentArea.querySelectorAll('script').forEach(function (oldScript) {
                    var newScript = document.createElement('script');
                    if (oldScript.src) {
                        newScript.src = oldScript.src;
                    } else {
                        newScript.textContent = oldScript.textContent;
                    }
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });

                window.scrollTo({ top: 0, behavior: 'smooth' });
                isNavigating = false;
            })
            .catch(function () {
                clearTimeout(safetyTimer);
                removeLoader();
                showLoadError(contentArea, section);
                isNavigating = false;
            });
    }

    window.dashboardNav = dashboardNav;

    function sectionFromHash() {
        return window.location.hash.replace('#', '');
    }

    // Boutons Précédent / Suivant du navigateur.
    window.addEventListener('popstate', function (event) {
        if (!document.getElementById('dashboardContent')) return;

        var section = (event.state && event.state.section) || sectionFromHash() || 'overview';
        if (sections.indexOf(section) !== -1) {
            isNavigating = false;
            dashboardNav(section, true);
        }
    });

    // Arrivée sur /home#rubrique : on ouvre la rubrique demandée.
    function openSectionFromAddress() {
        if (!document.getElementById('dashboardContent')) return;

        var section = sectionFromHash();
        if (section !== 'overview' && sections.indexOf(section) !== -1) {
            dashboardNav(section, true);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', openSectionFromAddress);
    } else {
        openSectionFromAddress();
    }
})();

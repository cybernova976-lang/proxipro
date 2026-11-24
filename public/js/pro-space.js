// Espace Pro : photo du compte qui ne se charge pas, et menu latéral sur tablette et téléphone.
(() => {
    // Photo absente ou illisible : on la retire, les initiales placées dessous restent visibles.
    // Le script est différé : une photo déjà en échec est repérée par sa largeur nulle.
    document.querySelectorAll('[data-pro-avatar-photo]').forEach((photo) => {
        const dropPhoto = () => photo.remove();
        if (photo.complete && photo.naturalWidth === 0) {
            dropPhoto();
        } else {
            photo.addEventListener('error', dropPhoto, { once: true });
        }
    });

    const sidebar = document.getElementById('proSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('sidebarToggle');

    if (!sidebar || !toggle) {
        return;
    }

    const setProSidebarOpen = (open) => {
        sidebar.classList.toggle('show', open);
        overlay?.classList.toggle('show', open);
        document.body.classList.toggle('pro-sidebar-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        overlay?.setAttribute('aria-hidden', String(!open));
    };

    toggle.addEventListener('click', () => setProSidebarOpen(!sidebar.classList.contains('show')));
    overlay?.addEventListener('click', () => setProSidebarOpen(false));

    // Bouton de fermeture dans l'en-tête du tiroir : il rend la main au bouton « Menu ».
    sidebar.querySelector('[data-pro-sidebar-close]')?.addEventListener('click', () => {
        setProSidebarOpen(false);
        toggle.focus();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar.classList.contains('show')) {
            setProSidebarOpen(false);
            toggle.focus();
        }
    });
})();

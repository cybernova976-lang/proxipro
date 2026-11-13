// Espace Pro : ouverture du menu latéral sur tablette et téléphone.
(() => {
    const sidebar = document.getElementById('proSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('sidebarToggle');

    if (!sidebar || !toggle) {
        return;
    }

    const setProSidebarOpen = (open) => {
        sidebar.classList.toggle('show', open);
        overlay?.classList.toggle('show', open);
        toggle.setAttribute('aria-expanded', String(open));
        overlay?.setAttribute('aria-hidden', String(!open));
    };

    toggle.addEventListener('click', () => setProSidebarOpen(!sidebar.classList.contains('show')));
    overlay?.addEventListener('click', () => setProSidebarOpen(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar.classList.contains('show')) {
            setProSidebarOpen(false);
            toggle.focus();
        }
    });
})();

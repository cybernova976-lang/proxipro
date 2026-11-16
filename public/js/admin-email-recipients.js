// Studio e-mail : tri et sélection des destinataires joignables.
(() => {
    const list = document.getElementById('recipientList');
    if (!list) return;

    const rows = [...list.querySelectorAll('.recipient')];
    const audience = document.getElementById('audience');
    const search = document.getElementById('recipientSearch');
    const filters = [...document.querySelectorAll('[data-recipient-filter]')];
    const counter = document.getElementById('selectionCount');
    const empty = document.getElementById('recipientEmpty');
    const box = (row) => row.querySelector('input');
    const plural = (count, one, many) => count + ' ' + (count > 1 ? many : one);
    let filter = 'tous';

    // Affiche la liste choisie, réduite par la recherche.
    function show() {
        const query = search.value.toLocaleLowerCase();
        let shown = 0;
        rows.forEach((row) => {
            const hidden = (filter !== 'tous' && row.dataset.type !== filter)
                || !row.textContent.toLocaleLowerCase().includes(query);
            row.classList.toggle('d-none', hidden);
            shown += hidden ? 0 : 1;
        });
        empty.classList.toggle('d-none', shown > 0);
    }

    // Le décompte porte sur toute la sélection, y compris les lignes masquées.
    function count() {
        const checked = rows.filter((row) => box(row).checked);
        const professionals = checked.filter((row) => row.dataset.type === 'professionnels').length;
        const individuals = checked.length - professionals;

        // « all » : le serveur reprend tous les comptes joignables au moment de la préparation.
        audience.value = rows.length > 0 && checked.length === rows.length ? 'all' : 'selected';

        if (checked.length === 0) {
            counter.textContent = 'Aucun destinataire sélectionné';
            return;
        }
        const parts = [];
        if (individuals > 0) parts.push(plural(individuals, 'particulier', 'particuliers'));
        if (professionals > 0) parts.push(plural(professionals, 'professionnel', 'professionnels'));
        counter.textContent = plural(checked.length, 'destinataire sélectionné', 'destinataires sélectionnés')
            + ' : ' + parts.join(' et ');
    }

    filters.forEach((button) => button.addEventListener('click', () => {
        filter = button.dataset.recipientFilter;
        filters.forEach((other) => {
            other.classList.toggle('active', other === button);
            other.setAttribute('aria-pressed', other === button ? 'true' : 'false');
        });
        show();
    }));

    document.getElementById('selectAll').addEventListener('click', () => {
        rows.filter((row) => !row.classList.contains('d-none')).forEach((row) => { box(row).checked = true; });
        count();
    });
    document.getElementById('selectNone').addEventListener('click', () => {
        rows.forEach((row) => { box(row).checked = false; });
        count();
    });
    rows.forEach((row) => box(row).addEventListener('change', count));
    search.addEventListener('input', show);

    show();
    count();
})();

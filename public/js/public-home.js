(() => {
    'use strict';
    const tabs = [...document.querySelectorAll('.home-journey-tabs [role="tab"]')];
    const tablist = document.querySelector('.home-journey-tabs');
    if (tablist && tabs.length) {
        const select = (selected, focus = false) => {
            tabs.forEach(tab => {
                const active = tab === selected;
                tab.setAttribute('aria-selected', String(active));
                tab.tabIndex = active ? 0 : -1;
                const panel = document.getElementById(tab.getAttribute('aria-controls'));
                panel.hidden = !active;
                panel.setAttribute('role', 'tabpanel');
                panel.setAttribute('aria-labelledby', tab.id);
                panel.tabIndex = 0;
            });
            if (focus) selected.focus();
        };
        tablist.hidden = false;
        document.getElementById('comment-ca-marche').classList.add('home-journeys-enhanced');
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => select(tab));
            tab.addEventListener('keydown', event => {
                let next;
                if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
                if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
                if (event.key === 'Home') next = 0;
                if (event.key === 'End') next = tabs.length - 1;
                if (next === undefined) return;
                event.preventDefault();
                select(tabs[next], true);
            });
        });
        select(tabs[0]);
    }

    // Close the mobile menu after local navigation; no impact on form submissions.
    document.querySelectorAll('#mobileMenu a[href^="#"]').forEach(link => {
        link.addEventListener('click', () => {
            if (document.getElementById('navBurger')?.getAttribute('aria-expanded') === 'true') {
                window.toggleMobileMenu();
            }
        });
    });

    const cta = document.getElementById('home-mobile-cta');
    const hero = document.getElementById('home-hero');
    const footer = document.getElementById('home-footer');
    if (cta && hero && footer && 'IntersectionObserver' in window) {
        let heroVisible = true;
        let footerVisible = false;
        const mobile = window.matchMedia('(max-width: 767px)');
        const update = () => { cta.hidden = !mobile.matches || heroVisible || footerVisible; };
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.target === hero) heroVisible = entry.isIntersecting;
                if (entry.target === footer) footerVisible = entry.isIntersecting;
            });
            update();
        });
        observer.observe(hero);
        observer.observe(footer);
        mobile.addEventListener('change', update);
    }
})();

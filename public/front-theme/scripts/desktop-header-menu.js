(() => {
    const init = () => {
        const root = document.querySelector('[data-mobile-menu-root]');
        if (!root || root.dataset.menuInit === '1') {
            return;
        }
        root.dataset.menuInit = '1';

        const panel = root.querySelector('[data-mobile-menu-panel]');
        const overlay = root.querySelector('[data-mobile-menu-close]');
        const openButtons = document.querySelectorAll('[data-mobile-menu-open]');
        const closeButtons = root.querySelectorAll('[data-mobile-menu-close]');

        const closeMenu = () => {
            root.classList.add('pointer-events-none');
            overlay?.classList.remove('opacity-100');
            overlay?.classList.add('opacity-0');
            panel?.classList.add('-translate-x-full');
            panel?.classList.remove('translate-x-0');
            document.body.classList.remove('overflow-hidden');
        };

        const openMenu = (event) => {
            if (event) {
                event.preventDefault();
            }
            root.classList.remove('pointer-events-none');
            overlay?.classList.remove('opacity-0');
            overlay?.classList.add('opacity-100');
            panel?.classList.remove('-translate-x-full');
            panel?.classList.add('translate-x-0');
            document.body.classList.add('overflow-hidden');
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', openMenu);
            button.addEventListener('touchend', openMenu, { passive: false });
        });
        closeButtons.forEach((button) => button.addEventListener('click', closeMenu));
        root.querySelectorAll('summary [data-mobile-nav-link]').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.stopPropagation();
            });
        });
        root.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
    window.addEventListener('pageshow', init);
})();

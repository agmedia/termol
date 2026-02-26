(() => {
    const init = function () {
        if (window.__headerSearchPanelInit === true) {
            return;
        }
        window.__headerSearchPanelInit = true;

    const panel = document.querySelector('[data-header-search-panel]');
    const toggles = document.querySelectorAll('[data-header-search-toggle]');
    const input = document.querySelector('[data-header-search-input]');

    if (!panel || !toggles.length) {
        return;
    }

    let isOpen = false;

    const isMobileViewport = function () {
        return window.matchMedia('(max-width: 1023px)').matches;
    };

    const ensurePanelVisible = function () {
        if (!isMobileViewport()) {
            return;
        }

        const panelTop = panel.getBoundingClientRect().top;
        const shouldScrollToTop = window.scrollY > 24 && panelTop < 0;

        if (shouldScrollToTop) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    const openPanel = function () {
        panel.classList.remove('max-h-0', 'opacity-0', 'pointer-events-none');
        panel.classList.add('max-h-40', 'opacity-100');
        isOpen = true;

        ensurePanelVisible();

        if (input) {
            window.setTimeout(function () {
                input.focus();
            }, isMobileViewport() ? 260 : 120);
        }
    };

    const closePanel = function () {
        panel.classList.add('max-h-0', 'opacity-0', 'pointer-events-none');
        panel.classList.remove('max-h-40', 'opacity-100');
        isOpen = false;
    };

    toggles.forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            if (isOpen) {
                closePanel();
                return;
            }
            openPanel();
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && isOpen) {
            closePanel();
        }
    });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
        return;
    }

    init();
})();

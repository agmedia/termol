(() => {
    const init = function () {
        if (window.__productCardOverlayInit === true) {
            return;
        }
        window.__productCardOverlayInit = true;
    const closeCardOverlay = function (card) {
        const panel = card.querySelector('[data-card-overlay]');
        const toggle = card.querySelector('[data-card-overlay-toggle]');
        if (!panel || !toggle) {
            return;
        }

        panel.classList.remove('opacity-100', 'pointer-events-auto', 'md:opacity-100', 'md:pointer-events-auto');
        panel.classList.add('opacity-0', 'pointer-events-none', 'md:opacity-0', 'md:pointer-events-none');
        toggle.setAttribute('aria-expanded', 'false');
    };

    const openCardOverlay = function (card) {
        const panel = card.querySelector('[data-card-overlay]');
        const toggle = card.querySelector('[data-card-overlay-toggle]');
        if (!panel || !toggle) {
            return;
        }

        panel.classList.remove('opacity-0', 'pointer-events-none', 'md:opacity-0', 'md:pointer-events-none');
        panel.classList.add('opacity-100', 'pointer-events-auto', 'md:opacity-100', 'md:pointer-events-auto');
        toggle.setAttribute('aria-expanded', 'true');
    };

    const bind = function (scope) {
        const cards = (scope || document).querySelectorAll('[data-product-card]');
        if (!cards.length) {
            return;
        }

        cards.forEach(function (card) {
        if (card.dataset.overlayInit === '1') {
            return;
        }
        card.dataset.overlayInit = '1';
        const toggle = card.querySelector('[data-card-overlay-toggle]');
        const panel = card.querySelector('[data-card-overlay]');
        if (!toggle || !panel) {
            return;
        }

        toggle.addEventListener('click', function () {
            const isOpen = toggle.getAttribute('aria-expanded') === 'true';

            document.querySelectorAll('[data-product-card]').forEach(function (otherCard) {
                if (otherCard !== card) {
                    closeCardOverlay(otherCard);
                }
            });

            if (isOpen) {
                closeCardOverlay(card);
                return;
            }

            openCardOverlay(card);
        });
    });
    };

    bind(document);
    document.addEventListener('catalog:items-appended', function (event) {
        bind(event.detail?.container || document);
    });

    document.addEventListener('product-card-overlay:close-all', function () {
        document.querySelectorAll('[data-product-card]').forEach(function (card) {
            closeCardOverlay(card);
        });
    });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
        return;
    }

    init();
})();

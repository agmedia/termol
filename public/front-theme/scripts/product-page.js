(() => {
    const ready = function (callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }

        callback();
    };

    const bindSwatches = function () {
        document.querySelectorAll('[data-color-variant-swatch]').forEach(function (swatch) {
            const imageUrl = String(swatch.dataset.swatchImage || '').trim();
            const cssText = String(swatch.dataset.swatchStyle || '').trim();

            if (imageUrl !== '') {
                swatch.style.backgroundImage = 'url("' + imageUrl.replace(/"/g, '\\"') + '")';
                return;
            }

            if (cssText !== '') {
                swatch.style.cssText += ';' + cssText;
            }
        });
    };

    const bindComments = function () {
        const commentsAnchor = document.getElementById('product-comments');
        const scrollToComments = function () {
            if (!commentsAnchor) {
                return;
            }

            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            commentsAnchor.scrollIntoView({
                behavior: reducedMotion ? 'auto' : 'smooth',
                block: 'start',
            });
        };

        if (commentsAnchor && window.location.hash === '#product-comments') {
            window.setTimeout(scrollToComments, 60);
        }

        window.addEventListener('hashchange', function () {
            if (window.location.hash === '#product-comments') {
                scrollToComments();
            }
        });

        const toggle = document.querySelector('[data-comment-form-toggle]');
        const panel = document.querySelector('[data-comment-form-panel]');
        if (!toggle || !panel) {
            return;
        }

        toggle.addEventListener('click', function () {
            const isClosed = panel.classList.contains('hidden') || panel.classList.contains('d-none');
            panel.classList.toggle('hidden', !isClosed);
            panel.classList.toggle('d-none', !isClosed);
            const isOpen = isClosed;
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    };

    const bindSizeGuide = function () {
        const modal = document.querySelector('[data-size-guide-modal]');
        if (!modal) {
            return;
        }

        const openButtons = document.querySelectorAll('[data-size-guide-open]');
        const closeButtons = modal.querySelectorAll('[data-size-guide-close]');

        const openModal = function () {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        };

        const closeModal = function () {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
        };

        openButtons.forEach(function (button) {
            button.addEventListener('click', openModal);
        });

        closeButtons.forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
                closeModal();
            }
        });
    };

    const initProductCarousels = function () {
        if (typeof window.Splide !== 'function') {
            return false;
        }

        document.querySelectorAll('[data-related-products-splide]').forEach(function (element) {
            if (element.dataset.splideReady === '1') {
                return;
            }

            const count = element.querySelectorAll('.splide__slide').length;
            if (count === 0) {
                return;
            }

            element.dataset.splideReady = '1';
            const desktopPerPage = Math.min(
                Math.max(1, Number.parseInt(element.dataset.desktopCols || '5', 10) || 5),
                count
            );
            const mobilePerPage = Math.min(
                Math.max(1, Number.parseInt(element.dataset.mobileCols || '2', 10) || 2),
                count
            );

            new window.Splide(element, {
                type: count > desktopPerPage ? 'loop' : 'slide',
                perPage: desktopPerPage,
                perMove: 1,
                gap: '0rem',
                drag: count > 1,
                snap: true,
                pagination: false,
                arrows: count > desktopPerPage,
                updateOnMove: true,
                speed: 520,
                breakpoints: {
                    1280: {
                        perPage: Math.min(4, count),
                        arrows: count > Math.min(4, count),
                    },
                    1024: {
                        perPage: Math.min(3, count),
                        arrows: count > Math.min(3, count),
                    },
                    860: {
                        perPage: Math.min(2, count),
                        arrows: count > Math.min(2, count),
                    },
                    640: {
                        perPage: mobilePerPage,
                        arrows: count > mobilePerPage,
                    },
                },
            }).mount();
        });

        return true;
    };

    const initWithRetry = function (initializer) {
        if (initializer()) {
            return;
        }

        let attempts = 0;
        const timer = window.setInterval(function () {
            attempts += 1;
            if (initializer() || attempts > 40) {
                window.clearInterval(timer);
            }
        }, 120);
    };

    ready(function () {
        bindSwatches();
        bindComments();
        bindSizeGuide();
        initWithRetry(initProductCarousels);
    });
})();

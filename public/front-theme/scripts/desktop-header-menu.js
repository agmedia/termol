(() => {
    const init = () => {
        const root = document.querySelector('[data-mobile-menu-root]');
        if (!root) {
            document.body.classList.remove('overflow-hidden');
            document.body.classList.remove('desktop-mobile-menu-open');
            return;
        }

        const panel = root.querySelector('[data-mobile-menu-panel]');
        const overlay = root.querySelector('[data-mobile-menu-close]');
        const openButtons = document.querySelectorAll('[data-mobile-menu-open]');
        const closeButtons = root.querySelectorAll('[data-mobile-menu-close]');
        const accordionSections = root.querySelectorAll('[data-mobile-menu-accordion]');
        const menuLinks = Array.from(root.querySelectorAll('a[href]'));
        const accordionStateKey = 'desktop-mobile-menu-accordion-state';
        let isRestoringAccordionState = false;

        const forceClosedState = () => {
            root.classList.add('pointer-events-none');
            root.dataset.menuOpen = '0';
            overlay?.classList.remove('opacity-100');
            overlay?.classList.add('opacity-0');
            panel?.classList.add('-translate-x-full');
            panel?.classList.remove('translate-x-0');
            document.body.classList.remove('overflow-hidden');
            document.body.classList.remove('desktop-mobile-menu-open');
        };

        forceClosedState();

        if (root.dataset.menuInit === '1') {
            return;
        }
        root.dataset.menuInit = '1';

        const normalizePath = (href) => {
            try {
                const url = new URL(href, window.location.origin);
                if (url.origin !== window.location.origin) {
                    return null;
                }

                const pathname = url.pathname.replace(/\/+$/, '');
                return pathname === '' ? '/' : pathname;
            } catch {
                return null;
            }
        };

        const slugify = (value) => value
            .toLowerCase()
            .trim()
            .replace(/\s+/g, '-')
            .replace(/[^a-z0-9\-/_]/g, '');

        const getSectionDepth = (link) => {
            let depth = 0;
            let currentSection = link.closest('details');

            while (currentSection) {
                depth += 1;
                currentSection = currentSection.parentElement?.closest('details') ?? null;
            }

            return depth;
        };

        const getSectionKey = (section) => {
            if (section.dataset.menuSectionKey) {
                return section.dataset.menuSectionKey;
            }

            const link = section.querySelector(':scope > summary [data-mobile-nav-link]');
            const path = link ? normalizePath(link.href) : null;
            const fallbackLabel = link ? slugify(link.textContent || '') : '';
            const key = path || fallbackLabel || '';

            if (key) {
                section.dataset.menuSectionKey = key;
            }

            return key;
        };

        const readAccordionState = () => {
            try {
                const raw = sessionStorage.getItem(accordionStateKey);
                if (!raw) {
                    return {};
                }

                const parsed = JSON.parse(raw);
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch {
                return {};
            }
        };

        const writeAccordionState = (state) => {
            try {
                sessionStorage.setItem(accordionStateKey, JSON.stringify(state));
            } catch {
                // Ignore storage failures and keep menu functional.
            }
        };

        const collapseSection = (section) => {
            section.querySelectorAll('[data-mobile-menu-accordion][open]').forEach((nestedSection) => {
                nestedSection.open = false;
            });
            section.open = false;
        };

        const getSiblingSections = (section) => {
            const directParent = section.parentElement;
            if (!directParent) {
                return [];
            }

            const container = directParent.tagName === 'LI' ? directParent.parentElement : directParent;
            if (!container) {
                return [];
            }

            return Array.from(container.children).flatMap((child) => {
                if (child.matches('[data-mobile-menu-accordion]')) {
                    return [child];
                }

                if (child.tagName === 'LI') {
                    const nestedSection = child.querySelector(':scope > [data-mobile-menu-accordion]');
                    return nestedSection ? [nestedSection] : [];
                }

                return [];
            });
        };

        const restoreAccordionState = () => {
            const accordionState = readAccordionState();

            isRestoringAccordionState = true;
            accordionSections.forEach((section) => {
                const key = getSectionKey(section);
                section.open = key ? Boolean(accordionState[key]) : false;
            });
            isRestoringAccordionState = false;
        };

        const findBestLinkForPath = (path) => menuLinks
            .filter((link) => normalizePath(link.href) === path)
            .sort((firstLink, secondLink) => getSectionDepth(secondLink) - getSectionDepth(firstLink))[0] ?? null;

        const clearActiveState = () => {
            root.querySelectorAll('.desktop-mobile-menu-row-active').forEach((row) => {
                row.classList.remove('desktop-mobile-menu-row-active');
            });

            root.querySelectorAll('.desktop-mobile-menu-link-active').forEach((link) => {
                link.classList.remove('desktop-mobile-menu-link-active');
                if (link.getAttribute('aria-current') === 'page') {
                    link.removeAttribute('aria-current');
                }
            });
        };

        const revealLinkPath = (link) => {
            if (!link) {
                return;
            }

            accordionSections.forEach((section) => {
                section.open = false;
            });

            const sectionsToOpen = [];
            let currentSection = link.closest('details');

            while (currentSection) {
                sectionsToOpen.unshift(currentSection);
                currentSection = currentSection.parentElement?.closest('details') ?? null;
            }

            sectionsToOpen.forEach((section) => {
                section.open = true;
            });

            clearActiveState();
            link.classList.add('desktop-mobile-menu-link-active');
            link.setAttribute('aria-current', 'page');
            link.closest('.desktop-mobile-menu-row')?.classList.add('desktop-mobile-menu-row-active');
        };

        const syncMenuState = () => {
            const currentPath = normalizePath(window.location.href);
            const currentLink = currentPath ? findBestLinkForPath(currentPath) : null;

            restoreAccordionState();
            clearActiveState();

            if (!currentLink) {
                return;
            }

            const accordionState = readAccordionState();
            const sectionsToReveal = [];
            let currentSection = currentLink.closest('details');

            while (currentSection) {
                sectionsToReveal.unshift(currentSection);
                currentSection = currentSection.parentElement?.closest('details') ?? null;
            }

            isRestoringAccordionState = true;
            sectionsToReveal.forEach((section) => {
                const key = getSectionKey(section);
                if (key && !Object.prototype.hasOwnProperty.call(accordionState, key)) {
                    section.open = true;
                }
            });
            isRestoringAccordionState = false;

            clearActiveState();
            revealLinkPath(currentLink);
        };

        const closeMenu = () => {
            forceClosedState();
        };

        const openMenu = (event) => {
            if (event) {
                event.preventDefault();
            }
            root.classList.remove('pointer-events-none');
            root.dataset.menuOpen = '1';
            syncMenuState();
            overlay?.classList.remove('opacity-0');
            overlay?.classList.add('opacity-100');
            panel?.classList.remove('-translate-x-full');
            panel?.classList.add('translate-x-0');
            document.body.classList.add('overflow-hidden');
            document.body.classList.add('desktop-mobile-menu-open');
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', openMenu);
            button.addEventListener('touchend', openMenu, { passive: false });
        });
        closeButtons.forEach((button) => button.addEventListener('click', closeMenu));
        accordionSections.forEach((section) => {
            section.addEventListener('toggle', () => {
                if (isRestoringAccordionState) {
                    return;
                }

                const key = getSectionKey(section);
                if (key) {
                    const accordionState = readAccordionState();
                    accordionState[key] = section.open;
                    writeAccordionState(accordionState);
                }

                if (!section.open) {
                    return;
                }

                getSiblingSections(section).forEach((otherSection) => {
                    if (otherSection !== section) {
                        collapseSection(otherSection);
                    }
                });
            });
        });
        root.querySelectorAll('summary [data-mobile-nav-link]').forEach((link) => {
            link.addEventListener('click', (event) => {
                event.stopPropagation();
            });
        });
        menuLinks.forEach((link) => {
            link.addEventListener('click', () => {
                closeMenu();
            });
        });

        syncMenuState();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
    window.addEventListener('pageshow', init);
})();

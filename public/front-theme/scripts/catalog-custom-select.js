(() => {
    const DESKTOP_MEDIA = '(min-width: 1025px)';

    const closeAll = () => {
        document.querySelectorAll('[data-custom-select].is-open').forEach((node) => {
            node.classList.remove('is-open');
            const button = node.querySelector('[data-custom-select-button]');
            if (button) {
                button.setAttribute('aria-expanded', 'false');
            }
        });
    };

    const setLabel = (select, button) => {
        const selected = select.options[select.selectedIndex];
        button.textContent = selected ? selected.textContent || '' : '';
        const isPlaceholder = !selected || String(selected.value || '').trim() === '';
        button.classList.toggle('is-placeholder', isPlaceholder);
    };

    const buildCustomSelect = (select) => {
        if (select.dataset.customSelectReady === '1') {
            return;
        }

        const container = select.parentElement;
        if (!container) {
            return;
        }

        const custom = document.createElement('div');
        custom.className = 'catalog-filter-custom';
        custom.setAttribute('data-custom-select', '');
        if (select.classList.contains('catalog-filter-sort')) {
            custom.classList.add('is-sort');
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'catalog-filter-custom-button';
        button.setAttribute('data-custom-select-button', '');
        button.setAttribute('aria-expanded', 'false');

        const list = document.createElement('div');
        list.className = 'catalog-filter-custom-list';
        list.setAttribute('data-custom-select-list', '');

        Array.from(select.options).forEach((option) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'catalog-filter-custom-item';
            item.textContent = option.textContent || '';
            item.dataset.value = option.value;
            if (String(option.value || '').trim() === '') {
                item.classList.add('is-placeholder');
            }
            if (option.disabled) {
                item.disabled = true;
            }
            if (option.selected) {
                item.classList.add('is-selected');
            }

            item.addEventListener('click', () => {
                select.value = option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));

                list.querySelectorAll('.catalog-filter-custom-item.is-selected').forEach((row) => {
                    row.classList.remove('is-selected');
                });
                item.classList.add('is-selected');

                setLabel(select, button);
                custom.classList.remove('is-open');
                button.setAttribute('aria-expanded', 'false');
            });

            list.appendChild(item);
        });

        setLabel(select, button);

        button.addEventListener('click', (event) => {
            event.preventDefault();
            const opening = !custom.classList.contains('is-open');
            closeAll();
            custom.classList.toggle('is-open', opening);
            button.setAttribute('aria-expanded', opening ? 'true' : 'false');
        });

        select.addEventListener('change', () => {
            setLabel(select, button);
            list.querySelectorAll('.catalog-filter-custom-item').forEach((row) => {
                row.classList.toggle('is-selected', row.dataset.value === select.value);
            });
        });

        container.appendChild(custom);
        custom.appendChild(button);
        custom.appendChild(list);

        select.classList.add('catalog-filter-native-hidden');
        select.dataset.customSelectReady = '1';
    };

    const destroyCustomSelect = (select) => {
        const container = select.parentElement;
        if (!container) {
            return;
        }

        const custom = container.querySelector('[data-custom-select]');
        if (custom) {
            custom.remove();
        }

        select.classList.remove('catalog-filter-native-hidden');
        select.dataset.customSelectReady = '0';
    };

    const applyMode = () => {
        const desktop = window.matchMedia(DESKTOP_MEDIA).matches;
        document.querySelectorAll('[data-desktop-filter-form] .catalog-filter-select').forEach((select) => {
            if (desktop) {
                buildCustomSelect(select);
            } else {
                destroyCustomSelect(select);
            }
        });
        if (!desktop) {
            closeAll();
        }
    };

    const init = () => {
        if (document.documentElement.dataset.catalogCustomSelectInit === '1') {
            applyMode();
            return;
        }

        document.documentElement.dataset.catalogCustomSelectInit = '1';
        applyMode();

        window.addEventListener('resize', applyMode);
        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-custom-select]')) {
                closeAll();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeAll();
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
    window.addEventListener('pageshow', init);
})();

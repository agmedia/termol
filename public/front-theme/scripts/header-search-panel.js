(() => {
    const MIN_QUERY_LENGTH = 2;
    const DEBOUNCE_MS = 180;

    const init = function () {
        if (window.__headerSearchPanelInit === true) {
            return;
        }
        window.__headerSearchPanelInit = true;

        const panel = document.querySelector('[data-header-search-panel]');
        const toggles = document.querySelectorAll('[data-header-search-toggle]');
        const form = document.querySelector('[data-header-search-form]');
        const input = document.querySelector('[data-header-search-input]');

        if (!panel || !toggles.length || !form || !input) {
            return;
        }

        const suggestions = form.querySelector('[data-header-search-suggestions]');
        const suggestionsMeta = form.querySelector('[data-header-search-suggestions-meta]');
        const suggestionsList = form.querySelector('[data-header-search-suggestions-list]');
        const loadingState = form.querySelector('[data-header-search-loading]');
        const emptyState = form.querySelector('[data-header-search-empty]');
        const footer = form.querySelector('[data-header-search-footer]');
        const viewAllLink = form.querySelector('[data-header-search-view-all]');

        const autocompleteEnabled = form.dataset.autocompleteEnabled === '1'
            && !!form.dataset.autocompleteEndpoint
            && !!suggestions
            && !!suggestionsMeta
            && !!suggestionsList
            && !!loadingState
            && !!emptyState
            && !!footer
            && !!viewAllLink;

        let isOpen = false;
        let debounceId = 0;
        let activeIndex = -1;
        let abortController = null;
        let resultLinks = [];

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

        const resetActiveLink = function () {
            resultLinks.forEach((link, index) => {
                link.classList.toggle('is-active', index === activeIndex);
            });
        };

        const closeSuggestions = function () {
            if (!autocompleteEnabled) {
                return;
            }

            if (abortController) {
                abortController.abort();
                abortController = null;
            }

            activeIndex = -1;
            resultLinks = [];
            suggestions.hidden = true;
            suggestionsList.innerHTML = '';
            suggestionsMeta.textContent = '';
            loadingState.hidden = true;
            emptyState.hidden = true;
            footer.hidden = true;
        };

        const setMetaLabel = function (template, valueMap) {
            let label = String(template || '');
            Object.entries(valueMap).forEach(([key, value]) => {
                label = label.replace(key, value);
            });
            return label;
        };

        const buildSuggestionRow = function (item, index) {
            const link = document.createElement('a');
            link.href = item.url || '#';
            link.className = 'header-search-suggestion';
            link.dataset.headerSearchSuggestion = '';
            link.dataset.index = String(index);

            const thumb = document.createElement('span');
            thumb.className = 'header-search-suggestion-thumb';
            if (item.image_url) {
                const image = document.createElement('img');
                image.src = item.image_url;
                image.alt = item.name || '';
                image.loading = 'lazy';
                thumb.appendChild(image);
            }

            const copy = document.createElement('span');
            copy.className = 'header-search-suggestion-copy';

            const title = document.createElement('span');
            title.className = 'header-search-suggestion-title';
            title.textContent = item.name || '';
            copy.appendChild(title);

            const sku = document.createElement('span');
            sku.className = 'header-search-suggestion-sku';
            sku.textContent = item.sku || '';
            copy.appendChild(sku);

            const prices = document.createElement('span');
            prices.className = 'header-search-suggestion-prices';

            const currentPrice = document.createElement('span');
            currentPrice.className = 'header-search-suggestion-price';
            currentPrice.textContent = item.price || '';
            prices.appendChild(currentPrice);

            if (item.has_discount && item.old_price) {
                const oldPrice = document.createElement('span');
                oldPrice.className = 'header-search-suggestion-old-price';
                oldPrice.textContent = item.old_price;
                prices.appendChild(oldPrice);
            }

            link.appendChild(thumb);
            link.appendChild(copy);
            link.appendChild(prices);

            link.addEventListener('mouseenter', function () {
                activeIndex = index;
                resetActiveLink();
            });

            return link;
        };

        const renderLoading = function () {
            if (!autocompleteEnabled) {
                return;
            }

            suggestions.hidden = false;
            suggestionsMeta.textContent = '';
            suggestionsList.innerHTML = '';
            loadingState.textContent = form.dataset.autocompleteLoadingLabel || '';
            loadingState.hidden = false;
            emptyState.hidden = true;
            footer.hidden = true;
            activeIndex = -1;
            resultLinks = [];
        };

        const renderResults = function (payload, query) {
            if (!autocompleteEnabled) {
                return;
            }

            suggestions.hidden = false;
            loadingState.hidden = true;
            suggestionsList.innerHTML = '';
            activeIndex = -1;

            const total = Number(payload.total || 0);
            const items = Array.isArray(payload.items) ? payload.items : [];

            if (items.length === 0) {
                suggestionsMeta.textContent = '';
                emptyState.textContent = setMetaLabel(form.dataset.autocompleteEmptyLabel, {
                    '__QUERY__': query,
                });
                emptyState.hidden = false;
                footer.hidden = true;
                resultLinks = [];
                return;
            }

            emptyState.hidden = true;
            suggestionsMeta.textContent = setMetaLabel(form.dataset.autocompleteResultsLabel, {
                '__COUNT__': String(total),
            });

            items.forEach((item, index) => {
                suggestionsList.appendChild(buildSuggestionRow(item, index));
            });

            resultLinks = Array.from(suggestionsList.querySelectorAll('[data-header-search-suggestion]'));
            footer.hidden = false;
            viewAllLink.textContent = form.dataset.autocompleteViewAllLabel || '';
            viewAllLink.href = payload.search_url || `${form.action}?q=${encodeURIComponent(query)}`;
        };

        const requestAutocomplete = function () {
            if (!autocompleteEnabled || !isOpen) {
                return;
            }

            const query = input.value.trim();
            if (query.length < MIN_QUERY_LENGTH) {
                closeSuggestions();
                return;
            }

            renderLoading();

            if (abortController) {
                abortController.abort();
            }

            abortController = new AbortController();
            const endpoint = new URL(form.dataset.autocompleteEndpoint, window.location.origin);
            endpoint.searchParams.set('q', query);

            window.fetch(endpoint.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                signal: abortController.signal,
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`Autocomplete request failed with ${response.status}`);
                    }

                    return response.json();
                })
                .then((payload) => {
                    if (input.value.trim() !== query) {
                        return;
                    }

                    renderResults(payload, query);
                })
                .catch((error) => {
                    if (error && error.name === 'AbortError') {
                        return;
                    }

                    closeSuggestions();
                })
                .finally(() => {
                    abortController = null;
                });
        };

        const queueAutocomplete = function () {
            if (!autocompleteEnabled) {
                return;
            }

            if (debounceId) {
                window.clearTimeout(debounceId);
            }

            debounceId = window.setTimeout(requestAutocomplete, DEBOUNCE_MS);
        };

        const openPanel = function () {
            panel.classList.add('is-open');
            isOpen = true;

            ensurePanelVisible();

            window.setTimeout(function () {
                input.focus();
                if (autocompleteEnabled && input.value.trim().length >= MIN_QUERY_LENGTH) {
                    requestAutocomplete();
                }
            }, isMobileViewport() ? 260 : 120);
        };

        const closePanel = function () {
            panel.classList.remove('is-open');
            closeSuggestions();
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

        input.addEventListener('focus', function () {
            if (!isOpen) {
                openPanel();
                return;
            }

            if (autocompleteEnabled && input.value.trim().length >= MIN_QUERY_LENGTH) {
                requestAutocomplete();
            }
        });

        input.addEventListener('input', function () {
            queueAutocomplete();
        });

        input.addEventListener('keydown', function (event) {
            if (!autocompleteEnabled || suggestions.hidden || resultLinks.length === 0) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                activeIndex = Math.min(resultLinks.length - 1, activeIndex + 1);
                resetActiveLink();
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                activeIndex = Math.max(0, activeIndex - 1);
                resetActiveLink();
                return;
            }

            if (event.key === 'Enter' && activeIndex >= 0 && resultLinks[activeIndex]) {
                event.preventDefault();
                window.location.assign(resultLinks[activeIndex].href);
            }
        });

        form.addEventListener('submit', function () {
            closeSuggestions();
        });

        document.addEventListener('click', function (event) {
            const target = event.target;
            if (isOpen && !panel.contains(target) && !Array.from(toggles).some((toggle) => toggle.contains(target))) {
                closePanel();
            }
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

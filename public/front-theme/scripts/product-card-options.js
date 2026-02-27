(() => {
    const bind = function (scope) {
        const forms = (scope || document).querySelectorAll('[data-product-card-form]');

        forms.forEach(function (form) {
        if (form.dataset.optionsInit === '1') {
            return;
        }
        form.dataset.optionsInit = '1';
        const optionInputs = form.querySelectorAll('input[name="product_option_value_id"]');
        const errorEl = form.querySelector('[data-option-error]');
        const autoSubmitOnOption = form.dataset.autoSubmitOnOption === '1';

        if (!optionInputs.length || !errorEl) {
            return;
        }

        const hideError = function () {
            errorEl.classList.add('hidden');
        };

        optionInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                hideError();
                if (autoSubmitOnOption) {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                    }
                }
            });
        });

        form.addEventListener('submit', function (event) {
            const selected = form.querySelector('input[name="product_option_value_id"]:checked');

            if (selected) {
                hideError();
                return;
            }

            event.preventDefault();
            errorEl.classList.remove('hidden');
        });
    });
    };

    const init = function () {
        if (window.__productCardOptionsInit === true) {
            return;
        }
        window.__productCardOptionsInit = true;
        bind(document);
        document.addEventListener('catalog:items-appended', function (event) {
            bind(event.detail?.container || document);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
        return;
    }

    init();
})();

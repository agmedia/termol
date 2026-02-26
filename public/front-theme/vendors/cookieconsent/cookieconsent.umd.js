(function () {
    var STORAGE_KEY = 'cc_cookie';

    function byId(id) {
        return document.getElementById(id);
    }

    function safeParse(json) {
        try {
            return JSON.parse(json);
        } catch (e) {
            return null;
        }
    }

    function readStoredState() {
        var raw = window.localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return null;
        }
        return safeParse(raw);
    }

    function writeStoredState(state) {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    }

    function shallowEqual(a, b) {
        return JSON.stringify(a) === JSON.stringify(b);
    }

    function categoryEnabled(state, category) {
        if (!state || !state.categories) {
            return false;
        }
        return state.categories[category] === true;
    }

    function createEl(tag, className, text) {
        var el = document.createElement(tag);
        if (className) {
            el.className = className;
        }
        if (typeof text === 'string') {
            el.textContent = text;
        }
        return el;
    }

    function createContainer() {
        var existing = byId('cc-main');
        if (existing) {
            return existing;
        }

        var root = document.createElement('div');
        root.id = 'cc-main';
        root.setAttribute('aria-live', 'polite');

        var overlay = createEl('div', 'cc-overlay');
        root.appendChild(overlay);
        document.body.appendChild(root);
        return root;
    }

    function pickLocale(config) {
        var language = config.language || {};
        var locale = language.default || 'hr';
        var translations = language.translations || {};
        return translations[locale] || translations.hr || {};
    }

    function buildRuntime(config) {
        var locale = pickLocale(config);
        var consent = locale.consentModal || {};
        var prefs = locale.preferencesModal || {};
        var sections = Array.isArray(prefs.sections) ? prefs.sections : [];

        var root = createContainer();
        var overlay = root.querySelector('.cc-overlay');

        var consentModal = createEl('div', 'cm');
        consentModal.id = 'cc-consent-modal';
        consentModal.setAttribute('role', 'dialog');
        consentModal.setAttribute('aria-modal', 'true');
        consentModal.hidden = true;

        var cmBody = createEl('div', 'cm__body');
        cmBody.appendChild(createEl('h3', 'cm__title', consent.title || 'Cookie postavke'));
        var cmDesc = createEl('p', 'cm__desc');
        cmDesc.innerHTML = consent.description || '';
        cmBody.appendChild(cmDesc);
        consentModal.appendChild(cmBody);

        var cmFooter = createEl('div', 'cm__footer');
        var cmBtns = createEl('div', 'cm__btn-group');
        var btnAcceptAll = createEl('button', 'cm__btn cm__btn--primary', consent.acceptAllBtn || 'Prihvati');
        var btnAcceptNecessary = createEl('button', 'cm__btn cm__btn--secondary', consent.acceptNecessaryBtn || 'Samo nuzni');
        var btnPrefs = createEl('button', 'cm__btn cm__btn--secondary', consent.showPreferencesBtn || 'Postavke');
        btnAcceptAll.type = 'button';
        btnAcceptNecessary.type = 'button';
        btnPrefs.type = 'button';
        cmBtns.appendChild(btnAcceptAll);
        cmBtns.appendChild(btnAcceptNecessary);
        cmBtns.appendChild(btnPrefs);
        cmFooter.appendChild(cmBtns);
        consentModal.appendChild(cmFooter);
        root.appendChild(consentModal);

        var prefsModal = createEl('div', 'pm');
        prefsModal.id = 'cc-preferences-modal';
        prefsModal.setAttribute('role', 'dialog');
        prefsModal.setAttribute('aria-modal', 'true');
        prefsModal.hidden = true;

        var pmBody = createEl('div', 'pm__body');
        pmBody.appendChild(createEl('h3', 'pm__title', prefs.title || 'Postavke kolačića'));

        var toggleMap = {};

        sections.forEach(function (section) {
            var category = section.linkedCategory || '';
            var sec = createEl('section', 'pm__section');
            sec.appendChild(createEl('h4', '', section.title || category));
            sec.appendChild(createEl('p', '', section.description || ''));

            if (category && category !== 'necessary') {
                var switchWrap = createEl('label', 'pm__switch');
                switchWrap.appendChild(createEl('span', '', 'Omogući'));
                var input = document.createElement('input');
                input.type = 'checkbox';
                input.dataset.category = category;
                switchWrap.appendChild(input);
                sec.appendChild(switchWrap);
                toggleMap[category] = input;
            }

            pmBody.appendChild(sec);
        });

        prefsModal.appendChild(pmBody);

        var pmFooter = createEl('div', 'pm__footer');
        var pmBtns = createEl('div', 'pm__btn-group');
        var btnPrefsAll = createEl('button', 'pm__btn pm__btn--primary', prefs.acceptAllBtn || 'Prihvati sve');
        var btnPrefsNecessary = createEl('button', 'pm__btn pm__btn--secondary', prefs.acceptNecessaryBtn || 'Samo nužni');
        var btnPrefsSave = createEl('button', 'pm__btn pm__btn--secondary', prefs.savePreferencesBtn || 'Spremi');
        btnPrefsAll.type = 'button';
        btnPrefsNecessary.type = 'button';
        btnPrefsSave.type = 'button';
        pmBtns.appendChild(btnPrefsAll);
        pmBtns.appendChild(btnPrefsNecessary);
        pmBtns.appendChild(btnPrefsSave);
        pmFooter.appendChild(pmBtns);
        prefsModal.appendChild(pmFooter);
        root.appendChild(prefsModal);

        return {
            root: root,
            overlay: overlay,
            consentModal: consentModal,
            prefsModal: prefsModal,
            toggleMap: toggleMap,
            buttons: {
                btnAcceptAll: btnAcceptAll,
                btnAcceptNecessary: btnAcceptNecessary,
                btnPrefs: btnPrefs,
                btnPrefsAll: btnPrefsAll,
                btnPrefsNecessary: btnPrefsNecessary,
                btnPrefsSave: btnPrefsSave
            }
        };
    }

    function defaultCategories() {
        return {
            necessary: true,
            analytics: false,
            marketing: false
        };
    }

    var runtime = null;
    var state = readStoredState();
    var firstConsentFired = state !== null;
    var configRef = null;

    function notify(oldState, newState) {
        if (!configRef) {
            return;
        }

        if (!firstConsentFired && typeof configRef.onFirstConsent === 'function') {
            configRef.onFirstConsent();
            firstConsentFired = true;
        }

        if (typeof configRef.onConsent === 'function') {
            configRef.onConsent();
        }

        if (oldState && typeof configRef.onChange === 'function' && !shallowEqual(oldState.categories, newState.categories)) {
            configRef.onChange();
        }
    }

    function setState(nextCategories) {
        var oldState = state;
        state = {
            categories: {
                necessary: true,
                analytics: !!nextCategories.analytics,
                marketing: !!nextCategories.marketing
            },
            acceptedAt: Date.now()
        };
        writeStoredState(state);
        notify(oldState, state);
    }

    function openConsent() {
        if (!runtime) {
            return;
        }
        runtime.consentModal.hidden = false;
        runtime.prefsModal.hidden = true;
        runtime.root.classList.add('cc-visible');
        runtime.root.classList.remove('cc-pref-visible');
    }

    function openPrefs() {
        if (!runtime) {
            return;
        }
        var current = state ? state.categories : defaultCategories();
        Object.keys(runtime.toggleMap).forEach(function (key) {
            runtime.toggleMap[key].checked = !!current[key];
        });

        runtime.prefsModal.hidden = false;
        runtime.consentModal.hidden = true;
        runtime.root.classList.remove('cc-visible');
        runtime.root.classList.add('cc-pref-visible');
    }

    function closeAll() {
        if (!runtime) {
            return;
        }
        runtime.root.classList.remove('cc-visible');
        runtime.root.classList.remove('cc-pref-visible');
        runtime.consentModal.hidden = true;
        runtime.prefsModal.hidden = true;
    }

    function wireEvents() {
        var buttons = runtime.buttons;

        buttons.btnAcceptAll.addEventListener('click', function () {
            setState({ analytics: true, marketing: true });
            closeAll();
        });

        buttons.btnAcceptNecessary.addEventListener('click', function () {
            setState({ analytics: false, marketing: false });
            closeAll();
        });

        buttons.btnPrefs.addEventListener('click', openPrefs);

        buttons.btnPrefsAll.addEventListener('click', function () {
            setState({ analytics: true, marketing: true });
            closeAll();
        });

        buttons.btnPrefsNecessary.addEventListener('click', function () {
            setState({ analytics: false, marketing: false });
            closeAll();
        });

        buttons.btnPrefsSave.addEventListener('click', function () {
            setState({
                analytics: runtime.toggleMap.analytics ? runtime.toggleMap.analytics.checked : false,
                marketing: runtime.toggleMap.marketing ? runtime.toggleMap.marketing.checked : false
            });
            closeAll();
        });

        runtime.overlay.addEventListener('click', function () {
            if (!state) {
                return;
            }
            closeAll();
        });
    }

    window.CookieConsent = {
        run: function (config) {
            configRef = config || {};
            runtime = buildRuntime(configRef);
            wireEvents();
            if (state) {
                notify(state, state);
            }
        },
        acceptedCategory: function (category) {
            return categoryEnabled(state, category);
        },
        validConsent: function () {
            return !!state;
        },
        show: function () {
            openConsent();
        },
        showPreferences: function () {
            openPrefs();
        }
    };
})();

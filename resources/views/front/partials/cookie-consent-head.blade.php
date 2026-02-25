@if ((bool) ($storeSettings['cookies']['enabled'] ?? true))
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/vanilla-cookieconsent@3/dist/cookieconsent.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vanilla-cookieconsent@3/dist/cookieconsent.css"></noscript>
    <style>
        #cc-main .cm {
            max-width: 42rem;
            border-radius: 1rem;
            padding: 0;
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.22);
        }

        #cc-main .cm__title {
            color: #1f2937;
            font-weight: 700;
            margin-bottom: 0.9rem;
        }

        #cc-main .cm__desc {
            color: #4b5563;
            line-height: 1.45;
            margin-bottom: 1.2rem;
        }

        #cc-main .cm__body {
            padding: 1.9rem 2.5rem 1.35rem;
        }

        #cc-main .cm__footer {
            border-top: 1px solid #e2e8f0;
            padding: 1rem 2.5rem 2rem;
        }

        #cc-main .cm__btn {
            border-radius: 0.75rem;
            min-height: 2.7rem;
            font-weight: 700;
        }

        #cc-main .cm__btn-group {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0;
        }

        #cc-main .cm__btn-group .cm__btn + .cm__btn {
            margin-top: 0.55rem;
        }

        #cc-main .cm__btn--secondary {
            border: 1px solid #111827;
            background: #fff;
            color: #111827;
        }

        #cc-main .cm__btn--secondary:hover {
            border-color: #000;
            background: #f3f4f6;
        }

        #cookie-consent-floating-button svg,
        #cookie-consent-floating-button img {
            display: block;
            flex-shrink: 0;
        }
        #cookie-consent-floating-button{
            position: fixed !important;
            left: 1rem !important;
            bottom: 1rem !important;
            z-index: 2147483647 !important;
            display: inline-flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }

    </style>
@endif

@php
    $mobileOffset = (bool) ($mobileOffset ?? false);
    $buttonLabel = app()->getLocale() === 'hr' ? 'Povratak na vrh' : 'Back to top';
@endphp

<style>
    .scroll-to-top-button {
        position: fixed;
        right: 1rem;
        bottom: 1rem;
        z-index: 9998;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 3rem;
        height: 3rem;
        border: 0;
        border-radius: 9999px;
        background: #0f172a;
        color: #ffffff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.24);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        -webkit-transform: translate3d(0, 10px, 0) scale(0.92);
        transform: translate3d(0, 10px, 0) scale(0.92);
        backface-visibility: hidden;
        will-change: opacity, transform;
        transition: opacity .24s ease, transform .28s ease, visibility .24s ease, box-shadow .24s ease;
    }

    .scroll-to-top-button.is-visible {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        -webkit-transform: translate3d(0, 0, 0) scale(1);
        transform: translate3d(0, 0, 0) scale(1);
    }

    .scroll-to-top-button:hover {
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.28);
    }

    .scroll-to-top-button svg {
        display: block;
        width: 1.2rem;
        height: 1.2rem;
        transition: transform .24s ease;
    }

    .scroll-to-top-button:hover svg {
        transform: translateY(-2px);
    }

    @media (prefers-reduced-motion: reduce) {
        .scroll-to-top-button,
        .scroll-to-top-button svg {
            transition: none;
        }
    }

    @media (hover: none) and (pointer: coarse) {
        .scroll-to-top-button,
        .scroll-to-top-button.is-visible,
        .scroll-to-top-button:hover {
            -webkit-transform: none;
            transform: none;
        }

        .scroll-to-top-button:hover svg {
            transform: none;
        }
    }
</style>

<button
    type="button"
    class="scroll-to-top-button{{ $mobileOffset ? ' scroll-to-top-button--mobile' : '' }}"
    data-scroll-to-top
    aria-label="{{ $buttonLabel }}"
    @if($mobileOffset)
        style="bottom: calc(5.75rem + env(safe-area-inset-bottom, 0px));"
    @endif
>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m6 14 6-6 6 6"></path>
    </svg>
</button>

<script>
    (function () {
        const scrollTopButton = document.querySelector('[data-scroll-to-top]');
        if (!scrollTopButton) {
            return;
        }

        let ticking = false;

        const syncVisibility = function () {
            const shouldShow = window.scrollY > 360;
            scrollTopButton.classList.toggle('is-visible', shouldShow);
            ticking = false;
        };

        const onScroll = function () {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(syncVisibility);
        };

        scrollTopButton.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: 'smooth',
            });
        });

        syncVisibility();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        window.addEventListener('pageshow', syncVisibility);
    })();
</script>

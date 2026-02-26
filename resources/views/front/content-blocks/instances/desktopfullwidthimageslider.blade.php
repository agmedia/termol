@php
    $translationPayload = is_array($translation?->payload ?? null) ? $translation->payload : [];
    $customClasses = trim((string) ($translationPayload['custom_classes'] ?? ''));
    $preferWebp = (bool) ($storeSettings['images']['use_webp'] ?? false);
    $sliderId = 'full-width-slider-'.$block->id;
    $slides = $block->getMedia('block_slides');

    if ($slides->isEmpty()) {
        $fallback = $block->getFirstMedia('block_background');
        if ($fallback) {
            $slides = collect([$fallback]);
        }
    }

    $firstSlide = $slides->first();
    $firstSlidePreloadUrl = $firstSlide
        ? (\App\Support\Media\MediaUrl::conversion($firstSlide, 'hero_1200w', $preferWebp)
            ?? \App\Support\Media\MediaUrl::conversion($firstSlide, 'hero_960w', $preferWebp)
            ?? \App\Support\Media\MediaUrl::conversion($firstSlide, 'hero_1600w', $preferWebp)
            ?? $firstSlide->getUrl())
        : null;

    $autoplayMs = 5000;
@endphp

@if ($slides->isNotEmpty())
    @if ($firstSlidePreloadUrl)
        @push('head')
            <link rel="preload" as="image" href="{{ $firstSlidePreloadUrl }}">
        @endpush
    @endif

    @include('front.partials.splide-assets')

    <style>
        #{{ $sliderId }} .splide__arrow {
            opacity: 0;
            width: 46px;
            height: 46px;
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, 0.75);
            background: rgba(15, 23, 42, 0.35);
            backdrop-filter: blur(6px);
            transform: translateY(-50%) scale(0.92);
            transition: opacity .25s ease, transform .25s ease, background-color .25s ease;
        }

        #{{ $sliderId }}:hover .splide__arrow,
        #{{ $sliderId }}:focus-within .splide__arrow {
            opacity: 1;
            transform: translateY(-50%) scale(1);
        }

        #{{ $sliderId }} .splide__arrow:hover {
            background: rgba(15, 23, 42, 0.55);
        }

        #{{ $sliderId }} .splide__arrow svg {
            fill: #fff;
        }

        @media (max-width: 768px) {
            #{{ $sliderId }} .hero-slide-image {
                width: 100%;
                aspect-ratio: 5 / 4;
                object-fit: cover;
                object-position: center;
            }
        }
    </style>

    <section class="relative left-1/2 w-screen -translate-x-1/2 overflow-hidden {{ $customClasses }}">
        <div id="{{ $sliderId }}" class="splide" data-fullwidth-splide>
            <div class="splide__track">
                <ul class="splide__list">
                    @foreach ($slides as $media)
                        @php
                            $slideUrl = \App\Support\Media\MediaUrl::conversion($media, 'hero_1360w', $preferWebp)
                                ?? \App\Support\Media\MediaUrl::conversion($media, 'hero_1200w', $preferWebp)
                                ?? \App\Support\Media\MediaUrl::conversion($media, 'hero_1600w', $preferWebp)
                                ?? $media->getUrl();
                            $slideUrl960 = \App\Support\Media\MediaUrl::conversion($media, 'hero_960w', $preferWebp) ?? $slideUrl;
                            $slideUrl720 = \App\Support\Media\MediaUrl::conversion($media, 'hero_720w', $preferWebp) ?? $slideUrl960;
                            $slideUrl540 = \App\Support\Media\MediaUrl::conversion($media, 'hero_540w', $preferWebp) ?? $slideUrl720;
                            $slideLink = trim((string) (
                                data_get($media->custom_properties, 'link_url.'.app()->getLocale())
                                ?: data_get($media->custom_properties, 'link_url.'.config('app.locale'))
                                ?: data_get($media->custom_properties, 'link_url_value', '')
                            ));
                            $hasSlideLink = $slideLink !== '';
                            $slideWidth = max(1, (int) ($media->width ?? 1200));
                            $slideHeight = max(1, (int) ($media->height ?? 700));
                        @endphp
                        <li class="splide__slide">
                            <article class="relative min-w-full">
                                @if ($hasSlideLink)
                                    <a href="{{ $slideLink }}" class="block">
                                @endif
                                    <img
                                        src="{{ $slideUrl }}"
                                        srcset="{{ $slideUrl540 }} 540w, {{ $slideUrl720 }} 720w, {{ $slideUrl960 }} 960w, {{ \App\Support\Media\MediaUrl::conversion($media, 'hero_1200w', $preferWebp) ?? $slideUrl }} 1200w, {{ $slideUrl }} 1360w, {{ \App\Support\Media\MediaUrl::conversion($media, 'hero_1600w', $preferWebp) ?? $slideUrl }} 1600w"
                                        sizes="100vw"
                                        alt="{{ $translation?->title ?: $block->name }} {{ $loop->iteration }}"
                                        class="hero-slide-image h-auto w-full bg-slate-100 object-contain"
                                        width="{{ $slideWidth }}"
                                        height="{{ $slideHeight }}"
                                        @if ($loop->first)
                                            loading="eager"
                                            fetchpriority="high"
                                        @else
                                            loading="lazy"
                                        @endif
                                        decoding="async"
                                    >
                                    <div class="absolute inset-0 bg-black/10"></div>
                                    @if (($translation?->title ?? '') !== '' || ($translation?->subtitle ?? '') !== '')
                                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/50 via-black/20 to-transparent px-6 pb-10 pt-16 text-white md:px-12">
                                            @if (($translation?->title ?? '') !== '')
                                                <h2 class="text-3xl font-extrabold tracking-tight md:text-5xl">{{ $translation->title }}</h2>
                                            @endif
                                            @if (($translation?->subtitle ?? '') !== '')
                                                <p class="mt-3 max-w-3xl text-sm text-white/90 md:text-base">{{ $translation->subtitle }}</p>
                                            @endif
                                            @if (($translation?->cta_label ?? '') !== '' && (($translation?->cta_url ?? '') !== '' || $hasSlideLink))
                                                @if ($hasSlideLink)
                                                    <span class="mt-6 inline-flex h-11 items-center border border-white bg-white px-6 text-sm font-semibold text-slate-900">
                                                        {{ $translation->cta_label }}
                                                    </span>
                                                @else
                                                    <a href="{{ $translation->cta_url }}" class="mt-6 inline-flex h-11 items-center border border-white bg-white px-6 text-sm font-semibold text-slate-900 hover:bg-slate-100">
                                                        {{ $translation->cta_label }}
                                                    </a>
                                                @endif
                                            @endif
                                        </div>
                                    @endif
                                @if ($hasSlideLink)
                                    </a>
                                @endif
                            </article>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    @once
        @push('scripts')
            <script>
                (function () {
                    const init = function () {
                        if (typeof window.Splide !== 'function') {
                            return false;
                        }

                        const sliders = document.querySelectorAll('[data-fullwidth-splide]');
                        sliders.forEach(function (el) {
                            if (el.dataset.splideReady === '1') {
                                return;
                            }
                            el.dataset.splideReady = '1';

                            const count = el.querySelectorAll('.splide__slide').length;
                            new window.Splide(el, {
                                type: count > 1 ? 'loop' : 'slide',
                                perPage: 1,
                                perMove: 1,
                                arrows: count > 1,
                                pagination: count > 1,
                                autoplay: count > 1,
                                interval: {{ $autoplayMs }},
                                pauseOnHover: true,
                                pauseOnFocus: true,
                                speed: 700,
                                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                            }).mount();
                        });

                        return true;
                    };

                    if (init()) {
                        return;
                    }

                    let attempts = 0;
                    const timer = window.setInterval(function () {
                        attempts += 1;
                        if (init() || attempts > 40) {
                            window.clearInterval(timer);
                        }
                    }, 120);
                })();
            </script>
        @endpush
    @endonce
@endif

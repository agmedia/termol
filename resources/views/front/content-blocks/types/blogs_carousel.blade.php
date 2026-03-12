@php
    use App\Models\Content\Blog\BlogPost;

    $locale = app()->getLocale();
    $fallbackLocale = config('app.locale');
    $preferWebp = (bool) ($storeSettings['images']['use_webp'] ?? false);
    $translationPayload = is_array($translation?->payload ?? null) ? $translation->payload : [];
    $blockPayload = is_array($block->payload ?? null) ? $block->payload : [];
    $mergedPayload = array_merge($blockPayload, $translationPayload);
    $allowedRoutes = config('content_blocks.route_whitelist', []);
    $displayTitle = trim((string) ($translation?->title ?? ''));
    $displaySubtitle = trim((string) ($translation?->subtitle ?? ''));
    $source = in_array((string) ($mergedPayload['blog_source'] ?? 'latest'), ['latest', 'featured'], true)
        ? (string) $mergedPayload['blog_source']
        : 'latest';
    $limit = max(1, min(12, (int) ($mergedPayload['items_limit'] ?? 6)));

    if ($displayTitle === '' || $displaySubtitle === '') {
        $allTranslations = $block->translations()->get(['locale', 'title', 'subtitle']);

        if ($displayTitle === '') {
            $displayTitle = trim((string) ($allTranslations->firstWhere('locale', $locale)?->title ?? ''));
            if ($displayTitle === '') {
                $displayTitle = trim((string) ($allTranslations->firstWhere('locale', $fallbackLocale)?->title ?? ''));
            }
            if ($displayTitle === '') {
                $displayTitle = trim((string) ($allTranslations->first(
                    static fn ($row): bool => trim((string) ($row->title ?? '')) !== ''
                )?->title ?? ''));
            }
        }

        if ($displaySubtitle === '') {
            $displaySubtitle = trim((string) ($allTranslations->firstWhere('locale', $locale)?->subtitle ?? ''));
            if ($displaySubtitle === '') {
                $displaySubtitle = trim((string) ($allTranslations->firstWhere('locale', $fallbackLocale)?->subtitle ?? ''));
            }
            if ($displaySubtitle === '') {
                $displaySubtitle = trim((string) ($allTranslations->first(
                    static fn ($row): bool => trim((string) ($row->subtitle ?? '')) !== ''
                )?->subtitle ?? ''));
            }
        }
    }

    if ($displayTitle === '') {
        $displayTitle = (string) $block->name;
    }

    $resolveRouteUrl = function (?string $routeName, mixed $routeParams, string $fallbackUrl = '#') use ($allowedRoutes): string {
        $name = trim((string) $routeName);
        if ($name === '') {
            return $fallbackUrl;
        }

        $isAllowed = $allowedRoutes === []
            || collect($allowedRoutes)->contains(fn ($pattern) => \Illuminate\Support\Str::is((string) $pattern, $name));

        if (! $isAllowed || !\Illuminate\Support\Facades\Route::has($name)) {
            return $fallbackUrl;
        }

        $params = is_array($routeParams) ? $routeParams : [];

        try {
            return route($name, $params);
        } catch (\Throwable) {
            return $fallbackUrl;
        }
    };

    $ctaLabel = trim((string) ($translation?->cta_label ?? ''));
    $ctaFallbackUrl = (string) ($translation?->cta_url ?? '#');
    $ctaRoute = (string) ($mergedPayload['cta_route'] ?? '');
    $ctaRouteParams = $mergedPayload['cta_route_params'] ?? [];
    $ctaUrl = $resolveRouteUrl($ctaRoute, $ctaRouteParams, $ctaFallbackUrl);

    $postsQuery = BlogPost::query()
        ->where('is_active', true)
        ->where(function ($q): void {
            $q->whereNull('published_at')->orWhere('published_at', '<=', now());
        })
        ->with([
            'translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
            'media',
        ])
        ->orderByDesc('published_at')
        ->orderByDesc('id');

    if ($source === 'featured') {
        $postsQuery->where('is_featured', true);
    }

    $posts = $postsQuery->limit($limit)->get();
@endphp

<section class="relative left-1/2 w-screen -translate-x-1/2 bg-white max-[540px]:pt-5 max-[540px]:pb-[2.75rem] pt-8 pb-16">
    <div class="w-full px-3 sm:px-4 lg:px-6">
        <div class="max-[540px]:mb-5 mb-8 text-center">
            <div class="mx-auto flex max-w-3xl items-center gap-4 md:gap-6">
                <span class="h-px flex-1 bg-slate-300"></span>
                <h2 class="max-[540px]:text-[1.18rem] max-[540px]:leading-[1.65rem] text-[1.35rem] leading-[1.95rem] sm:text-[1.7rem] sm:leading-[2.5rem] uppercase font-semibold text-slate-900">{{ $displayTitle }}</h2>
                <span class="h-px flex-1 bg-slate-300"></span>
            </div>
            @if ($displaySubtitle !== '')
                <p class="mx-auto mt-2 max-w-2xl text-sm text-slate-600 md:text-base">{{ $displaySubtitle }}</p>
            @endif

            @if ($ctaLabel !== '' && $ctaUrl !== '')
                <a href="{{ $ctaUrl }}" class="mt-4 inline-flex h-10 items-center bg-slate-100 px-5 text-xs font-semibold uppercase tracking-[0.14em] text-slate-700 hover:bg-slate-200">
                    {{ $ctaLabel }}
                </a>
            @endif
        </div>

        @if ($posts->isNotEmpty())
            <style>
                #blogs-carousel-{{ $block->id }} .splide__arrow {
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

                #blogs-carousel-{{ $block->id }}:hover .splide__arrow,
                #blogs-carousel-{{ $block->id }}:focus-within .splide__arrow {
                    opacity: 1;
                    transform: translateY(-50%) scale(1);
                }

                #blogs-carousel-{{ $block->id }} .splide__arrow:hover {
                    background: rgba(15, 23, 42, 0.55);
                }

                #blogs-carousel-{{ $block->id }} .splide__arrow svg {
                    fill: #fff;
                }

                #blogs-carousel-{{ $block->id }} .splide__pagination {
                    bottom: -1.1rem;
                    gap: 0.4rem;
                }

                #blogs-carousel-{{ $block->id }} .splide__pagination__page {
                    width: 10px;
                    height: 10px;
                    margin: 0;
                    opacity: 0.95;
                    background: #ffffff !important;
                    border: 2px solid transparent;
                    transition: transform 0.2s linear, background-color 0.2s linear, border-color 0.2s linear;
                }

                #blogs-carousel-{{ $block->id }} .splide__pagination__page.is-active {
                    transform: none;
                    background: #0f172a !important;
                    border-color: #ffffff;
                }

                @media (hover: none) {
                    #blogs-carousel-{{ $block->id }} .splide__arrow {
                        opacity: 1;
                        transform: translateY(-50%) scale(1);
                    }
                }
            </style>

            @include('front.partials.splide-assets')

            <div class="mt-4">
                <div id="blogs-carousel-{{ $block->id }}" class="splide" data-blogs-carousel-splide>
                    <div class="splide__track">
                        <ul class="splide__list">
                            @foreach ($posts as $post)
                                @php
                                    $postTranslation = $post->translations->firstWhere('locale', $locale)
                                        ?? $post->translations->firstWhere('locale', $fallbackLocale);
                                    $postTitle = (string) ($postTranslation?->title ?? $post->code);
                                    $postExcerpt = (string) ($postTranslation?->excerpt ?? '');
                                    $postSlug = (string) ($postTranslation?->slug ?? $post->id);
                                    $postUrl = route('blog.show', ['slug' => $postSlug]);
                                    $postCover = $post->getFirstMedia('blog_cover');
                                    $postCoverUrl1600 = $postCover
                                        ? \App\Support\Media\MediaUrl::conversionOrNull($postCover, 'cover_1600x2133', $preferWebp)
                                        : null;
                                    $postCoverUrl1200 = $postCover
                                        ? \App\Support\Media\MediaUrl::conversionOrNull($postCover, 'cover_1200x1600', $preferWebp)
                                        : null;
                                    $postCoverUrl900 = $postCover
                                        ? \App\Support\Media\MediaUrl::conversionOrNull($postCover, 'cover_900x1200', $preferWebp)
                                        : null;
                                    $postCoverUrl680 = $postCover
                                        ? \App\Support\Media\MediaUrl::conversionOrNull($postCover, 'cover_680x900', $preferWebp)
                                        : null;
                                    $postCoverUrl520 = $postCover
                                        ? \App\Support\Media\MediaUrl::conversionOrNull($postCover, 'cover_520x700', $preferWebp)
                                        : null;
                                    $postCoverUrl = $postCoverUrl1600 ?? $postCoverUrl1200 ?? $postCoverUrl900 ?? $postCoverUrl680 ?? $postCoverUrl520 ?? ($postCover?->getUrl());
                                    $postCoverSrcset = collect([
                                        $postCoverUrl520 ? $postCoverUrl520.' 520w' : null,
                                        $postCoverUrl680 ? $postCoverUrl680.' 680w' : null,
                                        $postCoverUrl900 ? $postCoverUrl900.' 900w' : null,
                                        $postCoverUrl1200 ? $postCoverUrl1200.' 1200w' : null,
                                        $postCoverUrl1600 ? $postCoverUrl1600.' 1600w' : null,
                                    ])->filter()->unique()->implode(', ');
                                    $postCoverWidth = max(1, (int) ($postCover?->width ?? 900));
                                    $postCoverHeight = max(1, (int) ($postCover?->height ?? 1200));
                                @endphp
                                <li class="splide__slide">
                                    <article class="group h-full bg-white">
                                        <a href="{{ $postUrl }}" class="block">
                                            <div class="overflow-hidden bg-slate-100">
                                                @if ($postCoverUrl)
                                                    <img
                                                        src="{{ $postCoverUrl }}"
                                                        @if ($postCoverSrcset !== '') srcset="{{ $postCoverSrcset }}" @endif
                                                        sizes="(max-width: 639px) 100vw, (max-width: 1023px) 50vw, (max-width: 1279px) 50vw, 33vw"
                                                        alt=""
                                                        class="h-auto w-full object-contain transition duration-300 group-hover:scale-[1.01]"
                                                        width="{{ $postCoverWidth }}"
                                                        height="{{ $postCoverHeight }}"
                                                        loading="lazy"
                                                        decoding="async"
                                                    >
                                                @else
                                                    <div class="flex h-full w-full items-center justify-center text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.product.no_image') }}</div>
                                                @endif
                                            </div>
                                            <div class="p-4">
                                                <h3 class="text-center text-[1.02rem] leading-[1.4rem] font-semibold text-slate-900 min-[541px]:text-lg min-[541px]:leading-snug lg:text-xl">{{ $postTitle }}</h3>
                                                @if ($postExcerpt !== '')
                                                    <p class="mt-2 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($postExcerpt, 120, '...') }}</p>
                                                @endif
                                            </div>
                                        </a>
                                    </article>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            @once
                @push('scripts')
                    <script>
                        (function () {
                            const init = function () {
                                if (typeof window.Splide !== 'function') {
                                    return false;
                                }

                                const sliders = document.querySelectorAll('[data-blogs-carousel-splide]');
                                sliders.forEach(function (el) {
                                    if (el.dataset.splideReady === '1') {
                                        return;
                                    }
                                    el.dataset.splideReady = '1';

                                    const count = el.querySelectorAll('.splide__slide').length;
                                    new window.Splide(el, {
                                        type: count > 1 ? 'loop' : 'slide',
                                        perPage: Math.min(4, Math.max(1, count)),
                                        perMove: 1,
                                        gap: '1.25rem',
                                        drag: count > 1,
                                        snap: true,
                                        pagination: count > 1,
                                        arrows: count > 1,
                                        updateOnMove: true,
                                        speed: 520,
                                        breakpoints: {
                                            1280: { perPage: Math.min(3, Math.max(1, count)) },
                                            1024: { perPage: Math.min(2, Math.max(1, count)) },
                                            860: { perPage: 1, gap: '1rem' },
                                            640: { perPage: 1, gap: '0.8rem' },
                                        },
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
        @else
            <div class="bg-slate-50 p-4 text-xs text-slate-500">
                No blog posts available for selected source.
            </div>
        @endif
    </div>
</section>

@extends('front.desktop.layouts.store')

@php
    $translation = $product->translations->firstWhere('locale', $locale)
        ?? $product->translations->firstWhere('locale', $fallbackLocale);
    $manufacturerTranslation = $product->manufacturer?->translations?->firstWhere('locale', $locale)
        ?? $product->manufacturer?->translations?->firstWhere('locale', $fallbackLocale);
    $manufacturerEnabled = app(\App\Services\Catalog\CatalogFeatureService::class)->useManufacturers();
    $currentPrice = number_format((float) ($pricePresentation['current_gross'] ?? 0), 2).' €';
    $oldPrice = isset($pricePresentation['old_gross']) && $pricePresentation['old_gross'] !== null
        ? number_format((float) $pricePresentation['old_gross'], 2).' €'
        : null;
    $discountPercent = (int) ($pricePresentation['discount_percent'] ?? 0);
    $lowest30DaysPrice = isset($pricePresentation['lowest_30_days_gross']) && $pricePresentation['lowest_30_days_gross'] !== null
        ? number_format((float) $pricePresentation['lowest_30_days_gross'], 2).' €'
        : null;
    $isWishlisted = app(\App\Services\Front\WishlistService::class)->has((int) $product->id);
    $preferWebp = (bool) ($storeSettings['images']['use_webp'] ?? false);

    $mediaItems = $product->relationLoaded('media')
        ? $product->media
            ->whereIn('collection_name', ['product_main', 'product_gallery'])
            ->sortBy(static fn ($mediaItem) => (int) ($mediaItem->order_column ?? 0))
            ->values()
        : collect();
    $mainMedia = $mediaItems->firstWhere('collection_name', 'product_main')
        ?? $mediaItems->firstWhere('collection_name', 'product_gallery')
        ?? $product->getFirstMedia('product_main')
        ?? $product->getFirstMedia('product_gallery');

    $galleryItems = collect();
    if ($mainMedia) {
        $galleryItems->push($mainMedia);
    }
    foreach ($mediaItems as $mediaItem) {
        if ($mainMedia && (int) $mediaItem->id === (int) $mainMedia->id) {
            continue;
        }

        $galleryItems->push($mediaItem);
    }

    $gallery = $galleryItems
        ->unique(fn ($mediaItem) => (int) $mediaItem->id)
        ->map(function ($mediaItem) use ($translation, $product, $preferWebp) {
            $displayUrl = \App\Support\Media\MediaUrl::conversion($mediaItem, 'detail_960x960', $preferWebp);
            $thumbUrl = \App\Support\Media\MediaUrl::conversion($mediaItem, 'thumb_100x100', $preferWebp);

            return [
                'id' => (int) $mediaItem->id,
                'full' => (string) ($displayUrl ?? $mediaItem->getUrl()),
                'display' => (string) $displayUrl,
                'thumb' => (string) ($thumbUrl ?? $mediaItem->getUrl()),
                'alt' => (string) ($translation?->name ?? $product->code),
            ];
        })
        ->values();

    $optionRows = $product->optionValues->where('is_active', true)->values();

    $firstCategory = $product->categories->first();
    $firstCategoryTranslation = $firstCategory?->translations?->firstWhere('locale', $locale)
        ?? $firstCategory?->translations?->firstWhere('locale', $fallbackLocale);
    $fitFinderEnabled = (bool) ($storeSettings['product']['fit_finder_enabled'] ?? false);
    $fitFinderSavedSize = trim((string) ($fitFinderSelection['size_label'] ?? ''));
@endphp

@section('title', $translation?->name ?? 'Product')
@section('main_class', 'w-full px-0 py-8')

@section('content')
    <style>
        .product-detail-layout {
            display: grid;
            gap: 2rem;
        }

        .product-ipad-slider {
            display: block;
            margin-top: -2rem;
            padding-bottom: 0;
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }

        .product-default-grid {
            display: none;
        }

        @media (max-width: 768px) {
            .product-detail-layout {
                gap: .75rem;
            }

            .product-ipad-slider .splide__track {
                overflow: hidden;
            }

            .product-ipad-slider .splide__slide > button {
                display: block;
                width: 100%;
                border: 0;
                padding: 0;
                background: #f8fafc;
            }

            .product-ipad-slider .splide__slide img {
                display: block;
                width: 100%;
                max-width: 100%;
                height: auto;
            }

            .product-ipad-slider .splide__pagination {
                bottom: .9rem !important;
                gap: .35rem;
                z-index: 20;
            }

            .product-ipad-slider .splide__pagination__page {
                width: .45rem;
                height: .45rem;
                margin: 0;
                opacity: .9;
                background: rgba(15, 23, 42, 0.28);
            }

            .product-ipad-slider .splide__pagination__page.is-active {
                transform: scale(1);
                background: rgba(15, 23, 42, 0.82);
            }

            .product-detail-layout > aside {
                padding-top: .5rem;
            }
        }

        @media (min-width: 769px) {
            .product-detail-layout {
                grid-template-columns: minmax(0, 1.2fr) minmax(360px, 1fr);
            }

            .product-ipad-slider {
                display: none;
                margin-top: 0;
            }

            .product-default-grid {
                display: block;
            }
        }

        [data-product-detail-form] .product-size-radio:checked + .product-size-label {
            border-color: #0f172a;
            background: #0f172a;
            color: #ffffff;
        }

        [data-fit-finder-modal] [data-fit-step].hidden {
            display: none;
        }

        [data-fit-finder-option][aria-pressed="true"] {
            border-color: #0f172a;
            background: #0f172a;
            color: #ffffff;
        }

        [data-fit-finder-result-row].is-selected {
            border-color: #166534;
            background: #f0fdf4;
        }

        [data-fit-finder-result-bar] {
            width: 0;
            transition: width .3s ease;
        }
    </style>

    @if ($topBlocks->isNotEmpty())
        <section class="mb-8">
            @include('components.content-placement', ['items' => $topBlocks])
        </section>
    @endif

    <section class="product-detail-layout" data-product-detail>
        <div class="bg-white xl:pl-8">
            @if ($gallery->isNotEmpty())
                @php
                    $displayGallery = $gallery->take(5)->values();
                    $topGallery = $displayGallery->take(2)->values();
                    $bottomGallery = $displayGallery->slice(2, 3)->values();
                @endphp

                <div id="product-mobile-splide" class="product-ipad-slider splide slider-no-arrows" data-product-splide>
                    <div class="splide__track">
                        <ul class="splide__list">
                            @foreach ($displayGallery as $index => $image)
                                <li class="splide__slide">
                                    <button
                                        type="button"
                                        data-gallery-thumb
                                        data-index="{{ $index }}"
                                        data-full="{{ $image['full'] }}"
                                        data-alt="{{ $image['alt'] }}"
                                        data-gallery-open="{{ $index }}"
                                        aria-label="{{ $image['alt'] }}"
                                    >
                                        <img
                                            src="{{ $image['display'] }}"
                                            alt="{{ $image['alt'] }}"
                                            class="block h-auto w-full bg-slate-50"
                                            loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                            decoding="async"
                                        >
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="product-default-grid block">
                    <div class="grid gap-1 md:grid-cols-2">
                        @foreach ($topGallery as $index => $image)
                            <button
                                type="button"
                                class="overflow-hidden border border-slate-200 bg-slate-50"
                                data-gallery-thumb
                                data-index="{{ $index }}"
                                data-full="{{ $image['full'] }}"
                                    data-alt="{{ $image['alt'] }}"
                                    data-gallery-open="{{ $index }}"
                                    aria-label="{{ $image['alt'] }}"
                                >
                                    <img
                                        src="{{ $image['display'] }}"
                                        alt="{{ $image['alt'] }}"
                                        class="block h-auto w-full bg-slate-50"
                                        loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                        decoding="async"
                                        @if ($index === 0) data-gallery-main @endif
                                        @if ($index === 1) data-gallery-secondary @endif
                                    >
                            </button>
                        @endforeach
                    </div>

                    @if ($bottomGallery->isNotEmpty())
                        <div class="mt-1 grid grid-cols-3 gap-1">
                            @foreach ($bottomGallery as $bottomIndex => $image)
                                @php $index = $bottomIndex + 2; @endphp
                                <button
                                    type="button"
                                    class="overflow-hidden border border-slate-200 bg-slate-50"
                                    data-gallery-thumb
                                    data-index="{{ $index }}"
                                    data-full="{{ $image['full'] }}"
                                    data-alt="{{ $image['alt'] }}"
                                    data-gallery-open="{{ $index }}"
                                    aria-label="{{ $image['alt'] }}"
                                >
                                    <img
                                        src="{{ $image['display'] }}"
                                        alt="{{ $image['alt'] }}"
                                        class="block h-auto w-full bg-slate-50"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="flex min-h-[420px] items-center justify-center border border-slate-200 bg-slate-100 text-sm font-semibold uppercase tracking-wide text-slate-500">
                    {{ __('ui.product.no_image') }}
                </div>
            @endif
        </div>

        <aside class="bg-white p-5">
            <nav aria-label="Breadcrumb" class="text-xs text-slate-500">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a href="{{ route('home') }}" class="hover:text-slate-700">{{ __('ui.front.desktop.footer.home') }}</a></li>
                    <li class="text-slate-400">&gt;</li>
                    <li><a href="{{ route('shop.index') }}" class="hover:text-slate-700">{{ __('ui.shop.page_title') }}</a></li>
                    @if ($firstCategoryTranslation)
                        <li class="text-slate-400">&gt;</li>
                        <li>
                            <a href="{{ route('categories.show', ['slug' => $firstCategoryTranslation->slug]) }}" class="hover:text-slate-700">
                                {{ $firstCategoryTranslation->name }}
                            </a>
                        </li>
                    @endif
                    <li class="text-slate-400">&gt;</li>
                    <li class="text-slate-700">{{ $translation?->name ?? $product->code }}</li>
                </ol>
            </nav>

            <div class="mt-4">
                <div>
                    <h1 class="text-2xl font-extrabold leading-tight text-slate-900">{{ $translation?->name ?? $product->code }}</h1>
                    <p class="mt-1 text-xs text-slate-500">{{ __('ui.product.sku') }}: {{ $product->sku ?: 'n/a' }}</p>
                    @if ($manufacturerTranslation && $manufacturerEnabled)
                        <p class="mt-1 text-xs text-slate-600">
                            <a href="{{ route('manufacturers.show', ['slug' => $manufacturerTranslation->slug]) }}" class="font-semibold text-slate-700 hover:text-slate-900">{{ $manufacturerTranslation->name }}</a>
                        </p>
                    @endif
                </div>
                <div class="mt-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-xl font-semibold text-slate-900">{{ $currentPrice }}</p>
                        @if ($discountPercent > 0)
                            <span class="inline-flex h-7 items-center border border-rose-600 bg-rose-600 px-2 text-xs font-bold text-white">-{{ $discountPercent }}%</span>
                        @endif
                    </div>
                    @if ($oldPrice)
                        <p class="mt-1 text-sm text-slate-500 line-through">{{ $oldPrice }}</p>
                    @endif
                    @if ($lowest30DaysPrice)
                        <p class="mt-1 text-xs text-slate-600">{{ __('ui.product.lowest_price_30_days', ['price' => $lowest30DaysPrice]) }}</p>
                    @endif
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('cart.items.store') }}"
                class="mt-6 space-y-4"
                data-product-detail-form
                data-ga4-add-to-cart-form
                data-ga4-item-id="{{ (string) ($product->sku ?: $product->id) }}"
                data-ga4-item-name="{{ $translation?->name ?? $product->code }}"
                data-ga4-item-price="{{ number_format((float) ($pricePresentation['current_gross'] ?? 0), 2, '.', '') }}"
                data-ga4-item-brand="{{ (string) ($manufacturerTranslation?->name ?? '') }}"
                data-ga4-item-category="{{ (string) ($firstCategoryTranslation?->name ?? '') }}"
                data-ga4-currency="EUR"
                data-product-name="{{ $translation?->name ?? $product->code }}"
                data-product-image="{{ (string) (($gallery->first()['full'] ?? '') ?: '') }}"
                data-cart-url="{{ route('cart.index') }}"
                data-modal-continue="{{ __('ui.cart.modal.continue') }}"
                data-modal-go-cart="{{ __('ui.cart.modal.go_to_cart') }}"
                data-modal-option="{{ __('ui.cart.modal.option') }}"
                data-modal-quantity="{{ __('ui.cart.modal.quantity') }}"
            >
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                @if ($optionRows->isNotEmpty())
                    <div>
                        <div class="mb-4 flex flex-col items-start gap-2 sm:mb-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-900">{{ __('ui.product.select_size') }} <span class="text-rose-600">*</span></label>
                            <div class="flex w-full flex-wrap items-center gap-x-3 gap-y-1 sm:w-auto sm:justify-end">
                                @if ($fitFinderEnabled && $optionRows->count() > 1)
                                    @if ($fitFinderSavedSize !== '')
                                        <button type="button" class="inline-flex items-baseline gap-1 text-xs font-normal tracking-normal text-slate-600 underline underline-offset-2 hover:text-slate-900 sm:text-sm" data-fit-finder-open>
                                            <span>{{ __('ui.product.fit_finder.saved_size_prefix') }}</span>
                                            <span class="font-semibold text-slate-800">{{ $fitFinderSavedSize }}</span>
                                        </button>
                                    @else
                                        <button type="button" class="text-xs font-semibold uppercase tracking-wide text-slate-700 underline underline-offset-2 hover:text-slate-900" data-fit-finder-open>
                                            {{ __('ui.product.fit_finder.trigger') }}
                                        </button>
                                    @endif
                                @endif
                                @if (!empty($sizeGuide))
                                    <button type="button" class="text-xs font-semibold uppercase tracking-wide text-slate-700 underline underline-offset-2 hover:text-slate-900" data-size-guide-open>
                                        {{ __('ui.product.size_guide') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($optionRows as $row)
                                @php
                                    $valueTranslation = $row->optionValue?->translations?->firstWhere('locale', $locale)
                                        ?? $row->optionValue?->translations?->firstWhere('locale', $fallbackLocale)
                                        ?? $row->optionValue?->translations?->first();
                                    $label = trim((string) ($valueTranslation?->name ?? $row->optionValue?->code ?? ''));
                                    $inputId = 'product-detail-pov-'.$product->id.'-'.$row->id;
                                @endphp
                                <span class="inline-flex">
                                    <input id="{{ $inputId }}" type="radio" name="product_option_value_id" value="{{ $row->id }}" class="sr-only product-size-radio" data-size-label="{{ $label }}">
                                    <label for="{{ $inputId }}" class="product-size-label inline-flex h-10 min-w-10 cursor-pointer items-center justify-center border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:bg-slate-100">
                                        <span>{{ $label }}</span>
                                    </label>
                                </span>
                            @endforeach
                        </div>
                        <p class="hidden mt-2 text-xs font-semibold text-rose-600" data-option-error>
                            {{ __('ui.cart.errors.select_size') }}
                        </p>
                    </div>
                @endif

                <div class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-2 sm:gap-2">
                    <div class="inline-flex h-10 items-stretch" data-qty-control>
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center border border-slate-300 text-xl font-semibold text-slate-700 hover:bg-slate-100" data-qty-dec aria-label="Decrease quantity">-</button>
                        <input type="text" name="quantity" value="1" inputmode="numeric" readonly aria-label="{{ __('ui.cart.modal.quantity') }}" class="h-10 w-10 border-y border-r border-slate-300 border-l-0 bg-white p-0 text-center text-base font-normal text-slate-900" data-qty-input>
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center border border-slate-300 text-xl font-semibold text-slate-700 hover:bg-slate-100" data-qty-inc aria-label="Increase quantity">+</button>
                    </div>

                    <button type="submit" class="inline-flex h-10 min-w-0 items-center justify-center gap-2 border border-slate-900 bg-slate-900 px-3 text-xs font-semibold text-white hover:bg-slate-700 sm:px-4 sm:text-sm" aria-label="{{ __('ui.product.add_to_cart') }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7 9h10l-1 10H8L7 9Z"></path>
                            <path d="M9 9V7a3 3 0 0 1 6 0v2"></path>
                        </svg>
                        <span class="text-center leading-tight sm:truncate">{{ __('ui.product.add_to_cart') }}</span>
                    </button>

                    <button
                        type="submit"
                        form="wishlist-product-{{ $product->id }}"
                        class="inline-flex h-10 w-10 items-center justify-center border transition sm:h-10 sm:w-10 {{ $isWishlisted ? 'border-slate-900 bg-slate-900 text-white hover:bg-slate-700' : 'border-slate-300 bg-white text-slate-700 hover:border-slate-900 hover:text-slate-900' }}"
                        aria-label="{{ $isWishlisted ? __('ui.wishlist.remove') : __('ui.wishlist.add') }}"
                        data-wishlist-button
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20.8 8.6c0 5.9-8.8 10.9-8.8 10.9S3.2 14.5 3.2 8.6a4.8 4.8 0 0 1 8.8-2.7 4.8 4.8 0 0 1 8.8 2.7Z"></path>
                        </svg>
                    </button>
                </div>
            </form>

            @if ($fitFinderEnabled && $optionRows->count() > 1)
                <div
                    class="fixed inset-0 z-[90] hidden bg-black/55 p-4 backdrop-blur-[2px]"
                    data-fit-finder-modal
                    data-text-error-height="{{ __('ui.product.fit_finder.errors.height') }}"
                    data-text-error-weight="{{ __('ui.product.fit_finder.errors.weight') }}"
                    data-text-error-age="{{ __('ui.product.fit_finder.errors.age') }}"
                    data-text-step-template="{{ __('ui.product.fit_finder.step_of', ['current' => '__CURRENT__', 'total' => '__TOTAL__']) }}"
                    data-text-recommendation-ready="{{ __('ui.product.fit_finder.recommendation_ready') }}"
                    data-text-summary-template="{{ __('ui.product.fit_finder.summary', ['size' => '__SIZE__', 'percent' => '__PERCENT__']) }}"
                    data-text-cta-template="{{ __('ui.product.fit_finder.add_cta', ['size' => '__SIZE__']) }}"
                    data-fit-save-url="{{ route('products.fit_finder.preferences') }}"
                    data-fit-product-id="{{ (int) $product->id }}"
                    data-fit-initial-height="{{ (string) ($fitFinderSelection['height'] ?? '') }}"
                    data-fit-initial-weight="{{ (string) ($fitFinderSelection['weight'] ?? '') }}"
                    data-fit-initial-age="{{ (string) ($fitFinderSelection['age'] ?? '') }}"
                    data-fit-initial-fit="{{ (string) ($fitFinderSelection['fit'] ?? 'average') }}"
                    data-fit-initial-chest="{{ (string) ($fitFinderSelection['chest'] ?? 'average') }}"
                    data-fit-initial-belly="{{ (string) ($fitFinderSelection['belly'] ?? 'average') }}"
                    data-fit-initial-size="{{ $fitFinderSavedSize }}"
                    aria-hidden="true"
                >
                    <div class="mx-auto mt-4 flex w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl md:mt-8">
                        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('ui.product.fit_finder.title') }}</p>
                                <p class="inline-flex rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-xs font-semibold text-slate-700" data-fit-finder-progress>{{ __('ui.product.fit_finder.step_of', ['current' => 1, 'total' => 5]) }}</p>
                            </div>
                            <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 text-lg text-slate-700 hover:bg-slate-100" data-fit-finder-close aria-label="{{ __('ui.product.size_guide_close') }}">
                                ×
                            </button>
                        </div>

                        <div class="grid gap-8 p-6 lg:grid-cols-[120px_minmax(0,1fr)]">
                            <div class="hidden lg:block">
                                @if ($gallery->isNotEmpty())
                                    <img src="{{ (string) (($gallery->first()['display'] ?? $gallery->first()['full'] ?? '') ?: '') }}" alt="{{ $translation?->name ?? $product->code }}" class="w-full border border-slate-200 bg-slate-50">
                                @endif
                            </div>

                            <div class="space-y-6">
                                <section data-fit-step="0">
                                    <h3 class="text-3xl font-extrabold text-slate-900">{{ __('ui.product.fit_finder.measurements_title') }}</h3>
                                    <p class="mb-1 text-sm text-slate-600">{{ __('ui.product.fit_finder.measurements_desc') }}</p>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <label class="space-y-1">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.product.fit_finder.height') }}</span>
                                            <input type="number" min="130" max="230" step="1" class="h-11 w-full border border-slate-300 px-3 text-sm focus:border-slate-500 focus:outline-none" data-fit-height>
                                        </label>
                                        <label class="space-y-1">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.product.fit_finder.weight') }}</span>
                                            <input type="number" min="35" max="220" step="1" class="h-11 w-full border border-slate-300 px-3 text-sm focus:border-slate-500 focus:outline-none" data-fit-weight>
                                        </label>
                                    </div>
                                    <p class="hidden text-xs font-semibold text-rose-600" data-fit-error></p>
                                </section>

                                <section data-fit-step="1" class="hidden">
                                    <h3 class="text-3xl font-extrabold text-slate-900">{{ __('ui.product.fit_finder.age_title') }}</h3>
                                    <label class="mt-1 block max-w-[240px] space-y-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.product.fit_finder.age') }}</span>
                                        <input type="number" min="12" max="100" step="1" class="h-11 w-full border border-slate-300 px-3 text-sm focus:border-slate-500 focus:outline-none" data-fit-age>
                                    </label>
                                    <p class="mb-1 text-sm text-slate-600">{{ __('ui.product.fit_finder.age_desc') }}</p>
                                    <p class="hidden text-xs font-semibold text-rose-600" data-fit-error></p>
                                </section>

                                <section data-fit-step="2" class="hidden">
                                    <h3 class="text-3xl font-extrabold text-slate-900">{{ __('ui.product.fit_finder.fit_title') }}</h3>
                                    <p class="mb-1 text-sm text-slate-600">{{ __('ui.product.fit_finder.fit_desc') }}</p>
                                    <div class="grid gap-2 sm:grid-cols-3">
                                        <button type="button" class="h-11 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:border-slate-900" data-fit-finder-option data-fit-fit="tighter">{{ __('ui.product.fit_finder.fit_tighter') }}</button>
                                        <button type="button" class="h-11 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:border-slate-900" data-fit-finder-option data-fit-fit="average" aria-pressed="true">{{ __('ui.product.fit_finder.fit_average') }}</button>
                                        <button type="button" class="h-11 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:border-slate-900" data-fit-finder-option data-fit-fit="looser">{{ __('ui.product.fit_finder.fit_looser') }}</button>
                                    </div>
                                </section>

                                <section data-fit-step="3" class="hidden">
                                    <h3 class="text-3xl font-extrabold text-slate-900">{{ __('ui.product.fit_finder.chest_title') }}</h3>
                                    <p class="mb-1 text-sm text-slate-600">{{ __('ui.product.fit_finder.chest_desc') }}</p>
                                    <div class="grid gap-2 sm:grid-cols-3">
                                        <button type="button" class="h-11 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:border-slate-900" data-fit-finder-option data-fit-chest="slimmer">{{ __('ui.product.fit_finder.chest_slimmer') }}</button>
                                        <button type="button" class="h-11 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:border-slate-900" data-fit-finder-option data-fit-chest="average" aria-pressed="true">{{ __('ui.product.fit_finder.chest_average') }}</button>
                                        <button type="button" class="h-11 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:border-slate-900" data-fit-finder-option data-fit-chest="broader">{{ __('ui.product.fit_finder.chest_broader') }}</button>
                                    </div>
                                </section>

                                <section data-fit-step="4" class="hidden">
                                    <h3 class="text-3xl font-extrabold text-slate-900">{{ __('ui.product.fit_finder.belly_title') }}</h3>
                                    <p class="mb-1 text-sm text-slate-600">{{ __('ui.product.fit_finder.belly_desc') }}</p>
                                    <div class="grid gap-2 sm:grid-cols-3">
                                        <button type="button" class="h-11 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:border-slate-900" data-fit-finder-option data-fit-belly="flatter">{{ __('ui.product.fit_finder.belly_flatter') }}</button>
                                        <button type="button" class="h-11 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:border-slate-900" data-fit-finder-option data-fit-belly="average" aria-pressed="true">{{ __('ui.product.fit_finder.belly_average') }}</button>
                                        <button type="button" class="h-11 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:border-slate-900" data-fit-finder-option data-fit-belly="rounder">{{ __('ui.product.fit_finder.belly_rounder') }}</button>
                                    </div>
                                </section>

                                <section data-fit-step="5" class="hidden">
                                    <h3 class="text-3xl font-extrabold text-slate-900">{{ __('ui.product.fit_finder.result_title') }}</h3>
                                    <p class="mb-1 text-sm text-slate-600">{{ __('ui.product.fit_finder.result_desc') }}</p>
                                    <div class="space-y-2">
                                        <div class="rounded-xl border border-slate-300 p-3" data-fit-finder-result-row="0">
                                            <div class="flex items-center justify-between">
                                                <p class="text-lg font-bold text-slate-900" data-fit-finder-result-size="0"></p>
                                                <p class="text-sm font-semibold text-emerald-700" data-fit-finder-result-percent="0"></p>
                                            </div>
                                            <div class="mt-2 h-2 bg-slate-200">
                                                <div class="h-2 bg-emerald-700" data-fit-finder-result-bar="0"></div>
                                            </div>
                                        </div>
                                        <div class="rounded-xl border border-slate-300 p-3" data-fit-finder-result-row="1">
                                            <div class="flex items-center justify-between">
                                                <p class="text-lg font-bold text-slate-700" data-fit-finder-result-size="1"></p>
                                                <p class="text-sm font-semibold text-slate-600" data-fit-finder-result-percent="1"></p>
                                            </div>
                                            <div class="mt-2 h-2 bg-slate-200">
                                                <div class="h-2 bg-slate-400" data-fit-finder-result-bar="1"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-sm text-slate-600" data-fit-finder-summary></p>
                                </section>

                                <div class="mt-2 flex items-center justify-between gap-2 border-t border-slate-200 pt-5">
                                    <button type="button" class="inline-flex h-11 items-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100" data-fit-prev>
                                        {{ __('ui.product.fit_finder.actions.back') }}
                                    </button>
                                    <button type="button" class="inline-flex h-11 items-center rounded-lg border border-slate-900 bg-slate-900 px-5 text-sm font-semibold text-white hover:bg-slate-700" data-fit-next>
                                        {{ __('ui.product.fit_finder.actions.continue') }}
                                    </button>
                                    <button type="button" class="hidden h-11 items-center rounded-lg border border-slate-900 bg-slate-900 px-5 text-sm font-semibold text-white hover:bg-slate-700" data-fit-apply>
                                        {{ __('ui.product.fit_finder.add_cta', ['size' => '']) }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <form
                id="wishlist-product-{{ $product->id }}"
                method="POST"
                action="{{ route('wishlist.items.toggle', ['product' => $product->id]) }}"
                class="hidden"
                data-wishlist-form
                data-wishlisted="{{ $isWishlisted ? '1' : '0' }}"
                data-label-add="{{ __('ui.wishlist.add') }}"
                data-label-remove="{{ __('ui.wishlist.remove') }}"
                data-msg-failed="{{ __('ui.wishlist.status.failed') }}"
            >
                @csrf
            </form>

            <button type="button" class="mt-4 inline-flex h-10 w-full items-center justify-center gap-3 border border-slate-300 bg-white px-4 text-xs font-semibold uppercase tracking-wide text-slate-800 hover:bg-slate-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="16" rx="1"></rect>
                    <path d="M7 8h3M7 12h10M7 16h10M14 8h3"></path>
                </svg>
                {{ __('ui.product.check_store') }}
            </button>

            @if (! empty($translation?->description))
                <div class="mt-6 text-slate-700">{!! $translation->description !!}</div>
            @elseif (! empty($translation?->excerpt))
                <p class="mt-6 text-slate-700">{{ $translation->excerpt }}</p>
            @endif

            @php
                $commentFormHasErrors = $errors->has('author_name') || $errors->has('author_email') || $errors->has('body') || $errors->has('rating');
                $commentUser = auth()->user();
            @endphp

            <div id="product-comments" class="mt-6 border-t border-slate-200 pt-4">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-bold text-slate-900">{{ __('ui.product.comments_title') }}</h3>
                    <button
                        type="button"
                        class="text-xs font-semibold uppercase tracking-wide text-slate-700 underline underline-offset-2 hover:text-slate-900"
                        data-comment-form-toggle
                        aria-expanded="{{ $commentFormHasErrors ? 'true' : 'false' }}"
                    >
                        {{ __('ui.product.comment_form.toggle') }}
                    </button>
                </div>

                <div class="{{ $commentFormHasErrors ? '' : 'hidden' }} mt-3 border border-slate-200 bg-slate-50 p-3" data-comment-form-panel>
                    <form method="POST" action="{{ route('products.comments.store', ['slug' => $translation?->slug ?? request()->route('slug')]) }}" class="space-y-3">
                        @csrf
                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.product.comment_form.name') }}</label>
                                <input type="text" name="author_name" value="{{ old('author_name', $commentUser?->name ?? '') }}" class="h-10 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" @if($commentUser) readonly @endif>
                                @error('author_name') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.product.comment_form.email') }}</label>
                                <input type="email" name="author_email" value="{{ old('author_email', $commentUser?->email ?? '') }}" class="h-10 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" @if($commentUser) readonly @endif>
                                @error('author_email') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.product.comment_form.rating') }}</label>
                            <select name="rating" class="h-10 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0">
                                <option value="">{{ __('ui.product.comment_form.rating_optional') }}</option>
                                @for ($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}" @selected((string) old('rating') === (string) $i)>{{ $i }} ★</option>
                                @endfor
                            </select>
                            @error('rating') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.product.comment_form.body') }}</label>
                            <textarea name="body" rows="4" class="w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required>{{ old('body') }}</textarea>
                            @error('body') <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="inline-flex h-10 items-center border border-slate-900 bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-700">
                            {{ __('ui.product.comment_form.submit') }}
                        </button>
                    </form>
                </div>

                @if (($comments ?? collect())->isNotEmpty())
                    <div class="mt-3 space-y-3">
                        @foreach ($comments as $comment)
                            <article class="border border-slate-200 bg-slate-50 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-900">{{ $comment->author_name ?: ($comment->user?->name ?? __('ui.product.comments_anonymous')) }}</p>
                                    @if ((int) ($comment->rating ?? 0) > 0)
                                        <p class="text-xs font-semibold text-slate-600">{{ str_repeat('★', (int) $comment->rating) }}</p>
                                    @endif
                                </div>
                                <p class="mt-2 text-sm leading-relaxed text-slate-700">{{ $comment->body }}</p>
                            </article>
                        @endforeach
                    </div>
                @else
                    <p class="mt-2 text-sm text-slate-500">{{ __('ui.product.comments_empty') }}</p>
                @endif
            </div>
        </aside>
    </section>

    @if (!empty($sizeGuide))
        <style>
            [data-size-guide-modal] .content-richtext {
                font-size: 0.9rem;
                line-height: 1.45;
            }
            [data-size-guide-modal] .content-richtext h1,
            [data-size-guide-modal] .content-richtext h2,
            [data-size-guide-modal] .content-richtext h3,
            [data-size-guide-modal] .content-richtext h4 {
                margin: 0.6rem 0 0.45rem;
                line-height: 1.25;
            }
            [data-size-guide-modal] .content-richtext h2 { font-size: 1.45rem; }
            [data-size-guide-modal] .content-richtext h3 { font-size: 1.15rem; }
            [data-size-guide-modal] .content-richtext p,
            [data-size-guide-modal] .content-richtext li {
                margin-bottom: 0.4rem;
            }
            [data-size-guide-modal] .content-richtext table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 0.82rem;
            }
            [data-size-guide-modal] .content-richtext th,
            [data-size-guide-modal] .content-richtext td {
                border: 1px solid #e2e8f0;
                padding: 0.35rem 0.45rem;
                vertical-align: middle;
            }
            [data-size-guide-modal] .content-richtext thead th {
                background: #f8fafc;
                font-weight: 700;
            }
        </style>
        <div class="fixed inset-0 z-[80] hidden bg-black/50 p-4" data-size-guide-modal aria-hidden="true">
            <div class="mx-auto mt-8 max-h-[86vh] w-full max-w-5xl overflow-hidden bg-white shadow-2xl md:mt-12">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-lg font-extrabold text-slate-900">{{ $sizeGuide['title'] ?: __('ui.product.size_guide') }}</h2>
                    <button type="button" class="inline-flex h-9 min-w-9 items-center justify-center border border-slate-300 bg-white px-3 text-xs font-semibold uppercase tracking-wide text-slate-700 hover:bg-slate-100" data-size-guide-close>
                        {{ __('ui.product.size_guide_close') }}
                    </button>
                </div>
                <div class="max-h-[calc(86vh-64px)] overflow-y-auto px-5 py-4">
                    <div class="content-richtext">{!! $sizeGuide['body_html'] !!}</div>
                </div>
            </div>
        </div>
    @endif

    @if ($related->isNotEmpty())
        <section class="mt-10 px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-slate-900">{{ __('ui.product.related') }}</h2>
            <style>
                #related-products-carousel-{{ $product->id }} .splide__arrow {
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

                #related-products-carousel-{{ $product->id }}:hover .splide__arrow,
                #related-products-carousel-{{ $product->id }}:focus-within .splide__arrow {
                    opacity: 1;
                    transform: translateY(-50%) scale(1);
                }

                #related-products-carousel-{{ $product->id }} .splide__arrow:hover {
                    background: rgba(15, 23, 42, 0.55);
                }

                #related-products-carousel-{{ $product->id }} .splide__arrow svg {
                    fill: #fff;
                }

                @media (hover: none) {
                    #related-products-carousel-{{ $product->id }} .splide__arrow {
                        opacity: 1;
                        transform: translateY(-50%) scale(1);
                    }
                }
            </style>
            <div class="mt-4">
                <div id="related-products-carousel-{{ $product->id }}" class="splide" data-related-products-splide>
                    <div class="splide__track">
                        <ul class="splide__list">
                            @foreach ($related as $relatedProduct)
                                <li class="splide__slide">
                                    @include('front.desktop.partials.product-card', ['product' => $relatedProduct, 'locale' => $locale, 'fallbackLocale' => $fallbackLocale, 'flat' => true])
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($bottomBlocks->isNotEmpty())
        <section class="mt-10">
            @include('components.content-placement', ['items' => $bottomBlocks])
        </section>
    @endif
@endsection

@push('scripts')
    @include('front.partials.splide-assets')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/css/lightgallery-bundle.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/lightgallery.min.js"></script>
    <script defer src="{{ asset('front-theme/scripts/product-detail.js') }}?v={{ md5_file(public_path('front-theme/scripts/product-detail.js')) }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const fitModal = document.querySelector('[data-fit-finder-modal]');
            const openButtons = document.querySelectorAll('[data-fit-finder-open]');
            const form = document.querySelector('[data-product-detail-form]');
            if (!fitModal || !openButtons.length || !form) {
                return;
            }

            const sizeInputs = Array.from(form.querySelectorAll('input[name="product_option_value_id"][data-size-label]'));
            if (sizeInputs.length < 2) {
                return;
            }

            const closeButtons = fitModal.querySelectorAll('[data-fit-finder-close]');
            const steps = Array.from(fitModal.querySelectorAll('[data-fit-step]'));
            const progress = fitModal.querySelector('[data-fit-finder-progress]');
            const nextButton = fitModal.querySelector('[data-fit-next]');
            const prevButton = fitModal.querySelector('[data-fit-prev]');
            const applyButton = fitModal.querySelector('[data-fit-apply]');

            const inputHeight = fitModal.querySelector('[data-fit-height]');
            const inputWeight = fitModal.querySelector('[data-fit-weight]');
            const inputAge = fitModal.querySelector('[data-fit-age]');

            const resultSize = [
                fitModal.querySelector('[data-fit-finder-result-size="0"]'),
                fitModal.querySelector('[data-fit-finder-result-size="1"]'),
            ];
            const resultPercent = [
                fitModal.querySelector('[data-fit-finder-result-percent="0"]'),
                fitModal.querySelector('[data-fit-finder-result-percent="1"]'),
            ];
            const resultBar = [
                fitModal.querySelector('[data-fit-finder-result-bar="0"]'),
                fitModal.querySelector('[data-fit-finder-result-bar="1"]'),
            ];
            const resultRow = [
                fitModal.querySelector('[data-fit-finder-result-row="0"]'),
                fitModal.querySelector('[data-fit-finder-result-row="1"]'),
            ];
            const summary = fitModal.querySelector('[data-fit-finder-summary]');
            const textErrorHeight = String(fitModal.dataset.textErrorHeight || 'Invalid height');
            const textErrorWeight = String(fitModal.dataset.textErrorWeight || 'Invalid weight');
            const textErrorAge = String(fitModal.dataset.textErrorAge || 'Invalid age');
            const textStepTemplate = String(fitModal.dataset.textStepTemplate || 'Step __CURRENT__ of __TOTAL__');
            const textRecommendationReady = String(fitModal.dataset.textRecommendationReady || 'Recommendation ready');
            const textSummaryTemplate = String(fitModal.dataset.textSummaryTemplate || 'Recommended size is __SIZE__ with confidence __PERCENT__%.');
            const textCtaTemplate = String(fitModal.dataset.textCtaTemplate || 'Add size __SIZE__ to cart');
            const fitSaveUrl = String(fitModal.dataset.fitSaveUrl || '');
            const fitProductId = Number(fitModal.dataset.fitProductId || 0);
            const initialSizeLabel = String(fitModal.dataset.fitInitialSize || '').trim().toUpperCase();

            const state = {
                step: 0,
                fit: String(fitModal.dataset.fitInitialFit || 'average'),
                chest: String(fitModal.dataset.fitInitialChest || 'average'),
                belly: String(fitModal.dataset.fitInitialBelly || 'average'),
                recommendation: null,
            };

            const sizeRankMap = {
                XXS: 1,
                XS: 2,
                S: 3,
                M: 4,
                L: 5,
                XL: 6,
                XXL: 7,
                XXXL: 8,
                '4XL': 9,
                '5XL': 10,
            };

            const sizeOptions = sizeInputs.map(function (input, index) {
                const label = String(input.dataset.sizeLabel || '').trim();
                const key = label.toUpperCase();
                const rank = Object.prototype.hasOwnProperty.call(sizeRankMap, key) ? sizeRankMap[key] : (index + 3);
                return { input, label, rank };
            });

            const clamp = function (value, min, max) {
                return Math.max(min, Math.min(max, value));
            };

            const persistFitPreference = function (sizeLabel) {
                if (!fitSaveUrl || !fitProductId || !sizeLabel) {
                    return;
                }

                const tokenInput = form.querySelector('input[name="_token"]');
                const token = tokenInput ? String(tokenInput.value) : '';
                if (!token) {
                    return;
                }

                const payload = new URLSearchParams();
                payload.set('_token', token);
                payload.set('product_id', String(fitProductId));
                payload.set('size_label', String(sizeLabel));
                payload.set('height', String(inputHeight.value || ''));
                payload.set('weight', String(inputWeight.value || ''));
                payload.set('age', String(inputAge.value || ''));
                payload.set('fit', String(state.fit || 'average'));
                payload.set('chest', String(state.chest || 'average'));
                payload.set('belly', String(state.belly || 'average'));

                if (typeof navigator.sendBeacon === 'function') {
                    const blob = new Blob([payload.toString()], { type: 'application/x-www-form-urlencoded;charset=UTF-8' });
                    navigator.sendBeacon(fitSaveUrl, blob);
                    return;
                }

                fetch(fitSaveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'Accept': 'application/json',
                    },
                    body: payload.toString(),
                    credentials: 'same-origin',
                    keepalive: true,
                }).catch(function () {});
            };

            const clearStepErrors = function () {
                const errors = fitModal.querySelectorAll('[data-fit-error]');
                errors.forEach(function (error) {
                    error.textContent = '';
                    error.classList.add('hidden');
                });
            };

            const setStepError = function (message) {
                const error = steps[state.step] ? steps[state.step].querySelector('[data-fit-error]') : null;
                if (!error) {
                    return;
                }
                error.textContent = message;
                error.classList.remove('hidden');
            };

            const setPressed = function (attribute, value) {
                const buttons = fitModal.querySelectorAll('[' + attribute + ']');
                buttons.forEach(function (button) {
                    const isActive = button.getAttribute(attribute) === value;
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
            };

            const validateCurrentStep = function () {
                clearStepErrors();

                if (state.step === 0) {
                    const height = Number(inputHeight.value);
                    const weight = Number(inputWeight.value);
                    if (!Number.isFinite(height) || height < 130 || height > 230) {
                        setStepError(textErrorHeight);
                        return false;
                    }
                    if (!Number.isFinite(weight) || weight < 35 || weight > 220) {
                        setStepError(textErrorWeight);
                        return false;
                    }
                }

                if (state.step === 1) {
                    const age = Number(inputAge.value);
                    if (!Number.isFinite(age) || age < 12 || age > 100) {
                        setStepError(textErrorAge);
                        return false;
                    }
                }

                return true;
            };

            const calculateRecommendation = function () {
                const height = Number(inputHeight.value);
                const weight = Number(inputWeight.value);
                const age = Number(inputAge.value);
                const heightMeters = height / 100;
                const bmi = weight / (heightMeters * heightMeters);

                let target = 4.2;
                if (bmi < 20) target -= 1;
                else if (bmi < 23) target -= 0.4;
                else if (bmi < 26) target += 0.2;
                else if (bmi < 29) target += 0.8;
                else target += 1.3;

                if (height > 188) target += 0.2;
                if (height < 170) target -= 0.2;
                if (age > 45) target += 0.2;
                if (age > 60) target += 0.25;

                if (state.fit === 'tighter') target -= 0.5;
                if (state.fit === 'looser') target += 0.5;

                if (state.chest === 'slimmer') target -= 0.2;
                if (state.chest === 'broader') target += 0.35;

                if (state.belly === 'flatter') target -= 0.15;
                if (state.belly === 'rounder') target += 0.35;

                const scored = sizeOptions
                    .map(function (option) {
                        const distance = Math.abs(option.rank - target);
                        const score = Math.max(25, 100 - (distance * 22));
                        return { option, score };
                    })
                    .sort(function (a, b) {
                        return b.score - a.score;
                    });

                const first = scored[0];
                const second = scored[1] || scored[0];
                const firstPercent = clamp(Math.round(68 + (first.score - second.score) * 0.9), 55, 93);
                let secondPercent = clamp(Math.round((100 - firstPercent) + 22), 35, 88);
                if (secondPercent >= firstPercent) {
                    secondPercent = Math.max(30, firstPercent - 8);
                }

                state.recommendation = {
                    primary: { label: first.option.label, input: first.option.input, percent: firstPercent },
                    secondary: { label: second.option.label, input: second.option.input, percent: secondPercent },
                };
            };

            const renderResults = function () {
                calculateRecommendation();
                if (!state.recommendation) {
                    return;
                }

                resultSize[0].textContent = state.recommendation.primary.label;
                resultPercent[0].textContent = state.recommendation.primary.percent + '%';
                resultBar[0].style.width = state.recommendation.primary.percent + '%';
                resultRow[0].classList.add('is-selected');

                resultSize[1].textContent = state.recommendation.secondary.label;
                resultPercent[1].textContent = state.recommendation.secondary.percent + '%';
                resultBar[1].style.width = state.recommendation.secondary.percent + '%';
                resultRow[1].classList.remove('is-selected');

                summary.textContent = textSummaryTemplate
                    .replace('__SIZE__', state.recommendation.primary.label)
                    .replace('__PERCENT__', String(state.recommendation.primary.percent));
                applyButton.textContent = textCtaTemplate.replace('__SIZE__', state.recommendation.primary.label);
            };

            const renderStep = function () {
                steps.forEach(function (step, index) {
                    step.classList.toggle('hidden', index !== state.step);
                });

                const totalInputSteps = 5;
                progress.textContent = state.step < totalInputSteps
                    ? textStepTemplate
                        .replace('__CURRENT__', String(state.step + 1))
                        .replace('__TOTAL__', String(totalInputSteps))
                    : textRecommendationReady;

                prevButton.classList.toggle('invisible', state.step === 0);
                nextButton.classList.toggle('hidden', state.step >= steps.length - 1);
                applyButton.classList.toggle('hidden', state.step < steps.length - 1);

                if (state.step === steps.length - 1) {
                    renderResults();
                }
            };

            const openModal = function () {
                state.step = 0;
                clearStepErrors();
                renderStep();
                fitModal.classList.remove('hidden');
                fitModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
            };

            const closeModal = function () {
                fitModal.classList.add('hidden');
                fitModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
            };

            openButtons.forEach(function (button) {
                button.addEventListener('click', openModal);
            });

            closeButtons.forEach(function (button) {
                button.addEventListener('click', closeModal);
            });

            fitModal.addEventListener('click', function (event) {
                if (event.target === fitModal) {
                    closeModal();
                }
            });

            nextButton.addEventListener('click', function () {
                if (!validateCurrentStep()) {
                    return;
                }
                if (state.step < steps.length - 1) {
                    state.step += 1;
                    renderStep();
                }
            });

            prevButton.addEventListener('click', function () {
                if (state.step > 0) {
                    clearStepErrors();
                    state.step -= 1;
                    renderStep();
                }
            });

            fitModal.querySelectorAll('[data-fit-fit]').forEach(function (button) {
                button.addEventListener('click', function () {
                    state.fit = button.dataset.fitFit || 'average';
                    setPressed('data-fit-fit', state.fit);
                });
            });

            fitModal.querySelectorAll('[data-fit-chest]').forEach(function (button) {
                button.addEventListener('click', function () {
                    state.chest = button.dataset.fitChest || 'average';
                    setPressed('data-fit-chest', state.chest);
                });
            });

            fitModal.querySelectorAll('[data-fit-belly]').forEach(function (button) {
                button.addEventListener('click', function () {
                    state.belly = button.dataset.fitBelly || 'average';
                    setPressed('data-fit-belly', state.belly);
                });
            });

            applyButton.addEventListener('click', function () {
                if (!state.recommendation || !state.recommendation.primary.input) {
                    return;
                }

                state.recommendation.primary.input.checked = true;
                state.recommendation.primary.input.dispatchEvent(new Event('change', { bubbles: true }));
                persistFitPreference(state.recommendation.primary.label);
                closeModal();

                const submitButton = form.querySelector('button[type="submit"]:not([form])');
                if (submitButton) {
                    form.requestSubmit(submitButton);
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !fitModal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            setPressed('data-fit-fit', state.fit);
            setPressed('data-fit-chest', state.chest);
            setPressed('data-fit-belly', state.belly);

            if (fitModal.dataset.fitInitialHeight) {
                inputHeight.value = String(fitModal.dataset.fitInitialHeight);
            }
            if (fitModal.dataset.fitInitialWeight) {
                inputWeight.value = String(fitModal.dataset.fitInitialWeight);
            }
            if (fitModal.dataset.fitInitialAge) {
                inputAge.value = String(fitModal.dataset.fitInitialAge);
            }
            if (initialSizeLabel !== '') {
                const savedSizeInput = sizeInputs.find(function (sizeInput) {
                    return String(sizeInput.dataset.sizeLabel || '').trim().toUpperCase() === initialSizeLabel;
                });
                if (savedSizeInput) {
                    savedSizeInput.checked = true;
                    savedSizeInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.querySelector('[data-size-guide-modal]');
            if (!modal) {
                return;
            }

            const openButtons = document.querySelectorAll('[data-size-guide-open]');
            const closeButtons = modal.querySelectorAll('[data-size-guide-close]');

            const openModal = function () {
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
            };

            const closeModal = function () {
                modal.classList.add('hidden');
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
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.querySelector('[data-comment-form-toggle]');
            const panel = document.querySelector('[data-comment-form-panel]');
            if (!toggle || !panel) return;

            toggle.addEventListener('click', function () {
                panel.classList.toggle('hidden');
                const isOpen = !panel.classList.contains('hidden');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            const initRelatedProductsCarousel = function () {
                if (typeof window.Splide !== 'function') {
                    return false;
                }

                const sliders = document.querySelectorAll('[data-related-products-splide]');
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
                        pagination: false,
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

            if (initRelatedProductsCarousel()) {
                return;
            }

            let attempts = 0;
            const timer = window.setInterval(function () {
                attempts += 1;
                if (initRelatedProductsCarousel() || attempts > 40) {
                    window.clearInterval(timer);
                }
            }, 120);
        });
    </script>
@endpush

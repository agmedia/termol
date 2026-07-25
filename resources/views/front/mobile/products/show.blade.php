@extends('front.mobile.layouts.store')

@php
    $translation = $product->translations->firstWhere('locale', $locale)
        ?? $product->translations->firstWhere('locale', $fallbackLocale);
    $manufacturerTranslation = $product->manufacturer?->translations?->firstWhere('locale', $locale)
        ?? $product->manufacturer?->translations?->firstWhere('locale', $fallbackLocale);
    $firstCategory = ($productBreadcrumbCategories ?? collect())->last() ?? $product->categories->first();
    $firstCategoryTranslation = $firstCategory?->translations?->firstWhere('locale', $locale)
        ?? $firstCategory?->translations?->firstWhere('locale', $fallbackLocale);
    $manufacturerEnabled = app(\App\Services\Catalog\CatalogFeatureService::class)->useManufacturers();
    $formatGrossPrice = static fn ($value): string => number_format((float) $value, 2).' €';
    $formatGrossDecimal = static fn ($value): string => number_format((float) $value, 2, '.', '');
    $formatPriceData = static function (array $priceData) use ($formatGrossPrice, $formatGrossDecimal): array {
        $oldGross = $priceData['old_gross'] ?? null;
        $lowestGross = $priceData['lowest_30_days_gross'] ?? null;
        $discountPercent = (int) ($priceData['discount_percent'] ?? 0);

        return [
            'current' => $formatGrossPrice((float) ($priceData['current_gross'] ?? 0)),
            'current_value' => $formatGrossDecimal((float) ($priceData['current_gross'] ?? 0)),
            'old' => $oldGross !== null ? $formatGrossPrice((float) $oldGross) : '',
            'discount_percent' => $discountPercent > 0 ? (string) $discountPercent : '',
            'lowest_30_days' => $lowestGross !== null
                ? __('ui.product.lowest_price_30_days', ['price' => $formatGrossPrice((float) $lowestGross)])
                : '',
        ];
    };
    $pricePresenter = app(\App\Services\Pricing\ProductPricePresentationService::class);
    $authUser = auth()->user();
    $productPriceData = $formatPriceData($pricePresentation);
    $optionPriceData = static function ($row) use ($pricePresenter, $product, $authUser, $formatPriceData): array {
        $storedBase = $row->price_override !== null
            ? (float) $row->price_override
            : (float) $product->base_price;

        return $formatPriceData($pricePresenter->forStoredBase($product, $storedBase, $authUser));
    };
    $preferWebp = (bool) ($storeSettings['images']['use_webp'] ?? false);
    $isWishlisted = app(\App\Services\Front\WishlistService::class)->has((int) $product->id);

    $mediaItems = $product->relationLoaded('media')
        ? $product->media
            ->whereIn('collection_name', ['product_main', 'product_gallery'])
            ->sortBy(static fn ($mediaItem) => (int) ($mediaItem->order_column ?? 0))
            ->values()
        : collect();
    $usableMediaItems = $mediaItems
        ->filter(fn ($mediaItem) => \App\Support\Media\MediaUrl::hasUsableSource($mediaItem, ['detail_960x960', 'thumb_100x100']))
        ->values();
    $mainMedia = $usableMediaItems->firstWhere('collection_name', 'product_main')
        ?? $usableMediaItems->firstWhere('collection_name', 'product_gallery')
        ?? $product->getMedia('*')
            ->whereIn('collection_name', ['product_main', 'product_gallery'])
            ->first(fn ($mediaItem) => \App\Support\Media\MediaUrl::hasUsableSource($mediaItem, ['detail_960x960', 'thumb_100x100']));

    $galleryItems = collect();
    if ($mainMedia) {
        $galleryItems->push($mainMedia);
    }
    foreach ($usableMediaItems as $mediaItem) {
        if ($mainMedia && (int) $mediaItem->id === (int) $mainMedia->id) {
            continue;
        }

        $galleryItems->push($mediaItem);
    }

    $gallery = $galleryItems
        ->unique(fn ($mediaItem) => (int) $mediaItem->id)
        ->map(function ($mediaItem) use ($translation, $product, $preferWebp) {
            $displayUrl = \App\Support\Media\MediaUrl::conversion($mediaItem, 'detail_960x960', $preferWebp);

            return [
                'id' => (int) $mediaItem->id,
                'full' => (string) ($displayUrl ?? $mediaItem->getUrl()),
                'alt' => (string) ($translation?->name ?? $product->code),
            ];
        })
        ->values();

    $allOptionRows = $product->visibleOptionRows();
    $availableOptionRows = $product->availableOptionRows();
    $optionRows = $availableOptionRows->isNotEmpty() ? $availableOptionRows : $allOptionRows;
    $isPurchasable = $product->storefrontIsPurchasable();
    $hasLinkedOptions = $optionRows->contains(fn ($row) => (int) ($row->parent_option_value_id ?? 0) > 0);
    $primaryOptionLabel = __('ui.cart.modal.option');
    $secondaryOptionLabel = __('ui.cart.modal.option');
    $linkedPrimaryValues = collect();
    if ($hasLinkedOptions) {
        $firstLinkedRow = $optionRows->first(fn ($row) => (int) ($row->parent_option_value_id ?? 0) > 0);
        $parentOptionTranslation = $firstLinkedRow?->parentOptionValue?->option?->translations?->firstWhere('locale', $locale)
            ?? $firstLinkedRow?->parentOptionValue?->option?->translations?->firstWhere('locale', $fallbackLocale)
            ?? $firstLinkedRow?->parentOptionValue?->option?->translations?->first();
        $childOptionTranslation = $firstLinkedRow?->optionValue?->option?->translations?->firstWhere('locale', $locale)
            ?? $firstLinkedRow?->optionValue?->option?->translations?->firstWhere('locale', $fallbackLocale)
            ?? $firstLinkedRow?->optionValue?->option?->translations?->first();
        $primaryOptionLabel = trim((string) ($parentOptionTranslation?->name ?? '')) ?: __('ui.cart.modal.option');
        $secondaryOptionLabel = trim((string) ($childOptionTranslation?->name ?? '')) ?: __('ui.cart.modal.option');

        $linkedPrimaryValues = $optionRows
            ->filter(fn ($row) => (int) ($row->parent_option_value_id ?? 0) > 0)
            ->map(function ($row) use ($locale, $fallbackLocale): array {
                $translation = $row->parentOptionValue?->translations?->firstWhere('locale', $locale)
                    ?? $row->parentOptionValue?->translations?->firstWhere('locale', $fallbackLocale)
                    ?? $row->parentOptionValue?->translations?->first();
                $label = trim((string) ($translation?->name ?? $row->parentOptionValue?->code ?? ''));

                return [
                    'id' => (int) ($row->parent_option_value_id ?? 0),
                    'label' => $label,
                ];
            })
            ->unique('id')
            ->values();
    }
    $hasProductStory = ! empty($translation?->description) || ! empty($translation?->excerpt);
    $colorVariants = $colorVariants ?? collect();
    $mobileDefaultCols = in_array((int) ($storeSettings['product']['mobile_default_cols'] ?? 2), [1, 2], true)
        ? (int) ($storeSettings['product']['mobile_default_cols'] ?? 2)
        : 2;
@endphp

@section('title', $translation?->name ?? __('ui.product.sku'))
@section('header_title', $translation?->name ?? __('ui.shop.page_title'))
@section('page_title', $translation?->name ?? __('ui.shop.page_title'))

@section('content')
    @push('head')
        <link rel="stylesheet" href="{{ asset('front-theme/styles/product-detail.css') }}?v={{ filemtime(public_path('front-theme/styles/product-detail.css')) }}">
    @endpush

    @if ($topBlocks->isNotEmpty())
        @include('components.content-placement', ['items' => $topBlocks])
    @endif

    <nav aria-label="Breadcrumb" class="product-detail-breadcrumb product-detail-breadcrumb-mobile">
        <ol>
            <li><a href="{{ route('home') }}">{{ __('ui.front.desktop.footer.home') }}</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('shop.index') }}">{{ __('ui.shop.page_title') }}</a></li>
            @foreach (($productBreadcrumbCategories ?? collect()) as $breadcrumbCategory)
                @php
                    $breadcrumbTranslation = $breadcrumbCategory->translations->firstWhere('locale', $locale)
                        ?? $breadcrumbCategory->translations->firstWhere('locale', $fallbackLocale)
                        ?? $breadcrumbCategory->translations->first();
                @endphp
                @if ($breadcrumbTranslation)
                    <li aria-hidden="true">/</li>
                    <li>
                        <a href="{{ route('categories.show', ['slug' => $breadcrumbTranslation->slug]) }}">
                            {{ $breadcrumbTranslation->name }}
                        </a>
                    </li>
                @endif
            @endforeach
            <li aria-hidden="true">/</li>
            @php $breadcrumbProductName = (string) ($translation?->name ?? $product->code); @endphp
            <li aria-current="page" title="{{ $breadcrumbProductName }}">
                {{ \Illuminate\Support\Str::limit($breadcrumbProductName, 25, '...') }}
            </li>
        </ol>
    </nav>

    <div class="card card-style">
        @if ($gallery->isNotEmpty())
            <div class="product-mobile-gallery-content content p-0" data-mobile-product-gallery>
                <div class="product-mobile-gallery-track" data-mobile-gallery-track>
                    @foreach ($gallery as $index => $image)
                        <button
                            type="button"
                            class="product-mobile-gallery-frame border-0 bg-transparent p-0 flex-shrink-0"
                            data-mobile-gallery-frame
                            data-gallery-open="{{ $index }}"
                            aria-label="{{ $image['alt'] }}"
                        >
                            <img
                                src="{{ $image['full'] }}"
                                alt="{{ $image['alt'] }}"
                                class="d-block w-100"
                                loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                decoding="async"
                            >
                        </button>
                    @endforeach
                </div>
                <div class="d-flex justify-content-center gap-2 py-3">
                    @foreach ($gallery as $index => $image)
                        <button
                            type="button"
                            class="product-mobile-gallery-dot border-0 rounded-circle bg-white/70"
                            data-mobile-gallery-dot="{{ $index }}"
                            aria-label="{{ __('ui.product.slide_aria', ['index' => $index + 1]) }}"
                        ></button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="card card-style">
        <div class="content">
            <div class="product-mobile-title-row">
                <h2 class="mb-0" data-product-price-current>{{ $productPriceData['current'] }}</h2>
                <form
                    id="mobile-wishlist-product-{{ $product->id }}"
                    method="POST"
                    action="{{ route('wishlist.items.toggle', ['product' => $product->id]) }}"
                    data-wishlist-form
                    data-wishlisted="{{ $isWishlisted ? '1' : '0' }}"
                    data-label-add="{{ __('ui.wishlist.add') }}"
                    data-label-remove="{{ __('ui.wishlist.remove') }}"
                    data-msg-failed="{{ __('ui.wishlist.status.failed') }}"
                >
                    @csrf
                    <button
                        type="submit"
                        class="product-card-wishlist product-detail-wishlist {{ $isWishlisted ? 'is-active' : '' }}"
                        aria-label="{{ $isWishlisted ? __('ui.wishlist.remove') : __('ui.wishlist.add') }}"
                        data-wishlist-button
                    >
                        <svg class="fa6-icon h-5 w-5" fill="currentColor" aria-hidden="true" focusable="false">
                            <use href="{{ asset('front-theme/fonts/sprites/'.($isWishlisted ? 'solid' : 'regular').'.svg') }}#heart"></use>
                        </svg>
                    </button>
                </form>
            </div>
            <p class="font-12 opacity-60 mb-2">{{ __('ui.product.sku') }} <span data-product-sku-value>{{ $product->sku ?: $product->code ?: 'n/a' }}</span></p>

            @if ($manufacturerTranslation && $manufacturerEnabled)
                <p class="font-12 mb-3">
                    <a href="{{ route('manufacturers.show', ['slug' => $manufacturerTranslation->slug]) }}" class="color-highlight">{{ $manufacturerTranslation->name }}</a>
                </p>
            @endif

            @if ($colorVariants->isNotEmpty())
                <div class="divider mt-2 mb-3"></div>
                <div class="mb-3" data-product-color-variants>
                    <p class="font-600 mb-2">{{ __('ui.product.color_variants') }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($colorVariants as $variant)
                            <a
                                href="{{ $variant['url'] }}"
                                class="mobile-product-color-variant-link"
                                title="{{ $variant['label'] }}"
                                aria-label="{{ $variant['label'] }}"
                                @if ($variant['is_current']) aria-current="true" @endif
                                data-color-variant-link
                                data-color-variant-label="{{ $variant['label'] }}"
                            >
                                <span
                                    class="mobile-product-color-variant-swatch"
                                    data-color-variant-swatch
                                    data-swatch-image="{{ $variant['swatch_image_url'] ?? '' }}"
                                    data-swatch-style="{{ $variant['swatch_style'] ?? '' }}"
                                    aria-hidden="true"
                                ></span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('cart.items.store') }}"
                data-product-detail-form
                data-ga4-add-to-cart-form
                data-ga4-item-id="{{ (string) ($product->sku ?: $product->id) }}"
                data-ga4-item-name="{{ $translation?->name ?? $product->code }}"
                data-ga4-item-price="{{ $productPriceData['current_value'] }}"
                data-ga4-item-brand="{{ (string) ($manufacturerTranslation?->name ?? '') }}"
                data-ga4-item-category="{{ (string) ($firstCategoryTranslation?->name ?? '') }}"
                data-ga4-currency="EUR"
                data-product-base-sku="{{ (string) ($product->sku ?: $product->code ?: '') }}"
                data-product-fallback-id="{{ (int) $product->id }}"
                data-product-name="{{ $translation?->name ?? $product->code }}"
                data-product-image="{{ (string) (($gallery->first()['full'] ?? '') ?: '') }}"
                data-cart-url="{{ route('cart.index') }}"
                data-modal-continue="{{ __('ui.cart.modal.continue') }}"
                data-modal-go-cart="{{ __('ui.cart.modal.go_to_cart') }}"
                data-modal-option="{{ __('ui.cart.modal.option') }}"
                data-modal-quantity="{{ __('ui.cart.modal.quantity') }}"
                data-option-error-required="{{ __('ui.cart.errors.select_size') }}"
                data-option-error-unavailable="{{ __('ui.cart.status.unavailable') }}"
                data-product-default-price-current="{{ $productPriceData['current'] }}"
                data-product-default-price-current-value="{{ $productPriceData['current_value'] }}"
                data-product-default-price-old="{{ $productPriceData['old'] }}"
                data-product-default-price-discount="{{ $productPriceData['discount_percent'] }}"
                data-product-default-price-lowest="{{ $productPriceData['lowest_30_days'] }}"
            >
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                @if ($optionRows->isNotEmpty())
                    @if ($hasLinkedOptions)
                        <div class="input-style has-borders no-icon input-style-always-active mb-3">
                            <label class="color-highlight">{{ $primaryOptionLabel }}</label>
                            <select data-linked-option-primary>
                                <option value="">{{ __('ui.shop.filters.select_option') }}</option>
                                @foreach ($linkedPrimaryValues as $primaryValue)
                                    <option value="{{ $primaryValue['id'] }}">{{ $primaryValue['label'] }}</option>
                                @endforeach
                            </select>
                            <span><i class="fa fa-chevron-down"></i></span>
                        </div>
                        <div class="input-style has-borders no-icon input-style-always-active mb-3">
                            <label class="color-highlight">{{ $secondaryOptionLabel }}</label>
                            <select name="product_option_value_id" data-linked-option-secondary disabled>
                                <option value="">{{ __('ui.shop.filters.select_option') }}</option>
                                @foreach ($optionRows as $row)
                                    @php
                                        $valueTranslation = $row->optionValue?->translations?->firstWhere('locale', $locale)
                                            ?? $row->optionValue?->translations?->firstWhere('locale', $fallbackLocale)
                                            ?? $row->optionValue?->translations?->first();
                                        $label = trim((string) ($valueTranslation?->name ?? $row->optionValue?->code ?? ''));
                                        $rowPriceData = $optionPriceData($row);
                                    @endphp
                                    <option
                                        value="{{ $row->id }}"
                                        data-parent-id="{{ (int) ($row->parent_option_value_id ?? 0) }}"
                                        data-option-sku="{{ (string) ($row->sku ?: '') }}"
                                        data-option-price-current="{{ $rowPriceData['current'] }}"
                                        data-option-price-current-value="{{ $rowPriceData['current_value'] }}"
                                        data-option-price-old="{{ $rowPriceData['old'] }}"
                                        data-option-price-discount="{{ $rowPriceData['discount_percent'] }}"
                                        data-option-price-lowest="{{ $rowPriceData['lowest_30_days'] }}"
                                    >{{ $label }}</option>
                                @endforeach
                            </select>
                            <span><i class="fa fa-chevron-down"></i></span>
                        </div>
                    @else
                        <div class="input-style has-borders no-icon input-style-always-active mb-3">
                            <label for="product-option" class="color-highlight">{{ __('ui.product.select_size') }}</label>
                            <select id="product-option" name="product_option_value_id">
                                <option value="">{{ __('ui.shop.filters.select_option') }}</option>
                                @foreach ($optionRows as $row)
                                    @php
                                        $valueTranslation = $row->optionValue?->translations?->firstWhere('locale', $locale)
                                            ?? $row->optionValue?->translations?->firstWhere('locale', $fallbackLocale)
                                            ?? $row->optionValue?->translations?->first();
                                        $label = trim((string) ($valueTranslation?->name ?? $row->optionValue?->code ?? ''));
                                        $rowPriceData = $optionPriceData($row);
                                    @endphp
                                    <option
                                        value="{{ $row->id }}"
                                        data-option-sku="{{ (string) ($row->sku ?: '') }}"
                                        data-option-price-current="{{ $rowPriceData['current'] }}"
                                        data-option-price-current-value="{{ $rowPriceData['current_value'] }}"
                                        data-option-price-old="{{ $rowPriceData['old'] }}"
                                        data-option-price-discount="{{ $rowPriceData['discount_percent'] }}"
                                        data-option-price-lowest="{{ $rowPriceData['lowest_30_days'] }}"
                                    >{{ $label }}</option>
                                @endforeach
                            </select>
                            <span><i class="fa fa-chevron-down"></i></span>
                        </div>
                    @endif
                    <p class="font-11 color-red-dark mb-3 d-none" data-option-error>{{ __('ui.cart.errors.select_size') }}</p>
                    @if (!empty($sizeGuide))
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn p-0 font-600 text-uppercase font-11" data-size-guide-open>
                                {{ __('ui.product.size_guide') }}
                            </button>
                        </div>
                    @endif
                @endif

                <div class="product-mobile-purchase-controls">
                    @if ($isPurchasable)
                        <div class="product-detail-quantity-control" data-qty-control>
                            <button type="button" data-qty-dec aria-label="{{ __('ui.cart.modal.quantity') }} -">-</button>
                            <input type="text" name="quantity" value="1" inputmode="numeric" readonly aria-label="{{ __('ui.cart.modal.quantity') }}" data-qty-input>
                            <button type="button" data-qty-inc aria-label="{{ __('ui.cart.modal.quantity') }} +">+</button>
                        </div>

                        <button type="submit" class="product-detail-action product-detail-cart-button">
                            <x-fa-icon name="bag-shopping" class="product-mobile-cart-icon" />
                            {{ __('ui.product.add_to_cart') }}
                        </button>
                    @else
                        <button type="button" disabled class="product-detail-cart-button product-detail-cart-button-disabled">
                            {{ __('ui.product.unavailable') }}
                        </button>
                    @endif
                </div>
            </form>

            @include('front.partials.product-purchase-information', [
                'product' => $product,
                'manufacturerTranslation' => $manufacturerTranslation,
                'firstCategoryTranslation' => $firstCategoryTranslation,
                'shippingMethods' => $shippingMethods ?? collect(),
                'paymentMethods' => $paymentMethods ?? collect(),
            ])
        </div>
    </div>

    @include('front.partials.product-detail-content', [
        'product' => $product,
        'translation' => $translation,
        'locale' => $locale,
        'fallbackLocale' => $fallbackLocale,
        'hasProductStory' => $hasProductStory,
        'comments' => $comments ?? collect(),
    ])

    @if (!empty($sizeGuide) && $optionRows->isNotEmpty())
        <div class="fixed inset-0 z-[120] hidden items-end justify-center bg-black/50" data-size-guide-modal aria-hidden="true">
            <div class="w-full max-h-[88vh] overflow-hidden bg-white shadow-2xl sm:mx-auto sm:max-w-4xl">
                <div class="d-flex justify-content-between align-items-center border-bottom px-4 py-3">
                    <h4 class="mb-0">{{ $sizeGuide['title'] ?: __('ui.product.size_guide') }}</h4>
                    <button type="button" class="btn btn-border border-slate-300 color-theme rounded-0 font-11 text-uppercase px-3 py-2" data-size-guide-close>
                        {{ __('ui.product.size_guide_close') }}
                    </button>
                </div>
                <div class="product-size-guide-scroll px-4 py-3 overflow-auto">
                    <div class="content-richtext">{!! $sizeGuide['body_html'] !!}</div>
                </div>
            </div>
        </div>
    @endif

    @if ($related->isNotEmpty())
        <section class="product-products-widget px-3">
            <h2 class="product-products-widget-heading">{{ __('ui.product.related') }}</h2>
            @if ($related->count() > $mobileDefaultCols)
                @include('front.partials.carousel-swipe-hint')
            @endif
            <div
                id="mobile-related-products-carousel-{{ $product->id }}"
                class="splide"
                data-related-products-splide
                data-desktop-cols="5"
                data-mobile-cols="{{ $mobileDefaultCols }}"
            >
                <div class="splide__track">
                    <ul class="splide__list">
                        @foreach ($related as $relatedProduct)
                            <li class="splide__slide">
                                @include('front.mobile.partials.product-widget-card', [
                                    'product' => $relatedProduct,
                                    'locale' => $locale,
                                    'fallbackLocale' => $fallbackLocale,
                                ])
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>
    @endif

    @if (($recentlyViewed ?? collect())->isNotEmpty())
        <section class="product-products-widget px-3">
            <h2 class="product-products-widget-heading">{{ __('ui.product.recently_viewed') }}</h2>
            @if ($recentlyViewed->count() > $mobileDefaultCols)
                @include('front.partials.carousel-swipe-hint')
            @endif
            <div
                id="mobile-recently-viewed-products-carousel-{{ $product->id }}"
                class="splide"
                data-related-products-splide
                data-desktop-cols="5"
                data-mobile-cols="{{ $mobileDefaultCols }}"
            >
                <div class="splide__track">
                    <ul class="splide__list">
                        @foreach ($recentlyViewed as $recentlyViewedProduct)
                            <li class="splide__slide">
                                @include('front.mobile.partials.product-widget-card', [
                                    'product' => $recentlyViewedProduct,
                                    'locale' => $locale,
                                    'fallbackLocale' => $fallbackLocale,
                                ])
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>
    @endif

    @if ($bottomBlocks->isNotEmpty())
        @include('components.content-placement', ['items' => $bottomBlocks])
    @endif
@endsection

@push('scripts')
    @include('front.partials.splide-assets')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/css/lightgallery-bundle.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/lightgallery.min.js"></script>
    <script defer src="{{ asset('front-theme/scripts/product-detail.js') }}?v={{ filemtime(public_path('front-theme/scripts/product-detail.js')) }}"></script>
    <script defer src="{{ asset('front-theme/scripts/product-page.js') }}?v={{ filemtime(public_path('front-theme/scripts/product-page.js')) }}"></script>
    <script defer src="{{ asset('front-theme/scripts/product-card-quantity.js') }}?v={{ filemtime(public_path('front-theme/scripts/product-card-quantity.js')) }}"></script>
@endpush

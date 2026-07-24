@extends('front.desktop.layouts.store')

@section('body_class', 'catalog-category-page')

@php
    $showManufacturers = app(\App\Services\Catalog\CatalogFeatureService::class)->useManufacturers();
    $translation = $category->translations->firstWhere('locale', $locale)
        ?? $category->translations->firstWhere('locale', $fallbackLocale);
    $hasSubcategories = ($subcategories ?? collect())->isNotEmpty();
    $mobileDefaultCols = in_array((int) ($storeSettings['product']['mobile_default_cols'] ?? 2), [1, 2], true)
        ? (int) ($storeSettings['product']['mobile_default_cols'] ?? 2)
        : 2;
    $desktopDefaultCols = in_array((int) ($storeSettings['product']['desktop_default_cols'] ?? 4), [4, 5], true)
        ? (int) ($storeSettings['product']['desktop_default_cols'] ?? 4)
        : 4;
    $showCategoryFilters = (bool) ($showCategoryFilters ?? true);
    $showCategoryProducts = (bool) ($showCategoryProducts ?? true);
    $filterPanelSettings = is_array($storeSettings['product']['filter_panel_settings'] ?? null)
        ? $storeSettings['product']['filter_panel_settings']
        : [];
    $resolveFilterPanel = static function (string $key) use ($filterPanelSettings): array {
        $settings = is_array($filterPanelSettings[$key] ?? null) ? $filterPanelSettings[$key] : [];
        $maxHeight = (int) ($settings['max_height'] ?? 286);

        return [
            'visible' => array_key_exists('visible', $settings) ? (bool) $settings['visible'] : true,
            'default_open' => array_key_exists('default_open', $settings) ? (bool) $settings['default_open'] : true,
            'max_height' => in_array($maxHeight, [160, 220, 286, 360], true) ? $maxHeight : 286,
        ];
    };
    $categoryFilterPanel = $resolveFilterPanel('category');
    $manufacturerFilterPanel = $resolveFilterPanel('manufacturer');
    $priceFilterPanel = $resolveFilterPanel('price');
    $currentCols = (int) ($filters['cols'] ?? $desktopDefaultCols);
    $mobileCols = in_array($currentCols, [1, 2], true) ? $currentCols : $mobileDefaultCols;
    $paginationMode = (string) ($storeSettings['product']['catalog_pagination_mode'] ?? 'pagination');
    $useAsyncPagination = in_array($paginationMode, ['load_more', 'infinite'], true);
    $isInfinitePagination = $paginationMode === 'infinite';
    $desktopFilterSelectCount = ($hasSubcategories ? 1 : 0) + ($showManufacturers ? 1 : 0) + count($optionFilters ?? []) + count($attributeFilters ?? []) + 1;
    $hasActiveFilters = trim((string) ($filters['q'] ?? '')) !== ''
        || trim((string) ($filters['manufacturer'] ?? '')) !== ''
        || trim((string) ($filters['price_min'] ?? '')) !== ''
        || trim((string) ($filters['price_max'] ?? '')) !== ''
        || (bool) ($filters['available_only'] ?? false)
        || (bool) ($filters['promo_only'] ?? false)
        || collect(array_keys(request()->query()))
            ->contains(fn ($key): bool => str_starts_with((string) $key, 'opt_') || str_starts_with((string) $key, 'attr_'))
        || (string) ($filters['sort'] ?? 'default') !== 'default';
    $priceMinValue = trim((string) ($filters['price_min'] ?? ''));
    $priceMaxValue = trim((string) ($filters['price_max'] ?? ''));
    $availableOnlyEnabled = (bool) ($filters['available_only'] ?? false);
    $promoOnlyEnabled = (bool) ($filters['promo_only'] ?? false);
    $promoToggleDisabled = ! (bool) ($promoFilterAvailable ?? false) && ! $promoOnlyEnabled;
    $hasPriceFilter = $priceMinValue !== '' || $priceMaxValue !== '';
    $hasPricePanelFilter = $hasPriceFilter || $promoOnlyEnabled;
    $priceTriggerLabel = __('ui.shop.filters.price');
    $resolvedPriceBoundsMin = isset($priceBounds['min']) && is_numeric($priceBounds['min'] ?? null)
        ? (float) $priceBounds['min']
        : null;
    $resolvedPriceBoundsMax = isset($priceBounds['max']) && is_numeric($priceBounds['max'] ?? null)
        ? (float) $priceBounds['max']
        : null;
    $priceSliderMin = $resolvedPriceBoundsMin !== null
        ? (int) floor($resolvedPriceBoundsMin)
        : ($priceMinValue !== '' ? (int) floor((float) $priceMinValue) : 0);
    $priceSliderMax = $resolvedPriceBoundsMax !== null
        ? (int) ceil($resolvedPriceBoundsMax)
        : ($priceMaxValue !== '' ? (int) ceil((float) $priceMaxValue) : max($priceSliderMin, 100));

    if ($priceSliderMax <= $priceSliderMin) {
        $priceSliderMax = $priceSliderMin + ($priceSliderMin > 0 ? 10 : 100);
    }

    $priceSliderSelectedMin = $priceMinValue !== ''
        ? max($priceSliderMin, min($priceSliderMax, (int) floor((float) $priceMinValue)))
        : $priceSliderMin;
    $priceSliderSelectedMax = $priceMaxValue !== ''
        ? max($priceSliderMin, min($priceSliderMax, (int) ceil((float) $priceMaxValue)))
        : $priceSliderMax;

    if ($priceSliderSelectedMin > $priceSliderSelectedMax) {
        [$priceSliderSelectedMin, $priceSliderSelectedMax] = [$priceSliderSelectedMax, $priceSliderSelectedMin];
    }

    $sizeFilterLabel = mb_strtolower(trim((string) __('ui.shop.filters.size')));
    $sizeOptionFilter = null;
    $compositionAttributeFilter = null;
    $orderedCategoryFilters = collect();

    foreach (($optionFilters ?? []) as $filterOption) {
        $queryKey = (string) ($filterOption['query_key'] ?? '');
        $label = mb_strtolower(trim((string) ($filterOption['label'] ?? '')));

        if ($queryKey === 'size' || $label === $sizeFilterLabel) {
            $sizeOptionFilter = $filterOption;
            continue;
        }

        $orderedCategoryFilters->push($filterOption);
    }

    foreach (($attributeFilters ?? []) as $attributeFilter) {
        if (in_array((string) ($attributeFilter['query_key'] ?? ''), ['attr_sastav', 'attr_material'], true)) {
            $compositionAttributeFilter = $attributeFilter;
            continue;
        }

        $orderedCategoryFilters->push($attributeFilter);
    }

    if ($compositionAttributeFilter !== null) {
        if ($sizeOptionFilter !== null) {
            $orderedCategoryFilters->prepend($compositionAttributeFilter);
            $orderedCategoryFilters->prepend($sizeOptionFilter);
        } else {
            $orderedCategoryFilters->prepend($compositionAttributeFilter);
        }
    } elseif ($sizeOptionFilter !== null) {
        $orderedCategoryFilters->prepend($sizeOptionFilter);
    }

    $gridClass = match ($currentCols) {
        1 => 'grid grid-cols-1',
        2 => 'grid grid-cols-2',
        3 => 'grid '.($mobileDefaultCols === 2 ? 'grid-cols-2 ' : '').'sm:grid-cols-2 xl:grid-cols-3',
        5 => 'grid '.($mobileDefaultCols === 2 ? 'grid-cols-2 ' : '').'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5',
        default => 'grid '.($mobileDefaultCols === 2 ? 'grid-cols-2 ' : '').'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
    };
    $mobileGridIcons = [1 => 'list', 2 => 'table-columns'];
    $desktopGridIcons = [3 => 'table-cells-large', 4 => 'table-cells', 5 => 'grip'];
@endphp

@section('title', ($translation?->name ?? __('ui.category.fallback_name')).' '.__('ui.category.products_suffix'))
@section('main_class', 'w-full px-0 pt-3 pb-4 sm:pt-3 sm:pb-6')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/category-catalog.css') }}?v={{ filemtime(public_path('front-theme/styles/category-catalog.css')) }}">
@endpush

@section('content')

    <section class="storefront-container px-3 sm:px-4 lg:px-6">
        <div class="front-soft-hero px-4 py-4 text-center sm:px-6 sm:py-5">
        <nav aria-label="Breadcrumb" class="mb-2">
            <ol class="flex flex-wrap items-center justify-center gap-1.5 text-[11px] font-medium uppercase tracking-wide text-slate-500 sm:gap-2">
                <li>
                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center text-slate-500 hover:text-slate-700">{{ __('ui.front.desktop.footer.home') }}</a>
                </li>
                @foreach (($breadcrumbCategories ?? collect()) as $breadcrumbCategory)
                    @php
                        $breadcrumbTranslation = $breadcrumbCategory->translations->firstWhere('locale', $locale)
                            ?? $breadcrumbCategory->translations->firstWhere('locale', $fallbackLocale);
                        $breadcrumbLabel = $breadcrumbTranslation?->name ?? $breadcrumbCategory->code;
                    @endphp
                    <li class="text-slate-400">/</li>
                    @if ($loop->last)
                        <li class="text-slate-700">{{ $breadcrumbLabel }}</li>
                    @else
                        <li>
                            <a href="{{ route('categories.show', ['slug' => $breadcrumbTranslation?->slug ?? $breadcrumbCategory->id]) }}" class="hover:text-slate-700">{{ $breadcrumbLabel }}</a>
                        </li>
                    @endif
                @endforeach
            </ol>
        </nav>
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $translation?->name ?? $category->code }}</h1>
        @if ($showCategoryProducts)
            <p class="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500">{{ (int) $products->total() }} {{ __('ui.cart.summary.total') }}</p>
        @endif
        </div>
    </section>

    @if ($topBlocks->isNotEmpty())
        <section class="storefront-container mb-8 px-3 sm:px-4 lg:px-6">
            @include('components.content-placement', ['items' => $topBlocks])
        </section>
    @endif

    @if ($showCategoryFilters)
        <section class="storefront-container relative z-20 px-3 pt-3 pb-4 sm:px-4 lg:px-6">
            <div class="catalog-filter-sticky-shell">
            <div class="catalog-filter-sticky-bar border-b border-slate-200/90 pb-4">
        <div class="catalog-mobile-filter-rail max-[1024px]:block min-[1025px]:hidden" data-mobile-filter-root>
            <div class="catalog-mobile-filter-toolbar">
                <button
                    type="button"
                    class="catalog-mobile-filter-trigger"
                    data-mobile-filter-toggle
                    aria-expanded="false"
                    aria-controls="category-mobile-filter-drawer"
                >
                    <x-fa-icon name="filter" class="h-4 w-4" />
                    <span class="catalog-mobile-filter-trigger-label">{{ __('ui.shop.filters.open') }}</span>
                </button>
                @if ($hasActiveFilters)
                    <a href="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" class="catalog-mobile-filter-reset" aria-label="{{ __('ui.shop.filters.reset') }}">
                        <x-fa-icon name="rotate-left" class="h-3.5 w-3.5" />
                        <span>{{ __('ui.shop.filters.reset') }}</span>
                    </a>
                @endif
                <div class="catalog-mobile-grid-group">
                @foreach ([1, 2] as $cols)
                    <a
                        href="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id] + array_merge(request()->query(), ['cols' => $cols])) }}"
                        class="catalog-mobile-grid-toggle {{ $mobileCols === $cols ? 'is-active' : '' }}"
                        aria-label="{{ __('ui.shop.filters.grid') }} {{ $cols }}"
                    >
                        <x-fa-icon name="{{ $mobileGridIcons[$cols] }}" class="h-4 w-4" />
                    </a>
                @endforeach
                </div>
            </div>
            <div class="catalog-mobile-filter-drawer hidden" data-mobile-filter-drawer id="category-mobile-filter-drawer">
                <button type="button" class="catalog-mobile-filter-drawer-backdrop" data-mobile-filter-close aria-label="{{ __('ui.front.desktop.close_navigation') }}"></button>
                <div class="catalog-mobile-filter-drawer-panel">
                    <div class="catalog-mobile-filter-drawer-header">
                        <h2 class="catalog-mobile-filter-drawer-title">{{ __('ui.shop.filters.open') }}</h2>
                        <button type="button" class="catalog-mobile-filter-drawer-close" data-mobile-filter-close aria-label="{{ __('ui.front.desktop.close_navigation') }}">
                            <x-fa-icon name="xmark" class="h-5 w-5" />
                        </button>
                    </div>
                    <form method="GET" action="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" class="catalog-mobile-filter-panel grid auto-rows-min gap-4" data-mobile-filter-panel id="category-mobile-filter-panel">
                        <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                        @if ($hasSubcategories && $categoryFilterPanel['visible'])
                            <div class="catalog-mobile-filter-group">
                                <select
                                    id="shop-category-mobile"
                                    class="catalog-mobile-filter-select"
                                    aria-label="{{ __('ui.shop.filters.category') }}"
                                    data-category-redirect
                                    data-default-url="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}"
                                >
                                    <option value="" data-url="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" @selected(true)>{{ __('ui.shop.filters.category') }}</option>
                                    @foreach (($subcategories ?? collect()) as $subCategory)
                                        @php
                                            $subCategoryTranslation = $subCategory->translations->firstWhere('locale', $locale)
                                                ?? $subCategory->translations->firstWhere('locale', $fallbackLocale);
                                        @endphp
                                        <option value="{{ $subCategoryTranslation?->slug }}" data-url="{{ $subCategoryTranslation?->slug ? route('categories.show', ['slug' => $subCategoryTranslation->slug]) : route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}">
                                            {{ $subCategoryTranslation?->name ?? $subCategory->code }} ({{ $subCategory->products_count }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        @if ($showManufacturers && $manufacturerFilterPanel['visible'])
                            <div class="catalog-mobile-filter-group">
                                <select id="shop-manufacturer-mobile" name="manufacturer" class="catalog-mobile-filter-select" aria-label="{{ __('ui.shop.filters.manufacturer') }}" data-auto-submit-filter>
                                    <option value="">{{ __('ui.shop.filters.manufacturer') }}</option>
                                    @foreach ($manufacturers as $manufacturer)
                                        @php
                                            $manufacturerTranslation = $manufacturer->translations->firstWhere('locale', $locale)
                                                ?? $manufacturer->translations->firstWhere('locale', $fallbackLocale);
                                        @endphp
                                        <option value="{{ $manufacturerTranslation?->slug }}" @selected(($filters['manufacturer'] ?? '') === ($manufacturerTranslation?->slug ?? ''))>
                                            {{ $manufacturerTranslation?->name ?? $manufacturer->code }} ({{ $manufacturer->products_count }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        @foreach ($orderedCategoryFilters as $filterOption)
                            <div class="catalog-mobile-filter-group">
                                <select name="{{ $filterOption['query_key'] }}" class="catalog-mobile-filter-select" aria-label="{{ $filterOption['label'] }}" data-auto-submit-filter>
                                    <option value="">{{ $filterOption['label'] }}</option>
                                    @foreach (($filterOption['values'] ?? []) as $value)
                                        <option value="{{ $value['id'] }}" @selected((string) ($filterOption['selected'] ?? '') === (string) $value['id'])>
                                            {{ $value['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                        <div class="catalog-mobile-filter-group">
                            <div class="catalog-mobile-price-card">
                                <label class="flex items-center justify-between gap-4">
                                    <span class="catalog-price-promo-label">{{ __('ui.shop.filters.available_only') }}</span>
                                    <span class="catalog-switch" aria-hidden="true">
                                        <input type="checkbox" name="available_only" value="1" @checked($availableOnlyEnabled) data-auto-submit-filter aria-label="{{ __('ui.shop.filters.available_only') }}">
                                        <span class="catalog-switch-track"></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        @if ($priceFilterPanel['visible'])
                        <div class="catalog-mobile-filter-group">
                            <div class="catalog-mobile-price-card">
                                <span class="catalog-mobile-price-heading">{{ __('ui.shop.filters.price') }}</span>
                                <div
                                    data-price-range-root
                                    data-price-min-bound="{{ $priceSliderMin }}"
                                    data-price-max-bound="{{ $priceSliderMax }}"
                                >
                                    <input type="hidden" name="price_min" value="{{ $priceMinValue }}" data-price-range-hidden-min>
                                    <input type="hidden" name="price_max" value="{{ $priceMaxValue }}" data-price-range-hidden-max>
                                    <div class="catalog-price-range-values">
                                        <div class="catalog-price-range-value">
                                            <span class="catalog-price-range-value-label">{{ __('ui.shop.filters.price_from') }}</span>
                                            <span class="catalog-price-range-value-amount" data-price-range-current-min>{{ $priceSliderSelectedMin }} €</span>
                                        </div>
                                        <div class="catalog-price-range-value text-right">
                                            <span class="catalog-price-range-value-label">{{ __('ui.shop.filters.price_to') }}</span>
                                            <span class="catalog-price-range-value-amount" data-price-range-current-max>{{ $priceSliderSelectedMax }} €</span>
                                        </div>
                                    </div>
                                    <div class="catalog-price-range-slider">
                                        <div class="catalog-price-range-track"></div>
                                        <div class="catalog-price-range-progress" data-price-range-progress></div>
                                        <input type="range" min="{{ $priceSliderMin }}" max="{{ $priceSliderMax }}" step="1" value="{{ $priceSliderSelectedMin }}" data-price-range-min>
                                        <input type="range" min="{{ $priceSliderMin }}" max="{{ $priceSliderMax }}" step="1" value="{{ $priceSliderSelectedMax }}" data-price-range-max>
                                    </div>
                                    <div class="catalog-price-range-scale">
                                        <span>{{ $priceSliderMin }} €</span>
                                        <span>{{ $priceSliderMax }} €</span>
                                    </div>
                                    <div class="catalog-price-promo-toggle">
                                        <div class="catalog-price-promo-copy">
                                            <span class="catalog-price-promo-label">{{ __('ui.shop.filters.promotion_only') }}</span>
                                            <span class="catalog-price-promo-hint">{{ __('ui.shop.filters.promotion_only_hint') }}</span>
                                        </div>
                                        <label class="catalog-switch" aria-label="{{ __('ui.shop.filters.promotion_only') }}">
                                            <input type="checkbox" name="promo_only" value="1" @checked($promoOnlyEnabled) @disabled($promoToggleDisabled) data-price-range-promo>
                                            <span class="catalog-switch-track" aria-hidden="true"></span>
                                        </label>
                                    </div>
                                    <button type="button" class="catalog-price-reset" data-price-filter-reset @disabled(! $hasPricePanelFilter)>
                                        <x-fa-icon name="rotate-left" class="h-3.5 w-3.5" />
                                        <span>{{ __('ui.shop.filters.reset') }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="catalog-mobile-filter-group">
                            <select id="shop-sort-mobile" name="sort" class="catalog-mobile-filter-select" aria-label="{{ __('ui.shop.filters.sort') }}" data-auto-submit-filter>
                                <option value="default" @selected(($filters['sort'] ?? 'default') === 'default')>{{ __('ui.shop.filters.default') }}</option>
                                <option value="newest" @selected(($filters['sort'] ?? '') === 'newest')>{{ __('ui.shop.filters.newest') }}</option>
                                <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('ui.shop.filters.oldest') }}</option>
                                <option value="price_low" @selected(($filters['sort'] ?? '') === 'price_low')>{{ __('ui.shop.filters.price_low') }}</option>
                                <option value="price_high" @selected(($filters['sort'] ?? '') === 'price_high')>{{ __('ui.shop.filters.price_high') }}</option>
                                <option value="stock_high" @selected(($filters['sort'] ?? '') === 'stock_high')>{{ __('ui.shop.filters.stock_high') }}</option>
                            </select>
                        </div>
                        <input type="hidden" name="cols" value="{{ $mobileCols }}">
                        @if ($hasActiveFilters)
                            <div class="catalog-mobile-reset-wrap" data-global-filter-reset>
                                <a href="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" class="catalog-mobile-reset-link">
                                    <x-fa-icon name="rotate-left" class="h-3.5 w-3.5" />
                                    <span>{{ __('ui.shop.filters.reset') }}</span>
                                </a>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="catalog-desktop-toolbar hidden min-[1025px]:flex">
            <div class="catalog-desktop-toolbar-toggles">
                <label class="catalog-toolbar-toggle">
                    <span>{{ __('ui.shop.filters.available_only') }}</span>
                    <span class="catalog-switch">
                        <input
                            type="checkbox"
                            name="available_only"
                            value="1"
                            form="category-desktop-filter-form"
                            @checked($availableOnlyEnabled)
                            data-auto-submit-filter
                            aria-label="{{ __('ui.shop.filters.available_only') }}"
                        >
                        <span class="catalog-switch-track" aria-hidden="true"></span>
                    </span>
                </label>
                <label class="catalog-toolbar-toggle">
                    <span>{{ __('ui.shop.filters.promotion_only') }}</span>
                    <span class="catalog-switch">
                        <input
                            type="checkbox"
                            name="promo_only"
                            value="1"
                            form="category-desktop-filter-form"
                            @checked($promoOnlyEnabled)
                            @disabled($promoToggleDisabled)
                            data-auto-submit-filter
                            aria-label="{{ __('ui.shop.filters.promotion_only') }}"
                        >
                        <span class="catalog-switch-track" aria-hidden="true"></span>
                    </span>
                </label>
            </div>
            <div class="catalog-desktop-toolbar-actions">
                <div class="catalog-filter-sort-wrap w-[180px]">
                    <select
                        id="shop-sort"
                        name="sort"
                        form="category-desktop-filter-form"
                        class="catalog-filter-select catalog-filter-inline-select h-9 w-full rounded-none border-slate-300 text-sm"
                        data-auto-submit-filter
                    >
                        <option value="default" @selected(($filters['sort'] ?? 'default') === 'default')>{{ __('ui.shop.filters.default') }}</option>
                        <option value="newest" @selected(($filters['sort'] ?? '') === 'newest')>{{ __('ui.shop.filters.newest') }}</option>
                        <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('ui.shop.filters.oldest') }}</option>
                        <option value="price_low" @selected(($filters['sort'] ?? '') === 'price_low')>{{ __('ui.shop.filters.price_low') }}</option>
                        <option value="price_high" @selected(($filters['sort'] ?? '') === 'price_high')>{{ __('ui.shop.filters.price_high') }}</option>
                        <option value="stock_high" @selected(($filters['sort'] ?? '') === 'stock_high')>{{ __('ui.shop.filters.stock_high') }}</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    @foreach ([3, 4, 5] as $cols)
                        <a
                            href="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id] + array_merge(request()->query(), ['cols' => $cols])) }}"
                            class="catalog-grid-toggle {{ $cols === 5 ? 'hidden 2xl:inline-flex' : 'inline-flex' }} {{ (int) ($filters['cols'] ?? 4) === $cols ? 'is-active' : '' }}"
                            aria-label="{{ __('ui.shop.filters.grid') }} {{ $cols }}"
                        >
                            <x-fa-icon name="{{ $desktopGridIcons[$cols] }}" class="h-4 w-4" />
                        </a>
                    @endforeach
                </div>
                @if ($hasActiveFilters)
                    <a href="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" class="catalog-reset-button whitespace-nowrap">
                        <x-fa-icon name="rotate-left" class="h-3.5 w-3.5" />
                        <span>{{ __('ui.shop.filters.reset') }}</span>
                    </a>
                @endif
            </div>
        </div>
            </div>
            </div>
        </section>
    @endif

    @if ($showCategoryProducts)
        <section class="storefront-container px-3 pt-3 pb-6 sm:px-4 lg:px-6">
            <div class="catalog-products-layout">
                @if ($showCategoryFilters)
                    <aside class="catalog-desktop-sidebar hidden min-[1025px]:block">
                        <div class="catalog-desktop-sidebar-inner">
                            @if ($hasActiveFilters)
                                <div class="catalog-sidebar-active">
                                    <span>{{ __('ui.shop.filters.open') }}</span>
                                    <a href="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}">
                                        {{ __('ui.shop.filters.reset') }}
                                    </a>
                                </div>
                            @endif

                            @if ($categoryFilterPanel['visible'])
                                <details class="catalog-sidebar-section" @if ($categoryFilterPanel['default_open']) open @endif>
                                    <summary class="catalog-sidebar-heading">
                                        <span>{{ __('ui.shop.filters.category') }}</span>
                                        <x-fa-icon name="chevron-down" class="catalog-sidebar-chevron" />
                                    </summary>
                                    <nav class="catalog-sidebar-options catalog-sidebar-category-options catalog-sidebar-max-height-{{ $categoryFilterPanel['max_height'] }}" aria-label="{{ __('ui.shop.filters.category') }}">
                                        <a
                                            href="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}"
                                            class="catalog-sidebar-category is-current"
                                            aria-current="page"
                                        >
                                            <span>{{ $translation?->name ?? $category->code }}</span>
                                            <span>{{ (int) $products->total() }}</span>
                                        </a>
                                        @foreach (($subcategories ?? collect()) as $subCategory)
                                            @php
                                                $subCategoryTranslation = $subCategory->translations->firstWhere('locale', $locale)
                                                    ?? $subCategory->translations->firstWhere('locale', $fallbackLocale);
                                            @endphp
                                            <a
                                                href="{{ route('categories.show', ['slug' => $subCategoryTranslation?->slug ?? $subCategory->id]) }}"
                                                class="catalog-sidebar-category"
                                            >
                                                <span>{{ $subCategoryTranslation?->name ?? $subCategory->code }}</span>
                                                <span>{{ (int) $subCategory->products_count }}</span>
                                            </a>
                                        @endforeach
                                    </nav>
                                </details>
                            @endif

                            <form
                                id="category-desktop-filter-form"
                                method="GET"
                                action="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}"
                                data-desktop-filter-form
                            >
                                <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                                <input type="hidden" name="cols" value="{{ (int) ($filters['cols'] ?? 4) }}">

                                @if ($showManufacturers && $manufacturerFilterPanel['visible'])
                                    <details class="catalog-sidebar-section" @if ($manufacturerFilterPanel['default_open'] || trim((string) ($filters['manufacturer'] ?? '')) !== '') open @endif>
                                        <summary class="catalog-sidebar-heading">
                                            <span>{{ __('ui.shop.filters.manufacturer') }}</span>
                                            <x-fa-icon name="chevron-down" class="catalog-sidebar-chevron" />
                                        </summary>
                                        <div class="catalog-sidebar-options catalog-sidebar-max-height-{{ $manufacturerFilterPanel['max_height'] }}">
                                            @foreach ($manufacturers as $manufacturer)
                                                @php
                                                    $manufacturerTranslation = $manufacturer->translations->firstWhere('locale', $locale)
                                                        ?? $manufacturer->translations->firstWhere('locale', $fallbackLocale);
                                                    $manufacturerSlugValue = (string) ($manufacturerTranslation?->slug ?? '');
                                                @endphp
                                                <label class="catalog-sidebar-option">
                                                    <input
                                                        type="radio"
                                                        name="manufacturer"
                                                        value="{{ $manufacturerSlugValue }}"
                                                        @checked(($filters['manufacturer'] ?? '') === $manufacturerSlugValue)
                                                        data-auto-submit-filter
                                                    >
                                                    <span class="catalog-sidebar-check" aria-hidden="true">
                                                        <x-fa-icon name="check" />
                                                    </span>
                                                    <span class="catalog-sidebar-option-label">{{ $manufacturerTranslation?->name ?? $manufacturer->code }}</span>
                                                    <span class="catalog-sidebar-option-count">{{ (int) $manufacturer->products_count }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif

                                @if ($priceFilterPanel['visible'])
                                <details class="catalog-sidebar-section" data-price-filter-root @if ($priceFilterPanel['default_open'] || $hasPricePanelFilter) open @endif>
                                    <summary class="catalog-sidebar-heading">
                                        <span>{{ __('ui.shop.filters.price') }}</span>
                                        <x-fa-icon name="chevron-down" class="catalog-sidebar-chevron" />
                                    </summary>
                                    <div class="catalog-sidebar-price catalog-sidebar-scroll catalog-sidebar-max-height-{{ $priceFilterPanel['max_height'] }}" data-price-range-root data-price-min-bound="{{ $priceSliderMin }}" data-price-max-bound="{{ $priceSliderMax }}">
                                        <input type="hidden" name="price_min" value="{{ $priceMinValue }}" data-price-range-hidden-min>
                                        <input type="hidden" name="price_max" value="{{ $priceMaxValue }}" data-price-range-hidden-max>
                                        <div class="catalog-price-range-values">
                                            <div class="catalog-price-range-value">
                                                <span class="catalog-price-range-value-label">{{ __('ui.shop.filters.price_from') }}</span>
                                                <span class="catalog-price-range-value-amount" data-price-range-current-min>{{ $priceSliderSelectedMin }} €</span>
                                            </div>
                                            <div class="catalog-price-range-value text-right">
                                                <span class="catalog-price-range-value-label">{{ __('ui.shop.filters.price_to') }}</span>
                                                <span class="catalog-price-range-value-amount" data-price-range-current-max>{{ $priceSliderSelectedMax }} €</span>
                                            </div>
                                        </div>
                                        <div class="catalog-price-range-slider">
                                            <div class="catalog-price-range-track"></div>
                                            <div class="catalog-price-range-progress" data-price-range-progress></div>
                                            <input type="range" min="{{ $priceSliderMin }}" max="{{ $priceSliderMax }}" step="1" value="{{ $priceSliderSelectedMin }}" data-price-range-min>
                                            <input type="range" min="{{ $priceSliderMin }}" max="{{ $priceSliderMax }}" step="1" value="{{ $priceSliderSelectedMax }}" data-price-range-max>
                                        </div>
                                        <div class="catalog-price-range-scale">
                                            <span>{{ $priceSliderMin }} €</span>
                                            <span>{{ $priceSliderMax }} €</span>
                                        </div>
                                        <button type="button" class="catalog-price-reset" data-price-filter-reset @disabled(! $hasPriceFilter)>
                                            <x-fa-icon name="rotate-left" class="h-3.5 w-3.5" />
                                            <span>{{ __('ui.shop.filters.reset') }}</span>
                                        </button>
                                    </div>
                                </details>
                                @endif

                                @foreach ($orderedCategoryFilters as $filterOption)
                                    @php
                                        $isColorFilter = (string) ($filterOption['kind'] ?? 'default') === 'color';
                                        $filterPanel = $resolveFilterPanel((string) ($filterOption['query_key'] ?? ''));
                                        $filterHasSelection = trim((string) ($filterOption['selected'] ?? '')) !== '';
                                    @endphp
                                    <details class="catalog-sidebar-section" @if ($filterPanel['default_open'] || $filterHasSelection) open @endif>
                                        <summary class="catalog-sidebar-heading">
                                            <span>{{ $filterOption['label'] }}</span>
                                            <x-fa-icon name="chevron-down" class="catalog-sidebar-chevron" />
                                        </summary>
                                        <div class="catalog-sidebar-options catalog-sidebar-max-height-{{ $filterPanel['max_height'] }}">
                                            @foreach (($filterOption['values'] ?? []) as $value)
                                                <label class="catalog-sidebar-option">
                                                    <input
                                                        type="radio"
                                                        name="{{ $filterOption['query_key'] }}"
                                                        value="{{ $value['id'] }}"
                                                        @checked((string) ($filterOption['selected'] ?? '') === (string) $value['id'])
                                                        data-auto-submit-filter
                                                        @if ($isColorFilter)
                                                            data-filter-kind="color"
                                                            data-filter-count="{{ (int) ($value['count'] ?? 0) }}"
                                                            @if (! empty($value['swatch_image_url']))
                                                                data-filter-swatch="{{ $value['swatch_image_url'] }}"
                                                            @endif
                                                        @endif
                                                    >
                                                    <span class="catalog-sidebar-check" aria-hidden="true">
                                                        <x-fa-icon name="check" />
                                                    </span>
                                                    <span class="catalog-sidebar-option-label">{{ $value['label'] }}</span>
                                                    @if (isset($value['count']))
                                                        <span class="catalog-sidebar-option-count">{{ (int) $value['count'] }}</span>
                                                    @endif
                                                </label>
                                            @endforeach
                                        </div>
                                    </details>
                                @endforeach
                            </form>
                        </div>
                    </aside>
                @endif

                <div class="catalog-products-main">
                    @if ($products->isEmpty())
                        <div class="border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">{{ __('ui.category.empty') }}</div>
                    @else
                        <div class="catalog-lined-grid {{ $gridClass }}" data-catalog-grid>
                            @foreach ($products as $product)
                                @include('front.desktop.partials.product-card', ['product' => $product, 'locale' => $locale, 'fallbackLocale' => $fallbackLocale, 'flat' => true, 'lined' => true])
                            @endforeach
                        </div>

                        @if ($useAsyncPagination)
                            <div class="mt-8 flex items-center justify-center" data-catalog-load-more-root data-catalog-load-mode="{{ $isInfinitePagination ? 'infinite' : 'load_more' }}">
                                @if ($products->hasMorePages())
                                    <a href="{{ $products->nextPageUrl() }}" class="hidden" data-catalog-next-url>{{ __('ui.shop.load_more') }}</a>
                                    <button type="button" class="{{ $isInfinitePagination ? 'hidden' : 'inline-flex' }} h-10 items-center justify-center border border-slate-900 bg-slate-900 px-5 text-sm font-semibold text-white hover:bg-slate-700" data-catalog-load-more-button>
                                        {{ __('ui.shop.load_more') }}
                                    </button>
                                @endif
                                <div class="h-8 flex items-center justify-center">
                                    <div class="hidden inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500" data-catalog-load-more-loader data-loading-label="{{ __('ui.shop.loading') }}" data-end-label="{{ __('ui.shop.end_of_list') }}">
                                        <span class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-slate-400 border-t-transparent" data-catalog-load-more-spinner aria-hidden="true"></span>
                                        <span data-catalog-load-more-loader-text>{{ __('ui.shop.loading') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="sr-only" data-catalog-pagination-seo aria-hidden="true">
                                {{ $products->onEachSide(0)->links() }}
                            </div>
                            <noscript>
                                <div class="mt-14">
                                    {{ $products->onEachSide(0)->links() }}
                                </div>
                            </noscript>
                        @else
                            <div class="mt-14" data-catalog-pagination>
                                {{ $products->onEachSide(0)->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if ($bottomBlocks->isNotEmpty())
        <section class="storefront-container mt-10 px-3 sm:px-4 lg:px-6">
            @include('components.content-placement', ['items' => $bottomBlocks])
        </section>
    @endif
@endsection

@push('scripts')
    @if ($showCategoryFilters)
        <script defer src="{{ asset('front-theme/scripts/category-select-redirect.js') }}?v={{ filemtime(public_path('front-theme/scripts/category-select-redirect.js')) }}"></script>
        <script defer src="{{ asset('front-theme/scripts/catalog-custom-select.js') }}?v={{ filemtime(public_path('front-theme/scripts/catalog-custom-select.js')) }}"></script>
    @endif
    @if ($showCategoryProducts && $useAsyncPagination)
        <script defer src="{{ asset('front-theme/scripts/catalog-load-more.js') }}?v={{ filemtime(public_path('front-theme/scripts/catalog-load-more.js')) }}"></script>
    @endif
    @if ($showCategoryFilters)
        <script defer src="{{ asset('front-theme/scripts/category-catalog.js') }}?v={{ filemtime(public_path('front-theme/scripts/category-catalog.js')) }}"></script>
    @endif
@endpush

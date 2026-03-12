@extends('front.desktop.layouts.store')

@php
    $showManufacturers = app(\App\Services\Catalog\CatalogFeatureService::class)->useManufacturers();
    $translation = $category->translations->firstWhere('locale', $locale)
        ?? $category->translations->firstWhere('locale', $fallbackLocale);
    $hasSubcategories = ($subcategories ?? collect())->isNotEmpty();
    $mobileDefaultCols = in_array((int) ($storeSettings['product']['mobile_default_cols'] ?? 2), [1, 2], true)
        ? (int) ($storeSettings['product']['mobile_default_cols'] ?? 2)
        : 2;
    $showCategoryFilters = (bool) ($showCategoryFilters ?? true);
    $showCategoryProducts = (bool) ($showCategoryProducts ?? true);
    $currentCols = (int) ($filters['cols'] ?? 4);
    $mobileCols = in_array($currentCols, [1, 2], true) ? $currentCols : $mobileDefaultCols;
    $paginationMode = (string) ($storeSettings['product']['catalog_pagination_mode'] ?? 'pagination');
    $useAsyncPagination = in_array($paginationMode, ['load_more', 'infinite'], true);
    $isInfinitePagination = $paginationMode === 'infinite';
    $desktopFilterSelectCount = ($hasSubcategories ? 1 : 0) + ($showManufacturers ? 1 : 0) + count($optionFilters ?? []) + count($attributeFilters ?? []) + 1;
    $hasActiveFilters = trim((string) ($filters['q'] ?? '')) !== ''
        || trim((string) ($filters['manufacturer'] ?? '')) !== ''
        || collect(array_keys(request()->query()))
            ->contains(fn ($key): bool => str_starts_with((string) $key, 'opt_') || str_starts_with((string) $key, 'attr_'))
        || (string) ($filters['sort'] ?? 'newest') !== 'newest';
    $gridClass = match ($currentCols) {
        1 => 'grid grid-cols-1 gap-y-5',
        2 => 'grid grid-cols-2 gap-x-4 gap-y-5',
        3 => 'grid gap-x-4 gap-y-5 '.($mobileDefaultCols === 2 ? 'grid-cols-2 ' : '').'sm:grid-cols-2 xl:grid-cols-3',
        5 => 'grid gap-x-4 gap-y-5 '.($mobileDefaultCols === 2 ? 'grid-cols-2 ' : '').'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5',
        default => 'grid gap-x-4 gap-y-5 '.($mobileDefaultCols === 2 ? 'grid-cols-2 ' : '').'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
    };
@endphp

@section('title', ($translation?->name ?? __('ui.category.fallback_name')).' '.__('ui.category.products_suffix'))
@section('main_class', 'w-full px-0 pt-3 pb-4 sm:pt-3 sm:pb-6')

@section('content')
    <style>
        @media (min-width: 768px) {
            .catalog-filter-select {
                appearance: none;
                -webkit-appearance: none;
                -moz-appearance: none;
                height: 36px;
                width: 100%;
                border: 1px solid #b8c7da;
                border-radius: 2px;
                background-color: #fff;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M5 7.5L10 12.5L15 7.5' stroke='%23475569' stroke-width='1.7' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right .65rem center;
                background-size: 12px 12px;
                padding: 0 .7rem;
                padding-right: 1.95rem;
                font-size: .78rem;
                color: #0f172a;
                text-transform: uppercase;
                letter-spacing: .02em;
                transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
            }

            .catalog-filter-select:hover {
                border-color: #94a3b8;
                background-color: #fcfdff;
            }

            .catalog-filter-select:focus {
                outline: none;
                border-color: #0f172a;
                box-shadow: 0 0 0 2px rgba(15, 23, 42, .12);
            }

            .catalog-filter-select.catalog-filter-native-hidden {
                display: none;
            }

            .catalog-filter-custom {
                position: relative;
            }

            .catalog-filter-custom-button {
                display: block;
                height: 36px;
                width: 100%;
                border: 1px solid #b8c7da;
                background: #fff;
                padding: 0 .7rem;
                padding-right: 1.95rem;
                text-align: left;
                font-size: .78rem;
                color: #0f172a;
                text-transform: uppercase;
                letter-spacing: .02em;
                transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M5 7.5L10 12.5L15 7.5' stroke='%23475569' stroke-width='1.7' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right .65rem center;
                background-size: 12px 12px;
            }

            .catalog-filter-custom-button.is-placeholder {
                font-size: .74rem;
                letter-spacing: .03em;
                color: #475569;
            }

            .catalog-filter-custom-button:hover {
                border-color: #94a3b8;
                background-color: #fcfdff;
            }

            .catalog-filter-custom-button:focus {
                outline: none;
                border-color: #0f172a;
                box-shadow: 0 0 0 2px rgba(15, 23, 42, .12);
            }

            .catalog-filter-custom-list {
                position: absolute;
                z-index: 120;
                top: calc(100% + 4px);
                left: 0;
                right: 0;
                max-height: 240px;
                overflow: auto;
                border: 1px solid #b8c7da;
                background: #fff;
                box-shadow: 0 10px 20px -18px rgba(15, 23, 42, 0.5);
                display: none;
            }

            .catalog-filter-custom.is-open .catalog-filter-custom-list {
                display: block;
            }

            .catalog-filter-custom-item {
                display: block;
                width: 100%;
                border: 0;
                background: #fff;
                padding: .45rem .7rem;
                text-align: left;
                font-size: .78rem;
                color: #1e293b;
                text-transform: uppercase;
                letter-spacing: .02em;
            }

            .catalog-filter-custom-item.is-placeholder {
                font-size: .74rem;
                letter-spacing: .03em;
                color: #475569;
            }

            .catalog-filter-select.catalog-filter-sort {
                font-size: .76rem;
                letter-spacing: .03em;
                color: #475569;
            }

            .catalog-filter-custom.is-sort .catalog-filter-custom-button,
            .catalog-filter-custom.is-sort .catalog-filter-custom-item {
                font-size: .76rem;
                letter-spacing: .03em;
                color: #475569;
            }

            .catalog-filter-custom-item:hover {
                background: #f8fafc;
            }

            .catalog-filter-custom-item.is-selected {
                background: #edf2f8;
                color: #0f172a;
                font-weight: 600;
                box-shadow: inset 3px 0 0 #0f172a;
            }
        }
    </style>

    <section class="px-3 sm:px-4 lg:px-6">
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
        <section class="mb-8 px-3 sm:px-4 lg:px-6">
            @include('components.content-placement', ['items' => $topBlocks])
        </section>
    @endif

    @if ($showCategoryFilters)
        <section class="relative z-20 px-3 pt-3 pb-4 sm:px-4 lg:px-6">
            <div class="border-b border-slate-200/90 pb-4">
        <div class="max-[1024px]:block min-[1025px]:hidden" data-mobile-filter-root>
            <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-2">
                <button
                    type="button"
                    class="flex h-[42px] w-full min-w-0 items-center justify-start gap-2 border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    data-mobile-filter-toggle
                    aria-expanded="false"
                    aria-controls="category-mobile-filter-panel"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M4 7h16M7 12h10M10 17h4"></path>
                    </svg>
                    {{ __('ui.shop.filters.open') }}
                </button>
                <div class="flex h-[42px] items-center gap-2">
                @foreach ([1, 2] as $cols)
                    <a
                        href="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id] + array_merge(request()->query(), ['cols' => $cols])) }}"
                        class="inline-flex h-[42px] w-[42px] items-center justify-center border border-slate-300 {{ $mobileCols === $cols ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-100' }}"
                        aria-label="{{ __('ui.shop.filters.grid') }} {{ $cols }}"
                    >
                        <span class="flex h-4 items-stretch gap-[2px]">
                            @for ($i = 0; $i < $cols; $i++)
                                <span class="h-4 w-[3px] border border-current/80"></span>
                            @endfor
                        </span>
                    </a>
                @endforeach
                </div>
            </div>
            <form method="GET" action="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" class="mt-3 hidden gap-3" data-mobile-filter-panel id="category-mobile-filter-panel">
                <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                @if ($hasSubcategories)
                    <div>
                        <label for="shop-category-mobile" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.shop.filters.category') }}</label>
                        <select
                            id="shop-category-mobile"
                            class="h-[42px] w-full rounded-none border-slate-300 text-sm"
                            data-category-redirect
                            data-default-url="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}"
                        >
                            <option value="" data-url="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" @selected(true)>{{ __('ui.shop.filters.all_categories') }}</option>
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
                @if ($showManufacturers)
                    <div>
                        <label for="shop-manufacturer-mobile" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.shop.filters.manufacturer') }}</label>
                        <select id="shop-manufacturer-mobile" name="manufacturer" class="h-[42px] w-full rounded-none border-slate-300 text-sm" data-auto-submit-filter>
                            <option value="">{{ __('ui.shop.filters.all_manufacturers') }}</option>
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
                @foreach (($optionFilters ?? []) as $filterOption)
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $filterOption['label'] }}</label>
                        <select name="{{ $filterOption['query_key'] }}" class="h-[42px] w-full rounded-none border-slate-300 text-sm" data-auto-submit-filter>
                            <option value="">{{ __('ui.shop.filters.select_option') }}</option>
                            @foreach (($filterOption['values'] ?? []) as $value)
                                <option value="{{ $value['id'] }}" @selected((string) ($filterOption['selected'] ?? '') === (string) $value['id'])>
                                    {{ $value['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                @foreach (($attributeFilters ?? []) as $attributeFilter)
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $attributeFilter['label'] }}</label>
                        <select name="{{ $attributeFilter['query_key'] }}" class="h-[42px] w-full rounded-none border-slate-300 text-sm" data-auto-submit-filter>
                            <option value="">{{ __('ui.shop.filters.select_option') }}</option>
                            @foreach (($attributeFilter['values'] ?? []) as $value)
                                <option value="{{ $value['id'] }}" @selected((string) ($attributeFilter['selected'] ?? '') === (string) $value['id'])>
                                    {{ $value['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div>
                    <label for="shop-sort-mobile" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.shop.filters.sort') }}</label>
                    <select id="shop-sort-mobile" name="sort" class="catalog-filter-sort h-[42px] w-full rounded-none border-slate-300 text-sm" data-auto-submit-filter>
                        <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>{{ __('ui.shop.filters.newest') }}</option>
                        <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('ui.shop.filters.oldest') }}</option>
                        <option value="price_low" @selected(($filters['sort'] ?? '') === 'price_low')>{{ __('ui.shop.filters.price_low') }}</option>
                        <option value="price_high" @selected(($filters['sort'] ?? '') === 'price_high')>{{ __('ui.shop.filters.price_high') }}</option>
                        <option value="stock_high" @selected(($filters['sort'] ?? '') === 'stock_high')>{{ __('ui.shop.filters.stock_high') }}</option>
                    </select>
                </div>
                <input type="hidden" name="cols" value="{{ $mobileCols }}">
                @if ($hasActiveFilters)
                    <div class="flex items-end justify-end">
                        <a href="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" class="inline-flex h-[42px] items-center justify-center gap-2 whitespace-nowrap border border-rose-600 px-4 text-sm font-semibold text-rose-600 hover:bg-rose-50">
                            <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                <path d="M3.5 3.5L12.5 12.5M12.5 3.5L3.5 12.5"></path>
                            </svg>
                            <span>{{ __('ui.shop.filters.reset') }}</span>
                        </a>
                    </div>
                @endif
            </form>
        </div>

        <form method="GET" action="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" class="hidden gap-x-3 gap-y-2.5 min-[1025px]:flex min-[1025px]:flex-wrap min-[1025px]:items-end min-[1025px]:justify-start" data-desktop-filter-form>
            <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
            @if ($hasSubcategories)
                <div class="w-[190px] xl:w-[210px]">
                    <label for="shop-category" class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('ui.shop.filters.category') }}</label>
                    <select
                        id="shop-category"
                        class="catalog-filter-select h-9 w-full rounded-none border-slate-300 text-sm"
                        data-category-redirect
                        data-default-url="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}"
                    >
                        <option value="" data-url="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" @selected(true)>{{ __('ui.shop.filters.all_categories') }}</option>
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
            @if ($showManufacturers)
                <div class="w-[190px] xl:w-[210px]">
                    <label for="shop-manufacturer" class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('ui.shop.filters.manufacturer') }}</label>
                    <select id="shop-manufacturer" name="manufacturer" class="catalog-filter-select h-9 w-full rounded-none border-slate-300 text-sm" data-auto-submit-filter>
                        <option value="">{{ __('ui.shop.filters.all_manufacturers') }}</option>
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
            @foreach (($optionFilters ?? []) as $filterOption)
                <div class="w-[190px] xl:w-[210px]">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $filterOption['label'] }}</label>
                    <select name="{{ $filterOption['query_key'] }}" class="catalog-filter-select h-9 w-full rounded-none border-slate-300 text-sm" data-auto-submit-filter>
                        <option value="">{{ __('ui.shop.filters.select_option') }}</option>
                        @foreach (($filterOption['values'] ?? []) as $value)
                            <option value="{{ $value['id'] }}" @selected((string) ($filterOption['selected'] ?? '') === (string) $value['id'])>
                                {{ $value['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            @foreach (($attributeFilters ?? []) as $attributeFilter)
                <div class="w-[190px] xl:w-[210px]">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $attributeFilter['label'] }}</label>
                    <select name="{{ $attributeFilter['query_key'] }}" class="catalog-filter-select h-9 w-full rounded-none border-slate-300 text-sm" data-auto-submit-filter>
                        <option value="">{{ __('ui.shop.filters.select_option') }}</option>
                        @foreach (($attributeFilter['values'] ?? []) as $value)
                            <option value="{{ $value['id'] }}" @selected((string) ($attributeFilter['selected'] ?? '') === (string) $value['id'])>
                                {{ $value['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <input type="hidden" name="cols" value="{{ (int) ($filters['cols'] ?? 4) }}">
            <div class="min-[1025px]:ml-auto flex items-end gap-3">
                <div class="w-[190px] xl:w-[210px]">
                    <label for="shop-sort" class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('ui.shop.filters.sort') }}</label>
                    <select id="shop-sort" name="sort" class="catalog-filter-select catalog-filter-sort h-9 w-full rounded-none border-slate-300 text-sm" data-auto-submit-filter>
                        <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>{{ __('ui.shop.filters.newest') }}</option>
                        <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('ui.shop.filters.oldest') }}</option>
                        <option value="price_low" @selected(($filters['sort'] ?? '') === 'price_low')>{{ __('ui.shop.filters.price_low') }}</option>
                        <option value="price_high" @selected(($filters['sort'] ?? '') === 'price_high')>{{ __('ui.shop.filters.price_high') }}</option>
                        <option value="stock_high" @selected(($filters['sort'] ?? '') === 'stock_high')>{{ __('ui.shop.filters.stock_high') }}</option>
                    </select>
                </div>
                <div class="w-auto shrink-0">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('ui.shop.filters.grid') }}</label>
                    <div class="flex h-9">
                        @foreach ([3, 4, 5] as $cols)
                            <a
                                href="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id] + array_merge(request()->query(), ['cols' => $cols])) }}"
                                class="{{ $cols === 5 ? 'hidden 2xl:inline-flex' : 'inline-flex' }} h-full w-9 items-center justify-center border border-slate-300 {{ (int) ($filters['cols'] ?? 4) === $cols ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-100' }}"
                                aria-label="{{ __('ui.shop.filters.grid') }} {{ $cols }}"
                            >
                                <span class="flex h-4 items-stretch gap-[1px]">
                                    @for ($i = 0; $i < $cols; $i++)
                                        <span class="h-4 w-[2px] border border-current/80"></span>
                                    @endfor
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
                @if ($hasActiveFilters)
                    <div class="flex items-end">
                        <a href="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" class="inline-flex h-9 items-center justify-center gap-1.5 whitespace-nowrap border border-rose-600 px-3.5 text-xs font-semibold uppercase tracking-[0.12em] text-rose-600 hover:bg-rose-50">
                            <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                <path d="M3.5 3.5L12.5 12.5M12.5 3.5L3.5 12.5"></path>
                            </svg>
                            <span>{{ __('ui.shop.filters.reset') }}</span>
                        </a>
                    </div>
                @endif
            </div>
        </form>
            </div>
        </section>
    @endif

    @if ($showCategoryProducts)
        <section class="px-3 pt-3 pb-6 sm:px-4 lg:px-6">
            @if ($products->isEmpty())
                <div class="border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">{{ __('ui.category.empty') }}</div>
            @else
                <div class="{{ $gridClass }}" data-catalog-grid>
                    @foreach ($products as $product)
                        @include('front.desktop.partials.product-card', ['product' => $product, 'locale' => $locale, 'fallbackLocale' => $fallbackLocale, 'flat' => true])
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
        </section>
    @endif

    @if ($bottomBlocks->isNotEmpty())
        <section class="mt-10 px-3 sm:px-4 lg:px-6">
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
    <script>
        (() => {
            const init = () => {
                document.querySelectorAll('[data-mobile-filter-root]').forEach((root) => {
                    if (root.dataset.mobileFilterInit === '1') {
                        return;
                    }
                    root.dataset.mobileFilterInit = '1';
                    const toggle = root.querySelector('[data-mobile-filter-toggle]');
                    const panel = root.querySelector('[data-mobile-filter-panel]');
                    if (!toggle || !panel) {
                        return;
                    }

                    toggle.addEventListener('click', () => {
                        const isHidden = panel.classList.contains('hidden');
                        panel.classList.toggle('hidden', !isHidden);
                        panel.classList.toggle('grid', isHidden);
                        toggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
                    });
                });

                document.querySelectorAll('[data-auto-submit-filter]').forEach((select) => {
                    if (select.dataset.autoSortInit === '1') {
                        return;
                    }
                    select.dataset.autoSortInit = '1';
                    select.addEventListener('change', () => {
                        const form = select.closest('form');
                        if (!form) {
                            return;
                        }
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                            return;
                        }
                        form.submit();
                    });
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init, { once: true });
                return;
            }
            init();
        })();
    </script>
@endpush

@extends('front.desktop.layouts.store')

@php
    $translation = $category->translations->firstWhere('locale', $locale)
        ?? $category->translations->firstWhere('locale', $fallbackLocale);
    $hasSubcategories = ($subcategories ?? collect())->isNotEmpty();
    $mobileDefaultCols = in_array((int) ($storeSettings['product']['mobile_default_cols'] ?? 2), [1, 2], true)
        ? (int) ($storeSettings['product']['mobile_default_cols'] ?? 2)
        : 2;
    $currentCols = (int) ($filters['cols'] ?? 4);
    $mobileCols = in_array($currentCols, [1, 2], true) ? $currentCols : $mobileDefaultCols;
    $paginationMode = (string) ($storeSettings['product']['catalog_pagination_mode'] ?? 'pagination');
    $useAsyncPagination = in_array($paginationMode, ['load_more', 'infinite'], true);
    $isInfinitePagination = $paginationMode === 'infinite';
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
    <section class="px-3 sm:px-4 lg:px-6">
        <div class="bg-slate-100 px-4 py-4 text-center sm:px-6 sm:py-5">
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
        <p class="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500">{{ (int) $products->total() }} {{ __('ui.category.products_suffix') }}</p>
        </div>
    </section>

    @if ($topBlocks->isNotEmpty())
        <section class="mb-8 px-3 sm:px-4 lg:px-6">
            @include('components.content-placement', ['items' => $topBlocks])
        </section>
    @endif

    <section class="mt-3 overflow-x-hidden border-y border-slate-200 bg-slate-50 px-3 py-4 sm:px-4 lg:px-6">
        <div class="md:hidden" data-mobile-filter-root>
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
                <div>
                    <label for="shop-manufacturer-mobile" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.shop.filters.manufacturer') }}</label>
                    <select id="shop-manufacturer-mobile" name="manufacturer" class="h-[42px] w-full rounded-none border-slate-300 text-sm">
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
                <div>
                    <label for="shop-size-mobile" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.shop.filters.size') }}</label>
                    <select id="shop-size-mobile" name="size" class="h-[42px] w-full rounded-none border-slate-300 text-sm">
                        <option value="">{{ __('ui.shop.filters.all_sizes') }}</option>
                        @foreach ($sizes as $size)
                            @php
                                $sizeTranslation = $size->translations->firstWhere('locale', $locale)
                                    ?? $size->translations->firstWhere('locale', $fallbackLocale);
                            @endphp
                            <option value="{{ $size->id }}" @selected((string) ($filters['size'] ?? '') === (string) $size->id)>
                                {{ $sizeTranslation?->name ?? $size->code }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="shop-sort-mobile" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.shop.filters.sort') }}</label>
                    <select id="shop-sort-mobile" name="sort" class="h-[42px] w-full rounded-none border-slate-300 text-sm">
                        <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>{{ __('ui.shop.filters.newest') }}</option>
                        <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('ui.shop.filters.oldest') }}</option>
                        <option value="price_low" @selected(($filters['sort'] ?? '') === 'price_low')>{{ __('ui.shop.filters.price_low') }}</option>
                        <option value="price_high" @selected(($filters['sort'] ?? '') === 'price_high')>{{ __('ui.shop.filters.price_high') }}</option>
                        <option value="stock_high" @selected(($filters['sort'] ?? '') === 'stock_high')>{{ __('ui.shop.filters.stock_high') }}</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <input type="hidden" name="cols" value="{{ $mobileCols }}">
                    <button type="submit" class="h-[42px] flex-1 border border-slate-900 bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-700">{{ __('ui.shop.filters.apply') }}</button>
                    <a href="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" class="inline-flex h-[42px] items-center justify-center border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('ui.shop.filters.reset') }}</a>
                </div>
            </form>
        </div>

        <form method="GET" action="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" class="hidden gap-3 md:grid {{ $hasSubcategories ? 'xl:grid-cols-[1fr_1fr_1fr_1fr_auto_auto]' : 'xl:grid-cols-[1fr_1fr_1fr_auto_auto]' }}">
            <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
            @if ($hasSubcategories)
                <div>
                    <label for="shop-category" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.shop.filters.category') }}</label>
                    <select
                        id="shop-category"
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
            <div>
                <label for="shop-manufacturer" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.shop.filters.manufacturer') }}</label>
                <select id="shop-manufacturer" name="manufacturer" class="h-[42px] w-full rounded-none border-slate-300 text-sm">
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
            <div>
                <label for="shop-size" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.shop.filters.size') }}</label>
                <select id="shop-size" name="size" class="h-[42px] w-full rounded-none border-slate-300 text-sm">
                    <option value="">{{ __('ui.shop.filters.all_sizes') }}</option>
                    @foreach ($sizes as $size)
                        @php
                            $sizeTranslation = $size->translations->firstWhere('locale', $locale)
                                ?? $size->translations->firstWhere('locale', $fallbackLocale);
                        @endphp
                        <option value="{{ $size->id }}" @selected((string) ($filters['size'] ?? '') === (string) $size->id)>
                            {{ $sizeTranslation?->name ?? $size->code }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="shop-sort" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.shop.filters.sort') }}</label>
                <select id="shop-sort" name="sort" class="h-[42px] w-full rounded-none border-slate-300 text-sm">
                    <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>{{ __('ui.shop.filters.newest') }}</option>
                    <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('ui.shop.filters.oldest') }}</option>
                    <option value="price_low" @selected(($filters['sort'] ?? '') === 'price_low')>{{ __('ui.shop.filters.price_low') }}</option>
                    <option value="price_high" @selected(($filters['sort'] ?? '') === 'price_high')>{{ __('ui.shop.filters.price_high') }}</option>
                    <option value="stock_high" @selected(($filters['sort'] ?? '') === 'stock_high')>{{ __('ui.shop.filters.stock_high') }}</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.shop.filters.grid') }}</label>
                <div class="flex h-[42px]">
                    @foreach ([3, 4, 5] as $cols)
                        <a
                            href="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id] + array_merge(request()->query(), ['cols' => $cols])) }}"
                            class="{{ $cols === 5 ? 'hidden 2xl:inline-flex' : 'inline-flex' }} h-full w-11 items-center justify-center border border-slate-300 {{ (int) ($filters['cols'] ?? 4) === $cols ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-100' }}"
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
            <div class="flex items-end gap-2">
                <input type="hidden" name="cols" value="{{ (int) ($filters['cols'] ?? 4) }}">
                <button type="submit" class="h-[42px] flex-1 border border-slate-900 bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-700">{{ __('ui.shop.filters.apply') }}</button>
                <a href="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" class="inline-flex h-[42px] items-center justify-center border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('ui.shop.filters.reset') }}</a>
            </div>
        </form>
    </section>

    <section class="px-3 py-6 sm:px-4 lg:px-6">
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

    @if ($bottomBlocks->isNotEmpty())
        <section class="mt-10 px-3 sm:px-4 lg:px-6">
            @include('components.content-placement', ['items' => $bottomBlocks])
        </section>
    @endif
@endsection

@push('scripts')
    <script defer src="{{ asset('front-theme/scripts/category-select-redirect.js') }}?v={{ filemtime(public_path('front-theme/scripts/category-select-redirect.js')) }}"></script>
    @if ($useAsyncPagination)
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
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init, { once: true });
                return;
            }
            init();
        })();
    </script>
@endpush

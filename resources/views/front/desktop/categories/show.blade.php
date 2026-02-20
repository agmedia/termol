@extends('front.desktop.layouts.store')

@php
    $translation = $category->translations->firstWhere('locale', $locale)
        ?? $category->translations->firstWhere('locale', $fallbackLocale);
    $gridClass = match ((int) ($filters['cols'] ?? 4)) {
        1 => 'grid grid-cols-1 gap-4',
        2 => 'grid grid-cols-2 gap-4',
        3 => 'grid gap-4 sm:grid-cols-2 xl:grid-cols-3',
        5 => 'grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5',
        default => 'grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4',
    };
@endphp

@section('title', ($translation?->name ?? __('ui.category.fallback_name')).' '.__('ui.category.products_suffix'))
@section('main_class', 'w-full px-0 py-8')

@section('content')
    <section class="px-4 sm:px-6 lg:px-8">
        <a href="{{ route('categories.index') }}" class="text-sm font-semibold text-slate-700 hover:text-slate-900">← {{ __('ui.category.back_to_categories') }}</a>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ $translation?->name ?? $category->code }}</h1>
        <p class="mt-2 max-w-3xl text-slate-600">{{ $translation?->description ?: __('ui.category.default_description') }}</p>
    </section>

    @if ($topBlocks->isNotEmpty())
        <section class="mb-8 px-4 sm:px-6 lg:px-8">
            @include('components.content-placement', ['items' => $topBlocks])
        </section>
    @endif

    <section class="mt-6 border-y border-slate-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
        <details class="group md:hidden">
            <summary class="flex h-[42px] w-full list-none cursor-pointer items-center justify-center gap-2 border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M4 7h16M7 12h10M10 17h4"></path>
                </svg>
                {{ __('ui.shop.filters.open') }}
            </summary>
            <form method="GET" action="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" class="mt-3 grid gap-3">
                <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
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
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.shop.filters.grid') }}</label>
                    <div class="flex h-[42px]">
                        @foreach ([1, 2] as $cols)
                            <a
                                href="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id] + array_merge(request()->query(), ['cols' => $cols])) }}"
                                class="inline-flex h-full w-11 items-center justify-center border border-slate-300 {{ (int) ($filters['cols'] ?? 4) === $cols ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-100' }}"
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
                <div class="flex items-end gap-2">
                    <input type="hidden" name="cols" value="{{ (int) ($filters['cols'] ?? 4) }}">
                    <button type="submit" class="h-[42px] flex-1 border border-slate-900 bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-700">{{ __('ui.shop.filters.apply') }}</button>
                    <a href="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" class="inline-flex h-[42px] items-center justify-center border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('ui.shop.filters.reset') }}</a>
                </div>
            </form>
        </details>

        <form method="GET" action="{{ route('categories.show', ['slug' => $translation?->slug ?? $category->id]) }}" class="hidden gap-3 md:grid xl:grid-cols-[1fr_1fr_1fr_auto_auto]">
            <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
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
                            class="inline-flex h-full w-11 items-center justify-center border border-slate-300 {{ (int) ($filters['cols'] ?? 4) === $cols ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-100' }}"
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

    <section class="px-4 py-6 sm:px-6 lg:px-8">
        @if ($products->isEmpty())
            <div class="border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">{{ __('ui.category.empty') }}</div>
        @else
            <div class="{{ $gridClass }}">
                @foreach ($products as $product)
                    @include('front.desktop.partials.product-card', ['product' => $product, 'locale' => $locale, 'fallbackLocale' => $fallbackLocale, 'flat' => true])
                @endforeach
            </div>

            <div class="mt-6">
                {{ $products->links() }}
            </div>
        @endif
    </section>

    @if ($bottomBlocks->isNotEmpty())
        <section class="mt-10 px-4 sm:px-6 lg:px-8">
            @include('components.content-placement', ['items' => $bottomBlocks])
        </section>
    @endif
@endsection

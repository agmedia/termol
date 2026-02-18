@extends('front.desktop.layouts.store')

@section('title', 'Shop')

@section('content')
    <section class="mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Shop Products</h1>
        <p class="mt-2 text-slate-600">Search and filter your live catalog with pretty storefront routes.</p>
    </section>

    <div class="grid gap-8 lg:grid-cols-[280px_1fr]">
        <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('shop.index') }}" class="space-y-4">
                <div>
                    <label for="shop-q" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                    <input id="shop-q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Name, excerpt, description" class="w-full rounded-lg border-slate-300 text-sm">
                </div>

                <div>
                    <label for="shop-category" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Category</label>
                    <select id="shop-category" name="category" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            @php
                                $translation = $category->translations->firstWhere('locale', $locale)
                                    ?? $category->translations->firstWhere('locale', $fallbackLocale);
                            @endphp
                            <option value="{{ $translation?->slug }}" @selected($filters['category'] === ($translation?->slug ?? ''))>
                                {{ $translation?->name ?? $category->code }} ({{ $category->products_count }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="shop-manufacturer" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Manufacturer</label>
                    <select id="shop-manufacturer" name="manufacturer" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">All manufacturers</option>
                        @foreach ($manufacturers as $manufacturer)
                            @php
                                $translation = $manufacturer->translations->firstWhere('locale', $locale)
                                    ?? $manufacturer->translations->firstWhere('locale', $fallbackLocale);
                            @endphp
                            <option value="{{ $translation?->slug }}" @selected($filters['manufacturer'] === ($translation?->slug ?? ''))>
                                {{ $translation?->name ?? $manufacturer->code }} ({{ $manufacturer->products_count }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="shop-sort" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Sort</label>
                    <select id="shop-sort" name="sort" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="newest" @selected($filters['sort'] === 'newest')>Newest first</option>
                        <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest first</option>
                        <option value="price_low" @selected($filters['sort'] === 'price_low')>Price low to high</option>
                        <option value="price_high" @selected($filters['sort'] === 'price_high')>Price high to low</option>
                        <option value="stock_high" @selected($filters['sort'] === 'stock_high')>Stock availability</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Apply filters</button>
                    <a href="{{ route('shop.index') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Reset</a>
                </div>
            </form>
        </aside>

        <section>
            @if ($products->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                    No products found for current filters.
                </div>
            @else
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($products as $product)
                        @include('front.desktop.partials.product-card', ['product' => $product, 'locale' => $locale, 'fallbackLocale' => $fallbackLocale])
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $products->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection

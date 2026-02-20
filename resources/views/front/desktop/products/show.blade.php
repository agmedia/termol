@extends('front.desktop.layouts.store')

@php
    $translation = $product->translations->firstWhere('locale', $locale)
        ?? $product->translations->firstWhere('locale', $fallbackLocale);
    $manufacturerTranslation = $product->manufacturer?->translations?->firstWhere('locale', $locale)
        ?? $product->manufacturer?->translations?->firstWhere('locale', $fallbackLocale);
    $manufacturerEnabled = app(\App\Services\Catalog\CatalogFeatureService::class)->useManufacturers();
@endphp

@section('title', $translation?->name ?? 'Product')

@section('content')
    @if ($topBlocks->isNotEmpty())
        <section class="mb-8">
            @include('components.content-placement', ['items' => $topBlocks])
        </section>
    @endif

    <section class="grid gap-8 lg:grid-cols-[1fr_360px]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">SKU {{ $product->sku ?: 'n/a' }}</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ $translation?->name ?? $product->code }}</h1>

            @if ($manufacturerTranslation && $manufacturerEnabled)
                <p class="mt-2 text-sm text-slate-600">
                    Manufacturer:
                    <a href="{{ route('manufacturers.show', ['slug' => $manufacturerTranslation->slug]) }}" class="font-semibold text-blue-700 hover:text-blue-800">{{ $manufacturerTranslation->name }}</a>
                </p>
            @endif

            <p class="mt-4 text-slate-700">{{ $translation?->excerpt ?: 'No short description available.' }}</p>

            @if (! empty($translation?->description))
                <div class="prose mt-6 max-w-none prose-slate">{!! $translation->description !!}</div>
            @endif

            @if ($product->categories->isNotEmpty())
                <div class="mt-6 flex flex-wrap items-center gap-2">
                    @foreach ($product->categories as $category)
                        @php
                            $categoryTranslation = $category->translations->firstWhere('locale', $locale)
                                ?? $category->translations->firstWhere('locale', $fallbackLocale);
                        @endphp
                        @if ($categoryTranslation)
                            <a href="{{ route('categories.show', ['slug' => $categoryTranslation->slug]) }}" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">{{ $categoryTranslation->name }}</a>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-3xl font-extrabold text-slate-900">EUR {{ number_format((float) $product->base_price, 2) }}</p>
            <p class="mt-2 text-sm text-slate-600">Available stock: <span class="font-semibold text-slate-900">{{ (int) $product->stock_qty }}</span></p>

            <form method="POST" action="{{ route('cart.items.store') }}" class="mt-5 space-y-3">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                @if ($product->optionValues->where('is_active', true)->isNotEmpty())
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Option</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($product->optionValues->where('is_active', true)->values() as $row)
                                @php
                                    $valueTranslation = $row->optionValue?->translations?->firstWhere('locale', $locale)
                                        ?? $row->optionValue?->translations?->firstWhere('locale', $fallbackLocale)
                                        ?? $row->optionValue?->translations?->first();
                                    $parentTranslation = $row->parentOptionValue?->translations?->firstWhere('locale', $locale)
                                        ?? $row->parentOptionValue?->translations?->firstWhere('locale', $fallbackLocale)
                                        ?? $row->parentOptionValue?->translations?->first();
                                    $valueLabel = trim((string) ($valueTranslation?->name ?? $row->optionValue?->code ?? ''));
                                    $parentLabel = trim((string) ($parentTranslation?->name ?? $row->parentOptionValue?->code ?? ''));
                                    $label = $parentLabel !== '' && $valueLabel !== '' ? $parentLabel.' / '.$valueLabel : ($valueLabel !== '' ? $valueLabel : $parentLabel);
                                    $inputId = 'product-pov-'.$product->id.'-'.$row->id;
                                @endphp
                                <label for="{{ $inputId }}" class="inline-flex items-center gap-2 border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700">
                                    <input
                                        id="{{ $inputId }}"
                                        type="radio"
                                        name="product_option_value_id"
                                        value="{{ $row->id }}"
                                        class="h-4 w-4 appearance-none border border-slate-400 bg-white checked:border-slate-900 checked:bg-slate-900"
                                        @checked($loop->first)
                                        required
                                    >
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Quantity</label>
                    <input type="number" name="quantity" min="1" max="99" value="1" class="w-full rounded-lg border-slate-300 text-sm">
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 9h10l-1 10H8L7 9Z"></path>
                        <path d="M9 9V7a3 3 0 0 1 6 0v2"></path>
                    </svg>
                    Add to cart
                </button>
                <a href="{{ route('checkout.create') }}" class="block w-full rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-100">Go to checkout</a>
            </form>
        </aside>
    </section>

    @if ($related->isNotEmpty())
        <section class="mt-10">
            <h2 class="text-2xl font-bold text-slate-900">Related products</h2>
            <div class="mt-4 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($related as $product)
                    @include('front.desktop.partials.product-card', ['product' => $product, 'locale' => $locale, 'fallbackLocale' => $fallbackLocale])
                @endforeach
            </div>
        </section>
    @endif

    @if ($bottomBlocks->isNotEmpty())
        <section class="mt-10">
            @include('components.content-placement', ['items' => $bottomBlocks])
        </section>
    @endif
@endsection

@extends('front.mobile.layouts.store')

@php
    $translation = $product->translations->firstWhere('locale', $locale)
        ?? $product->translations->firstWhere('locale', $fallbackLocale);
    $manufacturerTranslation = $product->manufacturer?->translations?->firstWhere('locale', $locale)
        ?? $product->manufacturer?->translations?->firstWhere('locale', $fallbackLocale);
    $manufacturerEnabled = app(\App\Services\Catalog\CatalogFeatureService::class)->useManufacturers();
    $displayBasePrice = app(\App\Services\Pricing\TaxPricingService::class)->grossFromNet((float) $product->base_price, $product);
@endphp

@section('title', $translation?->name ?? 'Product')
@section('header_title', 'Product')
@section('page_title', 'Details')

@section('content')
    @if ($topBlocks->isNotEmpty())
        @include('components.content-placement', ['items' => $topBlocks])
    @endif

    <div class="card card-style bg-18" data-card-height="260">
        <div class="card-bottom mb-3 ms-3 me-3">
            <h2 class="color-white font-800 mb-0">{{ $translation?->name ?? $product->code }}</h2>
            <p class="color-white font-14 mb-0 opacity-60">{{ $translation?->excerpt ?: 'Product overview and cart actions.' }}</p>
        </div>
        <div class="card-overlay bg-black opacity-60"></div>
    </div>

    <div class="card card-style">
        <div class="content">
            <h2 class="mb-0">{{ number_format($displayBasePrice, 2) }} €</h2>
            <p class="font-12 opacity-60 mb-2">SKU {{ $product->sku ?: 'n/a' }} • Stock {{ (int) $product->stock_qty }}</p>

            @if ($manufacturerTranslation && $manufacturerEnabled)
                <p class="font-12 mb-3">
                    Manufacturer:
                    <a href="{{ route('manufacturers.show', ['slug' => $manufacturerTranslation->slug]) }}" class="color-highlight">{{ $manufacturerTranslation->name }}</a>
                </p>
            @endif

            <form method="POST" action="{{ route('cart.items.store') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                @if ($product->optionValues->where('is_active', true)->isNotEmpty())
                    <div class="input-style has-borders no-icon input-style-always-active mb-3">
                        <label for="product-option" class="color-highlight">Option</label>
                        <select id="product-option" name="product_option_value_id" required>
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
                                @endphp
                                <option value="{{ $row->id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <span><i class="fa fa-chevron-down"></i></span>
                    </div>
                @endif
                <div class="row mb-2">
                    <div class="col-5">
                        <div class="input-style has-borders no-icon input-style-always-active mb-3">
                            <label for="product-qty" class="color-highlight">Qty</label>
                            <input id="product-qty" type="number" name="quantity" min="1" max="99" value="1">
                        </div>
                    </div>
                    <div class="col-7">
                        <button type="submit" class="btn btn-full gradient-highlight font-600 rounded-s mt-1 d-inline-flex align-items-center justify-content-center gap-2">
                            <svg style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" aria-hidden="true">
                                <path d="M7 9h10l-1 10H8L7 9Z"></path>
                                <path d="M9 9V7a3 3 0 0 1 6 0v2"></path>
                            </svg>
                            Add to cart
                        </button>
                    </div>
                </div>
            </form>

            <a href="{{ route('checkout.create') }}" class="btn btn-full btn-border border-gray-dark color-gray-dark rounded-s font-600">Checkout now</a>

            @if (!empty($translation?->description))
                <div class="divider mt-4"></div>
                <h4>Description</h4>
                <div class="font-13">{!! $translation->description !!}</div>
            @endif
        </div>
    </div>

    @if ($related->isNotEmpty())
        <div class="card card-style">
            <div class="content mb-1">
                <h4>Related Products</h4>
            </div>
        </div>
        @foreach ($related as $product)
            @include('front.mobile.partials.product-card', ['product' => $product, 'locale' => $locale, 'fallbackLocale' => $fallbackLocale])
        @endforeach
    @endif

    @if ($bottomBlocks->isNotEmpty())
        @include('components.content-placement', ['items' => $bottomBlocks])
    @endif
@endsection

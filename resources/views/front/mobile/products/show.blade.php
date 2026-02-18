@extends('front.mobile.layouts.store')

@php
    $translation = $product->translations->firstWhere('locale', $locale)
        ?? $product->translations->firstWhere('locale', $fallbackLocale);
    $manufacturerTranslation = $product->manufacturer?->translations?->firstWhere('locale', $locale)
        ?? $product->manufacturer?->translations?->firstWhere('locale', $fallbackLocale);
    $manufacturerEnabled = app(\App\Services\Catalog\CatalogFeatureService::class)->useManufacturers();
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
            <h2 class="mb-0">EUR {{ number_format((float) $product->base_price, 2) }}</h2>
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
                <div class="row mb-2">
                    <div class="col-5">
                        <div class="input-style has-borders no-icon input-style-always-active mb-3">
                            <label for="product-qty" class="color-highlight">Qty</label>
                            <input id="product-qty" type="number" name="quantity" min="1" max="99" value="1">
                        </div>
                    </div>
                    <div class="col-7">
                        <button type="submit" class="btn btn-full gradient-highlight font-600 rounded-s mt-1">Add to cart</button>
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

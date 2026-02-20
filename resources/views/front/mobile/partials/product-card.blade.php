@php
    $translation = $product->translations->firstWhere('locale', $locale ?? app()->getLocale())
        ?? $product->translations->firstWhere('locale', $fallbackLocale ?? config('app.locale'));
    $displayPrice = app(\App\Services\Pricing\TaxPricingService::class)->grossFromNet((float) $product->base_price, $product);
@endphp

<div class="card card-style mb-2">
    <div class="content">
        <div class="d-flex">
            <div class="w-100 pe-2">
                <a href="{{ route('products.show', ['slug' => $translation?->slug ?? $product->id]) }}" class="d-block">
                    <h5 class="font-600 mb-1">{{ $translation?->name ?? $product->code }}</h5>
                    <p class="font-12 opacity-70 mb-2">{{ $translation?->excerpt ?: 'Catalog ready item.' }}</p>
                </a>
                <h4 class="font-700 mb-0">{{ number_format($displayPrice, 2) }} €</h4>
                <p class="font-11 opacity-50 mb-0">Stock {{ (int) $product->stock_qty }}</p>
            </div>
            <form method="POST" action="{{ route('cart.items.store') }}" class="align-self-center text-end" style="min-width:88px;">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="number" name="quantity" min="1" max="99" value="1" class="form-control mb-2" style="height:32px;">
                <button type="submit" class="btn btn-3d btn-xs font-600 bg-highlight d-inline-flex align-items-center justify-content-center gap-1">
                    <svg class="me-1" style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" aria-hidden="true">
                        <path d="M7 9h10l-1 10H8L7 9Z"></path>
                        <path d="M9 9V7a3 3 0 0 1 6 0v2"></path>
                    </svg>
                    Add
                </button>
            </form>
        </div>
    </div>
</div>

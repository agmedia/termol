@php
    $cardLocale = $locale ?? app()->getLocale();
    $cardFallbackLocale = $fallbackLocale ?? config('app.locale');
    $cardTranslation = $product->translations->firstWhere('locale', $cardLocale)
        ?? $product->translations->firstWhere('locale', $cardFallbackLocale)
        ?? $product->translations->first();
    $cardBrand = $product->manufacturer?->translations?->firstWhere('locale', $cardLocale)
        ?? $product->manufacturer?->translations?->firstWhere('locale', $cardFallbackLocale)
        ?? $product->manufacturer?->translations?->first();
    $cardMedia = $product->media
        ->whereIn('collection_name', ['product_main', 'product_gallery'])
        ->first(fn ($media) => \App\Support\Media\MediaUrl::hasUsableSource($media, ['card_480w', 'card_320w']));
    $cardImage = \App\Support\Media\MediaUrl::conversionOrNull($cardMedia, 'card_480w')
        ?? \App\Support\Media\MediaUrl::conversionOrNull($cardMedia, 'card_320w')
        ?? ($cardMedia ? $cardMedia->getUrl() : null);
    $cardPrice = app(\App\Services\Pricing\ProductPricePresentationService::class)->forProduct($product, auth()->user());
    $cardUrl = route('products.show', ['slug' => $cardTranslation?->slug ?? $product->id]);
    $cardHasOptions = $product->visibleOptionRows()->isNotEmpty();
    $cardPurchasable = $product->storefrontIsPurchasable();
@endphp

<article class="product-widget-card">
    <a href="{{ $cardUrl }}" class="product-widget-card-media">
        @if ($cardImage)
            <img
                src="{{ $cardImage }}"
                alt="{{ $cardTranslation?->name ?? $product->code }}"
                loading="lazy"
                decoding="async"
            >
        @else
            <span>{{ __('ui.product.no_image') }}</span>
        @endif
    </a>

    <div class="product-widget-card-content">
        <a href="{{ $cardUrl }}" class="product-widget-card-title">
            {{ $cardTranslation?->name ?? $product->code }}
        </a>
        @if ($cardBrand)
            <p class="product-widget-card-brand">{{ $cardBrand->name }}</p>
        @endif
        @if (!empty($cardPrice['is_b2b_price']))
            <p class="font-11 font-600 color-highlight mb-1">{{ __('ui.product.b2b_contract_price') }}</p>
        @endif
        <div class="product-widget-card-price">
            @if (($cardPrice['old_gross'] ?? null) !== null)
                <del>{{ number_format((float) $cardPrice['old_gross'], 2) }} €</del>
            @endif
            <strong>{{ number_format((float) ($cardPrice['current_gross'] ?? 0), 2) }} €</strong>
        </div>

        @if ($cardPurchasable && ! $cardHasOptions)
            <form method="POST" action="{{ route('cart.items.store') }}" class="product-widget-card-form">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <div class="product-widget-card-quantity" data-qty-control>
                    <button type="button" data-qty-dec aria-label="{{ __('ui.cart.modal.quantity') }} -">
                        <x-fa-icon name="minus" />
                    </button>
                    <input type="text" name="quantity" value="1" readonly data-qty-input data-qty-value aria-label="{{ __('ui.cart.modal.quantity') }}">
                    <button type="button" data-qty-inc aria-label="{{ __('ui.cart.modal.quantity') }} +">
                        <x-fa-icon name="plus" />
                    </button>
                </div>
                <button type="submit" class="product-widget-card-cart" aria-label="{{ __('ui.product.add_to_cart') }}">
                    <x-fa-icon name="bag-shopping" />
                </button>
            </form>
        @elseif ($cardPurchasable)
            <a href="{{ $cardUrl }}" class="product-widget-card-select">
                {{ __('ui.product.select_options') }}
            </a>
        @else
            <span class="product-widget-card-unavailable">{{ __('ui.product.unavailable') }}</span>
        @endif
    </div>
</article>

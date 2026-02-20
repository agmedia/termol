<article class="{{ $flat ? 'border border-slate-200 bg-white p-4' : 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm' }}">
    <div class="relative">
        <a href="{{ $productUrl }}" class="group block">
            @if ($imageUrl)
                <div class="relative">
                    <img
                        src="{{ $imageUrl }}"
                        alt="{{ $productName }}"
                        class="{{ $flat ? 'w-full h-auto' : 'w-full h-auto rounded-xl' }} {{ $hoverImageUrl ? 'transition-opacity duration-300 group-hover:opacity-0' : '' }}"
                        loading="lazy"
                        decoding="async"
                    >
                    @if ($hoverImageUrl)
                        <img
                            src="{{ $hoverImageUrl }}"
                            alt="{{ $productName }}"
                            class="absolute inset-0 h-full w-full opacity-0 transition-opacity duration-300 group-hover:opacity-100 {{ $flat ? '' : 'rounded-xl' }}"
                            loading="lazy"
                            decoding="async"
                        >
                    @endif
                </div>
            @else
                <div class="{{ $flat ? 'flex min-h-56 w-full items-center justify-center bg-slate-100 text-xs font-semibold uppercase text-slate-500' : 'flex min-h-56 w-full items-center justify-center rounded-xl bg-slate-100 text-xs font-semibold uppercase text-slate-500' }}">
                    {{ __('ui.product.no_image') }}
                </div>
            @endif
        </a>
        <form
            method="POST"
            action="{{ route('wishlist.items.toggle', ['product' => $productId]) }}"
            class="absolute right-2 top-2"
            data-wishlist-form
            data-wishlisted="{{ $isWishlisted ? '1' : '0' }}"
            data-label-add="{{ __('ui.wishlist.add') }}"
            data-label-remove="{{ __('ui.wishlist.remove') }}"
            data-msg-failed="{{ __('ui.wishlist.status.failed') }}"
        >
            @csrf
            <button
                type="submit"
                class="inline-flex h-10 w-10 items-center justify-center border transition hover:border-slate-900 {{ $isWishlisted ? 'border-slate-900 bg-slate-900 text-white hover:bg-slate-700' : 'border-slate-200 bg-white/95 text-slate-700 hover:text-slate-900' }}"
                data-wishlist-button
                aria-label="{{ $isWishlisted ? __('ui.wishlist.remove') : __('ui.wishlist.add') }}"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M20.8 8.6c0 5.9-8.8 10.9-8.8 10.9S3.2 14.5 3.2 8.6a4.8 4.8 0 0 1 8.8-2.7 4.8 4.8 0 0 1 8.8 2.7Z"></path>
                </svg>
            </button>
        </form>
    </div>

    <a href="{{ $productUrl }}" class="mt-3 block">
        <h3 class="text-lg font-semibold leading-tight text-slate-900">{{ $productName }}</h3>
    </a>

    <div class="mt-3 flex items-center justify-between">
        <p class="text-lg font-bold text-slate-900">{{ $price }}</p>
    </div>

    <form
        method="POST"
        action="{{ route('cart.items.store') }}"
        class="mt-3 space-y-3"
        data-product-card-form
        data-product-name="{{ $productName }}"
        data-product-image="{{ (string) ($imageUrl ?? '') }}"
        data-cart-url="{{ route('cart.index') }}"
        data-modal-continue="{{ __('ui.cart.modal.continue') }}"
        data-modal-go-cart="{{ __('ui.cart.modal.go_to_cart') }}"
        data-modal-option="{{ __('ui.cart.modal.option') }}"
        data-modal-quantity="{{ __('ui.cart.modal.quantity') }}"
    >
        @csrf
        <input type="hidden" name="product_id" value="{{ $productId }}">
        @if ($optionRows !== [])
            <div class="w-full">
                <div class="mb-2 flex flex-wrap gap-2">
                    @foreach ($optionRows as $row)
                        <label for="{{ $row['input_id'] }}" class="inline-flex h-9 min-w-9 cursor-pointer items-center justify-center border border-slate-400 bg-white px-2.5 text-xs font-semibold text-slate-700 transition hover:border-slate-900 hover:bg-slate-100 has-[:checked]:border-slate-900 has-[:checked]:bg-slate-900 has-[:checked]:text-white">
                            <input
                                id="{{ $row['input_id'] }}"
                                type="radio"
                                name="product_option_value_id"
                                value="{{ $row['id'] }}"
                                class="sr-only"
                            >
                            <span>{{ $row['label'] }}</span>
                        </label>
                    @endforeach
                </div>
                <p
                    class="{{ (int) old('product_id', 0) === $productId && $errors->has('product_option_value_id') ? '' : 'hidden' }} text-xs font-semibold text-rose-600"
                    data-option-error
                    aria-live="polite"
                >
                    {{ __('ui.cart.errors.select_size') }}
                </p>
            </div>
        @endif
        <div class="flex items-center gap-2">
            <div class="inline-flex h-11 items-stretch bg-white" data-qty-control>
                <button type="button" class="inline-flex h-11 w-11 items-center justify-center border border-slate-400 text-lg font-semibold text-slate-700 hover:bg-slate-100" data-qty-dec aria-label="Decrease quantity">-</button>
                <input type="text" name="quantity" value="1" inputmode="numeric" readonly class="h-11 w-11 border-y border-r border-slate-400 border-l-0 bg-white p-0 text-center text-sm font-semibold text-slate-900 focus:ring-0" data-qty-input data-qty-value>
                <button type="button" class="inline-flex h-11 w-11 items-center justify-center border border-l-0 border-slate-400 text-lg font-semibold text-slate-700 hover:bg-slate-100" data-qty-inc aria-label="Increase quantity">+</button>
            </div>
            <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center gap-2 bg-slate-900 px-3 text-sm font-semibold text-white hover:bg-slate-700 {{ $flat ? 'rounded-none' : 'rounded-lg' }}" aria-label="{{ __('ui.product.add_to_cart') }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M7 9h10l-1 10H8L7 9Z"></path>
                    <path d="M9 9V7a3 3 0 0 1 6 0v2"></path>
                </svg>
                <span>{{ __('ui.product.add_to_cart') }}</span>
            </button>
        </div>
    </form>
</article>

@once
    @push('scripts')
        <script defer src="{{ asset('front-theme/scripts/product-card-options.js') }}"></script>
        <script defer src="{{ asset('front-theme/scripts/product-card-quantity.js') }}"></script>
        <script defer src="{{ asset('front-theme/scripts/product-card-cart-modal.js') }}"></script>
        <script defer src="{{ asset('front-theme/scripts/wishlist-toggle.js') }}"></script>
    @endpush
@endonce

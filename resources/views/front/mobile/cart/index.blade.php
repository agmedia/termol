@extends('front.mobile.layouts.store')

@section('title', __('ui.cart.page_title'))
@section('header_title', __('ui.cart.title'))
@section('page_title', __('ui.cart.title'))
@section('body_class', 'mobile-commerce-body mobile-cart-commerce-body')

@push('head')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/commerce-pages.css') }}?v={{ filemtime(public_path('front-theme/styles/commerce-pages.css')) }}">
@endpush

@section('content')
    @if ($lines->isEmpty())
        <div class="card card-style"><div class="content"><p class="mb-2">{{ __('ui.cart.empty') }}</p><a href="{{ route('shop.index') }}" class="commerce-primary-action btn btn-s font-600 px-3">{{ __('ui.cart.actions.continue') }}</a></div></div>
    @else
        @foreach ($lines as $line)
            @php
                $product = $line['product'];
                $translation = $line['translation'];
                $manufacturerTranslation = $product->relationLoaded('manufacturer')
                    ? ($product->manufacturer?->translations?->firstWhere('locale', app()->getLocale())
                        ?? $product->manufacturer?->translations?->firstWhere('locale', config('app.locale')))
                    : null;
                $categoryTranslation = $product->relationLoaded('categories')
                    ? ($product->categories?->first()?->translations?->firstWhere('locale', app()->getLocale())
                        ?? $product->categories?->first()?->translations?->firstWhere('locale', config('app.locale')))
                    : null;
                $displayCurrent = (float) ($line['display_unit_price'] ?? $line['unit_price'] ?? 0);
            @endphp
            <div class="card card-style mb-2">
                <div class="content">
                    <div class="d-flex">
                        <div class="w-100 me-2">
                            <h6 class="font-500 font-14 pb-1">{{ $translation?->name ?? $product->code }}</h6>
                            @if (!empty($line['sku']))
                                <p class="font-11 opacity-60 mb-1">SKU: {{ $line['sku'] }}</p>
                            @endif
                            @if (!empty($line['option_label']))
                                <p class="font-11 opacity-60 mb-1">{{ $line['option_label'] }}</p>
                            @endif
                            @if (!empty($line['is_b2b_price']))
                                <p class="font-11 font-600 color-highlight mb-1">{{ __('ui.product.b2b_contract_price') }}</p>
                            @endif
                            <h4 class="font-700 mb-1">{{ number_format((float) ($line['display_line_total'] ?? $line['line_total']), 2) }} €</h4>
                            <p class="font-11 opacity-60 mb-0">{{ __('ui.cart.table.price') }}: {{ number_format((float) ($line['display_unit_price'] ?? $line['unit_price']), 2) }} €</p>
                        </div>
                        <div class="align-self-center" style="min-width:88px;">
                            <form method="POST" action="{{ route('cart.items.update', ['product' => $product->id]) }}" class="mb-2">
                                @csrf
                                @method('PATCH')
                                @if (!empty($line['product_option_value_id']))
                                    <input type="hidden" name="product_option_value_id" value="{{ (int) $line['product_option_value_id'] }}">
                                @endif
                                <div class="cart-quantity-stepper mb-2" data-cart-qty-control>
                                    <button type="button" data-cart-qty-dec aria-label="{{ __('ui.cart.modal.quantity') }} -">−</button>
                                    <input id="cart-quantity-{{ $product->id }}-{{ (int) ($line['product_option_value_id'] ?? 0) }}" type="text" name="quantity" value="{{ (int) $line['quantity'] }}" inputmode="numeric" readonly aria-label="{{ __('ui.cart.table.quantity') }}" data-cart-qty-input>
                                    <button type="button" data-cart-qty-inc aria-label="{{ __('ui.cart.modal.quantity') }} +">+</button>
                                </div>
                                <button type="submit" class="cart-mobile-item-action btn btn-xs font-600 bg-highlight">{{ __('ui.cart.table.save') }}</button>
                            </form>
                            <form
                                method="POST"
                                action="{{ route('cart.items.destroy', ['product' => $product->id]) }}"
                                data-ga4-remove-from-cart-form
                                data-ga4-item-id="{{ (string) ($line['sku'] ?: $product->sku ?: $product->id) }}"
                                data-ga4-item-name="{{ $translation?->name ?? $product->code }}"
                                data-ga4-item-price="{{ number_format((float) $displayCurrent, 2, '.', '') }}"
                                data-ga4-item-brand="{{ (string) ($manufacturerTranslation?->name ?? '') }}"
                                data-ga4-item-category="{{ (string) ($categoryTranslation?->name ?? '') }}"
                                data-ga4-currency="EUR"
                                data-ga4-quantity="{{ (int) $line['quantity'] }}"
                            >
                                @csrf
                                @method('DELETE')
                                @if (!empty($line['product_option_value_id']))
                                    <input type="hidden" name="product_option_value_id" value="{{ (int) $line['product_option_value_id'] }}">
                                @endif
                                <button type="submit" class="cart-mobile-item-action cart-mobile-remove btn btn-xs font-600">{{ __('ui.cart.table.remove') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="cart-mobile-summary card card-style mt-n2">
            <div class="content mb-2 mt-3">
                <div class="d-flex">
                    <div class="pe-4 w-60">
                        <p class="font-600 color-highlight mb-0 font-13">{{ __('ui.cart.summary.total') }}</p>
                        <h2>{{ number_format((float) ($summary['grand_total'] ?? $summary['subtotal']), 2) }} €</h2>
                    </div>
                    <div class="w-100 pt-1">
                        <h6 class="font-14 font-700">{{ __('ui.cart.summary.items') }} <span class="float-end color-theme">{{ $summary['item_qty'] }}</span></h6>
                        <div class="divider mb-2 mt-1"></div>
                        <h6 class="font-14 font-700">{{ __('ui.cart.summary.subtotal') }} <span class="float-end color-theme">{{ number_format((float) $summary['subtotal'], 2) }} €</span></h6>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('checkout.create') }}" class="commerce-primary-action btn btn-margins btn-full font-13 btn-l font-600 mt-3">{{ __('ui.cart.actions.checkout') }}</a>

        <form method="POST" action="{{ route('cart.clear') }}" class="content mt-2">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-full btn-border border-gray-dark color-gray-dark rounded-sm font-600">{{ __('ui.cart.actions.clear') }}</button>
        </form>
    @endif
@endsection

@push('scripts')
    <script defer src="{{ asset('front-theme/scripts/cart-quantity.js') }}?v={{ filemtime(public_path('front-theme/scripts/cart-quantity.js')) }}"></script>
@endpush

@extends('front.mobile.layouts.store')

@section('title', __('ui.checkout.page_title'))
@section('header_title', __('ui.checkout.title'))
@section('page_title', __('ui.checkout.title'))

@section('content')
    @php
        $showShippingAddress = old('ship_to_different_address') === '1' || old('use_billing_for_shipping') === '0';
        $selectedShippingCode = (string) old('shipping_method_code', (string) ($shippingMethods->first()?->code ?? ''));
        $selectedPaymentCode = (string) old('payment_method_code', (string) ($paymentMethods->first()?->code ?? ''));
        $ga4Items = collect($lines)->map(function (array $line) {
            $locale = app()->getLocale();
            $fallbackLocale = (string) config('app.locale');
            $product = $line['product'];
            $translation = $line['translation'];
            $manufacturerTranslation = $product->relationLoaded('manufacturer')
                ? ($product->manufacturer?->translations?->firstWhere('locale', $locale)
                    ?? $product->manufacturer?->translations?->firstWhere('locale', $fallbackLocale))
                : null;
            $categoryTranslation = $product->relationLoaded('categories')
                ? ($product->categories?->first()?->translations?->firstWhere('locale', $locale)
                    ?? $product->categories?->first()?->translations?->firstWhere('locale', $fallbackLocale))
                : null;

            return [
                'item_id' => (string) ($line['sku'] ?: $product->sku ?: $product->id),
                'item_name' => (string) ($translation?->name ?? $product->code),
                'item_brand' => (string) ($manufacturerTranslation?->name ?? ''),
                'item_category' => (string) ($categoryTranslation?->name ?? ''),
                'price' => round((float) ($line['display_unit_price'] ?? $line['unit_price'] ?? 0), 2),
                'quantity' => (int) ($line['quantity'] ?? 1),
            ];
        })->values()->all();
    @endphp

    <form method="POST" action="{{ route('checkout.store') }}" data-address-autofill data-address-source="{{ $placesAssetUrl }}" data-checkout-options-url="{{ route('checkout.options') }}" data-region-options='@json($regionOptionsByCountry, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)' data-ga4-checkout-form data-ga4-currency="EUR" data-ga4-value="{{ number_format((float) ($summary['grand_total'] ?? 0), 2, '.', '') }}" data-ga4-items='@json($ga4Items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'>
        @csrf

        <div class="card card-style">
            <div class="content">
                <p class="font-600 color-highlight mb-n1">{{ __('ui.cart.title') }}</p>
                <h3>{{ __('ui.checkout.cart_title') }}</h3>
                @foreach ($lines as $line)
                    @php
                        $translation = $line['translation'];
                        $product = $line['product'];
                        $productImage = $product->getFirstMedia('product_main') ?? $product->getFirstMedia('product_gallery');
                        $productImageUrl = $productImage
                            ? ($productImage->hasGeneratedConversion('thumb_100x100') ? $productImage->getUrl('thumb_100x100') : $productImage->getUrl())
                            : null;
                    @endphp
                    <div class="d-flex mb-3">
                        <div class="me-3" style="width: 64px;">
                            <div class="border rounded-sm p-1 bg-white">
                                @if ($productImageUrl)
                                    <img src="{{ $productImageUrl }}" alt="{{ $translation?->name ?? $line['product']->code }}" class="img-fluid">
                                @else
                                    <div class="text-center font-10 opacity-60">{{ __('ui.product.no_image') }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="w-100">
                            <h6 class="font-500 font-14 pb-1">{{ $translation?->name ?? $line['product']->code }}</h6>
                            @if (!empty($line['sku']))
                                <p class="font-11 opacity-60 mb-1">{{ __('ui.checkout.labels.sku') }}: {{ $line['sku'] }}</p>
                            @endif
                            @if (!empty($line['option_label']))
                                <p class="font-11 opacity-60 mb-1">{{ $line['option_label'] }}</p>
                            @endif
                            <p class="font-11 opacity-60 mb-1">{{ __('ui.checkout.labels.qty') }} {{ $line['quantity'] }}</p>
                            <h4 class="font-700 mb-0">{{ \App\Support\Currency::format((float) ($line['display_line_total'] ?? $line['line_total'])) }}</h4>
                        </div>
                    </div>
                    @if (!$loop->last)<div class="divider"></div>@endif
                @endforeach
            </div>
        </div>

        <div class="card card-style mt-n2">
            <div class="content mb-2 mt-3">
                <div class="d-flex">
                    <div class="pe-4 w-60">
                        <p class="font-600 color-highlight mb-0 font-13">{{ __('ui.checkout.labels.total') }}</p>
                        <h2>{{ \App\Support\Currency::format((float) ($summary['grand_total'] ?? $summary['subtotal'])) }}</h2>
                    </div>
                    <div class="w-100 pt-1">
                        <h6 class="font-14 font-700">{{ __('ui.checkout.labels.items') }} <span class="float-end color-theme">{{ $summary['item_qty'] }}</span></h6>
                        <div class="divider mb-2 mt-1"></div>
                        <h6 class="font-14 font-700">{{ __('ui.checkout.labels.lines') }} <span class="float-end color-theme">{{ $summary['line_count'] }}</span></h6>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-style">
            <div class="content">
                <p class="font-600 color-highlight mb-n1">{{ __('ui.checkout.sections.customer') }}</p>
                <h3>{{ __('ui.checkout.sections.basic_information') }}</h3>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="customer-first" class="color-highlight">{{ __('ui.account.fields.first_name') }}</label><input id="customer-first" type="text" name="customer_first_name" value="{{ old('customer_first_name', $prefill['first_name']) }}" required></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="customer-last" class="color-highlight">{{ __('ui.account.fields.last_name') }}</label><input id="customer-last" type="text" name="customer_last_name" value="{{ old('customer_last_name', $prefill['last_name']) }}" required></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="customer-email" class="color-highlight">{{ __('ui.account.fields.email') }}</label><input id="customer-email" type="email" name="customer_email" value="{{ old('customer_email', $prefill['email']) }}" required></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-0"><label for="customer-phone" class="color-highlight">{{ __('ui.account.fields.phone') }}</label><input id="customer-phone" type="text" name="customer_phone" value="{{ old('customer_phone', $prefill['phone']) }}"></div>
            </div>
        </div>

        <div class="card card-style" data-address-scope="billing">
            <div class="content">
                <p class="font-600 color-highlight mb-n1">{{ __('ui.account.address.types.billing') }}</p>
                <h3>{{ __('ui.checkout.sections.billing') }}</h3>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="billing-first" class="color-highlight">{{ __('ui.account.fields.first_name') }}</label><input id="billing-first" type="text" name="billing_first_name" value="{{ old('billing_first_name', $prefill['billing']['first_name']) }}" required></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="billing-last" class="color-highlight">{{ __('ui.account.fields.last_name') }}</label><input id="billing-last" type="text" name="billing_last_name" value="{{ old('billing_last_name', $prefill['billing']['last_name']) }}" required></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="billing-company" class="color-highlight">{{ __('ui.account.fields.company') }}</label><input id="billing-company" type="text" name="billing_company" value="{{ old('billing_company', $prefill['billing']['company']) }}"></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="billing-oib" class="color-highlight">{{ __('ui.account.fields.oib') }}</label><input id="billing-oib" type="text" name="billing_oib" value="{{ old('billing_oib', $prefill['billing']['oib']) }}"></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="billing-address1" class="color-highlight">{{ __('ui.account.fields.address_line_1') }}</label><input id="billing-address1" type="text" name="billing_address_line_1" value="{{ old('billing_address_line_1', $prefill['billing']['address_line_1']) }}" required></div>
                <div class="row">
                    <div class="col-4"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="billing-postal" class="color-highlight">{{ __('ui.account.fields.postal_code') }}</label><input id="billing-postal" type="text" name="billing_postal_code" value="{{ old('billing_postal_code', $prefill['billing']['postal_code']) }}" data-address-postal required></div></div>
                    <div class="col-8"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="billing-city" class="color-highlight">{{ __('ui.account.fields.city') }}</label><input id="billing-city" type="text" name="billing_city" value="{{ old('billing_city', $prefill['billing']['city']) }}" data-address-city required></div></div>
                </div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="billing-state" class="color-highlight" data-state-label data-label-hr="{{ __('ui.account.fields.county') }}" data-label-intl="{{ __('ui.account.fields.region') }}">{{ __('ui.account.fields.state') }}</label>
                    <select id="billing-state" name="billing_state" data-address-county data-state-select data-option-hr="{{ __('ui.account.fields.select_county') }}" data-option-intl="{{ __('ui.account.fields.select_region') }}">
                        <option value="">{{ __('ui.account.fields.select_county') }}</option>
                        @foreach ($countyOptions as $countyOption)
                            <option value="{{ $countyOption }}" @selected(old('billing_state', $prefill['billing']['state']) === $countyOption)>{{ $countyOption }}</option>
                        @endforeach
                    </select>
                    <input type="text" value="{{ old('billing_state', $prefill['billing']['state']) }}" data-state-input data-placeholder-intl="{{ __('ui.account.fields.enter_region') }}" style="display:none;">
                    <span><i class="fa fa-chevron-down"></i></span>
                </div>
                <div class="input-style has-borders no-icon input-style-always-active mb-0">
                    <label for="billing-country" class="color-highlight">{{ __('ui.account.fields.country_code') }}</label>
                    <select id="billing-country" name="billing_country_code" data-address-country required>
                        @foreach ($countryOptions as $countryOption)
                            <option value="{{ $countryOption['code'] }}" @selected(old('billing_country_code', $prefill['billing']['country_code']) === $countryOption['code'])>{{ $countryOption['label'] }}</option>
                        @endforeach
                    </select>
                    <span><i class="fa fa-chevron-down"></i></span>
                </div>
            </div>
        </div>

        <div class="card card-style" data-address-scope="shipping">
            <div class="content">
                <div class="d-flex mb-2">
                    <div>
                        <p class="font-600 color-highlight mb-n1">{{ __('ui.account.address.types.shipping') }}</p>
                        <h3>{{ __('ui.checkout.sections.shipping') }}</h3>
                    </div>
                    <div class="ms-auto align-self-end">
                        <label class="font-12"><input type="checkbox" name="ship_to_different_address" value="1" @checked($showShippingAddress) data-ship-to-different> {{ __('ui.checkout.options.ship_to_different_address') }}</label>
                    </div>
                </div>

                <input type="hidden" name="use_billing_for_shipping" value="{{ $showShippingAddress ? '0' : '1' }}" data-use-billing-for-shipping>

                <div class="overflow-hidden" data-shipping-fields style="{{ $showShippingAddress ? '' : 'max-height:0;opacity:0;' }}; transition: max-height 0.3s ease, opacity 0.3s ease;">
                    <div class="pt-2">
                        <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="shipping-first" class="color-highlight">{{ __('ui.account.fields.first_name') }}</label><input id="shipping-first" type="text" name="shipping_first_name" value="{{ old('shipping_first_name', $prefill['shipping']['first_name']) }}"></div>
                        <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="shipping-last" class="color-highlight">{{ __('ui.account.fields.last_name') }}</label><input id="shipping-last" type="text" name="shipping_last_name" value="{{ old('shipping_last_name', $prefill['shipping']['last_name']) }}"></div>
                        <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="shipping-company" class="color-highlight">{{ __('ui.account.fields.company') }}</label><input id="shipping-company" type="text" name="shipping_company" value="{{ old('shipping_company', $prefill['shipping']['company']) }}"></div>
                        <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="shipping-vat" class="color-highlight">{{ __('ui.account.fields.vat_id') }}</label><input id="shipping-vat" type="text" name="shipping_vat_id" value="{{ old('shipping_vat_id', $prefill['shipping']['vat_id']) }}"></div>
                        <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="shipping-address1" class="color-highlight">{{ __('ui.account.fields.address_line_1') }}</label><input id="shipping-address1" type="text" name="shipping_address_line_1" value="{{ old('shipping_address_line_1', $prefill['shipping']['address_line_1']) }}"></div>
                        <div class="row">
                            <div class="col-4"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="shipping-postal" class="color-highlight">{{ __('ui.account.fields.postal_code') }}</label><input id="shipping-postal" type="text" name="shipping_postal_code" value="{{ old('shipping_postal_code', $prefill['shipping']['postal_code']) }}" data-address-postal></div></div>
                            <div class="col-8"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="shipping-city" class="color-highlight">{{ __('ui.account.fields.city') }}</label><input id="shipping-city" type="text" name="shipping_city" value="{{ old('shipping_city', $prefill['shipping']['city']) }}" data-address-city></div></div>
                        </div>
                        <div class="input-style has-borders no-icon input-style-always-active mb-3">
                            <label for="shipping-state" class="color-highlight" data-state-label data-label-hr="{{ __('ui.account.fields.county') }}" data-label-intl="{{ __('ui.account.fields.region') }}">{{ __('ui.account.fields.state') }}</label>
                            <select id="shipping-state" name="shipping_state" data-address-county data-state-select data-option-hr="{{ __('ui.account.fields.select_county') }}" data-option-intl="{{ __('ui.account.fields.select_region') }}">
                                <option value="">{{ __('ui.account.fields.select_county') }}</option>
                                @foreach ($countyOptions as $countyOption)
                                    <option value="{{ $countyOption }}" @selected(old('shipping_state', $prefill['shipping']['state']) === $countyOption)>{{ $countyOption }}</option>
                                @endforeach
                            </select>
                            <input type="text" value="{{ old('shipping_state', $prefill['shipping']['state']) }}" data-state-input data-placeholder-intl="{{ __('ui.account.fields.enter_region') }}" style="display:none;">
                            <span><i class="fa fa-chevron-down"></i></span>
                        </div>
                        <div class="input-style has-borders no-icon input-style-always-active mb-0">
                            <label for="shipping-country" class="color-highlight">{{ __('ui.account.fields.country_code') }}</label>
                            <select id="shipping-country" name="shipping_country_code" data-address-country>
                                @foreach ($countryOptions as $countryOption)
                                    <option value="{{ $countryOption['code'] }}" @selected(old('shipping_country_code', $prefill['shipping']['country_code']) === $countryOption['code'])>{{ $countryOption['label'] }}</option>
                                @endforeach
                            </select>
                            <span><i class="fa fa-chevron-down"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-style">
            <div class="content">
                <p class="font-600 color-highlight mb-n1">{{ __('ui.checkout.sections.shipping_payment') }}</p>
                <h3>{{ __('ui.checkout.labels.shipping_method') }}</h3>

                <div data-checkout-shipping-options>
                    @foreach ($shippingMethods as $method)
                        <label class="d-flex align-items-center justify-content-between border rounded-sm px-3 py-2 mb-2">
                            <span><input type="radio" name="shipping_method_code" value="{{ $method->code }}" @checked($selectedShippingCode === (string) $method->code) required> {{ $method->name }}</span>
                            <span>{{ \App\Support\Currency::format((float) $method->price) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card card-style mt-n2">
            <div class="content">
                <h3>{{ __('ui.checkout.labels.payment_method') }}</h3>

                <div data-checkout-payment-options>
                    @foreach ($paymentMethods as $method)
                        <label class="d-flex align-items-center justify-content-between border rounded-sm px-3 py-2 mb-2">
                            <span><input type="radio" name="payment_method_code" value="{{ $method->code }}" @checked($selectedPaymentCode === (string) $method->code) required> {{ $method->name }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="input-style has-borders input-style-always-active no-icon mb-3 mt-3">
                    <textarea id="customer-note" name="customer_note" style="height:120px;">{{ old('customer_note') }}</textarea>
                    <label for="customer-note" class="color-highlight">{{ __('ui.checkout.fields.order_note') }}</label>
                </div>

                <label class="font-12 d-block mb-3"><input type="checkbox" name="newsletter_opt_in" value="1" @checked((bool) old('newsletter_opt_in', $prefill['newsletter_opt_in'] ?? false))> {{ __('ui.checkout.options.newsletter_opt_in') }}</label>
                <label class="font-12 d-block mb-3"><input type="checkbox" name="accept_terms" value="1" required> {{ __('ui.checkout.options.accept_terms') }}</label>

                <button type="submit" class="btn btn-margins btn-full gradient-blue font-13 btn-l font-600 rounded-sm">{{ __('ui.checkout.actions.place_order') }}</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script defer src="{{ asset('front-theme/scripts/address-autofill.js') }}?v={{ filemtime(public_path('front-theme/scripts/address-autofill.js')) }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form[data-checkout-options-url]');
            const toggle = document.querySelector('[data-ship-to-different]');
            const shippingFields = document.querySelector('[data-shipping-fields]');
            const useBillingInput = document.querySelector('[data-use-billing-for-shipping]');
            const optionsUrl = form?.dataset.checkoutOptionsUrl || '';
            const regionOptionsByCountry = form?.dataset.regionOptions ? JSON.parse(form.dataset.regionOptions) : {};
            const shippingOptionsRoot = form?.querySelector('[data-checkout-shipping-options]');
            const paymentOptionsRoot = form?.querySelector('[data-checkout-payment-options]');
            let refreshTimer = null;
            let optionsAbortController = null;

            if (!toggle || !shippingFields || !useBillingInput) {
                return;
            }

            const setState = function () {
                if (toggle.checked) {
                    useBillingInput.value = '0';
                    shippingFields.style.maxHeight = shippingFields.scrollHeight + 'px';
                    shippingFields.style.opacity = '1';
                } else {
                    useBillingInput.value = '1';
                    shippingFields.style.maxHeight = '0';
                    shippingFields.style.opacity = '0';
                }

                scheduleOptionsRefresh();
            };

            const escapeHtml = function (value) {
                return String(value || '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            };

            const applyStateFieldMode = function (scope) {
                const countrySelect = scope.querySelector('[data-address-country]');
                const label = scope.querySelector('[data-state-label]');
                const select = scope.querySelector('[data-state-select]');
                const input = scope.querySelector('[data-state-input]');
                if (!countrySelect || !label || !select || !input) {
                    return;
                }

                const stateFieldName = select.dataset.stateName || select.getAttribute('name') || input.getAttribute('name') || 'state';
                select.dataset.stateName = stateFieldName;

                const countryCode = String(countrySelect.value || '').toUpperCase();
                const regions = Array.isArray(regionOptionsByCountry[countryCode]) ? regionOptionsByCountry[countryCode] : [];
                const hasRegions = regions.length > 0;
                const optionLabel = countryCode === 'HR'
                    ? (select.dataset.optionHr || '')
                    : (select.dataset.optionIntl || select.dataset.optionHr || '');

                label.textContent = countryCode === 'HR'
                    ? (label.dataset.labelHr || label.textContent)
                    : (label.dataset.labelIntl || label.textContent);

                if (hasRegions) {
                    const previousValue = select.value || input.value || '';
                    const options = ['<option value="">' + escapeHtml(optionLabel) + '</option>']
                        .concat(regions.map(function (region) {
                            const regionName = String(region?.name || '');
                            const selected = previousValue !== '' && previousValue === regionName ? ' selected' : '';
                            return '<option value="' + escapeHtml(regionName) + '"' + selected + '>' + escapeHtml(regionName) + '</option>';
                        }));
                    select.innerHTML = options.join('');

                    select.style.display = '';
                    select.disabled = false;
                    select.setAttribute('name', stateFieldName);
                    input.style.display = 'none';
                    input.disabled = true;
                    input.removeAttribute('name');
                } else {
                    if (!input.value && select.value) {
                        input.value = select.value;
                    }
                    input.style.display = '';
                    input.disabled = false;
                    input.setAttribute('name', stateFieldName);
                    input.placeholder = input.dataset.placeholderIntl || '';
                    select.style.display = 'none';
                    select.disabled = true;
                    select.removeAttribute('name');
                }
            };

            const applyAllStateModes = function () {
                form?.querySelectorAll('[data-address-scope]').forEach(function (scope) {
                    applyStateFieldMode(scope);
                });
            };

            const renderShippingOptions = function (methods) {
                if (!shippingOptionsRoot) {
                    return;
                }

                const selected = shippingOptionsRoot.querySelector('input[name="shipping_method_code"]:checked')?.value || '';
                if (!Array.isArray(methods) || methods.length === 0) {
                    shippingOptionsRoot.innerHTML = '<div class="font-12 color-red-dark">{{ __('ui.checkout.labels.no_shipping_methods') }}</div>';
                    return;
                }

                shippingOptionsRoot.innerHTML = methods.map(function (method, index) {
                    const checked = (selected !== '' && selected === method.code) || (selected === '' && index === 0);
                    return '<label class="d-flex align-items-center justify-content-between border rounded-sm px-3 py-2 mb-2">'
                        + '<span><input type="radio" name="shipping_method_code" value="' + String(method.code || '') + '" ' + (checked ? 'checked' : '') + ' required> ' + String(method.name || '') + '</span>'
                        + '<span>' + String(method.price_formatted || '') + '</span>'
                        + '</label>';
                }).join('');
            };

            const renderPaymentOptions = function (methods) {
                if (!paymentOptionsRoot) {
                    return;
                }

                const selected = paymentOptionsRoot.querySelector('input[name="payment_method_code"]:checked')?.value || '';
                if (!Array.isArray(methods) || methods.length === 0) {
                    paymentOptionsRoot.innerHTML = '<div class="font-12 color-red-dark">{{ __('ui.checkout.labels.no_payment_methods') }}</div>';
                    return;
                }

                paymentOptionsRoot.innerHTML = methods.map(function (method, index) {
                    const checked = (selected !== '' && selected === method.code) || (selected === '' && index === 0);
                    return '<label class="d-flex align-items-center justify-content-between border rounded-sm px-3 py-2 mb-2">'
                        + '<span><input type="radio" name="payment_method_code" value="' + String(method.code || '') + '" ' + (checked ? 'checked' : '') + ' required> ' + String(method.name || '') + '</span>'
                        + '</label>';
                }).join('');
            };

            const refreshOptions = async function () {
                if (!form || !optionsUrl) {
                    return;
                }

                if (optionsAbortController) {
                    optionsAbortController.abort();
                }
                optionsAbortController = new AbortController();

                const shipDifferent = !!toggle.checked;
                const params = new URLSearchParams({
                    ship_to_different_address: shipDifferent ? '1' : '0',
                    billing_country_code: form.querySelector('[name="billing_country_code"]')?.value || '',
                    billing_state: form.querySelector('[name="billing_state"]')?.value || '',
                    billing_postal_code: form.querySelector('[name="billing_postal_code"]')?.value || '',
                    shipping_country_code: form.querySelector('[name="shipping_country_code"]')?.value || '',
                    shipping_state: form.querySelector('[name="shipping_state"]')?.value || '',
                    shipping_postal_code: form.querySelector('[name="shipping_postal_code"]')?.value || '',
                });

                try {
                    const response = await fetch(optionsUrl + '?' + params.toString(), {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: optionsAbortController.signal,
                    });

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    renderShippingOptions(payload.shipping_methods || []);
                    renderPaymentOptions(payload.payment_methods || []);
                } catch (error) {
                    // Keep current options on request errors.
                }
            };

            const scheduleOptionsRefresh = function () {
                if (refreshTimer) {
                    clearTimeout(refreshTimer);
                }
                refreshTimer = setTimeout(refreshOptions, 200);
            };

            setState();
            applyAllStateModes();
            scheduleOptionsRefresh();
            toggle.addEventListener('change', setState);
            form?.querySelectorAll('[data-address-country], [data-state-select], [data-state-input], [name="billing_postal_code"], [name="shipping_postal_code"]').forEach(function (node) {
                node.addEventListener('change', function () {
                    applyAllStateModes();
                    scheduleOptionsRefresh();
                });
                node.addEventListener('input', function () {
                    scheduleOptionsRefresh();
                });
            });
            window.addEventListener('resize', function () {
                if (toggle.checked) {
                    shippingFields.style.maxHeight = shippingFields.scrollHeight + 'px';
                }
            });
        });
    </script>
@endpush

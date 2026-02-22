@extends('front.desktop.layouts.store')

@section('title', __('ui.checkout.page_title'))

@section('content')
    @php
        $showShippingAddress = old('ship_to_different_address') === '1' || old('use_billing_for_shipping') === '0';
        $selectedShippingCode = (string) old('shipping_method_code', (string) ($shippingMethods->first()?->code ?? ''));
        $selectedPaymentCode = (string) old('payment_method_code', (string) ($paymentMethods->first()?->code ?? ''));
        $showLoginForm = old('checkout_login') === '1';
        $showRegisterPanel = old('register_account') === '1';
        $showR1Fields = old('want_r1_invoice') === '1'
            || old('billing_company', $prefill['billing']['company']) !== ''
            || old('billing_oib', $prefill['billing']['oib']) !== '';
    @endphp

    <section class="mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ __('ui.checkout.title') }}</h1>
        <p class="mt-2 text-slate-600">{{ __('ui.checkout.subtitle') }}</p>
    </section>

    @guest
        <section class="mb-6 border border-slate-200 bg-white p-6">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-0" data-checkout-login-toggle @checked($showLoginForm)>
                {{ __('ui.checkout.login.toggle') }}
            </label>

            <div class="overflow-hidden transition-all duration-300" data-checkout-login-panel style="{{ $showLoginForm ? '' : 'max-height:0;opacity:0;' }}">
                <div class="mt-4 border-t border-slate-200 pt-4">
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('ui.checkout.login.title') }}</h2>
                    <form method="POST" action="{{ route('login') }}" class="mt-3 grid gap-3 md:grid-cols-2" novalidate>
                        @csrf
                        <input type="hidden" name="checkout_login" value="1">
                        <input type="hidden" name="intended" value="{{ route('checkout.create') }}">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.checkout.login.email') }}</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.checkout.login.password') }}</label>
                            <input type="password" name="password" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required>
                        </div>
                        <div class="md:col-span-2 flex items-center justify-between gap-3">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="remember" class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-0">
                                {{ __('ui.checkout.login.remember') }}
                            </label>
                            <button type="submit" class="border border-slate-900 bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">{{ __('ui.checkout.login.submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    @endguest

    <form method="POST" action="{{ route('checkout.store') }}" class="grid items-start gap-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(460px,1fr)]" data-address-autofill data-address-source="{{ $placesAssetUrl }}" data-checkout-form novalidate>
        @csrf

        <div class="space-y-6">
            <section class="border border-slate-200 bg-white p-6" data-address-scope="billing">
                <h2 class="text-xl font-bold text-slate-900">{{ __('ui.checkout.sections.customer') }} / {{ __('ui.checkout.sections.billing') }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ __('ui.checkout.sections.basic_information') }}</p>

                <input type="hidden" name="customer_first_name" value="{{ old('customer_first_name', old('billing_first_name', $prefill['billing']['first_name'])) }}" data-customer-first-hidden>
                <input type="hidden" name="customer_last_name" value="{{ old('customer_last_name', old('billing_last_name', $prefill['billing']['last_name'])) }}" data-customer-last-hidden>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.first_name') }}</label>
                        <input type="text" name="billing_first_name" value="{{ old('billing_first_name', $prefill['billing']['first_name']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-billing-first required>
                        @error('billing_first_name')
                            <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.last_name') }}</label>
                        <input type="text" name="billing_last_name" value="{{ old('billing_last_name', $prefill['billing']['last_name']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-billing-last required>
                        @error('billing_last_name')
                            <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.email') }}</label>
                        <input type="email" name="customer_email" value="{{ old('customer_email', $prefill['email']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required>
                        @error('customer_email')
                            <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.phone') }}</label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone', $prefill['phone']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required>
                        @error('customer_phone')
                            <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="want_r1_invoice" value="1" class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-0" data-r1-toggle @checked($showR1Fields)>
                            {{ __('ui.checkout.options.r1_invoice') }}
                        </label>
                    </div>
                    <div class="md:col-span-2 overflow-hidden transition-all duration-300" data-r1-panel style="{{ $showR1Fields ? '' : 'max-height:0;opacity:0;' }}">
                        <div class="grid gap-4 border-t border-slate-200 pt-4 md:grid-cols-2">
                            <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.company') }}</label><input type="text" name="billing_company" value="{{ old('billing_company', $prefill['billing']['company']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-r1-company></div>
                            <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.oib') }}</label><input type="text" name="billing_oib" value="{{ old('billing_oib', $prefill['billing']['oib']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-r1-oib></div>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.address_line_1') }}</label>
                        <input type="text" name="billing_address_line_1" value="{{ old('billing_address_line_1', $prefill['billing']['address_line_1']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required>
                        @error('billing_address_line_1')
                            <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.postal_code') }}</label>
                        <input type="text" name="billing_postal_code" value="{{ old('billing_postal_code', $prefill['billing']['postal_code']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-postal required>
                        @error('billing_postal_code')
                            <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.city') }}</label>
                        <input type="text" name="billing_city" value="{{ old('billing_city', $prefill['billing']['city']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-city required>
                        @error('billing_city')
                            <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.state') }}</label>
                        <select name="billing_state" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-county>
                            <option value="">{{ __('ui.account.fields.select_county') }}</option>
                            @foreach ($countyOptions as $countyOption)
                                <option value="{{ $countyOption }}" @selected(old('billing_state', $prefill['billing']['state']) === $countyOption)>{{ $countyOption }}</option>
                            @endforeach
                        </select>
                        @error('billing_state')
                            <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.country_code') }}</label>
                        <select name="billing_country_code" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-country required>
                            @foreach ($countryOptions as $countryOption)
                                <option value="{{ $countryOption['code'] }}" @selected(old('billing_country_code', $prefill['billing']['country_code']) === $countryOption['code'])>{{ $countryOption['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @guest
                    <div class="mt-4 border-t border-slate-200 pt-4">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="register_account" value="1" class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-0" data-register-account-toggle @checked($showRegisterPanel)>
                            {{ __('ui.checkout.options.register_account') }}
                        </label>

                        <div class="overflow-hidden transition-all duration-300" data-register-account-panel style="{{ $showRegisterPanel ? '' : 'max-height:0;opacity:0;' }}">
                            <div class="mt-4 grid gap-4 border-t border-slate-200 pt-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.checkout.register.password') }}</label>
                                    <input type="password" name="register_password" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-register-password>
                                    @error('register_password')
                                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.checkout.register.password_repeat') }}</label>
                                    <input type="password" name="register_password_confirmation" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-register-password-confirmation>
                                </div>
                            </div>
                        </div>
                    </div>
                @endguest
            </section>

            <section class="border border-slate-200 bg-white p-6" data-address-scope="shipping">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-xl font-bold text-slate-900">{{ __('ui.checkout.sections.shipping') }}</h2>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="ship_to_different_address" value="1" @checked($showShippingAddress) class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-0" data-ship-to-different>
                        {{ __('ui.checkout.options.ship_to_different_address') }}
                    </label>
                </div>

                <input type="hidden" name="use_billing_for_shipping" value="{{ $showShippingAddress ? '0' : '1' }}" data-use-billing-for-shipping>

                <div class="overflow-hidden transition-all duration-300" data-shipping-fields style="{{ $showShippingAddress ? '' : 'max-height:0;opacity:0;' }}">
                    <div class="mt-4 grid gap-4 border-t border-slate-200 pt-4 md:grid-cols-2">
                        <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.first_name') }}</label><input type="text" name="shipping_first_name" value="{{ old('shipping_first_name', $prefill['shipping']['first_name']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                        <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.last_name') }}</label><input type="text" name="shipping_last_name" value="{{ old('shipping_last_name', $prefill['shipping']['last_name']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                        <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.company') }}</label><input type="text" name="shipping_company" value="{{ old('shipping_company', $prefill['shipping']['company']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                        <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.vat_id') }}</label><input type="text" name="shipping_vat_id" value="{{ old('shipping_vat_id', $prefill['shipping']['vat_id']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                        <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.address_line_1') }}</label><input type="text" name="shipping_address_line_1" value="{{ old('shipping_address_line_1', $prefill['shipping']['address_line_1']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                        <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.postal_code') }}</label><input type="text" name="shipping_postal_code" value="{{ old('shipping_postal_code', $prefill['shipping']['postal_code']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-postal></div>
                        <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.city') }}</label><input type="text" name="shipping_city" value="{{ old('shipping_city', $prefill['shipping']['city']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-city></div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.state') }}</label>
                            <select name="shipping_state" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-county>
                                <option value="">{{ __('ui.account.fields.select_county') }}</option>
                                @foreach ($countyOptions as $countyOption)
                                    <option value="{{ $countyOption }}" @selected(old('shipping_state', $prefill['shipping']['state']) === $countyOption)>{{ $countyOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.country_code') }}</label>
                            <select name="shipping_country_code" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-country>
                                @foreach ($countryOptions as $countryOption)
                                    <option value="{{ $countryOption['code'] }}" @selected(old('shipping_country_code', $prefill['shipping']['country_code']) === $countryOption['code'])>{{ $countryOption['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border border-slate-200 bg-white p-6">
                <h2 class="text-xl font-bold text-slate-900">{{ __('ui.checkout.sections.shipping_payment') }}</h2>

                <div class="mt-4 space-y-5">
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.checkout.labels.shipping_method') }}</p>
                        <div class="grid gap-2">
                            @foreach ($shippingMethods as $method)
                                <label class="flex cursor-pointer items-center justify-between gap-3 border border-slate-300 px-3 py-2.5 text-sm hover:border-slate-500">
                                    <span class="inline-flex items-center gap-2">
                                        <input type="radio" name="shipping_method_code" value="{{ $method->code }}" @checked($selectedShippingCode === (string) $method->code) class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-0" required>
                                        <span class="font-semibold text-slate-900">{{ $method->name }}</span>
                                    </span>
                                    <span class="text-slate-600">{{ \App\Support\Currency::format((float) $method->price) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.checkout.labels.payment_method') }}</p>
                        <div class="grid gap-2">
                            @foreach ($paymentMethods as $method)
                                <label class="flex cursor-pointer items-center justify-between gap-3 border border-slate-300 px-3 py-2.5 text-sm hover:border-slate-500">
                                    <span class="inline-flex items-center gap-2">
                                        <input type="radio" name="payment_method_code" value="{{ $method->code }}" @checked($selectedPaymentCode === (string) $method->code) class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-0" required>
                                        <span class="font-semibold text-slate-900">{{ $method->name }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.checkout.fields.order_note') }}</label>
                    <textarea name="customer_note" rows="3" class="w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0">{{ old('customer_note') }}</textarea>
                </div>

                <label class="mt-4 inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="newsletter_opt_in" value="1" class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-0" @checked((bool) old('newsletter_opt_in', $prefill['newsletter_opt_in'] ?? false))>
                    {{ __('ui.checkout.options.newsletter_opt_in') }}
                </label>

                <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="accept_terms" value="1" class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-0" required>
                    {{ __('ui.checkout.options.accept_terms') }}
                </label>
                @error('accept_terms')
                    <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </section>
        </div>

        <aside class="h-fit self-start border border-slate-200 bg-white p-6 shadow-sm xl:sticky xl:top-28">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('ui.checkout.summary_title') }}</h2>

            <div class="mt-4 space-y-3">
                @foreach ($lines as $line)
                    @php
                        $translation = $line['translation'];
                        $product = $line['product'];
                        $productImage = $product->getFirstMedia('product_main') ?? $product->getFirstMedia('product_gallery');
                        $productImageUrl = $productImage
                            ? ($productImage->hasGeneratedConversion('thumb_100x100') ? $productImage->getUrl('thumb_100x100') : $productImage->getUrl())
                            : null;
                    @endphp
                    <div class="flex items-start gap-3 border border-slate-200 p-3">
                        <div class="w-16 shrink-0 border border-slate-200 bg-slate-50 p-1">
                            @if ($productImageUrl)
                                <img
                                    src="{{ $productImageUrl }}"
                                    alt="{{ $translation?->name ?? $line['product']->code }}"
                                    class="h-auto w-full"
                                    loading="lazy"
                                    decoding="async"
                                >
                            @else
                                <span class="flex h-full w-full items-center justify-center text-[10px] font-semibold uppercase text-slate-500">{{ __('ui.product.no_image') }}</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-slate-900">{{ $translation?->name ?? $line['product']->code }}</div>
                            @if (!empty($line['sku']))
                                <div class="mt-0.5 text-xs text-slate-500">{{ __('ui.checkout.labels.sku') }}: {{ $line['sku'] }}</div>
                            @endif
                            @if (!empty($line['option_label']))
                                <div class="mt-0.5 text-xs text-slate-500">{{ $line['option_label'] }}</div>
                            @endif
                            <div class="mt-1 flex items-center justify-between text-sm">
                                <span class="text-slate-600">{{ __('ui.checkout.labels.qty') }} {{ $line['quantity'] }}</span>
                                <span class="font-semibold text-slate-900">{{ \App\Support\Currency::format((float) ($line['display_line_total'] ?? $line['line_total'])) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 border-t border-slate-200 pt-4 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">{{ __('ui.checkout.labels.items') }}</span>
                    <span class="font-semibold text-slate-900">{{ $summary['item_qty'] }}</span>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-slate-600">{{ __('ui.checkout.labels.subtotal') }}</span>
                    <span class="font-semibold text-slate-900">{{ \App\Support\Currency::format((float) $summary['subtotal']) }}</span>
                </div>
                @if ((float) ($summary['discount_total'] ?? 0) > 0)
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-slate-600">{{ __('ui.checkout.labels.discount') }}</span>
                        <span class="font-semibold text-emerald-700">-{{ \App\Support\Currency::format((float) $summary['discount_total']) }}</span>
                    </div>
                @endif
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-slate-600">{{ __('ui.checkout.labels.tax') }}</span>
                    <span class="font-semibold text-slate-900">{{ \App\Support\Currency::format((float) ($summary['tax_total'] ?? 0)) }}</span>
                </div>
                <div class="mt-2 flex items-center justify-between border-t border-slate-200 pt-2">
                    <span class="text-slate-900">{{ __('ui.checkout.labels.total') }}</span>
                    <span class="font-bold text-slate-900">{{ \App\Support\Currency::format((float) ($summary['grand_total'] ?? 0)) }}</span>
                </div>
            </div>

            <button type="submit" class="mt-5 w-full border border-slate-900 bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">{{ __('ui.checkout.actions.place_order') }}</button>
        </aside>
    </form>
@endsection

@push('scripts')
    <script defer src="{{ asset('front-theme/scripts/address-autofill.js') }}?v={{ filemtime(public_path('front-theme/scripts/address-autofill.js')) }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkoutForm = document.querySelector('[data-checkout-form]');
            const toggle = document.querySelector('[data-ship-to-different]');
            const shippingFields = document.querySelector('[data-shipping-fields]');
            const useBillingInput = document.querySelector('[data-use-billing-for-shipping]');
            const billingFirst = document.querySelector('[data-billing-first]');
            const billingLast = document.querySelector('[data-billing-last]');
            const customerFirstHidden = document.querySelector('[data-customer-first-hidden]');
            const customerLastHidden = document.querySelector('[data-customer-last-hidden]');
            const loginToggle = document.querySelector('[data-checkout-login-toggle]');
            const loginPanel = document.querySelector('[data-checkout-login-panel]');
            const registerToggle = document.querySelector('[data-register-account-toggle]');
            const registerPanel = document.querySelector('[data-register-account-panel]');
            const registerPassword = document.querySelector('[data-register-password]');
            const registerPasswordConfirmation = document.querySelector('[data-register-password-confirmation]');
            const r1Toggle = document.querySelector('[data-r1-toggle]');
            const r1Panel = document.querySelector('[data-r1-panel]');
            const r1Company = document.querySelector('[data-r1-company]');
            const r1Oib = document.querySelector('[data-r1-oib]');

            const syncCustomerNames = function () {
                if (!billingFirst || !billingLast || !customerFirstHidden || !customerLastHidden) {
                    return;
                }

                customerFirstHidden.value = billingFirst.value;
                customerLastHidden.value = billingLast.value;
            };

            const setShippingState = function () {
                if (!toggle || !shippingFields || !useBillingInput) {
                    return;
                }

                if (toggle.checked) {
                    useBillingInput.value = '0';
                    shippingFields.style.maxHeight = shippingFields.scrollHeight + 'px';
                    shippingFields.style.opacity = '1';
                } else {
                    useBillingInput.value = '1';
                    shippingFields.style.maxHeight = '0';
                    shippingFields.style.opacity = '0';
                }
            };

            const setLoginState = function () {
                if (!loginToggle || !loginPanel) {
                    return;
                }

                if (loginToggle.checked) {
                    loginPanel.style.maxHeight = loginPanel.scrollHeight + 'px';
                    loginPanel.style.opacity = '1';
                } else {
                    loginPanel.style.maxHeight = '0';
                    loginPanel.style.opacity = '0';
                }
            };

            const setRegisterState = function () {
                if (!registerToggle || !registerPanel || !registerPassword || !registerPasswordConfirmation) {
                    return;
                }

                if (registerToggle.checked) {
                    registerPassword.required = true;
                    registerPasswordConfirmation.required = true;
                    registerPanel.style.maxHeight = registerPanel.scrollHeight + 'px';
                    registerPanel.style.opacity = '1';
                } else {
                    registerPassword.required = false;
                    registerPasswordConfirmation.required = false;
                    registerPanel.style.maxHeight = '0';
                    registerPanel.style.opacity = '0';
                }
            };

            const setR1State = function () {
                if (!r1Toggle || !r1Panel || !r1Company || !r1Oib) {
                    return;
                }

                if (r1Toggle.checked) {
                    r1Company.disabled = false;
                    r1Oib.disabled = false;
                    r1Panel.style.maxHeight = r1Panel.scrollHeight + 'px';
                    r1Panel.style.opacity = '1';
                } else {
                    r1Company.disabled = true;
                    r1Oib.disabled = true;
                    r1Panel.style.maxHeight = '0';
                    r1Panel.style.opacity = '0';
                }
            };

            const clearInlineErrors = function () {
                document.querySelectorAll('[data-checkout-error]').forEach(function (node) {
                    node.remove();
                });
            };

            const renderInlineError = function (field, message) {
                if (!checkoutForm) {
                    return;
                }

                const input = checkoutForm.querySelector('[name="' + field + '"]');
                if (!input || input.type === 'hidden') {
                    return;
                }

                const errorNode = document.createElement('p');
                errorNode.className = 'mt-2 text-xs font-semibold text-rose-600';
                errorNode.dataset.checkoutError = '1';
                errorNode.textContent = message;

                if (input.type === 'checkbox' || input.type === 'radio') {
                    const label = input.closest('label');
                    if (label && label.parentElement) {
                        label.parentElement.appendChild(errorNode);
                        return;
                    }
                }

                input.insertAdjacentElement('afterend', errorNode);
            };

            syncCustomerNames();
            setShippingState();
            setLoginState();
            setRegisterState();
            setR1State();

            toggle?.addEventListener('change', setShippingState);
            loginToggle?.addEventListener('change', setLoginState);
            registerToggle?.addEventListener('change', setRegisterState);
            r1Toggle?.addEventListener('change', setR1State);
            billingFirst?.addEventListener('input', syncCustomerNames);
            billingLast?.addEventListener('input', syncCustomerNames);

            window.addEventListener('resize', function () {
                if (toggle?.checked && shippingFields) {
                    shippingFields.style.maxHeight = shippingFields.scrollHeight + 'px';
                }
                if (loginToggle?.checked && loginPanel) {
                    loginPanel.style.maxHeight = loginPanel.scrollHeight + 'px';
                }
                if (registerToggle?.checked && registerPanel) {
                    registerPanel.style.maxHeight = registerPanel.scrollHeight + 'px';
                }
                if (r1Toggle?.checked && r1Panel) {
                    r1Panel.style.maxHeight = r1Panel.scrollHeight + 'px';
                }
            });

            checkoutForm?.addEventListener('submit', async function (event) {
                event.preventDefault();
                clearInlineErrors();

                const submitBtn = checkoutForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                }

                try {
                    const response = await fetch(checkoutForm.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(checkoutForm),
                    });

                    if (response.status === 422) {
                        const payload = await response.json();
                        const errors = payload.errors || {};
                        Object.keys(errors).forEach(function (field) {
                            const firstMessage = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                            if (firstMessage) {
                                renderInlineError(field, firstMessage);
                            }
                        });
                        return;
                    }

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    if (payload.redirect) {
                        window.location.href = payload.redirect;
                    }
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                }
            });
        });
    </script>
@endpush

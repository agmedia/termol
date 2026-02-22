@extends('front.desktop.layouts.store')

@section('title', 'Checkout')

@section('content')
    <section class="mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Checkout</h1>
        <p class="mt-2 text-slate-600">Complete billing and shipping details to place the order.</p>
    </section>

    <form method="POST" action="{{ route('checkout.store') }}" class="grid gap-8 lg:grid-cols-[1fr_320px]" data-address-autofill data-address-source="{{ $placesAssetUrl }}">
        @csrf

        <div class="space-y-6">
            <section class="border border-slate-200 bg-white p-6">
                <h2 class="text-xl font-bold text-slate-900">Customer</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">First name</label>
                        <input type="text" name="customer_first_name" value="{{ old('customer_first_name', $prefill['first_name']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Last name</label>
                        <input type="text" name="customer_last_name" value="{{ old('customer_last_name', $prefill['last_name']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Email</label>
                        <input type="email" name="customer_email" value="{{ old('customer_email', $prefill['email']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone', $prefill['phone']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0">
                    </div>
                </div>
            </section>

            <section class="border border-slate-200 bg-white p-6" data-address-scope="billing">
                <h2 class="text-xl font-bold text-slate-900">Billing address</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">First name</label><input type="text" name="billing_first_name" value="{{ old('billing_first_name', $prefill['billing']['first_name']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Last name</label><input type="text" name="billing_last_name" value="{{ old('billing_last_name', $prefill['billing']['last_name']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Company</label><input type="text" name="billing_company" value="{{ old('billing_company', $prefill['billing']['company']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">OIB</label><input type="text" name="billing_oib" value="{{ old('billing_oib', $prefill['billing']['oib']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                    <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Address</label><input type="text" name="billing_address_line_1" value="{{ old('billing_address_line_1', $prefill['billing']['address_line_1']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Postal code</label><input type="text" name="billing_postal_code" value="{{ old('billing_postal_code', $prefill['billing']['postal_code']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-postal required></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">City</label><input type="text" name="billing_city" value="{{ old('billing_city', $prefill['billing']['city']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-city required></div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">County</label>
                        <select name="billing_state" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-county>
                            <option value="">Select county</option>
                            @foreach ($countyOptions as $countyOption)
                                <option value="{{ $countyOption }}" @selected(old('billing_state', $prefill['billing']['state']) === $countyOption)>{{ $countyOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Country</label>
                        <select name="billing_country_code" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-country required>
                            @foreach ($countryOptions as $countryOption)
                                <option value="{{ $countryOption['code'] }}" @selected(old('billing_country_code', $prefill['billing']['country_code']) === $countryOption['code'])>{{ $countryOption['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <section class="border border-slate-200 bg-white p-6" data-address-scope="shipping">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-xl font-bold text-slate-900">Shipping address</h2>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="use_billing_for_shipping" value="1" @checked(old('use_billing_for_shipping')) class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-0">
                        Same as billing
                    </label>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">First name</label><input type="text" name="shipping_first_name" value="{{ old('shipping_first_name', $prefill['shipping']['first_name']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Last name</label><input type="text" name="shipping_last_name" value="{{ old('shipping_last_name', $prefill['shipping']['last_name']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Company</label><input type="text" name="shipping_company" value="{{ old('shipping_company', $prefill['shipping']['company']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">VAT ID</label><input type="text" name="shipping_vat_id" value="{{ old('shipping_vat_id', $prefill['shipping']['vat_id']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                    <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Address</label><input type="text" name="shipping_address_line_1" value="{{ old('shipping_address_line_1', $prefill['shipping']['address_line_1']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Postal code</label><input type="text" name="shipping_postal_code" value="{{ old('shipping_postal_code', $prefill['shipping']['postal_code']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-postal></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">City</label><input type="text" name="shipping_city" value="{{ old('shipping_city', $prefill['shipping']['city']) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-city></div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">County</label>
                        <select name="shipping_state" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-county>
                            <option value="">Select county</option>
                            @foreach ($countyOptions as $countyOption)
                                <option value="{{ $countyOption }}" @selected(old('shipping_state', $prefill['shipping']['state']) === $countyOption)>{{ $countyOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Country</label>
                        <select name="shipping_country_code" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-country>
                            @foreach ($countryOptions as $countryOption)
                                <option value="{{ $countryOption['code'] }}" @selected(old('shipping_country_code', $prefill['shipping']['country_code']) === $countryOption['code'])>{{ $countryOption['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <section class="border border-slate-200 bg-white p-6">
                <h2 class="text-xl font-bold text-slate-900">Shipping and payment</h2>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Shipping method</label>
                        <select name="shipping_method_code" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required>
                            @foreach ($shippingMethods as $method)
                                <option value="{{ $method->code }}" @selected(old('shipping_method_code') === $method->code)>
                                    {{ $method->name }} ({{ \App\Support\Currency::format((float) $method->price) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Payment method</label>
                        <select name="payment_method_code" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required>
                            @foreach ($paymentMethods as $method)
                                <option value="{{ $method->code }}" @selected(old('payment_method_code') === $method->code)>
                                    {{ $method->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Order note</label>
                    <textarea name="customer_note" rows="3" class="w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0">{{ old('customer_note') }}</textarea>
                </div>

                <label class="mt-4 inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="accept_terms" value="1" class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-0" required>
                    I agree to checkout terms.
                </label>
            </section>
        </div>

        <aside class="border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Order summary</h2>
            <div class="mt-4 overflow-x-auto border border-slate-200">
                <table class="min-w-[520px] w-full text-sm">
                    <thead class="bg-slate-100/70 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Product</th>
                        <th class="px-3 py-2">Qty</th>
                        <th class="px-3 py-2">Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($lines as $line)
                        @php
                            $translation = $line['translation'];
                        @endphp
                        <tr class="border-t border-slate-200">
                            <td class="px-3 py-2 align-top">
                                <div class="font-medium text-slate-900">{{ $translation?->name ?? $line['product']->code }}</div>
                                @if (!empty($line['sku']))
                                    <div class="mt-0.5 text-xs text-slate-500">SKU: {{ $line['sku'] }}</div>
                                @endif
                                @if (!empty($line['option_label']))
                                    <div class="mt-0.5 text-xs text-slate-500">{{ $line['option_label'] }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 align-top">{{ $line['quantity'] }}</td>
                            <td class="px-3 py-2 align-top font-semibold">{{ \App\Support\Currency::format((float) ($line['display_line_total'] ?? $line['line_total'])) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 border-t border-slate-200 pt-4 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Items</span>
                    <span class="font-semibold text-slate-900">{{ $summary['item_qty'] }}</span>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-slate-600">Subtotal</span>
                    <span class="font-semibold text-slate-900">{{ \App\Support\Currency::format((float) $summary['subtotal']) }}</span>
                </div>
                @if ((float) ($summary['discount_total'] ?? 0) > 0)
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-slate-600">Discount</span>
                        <span class="font-semibold text-emerald-700">-{{ \App\Support\Currency::format((float) $summary['discount_total']) }}</span>
                    </div>
                @endif
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-slate-600">Tax</span>
                    <span class="font-semibold text-slate-900">{{ \App\Support\Currency::format((float) ($summary['tax_total'] ?? 0)) }}</span>
                </div>
                <div class="mt-2 flex items-center justify-between border-t border-slate-200 pt-2">
                    <span class="text-slate-900">Total</span>
                    <span class="font-bold text-slate-900">{{ \App\Support\Currency::format((float) ($summary['grand_total'] ?? 0)) }}</span>
                </div>
            </div>

            <button type="submit" class="mt-5 w-full border border-slate-900 bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Place order</button>
        </aside>
    </form>
@endsection

@push('scripts')
    <script defer src="{{ asset('front-theme/scripts/address-autofill.js') }}?v={{ filemtime(public_path('front-theme/scripts/address-autofill.js')) }}"></script>
@endpush

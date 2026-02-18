@extends('front.desktop.layouts.store')

@section('title', 'Checkout')

@section('content')
    <section class="mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Checkout</h1>
        <p class="mt-2 text-slate-600">Complete billing and shipping details to place the order.</p>
    </section>

    <form method="POST" action="{{ route('checkout.store') }}" class="grid gap-8 lg:grid-cols-[1fr_320px]">
        @csrf

        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-slate-900">Customer</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">First name</label>
                        <input type="text" name="customer_first_name" value="{{ old('customer_first_name', $prefill['first_name']) }}" class="w-full rounded-lg border-slate-300 text-sm" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Last name</label>
                        <input type="text" name="customer_last_name" value="{{ old('customer_last_name', $prefill['last_name']) }}" class="w-full rounded-lg border-slate-300 text-sm" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Email</label>
                        <input type="email" name="customer_email" value="{{ old('customer_email', $prefill['email']) }}" class="w-full rounded-lg border-slate-300 text-sm" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone', $prefill['phone']) }}" class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-slate-900">Billing address</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">First name</label><input type="text" name="billing_first_name" value="{{ old('billing_first_name', $prefill['billing']['first_name']) }}" class="w-full rounded-lg border-slate-300 text-sm" required></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Last name</label><input type="text" name="billing_last_name" value="{{ old('billing_last_name', $prefill['billing']['last_name']) }}" class="w-full rounded-lg border-slate-300 text-sm" required></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Company</label><input type="text" name="billing_company" value="{{ old('billing_company', $prefill['billing']['company']) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">OIB</label><input type="text" name="billing_oib" value="{{ old('billing_oib', $prefill['billing']['oib']) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Address line 1</label><input type="text" name="billing_address_line_1" value="{{ old('billing_address_line_1', $prefill['billing']['address_line_1']) }}" class="w-full rounded-lg border-slate-300 text-sm" required></div>
                    <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Address line 2</label><input type="text" name="billing_address_line_2" value="{{ old('billing_address_line_2', $prefill['billing']['address_line_2']) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Postal code</label><input type="text" name="billing_postal_code" value="{{ old('billing_postal_code', $prefill['billing']['postal_code']) }}" class="w-full rounded-lg border-slate-300 text-sm" required></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">City</label><input type="text" name="billing_city" value="{{ old('billing_city', $prefill['billing']['city']) }}" class="w-full rounded-lg border-slate-300 text-sm" required></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">State</label><input type="text" name="billing_state" value="{{ old('billing_state', $prefill['billing']['state']) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Country code</label><input type="text" name="billing_country_code" value="{{ old('billing_country_code', $prefill['billing']['country_code']) }}" class="w-full rounded-lg border-slate-300 text-sm" maxlength="2" required></div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-xl font-bold text-slate-900">Shipping address</h2>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="use_billing_for_shipping" value="1" @checked(old('use_billing_for_shipping')) class="rounded border-slate-300">
                        Same as billing
                    </label>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">First name</label><input type="text" name="shipping_first_name" value="{{ old('shipping_first_name', $prefill['shipping']['first_name']) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Last name</label><input type="text" name="shipping_last_name" value="{{ old('shipping_last_name', $prefill['shipping']['last_name']) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Company</label><input type="text" name="shipping_company" value="{{ old('shipping_company', $prefill['shipping']['company']) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">VAT ID</label><input type="text" name="shipping_vat_id" value="{{ old('shipping_vat_id', $prefill['shipping']['vat_id']) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Address line 1</label><input type="text" name="shipping_address_line_1" value="{{ old('shipping_address_line_1', $prefill['shipping']['address_line_1']) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Address line 2</label><input type="text" name="shipping_address_line_2" value="{{ old('shipping_address_line_2', $prefill['shipping']['address_line_2']) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Postal code</label><input type="text" name="shipping_postal_code" value="{{ old('shipping_postal_code', $prefill['shipping']['postal_code']) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">City</label><input type="text" name="shipping_city" value="{{ old('shipping_city', $prefill['shipping']['city']) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">State</label><input type="text" name="shipping_state" value="{{ old('shipping_state', $prefill['shipping']['state']) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Country code</label><input type="text" name="shipping_country_code" value="{{ old('shipping_country_code', $prefill['shipping']['country_code']) }}" class="w-full rounded-lg border-slate-300 text-sm" maxlength="2"></div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-slate-900">Shipping and payment</h2>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Shipping method</label>
                        <select name="shipping_method_code" class="w-full rounded-lg border-slate-300 text-sm" required>
                            @foreach ($shippingMethods as $method)
                                <option value="{{ $method->code }}" @selected(old('shipping_method_code') === $method->code)>
                                    {{ $method->name }} (EUR {{ number_format((float) $method->price, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Payment method</label>
                        <select name="payment_method_code" class="w-full rounded-lg border-slate-300 text-sm" required>
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
                    <textarea name="customer_note" rows="3" class="w-full rounded-lg border-slate-300 text-sm">{{ old('customer_note') }}</textarea>
                </div>

                <label class="mt-4 inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="accept_terms" value="1" class="rounded border-slate-300" required>
                    I agree to checkout terms.
                </label>
            </section>
        </div>

        <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Order summary</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-700">
                @foreach ($lines as $line)
                    @php
                        $translation = $line['translation'];
                    @endphp
                    <li class="flex items-start justify-between gap-3">
                        <span>{{ $translation?->name ?? $line['product']->code }} × {{ $line['quantity'] }}</span>
                        <span class="font-semibold">EUR {{ number_format((float) $line['line_total'], 2) }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 border-t border-slate-200 pt-4 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Items</span>
                    <span class="font-semibold text-slate-900">{{ $summary['item_qty'] }}</span>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-slate-600">Subtotal</span>
                    <span class="font-semibold text-slate-900">EUR {{ number_format((float) $summary['subtotal'], 2) }}</span>
                </div>
            </div>

            <button type="submit" class="mt-5 w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Place order</button>
        </aside>
    </form>
@endsection

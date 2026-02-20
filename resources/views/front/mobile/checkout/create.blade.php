@extends('front.mobile.layouts.store')

@section('title', 'Checkout')
@section('header_title', 'Checkout')
@section('page_title', 'Checkout')

@section('content')
    <form method="POST" action="{{ route('checkout.store') }}">
        @csrf

        <div class="card card-style">
            <div class="content">
                <p class="font-600 color-highlight mb-n1">Your Cart</p>
                <h3>Order Details</h3>
                @foreach ($lines as $line)
                    @php $translation = $line['translation']; @endphp
                    <div class="d-flex mb-3">
                        <div class="w-100">
                            <h6 class="font-500 font-14 pb-1">{{ $translation?->name ?? $line['product']->code }}</h6>
                            @if (!empty($line['sku']))
                                <p class="font-11 opacity-60 mb-1">SKU: {{ $line['sku'] }}</p>
                            @endif
                            @if (!empty($line['option_label']))
                                <p class="font-11 opacity-60 mb-1">{{ $line['option_label'] }}</p>
                            @endif
                            <p class="font-11 opacity-60 mb-1">Qty {{ $line['quantity'] }}</p>
                            <h4 class="font-700 mb-0">EUR {{ number_format((float) ($line['display_line_total'] ?? $line['line_total']), 2) }}</h4>
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
                        <p class="font-600 color-highlight mb-0 font-13">Total</p>
                        <h2>EUR {{ number_format((float) ($summary['grand_total'] ?? $summary['subtotal']), 2) }}</h2>
                    </div>
                    <div class="w-100 pt-1">
                        <h6 class="font-14 font-700">Items <span class="float-end color-theme">{{ $summary['item_qty'] }}</span></h6>
                        <div class="divider mb-2 mt-1"></div>
                        <h6 class="font-14 font-700">Lines <span class="float-end color-theme">{{ $summary['line_count'] }}</span></h6>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-style">
            <div class="content">
                <p class="font-600 color-highlight mb-n1">Customer</p>
                <h3>Basic Information</h3>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="customer-first" class="color-highlight">First name</label><input id="customer-first" type="text" name="customer_first_name" value="{{ old('customer_first_name', $prefill['first_name']) }}" required></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="customer-last" class="color-highlight">Last name</label><input id="customer-last" type="text" name="customer_last_name" value="{{ old('customer_last_name', $prefill['last_name']) }}" required></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="customer-email" class="color-highlight">Email</label><input id="customer-email" type="email" name="customer_email" value="{{ old('customer_email', $prefill['email']) }}" required></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-0"><label for="customer-phone" class="color-highlight">Phone</label><input id="customer-phone" type="text" name="customer_phone" value="{{ old('customer_phone', $prefill['phone']) }}"></div>
            </div>
        </div>

        <div class="card card-style">
            <div class="content">
                <p class="font-600 color-highlight mb-n1">Billing</p>
                <h3>Billing Address</h3>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="billing-first" class="color-highlight">First name</label><input id="billing-first" type="text" name="billing_first_name" value="{{ old('billing_first_name', $prefill['billing']['first_name']) }}" required></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="billing-last" class="color-highlight">Last name</label><input id="billing-last" type="text" name="billing_last_name" value="{{ old('billing_last_name', $prefill['billing']['last_name']) }}" required></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="billing-company" class="color-highlight">Company</label><input id="billing-company" type="text" name="billing_company" value="{{ old('billing_company', $prefill['billing']['company']) }}"></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="billing-oib" class="color-highlight">OIB</label><input id="billing-oib" type="text" name="billing_oib" value="{{ old('billing_oib', $prefill['billing']['oib']) }}"></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="billing-address1" class="color-highlight">Address line 1</label><input id="billing-address1" type="text" name="billing_address_line_1" value="{{ old('billing_address_line_1', $prefill['billing']['address_line_1']) }}" required></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="billing-address2" class="color-highlight">Address line 2</label><input id="billing-address2" type="text" name="billing_address_line_2" value="{{ old('billing_address_line_2', $prefill['billing']['address_line_2']) }}"></div>
                <div class="row">
                    <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="billing-postal" class="color-highlight">Postal</label><input id="billing-postal" type="text" name="billing_postal_code" value="{{ old('billing_postal_code', $prefill['billing']['postal_code']) }}" required></div></div>
                    <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="billing-city" class="color-highlight">City</label><input id="billing-city" type="text" name="billing_city" value="{{ old('billing_city', $prefill['billing']['city']) }}" required></div></div>
                </div>
                <div class="row">
                    <div class="col-7"><div class="input-style has-borders no-icon input-style-always-active mb-0"><label for="billing-state" class="color-highlight">State</label><input id="billing-state" type="text" name="billing_state" value="{{ old('billing_state', $prefill['billing']['state']) }}"></div></div>
                    <div class="col-5"><div class="input-style has-borders no-icon input-style-always-active mb-0"><label for="billing-country" class="color-highlight">Country</label><input id="billing-country" type="text" name="billing_country_code" maxlength="2" value="{{ old('billing_country_code', $prefill['billing']['country_code']) }}" required></div></div>
                </div>
            </div>
        </div>

        <div class="card card-style">
            <div class="content">
                <div class="d-flex mb-2">
                    <div>
                        <p class="font-600 color-highlight mb-n1">Shipping</p>
                        <h3>Shipping Address</h3>
                    </div>
                    <div class="ms-auto align-self-end">
                        <label class="font-12"><input type="checkbox" name="use_billing_for_shipping" value="1" @checked(old('use_billing_for_shipping'))> Same as billing</label>
                    </div>
                </div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="shipping-first" class="color-highlight">First name</label><input id="shipping-first" type="text" name="shipping_first_name" value="{{ old('shipping_first_name', $prefill['shipping']['first_name']) }}"></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="shipping-last" class="color-highlight">Last name</label><input id="shipping-last" type="text" name="shipping_last_name" value="{{ old('shipping_last_name', $prefill['shipping']['last_name']) }}"></div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="shipping-address1" class="color-highlight">Address line 1</label><input id="shipping-address1" type="text" name="shipping_address_line_1" value="{{ old('shipping_address_line_1', $prefill['shipping']['address_line_1']) }}"></div>
                <div class="row">
                    <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="shipping-postal" class="color-highlight">Postal</label><input id="shipping-postal" type="text" name="shipping_postal_code" value="{{ old('shipping_postal_code', $prefill['shipping']['postal_code']) }}"></div></div>
                    <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="shipping-city" class="color-highlight">City</label><input id="shipping-city" type="text" name="shipping_city" value="{{ old('shipping_city', $prefill['shipping']['city']) }}"></div></div>
                </div>
                <div class="row">
                    <div class="col-7"><div class="input-style has-borders no-icon input-style-always-active mb-0"><label for="shipping-state" class="color-highlight">State</label><input id="shipping-state" type="text" name="shipping_state" value="{{ old('shipping_state', $prefill['shipping']['state']) }}"></div></div>
                    <div class="col-5"><div class="input-style has-borders no-icon input-style-always-active mb-0"><label for="shipping-country" class="color-highlight">Country</label><input id="shipping-country" type="text" maxlength="2" name="shipping_country_code" value="{{ old('shipping_country_code', $prefill['shipping']['country_code']) }}"></div></div>
                </div>
            </div>
        </div>

        <div class="card card-style">
            <div class="content">
                <p class="font-600 color-highlight mb-n1">Payment & Delivery</p>
                <h3>Methods</h3>

                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="shipping-method" class="color-highlight">Shipping method</label>
                    <select id="shipping-method" name="shipping_method_code" required>
                        @foreach ($shippingMethods as $method)
                            <option value="{{ $method->code }}" @selected(old('shipping_method_code') === $method->code)>
                                {{ $method->name }} (EUR {{ number_format((float) $method->price, 2) }})
                            </option>
                        @endforeach
                    </select>
                    <span><i class="fa fa-chevron-down"></i></span>
                </div>

                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="payment-method" class="color-highlight">Payment method</label>
                    <select id="payment-method" name="payment_method_code" required>
                        @foreach ($paymentMethods as $method)
                            <option value="{{ $method->code }}" @selected(old('payment_method_code') === $method->code)>{{ $method->name }}</option>
                        @endforeach
                    </select>
                    <span><i class="fa fa-chevron-down"></i></span>
                </div>

                <div class="input-style has-borders input-style-always-active no-icon mb-3">
                    <textarea id="customer-note" name="customer_note" style="height:120px;">{{ old('customer_note') }}</textarea>
                    <label for="customer-note" class="color-highlight">Order note</label>
                </div>

                <label class="font-12 d-block mb-3"><input type="checkbox" name="accept_terms" value="1" required> I agree to checkout terms.</label>

                <button type="submit" class="btn btn-margins btn-full gradient-blue font-13 btn-l font-600 rounded-sm">Place Order</button>
            </div>
        </div>
    </form>
@endsection

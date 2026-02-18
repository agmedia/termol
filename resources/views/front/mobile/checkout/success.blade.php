@extends('front.mobile.layouts.store')

@section('title', 'Order Success')
@section('header_title', 'Order Complete')
@section('page_title', 'Success')

@section('content')
    <div class="card card-style bg-green-dark" data-card-height="180">
        <div class="card-center text-center px-3">
            <i class="fa fa-circle-check color-white font-40 d-block mb-2"></i>
            <h2 class="color-white font-800 mb-1">Thank you</h2>
            <p class="color-white mb-0 opacity-70">Order {{ $order->order_number }} is confirmed.</p>
        </div>
        <div class="card-overlay bg-black opacity-30"></div>
    </div>

    <div class="card card-style">
        <div class="content">
            <h4 class="mb-3">Order Summary</h4>
            <div class="d-flex mb-2">
                <span class="font-13 opacity-70">Status</span>
                <span class="ms-auto font-600">{{ $order->status?->name ?? 'New' }}</span>
            </div>
            <div class="d-flex mb-2">
                <span class="font-13 opacity-70">Items</span>
                <span class="ms-auto font-600">{{ (int) $order->item_qty }}</span>
            </div>
            <div class="d-flex">
                <span class="font-13 opacity-70">Grand total</span>
                <span class="ms-auto font-700">{{ $order->currency_code }} {{ number_format((float) $order->grand_total, 2) }}</span>
            </div>

            @if ($order->totals->isNotEmpty())
                <div class="divider mt-3 mb-3"></div>
                @foreach ($order->totals as $total)
                    <div class="d-flex mb-2">
                        <span class="font-13 opacity-70">{{ $total->title }}</span>
                        <span class="ms-auto">{{ $order->currency_code }} {{ number_format((float) $total->value, 2) }}</span>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    @if ($order->items->isNotEmpty())
        <div class="card card-style">
            <div class="content">
                <h4 class="mb-3">Items</h4>
                @foreach ($order->items as $item)
                    <div class="d-flex mb-2">
                        <div class="w-100 pe-3">
                            <h6 class="font-14 mb-1">{{ $item->name }}</h6>
                            <p class="font-11 opacity-60 mb-0">Qty {{ (int) $item->quantity }}</p>
                        </div>
                        <div class="text-end">
                            <p class="font-14 font-600 mb-0">{{ $order->currency_code }} {{ number_format((float) $item->line_total, 2) }}</p>
                        </div>
                    </div>
                    @if (! $loop->last)
                        <div class="divider my-2"></div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <a href="{{ route('shop.index') }}" class="btn btn-margins btn-full gradient-blue font-13 btn-l font-600 rounded-sm">Continue Shopping</a>

    @auth
        <a href="{{ route('account.orders.show', ['orderNumber' => $order->order_number]) }}" class="btn btn-margins btn-full btn-border border-gray-dark color-gray-dark font-13 btn-l font-600 rounded-sm mt-2">View in Account</a>
    @endauth
@endsection

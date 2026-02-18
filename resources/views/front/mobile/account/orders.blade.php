@extends('front.mobile.layouts.store')

@section('title', 'My Orders')
@section('header_title', 'Account')
@section('page_title', 'Orders')

@section('content')
    <div class="card card-style">
        <div class="content mb-2">
            <div class="d-flex mb-1">
                <h4 class="mb-0">Order History</h4>
                <a href="{{ route('account.dashboard') }}" class="ms-auto font-12 color-highlight">Dashboard</a>
            </div>
            <p class="font-12 opacity-70 mb-0">All orders tied to your account.</p>
        </div>
    </div>

    @forelse ($orders as $order)
        <a href="{{ route('account.orders.show', ['orderNumber' => $order->order_number]) }}" class="card card-style d-block mb-2">
            <div class="content">
                <div class="d-flex mb-1">
                    <h5 class="mb-0">{{ $order->order_number }}</h5>
                    <p class="ms-auto mb-0 font-13">{{ $order->currency_code }} {{ number_format((float) $order->grand_total, 2) }}</p>
                </div>
                <p class="mb-1 opacity-70 font-12">{{ optional($order->placed_at ?? $order->created_at)->format('Y-m-d H:i') }}</p>
                <span class="badge bg-highlight">{{ $order->status?->name ?? 'New' }}</span>
            </div>
        </a>
    @empty
        <div class="card card-style"><div class="content"><p class="mb-0">No orders yet.</p></div></div>
    @endforelse

    @if ($orders->hasPages())
        <div class="card card-style"><div class="content">{{ $orders->links('pagination::bootstrap-5') }}</div></div>
    @endif
@endsection

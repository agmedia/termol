@extends('front.mobile.layouts.store')

@section('title', 'My Account')
@section('header_title', 'Account')
@section('page_title', 'Dashboard')

@section('content')
    <div class="card card-style bg-11" data-card-height="170">
        <div class="card-bottom ps-3 pb-3 pe-3">
            <p class="color-white opacity-70 mb-1">Signed in as</p>
            <h2 class="color-white font-800 mb-0">{{ $user->name }}</h2>
            <p class="color-white opacity-70 mb-0">{{ $user->email }}</p>
        </div>
        <div class="card-overlay bg-black opacity-70"></div>
    </div>

    <div class="content mt-0 mb-1">
        <div class="row mb-0">
            <div class="col-6 pe-1">
                <a href="{{ route('account.orders') }}" class="card card-style mx-0 mb-2 p-3 d-block">
                    <h6 class="font-14 mb-1">Orders</h6>
                    <h3 class="mb-0">{{ $orders->count() }}</h3>
                </a>
            </div>
            <div class="col-6 ps-1">
                <a href="{{ route('account.profile') }}" class="card card-style mx-0 mb-2 p-3 d-block">
                    <h6 class="font-14 mb-1">Profile</h6>
                    <h3 class="mb-0"><i class="fa fa-user font-18"></i></h3>
                </a>
            </div>
            <div class="col-12">
                <div class="card card-style mx-0 p-3 mb-0">
                    <h6 class="font-14 mb-1">Loyalty</h6>
                    @if ($loyaltyEnabled)
                        <h3 class="color-green-dark mb-0">{{ $loyaltyBalance }} pts</h3>
                    @else
                        <h3 class="opacity-50 mb-0">Disabled</h3>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card card-style">
        <div class="content">
            <div class="d-flex mb-2">
                <h4 class="mb-0">Recent Orders</h4>
                <a href="{{ route('account.orders') }}" class="ms-auto font-12 color-highlight">View all</a>
            </div>

            @forelse ($orders as $order)
                <a href="{{ route('account.orders.show', ['orderNumber' => $order->order_number]) }}" class="d-block">
                    <div class="d-flex">
                        <div>
                            <h6 class="font-14 mb-1">{{ $order->order_number }}</h6>
                            <p class="font-11 opacity-60 mb-0">{{ optional($order->placed_at ?? $order->created_at)->format('Y-m-d H:i') }}</p>
                        </div>
                        <div class="ms-auto text-end">
                            <p class="font-11 opacity-60 mb-1">{{ $order->status?->name ?? 'New' }}</p>
                            <h6 class="font-14 mb-0">{{ $order->currency_code }} {{ number_format((float) $order->grand_total, 2) }}</h6>
                        </div>
                    </div>
                </a>
                @if (! $loop->last)
                    <div class="divider my-2"></div>
                @endif
            @empty
                <p class="mb-0 opacity-70">No orders yet.</p>
            @endforelse
        </div>
    </div>

    @if ($loyaltyEnabled && $loyaltyRecent->isNotEmpty())
        <div class="card card-style">
            <div class="content">
                <h4 class="mb-2">Recent Loyalty Entries</h4>
                @foreach ($loyaltyRecent as $entry)
                    <div class="d-flex mb-2">
                        <div>
                            <p class="font-13 mb-0">{{ $entry->type }}</p>
                            <p class="font-11 opacity-60 mb-0">{{ optional($entry->created_at)->format('Y-m-d H:i') }}</p>
                        </div>
                        <p class="ms-auto mb-0 font-700 {{ $entry->points >= 0 ? 'color-green-dark' : 'color-red-dark' }}">
                            {{ $entry->points >= 0 ? '+' : '' }}{{ $entry->points }}
                        </p>
                    </div>
                    @if (! $loop->last)
                        <div class="divider my-2"></div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
@endsection

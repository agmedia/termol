@extends('front.mobile.layouts.store')

@section('title', 'Order '.$order->order_number)
@section('header_title', 'Order')
@section('page_title', $order->order_number)

@section('content')
    <div class="card card-style">
        <div class="content">
            <div class="d-flex mb-2">
                <h4 class="mb-0">{{ $order->order_number }}</h4>
                <a href="{{ route('account.orders') }}" class="ms-auto font-12 color-highlight">Back</a>
            </div>
            <p class="font-12 opacity-70 mb-1">{{ optional($order->placed_at ?? $order->created_at)->format('Y-m-d H:i') }}</p>
            <span class="badge bg-highlight">{{ $order->status?->name ?? 'New' }}</span>
        </div>
    </div>

    <div class="card card-style">
        <div class="content">
            <h4 class="mb-3">Items</h4>
            @foreach ($order->items as $item)
                <div class="d-flex mb-2">
                    <div class="w-100 pe-3">
                        <h6 class="font-14 mb-1">{{ $item->name }}</h6>
                        <p class="font-11 opacity-60 mb-0">Qty {{ (int) $item->quantity }} • Unit {{ $order->currency_code }} {{ number_format((float) $item->unit_price, 2) }}</p>
                    </div>
                    <p class="font-14 font-600 mb-0">{{ $order->currency_code }} {{ number_format((float) $item->line_total, 2) }}</p>
                </div>
                @if (! $loop->last)
                    <div class="divider my-2"></div>
                @endif
            @endforeach
        </div>
    </div>

    <div class="card card-style">
        <div class="content">
            <h4 class="mb-3">Totals</h4>
            @foreach ($order->totals as $total)
                <div class="d-flex mb-2">
                    <span class="font-13 opacity-70">{{ $total->title }}</span>
                    <span class="ms-auto font-600">{{ $order->currency_code }} {{ number_format((float) $total->value, 2) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card card-style">
        <div class="content">
            <h4 class="mb-3">Order Timeline</h4>
            @forelse ($order->history as $entry)
                <div class="mb-2">
                    <p class="mb-1 font-13">{{ optional($entry->created_at)->format('Y-m-d H:i') }} → {{ $entry->toStatus?->name ?? 'Status updated' }}</p>
                    @if ($entry->comment)
                        <p class="mb-0 font-12 opacity-70">{{ $entry->comment }}</p>
                    @endif
                </div>
                @if (! $loop->last)
                    <div class="divider my-2"></div>
                @endif
            @empty
                <p class="mb-0 opacity-70">No status updates yet.</p>
            @endforelse
        </div>
    </div>
@endsection

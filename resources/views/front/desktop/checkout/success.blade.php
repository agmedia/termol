@extends('front.desktop.layouts.store')

@section('title', 'Order Success')

@section('content')
    <section class="rounded-2xl border border-emerald-200 bg-white p-8 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Order placed</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">Thank you for your order</h1>
        <p class="mt-3 text-slate-600">Order number: <span class="font-semibold text-slate-900">{{ $order->order_number }}</span></p>

        <dl class="mt-6 grid gap-3 text-sm md:grid-cols-2">
            <div class="rounded-xl bg-slate-100 p-4">
                <dt class="text-slate-500">Status</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $order->status?->name ?? 'New' }}</dd>
            </div>
            <div class="rounded-xl bg-slate-100 p-4">
                <dt class="text-slate-500">Grand total</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $order->currency_code }} {{ number_format((float) $order->grand_total, 2) }}</dd>
            </div>
        </dl>

        <div class="mt-8 flex flex-wrap gap-2">
            @auth
                <a href="{{ route('account.orders.show', ['orderNumber' => $order->order_number]) }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">View in account</a>
            @endauth
            <a href="{{ route('shop.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Continue shopping</a>
        </div>
    </section>
@endsection

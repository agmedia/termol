@extends('front.desktop.layouts.store')

@section('title', 'My Orders')

@section('content')
    <section class="mb-8 flex items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">My orders</h1>
            <p class="mt-2 text-slate-600">All orders linked to your customer account.</p>
        </div>
        <a href="{{ route('account.dashboard') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Back to dashboard</a>
    </section>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-slate-100/70 text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3">Order</th>
                <th class="px-4 py-3">Placed</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Total</th>
                <th class="px-4 py-3"></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($orders as $order)
                <tr class="border-t border-slate-200">
                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $order->order_number }}</td>
                    <td class="px-4 py-3">{{ optional($order->placed_at ?? $order->created_at)->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3">{{ $order->status?->name ?? 'New' }}</td>
                    <td class="px-4 py-3 font-semibold">{{ $order->currency_code }} {{ number_format((float) $order->grand_total, 2) }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('account.orders.show', ['orderNumber' => $order->order_number]) }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800">Details</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No orders yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
@endsection

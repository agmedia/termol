@extends('front.desktop.layouts.store')

@section('title', 'Order '.$order->order_number)

@section('content')
    <section class="mb-8">
        <a href="{{ route('account.orders') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800">← Back to orders</a>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">Order {{ $order->order_number }}</h1>
        <p class="mt-2 text-slate-600">Status: <span class="font-semibold">{{ $order->status?->name ?? 'New' }}</span></p>
    </section>

    <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100/70 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Item</th>
                        <th class="px-4 py-3">Price</th>
                        <th class="px-4 py-3">Qty</th>
                        <th class="px-4 py-3">Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($order->items as $item)
                        <tr class="border-t border-slate-200">
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $item->name }}</td>
                            <td class="px-4 py-3">{{ $order->currency_code }} {{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="px-4 py-3">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $order->currency_code }} {{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-slate-900">Order timeline</h2>
                <ul class="mt-4 space-y-3 text-sm text-slate-700">
                    @forelse ($order->history as $entry)
                        <li class="rounded-lg bg-slate-100 px-3 py-2">
                            <span class="font-semibold">{{ optional($entry->created_at)->format('Y-m-d H:i') }}</span>
                            → {{ $entry->toStatus?->name ?? 'Status updated' }}
                            @if ($entry->comment)
                                <div class="mt-1 text-slate-600">{{ $entry->comment }}</div>
                            @endif
                        </li>
                    @empty
                        <li class="text-slate-500">No status history entries.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Totals</h2>
            <dl class="mt-4 space-y-2 text-sm">
                @foreach ($order->totals as $total)
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-600">{{ $total->title }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $order->currency_code }} {{ number_format((float) $total->value, 2) }}</dd>
                    </div>
                @endforeach
            </dl>
        </aside>
    </div>
@endsection

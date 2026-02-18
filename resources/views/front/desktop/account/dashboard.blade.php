@extends('front.desktop.layouts.store')

@section('title', 'My Account')

@section('content')
    <section class="mb-8 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">My account</h1>
            <p class="mt-2 text-slate-600">Personal info, orders, GDPR preferences and loyalty overview.</p>
        </div>
        <a href="{{ route('account.profile') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Edit profile</a>
    </section>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">User</p>
            <h2 class="mt-2 text-xl font-bold text-slate-900">{{ $user->name }}</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $user->email }}</p>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Orders</p>
            <h2 class="mt-2 text-xl font-bold text-slate-900">{{ $orders->count() }}</h2>
            <a href="{{ route('account.orders') }}" class="mt-3 inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800">View all orders</a>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Loyalty</p>
            @if ($loyaltyEnabled)
                <h2 class="mt-2 text-xl font-bold text-slate-900">{{ $loyaltyBalance }} pts</h2>
                <p class="mt-1 text-sm text-slate-600">Based on system switch `user_loyalty_enabled`.</p>
            @else
                <h2 class="mt-2 text-xl font-bold text-slate-900">Disabled</h2>
                <p class="mt-1 text-sm text-slate-600">Loyalty module is currently disabled in Settings → User.</p>
            @endif
        </article>
    </div>

    <section class="mt-10">
        <h2 class="text-2xl font-bold text-slate-900">Recent orders</h2>
        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-slate-100/70 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Placed</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Total</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($orders as $order)
                    <tr class="border-t border-slate-200">
                        <td class="px-4 py-3"><a href="{{ route('account.orders.show', ['orderNumber' => $order->order_number]) }}" class="font-semibold text-blue-700 hover:text-blue-800">{{ $order->order_number }}</a></td>
                        <td class="px-4 py-3">{{ optional($order->placed_at ?? $order->created_at)->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">{{ $order->status?->name ?? 'New' }}</td>
                        <td class="px-4 py-3 font-semibold">{{ $order->currency_code }} {{ number_format((float) $order->grand_total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No orders yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($loyaltyEnabled)
        <section class="mt-10">
            <h2 class="text-2xl font-bold text-slate-900">Recent loyalty entries</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($loyaltyRecent as $entry)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $entry->type }}</p>
                        <p class="mt-2 text-xl font-bold {{ $entry->points >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $entry->points >= 0 ? '+' : '' }}{{ $entry->points }} pts</p>
                        <p class="mt-1 text-sm text-slate-600">{{ optional($entry->created_at)->format('Y-m-d H:i') }}</p>
                    </article>
                @empty
                    <p class="text-sm text-slate-500">No loyalty transactions for this account.</p>
                @endforelse
            </div>
        </section>
    @endif
@endsection

@extends('front.desktop.layouts.store')

@section('title', __('ui.checkout.success.page_title'))

@section('content')
    <section class="border border-emerald-300 bg-white p-8">
        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">{{ __('ui.checkout.success.eyebrow') }}</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ __('ui.checkout.success.title') }}</h1>
        <p class="mt-3 text-slate-600">{{ __('ui.checkout.success.order_number') }}: <span class="font-semibold text-slate-900">{{ $order->order_number }}</span></p>

        <dl class="mt-6 grid gap-3 text-sm md:grid-cols-2">
            <div class="border border-slate-200 bg-slate-100 p-4">
                <dt class="text-slate-500">{{ __('ui.checkout.success.status') }}</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $order->status?->name ?? __('ui.checkout.success.status_fallback_new') }}</dd>
            </div>
            <div class="border border-slate-200 bg-slate-100 p-4">
                <dt class="text-slate-500">{{ __('ui.checkout.success.grand_total') }}</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $order->currency_code }} {{ number_format((float) $order->grand_total, 2) }}</dd>
            </div>
        </dl>

        <div class="mt-8 flex flex-wrap gap-2">
            @auth
                <a href="{{ route('account.orders.show', ['orderNumber' => $order->order_number]) }}" class="bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">{{ __('ui.checkout.success.view_in_account') }}</a>
            @endauth
            <a href="{{ route('shop.index') }}" class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('ui.checkout.success.continue_shopping') }}</a>
        </div>
    </section>

    @php
        $analytics = $storeSettings['analytics'] ?? [];
        $shouldTrackPurchase = (bool) ($analytics['enabled'] ?? false)
            && (bool) ($analytics['purchase_event_enabled'] ?? true)
            && trim((string) ($analytics['ga4_measurement_id'] ?? '')) !== '';
        $eventName = trim((string) ($analytics['purchase_event_name'] ?? 'purchase')) ?: 'purchase';
        $eventItems = $order->items->map(static function ($item): array {
            return [
                'item_id' => (string) ($item->sku ?: $item->code ?: $item->id),
                'item_name' => (string) $item->name,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->unit_price,
            ];
        })->values()->all();
    @endphp

    @if ($shouldTrackPurchase)
        @push('scripts')
            <script>
                if (typeof gtag === 'function') {
                    gtag('{{ $eventName }}', {
                        transaction_id: @json((string) $order->order_number),
                        currency: @json((string) ($order->currency_code ?: 'EUR')),
                        value: {{ (float) $order->grand_total }},
                        tax: {{ (float) $order->tax_total }},
                        shipping: {{ (float) $order->shipping_total }},
                        coupon: @json((string) ($order->payload['coupon_code'] ?? '')),
                        items: @json($eventItems)
                    });
                }
            </script>
        @endpush
    @endif
@endsection

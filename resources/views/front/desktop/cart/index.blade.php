@extends('front.desktop.layouts.store')

@section('title', 'Cart')

@section('content')
    <section class="mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Cart</h1>
        <p class="mt-2 text-slate-600">Review products before checkout.</p>
    </section>

    @if ($lines->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <p class="text-slate-600">Your cart is currently empty.</p>
            <a href="{{ route('shop.index') }}" class="mt-4 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Continue shopping</a>
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100/70 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">Price</th>
                        <th class="px-4 py-3">Qty</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($lines as $line)
                        @php
                            $product = $line['product'];
                            $translation = $line['translation'];
                        @endphp
                        <tr class="border-t border-slate-200">
                            <td class="px-4 py-4">
                                <a href="{{ route('products.show', ['slug' => $translation?->slug ?? $product->id]) }}" class="font-semibold text-slate-900 hover:text-blue-700">
                                    {{ $translation?->name ?? $product->code }}
                                </a>
                                @if (!empty($line['option_label']))
                                    <p class="mt-1 text-xs text-slate-500">{{ $line['option_label'] }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4">EUR {{ number_format((float) $line['unit_price'], 2) }}</td>
                            <td class="px-4 py-4">
                                <form method="POST" action="{{ route('cart.items.update', ['product' => $product->id]) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    @if (!empty($line['product_option_value_id']))
                                        <input type="hidden" name="product_option_value_id" value="{{ (int) $line['product_option_value_id'] }}">
                                    @endif
                                    <input type="number" name="quantity" value="{{ (int) $line['quantity'] }}" min="0" max="999" class="w-20 rounded-lg border-slate-300 text-sm">
                                    <button type="submit" class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Save</button>
                                </form>
                            </td>
                            <td class="px-4 py-4 font-semibold text-slate-900">EUR {{ number_format((float) $line['line_total'], 2) }}</td>
                            <td class="px-4 py-4">
                                <form method="POST" action="{{ route('cart.items.destroy', ['product' => $product->id]) }}">
                                    @csrf
                                    @method('DELETE')
                                    @if (!empty($line['product_option_value_id']))
                                        <input type="hidden" name="product_option_value_id" value="{{ (int) $line['product_option_value_id'] }}">
                                    @endif
                                    <button type="submit" class="rounded-lg border border-rose-200 px-2.5 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Summary</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-600">Items</dt>
                        <dd class="font-semibold text-slate-900">{{ $summary['item_qty'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-600">Subtotal</dt>
                        <dd class="font-semibold text-slate-900">EUR {{ number_format((float) $summary['subtotal'], 2) }}</dd>
                    </div>
                </dl>

                <a href="{{ route('checkout.create') }}" class="mt-5 block rounded-lg bg-slate-900 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-slate-700">Proceed to checkout</a>

                <form method="POST" action="{{ route('cart.clear') }}" class="mt-2">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Clear cart</button>
                </form>
            </aside>
        </div>
    @endif
@endsection

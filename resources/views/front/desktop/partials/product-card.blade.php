@php
    $translation = $product->translations->firstWhere('locale', $locale ?? app()->getLocale())
        ?? $product->translations->firstWhere('locale', $fallbackLocale ?? config('app.locale'));
@endphp

<article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <a href="{{ route('products.show', ['slug' => $translation?->slug ?? $product->id]) }}" class="block">
        <h3 class="text-lg font-semibold text-slate-900">{{ $translation?->name ?? $product->code }}</h3>
        <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ $translation?->excerpt ?: 'Ready for production catalog data.' }}</p>
    </a>

    <div class="mt-4 flex items-center justify-between">
        <p class="text-lg font-bold text-slate-900">EUR {{ number_format((float) $product->base_price, 2) }}</p>
        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Stock {{ (int) $product->stock_qty }}</span>
    </div>

    <form method="POST" action="{{ route('cart.items.store') }}" class="mt-4 flex items-center gap-2">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="number" name="quantity" min="1" max="99" value="1" class="w-20 rounded-lg border-slate-300 text-sm">
        <button type="submit" class="flex-1 rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">Add to cart</button>
    </form>
</article>

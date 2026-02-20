@php
    use App\Models\Catalog\Product\Product;

    $payload = $block->payload ?? [];
    $source = ($payload['source'] ?? 'manual') === 'query' ? 'query' : 'manual';
    $limit = max(1, min(30, (int) ($payload['limit'] ?? 10)));
    $sort = (string) ($payload['sort'] ?? 'newest');
    $locale = app()->getLocale();
    $fallbackLocale = config('app.locale');
    $translationPayload = is_array($translation?->payload ?? null) ? $translation->payload : [];
    $mergedPayload = is_array($payload) ? array_merge($payload, $translationPayload) : $translationPayload;
    $allowedRoutes = config('content_blocks.route_whitelist', []);

    $resolveRouteUrl = function (?string $routeName, mixed $routeParams, string $fallbackUrl = '#') use ($allowedRoutes): string {
        $name = trim((string) $routeName);
        if ($name === '') {
            return $fallbackUrl;
        }

        $isAllowed = $allowedRoutes === []
            || collect($allowedRoutes)->contains(fn ($pattern) => \Illuminate\Support\Str::is((string) $pattern, $name));

        if (! $isAllowed || !\Illuminate\Support\Facades\Route::has($name)) {
            return $fallbackUrl;
        }

        $params = is_array($routeParams) ? $routeParams : [];

        try {
            return route($name, $params);
        } catch (\Throwable) {
            return $fallbackUrl;
        }
    };

    $manualIds = collect($payload['manual_product_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->values()->all();
    $categoryIds = collect($payload['category_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->values()->all();
    $manufacturerIds = collect($payload['manufacturer_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->values()->all();

    $query = Product::query()
        ->where('is_active', true)
        ->with([
            'translations' => fn ($q) => $q->whereIn('locale', [$locale, $fallbackLocale]),
            'taxRate',
        ]);
    $taxPricing = app(\App\Services\Pricing\TaxPricingService::class);

    if ($source === 'manual' && $manualIds !== []) {
        $query->whereIn('id', $manualIds);
    } else {
        if ($categoryIds !== []) {
            $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds));
        }
        if ($manufacturerIds !== []) {
            $query->whereIn('manufacturer_id', $manufacturerIds);
        }
    }

    if ($sort === 'price_asc') {
        $query->orderBy('base_price');
    } elseif ($sort === 'price_desc') {
        $query->orderByDesc('base_price');
    } elseif ($sort === 'name') {
        $query->join('product_translations as pt_sort', function ($join) use ($locale) {
            $join->on('pt_sort.product_id', '=', 'products.id')->where('pt_sort.locale', '=', $locale);
        })->orderBy('pt_sort.name')->select('products.*');
    } else {
        $query->orderByDesc('id');
    }

    $products = $query->limit($limit)->get();

    if ($source === 'manual' && $manualIds !== []) {
        $rank = array_flip($manualIds);
        $products = $products->sortBy(fn ($item) => $rank[$item->id] ?? PHP_INT_MAX)->values();
    }

    $ctaLabel = (string) ($translation?->cta_label ?? '');
    $ctaFallbackUrl = (string) ($translation?->cta_url ?? '#');
    $ctaRoute = (string) ($mergedPayload['cta_route'] ?? '');
    $ctaRouteParams = $mergedPayload['cta_route_params'] ?? [];
    $ctaUrl = $resolveRouteUrl($ctaRoute, $ctaRouteParams, $ctaFallbackUrl);
@endphp

<section class="rounded-2xl border border-slate-200 bg-white p-6">
    <div class="mb-4 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">{{ $translation->title ?? $block->name }}</h2>
            @if (!empty($translation?->subtitle))
                <p class="mt-1 text-sm text-slate-600">{{ $translation->subtitle }}</p>
            @endif
        </div>
        @if ($ctaLabel !== '' && $ctaUrl !== '')
            <a href="{{ $ctaUrl }}" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">{{ $ctaLabel }}</a>
        @endif
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @forelse ($products as $product)
            @php
                $pt = $product->translations->firstWhere('locale', $locale)
                    ?? $product->translations->firstWhere('locale', $fallbackLocale);
                $excerpt = $pt?->meta_description ?: $pt?->excerpt;
                $displayPrice = $taxPricing->grossFromNet((float) $product->base_price, $product);
            @endphp
            <article class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="h-28 rounded-lg bg-gradient-to-br from-slate-200 to-slate-100"></div>
                <h3 class="mt-3 text-sm font-semibold text-slate-900">{{ $pt?->name ?? $product->code }}</h3>
                @if (!empty($excerpt))
                    <p class="mt-1 text-xs text-slate-600">{{ \Illuminate\Support\Str::limit((string) $excerpt, 80) }}</p>
                @endif
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ number_format($displayPrice, 2) }} €</p>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-xs text-slate-500 sm:col-span-2 xl:col-span-5">
                No products matched this carousel source.
            </div>
        @endforelse
    </div>
</section>

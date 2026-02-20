@php
    $basePayload = is_array($block->payload ?? null) ? $block->payload : [];
    $translationPayload = is_array($translation?->payload ?? null) ? $translation->payload : [];
    $payload = array_merge($basePayload, $translationPayload);

    $sectionClass = (string) ($payload['section_class'] ?? 'rounded-3xl border border-slate-200 bg-white p-6 lg:p-8');
    $gridClass = (string) ($payload['grid_class'] ?? 'mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-4');
    $cardClass = (string) ($payload['card_class'] ?? 'rounded-3xl border border-slate-200 bg-slate-50 p-5');
    $titleClass = (string) ($payload['title_class'] ?? 'text-2xl font-extrabold tracking-tight text-slate-900');
    $taxPricing = app(\App\Services\Pricing\TaxPricingService::class);
@endphp

<section class="{{ $sectionClass }}">
    <div class="flex items-end justify-between gap-4">
        <div>
            <h2 class="{{ $titleClass }}">{{ $translation?->title ?: $block->name }}</h2>
            @if (!empty($translation?->subtitle))
                <p class="mt-2 text-sm text-slate-600">{{ $translation->subtitle }}</p>
            @endif
        </div>
    </div>

    <div class="{{ $gridClass }}">
        @forelse ($products as $product)
            @php
                $pt = $product->translations->firstWhere('locale', app()->getLocale())
                    ?? $product->translations->firstWhere('locale', config('app.locale'));
                $imageUrl = $product->getFirstMediaUrl('product_main', 'detail_960x960');
                if ($imageUrl === '') {
                    $imageUrl = $product->getFirstMediaUrl('product_main');
                }
                $displayPrice = $taxPricing->grossFromNet((float) $product->base_price, $product);
            @endphp
            <article class="{{ $cardClass }}">
                <div class="relative overflow-hidden rounded-2xl bg-slate-100">
                    @if ($imageUrl !== '')
                        <img src="{{ $imageUrl }}" alt="{{ $pt?->name ?? $product->code }}" class="h-64 w-full object-cover" loading="lazy" />
                    @else
                        <div class="h-64 w-full bg-gradient-to-br from-slate-200 to-slate-100"></div>
                    @endif
                    <button type="button" class="absolute right-3 top-3 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/90 text-slate-600 shadow-sm ring-1 ring-slate-200 transition hover:bg-white" aria-label="Wishlist">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20.8 8.6c0 5.9-8.8 10.9-8.8 10.9S3.2 14.5 3.2 8.6a4.8 4.8 0 0 1 8.8-2.7 4.8 4.8 0 0 1 8.8 2.7Z"/>
                        </svg>
                    </button>
                </div>

                <p class="mt-5 text-sm text-slate-500">{{ $pt?->meta_title ?: 'Selected product' }}</p>
                <h3 class="mt-1 text-3xl font-semibold leading-tight text-slate-800">{{ $pt?->name ?? $product->code }}</h3>

                <div class="mt-3 flex items-center justify-between gap-3">
                    <p class="text-4xl font-bold leading-none text-indigo-600">{{ number_format($displayPrice, 2) }} €</p>
                    <div class="flex items-center gap-1 text-orange-400">
                        @for ($i = 0; $i < 5; $i++)
                            <svg viewBox="0 0 20 20" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                                <path d="M9.05 2.93a1 1 0 0 1 1.9 0l1.45 3.68a1 1 0 0 0 .82.62l3.95.3a1 1 0 0 1 .57 1.75l-3.02 2.54a1 1 0 0 0-.33.98l.93 3.83a1 1 0 0 1-1.5 1.08L10.5 16.6a1 1 0 0 0-1 0l-3.32 2.1a1 1 0 0 1-1.5-1.08l.93-3.83a1 1 0 0 0-.33-.98L2.26 9.28a1 1 0 0 1 .57-1.75l3.95-.3a1 1 0 0 0 .82-.62l1.45-3.68Z"/>
                            </svg>
                        @endfor
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach (['XS', 'S', 'M', 'L'] as $size)
                        <button type="button" class="inline-flex h-11 min-w-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-600 transition hover:border-rose-300 hover:text-rose-500 {{ $size === 'S' ? 'border-rose-400 text-rose-500' : '' }}">
                            {{ $size }}
                        </button>
                    @endforeach
                </div>

                <button type="button" class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-rose-500 px-5 py-3 text-lg font-semibold text-white transition hover:bg-rose-600">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M7 9h10l-1 10H8L7 9Z"></path>
                        <path d="M9 9V7a3 3 0 0 1 6 0v2"></path>
                    </svg>
                    Add to cart
                </button>

                <button type="button" class="mt-4 inline-flex w-full items-center justify-center gap-2 text-lg font-medium text-slate-500 transition hover:text-slate-700">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12Z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    Quick view
                </button>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 sm:col-span-2 xl:col-span-4">
                No products selected for this block.
            </div>
        @endforelse
    </div>
</section>

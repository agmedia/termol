<div class="space-y-6">
    <section class="admin-panel admin-search-panel p-6">
        <div class="flex flex-col gap-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ __('M SAN katalog') }}</p>
                    <h2 class="mt-2 text-xl font-semibold tracking-tight text-slate-900">{{ __('Odabir artikala za uvoz') }}</h2>
                    <p class="mt-2 max-w-3xl text-sm text-slate-600">
                        {{ __('Odabir se trajno sprema u bazu. Artikl se može odabrati tek kada ima barem jednu mapiranu M SAN kategoriju.') }}
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        <span class="admin-chip">{{ __('Stavki po stranici: :count', ['count' => $perPage]) }}</span>
                        <span class="rounded-full bg-cyan-100 px-2.5 py-1 font-semibold text-cyan-800">
                            {{ __('Odabrano i spremno: :count', ['count' => number_format($selectedEligibleCount, 0, ',', '.')]) }}
                        </span>
                    </div>
                </div>

                @if ($canManageImport)
                    <div class="flex flex-wrap gap-2 xl:max-w-xl xl:justify-end">
                        <button
                            type="button"
                            wire:click="selectFiltered"
                            wire:confirm="{{ __('Odabrati sve artikle koji odgovaraju trenutnim filtrima i imaju mapiranu kategoriju?') }}"
                            wire:loading.attr="disabled"
                            wire:target="selectFiltered"
                            class="rounded-xl border border-cyan-300 bg-cyan-50 px-3 py-2 text-xs font-semibold text-cyan-800 hover:bg-cyan-100 disabled:cursor-wait disabled:opacity-60"
                        >
                            {{ __('Odaberi sve filtrirane') }}
                        </button>
                        <button
                            type="button"
                            wire:click="deselectFiltered"
                            wire:confirm="{{ __('Poništiti odabir svih artikala koji odgovaraju trenutnim filtrima?') }}"
                            wire:loading.attr="disabled"
                            wire:target="deselectFiltered"
                            class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 disabled:cursor-wait disabled:opacity-60"
                        >
                            {{ __('Poništi odabir filtriranih') }}
                        </button>
                        <button
                            type="button"
                            wire:click="queueSelectedImport"
                            wire:confirm="{{ __('Pokrenuti uvoz svih odabranih artikala s mapiranom kategorijom?') }}"
                            wire:loading.attr="disabled"
                            wire:target="queueSelectedImport"
                            @disabled($selectedEligibleCount === 0)
                            class="rounded-xl bg-cyan-700 px-4 py-2 text-xs font-semibold text-white hover:bg-cyan-800 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="queueSelectedImport">{{ __('Pokreni uvoz odabranih') }}</span>
                            <span wire:loading wire:target="queueSelectedImport">{{ __('Pokretanje...') }}</span>
                        </button>
                    </div>
                @endif
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                <form wire:submit="applySearch" class="md:col-span-2 xl:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500" for="msan-product-search">{{ __('Pretraga') }}</label>
                    <div class="flex gap-2">
                        <input id="msan-product-search" type="search" wire:model="searchInput" minlength="2" maxlength="120" placeholder="{{ __('Početak naziva, M SAN šifre, modela ili kataloškog broja...') }}" class="admin-search-input min-w-0 flex-1 rounded-xl border px-3 py-2 text-sm">
                        <button type="submit" wire:loading.attr="disabled" wire:target="applySearch" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 disabled:cursor-wait disabled:opacity-60">
                            {{ __('Traži') }}
                        </button>
                    </div>
                    @error('searchInput') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </form>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500" for="msan-product-category">{{ __('Kategorija') }}</label>
                    <select id="msan-product-category" wire:model.live="categoryId" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">{{ __('Sve kategorije') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category['id'] }}">{{ $category['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500" for="msan-product-brand">{{ __('Proizvođač') }}</label>
                    <select id="msan-product-brand" wire:model.live="brand" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">{{ __('Svi proizvođači') }}</option>
                        @foreach ($brands as $brandOption)
                            <option value="{{ $brandOption }}">{{ $brandOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500" for="msan-product-availability">{{ __('Raspoloživost') }}</label>
                    <select id="msan-product-availability" wire:model.live="availability" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="all">{{ __('Sve') }}</option>
                        <option value="available">{{ __('Raspoloživo') }}</option>
                        <option value="unavailable">{{ __('Nije raspoloživo') }}</option>
                        <option value="unknown">{{ __('Nepoznato') }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500" for="msan-product-selection">{{ __('Odabir') }}</label>
                    <select id="msan-product-selection" wire:model.live="selection" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="all">{{ __('Svi artikli') }}</option>
                        <option value="selected">{{ __('Odabrani') }}</option>
                        <option value="unselected">{{ __('Neodabrani') }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500" for="msan-product-import-status">{{ __('Status uvoza') }}</label>
                    <select id="msan-product-import-status" wire:model.live="importStatus" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="all">{{ __('Svi statusi') }}</option>
                        @foreach ($importStatuses as $statusOption)
                            <option value="{{ $statusOption['value'] }}">{{ $statusOption['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end md:col-span-1 xl:col-span-1">
                    <button type="button" wire:click="clearFilters" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                        {{ __('Očisti filtre') }}
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-panel admin-panel-soft p-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="admin-section-title">{{ __('M SAN artikli') }}</h2>
            <p class="text-xs text-slate-500">
                {{ __('Prikazano :from–:to od :total', [
                    'from' => $products->firstItem() ?? 0,
                    'to' => $products->lastItem() ?? 0,
                    'total' => $products->total(),
                ]) }}
            </p>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="admin-items-table min-w-[96rem] text-sm">
                <thead class="text-slate-600">
                    <tr>
                        <th class="px-3 py-2 text-center font-semibold">{{ __('Odabir') }}</th>
                        <th class="px-3 py-2 text-left font-semibold">{{ __('Artikl') }}</th>
                        <th class="px-3 py-2 text-left font-semibold">{{ __('Proizvođač / kategorije') }}</th>
                        <th class="px-3 py-2 text-right font-semibold">{{ __('Cijene') }}</th>
                        <th class="px-3 py-2 text-center font-semibold">{{ __('Raspoloživost') }}</th>
                        <th class="px-3 py-2 text-left font-semibold">{{ __('Lokalni artikl') }}</th>
                        <th class="px-3 py-2 text-left font-semibold">{{ __('Uvoz') }}</th>
                        <th class="px-3 py-2 text-left font-semibold">{{ __('Zadnje viđeno') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        @php
                            $eligible = (int) $product->mapped_categories_count > 0
                                && ! $product->is_stale
                                && ! in_array((string) $product->match_status, ['conflict', 'ignored'], true);
                            $canToggle = $canManageImport && ($product->selected || $eligible);
                            $currency = strtoupper((string) ($product->currency_code ?: 'EUR'));
                            $importStatusClass = match ((string) $product->import_status) {
                                'imported' => 'bg-emerald-100 text-emerald-800',
                                'queued', 'importing' => 'bg-cyan-100 text-cyan-800',
                                'failed' => 'bg-rose-100 text-rose-800',
                                'skipped' => 'bg-amber-100 text-amber-800',
                                default => 'bg-slate-200 text-slate-700',
                            };
                            $importStatusLabel = match ((string) $product->import_status) {
                                'pending' => __('Čeka uvoz'),
                                'queued' => __('U redu čekanja'),
                                'importing' => __('Uvoz u tijeku'),
                                'imported' => __('Uvezeno'),
                                'failed' => __('Neuspješno'),
                                'skipped' => __('Preskočeno'),
                                default => (string) $product->import_status,
                            };
                            $matchStatusLabel = match ((string) $product->match_status) {
                                'matched' => __('Povezano'),
                                'conflict' => __('Konflikt'),
                                'ignored' => __('Ignorirano'),
                                default => __('Nije povezano'),
                            };
                        @endphp
                        <tr wire:key="msan-product-row-{{ $product->id }}" @class(['bg-cyan-50/50' => $product->selected])>
                            <td class="px-3 py-3 text-center">
                                @if ($canManageImport)
                                    <button
                                        type="button"
                                        wire:click="toggleSelection({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleSelection({{ $product->id }})"
                                        @disabled(! $canToggle)
                                        aria-pressed="{{ $product->selected ? 'true' : 'false' }}"
                                        title="{{ ! $eligible && ! $product->selected ? __('Artikl mora imati mapiranu kategoriju i ne smije imati konflikt.') : '' }}"
                                        class="inline-flex min-w-20 justify-center rounded-lg border px-2.5 py-1.5 text-xs font-semibold disabled:cursor-not-allowed disabled:opacity-50 {{ $product->selected ? 'border-cyan-300 bg-cyan-100 text-cyan-800' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100' }}"
                                    >
                                        {{ $product->selected ? __('Odabrano') : __('Odaberi') }}
                                    </button>
                                @else
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $product->selected ? 'bg-cyan-100 text-cyan-800' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $product->selected ? __('Da') : __('Ne') }}
                                    </span>
                                @endif
                                @if (! $eligible && ! $product->selected)
                                    <div class="mt-1 text-[11px] text-amber-700">{{ $product->is_stale ? __('Zastarjelo') : (in_array((string) $product->match_status, ['conflict', 'ignored'], true) ? __('Konflikt ili ignorirano') : __('Nema mapirane kategorije')) }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="min-w-0">
                                    <div class="max-w-[30rem] font-semibold text-slate-900">{{ $product->name }}</div>
                                    <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 font-mono text-[11px] text-slate-500">
                                        <span>{{ __('M SAN') }}: {{ $product->external_code }}</span>
                                        @if ($product->part_number)
                                            <span>{{ __('Kat. broj') }}: {{ $product->part_number }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-slate-700">
                                <div class="font-medium">{{ $product->brand ?: '—' }}</div>
                                <div class="mt-1 max-w-[24rem] text-xs text-slate-500">
                                    @foreach ($product->categories->take(3) as $category)
                                        <span class="mr-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5">{{ $category->name }}</span>
                                    @endforeach
                                    @if ($product->categories->count() > 3)
                                        <span class="text-slate-400">+{{ $product->categories->count() - 3 }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-700">
                                <div>
                                    <span class="text-xs text-slate-500">{{ __('Nabavna') }}</span>
                                    <span class="ml-1 font-semibold">{{ $product->partner_price !== null ? number_format((float) $product->partner_price, 2, ',', '.').' '.$currency : '—' }}</span>
                                </div>
                                <div class="mt-1">
                                    <span class="text-xs text-slate-500">{{ __('MPC') }}</span>
                                    <span class="ml-1">{{ $product->recommended_retail_price !== null ? number_format((float) $product->recommended_retail_price, 2, ',', '.').' '.$currency : '—' }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($product->availability_level === null)
                                    <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ __('Nepoznato') }}</span>
                                @elseif ($product->availability_level > 0)
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">{{ __('Razina :level', ['level' => $product->availability_level]) }}</span>
                                @else
                                    <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-800">{{ __('Nema') }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-slate-700">
                                @if ($product->localProduct)
                                    <div class="font-medium">{{ $product->localProduct->code }}</div>
                                    <div class="mt-0.5 font-mono text-[11px] text-slate-500">{{ $product->localProduct->sku ?: __('bez SKU-a') }}</div>
                                @else
                                    <span class="text-xs text-slate-500">{{ __('Nije povezan') }}</span>
                                @endif
                                <div class="mt-1 text-[11px] text-slate-400">{{ $matchStatusLabel }}</div>
                            </td>
                            <td class="px-3 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $importStatusClass }}">
                                    {{ $importStatusLabel }}
                                </span>
                                @if ($product->last_imported_at)
                                    <div class="mt-1 text-[11px] text-slate-500">{{ $product->last_imported_at->format('d.m.Y. H:i') }}</div>
                                @endif
                                @if ($product->last_error)
                                    <div class="mt-1 max-w-[20rem] truncate text-[11px] text-rose-700" title="{{ $product->last_error }}">{{ $product->last_error }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-xs text-slate-600">
                                <div>{{ $product->last_seen_at?->format('d.m.Y. H:i') ?? '—' }}</div>
                                @if ($product->is_stale)
                                    <span class="mt-1 inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-700">{{ __('Zastarjelo') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-10 text-center text-sm text-slate-500">
                                {{ __('Nema M SAN artikala prema odabranim filtrima. Najprije pokrenite sinkronizaciju kataloga.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $products->links() }}</div>
    </section>
</div>

<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <div class="flex items-end justify-between gap-6">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Products</h1>
                <p class="mt-1 text-sm text-slate-600">Product foundation with feature-flag-aware query strategy.</p>
                <p class="mt-2 text-xs text-slate-500">Items per page: <span class="admin-chip">{{ $perPage }}</span></p>
            </div>
            <div class="grid items-end gap-3" style="grid-template-columns: 24rem 8rem;">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Search</label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Code, SKU, name or slug..."
                        class="admin-search-input w-full rounded-xl border px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Locale</label>
                    <select wire:model.live="locale" data-tom-select data-tom-no-search="1" class="admin-search-input admin-select w-full rounded-xl border px-3 py-2 text-sm lowercase">
                        @foreach ($adminLocaleOptions as $localeOption)
                            <option value="{{ $localeOption }}">{{ $localeOption }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @php
            $secondRowColumns = ($features['catalog_use_manufacturers'] ?? false)
                ? '7rem 7rem minmax(16rem, 1fr) minmax(16rem, 1fr) 9rem 8rem 8rem'
                : '7rem 7rem minmax(24rem, 1fr) 9rem 8rem 8rem';
        @endphp
        <div class="mt-4 grid items-end gap-3" style="grid-template-columns: {{ $secondRowColumns }};">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">State</label>
                <select wire:model.live="stateFilter" data-tom-select data-tom-no-search="1" class="admin-search-input admin-select w-full rounded-xl border px-3 py-2 text-sm">
                    @foreach ($stateOptions as $stateKey => $stateLabel)
                        <option value="{{ $stateKey }}">{{ $stateLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Stock</label>
                <select wire:model.live="stockFilter" data-tom-select data-tom-no-search="1" class="admin-search-input admin-select w-full rounded-xl border px-3 py-2 text-sm">
                    @foreach ($stockOptions as $stockKey => $stockLabel)
                        <option value="{{ $stockKey }}">{{ $stockLabel }}</option>
                    @endforeach
                </select>
            </div>
            @if ($features['catalog_use_manufacturers'])
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Manufacturer</label>
                    <select wire:model.live="manufacturerFilter" data-tom-select class="admin-search-input admin-select w-full rounded-xl border px-3 py-2 text-sm">
                        <option value="all">All</option>
                        @foreach ($this->manufacturerOptions as $manufacturer)
                            @php $manufacturerTr = $manufacturer->translations->first(); @endphp
                            <option value="{{ $manufacturer->id }}">{{ $manufacturerTr?->name ?? $manufacturer->code }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Category</label>
                <select wire:model.live="categoryFilter" data-tom-select class="admin-search-input admin-select w-full rounded-xl border px-3 py-2 text-sm">
                    <option value="all">All</option>
                    @foreach ($this->categoryOptions as $category)
                        @php
                            $categoryTr = $category->translations->first();
                            $categoryLabel = $categoryTr?->name ?? $category->code;
                            $categoryPad = str_repeat('— ', max(0, (int) ($category->depth ?? 0)));
                        @endphp
                        <option value="{{ $category->id }}">{{ $categoryPad . $categoryLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Sort</label>
                <select wire:model.live="sortBy" data-tom-select data-tom-no-search="1" class="admin-search-input admin-select w-full rounded-xl border px-3 py-2 text-sm">
                    @foreach ($sortOptions as $sortKey => $sortLabel)
                        <option value="{{ $sortKey }}">{{ $sortLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="button" wire:click="clearFilters" class="w-full rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    Reset
                </button>
            </div>
            <div>
                <a href="{{ route('admin.products.create', ['locale' => $locale]) }}" class="block w-full rounded-xl bg-cyan-700 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-cyan-800">
                    Create
                </a>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <span class="admin-chip">Attributes: {{ $features['catalog_use_attributes'] ? 'ON' : 'OFF' }}</span>
            <span class="admin-chip">Options: {{ $features['catalog_use_options'] ? 'ON' : 'OFF' }}</span>
            <span class="admin-chip">Manufacturers: {{ $features['catalog_use_manufacturers'] ? 'ON' : 'OFF' }}</span>
            <span class="admin-chip">Actions: {{ ($features['catalog_use_actions'] ?? false) ? 'ON' : 'OFF' }}</span>
            <a href="{{ route('admin.settings.system.catalog-features') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Catalog Features</a>
            @if ($features['catalog_use_attributes'])
                <a href="{{ route('admin.attributes', ['locale' => $locale]) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Attributes</a>
            @endif
            @if ($features['catalog_use_options'])
                <a href="{{ route('admin.options', ['locale' => $locale]) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Options</a>
            @endif
            @if ($features['catalog_use_manufacturers'])
                <a href="{{ route('admin.manufacturers', ['locale' => $locale]) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Manufacturers</a>
            @endif
            @if ($features['catalog_use_actions'] ?? false)
                <a href="{{ route('admin.actions', ['locale' => $locale]) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Actions</a>
            @endif
        </div>
    </div>

    <div class="admin-panel admin-panel-soft p-5">
        <h2 class="admin-section-title">Items</h2>

        <div class="mt-4 overflow-x-auto">
            <table class="admin-items-table min-w-full text-sm">
                <thead class="text-slate-600">
                    <tr>
                        <th class="px-3 py-2 text-center font-semibold">Image</th>
                        <th class="px-3 py-2 text-left font-semibold">Product</th>
                        <th class="px-3 py-2 text-left font-semibold">Slug</th>
                        <th class="px-3 py-2 text-center font-semibold">Price</th>
                        <th class="px-3 py-2 text-center font-semibold">Stock</th>
                        <th class="px-3 py-2 text-center font-semibold">Categories</th>
                        <th class="px-3 py-2 text-center font-semibold">State</th>
                        <th class="px-3 py-2 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        @php
                            $tr = $row->translations->first();
                            $mainMedia = $row->getFirstMedia('product_main') ?: $row->getFirstMedia('product_gallery');
                            $mainThumb = $mainMedia
                                ? ($mainMedia->hasGeneratedConversion('thumb_100x100') ? $mainMedia->getUrl('thumb_100x100') : $mainMedia->getUrl())
                                : null;
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-center align-top">
                                @if ($mainThumb)
                                    <img
                                        src="{{ $mainThumb }}"
                                        alt="{{ $tr?->name ?? $row->code }}"
                                        class="mx-auto h-12 w-12 rounded-lg border border-slate-200 object-cover"
                                        loading="lazy"
                                    />
                                @else
                                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-500">
                                        n/a
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-slate-800">
                                <div class="font-medium">{{ $tr?->name ?? '(missing name)' }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $row->code }} @if($row->sku) / {{ $row->sku }} @endif
                                </div>
                                @if (($features['catalog_use_manufacturers'] ?? false) && $row->manufacturer)
                                    @php $manufacturerTr = $row->manufacturer->translations->first(); @endphp
                                    <div class="text-xs text-slate-500">
                                        Manufacturer: {{ $manufacturerTr?->name ?? $row->manufacturer->code }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-2 font-mono text-xs text-slate-700">{{ $tr?->slug ?? '-' }}</td>
                            <td class="px-3 py-2 text-center text-slate-700">{{ number_format((float) $row->base_price, 2) }}</td>
                            <td class="px-3 py-2 text-center text-slate-700">{{ $row->stock_qty }}</td>
                            <td class="px-3 py-2 text-center text-slate-700">
                                {{ $row->categories_count }}
                                @if ($features['catalog_use_attributes'])
                                    <div class="text-xs text-slate-500">Attr: {{ $row->attributes_count }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $row->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $row->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('admin.products.edit', ['product' => $row->id, 'locale' => $locale]) }}" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                    Edit
                                </a>
                                @if ($features['catalog_use_options'])
                                    <a href="{{ route('admin.products.options', ['product' => $row->id, 'locale' => $locale]) }}" class="ml-1 rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                        Option Values
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-8 text-center text-sm text-slate-500">No products yet. Seed or create products next.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $rows->links() }}
        </div>
    </div>
</div>

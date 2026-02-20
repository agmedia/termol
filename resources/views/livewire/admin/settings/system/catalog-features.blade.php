<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <h1 class="text-xl font-semibold tracking-tight">Catalog Features</h1>
        <p class="mt-2 text-sm text-slate-600">Settings namespace: <code>Settings/System/CatalogFeatures</code></p>
        <p class="mt-2 text-xs text-slate-500">Disable features you do not use to keep admin/front queries lighter.</p>
    </div>

    <div class="admin-panel admin-form-panel p-6">
        <form wire:submit="save" class="admin-form mt-1 space-y-4">
            @php
                $items = [
                    'catalog_use_api' => [
                        'title' => 'Use Wholesale API',
                        'description' => 'Enable API settings page and `/api/v1/wholesale/*` endpoints.',
                    ],
                    'catalog_use_blog' => [
                        'title' => 'Use Blog',
                        'description' => 'Enable blog module in Content section and related front routes.',
                    ],
                    'catalog_use_attributes' => [
                        'title' => 'Use Attributes',
                        'description' => 'Additional product characteristics (e.g. material, power, weight).',
                    ],
                    'catalog_use_options' => [
                        'title' => 'Use Options',
                        'description' => 'Selectable values like size/color shown in product page/cart.',
                    ],
                    'catalog_use_manufacturers' => [
                        'title' => 'Use Manufacturers',
                        'description' => 'Brand/manufacturer relations and listings.',
                    ],
                    'catalog_use_actions' => [
                        'title' => 'Use Actions & Discounts',
                        'description' => 'Promotions: percentages, fixed discounts, and advanced action rules.',
                    ],
                    'catalog_use_mobile_pwa' => [
                        'title' => 'Use Mobile PWA View',
                        'description' => 'When disabled, mobile devices will use desktop responsive storefront instead of mobile/PWA templates.',
                    ],
                ];
            @endphp

            <div>
                <p class="admin-section-title">Product Data Model Features</p>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    @foreach ($items as $key => $item)
                        @php $enabled = (bool) ($form[$key] ?? false); @endphp
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <strong class="block text-slate-900">{{ $item['title'] }}</strong>
                                    <p class="mt-1 text-sm text-slate-600">{{ $item['description'] }}</p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="toggle('{{ $key }}')"
                                    class="admin-switch"
                                    data-state="{{ $enabled ? 'on' : 'off' }}"
                                    role="switch"
                                    aria-checked="{{ $enabled ? 'true' : 'false' }}"
                                    aria-label="{{ $item['title'] }}"
                                >
                                    <span class="admin-switch-track">
                                        <span class="admin-switch-thumb"></span>
                                    </span>
                                    <span class="admin-switch-label">{{ $enabled ? 'On' : 'Off' }}</span>
                                </button>
                            </div>
                            <p class="mt-2 text-xs font-semibold uppercase tracking-[0.12em] {{ $enabled ? 'text-emerald-700' : 'text-slate-500' }}">
                                {{ $enabled ? 'Enabled' : 'Disabled' }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                Recommendation: keep <code>Options</code> off unless needed.
                Enable it when product SKU/price/stock depends on selected option values.
            </div>

            <div class="admin-form-actions flex items-center gap-2">
                <button type="submit" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">Save</button>
                <button type="button" wire:click="resetToDefaults" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Reset Defaults</button>
            </div>
        </form>
    </div>
</div>

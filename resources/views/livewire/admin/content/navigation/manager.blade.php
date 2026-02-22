<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Main Navigation</h1>
                <p class="mt-1 text-sm text-slate-600">Konfiguriraj glavni meni za desktop header i mobile slide navigaciju.</p>
            </div>

            <div class="flex flex-wrap items-end gap-2">
                <div class="w-28">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Locale</label>
                    <select wire:model.live="locale" data-tom-select data-tom-no-search="1" class="admin-search-input admin-select w-full rounded-xl border px-3 py-2 text-sm lowercase">
                        @foreach ($adminLocaleOptions as $localeOption)
                            <option value="{{ $localeOption }}">{{ $localeOption }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="button" wire:click="addCategoryItem" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">+ Kategorija</button>
                <button type="button" wire:click="addPageItem" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">+ Page</button>
                <button type="button" wire:click="addBlogItem" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">+ Blog</button>
                <button type="button" wire:click="addContactItem" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">+ Kontakt</button>
                <button type="button" wire:click="addCustomItem" class="rounded-xl border border-cyan-200 bg-cyan-50 px-3 py-2 text-xs font-semibold text-cyan-800 hover:bg-cyan-100">+ Custom Link</button>
            </div>
        </div>
    </div>

    <div class="admin-panel admin-panel-soft p-5">
        <h2 class="admin-section-title">Items</h2>

        @if (empty($form['items']))
            <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">
                Nema stavki. Dodaj kategoriju, page ili custom link.
            </div>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($form['items'] as $index => $item)
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="grid gap-3 lg:grid-cols-[8rem_1fr_1fr_1fr_auto] lg:items-end">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Tip</label>
                                <select wire:model.live="form.items.{{ $index }}.type" data-tom-select data-tom-no-search="1" class="admin-search-input admin-select w-full rounded-xl border px-3 py-2 text-sm">
                                    <option value="category">Category</option>
                                    <option value="page">Page</option>
                                    <option value="blog">Blog</option>
                                    <option value="contact">Contact</option>
                                    <option value="custom">Custom URL</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Naziv (override)</label>
                                <input type="text" wire:model.live="form.items.{{ $index }}.label" class="admin-search-input w-full rounded-xl border px-3 py-2 text-sm" placeholder="Automatski ako prazno" />
                                @error('form.items.'.$index.'.label') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            @if (($item['type'] ?? '') === 'category')
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Kategorija</label>
                                    <select wire:model.live="form.items.{{ $index }}.category_id" data-tom-select class="admin-search-input admin-select w-full rounded-xl border px-3 py-2 text-sm">
                                        <option value="0">Odaberi kategoriju</option>
                                        @foreach ($categoryOptions as $option)
                                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('form.items.'.$index.'.category_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                            @elseif (($item['type'] ?? '') === 'page')
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Page</label>
                                    <select wire:model.live="form.items.{{ $index }}.page_id" data-tom-select class="admin-search-input admin-select w-full rounded-xl border px-3 py-2 text-sm">
                                        <option value="0">Odaberi stranicu</option>
                                        @foreach ($pageOptions as $option)
                                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('form.items.'.$index.'.page_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                            @elseif (($item['type'] ?? '') === 'custom')
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">URL</label>
                                    <input type="text" wire:model.live="form.items.{{ $index }}.url" class="admin-search-input w-full rounded-xl border px-3 py-2 text-sm" placeholder="/faq ili https://..." />
                                    @error('form.items.'.$index.'.url') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                            @else
                                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                                    Koristi sistemsku rutu (nema dodatnog URL-a).
                                </div>
                            @endif

                            <div class="flex flex-wrap justify-end gap-1">
                                <button type="button" wire:click="moveUp({{ $index }})" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">↑</button>
                                <button type="button" wire:click="moveDown({{ $index }})" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">↓</button>
                                <button type="button" wire:click="removeItem({{ $index }})" class="rounded-lg border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">Ukloni</button>
                            </div>
                        </div>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                                <input type="checkbox" wire:model.live="form.items.{{ $index }}.is_active" class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-500" />
                                Aktivno
                            </label>

                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                                <input type="checkbox" wire:model.live="form.items.{{ $index }}.show_dropdown" class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-500" />
                                Dropdown podkategorije
                            </label>

                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                                <input type="checkbox" wire:model.live="form.items.{{ $index }}.open_in_new_tab" class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-500" />
                                Otvori u novom tabu
                            </label>

                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Sort</label>
                                <input type="number" min="0" max="9999" wire:model.live="form.items.{{ $index }}.sort_order" class="admin-search-input w-full rounded-xl border px-3 py-2 text-sm" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-5 flex items-center justify-end">
            <button type="button" wire:click="save" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">Spremi Navigation</button>
        </div>
    </div>
</div>

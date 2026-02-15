<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Content / Blocks</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $this->isEdit ? 'Edit Block' : 'Create Block' }}</h1>
                <p class="mt-2 text-sm text-slate-600">Simple section block setup. Use quick fields for products/blog data blocks.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="admin-chip">Locale: {{ $form['locale'] }}</span>
                <button type="button" wire:click="backToList" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Back to List</button>
            </div>
        </div>
    </div>

    <div class="admin-panel admin-form-panel p-6">
        <form wire:submit="save" class="admin-form space-y-4">
            <div class="grid gap-3 md:grid-cols-4">
                <div class="md:col-span-1">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Code</label>
                    <input type="text" wire:model="form.code" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-mono outline-none ring-cyan-200 focus:ring" />
                    @error('form.code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Name</label>
                    <input type="text" wire:model="form.name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring" />
                    @error('form.name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-1">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Type</label>
                    <select wire:model="form.type" data-tom-select data-tom-visual="block-type" data-tom-no-search="1" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring">
                        @foreach ($types as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Type Visual Presets</label>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($types as $typeKey => $typeLabel)
                        <button
                            type="button"
                            wire:click="$set('form.type', '{{ $typeKey }}')"
                            class="rounded-xl border p-2 text-left transition {{ ($form['type'] ?? null) === $typeKey ? 'border-cyan-300 bg-cyan-50' : 'border-slate-200 bg-white hover:bg-slate-50' }}"
                        >
                            @include('admin.content.partials.block-type-preview', ['type' => $typeKey, 'size' => 'sm'])
                            <p class="mt-2 text-xs font-semibold uppercase tracking-[0.1em] {{ ($form['type'] ?? null) === $typeKey ? 'text-cyan-800' : 'text-slate-600' }}">
                                {{ $typeLabel }}
                            </p>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Locale</label>
                    <select wire:model.live="form.locale" data-tom-select data-tom-no-search="1" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm lowercase outline-none ring-cyan-200 focus:ring">
                          @foreach ($adminLocaleOptions as $localeOption)
                                <option value="{{ $localeOption }}">{{ $localeOption }}</option>
                            @endforeach
                    </select>
                    @error('form.locale') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 flex items-end">
                    <button
                        type="button"
                        wire:click="$toggle('form.is_active')"
                        class="admin-switch"
                        data-state="{{ $form['is_active'] ? 'on' : 'off' }}"
                        role="switch"
                        aria-checked="{{ $form['is_active'] ? 'true' : 'false' }}"
                        aria-label="Toggle block active state"
                    >
                        <span class="admin-switch-track"><span class="admin-switch-thumb"></span></span>
                        <span class="admin-switch-label">{{ $form['is_active'] ? 'Active' : 'Inactive' }}</span>
                    </button>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Title</label>
                    <input type="text" wire:model="form.title" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Subtitle</label>
                    <input type="text" wire:model="form.subtitle" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring" />
                </div>
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between gap-2">
                    <label for="content-block-body-html" class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Body HTML (Ace-ready)</label>
                    <button
                        type="button"
                        data-ace-open
                        data-ace-target="content-block-body-html"
                        data-ace-label="Content Block Body HTML"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        Open in Ace
                    </button>
                </div>
                <textarea id="content-block-body-html" rows="6" wire:model="form.body_html" data-ace-inline class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring" placeholder="HTML/RTE content"></textarea>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">CTA Label</label>
                    <input type="text" wire:model="form.cta_label" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">CTA URL</label>
                    <input type="text" wire:model="form.cta_url" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring" />
                </div>
            </div>

            @if (($form['type'] ?? '') === 'products_carousel')
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Quick Setup: Products Carousel</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Source</label>
                            <select wire:model="form.quick_source" data-tom-select data-tom-no-search="1" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring">
                                <option value="manual">Manual IDs</option>
                                <option value="query">Query filters</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Limit</label>
                            <input type="number" min="1" max="30" wire:model="form.quick_limit" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Sort</label>
                            <select wire:model="form.quick_sort" data-tom-select data-tom-no-search="1" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring">
                                <option value="newest">Newest</option>
                                <option value="price_asc">Price: low to high</option>
                                <option value="price_desc">Price: high to low</option>
                                <option value="name">Name</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Manual Product IDs</label>
                            <textarea rows="3" wire:model="form.quick_manual_ids" placeholder="12, 44, 89" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring"></textarea>
                            <p class="mt-1 text-xs text-slate-500">Used when Source = Manual IDs.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Query Category IDs / Manufacturer IDs</label>
                            <div class="grid gap-2 md:grid-cols-2">
                                <input type="text" wire:model="form.quick_category_ids" placeholder="Categories: 3, 8" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring" />
                                <input type="text" wire:model="form.quick_manufacturer_ids" placeholder="Manufacturers: 2, 7" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring" />
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Used when Source = Query filters.</p>
                        </div>
                    </div>
                </div>
            @endif

            @if (($form['type'] ?? '') === 'blog_grid_3')
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Quick Setup: Blog Grid</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Source</label>
                            <select wire:model="form.quick_blog_source" data-tom-select data-tom-no-search="1" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring">
                                <option value="manual">Manual IDs</option>
                                <option value="query">Query filters</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Limit</label>
                            <input type="number" min="1" max="12" wire:model="form.quick_blog_limit" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Sort</label>
                            <select wire:model="form.quick_blog_sort" data-tom-select data-tom-no-search="1" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring">
                                <option value="newest">Newest</option>
                                <option value="featured">Featured first</option>
                                <option value="title">Title</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Manual Blog Post IDs</label>
                            <textarea rows="3" wire:model="form.quick_blog_manual_ids" placeholder="5, 7, 11" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring"></textarea>
                            <p class="mt-1 text-xs text-slate-500">Used when Source = Manual IDs.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Query Blog Category IDs</label>
                            <input type="text" wire:model="form.quick_blog_category_ids" placeholder="10, 12" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none ring-cyan-200 focus:ring" />
                            <p class="mt-1 text-xs text-slate-500">Used when Source = Query filters.</p>
                        </div>
                    </div>
                </div>
            @endif

            <details class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <summary class="cursor-pointer text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">Advanced JSON (optional)</summary>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Block Payload JSON</label>
                        <textarea rows="5" wire:model="form.payload_text" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs outline-none ring-cyan-200 focus:ring"></textarea>
                        @error('form.payload_text') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Translation Payload JSON</label>
                        <textarea rows="5" wire:model="form.translation_payload_text" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs outline-none ring-cyan-200 focus:ring"></textarea>
                        @error('form.translation_payload_text') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </details>

            <div class="flex items-center gap-2 pt-1">
                <button type="submit" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">
                    {{ $this->isEdit ? 'Update' : 'Create' }}
                </button>
                <button type="button" wire:click="backToList" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

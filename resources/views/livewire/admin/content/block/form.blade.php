<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Content / Blocks v2</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $this->isEdit ? 'Edit Block' : 'Create Block' }}</h1>
                <p class="mt-2 text-sm text-slate-600">Simple builder: choose type, set slot, pick items, edit Blade template, publish.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="admin-chip">Locale: {{ $form['locale'] }}</span>
                <button type="button" wire:click="backToList" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Back to List</button>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="admin-panel admin-form-panel p-6">
            <p class="admin-section-title">Core</p>

            <div class="mt-4 grid gap-3 md:grid-cols-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Code</label>
                    <input type="text" wire:model="form.code" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-mono" />
                    @error('form.code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Name</label>
                    <input type="text" wire:model="form.name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    @error('form.name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Type</label>
                    <select wire:model.live="form.type" data-tom-select data-tom-no-search="1" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($types as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}" @selected(($form['type'] ?? '') === $typeKey)>{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                    @error('form.type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Locale</label>
                    <select wire:model.live="form.locale" data-tom-select data-tom-no-search="1" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm lowercase">
                        @foreach ($adminLocaleOptions as $localeOption)
                            <option value="{{ $localeOption }}">{{ $localeOption }}</option>
                        @endforeach
                    </select>
                    @error('form.locale') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-end">
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
        </div>

        <div class="admin-panel admin-form-panel p-6">
            <p class="admin-section-title">Slot (Placement)</p>

            <div class="mt-4 grid gap-3 md:grid-cols-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Placement</label>
                    <select wire:model="form.slot_placement" data-tom-select data-tom-no-search="1" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($placements as $placementKey => $placementLabel)
                            <option value="{{ $placementKey }}" @selected(($form['slot_placement'] ?? '') === $placementKey)>{{ $placementLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Target Type</label>
                    <select wire:model="form.slot_target_type" data-tom-select data-tom-no-search="1" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($targetTypes as $targetTypeKey => $targetTypeLabel)
                            <option value="{{ $targetTypeKey }}" @selected((string) ($form['slot_target_type'] ?? '') === (string) $targetTypeKey)>{{ $targetTypeLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Target Ref</label>
                    <input type="text" wire:model="form.slot_target_ref" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="slug or id" />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Sort Order</label>
                    <input type="number" min="0" wire:model="form.slot_sort_order" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                </div>
            </div>

            <div class="mt-3 grid gap-3 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Starts At</label>
                    <input type="datetime-local" wire:model="form.slot_starts_at" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Ends At</label>
                    <input type="datetime-local" wire:model="form.slot_ends_at" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <div class="flex items-end">
                    <button
                        type="button"
                        wire:click="$toggle('form.slot_is_active')"
                        class="admin-switch"
                        data-state="{{ $form['slot_is_active'] ? 'on' : 'off' }}"
                        role="switch"
                        aria-checked="{{ $form['slot_is_active'] ? 'true' : 'false' }}"
                        aria-label="Toggle slot active state"
                    >
                        <span class="admin-switch-track"><span class="admin-switch-thumb"></span></span>
                        <span class="admin-switch-label">{{ $form['slot_is_active'] ? 'Slot Active' : 'Slot Inactive' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="admin-panel admin-form-panel p-6">
                <p class="admin-section-title">Content</p>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Title</label>
                        <input type="text" wire:model="form.title" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Subtitle</label>
                        <input type="text" wire:model="form.subtitle" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                </div>

                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">CTA Label</label>
                        <input type="text" wire:model="form.cta_label" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">CTA URL</label>
                        <input type="text" wire:model="form.cta_url" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="/shop or https://..." />
                    </div>
                </div>

                <p class="mt-3 text-xs text-slate-500">Main markup/content is edited in the Blade Template section below (Ace).</p>
            </div>

            <div class="admin-panel admin-form-panel p-6">
                <p class="admin-section-title">Style & Background</p>

                <div class="mt-4">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Custom Classes</label>
                    <input type="text" wire:model="form.custom_classes" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="extra utility classes" />
                </div>

                <div class="mt-3">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Background CSS</label>
                    <textarea rows="4" wire:model="form.bg_css" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs" placeholder="background-color:#0f172a; color:white;"></textarea>
                    <p class="mt-1 text-xs text-slate-500">If a background image is uploaded, it is applied first, then this CSS is appended.</p>
                </div>
            </div>
        </div>

        @if ($this->isItemBlock)
            <div class="admin-panel admin-form-panel p-6">
                <p class="admin-section-title">Selected Items</p>
                <p class="mt-1 text-xs text-slate-500">Choose items and order them. No JSON IDs needed.</p>

                <div class="mt-4 grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Available</label>
                        <select wire:model="pickerItemId" data-tom-select class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Select item...</option>
                            @foreach ($this->itemOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" wire:click="addSelectedItem" class="h-10 rounded-xl bg-cyan-700 px-4 text-sm font-semibold text-white hover:bg-cyan-800">Add Item</button>
                </div>

                <div class="mt-4 space-y-2">
                    @forelse ($this->selectedItems as $row)
                        <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                            <div class="text-sm text-slate-800">{{ $row['label'] }}</div>
                            <div class="inline-flex items-center gap-1">
                                <button type="button" wire:click="moveSelectedItemUp({{ $row['index'] }})" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">Up</button>
                                <button type="button" wire:click="moveSelectedItemDown({{ $row['index'] }})" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">Down</button>
                                <button type="button" wire:click="removeSelectedItem({{ $row['id'] }})" class="rounded-lg border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">Remove</button>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">No items selected.</div>
                    @endforelse
                    @error('form.selected_item_ids') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

        <div class="admin-panel admin-form-panel p-6">
            <p class="admin-section-title">Blade Template (Per Block File)</p>
            <p class="mt-1 text-xs text-slate-500">Saved to <code>resources/views/front/content-blocks/instances/{{ $form['code'] ?: 'block-code' }}.blade.php</code>. This block only.</p>

            <div class="mt-3 mb-2 flex flex-wrap items-center gap-2">
                <button type="button" wire:click="loadTemplatePreset" class="rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-1.5 text-xs font-semibold text-cyan-800 hover:bg-cyan-100">Load Default For Type</button>
                <button
                    type="button"
                    data-ace-open
                    data-ace-target="content-block-template-blade"
                    data-ace-label="Content Block Blade Template"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                >
                    Open in Ace
                </button>
            </div>

            <textarea id="content-block-template-blade" rows="16" wire:model="form.template_body" data-ace-inline class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs"></textarea>
            @error('form.template_body') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        @if ($blockId)
            <livewire:admin.media.manager
                :model-class="\App\Models\Content\ContentBlock::class"
                :model-id="$blockId"
                :locale="$form['locale']"
                :wire:key="'content-block-media-manager-'.($blockId ?? 'new').'-'.$form['locale']"
            />
        @endif

        <div class="admin-form-actions flex items-center gap-2 pt-2">
            <button type="submit" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">
                {{ $this->isEdit ? 'Update Block' : 'Create Block' }}
            </button>
            <button type="button" wire:click="backToList" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Cancel
            </button>
        </div>
    </form>
</div>

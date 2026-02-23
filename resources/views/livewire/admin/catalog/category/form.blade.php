<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Catalog / Categories</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $isEdit ? 'Edit Category' : 'Create Category' }}</h1>
                <p class="mt-2 text-sm text-slate-600">Dedicated edit page for large trees and cleaner workflow.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="admin-chip">Scope: {{ str($form['scope'])->replace('_', ' ')->title() }}</span>
                <span class="admin-chip">Locale: {{ $form['locale'] }}</span>
                <button type="button" wire:click="backToList" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Back to List</button>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="admin-panel admin-form-panel p-6">
            <p class="admin-section-title">Core Data</p>

            <div class="mt-4 grid gap-3" style="grid-template-columns: repeat(12, minmax(0, 1fr));">
                <div style="grid-column: span 2;">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Scope</label>
                    <select wire:model.live="form.scope" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($this->scopeOptions as $scope)
                            <option value="{{ $scope }}">{{ str($scope)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>
                    @error('form.scope') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div style="grid-column: span 4;">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Parent Category</label>
                    <select wire:model="form.parent_id" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">(Root)</option>
                        @foreach ($this->parentOptions as $parent)
                            @php
                                $parentTranslation = $parent->translations->first();
                                $parentName = $parentTranslation?->name ?? ($parent->code ?: 'Category #'.$parent->id);
                                $pad = str_repeat('— ', max(0, (int) $parent->depth));
                            @endphp
                            <option value="{{ $parent->id }}">{{ $pad.$parentName }}</option>
                        @endforeach
                    </select>
                    @error('form.parent_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div style="grid-column: span 2;">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Code</label>
                    <input type="text" wire:model="form.code" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-mono" />
                    @error('form.code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div style="grid-column: span 2;">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Locale</label>
                    <select wire:model.live="form.locale" data-tom-select data-tom-no-search="1" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm lowercase">
                        @foreach ($this->localeOptions as $localeOption)
                            <option value="{{ $localeOption }}">{{ $localeOption }}</option>
                        @endforeach
                    </select>
                    @error('form.locale') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div style="grid-column: span 2;">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Sort Order</label>
                    <input type="number" wire:model="form.sort_order" min="0" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    @error('form.sort_order') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    wire:click="$toggle('form.is_active')"
                    class="admin-switch"
                    data-state="{{ $form['is_active'] ? 'on' : 'off' }}"
                    role="switch"
                    aria-checked="{{ $form['is_active'] ? 'true' : 'false' }}"
                    aria-label="Toggle category active state"
                >
                    <span class="admin-switch-track"><span class="admin-switch-thumb"></span></span>
                    <span class="admin-switch-label">{{ $form['is_active'] ? 'Active' : 'Inactive' }}</span>
                </button>

                <button
                    type="button"
                    wire:click="$toggle('form.show_in_menu')"
                    class="admin-switch"
                    data-state="{{ $form['show_in_menu'] ? 'on' : 'off' }}"
                    role="switch"
                    aria-checked="{{ $form['show_in_menu'] ? 'true' : 'false' }}"
                    aria-label="Toggle category menu visibility"
                >
                    <span class="admin-switch-track"><span class="admin-switch-thumb"></span></span>
                    <span class="admin-switch-label">{{ $form['show_in_menu'] ? 'In Menu' : 'Hidden' }}</span>
                </button>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="admin-panel admin-form-panel p-6">
                <p class="admin-section-title">Content</p>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Name</label>
                        <input type="text" wire:model="form.name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                        @error('form.name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Slug</label>
                            <button type="button" wire:click="generateSlug" class="text-xs font-semibold text-slate-600 hover:text-slate-900">Generate</button>
                        </div>
                        <input type="text" wire:model="form.slug" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm lowercase" />
                        @error('form.slug') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-3">
                    <label for="category-description-html" class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Description</label>
                    <textarea id="category-description-html" rows="6" wire:model.live.debounce.300ms="form.description" data-quill-editor class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
                </div>
            </div>

            <div class="admin-panel admin-form-panel p-6">
                <p class="admin-section-title">SEO & Payload</p>

                <div class="mt-4">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Meta Title</label>
                    <input type="text" wire:model="form.meta_title" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    @error('form.meta_title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-3">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Meta Description</label>
                    <textarea rows="3" wire:model="form.meta_description" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
                    @error('form.meta_description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-3">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Category Payload JSON</label>
                    <textarea rows="5" wire:model="form.payload_text" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs"></textarea>
                    @error('form.payload_text') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-3">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Translation Payload JSON</label>
                    <textarea rows="5" wire:model="form.translation_payload_text" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs"></textarea>
                    @error('form.translation_payload_text') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <livewire:admin.media.manager
            :model-class="\App\Models\Catalog\Category\Category::class"
            :model-id="$categoryId"
            :locale="$form['locale']"
            :wire:key="'category-media-manager-'.($categoryId ?? 'new').'-'.$form['locale']"
        />

        <div class="admin-panel admin-form-panel p-6">
            <div class="admin-form-actions flex items-center gap-2 pt-0">
                <button type="submit" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">
                    {{ $isEdit ? 'Update Category' : 'Create Category' }}
                </button>
                <button type="button" wire:click="backToList" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    Cancel
                </button>
            </div>
        </div>
    </form>
</div>

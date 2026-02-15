<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Catalog / Actions</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $isEdit ? 'Edit Action / Discount' : 'Create Action / Discount' }}</h1>
                <p class="mt-2 text-sm text-slate-600">Use simple action types now, keep advanced logic in payload for future execution layer.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="admin-chip">Locale: {{ $form['locale'] }}</span>
                <button type="button" wire:click="backToList" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Back to List</button>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="admin-panel admin-form-panel p-6">
            <p class="admin-section-title">Core Data</p>

            <div class="mt-4 grid gap-3" style="grid-template-columns: repeat(12, minmax(0, 1fr));">
                <div style="grid-column: span 3;">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Code</label>
                    <input type="text" wire:model="form.code" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-mono" />
                    @error('form.code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div style="grid-column: span 2;">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Locale</label>
                    <select wire:model.live="form.locale" data-tom-select data-tom-no-search="1" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm lowercase">
                        @foreach ($adminLocaleOptions as $localeOption)
                            <option value="{{ $localeOption }}">{{ $localeOption }}</option>
                        @endforeach
                    </select>
                    @error('form.locale') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div style="grid-column: span 3;">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Scope</label>
                    <select wire:model.live="form.scope" data-tom-select data-tom-no-search="1" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($scopeOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('form.scope') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div style="grid-column: span 4;">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Type</label>
                    <select wire:model.live="form.type" data-tom-select class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($typeOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('form.type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Title</label>
                    <input type="text" wire:model="form.title" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    @error('form.title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Badge</label>
                    <input type="text" wire:model="form.badge" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Optional short label" />
                    @error('form.badge') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
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
                    aria-label="Toggle active state"
                >
                    <span class="admin-switch-track"><span class="admin-switch-thumb"></span></span>
                    <span class="admin-switch-label">{{ $form['is_active'] ? 'Active' : 'Inactive' }}</span>
                </button>

                <button
                    type="button"
                    wire:click="$toggle('form.is_exclusive')"
                    class="admin-switch"
                    data-state="{{ $form['is_exclusive'] ? 'on' : 'off' }}"
                    role="switch"
                    aria-checked="{{ $form['is_exclusive'] ? 'true' : 'false' }}"
                    aria-label="Toggle exclusive lock"
                >
                    <span class="admin-switch-track"><span class="admin-switch-thumb"></span></span>
                    <span class="admin-switch-label">{{ $form['is_exclusive'] ? 'Lock' : 'Normal' }}</span>
                </button>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="admin-panel admin-form-panel p-6">
                <p class="admin-section-title">Logic</p>

                <div class="mt-4 grid gap-3" style="grid-template-columns: repeat(12, minmax(0, 1fr));">
                    <div style="grid-column: span 4;">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Discount Value</label>
                        <input type="number" min="0" step="0.01" wire:model="form.discount_value" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                        @error('form.discount_value') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div style="grid-column: span 4;">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Priority</label>
                        <input type="number" min="0" wire:model="form.priority" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                        @error('form.priority') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div style="grid-column: span 4;">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Min Subtotal</label>
                        <input type="number" min="0" step="0.01" wire:model="form.min_subtotal" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                        @error('form.min_subtotal') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-3 grid gap-3" style="grid-template-columns: repeat(12, minmax(0, 1fr));">
                    <div style="grid-column: span 4;">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Buy Qty</label>
                        <input type="number" min="1" wire:model="form.buy_qty" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                        @error('form.buy_qty') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div style="grid-column: span 4;">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Get Qty</label>
                        <input type="number" min="1" wire:model="form.get_qty" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                        @error('form.get_qty') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div style="grid-column: span 4;">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Gift Product</label>
                        <select wire:model="form.gift_product_id" data-tom-select class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="">None</option>
                            @foreach ($this->giftProductOptions as $row)
                                @php $tr = $row->translations->first(); @endphp
                                <option value="{{ $row->id }}">{{ $tr?->name ?? $row->code }}</option>
                            @endforeach
                        </select>
                        @error('form.gift_product_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                    `Buy X Get Y` and `Gift On Amount` are fully storable now; execution logic can be expanded in checkout/cart services later.
                </div>
            </div>

            <div class="admin-panel admin-form-panel p-6">
                <p class="admin-section-title">Schedule & Usage</p>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Start At</label>
                        <input type="datetime-local" wire:model="form.starts_at" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                        @error('form.starts_at') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">End At</label>
                        <input type="datetime-local" wire:model="form.ends_at" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                        @error('form.ends_at') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Coupon Code</label>
                        <input type="text" wire:model="form.coupon_code" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-mono uppercase" />
                        @error('form.coupon_code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Usage Limit</label>
                        <input type="number" min="1" wire:model="form.usage_limit" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                        @error('form.usage_limit') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-3">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Usage Limit Per User</label>
                    <input type="number" min="1" wire:model="form.usage_limit_per_user" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    @error('form.usage_limit_per_user') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="admin-panel admin-form-panel p-6">
                <p class="admin-section-title">Targeting</p>

                <div class="mt-4">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Target Type</label>
                    <select wire:model.live="form.target_type" data-tom-select data-tom-no-search="1" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($targetOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('form.target_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                @if ($form['target_type'] !== \App\Models\Catalog\Action\CatalogAction::TARGET_ALL)
                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Target Search</label>
                        <input type="text" wire:model.live.debounce.300ms="targetSearch" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Search target rows..." />
                    </div>
                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Targets</label>
                        <select wire:model="form.target_ids" multiple size="9" class="admin-multiselect w-full rounded-xl border border-slate-300 text-sm">
                            @foreach ($this->targetOptions as $row)
                                @if ($form['target_type'] === \App\Models\Catalog\Action\CatalogAction::TARGET_PRODUCT)
                                    @php $tr = $row->translations->first(); @endphp
                                    <option value="{{ $row->id }}">{{ $tr?->name ?? '(missing name)' }} [{{ $row->code }}]</option>
                                @elseif ($form['target_type'] === \App\Models\Catalog\Action\CatalogAction::TARGET_CATEGORY)
                                    @php
                                        $tr = $row->translations->first();
                                        $label = $tr?->name ?? $row->code;
                                        $pad = str_repeat('— ', max(0, (int) ($row->depth ?? 0)));
                                    @endphp
                                    <option value="{{ $row->id }}">{{ $pad.$label }}</option>
                                @elseif ($form['target_type'] === \App\Models\Catalog\Action\CatalogAction::TARGET_MANUFACTURER)
                                    @php $tr = $row->translations->first(); @endphp
                                    <option value="{{ $row->id }}">{{ $tr?->name ?? $row->code }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('form.target_ids') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        @error('form.target_ids.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>

            <div class="admin-panel admin-form-panel p-6">
                <p class="admin-section-title">Audience</p>

                <div class="mt-4">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Audience Type</label>
                    <select wire:model.live="form.audience_type" data-tom-select data-tom-no-search="1" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($audienceOptions as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('form.audience_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                @if ($form['audience_type'] === \App\Models\Catalog\Action\CatalogAction::AUDIENCE_USER_GROUP)
                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">User Group</label>
                        <select wire:model="form.customer_group_id" data-tom-select class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Select user group</option>
                            @foreach ($this->customerGroupOptions as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                        @error('form.customer_group_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if ($form['audience_type'] === \App\Models\Catalog\Action\CatalogAction::AUDIENCE_USER)
                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">User Search</label>
                        <input type="text" wire:model.live.debounce.300ms="userSearch" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Name or email..." />
                    </div>
                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">User</label>
                        <select wire:model="form.user_id" data-tom-select class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Select user</option>
                            @foreach ($this->userOptions as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        @error('form.user_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>
        </div>

        <div class="admin-panel admin-form-panel p-6">
            <p class="admin-section-title">Description & Payload</p>

            <div class="mt-4">
                <label for="catalog-action-description-html" class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Description</label>
                <textarea id="catalog-action-description-html" rows="5" wire:model="form.description" data-quill-editor class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
                @error('form.description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Action Payload JSON</label>
                    <textarea rows="6" wire:model="form.payload_text" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs"></textarea>
                    @error('form.payload_text') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Translation Payload JSON</label>
                    <textarea rows="6" wire:model="form.translation_payload_text" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs"></textarea>
                    @error('form.translation_payload_text') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="admin-form-actions flex items-center gap-2 pt-2">
            <button type="submit" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">
                {{ $isEdit ? 'Update Action' : 'Create Action' }}
            </button>
            <button type="button" wire:click="backToList" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Cancel
            </button>
        </div>
    </form>
</div>

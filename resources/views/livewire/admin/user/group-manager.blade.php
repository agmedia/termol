<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">User Groups</h1>
                <p class="mt-1 text-sm text-slate-600">Manage segmentation groups for audience rules, pricing, and campaigns.</p>
                <p class="mt-2 text-xs text-slate-500">Items per page: <span class="admin-chip">{{ $perPage }}</span></p>
            </div>
            <div class="w-full sm:w-80">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Search</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Code, name or description..."
                    class="admin-search-input w-full rounded-xl border px-3 py-2 text-sm"
                />
            </div>
        </div>
    </div>

    <div class="admin-stack">
        <div class="admin-panel admin-form-panel p-6" style="order:2;">
            <h2 class="admin-section-title">{{ $editingId ? 'Edit Group' : 'Create Group' }}</h2>

            <form wire:submit="save" class="admin-form mt-4 space-y-4">
                <div class="grid gap-3" style="grid-template-columns: repeat(12, minmax(0, 1fr));">
                    <div style="grid-column: span 3;">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Code</label>
                        <input type="text" wire:model="form.code" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                        @error('form.code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div style="grid-column: span 4;">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Name</label>
                        <input type="text" wire:model="form.name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                        @error('form.name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div style="grid-column: span 2;">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Sort</label>
                        <input type="number" wire:model="form.sort_order" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <div style="grid-column: span 3;">
                        <div class="mt-6 flex flex-wrap gap-3">
                            <button
                                type="button"
                                wire:click="$toggle('form.is_active')"
                                class="admin-switch"
                                data-state="{{ $form['is_active'] ? 'on' : 'off' }}"
                                role="switch"
                            >
                                <span class="admin-switch-track"><span class="admin-switch-thumb"></span></span>
                                <span class="admin-switch-label">{{ $form['is_active'] ? 'Active' : 'Inactive' }}</span>
                            </button>

                            <button
                                type="button"
                                wire:click="$toggle('form.is_default')"
                                class="admin-switch"
                                data-state="{{ $form['is_default'] ? 'on' : 'off' }}"
                                role="switch"
                            >
                                <span class="admin-switch-track"><span class="admin-switch-thumb"></span></span>
                                <span class="admin-switch-label">{{ $form['is_default'] ? 'Default' : 'Not Default' }}</span>
                            </button>
                        </div>
                    </div>
                    <div style="grid-column: span 12;">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Description</label>
                        <textarea wire:model="form.description" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
                    </div>
                </div>

                <div class="admin-form-actions flex items-center gap-2 pt-2">
                    <button type="submit" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">
                        {{ $editingId ? 'Update Group' : 'Create Group' }}
                    </button>
                    @if ($editingId)
                        <button type="button" wire:click="cancelEdit" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                            Cancel
                        </button>
                    @endif
                </div>
            </form>
        </div>

        <div class="admin-panel admin-panel-soft p-5" style="order:1;">
            <h2 class="admin-section-title">Items</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="admin-items-table min-w-full text-sm">
                    <thead class="text-slate-600">
                        <tr>
                            <th class="px-3 py-2 text-center font-semibold">ID</th>
                            <th class="px-3 py-2 text-left font-semibold">Code</th>
                            <th class="px-3 py-2 text-left font-semibold">Name</th>
                            <th class="px-3 py-2 text-center font-semibold">Users</th>
                            <th class="px-3 py-2 text-center font-semibold">Sort</th>
                            <th class="px-3 py-2 text-center font-semibold">State</th>
                            <th class="px-3 py-2 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="px-3 py-2 text-center font-mono text-xs text-slate-700">{{ $row->id }}</td>
                                <td class="px-3 py-2 text-slate-800">{{ $row->code }}</td>
                                <td class="px-3 py-2 text-slate-800">
                                    <div>{{ $row->name }}</div>
                                    @if ($row->description)
                                        <div class="text-xs text-slate-500">{{ $row->description }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center text-slate-700">{{ $row->users_count }}</td>
                                <td class="px-3 py-2 text-center text-slate-700">{{ $row->sort_order }}</td>
                                <td class="px-3 py-2 text-center">
                                    <button type="button" wire:click="toggleActive({{ $row->id }})" class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $row->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                        {{ $row->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                    @if ($row->is_default)
                                        <span class="ml-1 rounded-full bg-cyan-100 px-2 py-0.5 text-xs font-semibold text-cyan-800">Default</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        @if (!$row->is_default)
                                            <button type="button" wire:click="makeDefault({{ $row->id }})" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">Default</button>
                                        @endif
                                        <button type="button" wire:click="edit({{ $row->id }})" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">Edit</button>
                                        <button type="button" wire:click="delete({{ $row->id }})" wire:confirm="Delete this group?" class="rounded-lg border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-8 text-center text-sm text-slate-500">No groups yet.</td>
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
</div>


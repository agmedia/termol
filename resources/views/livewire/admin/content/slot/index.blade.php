<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Content Slots</h1>
                <p class="mt-1 text-sm text-slate-600">Map blocks to placement/target rules.</p>
                <p class="mt-2 text-xs text-slate-500">Items per page: <span class="admin-chip">{{ $perPage }}</span></p>
            </div>
            <div class="flex w-full gap-2 sm:w-auto sm:items-end">
                <div class="w-full sm:w-80">
                    <label for="content-slot-search" class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Search</label>
                    <input
                        id="content-slot-search"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Placement, target or block..."
                        class="admin-search-input w-full rounded-xl border px-3 py-2 text-sm"
                    />
                </div>
                <a href="{{ route('admin.content.slots.create') }}" class="inline-flex h-10 items-center rounded-xl bg-cyan-700 px-4 text-sm font-semibold text-white hover:bg-cyan-800">Create</a>
            </div>
        </div>
    </div>

    <div class="admin-panel admin-panel-soft p-5">
        <h2 class="admin-section-title">Items</h2>

        <div class="mt-4 overflow-x-auto">
            <table class="admin-items-table min-w-full text-sm">
                <thead class="text-slate-600">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold">Placement</th>
                        <th class="px-3 py-2 text-left font-semibold">Target</th>
                        <th class="px-3 py-2 text-left font-semibold">Block</th>
                        <th class="px-3 py-2 text-left font-semibold">Preview</th>
                        <th class="px-3 py-2 text-center font-semibold">Sort</th>
                        <th class="px-3 py-2 text-center font-semibold">State</th>
                        <th class="px-3 py-2 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-3 py-2 font-mono text-xs text-slate-700">{{ $row->placement }}</td>
                            <td class="px-3 py-2 text-slate-700">
                                @if ($row->target_type)
                                    <span class="font-semibold">{{ $row->target_type }}</span>
                                    <span class="text-xs text-slate-500">/{{ $row->target_ref ?: '*' }}</span>
                                @else
                                    <span class="text-slate-500">Global</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-slate-800">
                                <span class="font-medium">{{ $row->block?->name ?? 'Missing block' }}</span>
                                <span class="ml-1 text-xs text-slate-500">({{ $row->block?->code ?? '-' }})</span>
                            </td>
                            <td class="px-3 py-2">
                                @if ($row->block)
                                    @include('admin.content.partials.block-type-preview', ['type' => $row->block->type, 'size' => 'xs'])
                                @else
                                    <span class="text-xs text-slate-500">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center text-slate-700">{{ $row->sort_order }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $row->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $row->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ route('admin.content.slots.edit', ['slot' => $row->id]) }}" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">Edit</a>
                                    <button type="button" wire:click="delete({{ $row->id }})" wire:confirm="Delete this slot?" class="rounded-lg border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-sm text-slate-500">No content slots yet.</td>
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

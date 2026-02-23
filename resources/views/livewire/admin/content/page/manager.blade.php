<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Info Pages</h1>
                <p class="mt-1 text-sm text-slate-600">Manage static/information pages with locale slugs.</p>
                <p class="mt-2 text-xs text-slate-500">Items per page: <span class="admin-chip">{{ $perPage }}</span></p>
            </div>

            <div class="flex w-[64rem] max-w-full items-end justify-end gap-3">
                <div class="grid w-full max-w-[56rem] items-end gap-3" style="grid-template-columns: minmax(26rem, 1fr) 8rem;">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Search</label>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Code, title or slug..."
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
                <a href="{{ route('admin.content.pages.create', ['locale' => $locale]) }}" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">
                    Create
                </a>
            </div>
        </div>
    </div>

    <div class="admin-panel admin-panel-soft p-5">
        <h2 class="admin-section-title">Items</h2>

        <div class="mt-4 overflow-x-auto">
            <table class="admin-items-table min-w-full text-sm">
                <thead class="text-slate-600">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold">Page</th>
                        <th class="px-3 py-2 text-left font-semibold">Slug</th>
                        <th class="px-3 py-2 text-center font-semibold">Layout</th>
                        <th class="px-3 py-2 text-center font-semibold">Categories</th>
                        <th class="px-3 py-2 text-center font-semibold">State</th>
                        <th class="px-3 py-2 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        @php $tr = $row->translations->first(); @endphp
                        <tr>
                            <td class="px-3 py-2 text-slate-800">
                                <div class="font-medium">{{ $tr?->title ?? '(missing title)' }}</div>
                                <div class="text-xs text-slate-500">{{ $row->code }}</div>
                            </td>
                            <td class="px-3 py-2 font-mono text-xs text-slate-700">{{ $tr?->slug ?? '-' }}</td>
                            <td class="px-3 py-2 text-center text-slate-700">{{ $row->layout }}</td>
                            <td class="px-3 py-2 text-center text-slate-700">{{ $row->categories_count }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $row->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $row->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.content.pages.edit', ['page' => $row->id, 'locale' => $locale]) }}" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                    Edit
                                </a>
                                <button
                                    type="button"
                                    wire:click="delete({{ (int) $row->id }})"
                                    wire:confirm="Delete page '{{ $tr?->title ?? $row->code }}'?"
                                    class="rounded-lg border border-rose-300 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                                >
                                    Delete
                                </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-sm text-slate-500">No info pages yet.</td>
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

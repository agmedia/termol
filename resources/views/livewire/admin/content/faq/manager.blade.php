<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">FAQs</h1>
                <p class="mt-1 text-sm text-slate-600">Frequently asked questions grouped by topic and locale.</p>
                <p class="mt-2 text-xs text-slate-500">Items per page: <span class="admin-chip">{{ $perPage }}</span></p>
            </div>

            <div class="flex w-[72rem] max-w-full items-end justify-end gap-3">
                <div class="grid w-full max-w-[64rem] items-end gap-3" style="grid-template-columns: minmax(20rem, 1.4fr) 10rem 9rem 8rem;">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Search</label>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Code, group, question, slug..." class="admin-search-input w-full rounded-xl border px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Group</label>
                        <select wire:model.live="group" data-tom-select class="admin-search-input admin-select w-full rounded-xl border px-3 py-2 text-sm">
                            <option value="all">All groups</option>
                            @foreach ($this->groupOptions as $groupCode)
                                <option value="{{ $groupCode }}">{{ $groupCode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">State</label>
                        <select wire:model.live="state" data-tom-select data-tom-no-search="1" class="admin-search-input admin-select w-full rounded-xl border px-3 py-2 text-sm">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="all">All</option>
                        </select>
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
                <a href="{{ route('admin.content.faqs.create', ['locale' => $locale]) }}" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">
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
                        <th class="px-3 py-2 text-left font-semibold">FAQ</th>
                        <th class="px-3 py-2 text-left font-semibold">Slug</th>
                        <th class="px-3 py-2 text-center font-semibold">Group</th>
                        <th class="px-3 py-2 text-center font-semibold">Comments</th>
                        <th class="px-3 py-2 text-center font-semibold">Featured</th>
                        <th class="px-3 py-2 text-center font-semibold">State</th>
                        <th class="px-3 py-2 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        @php $tr = $row->translations->first(); @endphp
                        <tr>
                            <td class="px-3 py-2 text-slate-800">
                                <div class="font-medium">{{ $tr?->question ?? '(missing question)' }}</div>
                                <div class="text-xs text-slate-500">{{ $row->code }}</div>
                            </td>
                            <td class="px-3 py-2 font-mono text-xs text-slate-700">{{ $tr?->slug ?? '-' }}</td>
                            <td class="px-3 py-2 text-center text-slate-700">{{ $row->group_code }}</td>
                            <td class="px-3 py-2 text-center text-slate-700">{{ $row->comments_count }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $row->is_featured ? 'bg-cyan-100 text-cyan-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $row->is_featured ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $row->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $row->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('admin.content.faqs.edit', ['faq' => $row->id, 'locale' => $locale]) }}" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-sm text-slate-500">No FAQs yet.</td>
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

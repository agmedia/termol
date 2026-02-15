<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Categories</h1>
                <p class="mt-1 text-sm text-slate-600">Lazy tree view with root pagination and scoped editing.</p>
            </div>

            <div class="grid w-[64rem] max-w-full items-end gap-3" style="grid-template-columns: minmax(34rem, 1fr) 9rem 7rem;">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Search</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Code, name or slug..." class="admin-search-input w-full rounded-xl border px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Scope</label>
                    <select wire:model.live="scope" data-tom-select data-tom-no-search="1" class="admin-select w-full rounded-xl border px-3 py-2 text-sm">
                        @foreach ($this->scopeOptions as $scopeOption)
                            <option value="{{ $scopeOption }}">{{ str($scopeOption)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Locale</label>
                    <select wire:model.live="locale" data-tom-select data-tom-no-search="1" class="admin-select w-full rounded-xl border px-3 py-2 text-sm lowercase">
                        @foreach ($this->localeOptions as $localeOption)
                            <option value="{{ $localeOption }}">{{ $localeOption }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.categories.create', ['scope' => $scope, 'locale' => $locale]) }}" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">
                Create Category
            </a>
            <span class="admin-chip">{{ $isSearchMode ? 'Search mode' : 'Tree mode' }}</span>
            <span class="admin-chip">Items per page: {{ $paginator->perPage() }}</span>
            <span class="admin-chip">Scope: {{ str($scope)->replace('_', ' ')->title() }}</span>
            <span class="admin-chip">Locale: {{ $locale }}</span>
        </div>
    </div>

    <div class="admin-panel admin-panel-soft p-5">
        <h2 class="admin-section-title">Items</h2>

        <div class="mt-4 overflow-x-auto">
            <table class="admin-items-table min-w-full text-sm">
                <thead class="text-slate-600">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold">Category</th>
                        <th class="px-3 py-2 text-left font-semibold">Slug</th>
                        <th class="px-3 py-2 text-center font-semibold">Depth</th>
                        <th class="px-3 py-2 text-center font-semibold">Sort</th>
                        <th class="px-3 py-2 text-center font-semibold">State</th>
                        <th class="px-3 py-2 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        @php
                            /** @var \App\Models\Catalog\Category $node */
                            $node = $row['node'];
                            $translation = $node->translations->first();
                            $depth = (int) ($row['depth'] ?? 0);
                            $indent = $depth * 18;
                            $hasChildren = (bool) ($row['hasChildren'] ?? false);
                            $expanded = (bool) ($row['isExpanded'] ?? false);
                        @endphp
                        <tr wire:key="category-tree-row-{{ $node->id }}">
                            <td class="px-3 py-2 text-slate-800">
                                <div class="flex items-center gap-2" style="padding-left: {{ $indent }}px;">
                                    @if ($hasChildren)
                                        <button type="button" wire:click="toggleExpand({{ $node->id }})" class="inline-flex h-5 w-5 items-center justify-center rounded border border-slate-300 text-xs text-slate-600 hover:bg-slate-100">
                                            {{ $expanded ? '−' : '+' }}
                                        </button>
                                    @else
                                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                    @endif
                                    <span class="font-medium">{{ $translation?->name ?? '(missing name)' }}</span>
                                    @if ($node->code)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $node->code }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2 font-mono text-xs text-slate-700">{{ $translation?->slug ?? '-' }}</td>
                            <td class="px-3 py-2 text-center text-slate-700">{{ $depth }}</td>
                            <td class="px-3 py-2 text-center text-slate-700">{{ $node->sort_order }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $node->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $node->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" wire:click="moveUp({{ $node->id }})" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">Up</button>
                                    <button type="button" wire:click="moveDown({{ $node->id }})" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">Down</button>
                                    <a href="{{ route('admin.categories.edit', ['category' => $node->id, 'scope' => $scope, 'locale' => $locale]) }}" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">Edit</a>
                                    <button type="button" wire:click="delete({{ $node->id }})" wire:confirm="Delete this category?" class="rounded-lg border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-sm text-slate-500">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $paginator->links() }}
        </div>
    </div>
</div>

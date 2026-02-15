<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Actions & Discounts</h1>
                <p class="mt-1 text-sm text-slate-600">Product actions and cart discounts with audience/target rules.</p>
                <p class="mt-2 text-xs text-slate-500">Items per page: <span class="admin-chip">{{ $perPage }}</span></p>
            </div>

            <div class="flex w-[72rem] max-w-full items-end justify-end gap-3">
                <div class="grid w-full max-w-[64rem] items-end gap-3" style="grid-template-columns: minmax(22rem, 1.6fr) 9rem 11rem 9rem 8rem;">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Search</label>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Code, title, coupon..."
                            class="admin-search-input w-full rounded-xl border px-3 py-2 text-sm"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Scope</label>
                        <select wire:model.live="scopeFilter" data-tom-select data-tom-no-search="1" class="admin-search-input admin-select w-full rounded-xl border px-3 py-2 text-sm">
                            <option value="all">All</option>
                            @foreach ($scopeLabels as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Type</label>
                        <select wire:model.live="typeFilter" data-tom-select data-tom-no-search="1" class="admin-search-input admin-select w-full rounded-xl border px-3 py-2 text-sm">
                            <option value="all">All</option>
                            @foreach ($typeLabels as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">State</label>
                        <select wire:model.live="stateFilter" data-tom-select data-tom-no-search="1" class="admin-search-input admin-select w-full rounded-xl border px-3 py-2 text-sm">
                            @foreach ($stateLabels as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
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
                <a href="{{ route('admin.actions.create', ['locale' => $locale]) }}" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">
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
                        <th class="px-3 py-2 text-left font-semibold">Action</th>
                        <th class="px-3 py-2 text-left font-semibold">Scope / Type</th>
                        <th class="px-3 py-2 text-left font-semibold">Target / Audience</th>
                        <th class="px-3 py-2 text-center font-semibold">Schedule</th>
                        <th class="px-3 py-2 text-center font-semibold">Priority</th>
                        <th class="px-3 py-2 text-center font-semibold">State</th>
                        <th class="px-3 py-2 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        @php
                            $tr = $row->translations->first();
                            $scopeLabel = $scopeLabels[$row->scope] ?? ucfirst($row->scope);
                            $typeLabel = $typeLabels[$row->type] ?? $row->type;
                            $targetLabel = $targetLabels[$row->target_type] ?? ucfirst($row->target_type);
                            $audienceLabel = $audienceLabels[$row->audience_type] ?? ucfirst($row->audience_type);
                            $groupText = $row->audienceCustomerGroup?->name;
                            $userText = $row->audienceUser?->name ? $row->audienceUser->name.' ('.$row->audienceUser->email.')' : null;
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-slate-800">
                                <div class="font-medium">{{ $tr?->title ?? '(missing title)' }}</div>
                                <div class="font-mono text-xs text-slate-500">{{ $row->code }}</div>
                                @if ($row->coupon_code)
                                    <div class="mt-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800">Coupon: {{ $row->coupon_code }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-slate-700">
                                <div>{{ $scopeLabel }}</div>
                                <div class="text-xs text-slate-500">{{ $typeLabel }}</div>
                                @if ($row->discount_value !== null)
                                    <div class="mt-1 text-xs font-semibold text-slate-700">
                                        {{ $row->type === \App\Models\Catalog\Action\CatalogAction::TYPE_PERCENTAGE ? rtrim(rtrim((string) $row->discount_value, '0'), '.') . '%' : number_format((float) $row->discount_value, 2) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-slate-700">
                                <div>{{ $targetLabel }} @if($row->targets_count > 0)<span class="text-xs text-slate-500">({{ $row->targets_count }})</span>@endif</div>
                                <div class="text-xs text-slate-500">{{ $audienceLabel }}</div>
                                @if ($row->audience_type === \App\Models\Catalog\Action\CatalogAction::AUDIENCE_USER_GROUP && $groupText)
                                    <div class="text-xs text-slate-500">{{ $groupText }}</div>
                                @endif
                                @if ($row->audience_type === \App\Models\Catalog\Action\CatalogAction::AUDIENCE_USER && $userText)
                                    <div class="text-xs text-slate-500">{{ $userText }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center text-xs text-slate-600">
                                <div>{{ $row->starts_at?->format('Y-m-d H:i') ?? 'No start' }}</div>
                                <div>{{ $row->ends_at?->format('Y-m-d H:i') ?? 'No end' }}</div>
                            </td>
                            <td class="px-3 py-2 text-center text-slate-700">{{ (int) $row->priority }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $row->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $row->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('admin.actions.edit', ['action' => $row->id, 'locale' => $locale]) }}" class="rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-sm text-slate-500">No actions or discounts yet.</td>
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

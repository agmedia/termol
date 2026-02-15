<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">User Activity</h1>
                <p class="mt-1 text-sm text-slate-600">Audit admin actions and front user tracking events.</p>
                <p class="mt-2 text-xs text-slate-500">Items per page: <span class="admin-chip">{{ $perPage }}</span></p>
            </div>

            <div class="flex w-[66rem] max-w-full items-end justify-end gap-3">
                <div class="grid w-full max-w-[56rem] items-end gap-3" style="grid-template-columns: 12rem minmax(24rem, 1fr);">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Source</label>
                        <select wire:model.live="source" data-tom-select data-tom-no-search="1" class="admin-select w-full rounded-xl border px-3 py-2 text-sm">
                            <option value="admin">Admin Activity Log</option>
                            @if ($loyaltyEnabled)
                                <option value="loyalty">Loyalty Audit</option>
                            @endif
                            <option value="tracking">User Tracking</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Search</label>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Log/event/user/url..." class="admin-search-input w-full rounded-xl border px-3 py-2 text-sm" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-panel admin-panel-soft p-5">
        <h2 class="admin-section-title">Items</h2>

        <div class="mt-4 overflow-x-auto">
            @if ($source === 'tracking')
                <table class="admin-items-table min-w-full text-sm">
                    <thead class="text-slate-600">
                        <tr>
                            <th class="px-3 py-2 text-center font-semibold">Time</th>
                            <th class="px-3 py-2 text-left font-semibold">Event</th>
                            <th class="px-3 py-2 text-left font-semibold">User</th>
                            <th class="px-3 py-2 text-left font-semibold">URL</th>
                            <th class="px-3 py-2 text-left font-semibold">Subject</th>
                            <th class="px-3 py-2 text-center font-semibold">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="px-3 py-2 text-center text-xs text-slate-600">{{ $row->occurred_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                                <td class="px-3 py-2">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $row->event }}</span>
                                </td>
                                <td class="px-3 py-2 text-slate-800">
                                    @if ($row->user)
                                        <div>{{ $row->user->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $row->user->email }}</div>
                                    @else
                                        <span class="text-xs text-slate-500">Guest</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-xs text-slate-700">
                                    <div class="max-w-[28rem] truncate">{{ $row->url ?: '-' }}</div>
                                    @if ($row->referrer)
                                        <div class="mt-1 max-w-[28rem] truncate text-slate-500">Ref: {{ $row->referrer }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-xs text-slate-700">
                                    @if ($row->subject_type && $row->subject_id)
                                        {{ class_basename($row->subject_type) }} #{{ $row->subject_id }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center text-xs text-slate-600">{{ $row->ip_address ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-8 text-center text-sm text-slate-500">No tracking events found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="admin-items-table min-w-full text-sm">
                    <thead class="text-slate-600">
                        <tr>
                            <th class="px-3 py-2 text-center font-semibold">Time</th>
                            <th class="px-3 py-2 text-left font-semibold">Log</th>
                            <th class="px-3 py-2 text-left font-semibold">Event</th>
                            <th class="px-3 py-2 text-left font-semibold">Description</th>
                            <th class="px-3 py-2 text-left font-semibold">Causer</th>
                            <th class="px-3 py-2 text-left font-semibold">Subject</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="px-3 py-2 text-center text-xs text-slate-600">{{ $row->created_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                                <td class="px-3 py-2">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $row->log_name ?: '-' }}</span>
                                </td>
                                <td class="px-3 py-2 text-xs text-slate-700">{{ $row->event ?: '-' }}</td>
                                <td class="px-3 py-2 text-slate-800">{{ $row->description }}</td>
                                <td class="px-3 py-2 text-slate-800">
                                    @if ($row->causer)
                                        <div>{{ $row->causer->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $row->causer->email }}</div>
                                    @else
                                        <span class="text-xs text-slate-500">System</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-xs text-slate-700">
                                    @if ($row->subject_type && $row->subject_id)
                                        {{ class_basename($row->subject_type) }} #{{ $row->subject_id }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-8 text-center text-sm text-slate-500">No admin activities found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        <div class="mt-4">
            {{ $rows->links() }}
        </div>
    </div>
</div>

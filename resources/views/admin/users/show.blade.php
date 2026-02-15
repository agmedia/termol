<x-admin-layout title="User Overview">
    @php
        $primaryRole = $user->roles->sortBy('id')->first();
        $roleLabel = $primaryRole?->title ?: ucfirst($primaryRole?->name ?? 'customer');
        $billing = $user->addresses->firstWhere('type', 'billing');
        $shipping = $user->addresses->firstWhere('type', 'shipping');
        $avatarUrl = $user->getFirstMediaUrl('avatar');
    @endphp

    <div class="space-y-6">
        <div class="admin-panel admin-search-panel p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Admin / Users</p>
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">User Overview</h1>
                    <p class="mt-2 text-sm text-slate-600">Read-only account view with profile, addresses and recent activity.</p>
                </div>
                <div class="flex items-center gap-2">
                    @if ($loyaltyEnabled)
                        <a href="{{ route('admin.users.loyalty', ['user_id' => $user->id]) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Loyalty Ledger</a>
                    @endif
                    <a href="{{ route('admin.users.edit', ['user' => $user->id]) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Edit User</a>
                    <a href="{{ route('admin.users') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Back</a>
                </div>
            </div>
        </div>

        <div class="admin-panel admin-form-panel p-6">
            <p class="admin-section-title">Core</p>
            <div class="mt-4 grid gap-4" style="grid-template-columns: 7rem repeat(12, minmax(0, 1fr));">
                <div class="flex items-start justify-center">
                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="Avatar" class="h-16 w-16 rounded-full border border-slate-200 object-cover" />
                    @else
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-900 text-lg font-semibold text-white">
                            {{ strtoupper(substr((string) $user->name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div style="grid-column: span 4;">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Name</label>
                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800">{{ $user->name }}</div>
                </div>
                <div style="grid-column: span 4;">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Email</label>
                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800">{{ $user->email }}</div>
                </div>
                <div style="grid-column: span 2;">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Role</label>
                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800">{{ $roleLabel }}</div>
                </div>
                <div style="grid-column: span 2;">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Verified</label>
                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm {{ $user->email_verified_at ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $user->email_verified_at ? 'Yes' : 'No' }}
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Segments</label>
                <div class="flex flex-wrap gap-2">
                    @forelse ($user->customerGroups as $group)
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $group->name }}</span>
                    @empty
                        <span class="text-sm text-slate-500">No segments assigned.</span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid gap-6" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
            <div class="admin-panel admin-form-panel p-6">
                <p class="admin-section-title">Profile</p>
                <div class="mt-4 grid gap-3" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <div><span class="text-xs uppercase tracking-[0.12em] text-slate-500">First name</span><div class="mt-1 text-sm text-slate-800">{{ $user->profile?->first_name ?: '-' }}</div></div>
                    <div><span class="text-xs uppercase tracking-[0.12em] text-slate-500">Last name</span><div class="mt-1 text-sm text-slate-800">{{ $user->profile?->last_name ?: '-' }}</div></div>
                    <div><span class="text-xs uppercase tracking-[0.12em] text-slate-500">Phone</span><div class="mt-1 text-sm text-slate-800">{{ $user->profile?->phone ?: '-' }}</div></div>
                    <div><span class="text-xs uppercase tracking-[0.12em] text-slate-500">Birthday</span><div class="mt-1 text-sm text-slate-800">{{ $user->profile?->birthday?->format('Y-m-d') ?: '-' }}</div></div>
                    <div><span class="text-xs uppercase tracking-[0.12em] text-slate-500">Company</span><div class="mt-1 text-sm text-slate-800">{{ $user->profile?->company ?: '-' }}</div></div>
                    <div><span class="text-xs uppercase tracking-[0.12em] text-slate-500">OIB</span><div class="mt-1 text-sm text-slate-800">{{ $user->profile?->oib ?: '-' }}</div></div>
                </div>
                <div class="mt-3"><span class="text-xs uppercase tracking-[0.12em] text-slate-500">Bio</span><div class="mt-1 text-sm text-slate-700">{{ $user->profile?->bio ?: '-' }}</div></div>
            </div>

            <div class="admin-panel admin-form-panel p-6">
                <p class="admin-section-title">Addresses</p>
                <div class="mt-4 space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <div class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Billing</div>
                        <div class="mt-2 text-sm text-slate-800">
                            {{ $billing?->first_name }} {{ $billing?->last_name }}<br>
                            {{ $billing?->company }} {{ $billing?->oib ? '('.$billing->oib.')' : '' }}<br>
                            {{ $billing?->address_line_1 }} {{ $billing?->address_line_2 }}<br>
                            {{ $billing?->postal_code }} {{ $billing?->city }} {{ $billing?->state }} {{ $billing?->country_code }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <div class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Shipping</div>
                        <div class="mt-2 text-sm text-slate-800">
                            {{ $shipping?->first_name }} {{ $shipping?->last_name }}<br>
                            {{ $shipping?->company }} {{ $shipping?->oib ? '('.$shipping->oib.')' : '' }}<br>
                            {{ $shipping?->address_line_1 }} {{ $shipping?->address_line_2 }}<br>
                            {{ $shipping?->postal_code }} {{ $shipping?->city }} {{ $shipping?->state }} {{ $shipping?->country_code }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
            @if ($loyaltyEnabled)
                <div class="admin-panel admin-panel-soft p-5">
                    <h2 class="admin-section-title">Loyalty</h2>
                    <div class="mt-3 grid gap-2" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
                        <div class="rounded-xl border border-slate-200 bg-white p-2.5 text-center">
                            <div class="text-[11px] uppercase tracking-[0.1em] text-slate-500">Balance</div>
                            <div class="mt-1 text-sm font-semibold {{ $loyaltyStats['balance'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $loyaltyStats['balance'] }}</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-2.5 text-center">
                            <div class="text-[11px] uppercase tracking-[0.1em] text-slate-500">Entries</div>
                            <div class="mt-1 text-sm font-semibold text-slate-700">{{ $loyaltyStats['entries'] }}</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-2.5 text-center">
                            <div class="text-[11px] uppercase tracking-[0.1em] text-slate-500">Earned</div>
                            <div class="mt-1 text-sm font-semibold text-emerald-700">{{ $loyaltyStats['earned'] }}</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-2.5 text-center">
                            <div class="text-[11px] uppercase tracking-[0.1em] text-slate-500">Spent/Reversed</div>
                            <div class="mt-1 text-sm font-semibold text-rose-700">{{ $loyaltyStats['spent'] }}</div>
                        </div>
                    </div>
                    <div class="mt-3 overflow-x-auto">
                        <table class="admin-items-table min-w-full text-xs">
                            <thead>
                                <tr>
                                    <th class="px-2 py-2 text-left">Time</th>
                                    <th class="px-2 py-2 text-left">Type</th>
                                    <th class="px-2 py-2 text-center">Points</th>
                                    <th class="px-2 py-2 text-left">Order</th>
                                    <th class="px-2 py-2 text-left">Actor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($loyaltyEntries as $entry)
                                    <tr>
                                        <td class="px-2 py-2">{{ $entry->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                        <td class="px-2 py-2">{{ str_replace('_', ' ', $entry->type) }}</td>
                                        <td class="px-2 py-2 text-center">
                                            <span class="rounded-full px-2 py-0.5 font-semibold {{ $entry->points >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                                {{ $entry->points }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-2">
                                            @if ($entry->order)
                                                <a href="{{ route('admin.orders.show', ['order' => $entry->order->id]) }}" class="text-cyan-700 hover:text-cyan-900">{{ $entry->order->order_number }}</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-2 py-2">{{ $entry->creator?->name ?: 'System' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-2 py-4 text-center text-slate-500">No loyalty entries.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                </div>
            @endif

            <div class="admin-panel admin-panel-soft p-5">
                <h2 class="admin-section-title">Recent Orders</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="admin-items-table min-w-full text-xs">
                        <thead>
                            <tr>
                                <th class="px-2 py-2 text-left">Order</th>
                                <th class="px-2 py-2 text-left">Status</th>
                                <th class="px-2 py-2 text-right">Total</th>
                                <th class="px-2 py-2 text-left">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td class="px-2 py-2">
                                        <a href="{{ route('admin.orders.show', ['order' => $order->id]) }}" class="text-cyan-700 hover:text-cyan-900">{{ $order->order_number }}</a>
                                    </td>
                                    <td class="px-2 py-2">{{ $order->status?->name ?: '-' }}</td>
                                    <td class="px-2 py-2 text-right">{{ number_format((float) $order->grand_total, 2) }} {{ $order->currency_code }}</td>
                                    <td class="px-2 py-2">{{ $order->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-2 py-4 text-center text-slate-500">No orders for this user.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid gap-6" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
            <div class="admin-panel admin-panel-soft p-5">
                <h2 class="admin-section-title">Recent Admin Activity</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="admin-items-table min-w-full text-xs">
                        <thead>
                            <tr>
                                <th class="px-2 py-2 text-left">Time</th>
                                <th class="px-2 py-2 text-left">Event</th>
                                <th class="px-2 py-2 text-left">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($adminActivity as $log)
                                <tr>
                                    <td class="px-2 py-2">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                    <td class="px-2 py-2">{{ $log->event ?: '-' }}</td>
                                    <td class="px-2 py-2">{{ $log->description }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-2 py-4 text-center text-slate-500">No admin activity.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="admin-panel admin-panel-soft p-5">
                <h2 class="admin-section-title">Recent Tracking Events</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="admin-items-table min-w-full text-xs">
                        <thead>
                            <tr>
                                <th class="px-2 py-2 text-left">Time</th>
                                <th class="px-2 py-2 text-left">Event</th>
                                <th class="px-2 py-2 text-left">URL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($trackingEvents as $event)
                                <tr>
                                    <td class="px-2 py-2">{{ $event->occurred_at?->format('Y-m-d H:i') }}</td>
                                    <td class="px-2 py-2">{{ $event->event }}</td>
                                    <td class="px-2 py-2 max-w-[20rem] truncate">{{ $event->url ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-2 py-4 text-center text-slate-500">No tracking events.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

@php
    $statusClasses = [
        'blue' => 'bg-blue-100 text-blue-800',
        'emerald' => 'bg-emerald-100 text-emerald-800',
        'green' => 'bg-emerald-100 text-emerald-800',
        'rose' => 'bg-rose-100 text-rose-800',
        'red' => 'bg-rose-100 text-rose-800',
        'amber' => 'bg-amber-100 text-amber-800',
        'yellow' => 'bg-amber-100 text-amber-800',
        'violet' => 'bg-violet-100 text-violet-800',
        'purple' => 'bg-violet-100 text-violet-800',
        'slate' => 'bg-slate-200 text-slate-700',
        'gray' => 'bg-slate-200 text-slate-700',
        'cyan' => 'bg-cyan-100 text-cyan-800',
    ];

    $statusColor = strtolower((string) ($order->status?->color ?? 'slate'));
    $statusClass = $statusClasses[$statusColor] ?? $statusClasses['slate'];
    $loyaltySettlement = $loyaltyEnabled
        ? $order->loyaltyTransactions->first(fn ($row) => $row->type === 'order_settlement')
        : null;
    $loyaltyRedemption = $loyaltyEnabled
        ? $order->loyaltyTransactions->first(fn ($row) => $row->type === 'order_redemption')
        : null;
    $visibleTotals = $loyaltyEnabled
        ? $order->totals
        : $order->totals->reject(fn ($total) => $total->code === 'loyalty_redemption');
@endphp

<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Sales / Orders</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Order {{ $order->order_number }}</h1>
                <p class="mt-2 text-sm text-slate-600">Review snapshot data, adjust status, and keep timeline notes.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $order->status?->name ?? 'Unknown' }}</span>
                <span class="admin-chip">{{ number_format((float) $order->grand_total, 2) }} {{ $order->currency_code }}</span>
                <a
                    href="{{ route('admin.orders.invoice', ['order' => $order->id]) }}"
                    target="_blank"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                >
                    Invoice / Print
                </a>
                <button type="button" wire:click="backToList" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Back to List</button>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="admin-panel admin-form-panel p-6 xl:col-span-2">
            <p class="admin-section-title">Order Snapshot</p>

            <div class="mt-4 grid gap-3" style="grid-template-columns: repeat(12, minmax(0, 1fr));">
                <div style="grid-column: span 4;">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Customer</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $order->customer_name }}</p>
                    <p class="text-xs text-slate-600">{{ $order->customer_email }}</p>
                    @if ($order->customer_phone)
                        <p class="text-xs text-slate-600">{{ $order->customer_phone }}</p>
                    @endif
                </div>
                <div style="grid-column: span 4;">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Payment</p>
                    <p class="mt-1 text-sm text-slate-800">{{ $order->payment_method_name ?: '-' }}</p>
                    <p class="text-xs text-slate-600">{{ $order->payment_method_code ?: '-' }}</p>
                </div>
                <div style="grid-column: span 4;">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Shipping</p>
                    <p class="mt-1 text-sm text-slate-800">{{ $order->shipping_method_name ?: '-' }}</p>
                    <p class="text-xs text-slate-600">{{ $order->shipping_method_code ?: '-' }}</p>
                </div>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Billing Address</p>
                    <div class="mt-2 text-sm text-slate-700">
                        <p>{{ trim(($order->billing_first_name ?? '').' '.($order->billing_last_name ?? '')) ?: '-' }}</p>
                        @if ($order->billing_company)<p>{{ $order->billing_company }}</p>@endif
                        <p>{{ $order->billing_address_line_1 ?: '-' }}</p>
                        @if ($order->billing_address_line_2)<p>{{ $order->billing_address_line_2 }}</p>@endif
                        <p>{{ trim(($order->billing_postal_code ?? '').' '.($order->billing_city ?? '')) ?: '-' }}</p>
                        <p>{{ $order->billing_country_code ?: '-' }}</p>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Shipping Address</p>
                    <div class="mt-2 text-sm text-slate-700">
                        <p>{{ trim(($order->shipping_first_name ?? '').' '.($order->shipping_last_name ?? '')) ?: '-' }}</p>
                        @if ($order->shipping_company)<p>{{ $order->shipping_company }}</p>@endif
                        <p>{{ $order->shipping_address_line_1 ?: '-' }}</p>
                        @if ($order->shipping_address_line_2)<p>{{ $order->shipping_address_line_2 }}</p>@endif
                        <p>{{ trim(($order->shipping_postal_code ?? '').' '.($order->shipping_city ?? '')) ?: '-' }}</p>
                        <p>{{ $order->shipping_country_code ?: '-' }}</p>
                    </div>
                </div>
            </div>

            @if ($order->customer_note || $order->admin_note)
                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Customer Note</p>
                        <p class="mt-2 text-sm text-slate-700">{{ $order->customer_note ?: '-' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Latest Admin Note</p>
                        <p class="mt-2 text-sm text-slate-700">{{ $order->admin_note ?: '-' }}</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="admin-panel admin-form-panel p-6">
            <p class="admin-section-title">Status Workflow</p>
            <div class="mt-4 space-y-3">
                @if ($quickStatuses->isNotEmpty())
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Quick Actions</label>
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach ($quickStatuses as $quickStatus)
                                <button
                                    type="button"
                                    wire:click="quickStatusByCode('{{ $quickStatus->code }}')"
                                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 hover:bg-slate-100"
                                >
                                    Mark {{ $quickStatus->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Target Status</label>
                    <select wire:model="form.status_id" data-tom-select data-tom-no-search="1" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($statuses as $statusItem)
                            <option value="{{ $statusItem->id }}">{{ $statusItem->name }}</option>
                        @endforeach
                    </select>
                    @error('form.status_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Timeline Note</label>
                    <textarea rows="5" wire:model="form.comment" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Optional note for this status update..."></textarea>
                    @error('form.comment') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="admin-form-actions flex items-center gap-2 pt-2">
                    <button type="button" wire:click="updateStatus" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">
                        Save Status Update
                    </button>
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                <p><strong>Placed:</strong> {{ optional($order->placed_at ?: $order->created_at)->format('Y-m-d H:i') }}</p>
                <p class="mt-1"><strong>Paid At:</strong> {{ optional($order->paid_at)->format('Y-m-d H:i') ?: '-' }}</p>
                <p class="mt-1"><strong>Source:</strong> {{ $order->source }}</p>
                @if ($loyaltyEnabled)
                    <p class="mt-1">
                        <strong>Loyalty Settlement:</strong>
                        @if ($loyaltySettlement)
                            {{ (int) $loyaltySettlement->points }} points
                        @else
                            -
                        @endif
                    </p>
                    <p class="mt-1">
                        <strong>Loyalty Redemption:</strong>
                        @if ($loyaltyRedemption)
                            {{ abs((int) $loyaltyRedemption->points) }} points
                        @else
                            -
                        @endif
                    </p>
                @endif
            </div>

            @if ($loyaltyEnabled)
                <div class="mt-4 rounded-xl border border-slate-200 bg-white p-3">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Loyalty Redemption</label>
                    @if ($order->user_id)
                        <div class="grid gap-2" style="grid-template-columns: 1fr auto;">
                            <input
                                type="number"
                                min="0"
                                wire:model="redeemPoints"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                placeholder="Points to redeem..."
                            />
                            <button type="button" wire:click="applyLoyaltyRedemption" class="rounded-xl bg-cyan-700 px-3 py-2 text-xs font-semibold text-white hover:bg-cyan-800">
                                Apply
                            </button>
                        </div>
                        @error('redeemPoints') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        <div class="mt-2 text-xs text-slate-600">
                            <p>Available: <strong>{{ $availableLoyaltyPoints ?? 0 }}</strong> pts</p>
                            <p>Max redeemable on this order: <strong>{{ $maxRedeemablePoints }}</strong> pts</p>
                            <p>Value per point: <strong>{{ number_format((float) $currencyValuePerPoint, 4) }} {{ $order->currency_code }}</strong></p>
                        </div>
                    @else
                        <p class="text-xs text-slate-600">Assign user to order before redemption can be used.</p>
                    @endif
                </div>
            @endif

            <div class="mt-4">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Internal Tags</label>
                <div class="mb-2 flex flex-wrap items-center gap-1.5">
                    @forelse ($internalTags as $tag)
                        <button
                            type="button"
                            wire:click='removeInternalTag(@js($tag))'
                            class="inline-flex items-center gap-1 rounded-full border border-slate-300 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100"
                            title="Remove tag"
                        >
                            <span>{{ $tag }}</span>
                            <span class="text-slate-500">×</span>
                        </button>
                    @empty
                        <span class="text-xs text-slate-500">No tags yet.</span>
                    @endforelse
                </div>
                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        wire:model.defer="tagInput"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        placeholder="e.g. priority, call-customer, waiting-stock"
                    />
                    <button type="button" wire:click="addInternalTag" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                        Add
                    </button>
                </div>
                @error('tagInput') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="admin-panel admin-panel-soft p-5 xl:col-span-2">
            <h2 class="admin-section-title">Order Items</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="admin-items-table min-w-full text-sm">
                    <thead class="text-slate-600">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Item</th>
                            <th class="px-3 py-2 text-center font-semibold">Qty</th>
                            <th class="px-3 py-2 text-center font-semibold">Unit</th>
                            <th class="px-3 py-2 text-center font-semibold">Discount</th>
                            <th class="px-3 py-2 text-center font-semibold">Tax</th>
                            <th class="px-3 py-2 text-center font-semibold">Line Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($order->items as $item)
                            <tr>
                                <td class="px-3 py-2 text-slate-800">
                                    <div class="font-medium">{{ $item->name }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $item->code ?: '-' }} @if($item->sku) / {{ $item->sku }} @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-center text-slate-700">{{ $item->quantity }}</td>
                                <td class="px-3 py-2 text-center text-slate-700">{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="px-3 py-2 text-center text-slate-700">{{ number_format((float) $item->discount_amount, 2) }}</td>
                                <td class="px-3 py-2 text-center text-slate-700">{{ number_format((float) $item->tax_amount, 2) }}</td>
                                <td class="px-3 py-2 text-center font-semibold text-slate-800">{{ number_format((float) $item->line_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-sm text-slate-500">No items recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-panel admin-panel-soft p-5">
            <h2 class="admin-section-title">Totals</h2>
            <div class="mt-4 space-y-2">
                @forelse ($visibleTotals as $total)
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                        <span class="text-slate-700">{{ $total->title }}</span>
                        <span class="font-semibold text-slate-900">{{ number_format((float) $total->value, 2) }} {{ $order->currency_code }}</span>
                    </div>
                @empty
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500">No total rows saved.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="admin-panel admin-panel-soft p-5">
            <h2 class="admin-section-title">Status Timeline</h2>
            <div class="mt-4 space-y-2">
                @forelse ($order->history as $entry)
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                        <div class="flex items-center justify-between gap-2 text-xs text-slate-500">
                            <span>{{ optional($entry->created_at)->format('Y-m-d H:i') }}</span>
                            <span>{{ $entry->changedBy?->name ?: 'System' }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-700">
                            {{ $entry->fromStatus?->name ?: '-' }}
                            <span class="px-1">-&gt;</span>
                            {{ $entry->toStatus?->name ?: '-' }}
                        </p>
                        @if ($entry->comment)
                            <p class="mt-1 text-xs text-slate-600">{{ $entry->comment }}</p>
                        @endif
                    </div>
                @empty
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500">No history rows yet.</div>
                @endforelse
            </div>
        </div>

        <div class="admin-panel admin-panel-soft p-5">
            <h2 class="admin-section-title">Transactions</h2>
            <div class="mt-4 space-y-2">
                @forelse ($order->transactions as $transaction)
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                        <div class="flex items-center justify-between gap-2 text-xs text-slate-500">
                            <span>{{ optional($transaction->processed_at ?: $transaction->created_at)->format('Y-m-d H:i') }}</span>
                            <span>{{ $transaction->provider }}</span>
                        </div>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ number_format((float) $transaction->amount, 2) }} {{ $transaction->currency_code }}</p>
                        <p class="text-xs text-slate-600">{{ $transaction->status }} @if($transaction->transaction_ref) / {{ $transaction->transaction_ref }} @endif</p>
                    </div>
                @empty
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500">No transactions recorded.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

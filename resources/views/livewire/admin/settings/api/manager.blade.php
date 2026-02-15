<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">API Settings</h1>
                <p class="mt-2 text-sm text-slate-600">Wholesale API base URL: <code>/api/v1/wholesale</code></p>
                <p class="mt-2 text-xs text-slate-500">Approve users for API access, issue scoped tokens, and revoke credentials.</p>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="admin-chip">Items per page: {{ $perPage }}</span>
                <span class="admin-chip">Endpoints: 8</span>
            </div>
        </div>
    </div>

    <div class="admin-panel admin-form-panel p-6">
        <p class="admin-section-title">API User Access</p>

        <div class="mt-4 grid gap-3" style="grid-template-columns: 3fr 1fr 1fr;">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Search</label>
                <input
                    type="text"
                    wire:model.live.debounce.250ms="search"
                    class="admin-search-input w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Name or email..."
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Role</label>
                <select wire:model.live="role" data-tom-select data-tom-no-search="1" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <option value="">All</option>
                    @foreach ($roles as $roleOption)
                        <option value="{{ $roleOption->name }}">{{ $roleOption->title ?: ucfirst($roleOption->name) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">API Access</label>
                <select wire:model.live="accessFilter" data-tom-select data-tom-no-search="1" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <option value="all">All</option>
                    <option value="enabled">Enabled</option>
                    <option value="disabled">Disabled</option>
                </select>
            </div>
        </div>

        <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
            <table class="admin-items-table min-w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-left">Role</th>
                        <th class="px-4 py-3 text-center">API Access</th>
                        <th class="px-4 py-3 text-center">Tokens</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($users as $row)
                        @php
                            $rowRole = $row->roles->sortBy('id')->first();
                            $apiEnabled = (bool) $row->api_access_enabled;
                        @endphp
                        <tr>
                            <td class="px-4 py-3 align-middle">
                                <p class="font-semibold text-slate-900">{{ $row->name }}</p>
                                <p class="text-xs text-slate-500">{{ $row->email }}</p>
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <span class="admin-chip">{{ $rowRole?->title ?: ucfirst((string) ($rowRole?->name ?? 'customer')) }}</span>
                            </td>
                            <td class="px-4 py-3 align-middle text-center">
                                <button
                                    type="button"
                                    wire:click="toggleApiAccess({{ $row->id }})"
                                    class="admin-switch"
                                    data-state="{{ $apiEnabled ? 'on' : 'off' }}"
                                    role="switch"
                                    aria-checked="{{ $apiEnabled ? 'true' : 'false' }}"
                                    aria-label="Toggle API access for {{ $row->email }}"
                                >
                                    <span class="admin-switch-track"><span class="admin-switch-thumb"></span></span>
                                    <span class="admin-switch-label">{{ $apiEnabled ? 'On' : 'Off' }}</span>
                                </button>
                            </td>
                            <td class="px-4 py-3 text-center align-middle">
                                <span class="admin-chip">{{ (int) $row->tokens_count }}</span>
                            </td>
                            <td class="px-4 py-3 text-right align-middle">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="prepareIssueToken({{ $row->id }})"
                                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                                    >
                                        Issue Token
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="revokeAllTokensForUser({{ $row->id }})"
                                        class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                                    >
                                        Revoke All
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">No users found for current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </div>

    <div class="admin-panel admin-form-panel p-6">
        <p class="admin-section-title">Issue API Token</p>

        <form wire:submit="issueToken" class="admin-form mt-4 space-y-4">
            <div class="grid gap-3" style="grid-template-columns: 3fr 2fr 2fr 2fr;">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Approved User</label>
                    <select wire:model="issueUserId" data-tom-select class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select user</option>
                        @foreach ($approvedUsers as $approvedUser)
                            <option value="{{ $approvedUser->id }}">{{ $approvedUser->name }} ({{ $approvedUser->email }})</option>
                        @endforeach
                    </select>
                    @error('issueUserId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Preset</label>
                    <select wire:model.live="preset" data-tom-select data-tom-no-search="1" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($presetCatalog as $presetKey => $presetLabel)
                            <option value="{{ $presetKey }}">{{ $presetLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Token Name</label>
                    <input type="text" wire:model="tokenName" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    @error('tokenName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Expires At</label>
                    <input type="datetime-local" wire:model="expiresAt" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
                    @error('expiresAt') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Token Abilities</label>
                <div class="grid gap-3" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                    @foreach ($abilityCatalog as $abilityKey => $ability)
                        <label class="flex items-start gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
                            <input type="checkbox" wire:model="selectedAbilities" value="{{ $abilityKey }}" class="mt-1 h-4 w-4 rounded border-slate-300 text-cyan-600" />
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">{{ $ability['title'] }}</span>
                                <span class="block text-xs text-slate-500">{{ $abilityKey }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('selectedAbilities') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                @error('selectedAbilities.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="admin-form-actions flex items-center gap-2">
                <button type="submit" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">Create Token</button>
            </div>
        </form>

        @if ($generatedPlainToken !== '')
            <div class="mt-4 rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-900">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">Plain Token (show once)</p>
                <div class="mt-2 rounded-lg border border-emerald-200 bg-white p-3 font-mono text-xs break-all">{{ $generatedPlainToken }}</div>
                <p class="mt-2 text-xs text-emerald-700">Copy this now. It cannot be displayed again.</p>
            </div>
        @endif
    </div>

    <div class="admin-panel admin-items-panel p-6">
        <p class="admin-section-title">Issued Tokens</p>

        <div class="mt-4 grid gap-3" style="grid-template-columns: 3fr 2fr;">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Search Token/User</label>
                <input
                    type="text"
                    wire:model.live.debounce.250ms="tokenSearch"
                    class="admin-search-input w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                    placeholder="Token name, user name, email..."
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">User Filter</label>
                <select wire:model.live="tokenUserFilter" data-tom-select class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <option value="">All Users</option>
                    @foreach ($approvedUsers as $approvedUser)
                        <option value="{{ $approvedUser->id }}">{{ $approvedUser->name }} ({{ $approvedUser->email }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
            <table class="admin-items-table min-w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-[0.12em] text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-left">Token</th>
                        <th class="px-4 py-3 text-left">Abilities</th>
                        <th class="px-4 py-3 text-center">Last Used</th>
                        <th class="px-4 py-3 text-center">Expires</th>
                        <th class="px-4 py-3 text-center">Created</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($tokens as $token)
                        @php $tokenUser = $token->tokenable; @endphp
                        <tr>
                            <td class="px-4 py-3 align-middle">
                                <p class="font-semibold text-slate-900">{{ $tokenUser?->name ?? 'Unknown User' }}</p>
                                <p class="text-xs text-slate-500">{{ $tokenUser?->email ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <span class="font-medium text-slate-900">{{ $token->name }}</span>
                                <p class="text-xs text-slate-500">ID: {{ $token->id }}</p>
                            </td>
                            <td class="px-4 py-3 align-middle text-xs text-slate-600">
                                {{ implode(', ', $token->abilities ?? []) }}
                            </td>
                            <td class="px-4 py-3 text-center align-middle text-xs text-slate-600">{{ $token->last_used_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 text-center align-middle text-xs text-slate-600">{{ $token->expires_at?->format('Y-m-d H:i') ?? 'No expiry' }}</td>
                            <td class="px-4 py-3 text-center align-middle text-xs text-slate-600">{{ $token->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-right align-middle">
                                <button
                                    type="button"
                                    wire:click="revokeToken({{ $token->id }})"
                                    class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                                >
                                    Revoke
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">No tokens found for current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $tokens->links() }}
        </div>
    </div>
</div>

<div class="space-y-6">
    <div class="admin-panel admin-search-panel p-6">
        <h1 class="text-xl font-semibold tracking-tight">{{ __('Kipos API') }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ __('Kipos catalog + ERP connector for granular product sync and manual admin order send.') }}</p>
        <p class="mt-2 text-xs text-slate-500">{{ __('Connection is separated from sync actions so we can safely test the transport first, then run only prices, quantities, actions, or images when needed.') }}</p>
    </div>

    <div class="admin-panel admin-form-panel p-6">
        <form wire:submit="save" class="admin-form mt-1 space-y-4">
            <div class="grid gap-3 md:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <strong class="block text-slate-900">{{ __('Kipos connector enabled') }}</strong>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Master switch for all Kipos sync actions and admin ERP order send buttons.') }}</p>
                        </div>
                        @php $enabled = (bool) ($form['kipos_api_enabled'] ?? false); @endphp
                        <button
                            type="button"
                            wire:click="toggleEnabled"
                            class="admin-switch"
                            data-state="{{ $enabled ? 'on' : 'off' }}"
                            role="switch"
                            aria-checked="{{ $enabled ? 'true' : 'false' }}"
                            aria-label="{{ __('Toggle Kipos connector enabled') }}"
                        >
                            <span class="admin-switch-track"><span class="admin-switch-thumb"></span></span>
                            <span class="admin-switch-label">{{ $enabled ? __('On') : __('Off') }}</span>
                        </button>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <strong class="block text-slate-900">{{ __('Verify TLS certificate') }}</strong>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Keep enabled for HTTPS. Disable only for trusted internal/self-signed endpoints.') }}</p>
                        </div>
                        @php $verifyTls = (bool) ($form['kipos_api_verify_tls'] ?? true); @endphp
                        <button
                            type="button"
                            wire:click="toggleTls"
                            class="admin-switch"
                            data-state="{{ $verifyTls ? 'on' : 'off' }}"
                            role="switch"
                            aria-checked="{{ $verifyTls ? 'true' : 'false' }}"
                            aria-label="{{ __('Toggle TLS verification') }}"
                        >
                            <span class="admin-switch-track"><span class="admin-switch-thumb"></span></span>
                            <span class="admin-switch-label">{{ $verifyTls ? __('On') : __('Off') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Base Route URL') }}</label>
                    <input
                        type="text"
                        wire:model="form.kipos_api_base_uri"
                        placeholder="http://host:8080/kipos.web.api/?route="
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                    />
                    @error('form.kipos_api_base_uri') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Timeout (seconds)') }}</label>
                    <input
                        type="number"
                        min="5"
                        max="120"
                        wire:model="form.kipos_api_timeout_seconds"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                    />
                    @error('form.kipos_api_timeout_seconds') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Image Base URL (optional fallback)') }}</label>
                    <input
                        type="text"
                        wire:model="form.kipos_api_image_base_uri"
                        placeholder="http://host:8080/slike/"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                    />
                    @error('form.kipos_api_image_base_uri') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Query Suffix') }}</label>
                    <input
                        type="text"
                        wire:model="form.kipos_api_query_suffix"
                        placeholder="webshop=1"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                    />
                    <p class="mt-1 text-xs text-slate-500">{{ __('Appended to every Kipos request. Old integration used `webshop=1`.') }}</p>
                    @error('form.kipos_api_query_suffix') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                <p class="font-semibold text-slate-800">{{ __('Manual note') }}</p>
                <p class="mt-1">{{ __('Use the API route root exactly as documented (`...?route=`), then keep sync-specific mapping like category / size option / order prefix in the Sync Manager settings below.') }}</p>
            </div>

            <div class="admin-form-actions flex flex-wrap items-center gap-2">
                <button type="submit" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">{{ __('admin.common.save') }}</button>
                <button type="button" wire:click="testConnection" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('Test Connection') }}</button>
            </div>
        </form>
    </div>

    @if ($lastProbeResult !== null)
        <div class="admin-panel admin-items-panel p-6">
            <p class="admin-section-title">{{ __('Last Probe Result') }}</p>
            <div class="mt-3 grid gap-3 md:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('Probe') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $lastProbeResult['probe'] }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('Rows returned') }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $lastProbeResult['result_count'] }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('Status') }}</p>
                    <p class="mt-1 text-sm font-semibold text-emerald-700">{{ __('Connected') }}</p>
                </div>
            </div>

            @if (!empty($lastProbeResult['first_item']))
                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('First row preview') }}</p>
                    <pre class="mt-2 overflow-x-auto rounded-lg border border-slate-200 bg-white p-3 text-xs text-slate-700">{{ json_encode($lastProbeResult['first_item'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            @endif
        </div>
    @endif

    @if ($lastProbeError)
        <div class="admin-panel border border-rose-300 bg-rose-50 p-6 text-sm text-rose-900">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose-700">{{ __('Connection error details') }}</p>
            <p class="mt-2 break-words">{{ $lastProbeError }}</p>
        </div>
    @endif
</div>

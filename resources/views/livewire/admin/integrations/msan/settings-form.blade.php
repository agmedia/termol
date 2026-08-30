<div class="space-y-6">
    <section class="admin-panel admin-form-panel p-6">
        <form wire:submit="save" class="space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-4 md:col-span-2">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">{{ __('M SAN B2B veza') }}</h2>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Puni XML se periodično sprema u lokalnu radnu kopiju; webshop nikad ne poziva M SAN pri prikazu artikla.') }}</p>
                        </div>
                        <button type="button" wire:click="$toggle('form.msan_enabled')" class="admin-switch" data-state="{{ ($form['msan_enabled'] ?? false) ? 'on' : 'off' }}" role="switch" aria-checked="{{ ($form['msan_enabled'] ?? false) ? 'true' : 'false' }}">
                            <span class="admin-switch-track"><span class="admin-switch-thumb"></span></span>
                            <span class="admin-switch-label">{{ ($form['msan_enabled'] ?? false) ? __('Uključeno') : __('Isključeno') }}</span>
                        </button>
                    </div>
                    @error('form.msan_enabled') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div @class([
                    'rounded-xl border p-4',
                    'border-emerald-200 bg-emerald-50' => $certificateValid,
                    'border-rose-200 bg-rose-50' => $certificateConfigured && ! $certificateValid,
                    'border-amber-200 bg-amber-50' => ! $certificateConfigured,
                ])>
                    <p @class([
                        'text-xs font-semibold uppercase tracking-[0.14em]',
                        'text-emerald-700' => $certificateValid,
                        'text-rose-700' => $certificateConfigured && ! $certificateValid,
                        'text-amber-700' => ! $certificateConfigured,
                    ])>{{ __('Certifikat') }}</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">
                        {{ $certificateValid ? __('Valjano i privatno spremljen') : ($certificateConfigured ? __('Nevaljan ili istekao') : __('Nije spremljen')) }}
                    </p>
                    @if ($certificateMetadata)
                        <p class="mt-1 text-xs text-slate-600">{{ __('Vrijedi do') }}: {{ $certificateMetadata['valid_until'] ?? '—' }}</p>
                        <p class="mt-1 break-all font-mono text-[10px] text-slate-500">SHA-256: {{ $certificateMetadata['fingerprint'] ?? '' }}</p>
                    @endif
                    @if ($certificateError)
                        <p class="mt-1 text-xs text-rose-700">{{ $certificateError }}</p>
                    @endif
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('P12/PFX certifikat') }}</label>
                    <input type="file" wire:model="certificate" accept=".p12,.pfx,application/x-pkcs12" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-slate-500">{{ __('Datoteka ide u privatni storage izvan public direktorija. Maksimalno 1 MB.') }}</p>
                    @error('certificate') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('PIN / lozinka za uvoz certifikata') }}</label>
                    <input type="password" wire:model="form.msan_p12_pin" autocomplete="new-password" placeholder="{{ ($form['msan_p12_pin_configured'] ?? false) ? __('PIN je spremljen — prazno ga zadržava') : __('Unesite PIN certifikata') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-slate-500">{{ __('PIN se sprema šifrirano i nikad se ponovno ne prikazuje.') }}</p>
                    @error('form.msan_p12_pin') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                <strong>{{ __('Fiksni B2B servis') }}:</strong> <span class="break-all font-mono">{{ $productEndpoint }}</span>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Vremensko ograničenje spajanja (sekunde)') }}</label><input type="number" min="2" max="60" wire:model="form.msan_connect_timeout" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">@error('form.msan_connect_timeout') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                <div><label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Vremensko ograničenje punog dohvata (sekunde)') }}</label><input type="number" min="15" max="300" wire:model="form.msan_request_timeout" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">@error('form.msan_request_timeout') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror</div>
            </div>

            <div class="border-t border-slate-200 pt-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">{{ __('FTP / FTPS slike') }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ __('Opcionalno. Slike se preuzimaju samo za uvezene artikle i spremaju lokalno; hotlinkanje nije dopušteno.') }}</p>
                    </div>
                    <button type="button" wire:click="$toggle('form.msan_ftp_enabled')" class="admin-switch" data-state="{{ ($form['msan_ftp_enabled'] ?? false) ? 'on' : 'off' }}" role="switch" aria-checked="{{ ($form['msan_ftp_enabled'] ?? false) ? 'true' : 'false' }}">
                        <span class="admin-switch-track"><span class="admin-switch-thumb"></span></span>
                        <span class="admin-switch-label">{{ ($form['msan_ftp_enabled'] ?? false) ? __('Uključeno') : __('Isključeno') }}</span>
                    </button>
                </div>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('FTP host') }}</label><input type="text" value="{{ $ftpHost }}" disabled class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-600"></div>
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('FTP korisničko ime') }}</label><input type="text" wire:model="form.msan_ftp_username" autocomplete="off" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">@error('form.msan_ftp_username') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                    <div class="md:col-span-2"><label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('FTP lozinka') }}</label><input type="password" wire:model="form.msan_ftp_password" autocomplete="new-password" placeholder="{{ ($form['msan_ftp_password_configured'] ?? false) ? __('Lozinka je spremljena — prazno je zadržava') : __('Unesite FTP lozinku') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">@error('form.msan_ftp_password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('FTP vremensko ograničenje spajanja') }}</label><input type="number" min="2" max="60" wire:model="form.msan_ftp_connect_timeout" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">@error('form.msan_ftp_connect_timeout') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('FTP vremensko ograničenje prijenosa') }}</label><input type="number" min="15" max="120" wire:model="form.msan_ftp_timeout" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">@error('form.msan_ftp_timeout') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-6">
                <h2 class="text-base font-semibold text-slate-900">{{ __('Pravila uvoza') }}</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach ([
                        'msan_import_images' => [__('Preuzmi slike'), __('Slike se obrađuju zasebnim poslovima nakon artikla.')],
                        'msan_import_products_active' => [__('Nove artikle odmah aktiviraj'), __('Sigurnije je ostaviti isključeno do ručne provjere cijene i opisa.')],
                    ] as $field => [$label, $description])
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div><strong class="text-sm text-slate-900">{{ $label }}</strong><p class="mt-1 text-xs text-slate-600">{{ $description }}</p></div>
                                <button type="button" wire:click="$toggle('form.{{ $field }}')" class="admin-switch" data-state="{{ ($form[$field] ?? false) ? 'on' : 'off' }}" role="switch" aria-checked="{{ ($form[$field] ?? false) ? 'true' : 'false' }}"><span class="admin-switch-track"><span class="admin-switch-thumb"></span></span></button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-xs text-slate-500">{{ __('M SAN daje razinu dostupnosti 0–4, ne stvarnu količinu. Ovdje definirate sigurnu lokalnu količinu za svaku razinu.') }}</p>
                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-5">
                    @foreach (range(0, 4) as $level)
                        <div><label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Razina') }} {{ $level }}</label><input type="number" min="0" max="999999" wire:model="form.msan_stock_level_{{ $level }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">@error('form.msan_stock_level_'.$level) <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 pt-5">
                <p class="mr-auto self-center text-xs text-slate-500">{{ __('Provjere veze koriste zadnje spremljene postavke. Nakon izmjene najprije ih spremite.') }}</p>
                <button type="button" wire:click="testConnection" wire:loading.attr="disabled" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('Provjeri B2B vezu') }}</button>
                <button type="button" wire:click="testFtpConnection" wire:loading.attr="disabled" @disabled(!($form['msan_ftp_enabled'] ?? false)) class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 disabled:opacity-50">{{ __('Provjeri FTP') }}</button>
                <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">{{ __('Spremi M SAN postavke') }}</button>
            </div>
        </form>
    </section>
</div>

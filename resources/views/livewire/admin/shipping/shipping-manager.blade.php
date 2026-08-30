<div class="space-y-6">
    <section class="admin-panel admin-search-panel p-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ __('Prodaja / Dostava') }}</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ __('Modul dostave') }}</h1>
                <p class="mt-2 text-sm text-slate-600">{{ __('Upravljajte zonama, cijenama, težinom, dimenzijama i ograničenjima prijevoznika.') }}</p>
            </div>
            <div class="flex w-full flex-col gap-3 sm:flex-row xl:w-auto xl:items-end">
                <div class="w-full sm:w-72">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Pretraga') }}</label>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Naziv, šifra ili prijevoznik...') }}" class="admin-search-input w-full rounded-xl border px-3 py-2 text-sm">
                </div>
                <button type="button" wire:click="create" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">
                    {{ __('Nova metoda') }}
                </button>
            </div>
        </div>
    </section>

    <section class="admin-panel border border-cyan-200 bg-cyan-50 p-5">
        <form wire:submit="saveIslandPolicy" class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-800">{{ __('Kopno i otoci') }}</p>
                <h2 class="mt-1 text-lg font-semibold text-slate-900">{{ __('Kako obračunati otočnu dostavu?') }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ __('Kupac ništa ne odabire. Sustav prema adresi automatski prikazuje MBE tarifu za kopno ili otoke.') }}</p>
            </div>
            <div class="flex w-full flex-col gap-2 sm:flex-row sm:items-end xl:w-auto">
                <div class="w-full sm:w-80">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">{{ __('Pravilo klasifikacije') }}</label>
                    <select wire:model="islandPolicy" class="admin-select w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                        @foreach ($islandPolicyOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('islandPolicy') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">
                    {{ __('Spremi pravilo') }}
                </button>
            </div>
        </form>
    </section>

    <section class="admin-panel admin-panel-soft p-5">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($rows as $row)
                @php
                    $carrierLabel = $carrierOptions[$row->carrier] ?? $row->carrier;
                    $serviceLabel = $serviceTypeOptions[$row->service_type] ?? $row->service_type;
                    $priceLabel = match ((string) $row->pricing_type) {
                        'free' => __('Besplatno'),
                        'quote' => __('Na upit'),
                        'weight_tiers' => trans_choice(':count raspon|:count raspona|:count raspona', $row->rates_count, ['count' => $row->rates_count]),
                        default => \App\Support\Currency::format((float) $row->price),
                    };
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold text-slate-900">{{ $row->name }}</h2>
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $row->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $row->is_active ? __('Aktivno') : __('Neaktivno') }}
                                </span>
                            </div>
                            <p class="mt-1 font-mono text-xs text-slate-500">{{ $row->code }}</p>
                        </div>
                        <span class="text-sm font-semibold text-slate-900">{{ $priceLabel }}</span>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Prijevoznik') }}</dt>
                            <dd class="mt-1 font-medium text-slate-800">{{ $carrierLabel }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Usluga') }}</dt>
                            <dd class="mt-1 font-medium text-slate-800">{{ $serviceLabel }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Zona') }}</dt>
                            <dd class="mt-1 text-slate-700">{{ $row->geoZone?->name ?? __('Sve zone') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">{{ __('Limit težine') }}</dt>
                            <dd class="mt-1 text-slate-700">{{ $row->max_weight_kg !== null ? number_format((float) $row->max_weight_kg, 3).' kg' : '—' }}</dd>
                        </div>
                    </dl>

                    @if ($row->max_length_cm !== null || $row->max_width_cm !== null || $row->max_height_cm !== null)
                        <p class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">
                            {{ __('Maks. dimenzije') }}:
                            {{ $row->max_length_cm ?? '∞' }} × {{ $row->max_width_cm ?? '∞' }} × {{ $row->max_height_cm ?? '∞' }} cm
                        </p>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <button type="button" wire:click="edit({{ $row->id }})" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">{{ __('Uredi') }}</button>
                        <button type="button" wire:click="toggleActive({{ $row->id }})" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                            {{ $row->is_active ? __('Isključi') : __('Uključi') }}
                        </button>
                        <button type="button" wire:click="delete({{ $row->id }})" wire:confirm="{{ __('Obrisati način dostave :name?', ['name' => $row->name]) }}" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">{{ __('Obriši') }}</button>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-xl border border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">{{ __('Nema načina dostave.') }}</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $rows->links() }}</div>
    </section>

    <section class="admin-panel admin-form-panel p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-section-title">{{ $editingId ? __('Uredi način dostave') : __('Novi način dostave') }}</p>
                <p class="mt-2 text-sm text-slate-600">{{ __('Sva pravila provjeravaju se na checkoutu i ponovno prilikom kreiranja narudžbe.') }}</p>
            </div>
            @if ($editingId)
                <button type="button" wire:click="create" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">{{ __('Odustani od uređivanja') }}</button>
            @endif
        </div>

        <form wire:submit="save" class="mt-5 space-y-6">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Šifra') }}</label>
                    <input type="text" wire:model="form.code" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-sm lowercase">
                    @error('form.code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-1 xl:col-span-2">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Naziv') }}</label>
                    <input type="text" wire:model="form.name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    @error('form.name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Redoslijed') }}</label>
                    <input type="number" min="0" wire:model="form.sort_order" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Prijevoznik') }}</label>
                    <select wire:model.live="form.carrier" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($carrierOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Vrsta usluge') }}</label>
                    <select wire:model.live="form.service_type" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($serviceTypeOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Način obračuna') }}</label>
                    <select wire:model.live="form.pricing_type" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($pricingTypeOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Geo zona') }}</label>
                    <select wire:model="form.geo_zone_id" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <option value="">{{ __('Sve zone') }}</option>
                        @foreach ($geoZones as $zone)<option value="{{ $zone->id }}">{{ $zone->name }}</option>@endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Opis za kupca') }}</label>
                <textarea rows="2" wire:model="form.description" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
                @error('form.description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Cijena i vrijednost košarice') }}</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Fiksna cijena (€)') }}</label>
                            <input type="number" min="0" step="0.01" wire:model="form.price" @disabled(($form['pricing_type'] ?? 'flat') !== 'flat') class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Besplatno iznad (€)') }}</label>
                            <input type="number" min="0" step="0.01" wire:model="form.free_over" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Min. košarica (€)') }}</label>
                            <input type="number" min="0" step="0.01" wire:model="form.min_subtotal" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Maks. košarica (€)') }}</label>
                            <input type="number" min="0" step="0.01" wire:model="form.max_subtotal" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Težina i dimenzije') }}</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Min. težina (kg)') }}</label>
                            <input type="number" min="0" step="0.001" wire:model="form.min_weight_kg" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Maks. težina (kg)') }}</label>
                            <input type="number" min="0" step="0.001" wire:model="form.max_weight_kg" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        @foreach (['max_length_cm' => 'Maks. duljina (cm)', 'max_width_cm' => 'Maks. širina (cm)', 'max_height_cm' => 'Maks. visina (cm)'] as $field => $label)
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __($label) }}</label>
                                <input type="number" min="0" step="0.01" wire:model="form.{{ $field }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            </div>
                        @endforeach
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Ako mjere nedostaju') }}</label>
                            <select wire:model="form.missing_measurements_policy" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                <option value="allow">{{ __('Dopusti dostavu') }}</option>
                                <option value="block">{{ __('Sakrij dostavu') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Posebna roba') }}</p>
                <div class="mt-3 grid gap-3 md:grid-cols-3">
                    @foreach ([
                        'fragile' => ['label' => 'Lomljivo', 'allow' => 'allows_fragile', 'surcharge' => 'fragile_surcharge'],
                        'oversized' => ['label' => 'Vangabaritno', 'allow' => 'allows_oversized', 'surcharge' => 'oversized_surcharge'],
                        'heavy' => ['label' => 'Teško', 'allow' => 'allows_heavy', 'surcharge' => 'heavy_surcharge'],
                    ] as $type => $config)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <button
                                type="button"
                                wire:click="$toggle('form.{{ $config['allow'] }}')"
                                class="admin-switch"
                                data-state="{{ $form[$config['allow']] ? 'on' : 'off' }}"
                                role="switch"
                                aria-checked="{{ $form[$config['allow']] ? 'true' : 'false' }}"
                            >
                                <span class="admin-switch-track"><span class="admin-switch-thumb"></span></span>
                                <span class="admin-switch-label">{{ __($config['label']) }}: {{ $form[$config['allow']] ? __('dopušteno') : __('blokirano') }}</span>
                            </button>
                            <label class="mt-3 block text-xs font-semibold text-slate-600">{{ __('Doplata (€)') }}</label>
                            <input type="number" min="0" step="0.01" wire:model="form.{{ $config['surcharge'] }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    @endforeach
                </div>
            </div>

            @if (($form['carrier'] ?? '') === 'boxnow')
                <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-800">BOX NOW</p>
                    <label class="mt-3 mb-1 block text-xs font-semibold text-slate-600">{{ __('Partner ID') }}</label>
                    <input type="text" wire:model="form.boxnow_partner_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-sm">
                    @error('form.boxnow_partner_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            @endif

            @if (($form['pricing_type'] ?? '') === 'weight_tiers')
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Cjenik prema težini') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Rasponi se ne smiju preklapati. Prazan maksimum znači bez gornje granice.') }}</p>
                        </div>
                        <button type="button" wire:click="addRate" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">{{ __('Dodaj raspon') }}</button>
                    </div>
                    @error('rates') <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                    <div class="mt-4 space-y-2">
                        @foreach ($rates as $index => $rate)
                            <div wire:key="shipping-rate-{{ $rate['id'] ?? 'new-'.$index }}" class="grid items-end gap-2 rounded-xl border border-slate-200 bg-white p-3 sm:grid-cols-[1fr_1fr_1fr_auto]">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Od kg') }}</label>
                                    <input type="number" min="0" step="0.001" wire:model="rates.{{ $index }}.min_weight_kg" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Do kg') }}</label>
                                    <input type="number" min="0" step="0.001" wire:model="rates.{{ $index }}.max_weight_kg" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    @error('rates.'.$index.'.max_weight_kg') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Cijena (€)') }}</label>
                                    <input type="number" min="0" step="0.01" wire:model="rates.{{ $index }}.price" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                </div>
                                <button type="button" wire:click="removeRate({{ $index }})" class="rounded-lg border border-rose-300 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">{{ __('Ukloni') }}</button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
                <button
                    type="button"
                    wire:click="$toggle('form.is_active')"
                    class="admin-switch"
                    data-state="{{ $form['is_active'] ? 'on' : 'off' }}"
                    role="switch"
                    aria-checked="{{ $form['is_active'] ? 'true' : 'false' }}"
                >
                    <span class="admin-switch-track"><span class="admin-switch-thumb"></span></span>
                    <span class="admin-switch-label">{{ $form['is_active'] ? __('Aktivno') : __('Neaktivno') }}</span>
                </button>
                <button type="submit" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">
                    {{ $editingId ? __('Spremi promjene') : __('Kreiraj metodu') }}
                </button>
            </div>
        </form>
    </section>
</div>

<div class="space-y-6">
    <section class="admin-panel admin-search-panel p-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ __('M SAN katalog') }}</p>
                <h2 class="mt-2 text-xl font-semibold tracking-tight text-slate-900">{{ __('Mapiranje kategorija') }}</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">
                    {{ __('Jedna kategorija webshopa može primati artikle iz više M SAN kategorija. Ignorirane kategorije neće se nuditi za uvoz.') }}
                </p>
                <p class="mt-2 text-xs text-slate-500">
                    {{ __('Stavki po stranici') }}: <span class="admin-chip">{{ $perPage }}</span>
                </p>
            </div>

            <div class="flex w-full flex-col gap-3 xl:w-auto xl:items-end">
                <div class="grid w-full gap-3 sm:grid-cols-[minmax(18rem,32rem)_12rem]">
                    <form wire:submit="applySearch">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500" for="msan-category-search">
                            {{ __('Pretraga') }}
                        </label>
                        <div class="flex gap-2">
                            <input
                                id="msan-category-search"
                                type="search"
                                wire:model="searchInput"
                                minlength="2"
                                maxlength="120"
                                placeholder="{{ __('Početak naziva ili M SAN šifre...') }}"
                                class="admin-search-input min-w-0 flex-1 rounded-xl border px-3 py-2 text-sm"
                            >
                            <button type="submit" wire:loading.attr="disabled" wire:target="applySearch" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 disabled:cursor-wait disabled:opacity-60">
                                {{ __('Traži') }}
                            </button>
                        </div>
                        @error('searchInput') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </form>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500" for="msan-category-status">
                            {{ __('Status') }}
                        </label>
                        <select id="msan-category-status" wire:model.live="status" class="admin-select w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="all">{{ __('Svi statusi') }}</option>
                            <option value="unmapped">{{ __('Nije mapirano') }}</option>
                            <option value="mapped">{{ __('Mapirano') }}</option>
                            <option value="ignored">{{ __('Ignorirano') }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" wire:click="clearFilters" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                        {{ __('Očisti filtre') }}
                    </button>
                    @if ($canManageMapping)
                        <button
                            type="button"
                            wire:click="autoMatchExactNames"
                            wire:confirm="{{ __('Automatski mapirati samo M SAN i lokalne kategorije s potpuno jednakim, jedinstvenim nazivom?') }}"
                            wire:loading.attr="disabled"
                            wire:target="autoMatchExactNames"
                            class="rounded-xl bg-cyan-700 px-4 py-2 text-xs font-semibold text-white hover:bg-cyan-800 disabled:cursor-wait disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="autoMatchExactNames">{{ __('Automatski spoji jednake nazive') }}</span>
                            <span wire:loading wire:target="autoMatchExactNames">{{ __('Mapiranje...') }}</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if ($editingCategoryId && $canManageMapping)
        @php
            $editingCategory = $categories->getCollection()->firstWhere('id', $editingCategoryId);
        @endphp
        <section class="admin-panel admin-form-panel p-6" wire:key="msan-category-editor-{{ $editingCategoryId }}">
            <form wire:submit="saveMapping" class="space-y-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-800">{{ __('Uredi mapiranje') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900">
                            {{ $editingCategory?->name ?? __('M SAN kategorija #:id', ['id' => $editingCategoryId]) }}
                        </h3>
                        @if ($editingCategory?->path)
                            <p class="mt-1 text-sm text-slate-600">{{ $editingCategory->path }}</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <button type="button" wire:click="cancelEdit" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('Odustani') }}</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveMapping" class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800 disabled:cursor-wait disabled:opacity-60">{{ __('Spremi mapiranje') }}</button>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500" for="msan-local-category">{{ __('Kategorija webshopa') }}</label>
                        <select id="msan-local-category" wire:model="localCategoryId" class="admin-select w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                            <option value="">{{ __('Odaberite kategoriju...') }}</option>
                            @foreach ($localCategoryOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        @error('localCategoryId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500" for="msan-eprel-group">{{ __('EPREL grupa proizvoda') }}</label>
                        <select id="msan-eprel-group" wire:model="eprelProductGroup" class="admin-select w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                            <option value="">{{ __('Bez EPREL grupe') }}</option>
                            @foreach ($eprelProductGroupOptions as $groupSlug => $groupCode)
                                <option value="{{ $groupSlug }}">{{ str_replace('_', ' ', $groupCode) }} · {{ $groupSlug }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Popis je ograničen na grupe koje podržava EPREL servis.') }}</p>
                        @error('eprelProductGroup') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500" for="msan-energy-requirement">{{ __('Pravilo energetske oznake') }}</label>
                        <select id="msan-energy-requirement" wire:model="energyRequirement" class="admin-select w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                            @foreach ($energyRequirementOptions as $requirementValue => $requirementLabel)
                                <option value="{{ $requirementValue }}">{{ __($requirementLabel) }}</option>
                            @endforeach
                        </select>
                        @error('energyRequirement') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </form>
        </section>
    @endif

    <section class="admin-panel admin-panel-soft p-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="admin-section-title">{{ __('M SAN kategorije') }}</h2>
            <p class="text-xs text-slate-500">
                {{ __('Prikazano :from–:to od :total', [
                    'from' => $categories->firstItem() ?? 0,
                    'to' => $categories->lastItem() ?? 0,
                    'total' => $categories->total(),
                ]) }}
            </p>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="admin-items-table min-w-[82rem] text-sm">
                <thead class="text-slate-600">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold">{{ __('M SAN kategorija') }}</th>
                        <th class="px-3 py-2 text-center font-semibold">{{ __('Artikli') }}</th>
                        <th class="px-3 py-2 text-left font-semibold">{{ __('Status') }}</th>
                        <th class="px-3 py-2 text-left font-semibold">{{ __('Kategorija webshopa') }}</th>
                        <th class="px-3 py-2 text-left font-semibold">{{ __('Energetski podaci') }}</th>
                        <th class="px-3 py-2 text-left font-semibold">{{ __('Zadnje viđeno') }}</th>
                        @if ($canManageMapping)
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Radnje') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                        @php
                            $mapping = $category->mapping;
                            $mappingStatus = match (true) {
                                $mapping?->status === 'ignored' => 'ignored',
                                $mapping?->status === 'mapped' && $mapping?->local_category_id => 'mapped',
                                default => 'unmapped',
                            };
                            $statusLabel = match ($mappingStatus) {
                                'mapped' => __('Mapirano'),
                                'ignored' => __('Ignorirano'),
                                default => __('Nije mapirano'),
                            };
                            $statusClass = match ($mappingStatus) {
                                'mapped' => 'bg-emerald-100 text-emerald-800',
                                'ignored' => 'bg-amber-100 text-amber-800',
                                default => 'bg-slate-200 text-slate-700',
                            };
                            $localCategory = $mapping?->localCategory;
                            $localTranslation = $localCategory?->translations?->firstWhere('locale', 'hr')
                                ?? $localCategory?->translations?->first();
                        @endphp
                        <tr wire:key="msan-category-row-{{ $category->id }}" @class(['bg-cyan-50/60' => $editingCategoryId === $category->id])>
                            <td class="px-3 py-3">
                                <div class="font-semibold text-slate-900">{{ $category->name }}</div>
                                @if ($category->path && $category->path !== $category->name)
                                    <div class="mt-0.5 max-w-[32rem] text-xs text-slate-500">{{ $category->path }}</div>
                                @endif
                                <div class="mt-1 font-mono text-[11px] text-slate-400">{{ $category->external_id }}</div>
                            </td>
                            <td class="px-3 py-3 text-center font-medium text-slate-700">{{ number_format((int) $category->product_count, 0, ',', '.') }}</td>
                            <td class="px-3 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-3 py-3 text-slate-700">
                                @if ($localCategory)
                                    <div class="font-medium">{{ $localTranslation?->name ?? $localCategory->code }}</div>
                                    <div class="mt-0.5 font-mono text-[11px] text-slate-400">{{ $localCategory->code }}</div>
                                @elseif ($mappingStatus === 'ignored')
                                    <span class="text-xs text-slate-500">{{ __('Preskače se pri uvozu') }}</span>
                                @else
                                    <span class="text-xs text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-xs text-slate-600">
                                <div class="font-medium text-slate-700">{{ __($energyRequirementOptions[$mapping?->energy_requirement ?? 'inherit'] ?? __('Automatski prema dostupnim podacima')) }}</div>
                                <div class="mt-1 font-mono text-[11px] text-slate-400">{{ $mapping?->eprel_product_group ?: __('EPREL grupa nije zadana') }}</div>
                            </td>
                            <td class="px-3 py-3 text-xs text-slate-600">
                                <div>{{ $category->last_seen_at?->format('d.m.Y. H:i') ?? '—' }}</div>
                                @if ($category->is_stale)
                                    <span class="mt-1 inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-700">{{ __('Zastarjelo') }}</span>
                                @endif
                            </td>
                            @if ($canManageMapping)
                                <td class="px-3 py-3 text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button type="button" wire:click="openEditor({{ $category->id }})" class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                            {{ $mappingStatus === 'mapped' ? __('Promijeni') : __('Mapiraj') }}
                                        </button>
                                        @if ($mappingStatus !== 'ignored')
                                            <button type="button" wire:click="ignoreCategory({{ $category->id }})" wire:confirm="{{ __('Označiti kategoriju za preskakanje pri uvozu?') }}" class="rounded-lg border border-amber-300 px-2.5 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-50">
                                                {{ __('Ignoriraj') }}
                                            </button>
                                        @endif
                                        @if ($mapping)
                                            <button type="button" wire:click="clearMapping({{ $category->id }})" wire:confirm="{{ __('Ukloniti postojeće mapiranje ove kategorije?') }}" class="rounded-lg border border-rose-300 px-2.5 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                {{ __('Ukloni') }}
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManageMapping ? 7 : 6 }}" class="px-3 py-10 text-center text-sm text-slate-500">
                                {{ __('Nema M SAN kategorija prema odabranim filtrima. Najprije pokrenite sinkronizaciju kataloga.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $categories->links() }}</div>
    </section>
</div>

<div class="space-y-5" @if($pollFrequently) wire:poll.visible.10s @else wire:poll.visible.60s @endif>
    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['label' => __('Artikli u privremenom katalogu'), 'value' => $counts['products']],
            ['label' => __('Odabrani artikli'), 'value' => $counts['selected']],
            ['label' => __('Uvezeni artikli'), 'value' => $counts['imported']],
            ['label' => __('M SAN kategorije'), 'value' => $counts['categories']],
            ['label' => __('Nemapirane kategorije'), 'value' => $counts['unmapped']],
        ] as $card)
            <div class="admin-panel p-4"><p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $card['label'] }}</p><p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($card['value'], 0, ',', '.') }}</p></div>
        @endforeach
    </section>

    <section class="admin-panel admin-form-panel p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('Sinkronizacija kataloga') }}</h2>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $ready ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ $ready ? __('Spremno') : __('Nije konfigurirano') }}</span>
                </div>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ __('Dohvat radi u pozadini, puni XML čita streamingom i ažurira lokalnu radnu kopiju u paketima. Dohvat sam po sebi ne objavljuje niti uvozi artikle.') }}</p>
                @if (! $enabled)<p class="mt-2 text-xs text-amber-700">{{ __('Integracija je isključena u Postavkama.') }}</p>@endif
            </div>
            @if ($canSync)
                <div class="flex shrink-0 flex-wrap gap-2">
                    <button type="button" wire:click="testConnection" wire:loading.attr="disabled" @disabled(!$ready) class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 disabled:opacity-50">{{ __('Provjeri vezu') }}</button>
                    <button type="button" wire:click="syncCatalog" wire:confirm="{{ __('Pokrenuti puni M SAN dohvat u lokalni privremeni katalog?') }}" wire:loading.attr="disabled" @disabled(!$ready) class="rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800 disabled:opacity-50">{{ __('Dohvati katalog') }}</button>
                </div>
            @endif
        </div>
    </section>

    @if ($latestRun)
        @php
            $latestKindLabel = match ((string) $latestRun->kind) {
                'full' => __('Puna sinkronizacija'),
                'import' => __('Uvoz'),
                'connection_test' => __('Provjera B2B veze'),
                'ftp_connection_test' => __('Provjera FTPS veze'),
                default => (string) $latestRun->kind,
            };
            $latestStatusLabel = match ((string) $latestRun->status) {
                'pending' => __('Na čekanju'),
                'running' => __('U tijeku'),
                'completed' => __('Završeno'),
                'failed' => __('Neuspješno'),
                'cancelled' => __('Otkazano'),
                default => (string) $latestRun->status,
            };
        @endphp
        <section class="admin-panel overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4"><h2 class="admin-section-title">{{ __('Posljednje izvršavanje') }}</h2></div>
            <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-5">
                <div><p class="text-xs uppercase text-slate-500">{{ __('Vrsta') }}</p><p class="mt-1 font-semibold">{{ $latestKindLabel }}</p></div>
                <div><p class="text-xs uppercase text-slate-500">{{ __('Status') }}</p><p class="mt-1 font-semibold">{{ $latestStatusLabel }}</p></div>
                <div><p class="text-xs uppercase text-slate-500">{{ __('Napredak') }}</p><p class="mt-1 font-semibold">{{ $latestRun->progress }}%</p></div>
                <div><p class="text-xs uppercase text-slate-500">{{ __('Obrađeno') }}</p><p class="mt-1 font-semibold">{{ $latestRun->processed_count }} / {{ $latestRun->total_count }}</p></div>
                <div><p class="text-xs uppercase text-slate-500">{{ __('Pokrenuto') }}</p><p class="mt-1 font-semibold">{{ $latestRun->started_at?->format('d.m.Y. H:i') ?? '—' }}</p></div>
            </div>
            @if ($latestRun->error_message)<p class="border-t border-rose-200 bg-rose-50 px-5 py-3 text-sm text-rose-800">{{ $latestRun->error_message }}</p>@endif
        </section>
    @endif

    <section class="admin-panel overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4"><h2 class="admin-section-title">{{ __('Ograničenja M SAN izvora podataka') }}</h2></div>
        <div class="overflow-x-auto">
            <table class="admin-items-table min-w-[46rem] border-0 text-sm">
                <thead><tr><th class="px-4 py-3 text-left">{{ __('Skup podataka') }}</th><th class="px-4 py-3 text-left">{{ __('Zadnji uspjeh') }}</th><th class="px-4 py-3 text-left">{{ __('Sljedeći dopušteni poziv') }}</th><th class="px-4 py-3 text-left">{{ __('Status') }}</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($endpointStates as $state)
                        <tr><td class="px-4 py-3 font-mono text-xs">{{ $state->endpoint }}</td><td class="px-4 py-3">{{ $state->last_success_at?->format('d.m.Y. H:i:s') ?? '—' }}</td><td class="px-4 py-3">{{ $state->next_allowed_at?->format('d.m.Y. H:i:s') ?? 'odmah' }}</td><td class="px-4 py-3 text-xs {{ $state->last_error ? 'text-rose-700' : 'text-emerald-700' }}">{{ $state->last_error ?: __('OK') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ __('Još nema zabilježenih M SAN poziva.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

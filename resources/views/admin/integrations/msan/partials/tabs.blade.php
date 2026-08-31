@php
    $currentAdmin = auth()->user();
    $isSuperadmin = $currentAdmin?->isA('superadmin') ?? false;
    $canView = $isSuperadmin || ($currentAdmin?->can('integrations.msan.view') ?? false);
    $canManageSettings = $isSuperadmin || ($currentAdmin?->can('integrations.msan.settings.manage') ?? false);

    $tabs = $canView
        ? [
            ['route' => 'admin.integrations.msan.overview', 'label' => __('Pregled'), 'step' => null],
            ['route' => 'admin.integrations.msan.categories', 'label' => __('Kategorije'), 'step' => '2'],
            ['route' => 'admin.integrations.msan.products', 'label' => __('Artikli'), 'step' => '3'],
            ['route' => 'admin.integrations.msan.specifications', 'label' => __('Specifikacije'), 'step' => '4'],
            ['route' => 'admin.integrations.msan.runs', 'label' => __('Izvršavanja'), 'step' => null],
        ]
        : [];

    if ($canManageSettings) {
        array_splice($tabs, $canView ? 1 : 0, 0, [[
            'route' => 'admin.integrations.msan.settings',
            'label' => __('Postavke'),
            'step' => '1',
        ]]);
    }
@endphp

<nav class="admin-panel overflow-x-auto p-2" aria-label="{{ __('M SAN navigacija') }}">
    <div class="flex min-w-max items-center gap-1.5">
        @foreach ($tabs as $tab)
            @php($isActive = request()->routeIs($tab['route']))
            <a
                href="{{ route($tab['route']) }}"
                @if($isActive) aria-current="page" @endif
                class="group inline-flex min-h-11 items-center gap-2 rounded-xl border px-3.5 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-600 {{ $isActive ? 'border-slate-900 bg-slate-900 text-white shadow-sm' : 'border-transparent bg-white text-slate-600 hover:border-slate-200 hover:bg-slate-100 hover:text-slate-900' }}"
            >
                @if ($tab['step'])
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-bold {{ $isActive ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-white' }}">{{ $tab['step'] }}</span>
                @else
                    <span class="h-2 w-2 rounded-full {{ $isActive ? 'bg-cyan-300' : 'bg-slate-300 group-hover:bg-cyan-500' }}" aria-hidden="true"></span>
                @endif
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>

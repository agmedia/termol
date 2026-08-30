@php
    $currentAdmin = auth()->user();
    $isSuperadmin = $currentAdmin?->isA('superadmin') ?? false;
    $canView = $isSuperadmin || ($currentAdmin?->can('integrations.msan.view') ?? false);
    $canManageSettings = $isSuperadmin || ($currentAdmin?->can('integrations.msan.settings.manage') ?? false);

    $tabs = $canView
        ? [
            ['route' => 'admin.integrations.msan.overview', 'label' => __('Pregled')],
            ['route' => 'admin.integrations.msan.categories', 'label' => __('Mapiranje kategorija')],
            ['route' => 'admin.integrations.msan.products', 'label' => __('Odabir artikala')],
            ['route' => 'admin.integrations.msan.runs', 'label' => __('Izvršavanja')],
        ]
        : [];

    if ($canManageSettings) {
        array_splice($tabs, $canView ? 1 : 0, 0, [[
            'route' => 'admin.integrations.msan.settings',
            'label' => __('Postavke'),
        ]]);
    }
@endphp

<nav class="admin-panel flex flex-wrap gap-2 p-3" aria-label="{{ __('M SAN navigacija') }}">
    @foreach ($tabs as $tab)
        <a
            href="{{ route($tab['route']) }}"
            class="rounded-xl px-4 py-2 text-sm font-semibold {{ request()->routeIs($tab['route']) ? 'bg-slate-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-100' }}"
        >
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>

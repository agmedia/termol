<x-admin-layout :title="__('Integracije / M SAN / Artikli')">
    <div class="space-y-6">
        @include('admin.integrations.msan.partials.tabs')

        <section class="admin-panel admin-search-panel p-6">
            <h1 class="text-xl font-semibold tracking-tight">{{ __('Odabir M SAN artikala') }}</h1>
            <p class="mt-2 text-sm text-slate-600">
                {{ __('Filtrirajte lokalno dohvaćeni katalog i odaberite samo artikle koje želite uvesti u webshop.') }}
            </p>
        </section>

        <livewire:admin.integrations.msan.product-selection-manager />
    </div>
</x-admin-layout>

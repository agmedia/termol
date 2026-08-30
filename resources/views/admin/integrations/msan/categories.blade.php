<x-admin-layout :title="__('Integracije / M SAN / Kategorije')">
    <div class="space-y-6">
        @include('admin.integrations.msan.partials.tabs')

        <section class="admin-panel admin-search-panel p-6">
            <h1 class="text-xl font-semibold tracking-tight">{{ __('Mapiranje M SAN kategorija') }}</h1>
            <p class="mt-2 text-sm text-slate-600">
                {{ __('Povežite dobavljačke kategorije s postojećim kategorijama webshopa ili ih označite za preskakanje.') }}
            </p>
        </section>

        <livewire:admin.integrations.msan.category-mapping-manager />
    </div>
</x-admin-layout>

<x-admin-layout :title="__('Integracije / M SAN / Specifikacije')">
    <div class="space-y-6">
        @include('admin.integrations.msan.partials.tabs')

        <section class="admin-panel admin-search-panel p-6">
            <h1 class="text-xl font-semibold tracking-tight">{{ __('Mapiranje M SAN specifikacija') }}</h1>
            <p class="mt-2 text-sm text-slate-600">
                {{ __('Kontrolirajte uvoz, filtre, namjenu i hrvatske prikazne oznake tehničkih podataka.') }}
            </p>
        </section>

        <livewire:admin.integrations.msan.specification-mapping-manager />
    </div>
</x-admin-layout>

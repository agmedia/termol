<x-admin-layout :title="__('Integracije / M SAN / Izvršavanja')">
    <div class="space-y-6">
        @include('admin.integrations.msan.partials.tabs')

        <section class="admin-panel admin-search-panel p-6">
            <h1 class="text-xl font-semibold tracking-tight">{{ __('M SAN sinkronizacije i uvozi') }}</h1>
            <p class="mt-2 text-sm text-slate-600">
                {{ __('Pratite tijek, rezultate i sigurno pročišćene pogreške svakog dohvaćanja, probnog plana i uvoza.') }}
            </p>
        </section>

        <livewire:admin.integrations.msan.run-history-manager />
    </div>
</x-admin-layout>

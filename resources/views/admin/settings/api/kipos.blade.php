<x-admin-layout :title="__('Settings / API / Kipos')">
    <div class="space-y-6">
        @include('admin.settings.api.partials.nav')
        <livewire:admin.settings.api.kipos-manager />
        <livewire:admin.settings.api.kipos-sync-manager />
    </div>
</x-admin-layout>

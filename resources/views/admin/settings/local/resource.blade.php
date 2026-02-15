<x-admin-layout :title="'Settings / Local / '.str_replace('-', ' ', ucwords($resource, '-'))">
    <livewire:admin.settings.local.resource-manager :resource="$resource" />
</x-admin-layout>

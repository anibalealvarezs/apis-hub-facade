<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-medium">Miembros del Proyecto</h2>
            <p class="text-sm text-gray-500">Administra a los usuarios que tienen acceso a este proyecto.</p>
        </div>

        {{ $this->table }}

        <!-- Tabla de Invitaciones Pendientes -->
        @livewire('pending-invitations-table')
    </div>
</x-filament-panels::page>

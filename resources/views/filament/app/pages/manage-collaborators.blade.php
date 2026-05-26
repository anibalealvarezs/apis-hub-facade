<x-filament-panels::page>
    <div class="space-y-6">
        @if(!filament()->getTenant()->is_active || filament()->getTenant()->billing_status === 'suspended')
            <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-800/30" role="alert">
              <span class="font-bold">Proyecto Suspendido:</span> Este proyecto está actualmente inactivo debido a incidencias de facturación. Se permite el acceso de solo lectura para ver la configuración, pero las opciones de edición, despliegue, sincronización y transferencia de propiedad están bloqueadas.
            </div>
        @endif

        <div>
            <h2 class="text-lg font-medium">Miembros del Proyecto</h2>
            <p class="text-sm text-gray-500">Administra a los usuarios que tienen acceso a este proyecto.</p>
        </div>

        {{ $this->table }}

        <!-- Tabla de Invitaciones Pendientes -->
        @livewire('pending-invitations-table')
    </div>
</x-filament-panels::page>

<x-filament-panels::page>
    @if(!filament()->getTenant()->is_active || filament()->getTenant()->billing_status === 'suspended')
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-800/30" role="alert">
          <span class="font-bold">Proyecto Suspendido:</span> Este proyecto está actualmente inactivo debido a incidencias de facturación. Se permite el acceso de solo lectura para ver la configuración, pero las opciones de edición, despliegue, sincronización y transferencia de propiedad están bloqueadas.
        </div>
    @endif

    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" :disabled="!filament()->getTenant()->is_active || filament()->getTenant()->billing_status === 'suspended'">
                Save & Push Changes
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

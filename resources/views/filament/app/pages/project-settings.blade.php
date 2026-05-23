<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $isOwner = auth()->id() === filament()->getTenant()->user_id;
        @endphp

        @if(!$isOwner)
            <div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400" role="alert">
              <span class="font-medium">Aviso:</span> Solo el propietario (creador original) del proyecto tiene acceso a opciones destructivas como transferencia de propiedad o eliminación.
            </div>
        @endif

        <x-filament::section>
            <x-slot name="heading">
                Detalles del Proyecto
            </x-slot>
            
            <x-slot name="description">
                Información general de infraestructura de tu proyecto.
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Nombre</p>
                    <p class="font-medium">{{ filament()->getTenant()->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Subdominio</p>
                    <p class="font-medium">{{ filament()->getTenant()->subdomain }}.apis-hub.cloud</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Propietario Principal</p>
                    <p class="font-medium">{{ filament()->getTenant()->trueOwner->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Fecha de Creación</p>
                    <p class="font-medium">{{ filament()->getTenant()->created_at->format('d M, Y') }}</p>
                </div>
            </div>
        </x-filament::section>

        @if($isOwner)
        <x-filament::section>
            <x-slot name="heading">
                Opciones Peligrosas
            </x-slot>
            <x-slot name="description">
                Acciones irreversibles o críticas para la vida del proyecto.
            </x-slot>

            <div class="flex flex-col gap-4">
                <p class="text-sm text-gray-500">Para transferir este proyecto a otro miembro del equipo o para eliminarlo (iniciando el periodo de gracia de 30 días), utiliza los botones superiores de acción.</p>
            </div>
        </x-filament::section>
        @endif

        @if($logs && $logs->count() > 0)
        <x-filament::section>
            <x-slot name="heading">
                Logs de Despliegue
            </x-slot>
            <x-slot name="description">
                Registro de actividades de infraestructura y sincronización en vivo.
            </x-slot>

            <div wire:poll.5s>
                <div class="bg-gray-950 rounded-lg p-4 font-mono text-xs text-gray-300 overflow-x-auto max-h-96 overflow-y-auto">
                    @foreach($logs as $log)
                        <div class="mb-4 pb-4 border-b border-gray-800 last:border-0 last:pb-0 last:mb-0">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-500">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
                                <span @class([
                                    'px-2 py-0.5 rounded text-xs font-medium',
                                    'bg-green-500/10 text-green-400' => $log->status === 'completed',
                                    'bg-red-500/10 text-red-400' => $log->status === 'failed',
                                    'bg-blue-500/10 text-blue-400' => $log->status === 'running' || $log->status === 'pending',
                                ])>
                                    {{ strtoupper($log->status) }}
                                </span>
                            </div>
                            <pre class="whitespace-pre-wrap font-inherit">{{ $log->output ?? 'Iniciando proceso de despliegue...' }}</pre>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>

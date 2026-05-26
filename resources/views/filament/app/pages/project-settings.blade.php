<x-filament-panels::page>
    <div class="space-y-6">
        @if(!filament()->getTenant()->is_active || filament()->getTenant()->billing_status === 'suspended')
            <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-800/30" role="alert">
              <span class="font-bold">Proyecto Suspendido:</span> Este proyecto está actualmente inactivo debido a incidencias de facturación. Se permite el acceso de solo lectura para ver la configuración, pero las opciones de edición, despliegue, sincronización y transferencia de propiedad están bloqueadas.
            </div>
        @endif

        @php
            $isOwner = auth()->id() === filament()->getTenant()->user_id;
        @endphp

        @if(!$isOwner)
            <div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400" role="alert">
              <span class="font-medium">Aviso:</span> Solo el propietario (creador original) del proyecto tiene acceso a opciones destructivas como transferencia de propiedad o eliminación.
            </div>
        @endif

        @if($logs && $logs->count() > 0)
        @php
            $latestLog = $logs->first();
            $statusColors = [
                'pending' => '#3b82f6', // blue-500
                'running' => '#3b82f6', // blue-500
                'completed' => '#22c55e', // green-500
                'success' => '#22c55e', // green-500
                'failed' => '#ef4444', // red-500
            ];
            $statusText = [
                'pending' => 'En cola...',
                'running' => 'Aprovisionando motor de sincronización...',
                'completed' => 'Activo y En Línea',
                'success' => 'Activo y En Línea',
                'failed' => 'Error de Aprovisionamiento',
            ];
            
            // Extract a safe euphemism from output
            $safeErrorMessage = 'Ocurrió un problema de conectividad o configuración durante el aprovisionamiento.';
            if ($latestLog->status === 'failed' && $latestLog->output) {
                // Look for common errors without exposing paths or IPs
                if (str_contains($latestLog->output, 'Connection refused')) {
                    $safeErrorMessage = 'El servidor de destino rechazó la conexión (Error de Red/SSH).';
                } elseif (str_contains($latestLog->output, 'Conflict. The container name')) {
                    $safeErrorMessage = 'Conflicto de recursos en el servidor destino (ERR_SYNC_ENGINE_CONFLICT).';
                } elseif (str_contains($latestLog->output, 'No space left on device')) {
                    $safeErrorMessage = 'El servidor ha alcanzado su límite de almacenamiento (ERR_DISK_FULL).';
                } elseif (str_contains($latestLog->output, 'git clone')) {
                    $safeErrorMessage = 'No se pudo obtener la última versión del código base (ERR_VCS_FETCH).';
                } else {
                    // Generic fallback with a short hash of the error for support tickets
                    $errorHash = substr(md5($latestLog->output), 0, 8);
                    $safeErrorMessage = "La plataforma encontró una excepción no manejada (Ref: ERR_{$errorHash}). Contacte a soporte.";
                }
            }
        @endphp
        
        <div wire:poll.5s class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="relative flex h-3 w-3">
                    @if($latestLog->status === 'running')
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background-color: #60a5fa;"></span>
                    @endif
                    <span class="relative inline-flex rounded-full h-3 w-3 {{ $latestLog->status === 'running' ? 'animate-pulse' : '' }}" style="background-color: {{ $statusColors[$latestLog->status] ?? '#6b7280' }};"></span>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white">Estado de la Infraestructura</h3>
                    <p class="text-xs text-gray-400">{{ $statusText[$latestLog->status] ?? 'Desconocido' }}</p>
                </div>
            </div>
            
            @if($latestLog->status === 'failed')
                <div class="bg-red-500/10 text-red-400 px-3 py-2 rounded-lg text-xs max-w-lg">
                    <strong>Aviso:</strong> {{ $safeErrorMessage }}
                </div>
            @endif
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
                    <p class="text-sm text-gray-500">Zona Horaria</p>
                    <p class="font-medium">{{ filament()->getTenant()->timezone ?? 'UTC' }}</p>
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

        @if(config('app.debug'))
        @if($logs && $logs->count() > 0)
        <x-filament::section>
            <x-slot name="heading">
                Logs de Actividad
            </x-slot>
            <x-slot name="description">
                Registro de actividades del motor de sincronización en vivo.
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
                            <pre class="whitespace-pre-wrap font-inherit">{{ $log->output ?? 'Iniciando aprovisionamiento del motor de sincronización...' }}</pre>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-filament::section>
        @endif
        @endif
    </div>
</x-filament-panels::page>

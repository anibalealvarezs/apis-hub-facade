@php
    $statusColors = [
        'pending' => 'bg-primary-500',
        'running' => 'bg-primary-500',
        'completed' => 'bg-success-500',
        'success' => 'bg-success-500',
        'failed' => 'bg-danger-500',
        'undeployed' => 'bg-gray-500',
    ];
    $statusText = [
        'pending' => 'Aprovisionando...',
        'running' => 'Aprovisionando...',
        'completed' => 'Activo y En Línea',
        'success' => 'Activo y En Línea',
        'failed' => 'Error Crítico',
        'undeployed' => 'Sin Desplegar',
    ];

    $status = $latestLog ? $latestLog->status : 'undeployed';
    $isProcessing = in_array($status, ['running', 'pending']);
@endphp

<div wire:poll.5s class="px-4 py-3 mx-4 mt-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg flex items-center gap-3">
    <div class="relative flex h-3 w-3 shrink-0">
        @if($isProcessing)
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 bg-primary-400"></span>
        @endif
        <span class="relative inline-flex rounded-full h-3 w-3 {{ $isProcessing ? 'animate-pulse' : '' }} {{ $statusColors[$status] ?? 'bg-gray-500' }}"></span>
    </div>
    <div class="flex flex-col overflow-hidden">
        <span class="text-[10px] uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 leading-none mb-1">Estado del Servidor</span>
        <span class="text-xs font-medium text-gray-900 dark:text-gray-100 truncate" title="{{ $statusText[$status] ?? 'Desconocido' }}">
            {{ $statusText[$status] ?? 'Desconocido' }}
        </span>
    </div>
</div>

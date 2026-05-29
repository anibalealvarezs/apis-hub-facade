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
    
    $tierLabel = $tenant?->billingProfile?->tier?->getLabel() ?? 'Unknown';
    $tierColors = [
        'Free' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 ring-gray-500/10 dark:ring-gray-400/20',
        'Pro' => 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300 ring-primary-600/10 dark:ring-primary-500/20',
        'Ultra' => 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 ring-purple-600/10 dark:ring-purple-500/20',
        'Founder' => 'bg-warning-50 text-warning-700 dark:bg-warning-900/30 dark:text-warning-300 ring-warning-600/10 dark:ring-warning-500/20',
        'Enterprise' => 'bg-danger-50 text-danger-700 dark:bg-danger-900/30 dark:text-danger-300 ring-danger-600/10 dark:ring-danger-500/20',
        'Suspended' => 'bg-danger-100 text-danger-800 dark:bg-danger-900/50 dark:text-danger-300 ring-danger-600/20 dark:ring-danger-500/30',
        'Unknown' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 ring-gray-500/10 dark:ring-gray-400/20',
    ];
    $tierColorClass = $tierColors[$tierLabel] ?? $tierColors['Unknown'];
@endphp

<div class="flex flex-col gap-2 mx-4 mt-2">
    <!-- Tier Badge -->
    <div class="w-full flex justify-center">
        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] uppercase tracking-widest font-bold {{ $tierColorClass }} w-full justify-center shadow-sm ring-1 ring-inset">
            Plan: {{ $tierLabel }}
        </span>
    </div>

    <!-- Infrastructure Status -->
    <div wire:poll.5s class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg flex items-center gap-3">
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
</div>

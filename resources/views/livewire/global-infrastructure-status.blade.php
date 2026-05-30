@php
    // Si hay un despliegue en curso, mostramos eso. Si no, mostramos el estado de salud real del proyecto.
    $isProcessing = $latestLog && in_array($latestLog->status, ['running', 'pending']);
    
    $statusKey = $isProcessing ? 'provisioning' : ($tenant?->health_status ?? 'undeployed');

    $statusColorRGB = match($statusKey) {
        'provisioning' => '59, 130, 246', // Blue 500
        'online', 'active', 'healthy' => '34, 197, 94', // Green 500
        'offline', 'failed', 'error' => '239, 68, 68', // Red 500
        'suspended', 'stopping_workers', 'ready_for_auth', 'disputed' => '234, 179, 8', // Yellow 500
        default => '107, 114, 128', // Gray 500
    };
    
    $statusTextArray = [
        'provisioning' => __('Provisioning...'),
        'online' => __('Active and Online'),
        'active' => __('Active and Online'),
        'healthy' => __('Active and Online'),
        'offline' => __('Offline'),
        'failed' => __('Critical Error'),
        'error' => __('Critical Error'),
        'suspended' => __('Suspended'),
        'stopping_workers' => __('Stopping Synchronization...'),
        'ready_for_auth' => __('Auth Renewal...'),
        'disputed' => __('Disputed Payment'),
        'undeployed' => __('Undeployed'),
    ];
    
    $statusText = $statusTextArray[$statusKey] ?? __('Unknown');
    
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

    $tierIcons = [
        'Free' => 'heroicon-m-paper-airplane',
        'Pro' => 'heroicon-m-rocket-launch',
        'Ultra' => 'heroicon-m-bolt',
        'Founder' => 'heroicon-m-building-office-2',
        'Enterprise' => 'heroicon-m-building-office-2',
        'Suspended' => 'heroicon-m-pause-circle',
        'Unknown' => 'heroicon-m-question-mark-circle',
    ];
    $tierIcon = $tierIcons[$tierLabel] ?? $tierIcons['Unknown'];
@endphp

<div class="flex flex-col gap-2 mx-4 mt-2">
    <!-- Tier Badge -->
    <div class="w-full flex justify-center mt-1">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] uppercase tracking-widest font-bold {{ $tierColorClass }} justify-center shadow-sm ring-1 ring-inset" 
              :class="{ 'w-full': $store.sidebar.isOpen, 'w-auto px-1.5': !$store.sidebar.isOpen }"
              title="{{ $tierLabel }}">
            <x-filament::icon :icon="$tierIcon" class="w-4 h-4" />
            <span x-show="$store.sidebar.isOpen" x-transition>{{ $tierLabel }}</span>
        </span>
    </div>

    <!-- Infrastructure Status -->
    <div wire:poll.5s class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg flex items-center justify-center gap-3" :class="{ 'px-0 bg-transparent border-transparent dark:bg-transparent dark:border-transparent py-1': !$store.sidebar.isOpen }">
        <div class="relative flex h-3 w-3 shrink-0" title="{{ $statusText }}">
            @if($isProcessing)
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background-color: rgb({{ $statusColorRGB }});"></span>
            @endif
            <span class="relative inline-flex rounded-full h-3 w-3 {{ $isProcessing ? 'animate-pulse' : '' }}" style="background-color: rgb({{ $statusColorRGB }});"></span>
        </div>
        <div x-show="$store.sidebar.isOpen" class="flex flex-col overflow-hidden">
            <span class="text-[10px] uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 leading-none mb-1">{{ __('Server Status') }}</span>
            <span class="text-xs font-medium text-gray-900 dark:text-gray-100 truncate" title="{{ $statusText }}">
                {{ $statusText }}
            </span>
        </div>
    </div>
</div>

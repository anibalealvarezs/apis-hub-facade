@php
    $isProcessing = $latestLog && in_array($latestLog->status, ['running', 'pending']);

    $statusKey = $isProcessing ? 'provisioning' : ($tenant?->health_status ?? 'undeployed');

    $statusColorRGB = match($statusKey) {
        'provisioning' => '59, 130, 246',
        'online', 'active', 'healthy' => '34, 197, 94',
        'offline', 'failed', 'error' => '239, 68, 68',
        'suspended', 'stopping_workers', 'ready_for_auth', 'disputed' => '234, 179, 8',
        default => '107, 114, 128',
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

    $showSync = $syncPercentage !== null;
@endphp

<div class="flex-1 flex items-center justify-center gap-4 px-2" wire:poll.30s>
    @if($showSync)
        <a href="{{ \App\Filament\App\Pages\DataSync::getUrl() }}" class="flex items-center gap-2 group" title="{{ __('Sync Progress') }}: {{ $syncPercentage }}%">
            <div class="w-20 sm:w-28 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-primary-500 h-1.5 rounded-full transition-all duration-500 ease-out" style="width: {{ $syncPercentage }}%"></div>
            </div>
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors whitespace-nowrap">{{ $syncPercentage }}%</span>
        </a>
    @endif

    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] uppercase tracking-widest font-bold {{ $tierColorClass }} shadow-sm ring-1 ring-inset whitespace-nowrap">
        <x-filament::icon :icon="$tierIcon" class="w-3.5 h-3.5" />
        <span>{{ $tierLabel }}</span>
    </span>

    <div class="flex items-center gap-1.5" title="{{ $statusText }}">
        <div class="relative flex h-2.5 w-2.5 shrink-0">
            @if($isProcessing)
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background-color: rgb({{ $statusColorRGB }});"></span>
            @endif
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 {{ $isProcessing ? 'animate-pulse' : '' }}" style="background-color: rgb({{ $statusColorRGB }});"></span>
        </div>
        <span class="text-xs text-gray-500 dark:text-gray-400 hidden lg:inline max-w-[120px] truncate">
            {{ $statusText }}
        </span>
    </div>
</div>
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

    $showSync = $syncPercentage !== null;
@endphp

<div class="flex-1 flex items-center justify-center gap-4 px-2" wire:poll.30s>
    <div class="flex items-center gap-1.5 px-2 py-1 bg-gray-50 dark:bg-white/5 rounded-lg" title="{{ $statusText }}">
        <div class="relative flex h-3 w-3 shrink-0">
            @if($isProcessing)
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                      style="background-color: rgb({{ $statusColorRGB }});"></span>
            @endif
            <span class="relative inline-flex rounded-full h-3 w-3 {{ $isProcessing ? 'animate-pulse' : '' }}"
                  style="background-color: rgb({{ $statusColorRGB }});"></span>
        </div>
        <span class="topbar-status-text hidden lg:inline text-xs font-medium text-gray-600 dark:text-gray-200 max-w-[120px] truncate">
            {{ $statusText }}
        </span>
    </div>

    @if($showSync)
        <a href="{{ \App\Filament\App\Pages\DataSync::getUrl() }}"
           class="flex items-center gap-2 group px-2 py-1 bg-gray-50 dark:bg-white/5 rounded-lg dark:hover:bg-custom-400"
           title="{{ __('Sync Progress') }}: {{ $syncPercentage }}%">
            <div class="topbar-sync-progress hidden lg:block w-20 lg:w-28 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-primary-500 h-1.5 rounded-full transition-all duration-500 ease-out"
                     style="width: {{ $syncPercentage }}%"></div>
            </div>
            <span
                class="text-xs font-semibold text-gray-700 dark:text-gray-200 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors whitespace-nowrap">{{ $syncPercentage }}%</span>
        </a>
    @endif

    @php
        $tenantTimezone = $tenant?->timezone ?? config('app.timezone', 'UTC');
    @endphp

    {{-- Real-Time Tenant Clock & Timezone Widget --}}
    <div x-data="typeof tenantClock === 'function' ? tenantClock({ timezone: '{{ $tenantTimezone }}' }) : { timeStr: '', tzStr: '{{ $tenantTimezone }}', init() { if (typeof tenantClock === 'function') { Object.assign(this, tenantClock({ timezone: '{{ $tenantTimezone }}' })); if (this.updateClock) this.init(); } } }" 
       class="flex items-center gap-1.5 px-2.5 py-1 bg-gray-50 dark:bg-white/5 rounded-lg border border-gray-200/50 dark:border-white/10 text-xs font-mono text-gray-700 dark:text-gray-200 select-none shrink-0" 
       title="{{ __('Current Tenant Local Time') }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="topbar-clock-icon hidden lg:block w-3.5 h-3.5 text-primary-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <span x-text="timeStr" class="font-bold tracking-tight"></span>
        <span class="topbar-clock-tz hidden lg:inline text-[10px] text-gray-500 dark:text-gray-400 font-semibold uppercase" x-text="tzStr"></span>
    </div>
</div>

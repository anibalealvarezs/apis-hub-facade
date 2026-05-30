<x-filament-panels::page>
    @php
        $tenant = filament()->getTenant()->fresh();
        $isRedeploying  = $tenant->health_status === 'redeploying';
        $isSyncStarted  = !is_null($tenant->last_sync_started_at);
        $elapsedRedeploy = $tenant->deploy_started_at
            ? now()->diffForHumans($tenant->deploy_started_at, ['parts' => 1, 'short' => true])
            : null;
        $elapsedSync = $tenant->last_sync_started_at
            ? now()->diffForHumans($tenant->last_sync_started_at, ['parts' => 1, 'short' => true])
            : null;
    @endphp

    {{-- Auto-refresh so status banners update without a full page reload --}}
    <div wire:poll.15s="refreshTenantStatus"></div>

    @if(!$tenant->is_active || $tenant->billing_status === 'suspended')
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-800/30" role="alert">
          <span class="font-bold">{{ __('Suspended Project') }}:</span> {{ __('This project is currently inactive due to billing issues. Read-only access is permitted to view configuration, but editing, deployment, synchronization, and ownership transfer options are blocked.') }}
        </div>
    @endif

    @if($isRedeploying)
        {{-- Full redeployment in progress --}}
        <div class="p-4 mb-4 text-sm rounded-lg border bg-amber-50 text-amber-900 border-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:border-amber-800/40 flex items-start gap-3" role="status">
            <svg class="animate-spin mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div>
                <span class="font-semibold">{{ __('Redeployment in progress') }}</span>
                @if($elapsedRedeploy)
                    <span class="text-xs ml-1 opacity-70">({{ __('started') }} {{ $elapsedRedeploy }})</span>
                @endif
                <p class="mt-0.5 opacity-80 text-xs">
                    {{ __('The server is rebuilding its containers. Workers will restart once active jobs drain. This may take up to an hour depending on queued work. The Deploy button is disabled until the process completes.') }}
                </p>
            </div>
        </div>
    @elseif($isSyncStarted)
        {{-- Lightweight sync sequence started but node hasn't heartbeated back yet --}}
        <div class="p-4 mb-4 text-sm rounded-lg border bg-blue-50 text-blue-900 border-blue-200 dark:bg-blue-950/30 dark:text-blue-300 dark:border-blue-800/40 flex items-start gap-3" role="status">
            <svg class="animate-spin mt-0.5 h-4 w-4 shrink-0 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div>
                <span class="font-semibold">{{ __('Sync sequence in progress') }}</span>
                @if($elapsedSync)
                    <span class="text-xs ml-1 opacity-70">({{ __('started') }} {{ $elapsedSync }})</span>
                @endif
                <p class="mt-0.5 opacity-80 text-xs">
                    {{ __('Refreshing instances, rebuilding the container manifest, and scheduling new jobs. This banner will clear once the node confirms it is live. Check the Telemetry page to see job progress.') }}
                </p>
            </div>
        </div>
    @elseif($tenant->last_deployed_at)
        <div class="p-4 mb-4 text-sm text-gray-800 rounded-lg bg-gray-50 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-800/30" role="alert">
          <span class="font-bold">{{ __('Last deployment') }}:</span> {{ $tenant->last_deployed_at->format('M j, Y H:i') }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" :disabled="!$tenant->is_active || $tenant->billing_status === 'suspended'">
                {{ __('Save & Push Changes') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

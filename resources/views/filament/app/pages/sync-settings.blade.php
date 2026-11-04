<x-filament-panels::page>
    @php
        $tenant = filament()->getTenant()->fresh();
        $isRedeploying  = $tenant->health_status === 'redeploying';
        $isSyncStarted  = $tenant->health_status === 'syncing';
        $syncElapsedSec = ($isSyncStarted && $tenant->deploy_started_at)
            ? $tenant->deploy_started_at->diffInSeconds(now())
            : 0;
        $syncIsStale    = $syncElapsedSec > 1800; // warn after 30 minutes
        $elapsedRedeploy = $tenant->deploy_started_at
            ? now()->diffForHumans($tenant->deploy_started_at, ['parts' => 1, 'short' => true])
            : null;
        $elapsedSync = $tenant->deploy_started_at
            ? now()->diffForHumans($tenant->deploy_started_at, ['parts' => 1, 'short' => true])
            : null;
    @endphp

    {{-- Auto-refresh so status banners update without a full page reload --}}
    <div wire:poll.15s="refreshTenantStatus"></div>

    <x-filament::section>
        <x-slot name="heading">{{ __('Infrastructure') }}</x-slot>
        <x-slot name="headerEnd">
            <div class="flex flex-wrap gap-2">
                {{ $this->getAction('viewLogs') }}
                {{ $this->getAction('triggerSync') }}
            </div>
        </x-slot>
        @php
            $tz = $tenant->timezone ?? config('app.timezone', 'UTC');
        @endphp
        <dl class="flex flex-wrap gap-x-8 gap-y-2 text-sm">
            <div>
                <dt class="text-gray-600 dark:text-gray-300">{{ __('Last server deployment') }}</dt>
                <dd class="font-medium">{{ $tenant->last_deployed_at?->setTimezone($tz)->translatedFormat(__('M j, Y H:i')) ?? __('Never') }}</dd>
            </div>
            @if($tenant->last_sync_started_at)
                <div>
                    <dt class="text-gray-600 dark:text-gray-300">{{ __('Last sync push') }}</dt>
                    <dd class="font-medium">{{ $tenant->last_sync_started_at->setTimezone($tz)->translatedFormat(__('M j, Y H:i')) }}</dd>
                </div>
            @endif
        </dl>
    </x-filament::section>

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
    @elseif($isSyncStarted && !$syncIsStale)
        {{-- Sync sequence started, script still likely running (< 30 min) --}}
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
                    {{ __('Refreshing instances, rebuilding the container manifest, and scheduling new jobs. This banner will clear once the node confirms it is live. Check the Sync Activity page to see job progress.') }}
                </p>
            </div>
        </div>
    @elseif($isSyncStarted && $syncIsStale)
        {{-- Sync running > 10 min — likely completed or failed silently. Banner auto-clears at 15 min. --}}
        <div class="p-4 mb-4 text-sm rounded-lg border bg-yellow-50 text-yellow-900 border-yellow-200 dark:bg-yellow-950/30 dark:text-yellow-300 dark:border-yellow-800/40 flex items-start gap-3" role="alert">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-yellow-600 dark:text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
            </svg>
            <div>
                <span class="font-semibold">{{ __('Sync sequence is taking longer than expected') }}</span>
                <span class="text-xs ml-1 opacity-70">({{ __('started') }} {{ $elapsedSync }})</span>
                <p class="mt-0.5 opacity-80 text-xs">
                    {{ __('The sync process may have already completed or encountered an error on the node. This banner will automatically dismiss in a few minutes. Check the Sync Activity page to verify job status.') }}
                </p>
            </div>
        </div>
    @endif

    <form wire:submit.prevent="save">
        {{ $this->form }}

    </form>
</x-filament-panels::page>

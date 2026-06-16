<x-filament-panels::page>
    @php
        $tenant = filament()->getTenant()->fresh();
        $isRedeploying  = $tenant->health_status === 'redeploying';
        $elapsedRedeploy = $tenant->deploy_started_at
            ? now()->diffForHumans($tenant->deploy_started_at, ['parts' => 1, 'short' => true])
            : null;
    @endphp

    {{-- Auto-refresh so status banners update without a full page reload --}}
    <div wire:poll.15s></div>

    @if($tenant)
    <div class="space-y-6">
        @if(!$tenant->is_active || $tenant->billing_status === 'suspended')
            <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-800/30" role="alert">
              <span class="font-bold">Proyecto Suspendido:</span> Este proyecto está actualmente inactivo debido a incidencias de facturación. Se permite el acceso de solo lectura para ver la configuración, pero las opciones de edición, despliegue, sincronización y transferencia de propiedad están bloqueadas.
            </div>
        @endif

        @if($isRedeploying)
            <div class="p-4 text-sm rounded-lg border bg-amber-50 text-amber-900 border-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:border-amber-800/40 flex items-start gap-3" role="status">
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
                        {{ __('The server is rebuilding its containers. Workers will restart once active jobs drain. This may take up to an hour. The Apply Changes button is disabled until the process completes.') }}
                    </p>
                </div>
            </div>
        @elseif($tenant->health_status === 'error')
            <div class="p-4 text-sm rounded-lg border bg-red-50 text-red-900 border-red-200 dark:bg-red-950/30 dark:text-red-300 dark:border-red-800/40" role="alert">
                <span class="font-semibold">{{ __('Last deployment failed.') }}</span>
                {{ __('Check the activity logs below for details. You can attempt a new redeployment from the action buttons above.') }}
            </div>
        @endif

        @php
            $isOwner = auth()->id() === $tenant->user_id;
        @endphp

        @if(!$isOwner)
            <div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400" role="alert">
              <span class="font-medium">{{ __('Notice:') }}</span> {{ __('Only the project owner (original creator) has access to destructive options such as ownership transfer or deletion.') }}
            </div>
        @endif


 
        <x-filament::section>
            <x-slot name="heading">
                {{ __('Project Details') }}
            </x-slot>
            
            <x-slot name="description">
                {{ __('General infrastructure information for your project.') }}
            </x-slot>
 
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">{{ __('Name') }}</p>
                    <p class="font-medium">{{ $tenant->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('Subdomain') }}</p>
                    <p class="font-medium">{{ $tenant->subdomain }}.apis-hub.cloud</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('Primary Owner') }}</p>
                    <p class="font-medium">{{ $tenant->trueOwner->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('Timezone') }}</p>
                    <p class="font-medium">{{ $tenant->timezone ?? 'UTC' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('Billing Profile') }}</p>
                    <p class="font-medium">
                        {{ $tenant->billingProfile?->display_name ?? __('No Profile') }} 
                        @if($tenant->billingProfile)
                            <span class="text-xs text-gray-400 bg-gray-800 dark:bg-gray-700 px-2 py-0.5 rounded-full ml-1 font-semibold">
                                {{ $tenant->billingProfile->tier->value ?? $tenant->billingProfile->tier }}
                            </span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('Next Cycle Renewal') }}</p>
                    <p class="font-medium">
                        @if($tenant->billingProfile)
                            @php
                                $profile = $tenant->billingProfile;
                                $starts = $profile->current_cycle_starts_at ?? $profile->created_at ?? now()->startOfMonth();
                                $ends = $profile->current_cycle_ends_at ?? $starts->copy()->addMonth();
                            @endphp
                            {{ $ends->format('d M, Y') }}
                        @else
                            N/A
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('Creation Date') }}</p>
                    <p class="font-medium">{{ $tenant->created_at->format('d M, Y') }}</p>
                </div>
            </div>
        </x-filament::section>
 
        @if($isOwner)
        <x-filament::section>
            <x-slot name="heading">
                {{ __('Danger Zone') }}
            </x-slot>
            <x-slot name="description">
                {{ __('Irreversible or critical actions for the lifecycle of the project.') }}
            </x-slot>
 
            <div class="flex flex-col gap-4">
                <p class="text-sm text-gray-500">{{ __('To transfer this project to another team member or delete it (starting the 30-day grace period), use the top action buttons.') }}</p>
            </div>
        </x-filament::section>
        @endif
 
        @if(auth()->user()->can('deploy_project') && $logs && $logs->count() > 0)
        <x-filament::section>
            <x-slot name="heading">
                {{ __('Activity Logs') }}
            </x-slot>
            <x-slot name="description">
                {{ __('Recent deployment and synchronization history.') }}
            </x-slot>
 
            <div wire:poll.10s>
                <div class="overflow-x-auto ring-1 ring-gray-200 dark:ring-white/10 rounded-lg">
                    <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5 text-gray-500 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-medium whitespace-nowrap">{{ __('Date') }}</th>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3 font-medium w-full">{{ __('Summary') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5 bg-white dark:bg-gray-900">
                            @foreach($logs as $log)
                                @php
                                    $summary = $log->getSummaryMessage();
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ $log->created_at->format('Y-m-d H:i:s') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span @class([
                                            'px-2 py-0.5 rounded text-xs font-medium',
                                            'bg-success-500/10 text-success-600 dark:text-success-400' => $log->status === 'completed' || $log->status === 'success',
                                            'bg-danger-500/10 text-danger-600 dark:text-danger-400' => $log->status === 'failed',
                                            'bg-warning-500/10 text-warning-600 dark:text-warning-400' => $log->status === 'running',
                                            'bg-gray-500/10 text-gray-600 dark:text-gray-400' => $log->status === 'pending',
                                        ])>
                                            {{ strtoupper($log->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                        <div class="truncate max-w-lg" title="{{ $summary }}">
                                            {{ $summary }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </x-filament::section>
        @endif
    </div>
    @endif
</x-filament-panels::page>


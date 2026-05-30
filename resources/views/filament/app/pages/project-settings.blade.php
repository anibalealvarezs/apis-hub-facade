<x-filament-panels::page>
    @php
        $tenant = filament()->getTenant();
    @endphp
    @if($tenant)
    <div class="space-y-6">
        @if(!$tenant->is_active || $tenant->billing_status === 'suspended')
            <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-800/30" role="alert">
              <span class="font-bold">Proyecto Suspendido:</span> Este proyecto está actualmente inactivo debido a incidencias de facturación. Se permite el acceso de solo lectura para ver la configuración, pero las opciones de edición, despliegue, sincronización y transferencia de propiedad están bloqueadas.
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
                        {{ $tenant->billingProfile?->display_name ?? '{{ __('No Profile') }}' }} 
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
 
        @if(config('app.debug'))
        @if($logs && $logs->count() > 0)
        <x-filament::section>
            <x-slot name="heading">
                {{ __('Activity Logs') }}
            </x-slot>
            <x-slot name="description">
                {{ __('Live sync engine activity logs.') }}
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
                            <pre class="whitespace-pre-wrap font-inherit">{{ $log->output ?? '{{ __('Starting sync engine provisioning...') }}' }}</pre>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-filament::section>
        @endif
        @endif
    </div>
    @endif
</x-filament-panels::page>

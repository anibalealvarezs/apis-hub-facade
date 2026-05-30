<x-filament-panels::page>
    @if(!filament()->getTenant()->is_active || filament()->getTenant()->billing_status === 'suspended')
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-800/30" role="alert">
          <span class="font-bold">{{ __('Suspended Project') }}:</span> {{ __('This project is currently inactive due to billing issues. Read-only access is permitted to view configuration, but editing, deployment, synchronization, and ownership transfer options are blocked.') }}
        </div>
    @endif

    @if(filament()->getTenant()->last_deployed_at)
        <div class="p-4 mb-4 text-sm text-gray-800 rounded-lg bg-gray-50 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-800/30" role="alert">
          <span class="font-bold">{{ __('Last deployment') }}:</span> {{ filament()->getTenant()->last_deployed_at->format('M j, Y H:i') }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" :disabled="!filament()->getTenant()->is_active || filament()->getTenant()->billing_status === 'suspended'">
                {{ __('Save & Push Changes') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

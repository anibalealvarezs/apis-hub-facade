<x-filament-panels::page>
    <div class="space-y-6">
        @if(!filament()->getTenant()->is_active || filament()->getTenant()->billing_status === 'suspended')
            <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-800/30" role="alert">
              <span class="font-bold">{{ __('Suspended Project') }}:</span> {{ __('This project is currently inactive due to billing issues. Read-only access is permitted to view configuration, but editing, deployment, synchronization, and ownership transfer options are blocked.') }}
            </div>
        @endif

        <div>
            <h2 class="text-lg font-medium">{{ __('Project Members') }}</h2>
            <p class="text-sm text-gray-500">{{ __('Manage users who have access to this project.') }}</p>
        </div>

        {{ $this->table }}

        <!-- Tabla de Invitaciones Pendientes -->
        @livewire('pending-invitations-table')

        <!-- Tabla de Códigos Compartidos -->
        @livewire('share-codes-table')
    </div>
</x-filament-panels::page>

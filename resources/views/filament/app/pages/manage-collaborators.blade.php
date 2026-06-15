<x-filament-panels::page>
    <div class="space-y-6" x-on:member-approved.window="location.reload()">
        @php
            $tier = filament()->getTenant()->billingProfile?->tier ?? \App\Enums\UserTier::FREE;
            $canInvite = app(\App\Services\BillingLifecycleService::class)->canInviteCollaborators($tier);
            $isSuspended = !filament()->getTenant()->is_active || filament()->getTenant()->billing_status === 'suspended';
        @endphp

        @if($isSuspended)
            <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-800/30" role="alert">
              <span class="font-bold">{{ __('Suspended Project') }}:</span> {{ __('This project is currently inactive due to billing issues. Read-only access is permitted to view configuration, but editing, deployment, synchronization, and ownership transfer options are blocked.') }}
            </div>
        @elseif(!$canInvite)
            <div class="p-4 bg-warning-50 dark:bg-warning-500/10 rounded-xl text-warning-600 dark:text-warning-400 border border-warning-200 dark:border-warning-500/20 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        <h3 class="font-bold">{{ __('Upgrade Required to Invite Collaborators') }}</h3>
                    </div>
                    <p class="text-sm">{{ __('Team collaboration is exclusively available on Ultra and Enterprise tiers. Please upgrade your associated billing profile to one of these tiers to add members to this project.') }}</p>
                </div>
                <a href="/account/account-subscription" class="shrink-0 inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-warning-600 border border-transparent rounded-lg shadow-sm hover:bg-warning-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-warning-500">
                    {{ __('Manage Subscription') }}
                </a>
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

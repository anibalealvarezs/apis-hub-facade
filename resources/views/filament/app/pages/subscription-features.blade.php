<x-filament-panels::page>
    <div class="space-y-6">
        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
            {{ __('Permissions define what tasks users can perform based on their assigned role, but <strong>Tiers</strong> define the strategic capabilities available for the project as a whole.') }}
        </div>

        <x-filament::grid default="1" md="2" xl="4" class="gap-6">

            <!-- FREE TIER -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-paper-airplane" class="h-6 w-6 text-gray-500"/>
                        Free
                    </div>
                </x-slot>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    {{ __('Basic access plan. Limited functionalities designed for testing and small projects.') }}
                </p>
                <div class="space-y-2 mb-4">
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-white/10">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Projects') }}</span>
                        <x-filament::badge color="gray">{{ __('1 maximum') }}</x-filament::badge>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-white/10">
                        <span
                            class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Accounts to Sync') }}</span>
                        <x-filament::badge color="gray">{{ __('5 maximum') }}</x-filament::badge>
                    </div>
                </div>
                <ul class="space-y-3 pt-2">
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-400 shrink-0"/>
                        <span class="text-sm">{{ __('Basic data synchronization') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-400 shrink-0"/>
                        <span class="text-sm">{{ __('Exclusive use by the owner') }}</span>
                    </li>
                </ul>
            </x-filament::section>

            <!-- PRO TIER -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400">
                        <x-filament::icon icon="heroicon-o-rocket-launch" class="h-6 w-6"/>
                        Pro
                    </div>
                </x-slot>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    {{ __('Ideal for agencies and small teams. Expanded capacity for collaboration.') }}
                </p>
                <div class="space-y-2 mb-4">
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-white/10">
                        <span
                            class="text-sm font-medium text-primary-700 dark:text-primary-400">{{ __('Projects') }}</span>
                        <x-filament::badge color="primary">{{ __('5 maximum') }}</x-filament::badge>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-white/10">
                        <span
                            class="text-sm font-medium text-primary-700 dark:text-primary-400">{{ __('Accounts to Sync') }}</span>
                        <x-filament::badge color="primary">{{ __('100 maximum') }}</x-filament::badge>
                    </div>
                </div>
                <ul class="space-y-3 pt-2">
                    <li class="flex items-start gap-2 opacity-75">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-primary-400 shrink-0"/>
                        <span class="text-sm">{{ __('Basic data synchronization') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-primary-500 shrink-0"/>
                        <span class="text-sm font-medium">{{ __('Invite users to collaborate') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-primary-500 shrink-0"/>
                        <span class="text-sm font-medium">{{ __('Public views for clients') }} <span
                                class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">{{ __('(coming soon)') }}</span></span>
                    </li>
                </ul>
            </x-filament::section>

            <!-- ULTRA TIER -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2 text-purple-600 dark:text-purple-400">
                        <x-filament::icon icon="heroicon-o-bolt" class="h-6 w-6"/>
                        Ultra
                    </div>
                </x-slot>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    {{ __('For automated, massive operations and full programmatic access.') }}
                </p>
                <div class="space-y-2 mb-4">
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-white/10">
                        <span
                            class="text-sm font-medium text-purple-700 dark:text-purple-400">{{ __('Projects') }}</span>
                        <x-filament::badge color="purple">{{ __('15 maximum') }}</x-filament::badge>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-white/10">
                        <span
                            class="text-sm font-medium text-purple-700 dark:text-purple-400">{{ __('Accounts to Sync') }}</span>
                        <x-filament::badge color="purple">{{ __('500 maximum') }}</x-filament::badge>
                    </div>
                </div>
                <ul class="space-y-3 pt-2">
                    <li class="flex items-start gap-2 opacity-75">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-purple-400 shrink-0"/>
                        <span class="text-sm">{{ __('Basic data synchronization') }}</span>
                    </li>
                    <li class="flex items-start gap-2 opacity-75">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-purple-400 shrink-0"/>
                        <span class="text-sm">{{ __('Invite users to collaborate') }}</span>
                    </li>
                    <li class="flex items-start gap-2 opacity-75">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-purple-400 shrink-0"/>
                        <span class="text-sm">{{ __('Public views for clients') }} <span
                                class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">{{ __('(coming soon)') }}</span></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-purple-500 shrink-0"/>
                        <span class="text-sm font-medium">{{ __('Full Access to APIs Hub API') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-purple-500 shrink-0"/>
                        <span class="text-sm font-medium">{{ __('Internal integrations capacity') }}</span>
                    </li>
                </ul>
            </x-filament::section>

            <!-- ENTERPRISE TIER -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2 text-warning-500 dark:text-warning-400">
                        <x-filament::icon icon="heroicon-o-building-office-2" class="h-6 w-6"/>
                        Enterprise
                    </div>
                </x-slot>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    {{ __('Corporate-grade solution with dedicated infrastructure and support.') }}
                </p>
                <div class="space-y-2 mb-4">
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-white/10">
                        <span
                            class="text-sm font-medium text-warning-700 dark:text-warning-400">{{ __('Projects') }}</span>
                        <x-filament::badge color="warning">{{ __('Custom (Base 15)') }}</x-filament::badge>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-white/10">
                        <span
                            class="text-sm font-medium text-warning-700 dark:text-warning-400">{{ __('Accounts to Sync') }}</span>
                        <x-filament::badge color="warning">{{ __('Custom (Base 500)') }}</x-filament::badge>
                    </div>
                </div>
                <ul class="space-y-3 pt-2">
                    <li class="flex items-start gap-2 opacity-75">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-purple-400 shrink-0"/>
                        <span class="text-sm">{{ __('Basic data synchronization') }}</span>
                    </li>
                    <li class="flex items-start gap-2 opacity-75">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-purple-400 shrink-0"/>
                        <span class="text-sm">{{ __('Invite users to collaborate') }}</span>
                    </li>
                    <li class="flex items-start gap-2 opacity-75">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-purple-400 shrink-0"/>
                        <span class="text-sm">{{ __('Public views for clients') }} <span
                                class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">{{ __('(coming soon)') }}</span></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-purple-500 shrink-0"/>
                        <span class="text-sm font-medium">{{ __('Full Access to APIs Hub API') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-purple-500 shrink-0"/>
                        <span class="text-sm font-medium">{{ __('Internal integrations capacity') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-warning-500 shrink-0"/>
                        <span class="text-sm font-medium">{{ __('Share Billing Profiles') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-warning-500 shrink-0"/>
                        <span class="text-sm font-medium">{{ __('Support and Guaranteed SLA Stability') }}</span>
                    </li>
                </ul>
            </x-filament::section>

        </x-filament::grid>

        <x-filament::section icon="heroicon-o-information-circle" icon-color="warning">
            <x-slot name="heading">
                <span class="text-warning-600 dark:text-warning-400">{{ __('Important Note on API Access') }}</span>
            </x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                {!! __('api_credentials_notice', [
                    'ultra' => '<strong>Ultra</strong>',
                    'enterprise' => '<strong>Enterprise</strong>',
                    'free' => '<em>Free</em>',
                    'pro' => '<em>Pro</em>'
                ]) !!}
            </p>
        </x-filament::section>
    </div>
</x-filament-panels::page>

<x-filament-panels::page>
    <div class="space-y-6">
        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
            {!! __('permissions_vs_tiers', [
                'tiers' => '<strong>Tiers</strong>'
            ]) !!}
        </div>

        <x-filament::grid default="1" md="2" xl="4" class="gap-6">

            <!-- FREE TIER -->
                    @php
            $id = \Illuminate\Support\Str::slug('Free');
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="{ copied: false }">
                    <x-filament::icon icon="heroicon-o-paper-airplane" class="h-6 w-6 text-gray-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           navigator.clipboard.writeText(window.location.origin + window.location.pathname + '#' + $id);
                           copied = true;
                           setTimeout(() => copied = false, 2000);
                       ">
                        <span>Free</span>
                        <x-filament::icon 
                            icon="heroicon-o-link" 
                            class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" 
                            x-show="!copied"
                        />
                        <x-filament::icon 
                            icon="heroicon-o-check" 
                            class="h-4 w-4 text-success-500" 
                            x-show="copied"
                            style="display: none;"
                        />
                    </a>
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
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-500 shrink-0"/>
                        <span class="text-sm">{{ __('Exclusive use by the owner') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-primary-400 shrink-0"/>
                        <span class="text-sm">{{ __('Custom KPIs') }}  <span
                                class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">({{ __('up to') }} 5)</span></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-primary-400 shrink-0"/>
                        <span class="text-sm">{{ __('Private dashboards') }}  <span
                                class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">({{ __('up to') }} 1)</span></span>
                    </li>
                </ul>
            </x-filament::section>

            <!-- PRO TIER -->
                    @php
            $id = \Illuminate\Support\Str::slug('Pro');
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400 group" x-data="{ copied: false }">
                    <x-filament::icon icon="heroicon-o-rocket-launch" class="h-6 w-6" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           navigator.clipboard.writeText(window.location.origin + window.location.pathname + '#' + $id);
                           copied = true;
                           setTimeout(() => copied = false, 2000);
                       ">
                        <span>Pro</span>
                        <x-filament::icon 
                            icon="heroicon-o-link" 
                            class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" 
                            x-show="!copied"
                        />
                        <x-filament::icon 
                            icon="heroicon-o-check" 
                            class="h-4 w-4 text-success-500" 
                            x-show="copied"
                            style="display: none;"
                        />
                    </a>
                </div>
            </x-slot>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    {{ __('Ideal for enthusiasts and freelancers. Includes client-focused features.') }}
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
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-400 shrink-0"/>
                        <span class="text-sm">{{ __('Basic data synchronization') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-500 shrink-0"/>
                        <span class="text-sm">{{ __('Exclusive use by the owner') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-400 shrink-0"/>
                        <span class="text-sm">{{ __('Custom KPIs') }}  <span
                                class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">({{ __('up to') }} 20)</span></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-400 shrink-0"/>
                        <span class="text-sm">{{ __('Private dashboards') }}  <span
                                class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">({{ __('up to') }} 5)</span></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-primary-500 shrink-0"/>
                        <span class="text-sm font-medium">{{ __('Public dashboards') }} <span
                                class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">({{ __('up to') }} 5)</span></span>
                    </li>
                </ul>
            </x-filament::section>

            <!-- ULTRA/FOUNDER TIER -->
                    @php
            $id = \Illuminate\Support\Str::slug('Ultra');
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-purple-600 dark:text-purple-400 group" x-data="{ copied: false }">
                    <x-filament::icon icon="heroicon-o-bolt" class="h-6 w-6" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           navigator.clipboard.writeText(window.location.origin + window.location.pathname + '#' + $id);
                           copied = true;
                           setTimeout(() => copied = false, 2000);
                       ">
                        <span>Ultra / Founder</span>
                        <x-filament::icon 
                            icon="heroicon-o-link" 
                            class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" 
                            x-show="!copied"
                        />
                        <x-filament::icon 
                            icon="heroicon-o-check" 
                            class="h-4 w-4 text-success-500" 
                            x-show="copied"
                            style="display: none;"
                        />
                    </a>
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
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-primary-400 shrink-0"/>
                        <span class="text-sm">{{ __('Invite users to collaborate') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-400 shrink-0"/>
                        <span class="text-sm">{{ __('Custom KPIs') }}  <span
                                class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">({{ __('up to') }} 30)</span></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-400 shrink-0"/>
                        <span class="text-sm">{{ __('Private dashboards') }}  <span
                                class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">({{ __('up to') }} 15)</span></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-400 shrink-0"/>
                        <span class="text-sm font-medium">{{ __('Public dashboards') }} <span
                                class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">({{ __('up to') }} 15)</span></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-primary-500 shrink-0"/>
                        <span class="text-sm font-medium">{{ __('Full Access to APIs Hub API') }}</span>
                    </li>
                </ul>
            </x-filament::section>

            <!-- ENTERPRISE TIER -->
                    @php
            $id = \Illuminate\Support\Str::slug('Enterprise');
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-warning-500 dark:text-warning-400 group" x-data="{ copied: false }">
                    <x-filament::icon icon="heroicon-o-building-office-2" class="h-6 w-6" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           navigator.clipboard.writeText(window.location.origin + window.location.pathname + '#' + $id);
                           copied = true;
                           setTimeout(() => copied = false, 2000);
                       ">
                        <span>Enterprise</span>
                        <x-filament::icon 
                            icon="heroicon-o-link" 
                            class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" 
                            x-show="!copied"
                        />
                        <x-filament::icon 
                            icon="heroicon-o-check" 
                            class="h-4 w-4 text-success-500" 
                            x-show="copied"
                            style="display: none;"
                        />
                    </a>
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
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-400 shrink-0"/>
                        <span class="text-sm">{{ __('Basic data synchronization') }}</span>
                    </li>
                    <li class="flex items-start gap-2 opacity-75">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-400 shrink-0"/>
                        <span class="text-sm">{{ __('Invite users to collaborate') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-400 shrink-0"/>
                        <span class="text-sm">{{ __('Custom KPIs') }}  <span
                                class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">({{ __('unlimited') }})</span></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-400 shrink-0"/>
                        <span class="text-sm">{{ __('Private dashboards') }}  <span
                                class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">({{ __('unlimited') }})</span></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-500 shrink-0"/>
                        <span class="text-sm font-medium">{{ __('Public dashboards') }} <span
                                class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">({{ __('unlimited') }})</span></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-500 shrink-0"/>
                        <span class="text-sm font-medium">{{ __('Full Access to APIs Hub API') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-primary-500 shrink-0"/>
                        <span class="text-sm font-medium">{{ __('Share Billing Profiles') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-primary-500 shrink-0"/>
                        <span class="text-sm font-medium">{{ __('Cross-project Analytics and Dashboards') }} <span
                                class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">({{ __('coming soon') }})</span></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-primary-500 shrink-0"/>
                        <span class="text-sm font-medium">{{ __('Guaranteed SLA') }}</span>
                    </li>
                </ul>
            </x-filament::section>

        </x-filament::grid>

        @php
            $id = \Illuminate\Support\Str::slug(__('Important Note on API Access'));
        @endphp
        <x-filament::section id="{{ $id }}" icon="heroicon-o-information-circle" icon-color="warning">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="{ copied: false }">
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           navigator.clipboard.writeText(window.location.origin + window.location.pathname + '#' + '{{ $id }}');
                           copied = true;
                           setTimeout(() => copied = false, 2000);
                       ">
                        <span class="text-warning-600 dark:text-warning-400">{{ __('Important Note on API Access') }}</span>
                        <x-filament::icon 
                            icon="heroicon-o-link" 
                            class="h-4 w-4 text-warning-400 opacity-0 group-hover:opacity-100 transition-opacity" 
                            x-show="!copied"
                        />
                        <x-filament::icon 
                            icon="heroicon-o-check" 
                            class="h-4 w-4 text-success-500" 
                            x-show="copied"
                            style="display: none;"
                        />
                    </a>
                </div>
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

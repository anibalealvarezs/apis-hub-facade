<x-filament-panels::page>
    @php
        $tenantPrefix = !empty($tenantSubdomain) ? "/app/{$tenantSubdomain}" : "/app";

        $sections = [
            'Workspace & Navigation' => [
                'icon' => 'heroicon-o-computer-desktop',
                'tours' => [
                    [
                        'id' => 'global-ui',
                        'name' => __('Global Workspace & Main Menu'),
                        'description' => __('Project switcher, top-bar telemetry & timezone clock, account controls, and main menu overview.'),
                        'badge' => __('Essential'),
                        'url' => $tenantPrefix,
                    ],
                ],
            ],
            'Setup & Ingestion' => [
                'icon' => 'heroicon-o-server-stack',
                'tours' => [
                    [
                        'id' => 'data-sources',
                        'name' => __('Data Sources & Channels'),
                        'description' => __('OAuth authentication, provider channel switching, and tracked properties/ad accounts selection.'),
                        'badge' => __('Integration'),
                        'url' => "{$tenantPrefix}/data-sources",
                    ],
                    [
                        'id' => 'project-settings',
                        'name' => __('Project Settings & Deployment'),
                        'description' => __('Timezone configuration, dedicated worker provisioning, and live deployment activity logs.'),
                        'badge' => __('Core'),
                        'url' => "{$tenantPrefix}/project-settings",
                    ],
                    [
                        'id' => 'telemetry',
                        'name' => __('Sync Telemetry & Worker Health'),
                        'description' => __('Remote worker queue status, global backfill progress, and per-channel sync drill-downs.'),
                        'badge' => __('Monitoring'),
                        'url' => "{$tenantPrefix}/telemetry",
                    ],
                ],
            ],
            'Analytics & Insights' => [
                'icon' => 'heroicon-o-chart-bar',
                'tours' => array_values(array_filter([
                    [
                        'id' => 'data-explorer',
                        'name' => __('Data Explorer'),
                        'description' => __('Raw normalized records inspection, dimension breakdowns, and currency conversions.'),
                        'badge' => __('Analytics'),
                        'url' => "{$tenantPrefix}/data-explorer",
                    ],
                    [
                        'id' => 'asset-groups',
                        'name' => __('Asset Groups'),
                        'description' => __('Cross-channel asset clustering for global dashboard filtering.'),
                        'badge' => __('Structure'),
                        'url' => "{$tenantPrefix}/asset-groups",
                    ],
                    [
                        'id' => 'dashboards',
                        'name' => __('Dashboards & Visual Builder'),
                        'description' => __('Widget tile customization, widget palette, and asset group selector controls.'),
                        'badge' => __('Analytics'),
                        'url' => $dashboardBuilderUrl ?? "{$tenantPrefix}/dashboards",
                    ],
                    $alertsAvailable ?? false ? [
                        'id' => 'alerts',
                        'name' => __('Automated Alerts & Anomalies'),
                        'description' => __('AST calculation lines, threshold bounds, and background cron schedules.'),
                        'badge' => __('Proactive'),
                        'url' => "{$tenantPrefix}/alerts",
                    ] : null,
                    [
                        'id' => 'custom-kpis',
                        'name' => __('Custom KPIs (Blended AST)'),
                        'description' => __('Cross-channel blended mathematical formulas and AST validation.'),
                        'badge' => __('Advanced'),
                        'url' => "{$tenantPrefix}/custom-kpis",
                    ],
                    [
                        'id' => 'derived-metrics',
                        'name' => __('Derived Metrics'),
                        'description' => __('Channel-specific calculated metric transformations.'),
                        'badge' => __('Advanced'),
                        'url' => "{$tenantPrefix}/derived-metrics",
                    ],
                ])),
            ],
            'Administration & Account' => [
                'icon' => 'heroicon-o-user-group',
                'tours' => [
                    [
                        'id' => 'collaborators',
                        'name' => __('Team & Collaborators'),
                        'description' => __('User invitations, role assignments, and granular asset group restrictions.'),
                        'badge' => __('Team'),
                        'url' => "{$tenantPrefix}/manage-collaborators",
                    ],
                    [
                        'id' => 'billing',
                        'name' => __('Billing & Subscriptions'),
                        'description' => __('Billing profile management, subscription tiers, and payment checkouts.'),
                        'badge' => __('Account'),
                        'url' => '/account/account-subscription',
                    ],
                ],
            ],
        ];

        $allTourIds = [];
        foreach ($sections as $sec) {
            foreach ($sec['tours'] as $t) {
                $allTourIds[] = $t['id'];
            }
        }
    @endphp

    <div x-data="onboardingSettings(@js($allTourIds))" class="space-y-6">
        <!-- Header Card with Master Controls -->
        <div class="p-6 onboarding-header-card rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                    {{ __('Interactive Walkthroughs & Onboarding Guides') }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-xl">
                    {{ __('Enable or disable specific tour flows across your workspaces. Enabled guides will automatically pop up the first time you visit their corresponding pages.') }}
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button"
                        @click="enableAll()"
                        class="px-3.5 py-2 text-xs font-bold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-500/10 border border-primary-200 dark:border-primary-500/20 rounded-xl hover:bg-primary-100 dark:hover:bg-primary-500/20 transition-all">
                    {{ __('Enable All Guides') }}
                </button>
                <button type="button"
                        @click="disableAll()"
                        class="px-3.5 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl hover:bg-gray-200 dark:hover:bg-white/10 transition-all">
                    {{ __('Disable All Guides') }}
                </button>
            </div>
        </div>

        <!-- Groups Breakdown -->
        <div class="space-y-6">
            @foreach($sections as $groupTitle => $group)
                <div class="space-y-3">
                    <div class="flex items-center gap-2 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider pb-1 border-b border-gray-200 dark:border-white/10">
                        <x-dynamic-component :component="$group['icon']" class="w-4 h-4 text-primary-500" />
                        <span>{{ __($groupTitle) }}</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($group['tours'] as $tour)
                            <div class="p-6 onboarding-card rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 shadow-sm flex flex-col justify-between gap-4 transition-all hover:border-gray-300 dark:hover:border-white/20">
                                <div>
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white leading-snug">
                                            {{ $tour['name'] }}
                                        </h4>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bd-text-2xs font-bold uppercase tracking-wider bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-white/10">
                                            {{ $tour['badge'] }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">
                                        {{ $tour['description'] }}
                                    </p>
                                </div>

                                <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-white/5">
                                    <button type="button"
                                            @click="playTour('{{ $tour['id'] }}', '{{ $tour['url'] ?? '' }}')"
                                            class="inline-flex items-center gap-1.5 text-xs font-bold text-primary-600 dark:text-primary-400 hover:text-primary-500 transition-colors">
                                        <x-heroicon-m-play class="w-4 h-4 text-primary-500" />
                                        <span>{{ __('Play Tour') }}</span>
                                    </button>

                                    <!-- Switch -->
                                    <button type="button" 
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2" 
                                            :class="isEnabled('{{ $tour['id'] }}') ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700'" 
                                            @click="toggle('{{ $tour['id'] }}')" 
                                            role="switch" 
                                            :aria-checked="isEnabled('{{ $tour['id'] }}').toString()">
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" 
                                              :class="isEnabled('{{ $tour['id'] }}') ? 'translate-x-5' : 'translate-x-0'"></span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>

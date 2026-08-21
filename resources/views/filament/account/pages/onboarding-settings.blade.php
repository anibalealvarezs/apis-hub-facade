<x-filament-panels::page>
    <div x-data="{
        storageKey: 'apis_hub_completed_tours',
        completedTours: [],
        init() {
            this.load();
            window.addEventListener('tour-status-changed', () => this.load());
        },
        load() {
            try {
                const raw = localStorage.getItem(this.storageKey);
                this.completedTours = raw ? JSON.parse(raw) : [];
            } catch {
                this.completedTours = [];
            }
        },
        isEnabled(tourId) {
            return !this.completedTours.includes(tourId);
        },
        toggle(tourId) {
            if (this.isEnabled(tourId)) {
                if (!this.completedTours.includes(tourId)) {
                    this.completedTours.push(tourId);
                }
            } else {
                this.completedTours = this.completedTours.filter(id => id !== tourId);
            }
            localStorage.setItem(this.storageKey, JSON.stringify(this.completedTours));
            window.dispatchEvent(new CustomEvent('tour-status-changed', { detail: { tourId } }));
        },
        enableAll() {
            this.completedTours = [];
            localStorage.setItem(this.storageKey, JSON.stringify([]));
            window.dispatchEvent(new CustomEvent('tour-status-changed', { detail: { all: true } }));
        },
        disableAll(allIds) {
            this.completedTours = [...allIds];
            localStorage.setItem(this.storageKey, JSON.stringify(this.completedTours));
            window.dispatchEvent(new CustomEvent('tour-status-changed', { detail: { all: false } }));
        },
        playTour(tourId) {
            if (window.apisHubTours) {
                window.apisHubTours.start(tourId, true);
            } else {
                window.dispatchEvent(new CustomEvent('start-page-tour', { detail: { tourId, force: true } }));
            }
        }
    }" class="space-y-6">

        @php
            $sections = [
                'Workspace & Navigation' => [
                    'icon' => 'heroicon-o-computer-desktop',
                    'tours' => [
                        [
                            'id' => 'global-ui',
                            'name' => __('Global Workspace & Main Menu'),
                            'description' => __('Project switcher, top-bar telemetry & timezone clock, account controls, and main menu overview.'),
                            'badge' => __('Essential'),
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
                        ],
                        [
                            'id' => 'project-settings',
                            'name' => __('Project Settings & Deployment'),
                            'description' => __('Timezone configuration, dedicated worker provisioning, and live deployment activity logs.'),
                            'badge' => __('Core'),
                        ],
                        [
                            'id' => 'telemetry',
                            'name' => __('Sync Telemetry & Worker Health'),
                            'description' => __('Remote worker queue status, global backfill progress, and per-channel sync drill-downs.'),
                            'badge' => __('Monitoring'),
                        ],
                    ],
                ],
                'Analytics & Insights' => [
                    'icon' => 'heroicon-o-chart-bar',
                    'tours' => [
                        [
                            'id' => 'data-explorer',
                            'name' => __('Data Explorer'),
                            'description' => __('Raw normalized records inspection, dimension breakdowns, and currency conversions.'),
                            'badge' => __('Analytics'),
                        ],
                        [
                            'id' => 'asset-groups',
                            'name' => __('Asset Groups'),
                            'description' => __('Cross-channel asset clustering for global dashboard filtering.'),
                            'badge' => __('Structure'),
                        ],
                        [
                            'id' => 'dashboards',
                            'name' => __('Dashboards & Visual Builder'),
                            'description' => __('GridStack tile customization, widget palette, and asset group selector controls.'),
                            'badge' => __('Analytics'),
                        ],
                        [
                            'id' => 'alerts',
                            'name' => __('Automated Alerts & Anomalies'),
                            'description' => __('AST calculation lines, threshold bounds, and background cron schedules.'),
                            'badge' => __('Proactive'),
                        ],
                        [
                            'id' => 'custom-kpis',
                            'name' => __('Custom KPIs (Blended AST)'),
                            'description' => __('Cross-channel blended mathematical formulas and AST validation.'),
                            'badge' => __('Advanced'),
                        ],
                        [
                            'id' => 'derived-metrics',
                            'name' => __('Derived Metrics'),
                            'description' => __('Channel-specific calculated metric transformations.'),
                            'badge' => __('Advanced'),
                        ],
                    ],
                ],
                'Administration & Account' => [
                    'icon' => 'heroicon-o-user-group',
                    'tours' => [
                        [
                            'id' => 'collaborators',
                            'name' => __('Team & Collaborators'),
                            'description' => __('User invitations, role assignments, and granular asset group restrictions.'),
                            'badge' => __('Team'),
                        ],
                        [
                            'id' => 'billing',
                            'name' => __('Billing & Subscriptions'),
                            'description' => __('Billing profile management, subscription tiers, and payment checkouts.'),
                            'badge' => __('Account'),
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

        <!-- Header Card with Master Controls -->
        <div class="p-6 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
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
                        @click="disableAll(@js($allTourIds))"
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
                            <div class="p-5 rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 shadow-sm flex flex-col justify-between gap-4 transition-all hover:border-gray-300 dark:hover:border-white/20">
                                <div>
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white leading-snug">
                                            {{ $tour['name'] }}
                                        </h4>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-white/10">
                                            {{ $tour['badge'] }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">
                                        {{ $tour['description'] }}
                                    </p>
                                </div>

                                <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-white/5">
                                    <button type="button"
                                            @click="playTour('{{ $tour['id'] }}')"
                                            class="inline-flex items-center gap-1.5 text-xs font-bold text-primary-600 dark:text-primary-400 hover:text-primary-500 transition-colors">
                                        <x-heroicon-m-play class="w-4 h-4 text-primary-500" />
                                        <span>{{ __('Play Tour') }}</span>
                                    </button>

                                    <!-- Switch -->
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox"
                                               class="sr-only peer"
                                               :checked="isEnabled('{{ $tour['id'] }}')"
                                               @change="toggle('{{ $tour['id'] }}')">
                                        <div class="w-10 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-800 border border-gray-300 dark:border-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-500 peer-checked:border-primary-500"></div>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>

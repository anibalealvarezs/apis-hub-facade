<x-filament-panels::page>
    <div class="flex flex-col gap-6"
         x-data="{
            activeTab: @entangle('activeChannel'),
            isOwner: {{ $this->isOwner() ? 'true' : 'false' }},
            ownerLimit: {{ $this->getOwnerLimit() }},
            globalLedgerCount: {{ $this->getGlobalLedgerCount() }},
            lockedAssets: {{ json_encode($this->getLockedAssets()) }},
            lockStates: @js($this->getAssetLockStates()),
            cycleBounds: @js($this->getCycleBounds()),
            projectDeploymentTime: @js($this->getProjectDeploymentTime()),
            assetGroupsData: @js($this->getAssetGroupsData()),
            currentTime: new Date().getTime(),
            cycleLabel: '{{ __('Cycle') }}',

            init() {
                setInterval(() => {
                    this.currentTime = new Date().getTime();
                }, 1000);

                let apply = () => document.querySelectorAll('.fi-fo-repeater-item').forEach(el => { if (el.style.position !== 'relative') el.style.position = 'relative'; });
                this.$nextTick(apply);
                new MutationObserver(apply).observe(document.body, { childList: true, subtree: true });
            },

            quotaLockedLabel: '{{ __('Quota Locked') }}',
            lockedUntilCycleEndLabel: '{{ __('Locked until cycle end') }}',
            gracePeriodPausedLabel: '{{ __('Grace Period paused (Waiting for deployment)') }}',
            gracePeriodEndedLabel: '{{ __('Grace Period (Ended)') }}',
            gracePeriodLabel: '{{ __('Grace Period (Ends in') }}',

            getAssetBadgeColor(id) {
                if (!id || !this.lockStates[id]) return null;
                let lock = this.lockStates[id];

                if (lock.status === 'locked') return '#22c55e';
                if (lock.status === 'pending_release') return '#ef4444';
                if (lock.status === 'staged') {
                    if (!this.projectDeploymentTime) return '#9ca3af';
                    let stagedAt = new Date(lock.staged_at).getTime();
                    let endsAt = stagedAt + (2 * 60 * 60 * 1000);
                    if (endsAt - this.currentTime <= 0) return '#ef4444';
                    return '#f97316'; // orange (warning-500)
                }
                return null;
            },

            getBadgeStyle(id) {
                let color = this.getAssetBadgeColor(id);
                if (!color) return 'display:none';
                return `position:absolute;top:0.75rem;right:0.75rem;width:1rem;height:1rem;border-radius:9999px;box-shadow:0 0 0 2px white;background-color:${color}`;
            },

            getAssetBadgeLabel(id) {
                if (!id || !this.lockStates[id]) return '';
                let lock = this.lockStates[id];

                if (lock.status === 'locked') return this.quotaLockedLabel;
                if (lock.status === 'pending_release') return `${this.lockedUntilCycleEndLabel} (${this.cycleBounds.ends_at})`;
                if (lock.status === 'staged') {
                    if (!this.projectDeploymentTime) return this.gracePeriodPausedLabel;
                    let stagedAt = new Date(lock.staged_at).getTime();
                    let endsAt = stagedAt + (2 * 60 * 60 * 1000);
                    let remainingMs = endsAt - this.currentTime;
                    if (remainingMs <= 0) return this.gracePeriodEndedLabel;
                    let totalSec = Math.floor(remainingMs / 1000);
                    let h = Math.floor(totalSec / 3600);
                    let m = Math.floor((totalSec % 3600) / 60);
                    let s = totalSec % 60;
                    let timeStr = h > 0 ? `${h}h ${m}m ${s}s` : `${m}m ${s}s`;
                    return `${this.gracePeriodLabel} ${timeStr})`;
                }
                return '';
            },

            getAssetGroups(id) {
                if (!this.assetGroupsData || !this.assetGroupsData.assetMap) return [];
                return this.assetGroupsData.assetMap[id] || [];
            },

            getAssetGroupsTooltip(id) {
                let groups = this.getAssetGroups(id);
                if (groups.length === 0) return '';
                let title = '{{ __('Groups:') }}';
                let list = groups.map(g => `<li class='ml-2'>&bull; ${g}</li>`).join('');
                return `<div class='text-left font-sans'><strong class='block mb-1 font-medium text-gray-200'>${title}</strong><ul class='text-xs text-gray-300 leading-tight'>${list}</ul></div>`;
            },

            getAssetBadgeText(id) {
                if (!id || !this.lockStates[id]) return '';
                let lock = this.lockStates[id];
                if (lock.status === 'staged' && this.projectDeploymentTime) {
                    let stagedAt = new Date(lock.staged_at).getTime();
                    let endsAt = stagedAt + (2 * 60 * 60 * 1000);
                    let remainingMs = endsAt - this.currentTime;
                    if (remainingMs > 0) {
                        let totalSec = Math.floor(remainingMs / 1000);
                        let h = Math.floor(totalSec / 3600);
                        let m = Math.floor((totalSec % 3600) / 60);
                        let s = totalSec % 60;
                        return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
                    }
                }
                return '';
            },
            getAssetBadgeTextColor(id) {
                if (!id || !this.lockStates[id]) return '';
                let lock = this.lockStates[id];
                if (lock.status === 'staged' && this.projectDeploymentTime) {
                    let stagedAt = new Date(lock.staged_at).getTime();
                    let endsAt = stagedAt + (2 * 60 * 60 * 1000);
                    if (endsAt - this.currentTime > 0) return 'text-orange-500 dark:text-orange-400';
                }
                return '';
            },

            get formAssets() {
                let formAssets = new Set();
                let data = $wire.get('data') || {};

                function scan(obj) {
                    if (typeof obj === 'object' && obj !== null) {
                        if (obj.hasOwnProperty('enabled') && (obj.hasOwnProperty('url') || obj.hasOwnProperty('id') || obj.hasOwnProperty('lost_access') || obj.hasOwnProperty('platformId'))) {
                            if (obj.enabled && !obj.lost_access) {
                                let id = obj.id || obj.url || obj.platformId;
                                if (id) formAssets.add(id);
                            }
                            return;
                        }
                        for (let key in obj) scan(obj[key]);
                    }
                }

                for (let key in data) scan(data[key]);
                return formAssets;
            },

            get currentProjectUsage() {
                let locked = new Set(this.lockedAssets);
                let form = this.formAssets;
                let union = new Set([...locked, ...form]);
                return union.size;
            },

            get usageData() {
                let lockedSize = new Set(this.lockedAssets).size;
                let newlyStaged = this.currentProjectUsage - lockedSize;

                if (this.isOwner) {
                    return {
                        usage: this.globalLedgerCount + newlyStaged,
                        limit: this.ownerLimit
                    };
                } else {
                    let availableGlobalQuota = Math.max(0, this.ownerLimit - this.globalLedgerCount);
                    return {
                        usage: this.currentProjectUsage,
                        limit: lockedSize + availableGlobalQuota
                    };
                }
            },

            get selectedCount() {
                return this.usageData.usage;
            },

            get maxAssets() {
                return this.usageData.limit;
            },

            getChannelCount(channelKey) {
                let count = 0;
                let data = $wire.get('data') || {};
                let channelData = data[channelKey] || {};

                function scan(obj) {
                    if (typeof obj === 'object' && obj !== null) {
                        if (obj.hasOwnProperty('enabled') && (obj.hasOwnProperty('url') || obj.hasOwnProperty('id') || obj.hasOwnProperty('lost_access') || obj.hasOwnProperty('platformId'))) {
                            if (obj.enabled && !obj.lost_access) count++;
                            return;
                        }
                        for (let key in obj) scan(obj[key]);
                    }
                }

                scan(channelData);
                return count;
            },
            getProviderCount(providerKey) {
                let count = 0;
                let providers = {{ json_encode($this->getProviders()) }};

                if (providerKey && providers[providerKey]) {
                    providers[providerKey].channels.forEach(ch => {
                        count += this.getChannelCount(ch.key);
                    });
                } else if (!providerKey) {
                    for (let pk in providers) {
                        providers[pk].channels.forEach(ch => {
                            count += this.getChannelCount(ch.key);
                        });
                    }
                }
                return count;
            }
         }">

        <template x-teleport="#tier-usage-header-target">
            <div class="flex items-center gap-4 text-sm transition-colors"
                 :class="selectedCount >= (maxAssets * 0.8) ? 'text-danger-600 dark:text-danger-500' : (selectedCount < (maxAssets * 0.5) ? 'text-success-600 dark:text-success-500' : 'text-gray-700 dark:text-gray-300')">
                <span class="inline-flex items-center px-3 py-1 rounded text-xs font-medium bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-white/5 shadow-sm">
                    <a href="{{ App\Filament\App\Pages\AssetBillingReference::getUrl() }}#{{ \Illuminate\Support\Str::slug(__('Annual vs. Monthly Subscriptions')) }}" target="_blank" x-tooltip="{ content: '{{ __('Click to read more about how billing cycles work.') }}', theme: $store.theme }" class="text-gray-500 dark:text-gray-400 font-normal pr-1 hover:underline cursor-help transition-colors hover:text-primary-600 dark:hover:text-primary-400">
                        <span x-text="cycleLabel + ':'"></span>
                    </a>
                    <span class="text-gray-800 dark:text-gray-200 tracking-wide font-semibold" x-text="`${cycleBounds.starts_at} - ${cycleBounds.ends_at}`"></span>
                    <span class="text-gray-300 dark:text-gray-600 px-3">|</span>
                    <a href="{{ App\Filament\App\Pages\AssetBillingReference::getUrl() }}#{{ \Illuminate\Support\Str::slug(__('Releasing Quota & Billing Rollover')) }}" target="_blank" x-tooltip="{ content: '{{ __('Click to read more about how the quota is released and rolled over.') }}', theme: $store.theme }" class="text-gray-500 dark:text-gray-400 font-normal pr-1 hover:underline cursor-help transition-colors hover:text-primary-600 dark:hover:text-primary-400">
                        {{ __('Quota Resets:') }}
                    </a>
                    <span class="text-gray-800 dark:text-gray-200 tracking-wide font-semibold" x-text="cycleBounds.next_quota_reset"></span>
                </span>
                <div class="flex items-center gap-2">
                    <span class="text-xs uppercase font-semibold tracking-wide text-gray-500 dark:text-gray-400">{{ __('Tier Usage') }}</span>
                    <div class="font-bold text-base">
                        <span x-text="selectedCount"></span> / <span x-text="maxAssets"></span>
                    </div>
                </div>
            </div>
        </template>

        <div class="flex flex-col md:flex-row gap-6 items-start relative">
        <!-- Sidebar Navigation -->
        <div class="w-full flex-shrink-0 flex flex-col gap-4 bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4 sticky top-6 max-h-[calc(100vh-2rem)] overflow-y-auto" style="max-width: 16rem;">
            @foreach($this->getProviders() as $pKey => $provider)
                @php
                    $hasActiveChannel = collect($provider['channels'])->contains('key', $activeChannel);
                @endphp
                <div class="flex flex-col gap-2" x-data="{ expanded: {{ $hasActiveChannel ? 'true' : 'false' }} }">
                    <!-- Provider Header -->
                    <div @click="expanded = !expanded" class="cursor-pointer flex items-center justify-between text-gray-900 dark:text-white font-bold border-b border-gray-100 dark:border-white/5 transition hover:text-primary-500" style="padding-bottom: 0.5rem;">
                        <div class="flex items-center gap-2">
                            @if($pKey === 'google')
                                <x-heroicon-o-globe-alt class="w-5 h-5 text-gray-500" />
                            @elseif($pKey === 'facebook')
                                <x-heroicon-o-users class="w-5 h-5 text-gray-500" />
                            @else
                                <x-heroicon-o-server-stack class="w-5 h-5 text-gray-500" />
                            @endif
                            <span class="tracking-wide">{{ $provider['label'] }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full"
                                 x-text="getProviderCount('{{ $pKey }}')">
                            </div>
                            <x-heroicon-m-chevron-down class="w-4 h-4 transition-transform text-gray-400" x-bind:class="expanded ? '' : '-rotate-90'" />
                        </div>
                    </div>

                    <!-- Nested Channels -->
                    <div x-show="expanded" x-collapse class="flex flex-col gap-1 ml-2 border-l-2 border-gray-100 dark:border-white/5 pl-2 mt-1">
                        @foreach($provider['channels'] as $channel)
                            @if($channel['status'] === 'Active')
                                <button wire:click="$set('activeChannel', '{{ $channel['key'] }}')"
                                        class="px-3 py-2 text-left rounded-lg text-sm font-medium transition-colors flex items-center justify-between"
                                        :class="activeTab === '{{ $channel['key'] }}' ? 'bg-primary-500/10 text-primary-600 dark:text-primary-400' : 'text-gray-950 dark:text-white hover:bg-gray-50 dark:hover:bg-white/5'">
                                    <span class="truncate pr-2">{{ $channel['label'] }}</span>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 px-1.5 py-0.5 rounded-md"
                                            :class="activeTab === '{{ $channel['key'] }}' ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300' : ''"
                                            x-text="getChannelCount('{{ $channel['key'] }}')">
                                        </span>
                                        @if(isset($this->data[$channel['key'].'_enabled']) && $this->data[$channel['key'].'_enabled'])
                                            <span class="flex h-2 w-2 rounded-full bg-success-500"></span>
                                        @else
                                            <span class="flex h-2 w-2 rounded-full bg-gray-300 dark:bg-gray-700"></span>
                                        @endif
                                    </div>
                                </button>
                            @elseif ($channel['status'] === 'Maintenance')
                                <button disabled data-tooltip-target="maintenance-tooltip-{{ $channel['key'] }}"
                                        class="px-3 py-2 text-left rounded-lg text-sm font-medium transition-colors flex items-center justify-between"
                                        :class="activeTab === '{{ $channel['key'] }}' ? 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'">
                                    <span class="truncate pr-2">{{ $channel['label'] }}</span>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <x-heroicon-m-exclamation-triangle class="w-5 h-5 text-yellow-600" />
                                    </div>
                                    <div id="maintenance-tooltip-{{ $channel['key'] }}" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
                                        Maintenance
                                        <div class="tooltip-arrow" data-popper-arrow></div>
                                    </div>
                                </button>
                            @else
                                <button disabled data-tooltip-target="coming-soon-tooltip-{{ $channel['key'] }}"
                                        class="px-3 py-2 text-left rounded-lg text-sm font-medium transition-colors flex items-center justify-between"
                                        :class="activeTab === '{{ $channel['key'] }}' ? 'bg-primary-500/10 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'">
                                    <span class="truncate pr-2">{{ $channel['label'] }}</span>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <x-heroicon-m-lock-closed class="w-5 h-5 text-gray-600" />
                                    </div>
                                    <div id="coming-soon-tooltip-{{ $channel['key'] }}" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700">
                                        Coming soon
                                        <div class="tooltip-arrow" data-popper-arrow></div>
                                    </div>
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Content Area -->
        <div class="w-full relative bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6" style="flex: 1 1 0%;">
            
            <div wire:loading.flex wire:target="activeChannel, save" class="absolute inset-0 rounded-xl" style="z-index: 9999; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
                <div class="fixed inset-0 flex items-center justify-center pointer-events-none" style="z-index: 10000;">
                    <svg class="animate-spin text-primary-500 drop-shadow-lg" style="width: 4rem; height: 4rem;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-6 pb-6 border-b border-gray-200 dark:border-white/10" style="margin-bottom: 1.5rem;">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $this->getChannelLabel($activeChannel) }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('Last synced') }}: {{ $this->getLastSyncTime($activeChannel) }}
                        @if(filament()->getTenant()->last_deployed_at)
                            <span class="mx-2">|</span>
                            {{ __('Last deployment') }}: {{ filament()->getTenant()->last_deployed_at->translatedFormat(__('M j, Y H:i')) }}
                        @endif
                    </p>
                </div>

                <div class="flex flex-col items-end gap-3">
                    <div class="flex items-center gap-3">
                        {{ $this->getAction('updateCredentials') }}
                        {{ $this->getAction('discoverAssets') }}
                    </div>
                </div>
            </div>

            @if(!filament()->getTenant()->is_active || filament()->getTenant()->billing_status === 'suspended')
                <div class="p-4 mb-6 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-800/30" role="alert">
                  <span class="font-bold">{{ __('Suspended Project') }}:</span> {{ __('This project is currently inactive due to billing issues. Read-only access is permitted to view configuration, but editing, deployment, synchronization, and ownership transfer options are blocked.') }}
                </div>
            @endif

            @if($this->isConnected($activeChannel) && $this->isProfileShared($activeChannel))
                <div class="mb-6 p-4 rounded-lg bg-warning-50 dark:bg-warning-500/10 border border-warning-200 dark:border-warning-500/20">
                    <div class="flex items-start gap-3">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-600 dark:text-warning-500 shrink-0 mt-0.5" />
                        <div>
                            <h3 class="text-sm font-bold text-warning-800 dark:text-warning-400">{{ __('Shared API Rate Limits') }}</h3>
                            <p class="text-sm text-warning-700 dark:text-warning-500 mt-1">
                                {{ __('This social profile is connected to multiple projects. To protect your connection stability, the API rate limits for this profile will be shared across all connected projects. Avoid syncing excessive assets simultaneously.') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            @if(!$this->isConnected($activeChannel))
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                        <x-heroicon-o-link class="w-8 h-8 text-gray-400" />
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ __('Not Connected') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mb-6">
                        {{ __('You need to authorize access to this provider before you can configure its data sources.') }}
                    </p>

                    @if(str_contains($activeChannel, 'facebook') || str_contains($activeChannel, 'google'))
                        {{ $this->getAction('connect') }}
                    @endif
                </div>
            @else

                <!-- Removed internal {{ __('Tier Usage') }} -->



                <form wire:submit="save">
                    {{ $this->form }}
                    <div class="sticky bottom-0 z-20 -mx-6 -mb-6 mt-6 p-4 flex justify-end pointer-events-none">
                        <div class="flex items-center gap-3 pointer-events-auto bg-white dark:bg-gray-800 p-3 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700">
                            {{ $this->getAction('redeployInfrastructure') }}
                            
                            <x-filament::button type="submit" color="primary" size="lg"
                                :disabled="!filament()->getTenant()->is_active || filament()->getTenant()->billing_status === 'suspended' || !auth()->user()->can('manage_channels')"
                                wire:loading.attr="disabled"
                                wire:target="save"
                                wire:confirm="{{ __('Saving this configuration will update your tracked assets and may impact your monthly billing quota.') }}<br>{{ __('Are you sure you want to proceed?') }}">
                                <span wire:loading.remove wire:target="save">{{ __('Save Configuration') }}</span>
                                <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                            </x-filament::button>
                        </div>
                    </div>
                </form>
            @endif

        </div>
    </div>
</div>
</x-filament-panels::page>

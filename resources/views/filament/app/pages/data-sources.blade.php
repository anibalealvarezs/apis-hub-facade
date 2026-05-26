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
            currentTime: new Date().getTime(),
            
            init() {
                setInterval(() => {
                    this.currentTime = new Date().getTime();
                }, 60000); // Update every minute
            },
            
            getAssetBadge(id) {
                if (!id || !this.lockStates[id]) return '';
                
                let lock = this.lockStates[id];
                
                if (lock.status === 'locked') {
                    return `<span class='inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-400'>Quota Locked</span>`;
                }
                
                if (lock.status === 'pending_release') {
                    let dDate = lock.disabled_at ? new Date(lock.disabled_at).toLocaleDateString() : 'recently';
                    return `<span class='inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-danger-100 text-danger-800 dark:bg-danger-900/30 dark:text-danger-400' title='Disabled at ${dDate}'>Locked until cycle end (${this.cycleBounds.ends_at})</span>`;
                }
                
                if (lock.status === 'staged') {
                    if (!this.projectDeploymentTime) {
                        return `<span class='inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-warning-100 text-warning-800 dark:bg-warning-900/30 dark:text-warning-400'>Grace Period paused (Waiting for deployment)</span>`;
                    }
                    
                    let stagedAt = new Date(lock.staged_at).getTime();
                    let deployedAt = new Date(this.projectDeploymentTime).getTime();
                    let startTime = Math.max(stagedAt, deployedAt);
                    let endsAt = startTime + (2 * 60 * 60 * 1000); // +2 hours
                    
                    let remainingMs = endsAt - this.currentTime;
                    
                    if (remainingMs <= 0) {
                        return `<span class='inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-400'>Quota Locked (Refresh needed)</span>`;
                    }
                    
                    let remainingMins = Math.floor(remainingMs / 60000);
                    let h = Math.floor(remainingMins / 60);
                    let m = remainingMins % 60;
                    let timeStr = h > 0 ? `${h}h ${m}m` : `${m}m`;
                    
                    return `<span class='inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-warning-100 text-warning-800 dark:bg-warning-900/30 dark:text-warning-400'>Grace Period (Ends in ${timeStr})</span>`;
                }
                
                return '';
            },
            
            get formAssets() {
                let formAssets = new Set();
                let data = $wire.get('data') || {};
                
                function scan(obj) {
                    if (typeof obj === 'object' && obj !== null) {
                        if (obj.hasOwnProperty('enabled') && (obj.hasOwnProperty('url') || obj.hasOwnProperty('id') || obj.hasOwnProperty('lost_access'))) {
                            if (obj.enabled && !obj.lost_access) {
                                let id = obj.id || obj.url;
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
                        if (obj.hasOwnProperty('enabled') && (obj.hasOwnProperty('url') || obj.hasOwnProperty('id') || obj.hasOwnProperty('lost_access'))) {
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
            <div class="flex items-center gap-3 text-sm transition-colors"
                 :class="selectedCount > maxAssets ? 'text-danger-600 dark:text-danger-500' : 'text-gray-700 dark:text-gray-300'">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300" x-text="`Cycle: ${cycleBounds.starts_at} - ${cycleBounds.ends_at}`"></span>
                <div class="flex items-center gap-2">
                    <span class="text-xs uppercase font-semibold tracking-wide text-gray-500 dark:text-gray-400">Tier Usage</span>
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
                    <div @click="expanded = !expanded" class="cursor-pointer flex items-center justify-between text-gray-900 dark:text-white font-bold pb-1 border-b border-gray-100 dark:border-white/5 transition hover:text-primary-500">
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
                            <button wire:click="$set('activeChannel', '{{ $channel['key'] }}')"
                                    class="px-3 py-2 text-left rounded-lg text-sm font-medium transition-colors flex items-center justify-between"
                                    :class="activeTab === '{{ $channel['key'] }}' ? 'bg-primary-500/10 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'">
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
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Content Area -->
        <div class="w-full bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6" style="flex: 1 1 0%;">
            
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-6 pb-6 border-b border-gray-200 dark:border-white/10" style="margin-bottom: 2.5rem;">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $this->getChannelLabel($activeChannel) }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Last synced: {{ $this->getLastSyncTime($activeChannel) }}
                    </p>
                </div>
                
                <div class="flex flex-col items-end gap-3">
                    <div class="flex items-center gap-3">
                        {{ $this->getAction('updateCredentials') }}
                        {{ $this->getAction('discoverAssets') }}
                    </div>
                </div>
            </div>

            @if($this->isConnected($activeChannel) && $this->isProfileShared($activeChannel))
                <div class="mb-6 p-4 rounded-lg bg-warning-50 dark:bg-warning-500/10 border border-warning-200 dark:border-warning-500/20">
                    <div class="flex items-start gap-3">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-600 dark:text-warning-500 shrink-0 mt-0.5" />
                        <div>
                            <h3 class="text-sm font-bold text-warning-800 dark:text-warning-400">Shared API Rate Limits</h3>
                            <p class="text-sm text-warning-700 dark:text-warning-500 mt-1">
                                This social profile is connected to multiple projects. To protect your connection stability, 
                                the API rate limits for this profile will be shared across all connected projects. Avoid 
                                syncing excessive assets simultaneously.
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
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Not Connected</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mb-6">
                        You need to authorize access to this provider before you can configure its data sources.
                    </p>
                    
                    @if(str_contains($activeChannel, 'facebook'))
                        <x-oauth-buttons provider="facebook" :type="$activeChannel" />
                    @elseif(str_contains($activeChannel, 'google'))
                        <x-oauth-buttons provider="google" :type="$activeChannel" />
                    @endif
                </div>
            @else
                
                <!-- Removed internal Tier Usage -->

                @if($activeChannel === 'facebook_organic')
                    <div class="mt-6 p-5 rounded-r-xl border-l-4 border-warning-500 bg-warning-50 dark:bg-warning-500/10 shadow-sm" style="margin-bottom: 2.5rem;">
                        <div class="flex items-start gap-4">
                            <x-heroicon-s-exclamation-triangle class="w-6 h-6 text-warning-600 dark:text-warning-400 shrink-0 mt-0.5" />
                            <div>
                                <h3 class="text-base font-bold text-warning-800 dark:text-warning-300 tracking-tight">Historic Metrics Limitation</h3>
                                <p class="text-sm text-warning-700 dark:text-warning-100 mt-1 leading-relaxed">
                                    Facebook does not provide historic metrics for posts and media; it only provides daily snapshots. Therefore, we will build the history for your assets by caching the daily data to provide time series starting from today. <strong class="font-semibold text-warning-900 dark:text-white">To successfully build these time series without gaps, you must keep the channel and the asset enabled continuously.</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <form wire:submit="save">
                    {{ $this->form }}
                    <div class="sticky bottom-0 z-20 -mx-6 -mb-6 mt-6 p-4 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-t border-gray-200 dark:border-white/10 flex justify-end shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] rounded-b-xl">
                        <x-filament::button type="submit" color="primary" size="lg"
                            wire:confirm="Saving this configuration will update your tracked assets and may impact your monthly billing quota. Are you sure you want to proceed?">
                            Save Configuration
                        </x-filament::button>
                    </div>
                </form>
            @endif

        </div>
    </div>
</div>
</x-filament-panels::page>

<x-filament-panels::page>
    <div class="flex flex-col md:flex-row gap-6 items-start" 
         x-data="{ 
            activeTab: @entangle('activeChannel'),
            maxAssets: {{ $this->getMaxAssets() }},
            get selectedCount() {
                let count = 0;
                let data = $wire.get('data') || {};
                
                function scan(obj) {
                    if (typeof obj === 'object' && obj !== null) {
                        // Identify an asset object by its standard properties
                        if (obj.hasOwnProperty('enabled') && (obj.hasOwnProperty('url') || obj.hasOwnProperty('id') || obj.hasOwnProperty('lost_access'))) {
                            if (obj.enabled && !obj.lost_access) {
                                count++;
                            }
                            return; // No need to scan inside the asset
                        }
                        
                        // Otherwise traverse deeper
                        for (let key in obj) {
                            scan(obj[key]);
                        }
                    }
                }
                
                scan(data);
                return count;
            }
         }">
        <!-- Sidebar Tabs -->
        <div class="w-full flex-shrink-0 flex flex-col gap-2 bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-2" style="max-width: 16rem;">
            @foreach($this->getChannels() as $channel)
                <button wire:click="$set('activeChannel', '{{ $channel['key'] }}')"
                        class="px-4 py-3 text-left rounded-lg text-sm font-medium transition-colors flex items-center justify-between"
                        :class="activeTab === '{{ $channel['key'] }}' ? 'bg-primary-500/10 text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'">
                    <span>{{ $channel['label'] }}</span>
                    @if(isset($this->data[$channel['key'].'_enabled']) && $this->data[$channel['key'].'_enabled'])
                        <span class="flex h-2 w-2 rounded-full bg-success-500"></span>
                    @endif
                </button>
            @endforeach
        </div>

        <!-- Content Area -->
        <div class="w-full bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6" style="flex: 1 1 0%;">
            
            <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200 dark:border-white/10">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ collect($this->getChannels())->firstWhere('key', $activeChannel)['label'] ?? 'Configuration' }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Last synced: {{ $this->getLastSyncTime($activeChannel) }}
                    </p>
                </div>
                
                <div class="flex items-center gap-3">
                    {{ $this->getAction('updateCredentials') }}
                    {{ $this->getAction('discoverAssets') }}
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
                        @php $type = str_replace('facebook_', '', $activeChannel); @endphp
                        <x-oauth-buttons provider="facebook" :type="$type" />
                    @elseif(str_contains($activeChannel, 'google'))
                        @php $type = str_replace('google_', '', $activeChannel); @endphp
                        <x-oauth-buttons provider="google" :type="$type" />
                    @endif
                </div>
            @else
                
                <!-- Sticky Counter -->
                <div class="sticky top-0 z-10 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md pb-4 mb-4 border-b border-gray-200 dark:border-white/10 flex justify-between items-center transition-colors"
                     :class="selectedCount > maxAssets ? 'text-danger-600 dark:text-danger-500' : 'text-gray-700 dark:text-gray-300'">
                    <span class="text-sm font-semibold tracking-wide uppercase">Tier Usage</span>
                    <div class="flex items-center gap-2 font-bold text-lg">
                        <span x-text="selectedCount"></span> 
                        <span class="text-gray-400 font-normal">/</span> 
                        <span x-text="maxAssets"></span>
                    </div>
                </div>

                <form wire:submit="save">
                    {{ $this->form }}
                    
                    <div class="mt-6 pt-4 border-t border-gray-200 dark:border-white/10 flex justify-end">
                        <x-filament::button type="submit" color="primary">
                            Save Configuration
                        </x-filament::button>
                    </div>
                </form>
            @endif

        </div>
    </div>
</x-filament-panels::page>

<x-filament-panels::page>
    <div wire:poll.10s="refreshData">
        @if($isLoading)
            <div class="flex items-center justify-center p-12">
                <x-filament::loading-indicator class="h-12 w-12 text-primary-500" />
            </div>
        @elseif(empty($syncData))
            <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                <x-heroicon-o-exclamation-triangle class="h-12 w-12 mx-auto mb-4" />
                <p class="text-lg">Waiting for data from instance node...</p>
            </div>
        @else
            {{-- 🟢 Header Statistics: Database Totals --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                @foreach(($syncData['dbTotals'] ?? []) as $total)
                    @if(in_array($total['entity'], ['Campaigns', 'Ads', 'Posts', 'Queries']))
                        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
                            <div class="flex flex-col gap-y-1">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $total['entity'] }}</span>
                                <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                                    {{ number_format($total['count']) }}
                                </span>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- 🔵 Synchronization Pipelines: Grouped by Channel --}}
            <div class="space-y-12">
                @foreach(($syncData['groupedJobs'] ?? []) as $chan => $jobs)
                    <section class="space-y-4">
                        <div class="flex items-center gap-x-3 mb-6">
                            <div class="h-8 w-1 bg-primary-500 rounded-full"></div>
                            <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white capitalize">
                                {{ strtoupper($chan) }} Synchronization Pipeline
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            @foreach($jobs as $job)
                                @php
                                    $statusColor = match($job['status_text']) {
                                        'COMPLETED' => 'success',
                                        'PROCESSING', 'RUNNING' => 'warning',
                                        'FAILED', 'ERROR' => 'danger',
                                        default => 'gray',
                                    };
                                    $statusIcon = match($job['status_text']) {
                                        'COMPLETED' => 'heroicon-m-check-circle',
                                        'PROCESSING', 'RUNNING' => 'heroicon-m-arrow-path',
                                        'FAILED', 'ERROR' => 'heroicon-m-x-circle',
                                        default => 'heroicon-m-clock',
                                    };
                                @endphp

                                <div class="relative rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition duration-300 hover:shadow-md dark:border-white/10 dark:bg-gray-900">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="font-bold text-gray-900 dark:text-white uppercase text-sm tracking-widest">
                                            {{ str_replace('-', ' ', $job['entity']) }}
                                        </h3>
                                        <div class="flex items-center gap-x-2">
                                            @php
                                                $totalCCount = $job['container_stats']['total'] ?? 0;
                                                $compCCount = ($job['container_stats']['completed'] ?? 0) + ($job['container_stats']['COMPLETED'] ?? 0);
                                            @endphp
                                            @if($totalCCount > 0)
                                                @if($totalCCount === $compCCount)
                                                    <x-filament::badge color="success" icon="heroicon-m-check-badge" size="xs">
                                                        CACHED
                                                    </x-filament::badge>
                                                @else
                                                    <span class="text-[10px] font-bold text-gray-400">
                                                        {{ $compCCount }}/{{ $totalCCount }}
                                                    </span>
                                                @endif
                                            @endif
                                            <x-filament::badge :color="$statusColor" :icon="$statusIcon">
                                                {{ $job['status_text'] }}
                                            </x-filament::badge>
                                        </div>

                                    </div>

                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                            <span>Frequency:</span>
                                            <span class="font-medium text-gray-900 dark:text-white italic">{{ $job['frequency'] }}</span>
                                        </div>
                                        <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                            <span>Execution Time:</span>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $job['execution_time'] }}</span>
                                        </div>
                                        <div class="flex justify-between text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-50 dark:border-white/5">
                                            <span>Last Update:</span>
                                            <span class="font-medium text-gray-900 dark:text-white text-xs">{{ $job['updated_at'] }}</span>
                                        </div>
                                    </div>

                                    @if($job['message'])
                                        <div class="mt-4 p-2 rounded bg-gray-50 dark:bg-white/5 text-[10px] text-gray-500 font-mono overflow-hidden truncate">
                                            {{ $job['message'] }}
                                        </div>
                                    @endif

                                    {{-- 📜 Execution History Timeline --}}
                                    @if(!empty($job['history']))
                                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-white/5">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-[10px] uppercase font-bold text-gray-400">Recent History</span>
                                            </div>
                                            <div class="flex gap-x-1.5 overflow-x-auto pb-1">
                                                @foreach($job['history'] as $hist)
                                                    @php
                                                        // JobStatus::completed->value = 3, failed->value = 4
                                                        $histColor = match($hist['status']) {
                                                            3 => 'success',
                                                            4 => 'danger',
                                                            default => 'gray',
                                                        };
                                                        $histTooltip = ($hist['status'] == 3 ? 'Completed' : 'Failed') . " at " . $hist['date'];
                                                    @endphp
                                                    <div 
                                                        class="h-2.5 w-2.5 rounded-full bg-{{ $histColor }}-500 cursor-help flex-shrink-0" 
                                                        title="{{ $histTooltip }}"
                                                    ></div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- 🛰️ Infrastructure Controls (Phase 5) --}}

                                    @if(isset($job['instance_name']) && preg_match('/-[0-9]{4}-[0-9]{2}$/', $job['instance_name']))
                                        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-white/5 flex items-center justify-between gap-x-2">
                                            <span class="text-[10px] uppercase font-bold text-gray-400">Scale Control</span>
                                            <div class="flex gap-x-2">
                                                <x-filament::button 
                                                    wire:click="toggleContainer('{{ $job['instance_name'] }}', 'start')" 
                                                    size="xs" 
                                                    color="success" 
                                                    icon="heroicon-m-play"
                                                    outlined
                                                >
                                                    Start
                                                </x-filament::button>
                                                <x-filament::button 
                                                    wire:click="toggleContainer('{{ $job['instance_name'] }}', 'stop')" 
                                                    size="xs" 
                                                    color="danger" 
                                                    icon="heroicon-m-stop"
                                                    outlined
                                                >
                                                    Stop
                                                </x-filament::button>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>

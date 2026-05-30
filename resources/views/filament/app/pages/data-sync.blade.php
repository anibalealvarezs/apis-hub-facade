<x-filament-panels::page>
    <div wire:poll.10s="refreshData">
        @if(!filament()->getTenant()->is_active || filament()->getTenant()->billing_status === 'suspended')
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-800/30" role="alert">
              <span class="font-bold">Proyecto Suspendido:</span> Este proyecto está actualmente inactivo debido a incidencias de facturación. Se permite el acceso de solo lectura para ver la configuración, pero las opciones de edición, despliegue, sincronización y transferencia de propiedad están bloqueadas.
            </div>
        @endif

        @if($isLoading)
            <div class="flex items-center justify-center p-12">
                <x-filament::loading-indicator class="h-12 w-12 text-primary-500" />
            </div>
        @elseif(empty($syncData) || !isset($syncData['completion_percentage']))
            <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                <x-heroicon-o-exclamation-triangle class="h-12 w-12 mx-auto mb-4" />
                <p class="text-lg">Establishing connection to Sync Engine or data is unavailable...</p>
            </div>
        @else
            @php
                $globalCompletion = number_format((float)$syncData['completion_percentage'], 2);
                $totalFailed = 0;
                $totalProcessing = 0;
                $totalScheduled = 0;
                foreach($syncData['channels'] ?? [] as $ch) {
                    $totalFailed += $ch['failed'] ?? 0;
                    $totalProcessing += $ch['processing'] ?? 0;
                    $totalScheduled += $ch['scheduled'] ?? 0;
                }

                if ($totalProcessing > 0) {
                    $statusLabel = 'Workers Corriendo';
                    $statusColor = 'bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30';
                    $statusDescription = "Se están procesando $totalProcessing trabajos activamente.";
                } elseif ($totalScheduled > 0) {
                    $statusLabel = 'Workers en Espera / Pausados';
                    $statusColor = 'bg-warning-50 text-warning-600 ring-warning-600/10 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30';
                    $statusDescription = "No hay procesamiento activo, pero hay $totalScheduled trabajos en cola.";
                } else {
                    $statusLabel = 'Workers Inactivos';
                    $statusColor = 'bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30';
                    $statusDescription = 'No hay trabajos programados ni en procesamiento. Los workers están inactivos.';
                }
            @endphp

            {{-- 🟢 Layer 0: Worker Status --}}
            <div class="mb-4 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-white/10 p-4 md:p-6">
                <div>
                    <h2 class="text-sm uppercase tracking-wider font-bold text-gray-500 dark:text-gray-400 mb-2">Remote Workers Status</h2>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <span class="inline-flex items-center w-max rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusColor }}">
                            {{ $statusLabel }}
                        </span>
                        <span class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $statusDescription }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- 🟢 Layer 1: Global Health Overview --}}
            <div class="mb-8 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-white/10 p-6 md:p-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="w-full md:w-1/2">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Overall Sync Progress</h2>
                        <div class="flex items-center gap-4">
                            <div class="flex-grow bg-gray-200 dark:bg-gray-800 rounded-full h-4 overflow-hidden">
                                <div class="bg-primary-600 h-4 rounded-full transition-all duration-500 ease-out" style="width: {{ $globalCompletion }}%"></div>
                            </div>
                            <span class="text-3xl font-black text-gray-900 dark:text-white">{{ $globalCompletion }}%</span>
                        </div>
                    </div>

                    <div class="w-full md:w-auto flex flex-wrap gap-6">
                        <div class="flex flex-col">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Assets</span>
                            <span class="text-3xl font-semibold text-gray-900 dark:text-white">{{ $syncData['total_assets'] ?? 0 }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Fully Synced</span>
                            <div class="flex items-end gap-2">
                                <span class="text-3xl font-semibold text-success-600 dark:text-success-400">{{ $syncData['fully_synced_count'] ?? 0 }}</span>
                                <span class="text-sm text-gray-400 mb-1">({{ number_format($syncData['fully_synced_percentage'] ?? 0, 1) }}%)</span>
                            </div>
                        </div>
                        @if($totalFailed > 0)
                        <div class="flex flex-col px-4 py-2 bg-danger-50 dark:bg-danger-500/10 rounded-xl border border-danger-200 dark:border-danger-500/20">
                            <span class="text-sm font-medium text-danger-600 dark:text-danger-400 flex items-center gap-1">
                                <x-heroicon-m-exclamation-triangle class="w-4 h-4"/> Failed Jobs
                            </span>
                            <span class="text-3xl font-semibold text-danger-700 dark:text-danger-500">{{ $totalFailed }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 🔵 Layer 2 & 3: Channel Breakdown & Asset Drill-down --}}
            <div class="space-y-6">
                @foreach(($syncData['channels'] ?? []) as $channelKey => $channelData)
                    @php
                        $chComp = number_format((float)($channelData['completion_percentage'] ?? 0), 2);
                        $chFailed = $channelData['failed'] ?? 0;
                        $hasAssets = !empty($channelData['assets']);
                    @endphp
                    <div x-data="{ expanded: false }" class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-white/10 overflow-hidden transition-all duration-200">

                        {{-- Channel Header Card --}}
                        <div class="p-6 cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 transition-colors" @click="expanded = !expanded">
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">

                                <div class="flex items-center gap-4 lg:w-1/3">
                                    <div class="p-3 rounded-xl {{ $chFailed > 0 ? 'bg-danger-100 text-danger-600 dark:bg-danger-500/20 dark:text-danger-400' : 'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400' }}">
                                        @if($chFailed > 0)
                                            <x-heroicon-o-exclamation-circle class="w-8 h-8" />
                                        @else
                                            <x-heroicon-o-server-stack class="w-8 h-8" />
                                        @endif
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white capitalize">
                                            {{ str_replace('_', ' ', $channelData['channel'] ?? $channelKey) }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $channelData['total_assets'] ?? 0 }} Assets Tracker
                                        </p>
                                    </div>
                                </div>

                                <div class="flex-grow lg:w-1/3">
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Completion</span>
                                        <span class="font-bold text-gray-900 dark:text-white">{{ $chComp }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2.5">
                                        <div class="h-2.5 rounded-full {{ $chComp == 100 ? 'bg-success-500' : 'bg-primary-500' }}" style="width: {{ $chComp }}%"></div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 lg:w-1/3 lg:justify-end flex-wrap">
                                    <div class="flex gap-2">
                                        <x-filament::badge color="success" class="flex-col !px-2 !py-1">
                                            <span class="text-[10px] uppercase opacity-70">Completed</span>
                                            <span class="font-bold text-sm">{{ $channelData['completed'] ?? 0 }}</span>
                                        </x-filament::badge>

                                        @if(($channelData['processing'] ?? 0) > 0)
                                        <x-filament::badge color="warning" class="flex-col !px-2 !py-1">
                                            <span class="text-[10px] uppercase opacity-70">Processing</span>
                                            <span class="font-bold text-sm">{{ $channelData['processing'] }}</span>
                                        </x-filament::badge>
                                        @endif

                                        <x-filament::badge color="gray" class="flex-col !px-2 !py-1" tooltip="Jobs waiting for quota or time limits">
                                            <span class="text-[10px] uppercase opacity-70">Scheduled</span>
                                            <span class="font-bold text-sm">{{ $channelData['scheduled'] ?? 0 }}</span>
                                        </x-filament::badge>

                                        @if($chFailed > 0)
                                        <x-filament::badge color="danger" class="flex-col !px-2 !py-1">
                                            <span class="text-[10px] uppercase opacity-70">Failed</span>
                                            <span class="font-bold text-sm">{{ $chFailed }}</span>
                                        </x-filament::badge>
                                        @endif
                                    </div>

                                    <div class="ml-2 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': expanded }">
                                        <x-heroicon-m-chevron-down class="w-6 h-6" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Asset Details (Expanded) --}}
                        <div x-show="expanded" x-collapse x-cloak>
                            <div class="border-t border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/[0.02] p-6">
                                @if($hasAssets)
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm text-left">
                                            <thead class="text-xs text-gray-500 uppercase bg-gray-100/50 dark:bg-gray-800/50 rounded-t-lg">
                                                <tr>
                                                    <th class="px-4 py-3 font-medium rounded-tl-lg">Asset / Identifier</th>
                                                    <th class="px-4 py-3 font-medium">Progress</th>
                                                    <th class="px-4 py-3 font-medium text-center">Completed</th>
                                                    <th class="px-4 py-3 font-medium text-center">Processing</th>
                                                    <th class="px-4 py-3 font-medium text-center">Scheduled</th>
                                                    <th class="px-4 py-3 font-medium text-center rounded-tr-lg">Failed</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                                {{-- Sort to ensure 'global' is at top, then by failures, then alphabetically --}}
                                                @php
                                                    $assetsList = $channelData['assets'];
                                                    uksort($assetsList, function($a, $b) use ($assetsList) {
                                                        if ($a === 'global') return -1;
                                                        if ($b === 'global') return 1;

                                                        $aFail = $assetsList[$a]['failed'] ?? 0;
                                                        $bFail = $assetsList[$b]['failed'] ?? 0;
                                                        if ($aFail !== $bFail) return $bFail <=> $aFail;

                                                        return strcmp($a, $b);
                                                    });
                                                @endphp

                                                @foreach($assetsList as $assetId => $assetStats)
                                                    @php
                                                        $isGlobal = $assetId === 'global';
                                                        $aTotal = $assetStats['total_for_percentage'] ?? 1; // avoid division by zero
                                                        $aComp = $assetStats['completed'] ?? 0;
                                                        $aFail = $assetStats['failed'] ?? 0;
                                                        $aPct = $aTotal > 0 ? min(100, round(($aComp / $aTotal) * 100)) : 100;

                                                        $rowClass = $aFail > 0 ? 'bg-danger-50/50 dark:bg-danger-500/5 hover:bg-danger-50 dark:hover:bg-danger-500/10' : 'hover:bg-gray-100/50 dark:hover:bg-white/5';
                                                    @endphp
                                                    <tr class="{{ $rowClass }} transition-colors">
                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center gap-2">
                                                                @if($isGlobal)
                                                                    <x-heroicon-o-globe-alt class="w-5 h-5 text-gray-400" />
                                                                    <span class="font-bold text-gray-700 dark:text-gray-300">Channel-Wide Tasks</span>
                                                                @else
                                                                    @if($aFail > 0)
                                                                        <x-heroicon-m-exclamation-circle class="w-5 h-5 text-danger-500" />
                                                                    @else
                                                                        <x-heroicon-o-document-text class="w-5 h-5 text-gray-400" />
                                                                    @endif
                                                                    <div class="flex flex-col">
                                                                        @if(($channelData['channel'] ?? '') === 'facebook_organic')
                                                                            <a href="https://facebook.com/{{ $assetId }}" target="_blank" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 truncate max-w-xs block transition-colors" title="{{ $assetStats['name'] ?? $assetId }}">
                                                                                {{ Str::limit($assetStats['name'] ?? str_replace(['sc-domain:', 'https://', 'http://'], '', $assetId), 40) }}
                                                                                <x-heroicon-m-arrow-top-right-on-square class="inline w-3 h-3 ml-1 mb-0.5 opacity-70"/>
                                                                            </a>
                                                                            @if(!empty($assetStats['name']))
                                                                                <span class="text-xs text-gray-400 dark:text-gray-500 truncate max-w-xs block mt-0.5" title="{{ $assetId }}">
                                                                                    ID: {{ Str::limit(str_replace(['sc-domain:', 'https://', 'http://'], '', $assetId), 40) }}
                                                                                </span>
                                                                            @endif
                                                                            @if(!empty($assetStats['ig_username']))
                                                                                <a href="https://instagram.com/{{ $assetStats['ig_username'] }}" target="_blank" class="text-xs font-medium text-pink-600 hover:text-pink-500 dark:text-pink-400 dark:hover:text-pink-300 truncate max-w-xs block mt-1 transition-colors" title="Instagram: {{ $assetStats['ig_username'] }}">
                                                                                    @ {{ $assetStats['ig_username'] }}
                                                                                    <x-heroicon-m-arrow-top-right-on-square class="inline w-3 h-3 ml-1 mb-0.5 opacity-70"/>
                                                                                </a>
                                                                            @endif
                                                                        @else
                                                                            <span class="font-medium text-gray-900 dark:text-white truncate max-w-xs block" title="{{ $assetStats['name'] ?? $assetId }}">
                                                                                {{ Str::limit($assetStats['name'] ?? str_replace(['sc-domain:', 'https://', 'http://'], '', $assetId), 40) }}
                                                                            </span>
                                                                            @if(!empty($assetStats['name']))
                                                                                <span class="text-xs text-gray-400 dark:text-gray-500 truncate max-w-xs block mt-0.5" title="{{ $assetId }}">
                                                                                    ID: {{ Str::limit(str_replace(['sc-domain:', 'https://', 'http://'], '', $assetId), 40) }}
                                                                                </span>
                                                                            @endif
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            @if($aFail > 0)
                                                                <p class="text-xs mt-1 text-danger-600 dark:text-danger-400 ml-7">
                                                                    Issues detected. Check credentials or rate limits.
                                                                </p>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3 w-48">
                                                            <div class="flex items-center gap-2">
                                                                <div class="flex-grow bg-gray-200 dark:bg-gray-800 rounded-full h-1.5">
                                                                    <div class="h-1.5 rounded-full {{ $aPct == 100 ? 'bg-success-500' : 'bg-primary-500' }}" style="width: {{ $aPct }}%"></div>
                                                                </div>
                                                                <span class="text-xs font-medium text-gray-500">{{ $aPct }}%</span>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-success-50 dark:bg-success-500/10 text-success-600 dark:text-success-400 font-medium text-xs">
                                                                {{ $aComp }}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            @if(($assetStats['processing'] ?? 0) > 0)
                                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-warning-50 dark:bg-warning-500/10 text-warning-600 dark:text-warning-400 font-medium text-xs">
                                                                    {{ $assetStats['processing'] }}
                                                                </span>
                                                            @else
                                                                <span class="text-gray-300 dark:text-gray-600">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            @if(($assetStats['scheduled'] ?? 0) > 0)
                                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-gray-300 font-medium text-xs">
                                                                    {{ $assetStats['scheduled'] }}
                                                                </span>
                                                            @else
                                                                <span class="text-gray-300 dark:text-gray-600">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            @if($aFail > 0)
                                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-danger-100 dark:bg-danger-500/20 text-danger-700 dark:text-danger-400 font-bold text-xs">
                                                                    {{ $aFail }}
                                                                </span>
                                                            @else
                                                                <span class="text-gray-300 dark:text-gray-600">-</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                        <p>No assets configured or syncing yet.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>

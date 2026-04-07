<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            {{-- Project Identity --}}
            <div class="flex-1 space-y-1">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-500/10 dark:bg-primary-400/10">
                        <x-heroicon-m-cube class="h-5 w-5 text-primary-500 dark:text-primary-400" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-950 dark:text-white">
                            {{ $this->getProject()->name }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-mono text-xs bg-gray-100 dark:bg-white/5 px-2 py-0.5 rounded">
                                {{ $this->getProject()->subdomain }}.apis-hub.cloud
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Status & Server --}}
            <div class="flex flex-col items-start gap-3 sm:items-end">
                {{-- Status Badge --}}
                @php
                    $statusColor = $this->getStatusColor();
                    $statusLabel = $this->getStatusLabel();
                    $colorClasses = match($statusColor) {
                        'success' => 'bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/20',
                        'warning' => 'bg-warning-50 text-warning-600 ring-warning-600/10 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/20',
                        'danger' => 'bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/20',
                        default => 'bg-gray-50 text-gray-600 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20',
                    };
                @endphp
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $colorClasses }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ match($statusColor) {
                        'success' => 'bg-success-500 dark:bg-success-400',
                        'warning' => 'bg-warning-500 dark:bg-warning-400',
                        'danger' => 'bg-danger-500 dark:bg-danger-400',
                        default => 'bg-gray-500 dark:bg-gray-400',
                    } }}"></span>
                    {{ $statusLabel }}
                </span>

                {{-- Server Info --}}
                <div class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                    <x-heroicon-m-server class="h-4 w-4" />
                    <span>{{ $this->getServerName() }}</span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

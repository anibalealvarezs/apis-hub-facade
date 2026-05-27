<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Este proyecto está enlazado a un único perfil de facturación que financia el coste de su infraestructura y sus cuotas de sincronización.
                </p>
            </div>
        </div>

        @if($billingProfile)
            <!-- Premium Native-Tailwind Ficha del Perfil de Facturación -->
            <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 relative overflow-hidden transition duration-300 hover:shadow-md">
                <!-- Background ambient glow -->
                <div class="absolute -right-16 -top-16 h-32 w-32 rounded-full bg-primary-500/10 blur-3xl dark:bg-primary-500/5"></div>

                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 relative z-10">
                    <div class="space-y-4 flex-1">
                        <!-- Profile Header -->
                        <div class="flex items-start gap-4">
                            <div class="mt-1 flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-950 dark:text-white leading-tight">
                                    {{ $billingProfile->display_name }}
                                </h3>
                                @if($billingProfile->reference_name)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Razón Social / Legal: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $billingProfile->name }}</span>
                                    </p>
                                @endif
                            </div>
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 pt-2">
                            <!-- Tipo -->
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Tipo de Perfil</span>
                                <div class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10">
                                        {{ ucfirst($billingProfile->type ?? 'Individual') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Plan / Subscription -->
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Plan del Perfil</span>
                                <div class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/10 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20">
                                        {{ strtoupper($billingProfile->tier->value ?? $billingProfile->tier) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Estado del Proyecto -->
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Estado de Pago del Proyecto</span>
                                <div class="flex items-center gap-1.5">
                                    @php
                                        $statusColors = [
                                            'active' => 'bg-emerald-500/10 text-emerald-500 dark:bg-emerald-500/20 dark:text-emerald-400 ring-emerald-500/30',
                                            'pending_approval' => 'bg-amber-500/10 text-amber-500 dark:bg-amber-500/20 dark:text-amber-400 ring-amber-500/30',
                                            'suspended' => 'bg-rose-500/10 text-rose-500 dark:bg-rose-500/20 dark:text-rose-400 ring-rose-500/30',
                                        ];
                                        $statusLabels = [
                                            'active' => 'Activo',
                                            'pending_approval' => 'Pendiente de Aprobación',
                                            'suspended' => 'Suspendido',
                                        ];
                                        $projectStatus = $project->billing_status ?? 'active';
                                        $styleClass = $statusColors[$projectStatus] ?? 'bg-gray-500/10 text-gray-500';
                                        $label = $statusLabels[$projectStatus] ?? ucfirst($projectStatus);
                                    @endphp
                                    <span class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $styleClass }}">
                                        @if($projectStatus === 'active')
                                            <span class="relative flex h-1.5 w-1.5 mr-0.5">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                            </span>
                                        @endif
                                        {{ $label }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Cycle Duration -->
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-8 pt-4 border-t border-gray-100 dark:border-gray-800 text-sm text-gray-600 dark:text-gray-300">
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                                <span>Inicio de Ciclo: <strong class="font-semibold text-gray-950 dark:text-white">{{ $cycleStarts?->format('d M, Y') ?? 'N/A' }}</strong></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Próxima Renovación: <strong class="font-semibold text-gray-950 dark:text-white">{{ $cycleEnds?->format('d M, Y') ?? 'N/A' }}</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Trigger Button -->
                    <div class="flex items-center justify-center shrink-0">
                        {{ $this->getAction('assign_profile') }}
                    </div>
                </div>
            </div>
        @else
            <!-- Empty state when no profile is linked -->
            <div class="text-center py-12 rounded-xl border border-dashed border-gray-300 dark:border-white/10 p-6">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 mb-4">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-950 dark:text-white">Sin Perfil de Facturación</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                    Este proyecto no tiene asignado ningún perfil de facturación activo. Por favor, asigna uno para evitar interrupciones de servicio.
                </p>
                <div class="mt-6 flex justify-center">
                    {{ $this->getAction('assign_profile') }}
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

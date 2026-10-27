@props([
    'currentStep' => 1,
    'currentLang' => 'es',
])

@php
    $steps = [
        1 => [
            'num' => '1',
            'label' => $currentLang === 'es' ? 'Fuentes de Datos' : 'Data Sources',
            'desc' => $currentLang === 'es' ? 'Activos y Cuotas' : 'Assets & Quotas',
            'route' => $currentLang === 'es' ? route('demo.data-sources.es') : route('demo.data-sources', ['lang' => 'en']),
        ],
        2 => [
            'num' => '2',
            'label' => $currentLang === 'es' ? 'Grupos de Activos' : 'Asset Groups',
            'desc' => $currentLang === 'es' ? 'Organización Cross-Channel' : 'Cross-Channel Setup',
            'route' => $currentLang === 'es' ? route('demo.asset-groups.es') : route('demo.asset-groups', ['lang' => 'en']),
        ],
        3 => [
            'num' => '3',
            'label' => $currentLang === 'es' ? 'Dashboard Omnicanal' : 'Omnichannel Dashboard',
            'desc' => $currentLang === 'es' ? 'Analítica y Visualización' : 'Analytics & Insights',
            'route' => $currentLang === 'es' ? route('demo.show.es') : route('demo.show', ['lang' => 'en']),
        ],
        4 => [
            'num' => '4',
            'label' => $currentLang === 'es' ? 'Colaboración & Equipos' : 'Team Collaboration',
            'desc' => $currentLang === 'es' ? 'Proyectos y Roles' : 'Projects & Roles',
            'route' => $currentLang === 'es' ? route('demo.collaboration.es') : route('demo.collaboration', ['lang' => 'en']),
        ],
        5 => [
            'num' => '5',
            'label' => $currentLang === 'es' ? 'Data Intelligence' : 'Data Intelligence',
            'desc' => $currentLang === 'es' ? 'Alertas, API & MCP' : 'Alerts, API & MCP',
            'route' => $currentLang === 'es' ? route('demo.intelligence.es') : route('demo.intelligence', ['lang' => 'en']),
        ],
    ];
@endphp

<div class="w-full bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-4 py-3 shadow-xs">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
        {{-- Left: Stepper Navigation --}}
        <nav aria-label="Demo Progress" class="flex-1">
            <ol class="flex items-center gap-2 sm:gap-4 overflow-x-auto py-1">
                @foreach($steps as $sNum => $step)
                    @php
                        $isActive = $currentStep === $sNum;
                        $isCompleted = $currentStep > $sNum;
                    @endphp
                    <li class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                        <a href="{{ $step['route'] }}"
                           class="group flex items-center gap-2.5 px-3 py-1.5 rounded-xl transition-all duration-150 {{ $isActive ? 'bg-primary-50 dark:bg-primary-950/50 ring-1 ring-primary-500/30' : 'hover:bg-gray-100 dark:hover:bg-gray-800/60' }}">
                            {{-- Step Number Circle --}}
                            <span class="flex items-center justify-center w-6 h-6 rounded-lg text-xs font-bold transition-all {{ $isActive ? 'bg-primary-600 text-white shadow-xs' : ($isCompleted ? 'bg-emerald-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-200') }}">
                                @if($isCompleted)
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    {{ $step['num'] }}
                                @endif
                            </span>

                            <div class="flex flex-col text-left">
                                <span class="text-xs font-bold tracking-tight leading-tight {{ $isActive ? 'text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white' }}">
                                    {{ $step['label'] }}
                                </span>
                                <span class="text-[10px] text-gray-400 dark:text-gray-500 leading-none mt-0.5 hidden sm:inline">
                                    {{ $step['desc'] }}
                                </span>
                            </div>
                        </a>

                        @if(!$loop->last)
                            <svg class="w-4 h-4 text-gray-300 dark:text-gray-700 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>

        {{-- Right: Quick Navigation Arrows --}}
        <div class="flex items-center gap-2 self-end md:self-auto flex-shrink-0 text-xs">
            @if($currentStep > 1)
                <a href="{{ $steps[$currentStep - 1]['route'] }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span>{{ $currentLang === 'es' ? 'Anterior' : 'Previous' }}</span>
                </a>
            @endif

            @if($currentStep < 5)
                <a href="{{ $steps[$currentStep + 1]['route'] }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-semibold bg-primary-600 hover:bg-primary-500 text-white shadow-xs transition-colors">
                    <span>{{ $currentLang === 'es' ? 'Siguiente Paso' : 'Next Step' }}</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <a href="{{ $currentLang === 'es' ? route('landing.plans.es') : route('landing.plans') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-semibold bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs transition-colors">
                    <span>{{ $currentLang === 'es' ? 'Comenzar Prueba Gratis' : 'Start Free Trial' }}</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            @endif
        </div>
    </div>
</div>

<x-layouts.public 
    :title="$currentLang === 'es' ? 'Paso 5: Data Intelligence | Demostración Interactiva — APIs Hub' : 'Step 5: Data Intelligence | Interactive Demo — APIs Hub'"
    :description="$currentLang === 'es' ? 'Demostración pública interactiva de APIs Hub: Alertas predictivas de KPIs, API REST analítica y Servidor MCP para agentes de IA.' : 'APIs Hub interactive public demo: Proactive KPI alerts, high-speed analytical REST API, and Model Context Protocol (MCP) server for AI agents.'"
    :keywords="$currentLang === 'es' ? 'data intelligence, MCP server, servidor MCP, IA analitica, alertas de KPIs, APIs Hub demo' : 'data intelligence, MCP server, AI analytics, KPI alerts, APIs Hub demo'"
    :canonical-url="$currentLang === 'es' ? route('demo.intelligence.es') : route('demo.intelligence', ['lang' => 'en'])"
    :alternate-en="route('demo.intelligence', ['lang' => 'en'])"
    :alternate-es="route('demo.intelligence.es')"
    :alternate-default="route('demo.intelligence')"
    :meta-image="asset('images/branding/apishub-620.png')"
    :og-type="'website'"
    :body-class="'font-sans'"
    :x-data="'demoIntelligence({
        theme: localStorage.getItem(\'color-theme\') || (window.matchMedia(\'(prefers-color-scheme: dark)\').matches ? \'dark\' : \'light\'),
        currentLang: \'' . $currentLang . '\',
        activeTab: \'alerts\'
    })'"
    :portals="$portals"
>
    <x-slot:extra-head>
        <link rel="stylesheet" href="{{ asset('css/demo-theme.css') }}"/>
        <script src="{{ asset('js/demo-controllers.js') }}"></script>
    </x-slot:extra-head>

    <x-slot:stepper>
        <div class="pt-20 md:pt-24">
            <x-demo.stepper :current-step="5" :current-lang="$currentLang" />
        </div>
    </x-slot:stepper>

    <main class="flex-1 w-full max-w-7xl mx-auto p-4 sm:p-6 space-y-6">

        {{-- Page Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-xs">
            <div>
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                            {{ $currentLang === 'es' ? 'Data Intelligence & Capacidades de Caché' : 'Data Intelligence & Cached Node Features' }}
                        </h1>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5 max-w-3xl">
                            {{ $currentLang === 'es'
                                ? 'Gracias a la disponibilidad de datos previamente cacheados y normalizados, desbloqueas monitoreo en tiempo real, consultas API ultrarrápidas y acceso MCP para agentes de IA.'
                                : 'With continuous multi-channel cached and normalized data, unlock automated KPI alerts, sub-10ms aggregated APIs, and dedicated MCP servers for AI agents.' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Feature Tab Selector --}}
            <div class="flex items-center gap-1.5 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl self-start md:self-auto">
                <button type="button"
                        @click="activeTab = 'alerts'"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5"
                        :class="activeTab === 'alerts' ? 'bg-white dark:bg-gray-900 text-primary-600 dark:text-primary-400 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span>{{ $currentLang === 'es' ? '1. Alertas & Monitoreo' : '1. KPI Alerts' }}</span>
                </button>
                <button type="button"
                        @click="activeTab = 'api'"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5"
                        :class="activeTab === 'api' ? 'bg-white dark:bg-gray-900 text-primary-600 dark:text-primary-400 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>{{ $currentLang === 'es' ? '2. API & Agregaciones' : '2. Live APIs' }}</span>
                </button>
                <button type="button"
                        @click="activeTab = 'mcp'"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5"
                        :class="activeTab === 'mcp' ? 'bg-white dark:bg-gray-900 text-primary-600 dark:text-primary-400 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>{{ $currentLang === 'es' ? '3. Servidor MCP (IA)' : '3. MCP Server (AI)' }}</span>
                </button>
            </div>
        </div>

        {{-- FEATURE 1: ALERTS & KPIS --}}
        <div x-show="activeTab === 'alerts'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center font-bold text-lg">
                        3
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $currentLang === 'es' ? 'Alertas Configuradas' : 'Configured Alerts' }}</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $currentLang === 'es' ? 'Evaluación continua cada 5 min' : '5-min Continuous Cron' }}</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 flex items-center justify-center font-bold text-lg">
                        1
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $currentLang === 'es' ? 'Anomalía Detectada' : 'Active Anomaly' }}</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $currentLang === 'es' ? 'Notificación enviada a Slack' : 'Dispatched to Slack' }}</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 flex items-center justify-center font-bold text-lg">
                        0
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $currentLang === 'es' ? 'Impacto en Cuota de APIs' : 'External Quota Impact' }}</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $currentLang === 'es' ? '100% sobre base local' : 'Evaluated against cached state' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                        {{ $currentLang === 'es' ? 'Reglas de Alerta sobre Métricas y KPIs Derivados' : 'Configured Alert Rules on Derived & Cross-Channel KPIs' }}
                    </h3>
                    <span class="text-xs text-gray-400 font-mono">cron: * / 5 * * * *</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400 uppercase font-semibold">
                            <tr>
                                <th class="px-4 py-3">{{ $currentLang === 'es' ? 'Alerta' : 'Alert Name' }}</th>
                                <th class="px-4 py-3">{{ $currentLang === 'es' ? 'Condición de Disparo' : 'Trigger Condition' }}</th>
                                <th class="px-4 py-3">{{ $currentLang === 'es' ? 'Valor Actual' : 'Current Value' }}</th>
                                <th class="px-4 py-3 text-center">{{ $currentLang === 'es' ? 'Estado' : 'Status' }}</th>
                                <th class="px-4 py-3">{{ $currentLang === 'es' ? 'Canales de Notificación' : 'Notification Channels' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach($alerts as $alert)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                                    <td class="px-4 py-3.5 font-bold text-gray-900 dark:text-white">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full {{ $alert['status'] === 'triggered' ? 'bg-amber-500 animate-ping' : 'bg-emerald-500' }}"></span>
                                            <span>{{ $alert['name'] }}</span>
                                        </div>
                                        <p class="text-[10px] text-gray-400 font-normal mt-0.5">{{ $alert['channel'] }} · {{ $alert['last_evaluated'] }}</p>
                                    </td>
                                    <td class="px-4 py-3.5 font-mono text-gray-700 dark:text-gray-300">{{ $alert['condition'] }}</td>
                                    <td class="px-4 py-3.5 font-bold text-gray-900 dark:text-white">{{ $alert['current_value'] }}</td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $alert['status'] === 'triggered' ? 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-400 border border-amber-300' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-300' }}">
                                            {{ $alert['status_label'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($alert['notification_channels'] as $nc)
                                                <span class="px-2 py-0.5 rounded text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 font-medium">
                                                    {{ $nc }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- FEATURE 2: API AVAILABILITY & LIVE AGGREGATIONS --}}
        <div x-show="activeTab === 'api'" class="space-y-4">
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-800">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>{{ $currentLang === 'es' ? 'Acceso Directo por API REST (Sub-10ms)' : 'Direct REST API Endpoints (Sub-10ms)' }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-700 font-semibold">Zero External Rate Limit</span>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $currentLang === 'es'
                                ? 'Cualquier frontend, app móvil o script interno puede consultar métricas calculadas instantáneamente sin agotar las cuotas de Google o Meta.'
                                : 'Any frontend, mobile app, or internal script can consume pre-aggregated live metrics without consuming third-party API quotas.' }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 space-y-4">
                    @foreach($apiEndpoints as $ep)
                        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/40 p-4 space-y-3">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $ep['method'] === 'POST' ? 'bg-blue-600' : 'bg-emerald-600' }} text-white font-mono">{{ $ep['method'] }}</span>
                                    <span class="text-xs font-mono font-bold text-gray-900 dark:text-white">https://aura-living.apis-hub.cloud{{ $ep['endpoint'] }}</span>
                                </div>
                                <span class="text-[11px] font-semibold text-primary-600 dark:text-primary-400">{{ $ep['label'] }}</span>
                            </div>

                            <div class="grid grid-cols-1 {{ !empty($ep['payload']) ? 'lg:grid-cols-2' : '' }} gap-3">
                                @if(!empty($ep['payload']))
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between text-[10px] text-gray-400 uppercase font-semibold">
                                            <span>{{ $currentLang === 'es' ? 'Carga Útil (Request Body)' : 'Request Payload' }}</span>
                                            <span class="font-mono">X-API-KEY: ah_live_...</span>
                                        </div>
                                        <div class="bg-gray-900 text-sky-300 p-3 rounded-lg font-mono text-[11px] overflow-x-auto shadow-inner leading-relaxed">
                                            <pre>{{ $ep['payload'] }}</pre>
                                        </div>
                                    </div>
                                @endif

                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-[10px] text-gray-400 uppercase font-semibold">
                                        <span>{{ $currentLang === 'es' ? 'Respuesta Normalizada (200 OK)' : 'Normalized Response (200 OK)' }}</span>
                                        <span class="text-emerald-400 font-mono">meta.cached: true</span>
                                    </div>
                                    <div class="bg-gray-900 text-emerald-400 p-3 rounded-lg font-mono text-[11px] overflow-x-auto shadow-inner leading-relaxed">
                                        <pre>{{ $ep['sample_response'] }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- FEATURE 3: MCP SERVER FOR AGENTIC ANALYSIS --}}
        <div x-show="activeTab === 'mcp'" class="space-y-4">
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-800">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>{{ $currentLang === 'es' ? 'Model Context Protocol (MCP) Server Integrado' : 'Integrated Model Context Protocol (MCP) Server' }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 font-semibold">AI Agent Ready</span>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $currentLang === 'es'
                                ? 'Conecta Claude Desktop, Cursor, Copilot o Antigravity IDE vía Server-Sent Events (SSE) para consultar analítica con lenguaje natural.'
                                : 'Connect Claude Desktop, Cursor, Copilot, or Antigravity IDE over Server-Sent Events (SSE) to query performance in natural language.' }}
                        </p>
                    </div>
                    <div class="text-xs font-mono bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">
                        SSE: https://aura-living.apis-hub.cloud/mcp/sse
                    </div>
                </div>

                {{-- MCP Tools Table --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($mcpTools as $tool)
                        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                    <span class="font-mono text-xs font-bold">{{ $tool['name'] }}</span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-2 leading-relaxed">
                                    {{ $tool['desc'] }}
                                </p>
                            </div>
                            <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                                <span class="text-[10px] uppercase font-semibold text-gray-400 tracking-wider">{{ $currentLang === 'es' ? 'Prompt de Ejemplo:' : 'Sample Query:' }}</span>
                                <p class="text-[11px] font-mono text-gray-700 dark:text-gray-300 mt-1 italic bg-white dark:bg-gray-900 p-2 rounded border border-gray-200 dark:border-gray-800">
                                    "{{ $tool['example'] }}"
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Bottom Stepper CTA --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <a href="{{ $currentLang === 'es' ? route('demo.collaboration.es') : route('demo.collaboration', ['lang' => 'en']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>{{ $currentLang === 'es' ? 'Volver al Paso 4: Colaboración & Equipos' : 'Back to Step 4: Team Collaboration' }}</span>
            </a>

            <a href="{{ $currentLang === 'es' ? route('landing.plans.es') : route('landing.plans') }}"
               class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition-colors">
                <span>{{ $currentLang === 'es' ? 'Comenzar Prueba Gratis en APIs Hub' : 'Start Your Free Trial with APIs Hub' }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </main>
</x-layouts.public>

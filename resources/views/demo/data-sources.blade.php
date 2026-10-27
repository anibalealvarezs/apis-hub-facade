<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <meta name="description" content="{{ __('Demostración pública interactiva de APIs Hub: Configuración de Fuentes de Datos y Cuota de Activos.') }}">
    <title>{{ __('Paso 1: Fuentes de Datos') }} — APIs Hub</title>
    <script>
        (function() {
            var theme = localStorage.getItem('demo_theme');
            if (!theme) {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    <style>
        html { background-color: #f9fafb; color-scheme: light; }
        html.dark { background-color: #030712; color-scheme: dark; }
    </style>
    <script>
        const origWarn = console.warn;
        console.warn = function(...args) {
            if (args[0] && typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) return;
            origWarn.apply(console, args);
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            '50': '#eff6ff', '100': '#dbeafe', '200': '#bfdbfe', '300': '#93c5fd',
                            '400': '#60a5fa', '500': '#3b82f6', '600': '#2563eb', '700': '#1d4ed8',
                            '800': '#1e40af', '900': '#1e3a8a', '950': '#172554'
                        },
                        success: {
                            '500': '#10b981', '600': '#059669', '700': '#047857'
                        },
                        warning: {
                            '50': '#fffbeb', '500': '#f59e0b', '600': '#d97706', '700': '#b45309'
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="{{ asset('css/branding.css') }}"/>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col font-sans antialiased selection:bg-primary-500 selection:text-white"
      x-data="demoDataSources({
          providers: @js($providers),
          activeChannelKey: 'google_search_console',
          tierUsage: {{ $stats['tier_usage'] }},
          tierMax: {{ $stats['tier_max'] }},
          theme: localStorage.getItem('demo_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
          currentLang: '{{ $currentLang }}'
      })">

    {{-- Unified Public Header --}}
    @include('components.public.header')

    {{-- Interactive Multi-Step Demo Progress Bar --}}
    <div class="pt-20 md:pt-24">
        @include('demo.stepper', ['currentStep' => 1, 'currentLang' => $currentLang])
    </div>

    {{-- Main Container --}}
    <main class="flex-1 w-full max-w-7xl mx-auto p-4 sm:p-6 space-y-6">

        {{-- Page Header with Quota & Billing Cycle Ledger (Exact Replica of DataSources.blade.php) --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-xs">
            <div>
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-primary-50 dark:bg-primary-950/60 text-primary-600 dark:text-primary-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                            {{ $currentLang === 'es' ? 'Configuración de Fuentes de Datos' : 'Data Sources Configuration' }}
                        </h1>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $currentLang === 'es' ? 'Conecta y habilita cuentas publicitarias, propiedades analíticas y listas de correo con control de cuota de activos.' : 'Connect and toggle ad accounts, web properties, and email audiences with real-time asset quota management.' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Tier Usage Quota Badge --}}
            <div class="flex items-center gap-4 text-sm bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700/80 rounded-xl px-4 py-2.5 shadow-xs">
                <div class="flex flex-col text-xs text-gray-500 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 pr-3">
                    <span class="text-[10px] uppercase font-semibold text-gray-400">{{ $currentLang === 'es' ? 'Ciclo Actual' : 'Current Cycle' }}</span>
                    <span class="font-bold text-gray-800 dark:text-gray-200">{{ $stats['billing_cycle'] }}</span>
                </div>
                <div class="flex flex-col text-xs text-gray-500 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700 pr-3">
                    <span class="text-[10px] uppercase font-semibold text-gray-400">{{ $currentLang === 'es' ? 'Reinicio de Cuota' : 'Quota Reset' }}</span>
                    <span class="font-bold text-gray-800 dark:text-gray-200">{{ $stats['quota_reset'] }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex flex-col">
                        <span class="text-[10px] uppercase font-bold tracking-wide text-gray-400 dark:text-gray-500">{{ $currentLang === 'es' ? 'Uso del Tier' : 'Tier Usage' }}</span>
                        <div class="font-extrabold text-base text-emerald-600 dark:text-emerald-400">
                            <span x-text="tierUsage"></span> / <span x-text="tierMax"></span>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                        {{ $currentLang === 'es' ? 'Saludable' : 'Healthy' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- 2-Column Layout: Sidebar Channels + Main Channel Config --}}
        <div class="flex flex-col md:flex-row gap-6 items-start relative">
            
            {{-- Channels Sidebar (Exact Replica of DataSources sidebar) --}}
            <aside class="w-full md:w-80 md:flex-none flex-shrink-0 flex flex-col gap-3 bg-white dark:bg-gray-900 rounded-xl shadow-xs ring-1 ring-gray-200 dark:ring-gray-800 p-4 sticky top-24">
                <div class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 px-2 pb-1 border-b border-gray-100 dark:border-gray-800">
                    {{ $currentLang === 'es' ? 'Proveedores Conectados' : 'Connected Providers' }}
                </div>

                <template x-for="(provider, pKey) in providers" :key="pKey">
                    <div class="flex flex-col gap-1" x-data="{ expanded: true }">
                        <!-- Provider Header -->
                        <button type="button"
                                @click="expanded = !expanded"
                                class="w-full flex items-center justify-between text-gray-900 dark:text-white font-bold p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/60 transition text-left">
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 flex items-center justify-center shrink-0 text-gray-500">
                                    <template x-if="pKey === 'google'">
                                        {!! \App\Support\BrandIcon::forProvider('google', 'w-4 h-4') !!}
                                    </template>
                                    <template x-if="pKey === 'facebook'">
                                        {!! \App\Support\BrandIcon::forProvider('meta', 'w-4 h-4') !!}
                                    </template>
                                    <template x-if="pKey === 'mailchimp'">
                                        {!! \App\Support\BrandIcon::forProvider('mailchimp', 'w-4 h-4') !!}
                                    </template>
                                    <template x-if="pKey === 'tiktok'">
                                        {!! \App\Support\BrandIcon::forProvider('tiktok', 'w-4 h-4') !!}
                                    </template>
                                    <template x-if="pKey === 'shopify'">
                                        {!! \App\Support\BrandIcon::forProvider('shopify', 'w-4 h-4') !!}
                                    </template>
                                    <template x-if="pKey === 'klaviyo'">
                                        {!! \App\Support\BrandIcon::forProvider('klaviyo', 'w-4 h-4') !!}
                                    </template>
                                </div>
                                <span class="text-sm tracking-tight" x-text="provider.label"></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full"
                                      x-text="getProviderCount(provider)"></span>
                                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="expanded ? '' : '-rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </button>

                        <!-- Nested Channels -->
                        <div x-show="expanded" class="flex flex-col gap-1 ml-3 border-l-2 border-gray-100 dark:border-gray-800 pl-2 mt-0.5">
                            <template x-for="channel in provider.channels" :key="channel.key">
                                <button type="button"
                                        @click="selectChannel(channel.key)"
                                        class="px-2.5 py-1.5 text-left rounded-lg text-xs font-medium transition-all flex items-center justify-between"
                                        :class="activeChannelKey === channel.key ? 'bg-primary-50 dark:bg-primary-950/70 text-primary-600 dark:text-primary-400 font-semibold ring-1 ring-primary-500/20' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/40'">
                                    <span class="truncate" x-text="channel.label"></span>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <span class="text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 px-1.5 py-0.5 rounded-md"
                                              :class="activeChannelKey === channel.key ? 'bg-primary-100 dark:bg-primary-900/60 text-primary-700 dark:text-primary-300' : ''"
                                              x-text="channel.count"></span>
                                        <template x-if="channel.enabled">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        </template>
                                        <template x-if="!channel.enabled">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-700"></span>
                                        </template>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </aside>

            {{-- Main Content: Active Channel Configuration & Assets (Exact Replica of DataSources content) --}}
            <section class="w-full flex-1 min-w-0 bg-white dark:bg-gray-900 rounded-xl shadow-xs ring-1 ring-gray-200 dark:ring-gray-800 p-6 space-y-6">
                {{-- Channel Header --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-gray-100 dark:border-gray-800">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 uppercase tracking-wider">
                                {{ $currentLang === 'es' ? 'Conectado' : 'Connected' }}
                            </span>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white" x-text="activeChannel?.label"></h2>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap items-center gap-x-2">
                            <span>{{ $currentLang === 'es' ? 'Última sincronización' : 'Last synced' }}: {{ $stats['last_synced'] }}</span>
                            <span class="text-gray-300 dark:text-gray-600">|</span>
                            <span>{{ $currentLang === 'es' ? 'Despliegue de infraestructura' : 'Server deployment' }}: {{ $stats['last_deploy'] }}</span>
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="simulateDiscovery()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-200 transition-colors shadow-xs">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <span>{{ $currentLang === 'es' ? 'Descubrir Activos' : 'Discover Assets' }}</span>
                        </button>
                    </div>
                </div>

                {{-- Channel Enabled Status Alert --}}
                <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        <div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $currentLang === 'es' ? 'Sincronización de Canal Activa' : 'Channel Synchronization Active' }}
                            </span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $currentLang === 'es' ? 'Los datos se sincronizan diariamente y se integran automáticamente en los modelos normalizados.' : 'Daily sync extracts raw metrics and transforms them into standardized omnichannel schemas.' }}
                            </p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                        {{ $currentLang === 'es' ? 'Activo' : 'Active' }}
                    </span>
                </div>

                {{-- Assets List / Repeater Table (Exact Replica of DataSources repeaters) --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $currentLang === 'es' ? 'Activos Monitoreados' : 'Monitored Assets' }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $currentLang === 'es' ? 'Cada activo habilitado cuenta para tu cuota de cuenta y se asocia a grupos de activos en el Paso 2.' : 'Each enabled asset contributes to your project tier quota and is grouped in Step 2.' }}
                            </p>
                        </div>
                        <span class="text-xs font-semibold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/50 px-2.5 py-1 rounded-lg border border-primary-200 dark:border-primary-900/50"
                              x-text="(activeChannel?.assets?.length || 0) + ' ' + (currentLang === 'es' ? 'Activos Disponibles' : 'Assets Available')">
                        </span>
                    </div>

                    <div class="divide-y divide-gray-200 dark:divide-gray-800 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden bg-white dark:bg-gray-900">
                        <template x-for="(asset, aIdx) in (activeChannel?.assets || [])" :key="asset.id">
                            <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                                <div class="flex items-start gap-3 min-w-0">
                                    {{-- Asset Icon --}}
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0 text-gray-500 dark:text-gray-400 mt-0.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-bold text-sm text-gray-900 dark:text-white truncate" x-text="asset.title"></span>
                                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300" x-text="asset.type"></span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="asset.helper"></p>
                                        
                                        {{-- Linked Asset Groups Preview --}}
                                        <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                            <span class="text-[10px] text-gray-400 uppercase font-semibold">{{ $currentLang === 'es' ? 'Grupos:' : 'Groups:' }}</span>
                                            <template x-for="group in asset.groups" :key="group">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-primary-50 dark:bg-primary-950/60 text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-800">
                                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605"/></svg>
                                                    <span x-text="group"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- Toggle Switch Replica --}}
                                <div class="flex items-center gap-3 self-end sm:self-center shrink-0">
                                    <button type="button"
                                            @click="toggleAsset(asset)"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                                            :class="asset.enabled ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700'"
                                            role="switch"
                                            :aria-checked="asset.enabled">
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                              :class="asset.enabled ? 'translate-x-5' : 'translate-x-0'"></span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Action / Bottom Stepper CTA --}}
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $currentLang === 'es' ? '💡 En este paso activas los activos que luego organizarás en Grupos de Activos.' : '💡 In this step you enable the assets which you will organize into Asset Groups next.' }}
                    </p>
                    <a href="{{ $currentLang === 'es' ? '/es/demo/asset-groups' : '/demo/asset-groups?lang=en' }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-primary-600 hover:bg-primary-500 text-white shadow-xs transition-colors">
                        <span>{{ $currentLang === 'es' ? 'Continuar al Paso 2: Grupos de Activos' : 'Continue to Step 2: Asset Groups' }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </section>
        </div>
    </main>

    {{-- Unified Public Footer --}}
    @include('components.public.footer')

    <script>
        function demoDataSources(config) {
            return {
                darkMode: document.documentElement.classList.contains('dark'),
                providers: config.providers || {},
                activeChannelKey: config.activeChannelKey || 'google_search_console',
                tierUsage: config.tierUsage || 21,
                tierMax: config.tierMax || 50,
                theme: config.theme || 'dark',
                currentLang: config.currentLang || 'es',

                init() {
                    this.$watch('darkMode', val => {
                        this.theme = val ? 'dark' : 'light';
                        localStorage.setItem('color-theme', this.theme);
                        localStorage.setItem('demo_theme', this.theme);
                        document.documentElement.classList.toggle('dark', val);
                    });
                },

                get activeChannel() {
                    for (const p of Object.values(this.providers)) {
                        for (const ch of p.channels) {
                            if (ch.key === this.activeChannelKey) return ch;
                        }
                    }
                    return null;
                },

                getProviderCount(provider) {
                    return provider.channels.reduce((sum, ch) => sum + (ch.count || 0), 0);
                },

                selectChannel(key) {
                    this.activeChannelKey = key;
                },

                toggleAsset(asset) {
                    asset.enabled = !asset.enabled;
                    if (asset.enabled) {
                        this.tierUsage++;
                    } else {
                        this.tierUsage = Math.max(0, this.tierUsage - 1);
                    }
                    // Update active channel count
                    if (this.activeChannel) {
                        this.activeChannel.count = this.activeChannel.assets.filter(a => a.enabled).length;
                    }
                },

                simulateDiscovery() {
                    alert(this.currentLang === 'es' 
                        ? '¡Descubrimiento completado! Todos los activos disponibles están sincronizados.' 
                        : 'Asset discovery completed! All available assets are synced.');
                },

                toggleTheme() {
                    this.theme = this.theme === 'dark' ? 'light' : 'dark';
                    localStorage.setItem('demo_theme', this.theme);
                    if (this.theme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            };
        }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <meta name="description" content="{{ __('Demostración pública interactiva de APIs Hub con datos normalizados en tiempo real para 6 canales y 3 grupos de activos.') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $dashboard->name }} — APIs Hub</title>
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
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/dashboard-builder.css') }}?v={{ time() }}"/>
    <link rel="stylesheet" href="{{ asset('css/branding.css') }}"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack-all.min.js"></script>
    <script>
        window.dashboardWidgetDataUrlPrefix = '/demo/api/widget/';
    </script>
    <script src="{{ asset('js/dashboard-renderer.js') }}?v={{ time() }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-950 min-h-screen text-gray-900 dark:text-gray-100 flex flex-col font-sans"
      x-data="demoDashboard({
          selectedGroup: '1',
          currentLang: '{{ $currentLang }}',
          assetGroups: @js($assetGroups),
          widgets: @js($widgets)
      })"
      x-init="init()">

    {{-- Top Demo Announcement & CTA Bar --}}
    <header class="sticky top-0 z-50 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 shadow-xs px-4 py-2.5">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ $currentLang === 'es' ? '/' : '/en' }}" class="flex items-center gap-2 group">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-primary-600 to-indigo-500 flex items-center justify-center text-white font-bold text-sm shadow-sm group-hover:scale-105 transition-transform">
                        AH
                    </div>
                    <span class="font-bold text-base tracking-tight text-gray-900 dark:text-white">APIs Hub</span>
                </a>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    {{ __('Demo Interactivo Beta') }}
                </span>
            </div>

            <div class="flex items-center gap-3 text-xs">
                {{-- Theme Switcher --}}
                <button @click="toggleTheme()" class="p-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="{{ __('Cambiar Modo') }}">
                    <template x-if="theme === 'dark'">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </template>
                    <template x-if="theme !== 'dark'">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </template>
                </button>

                {{-- Language Switcher --}}
                <div class="flex items-center bg-gray-100 dark:bg-gray-800 rounded-lg p-0.5 border border-gray-200 dark:border-gray-700">
                    <a href="/es/demo" class="px-2 py-1 rounded text-xs font-semibold {{ $currentLang === 'es' ? 'bg-primary-600 text-white shadow-xs' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900' }}">ES</a>
                    <a href="/demo?lang=en" class="px-2 py-1 rounded text-xs font-semibold {{ $currentLang === 'en' ? 'bg-primary-600 text-white shadow-xs' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900' }}">EN</a>
                </div>

                {{-- CTA Button --}}
                <a href="{{ $currentLang === 'es' ? '/es/planes' : '/plans' }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg font-semibold bg-primary-600 hover:bg-primary-500 text-white shadow-sm transition-colors focus:ring-2 focus:ring-primary-400">
                    <span>{{ __('Comenzar Prueba') }}</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 space-y-4">
        {{-- Hero Context & Filter Controls --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-xs space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        {{ $dashboard->name }}
                    </h1>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-3xl">
                        {{ $dashboard->description }}
                    </p>
                </div>
                <div class="flex items-center gap-2 self-start md:self-auto">
                    <button @click="refreshAll()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-200 transition-colors shadow-xs">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>{{ __('Actualizar') }}</span>
                    </button>
                </div>
            </div>

            {{-- Asset Group & Date Filters --}}
            <div class="flex flex-wrap items-center justify-between gap-4 pt-1">
                {{-- Asset Group Selector (The Hero Control) --}}
                <div class="flex items-center gap-2.5">
                    <label for="demo-asset-group" class="text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <span>{{ __('Grupo de Activos:') }}</span>
                    </label>
                    <div class="relative">
                        <select id="demo-asset-group"
                                x-model="selectedGroup"
                                @change="onGroupChange()"
                                class="text-xs font-medium bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-1.5 pr-8 focus:ring-2 focus:ring-primary-500 text-gray-900 dark:text-gray-100 shadow-xs cursor-pointer">
                            <template x-for="(label, id) in assetGroups" :key="id">
                                <option :value="id" x-text="label" :selected="id === selectedGroup"></option>
                            </template>
                        </select>
                    </div>
                    <span class="text-[11px] text-gray-400 dark:text-gray-500 italic hidden sm:inline">
                        ({{ __('Cambia entre regiones y marcas para ver las métricas normalizadas') }})
                    </span>
                </div>

                {{-- Badges for the 6 Active Beta Channels --}}
                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                    <span class="px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/60 font-medium">Meta Ads</span>
                    <span class="px-2 py-0.5 rounded-md bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/60 font-medium">Meta Organic</span>
                    <span class="px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 font-medium">Google Ads</span>
                    <span class="px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60 font-medium">GSC</span>
                    <span class="px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 font-medium">GA4</span>
                    <span class="px-2 py-0.5 rounded-md bg-yellow-50 dark:bg-yellow-950/60 text-yellow-700 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-800/60 font-medium">Mailchimp</span>
                </div>
            </div>
        </div>

        {{-- GridStack Canvas --}}
        <div id="demo-grid-stack" class="grid-stack relative" wire:ignore>
            @foreach ($widgets as $widget)
                <div class="grid-stack-item"
                     gs-id="{{ $widget['id'] }}"
                     gs-x="{{ $widget['grid_x'] }}"
                     gs-y="{{ $widget['grid_y'] }}"
                     gs-w="{{ $widget['grid_w'] }}"
                     gs-h="{{ $widget['grid_h'] }}"
                     gs-no-resize="true"
                     gs-no-move="true">
                    <div class="grid-stack-item-content rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-xs relative flex flex-col overflow-hidden">
                        {{-- Widget Card Header --}}
                        <div class="px-3.5 py-2.5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 flex items-center justify-between flex-shrink-0">
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate pr-2">
                                {{ $widget['name'] }}
                            </span>
                            <span class="text-[10px] font-mono uppercase px-1.5 py-0.5 rounded bg-gray-200/60 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300">
                                {{ str_replace('_', ' ', $widget['resolved_controls']['channel'] ?? 'Omnichannel') }}
                            </span>
                        </div>

                        {{-- Widget Content Container --}}
                        <div class="widget-content flex-1 p-3 flex flex-col justify-center min-h-[120px]"
                             id="widget-container-{{ $widget['id'] }}"
                             data-widget-id="{{ $widget['id'] }}">
                            <div class="flex items-center justify-center h-full">
                                <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </main>

    {{-- Bottom Banner Floating CTA --}}
    <footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 py-6 px-4 mt-8">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ __('¿Listo para unificar tus métricas reales?') }}
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('Conecta tus cuentas de Google, Meta y Mailchimp sin esfuerzo y personaliza tus propios dashboards.') }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ $currentLang === 'es' ? '/es/planes' : '/plans' }}"
                   class="px-4 py-2 rounded-lg text-xs font-semibold bg-primary-600 hover:bg-primary-500 text-white shadow-xs transition-colors">
                    {{ __('Ver Planes y Comenzar') }}
                </a>
            </div>
        </div>
    </footer>

    <script>
        function demoDashboard(config) {
            return {
                selectedGroup: config.selectedGroup || '1',
                currentLang: config.currentLang || 'es',
                assetGroups: config.assetGroups || {},
                widgets: config.widgets || [],
                theme: localStorage.getItem('demo_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),

                init() {
                    const grid = GridStack.init({
                        staticGrid: true,
                        margin: 10,
                        cellHeight: 70,
                        animate: true
                    }, '#demo-grid-stack');

                    this.loadAllWidgets();
                },

                toggleTheme() {
                    this.theme = this.theme === 'dark' ? 'light' : 'dark';
                    localStorage.setItem('demo_theme', this.theme);
                    if (this.theme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                    this.loadAllWidgets();
                },

                onGroupChange() {
                    this.loadAllWidgets();
                },

                refreshAll() {
                    this.loadAllWidgets();
                },

                loadAllWidgets() {
                    const renderer = window.dashboardRenderer || window.DashboardRenderer;
                    if (!renderer) {
                        setTimeout(() => this.loadAllWidgets(), 100);
                        return;
                    }

                    this.widgets.forEach(w => {
                        const el = document.getElementById('widget-container-' + w.id);
                        if (!el) return;

                        const controls = {
                            asset_group: this.selectedGroup,
                            lang: this.currentLang
                        };

                        renderer.renderWidget(w.id, el, controls, 'demo-tenant');
                    });
                }
            };
        }
    </script>
</body>
</html>

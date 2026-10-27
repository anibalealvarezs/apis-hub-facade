<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">
    <meta name="description" content="{{ $currentLang === 'es' ? 'Demostración pública interactiva de APIs Hub: Grupos de Activos Cross-Channel.' : 'APIs Hub Interactive Public Demo: Cross-Channel Asset Groups.' }}">
    <title>{{ $currentLang === 'es' ? 'Paso 2: Grupos de Activos' : 'Step 2: Asset Groups' }} — APIs Hub</title>
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
      x-data="demoAssetGroups({
          groups: @js($assetGroupsList),
          theme: localStorage.getItem('demo_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
          currentLang: '{{ $currentLang }}'
      })">

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
                    {{ $currentLang === 'es' ? 'Demo Interactivo Beta' : 'Interactive Beta Demo' }}
                </span>
            </div>

            <div class="flex items-center gap-3 text-xs">
                {{-- Theme Switcher --}}
                <button @click="toggleTheme()" class="p-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="{{ $currentLang === 'es' ? 'Cambiar Modo' : 'Toggle Theme' }}">
                    <template x-if="theme === 'dark'">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </template>
                    <template x-if="theme !== 'dark'">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </template>
                </button>

                {{-- Language Switcher --}}
                <div class="flex items-center bg-gray-100 dark:bg-gray-800 rounded-lg p-0.5 border border-gray-200 dark:border-gray-700">
                    <a href="/es/demo/asset-groups" class="px-2 py-1 rounded text-xs font-semibold {{ $currentLang === 'es' ? 'bg-primary-600 text-white shadow-xs' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900' }}">ES</a>
                    <a href="/demo/asset-groups?lang=en" class="px-2 py-1 rounded text-xs font-semibold {{ $currentLang === 'en' ? 'bg-primary-600 text-white shadow-xs' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900' }}">EN</a>
                </div>

                {{-- CTA Button --}}
                <a href="{{ $currentLang === 'es' ? '/es/planes' : '/plans' }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg font-semibold bg-primary-600 hover:bg-primary-500 text-white shadow-sm transition-colors focus:ring-2 focus:ring-primary-400">
                    <span>{{ $currentLang === 'es' ? 'Comenzar Prueba' : 'Start Free Trial' }}</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        </div>
    </header>

    {{-- Interactive Multi-Step Demo Progress Bar --}}
    @include('demo.stepper', ['currentStep' => 2, 'currentLang' => $currentLang])

    {{-- Main Container --}}
    <main class="flex-1 w-full max-w-7xl mx-auto p-4 sm:p-6 space-y-6">

        {{-- Page Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-xs">
            <div>
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-primary-50 dark:bg-primary-950/60 text-primary-600 dark:text-primary-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                            {{ $currentLang === 'es' ? 'Grupos de Activos (Asset Groups)' : 'Asset Groups' }}
                        </h1>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $currentLang === 'es'
                                ? 'Organiza los activos habilitados en el Paso 1 en unidades de negocio, marcas o regiones para filtrar dashboards instantáneamente.'
                                : 'Organize monitored assets enabled in Step 1 into business units, brands, or regions to filter dashboards instantly.' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button"
                        @click="showCreateModal = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-primary-600 hover:bg-primary-500 text-white shadow-xs transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>{{ $currentLang === 'es' ? 'Nuevo Grupo de Activos' : 'New Asset Group' }}</span>
                </button>
            </div>
        </div>

        {{-- Filament Table Replica: List of Asset Groups --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between gap-4">
                <div class="relative max-w-sm w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text"
                           x-model="search"
                           placeholder="{{ $currentLang === 'es' ? 'Buscar grupos de activos...' : 'Search asset groups...' }}"
                           class="w-full pl-9 pr-4 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-primary-500">
                </div>
                <span class="text-xs text-gray-400" x-text="filteredGroups.length + ' {{ $currentLang === 'es' ? 'grupos definidos' : 'groups defined' }}'"></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400 uppercase font-semibold">
                        <tr>
                            <th class="px-4 py-3">{{ $currentLang === 'es' ? 'Nombre' : 'Name' }}</th>
                            <th class="px-4 py-3">{{ $currentLang === 'es' ? 'Descripción' : 'Description' }}</th>
                            <th class="px-4 py-3 text-center">{{ $currentLang === 'es' ? 'Total Activos' : 'Total Assets' }}</th>
                            <th class="px-4 py-3">{{ $currentLang === 'es' ? 'Resumen de Canales (Badges)' : 'Channels Summary' }}</th>
                            <th class="px-4 py-3 text-right">{{ $currentLang === 'es' ? 'Acciones' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach($assetGroupsList as $group)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                                x-show="!search || '{{ strtolower(addslashes($group['name'])) }}'.includes(search.toLowerCase()) || '{{ strtolower(addslashes($group['description'])) }}'.includes(search.toLowerCase())">
                                <td class="px-4 py-3.5 font-bold text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-primary-500"></div>
                                        <span>{{ $group['name'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ $group['description'] }}</td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary-50 dark:bg-primary-950/60 text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-800">
                                        {{ count($group['items']) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex flex-wrap gap-1.5 max-w-md">
                                        @php
                                            $channelCounts = [];
                                            foreach ($group['items'] as $item) {
                                                $channelCounts[$item['channel']] = ($channelCounts[$item['channel']] ?? 0) + 1;
                                            }
                                            $badges = [
                                                'google_search_console' => 'GSC',
                                                'google_analytics' => 'GA4',
                                                'google_ads' => 'GAds',
                                                'facebook_marketing' => 'Meta Ads',
                                                'facebook_organic' => 'Meta Org',
                                                'mailchimp' => 'Mailchimp'
                                            ];
                                        @endphp
                                        @foreach($channelCounts as $cKey => $cnt)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                                                {{ ($badges[$cKey] ?? $cKey) . " ({$cnt})" }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                @click="viewGroupById('{{ $group['id'] }}')"
                                                class="px-2.5 py-1 rounded text-xs font-medium text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-950/50 transition">
                                            {{ $currentLang === 'es' ? 'Ver Activos' : 'View Assets' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Interactive Asset Selector Showcase (Exact Replica of asset-selector.blade.php) --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>{{ $currentLang === 'es' ? 'Vista de Configuración de Grupo (Selector Cross-Channel)' : 'Asset Group Configuration (Cross-Channel Selector)' }}</span>
                        <span class="text-xs px-2 py-0.5 rounded bg-primary-100 dark:bg-primary-950 text-primary-700 dark:text-primary-300 font-semibold"
                              x-text="activeGroup.name"></span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $currentLang === 'es'
                            ? 'Selecciona un grupo para explorar cómo se distribuyen los activos de cada canal configurados en el Paso 1.'
                            : 'Select an asset group to inspect how monitored assets configured in Step 1 are distributed.' }}
                    </p>
                </div>

                {{-- Group Switcher Tab Pills --}}
                <div class="flex items-center gap-1.5 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl">
                    <template x-for="g in groups" :key="g.id">
                        <button type="button"
                                @click="activeGroup = g"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
                                :class="activeGroup.id === g.id ? 'bg-white dark:bg-gray-900 text-primary-600 dark:text-primary-400 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                                x-text="g.name.split(' (')[0]">
                        </button>
                    </template>
                </div>
            </div>

            {{-- Horizontal Multi-Channel Columns (Exact Replica of asg-container) --}}
            <div class="min-w-0 flex overflow-x-auto gap-4 custom-scrollbar pb-3 items-stretch snap-x snap-mandatory">
                <template x-for="channelKey in ['google_search_console', 'google_analytics', 'google_ads', 'facebook_marketing', 'facebook_organic', 'mailchimp']" :key="channelKey">
                    <div class="flex-none w-72 h-80 min-h-0 flex flex-col snap-start">
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-xs overflow-hidden flex flex-col h-full min-h-0">
                            
                            {{-- Channel Header --}}
                            <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 flex-shrink-0">
                                <span class="text-xs font-bold text-gray-800 dark:text-white uppercase tracking-wider truncate"
                                      x-text="getChannelLabel(channelKey)"></span>
                                <span class="text-[10px] font-bold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/60 px-2 py-0.5 rounded-full"
                                      x-text="getChannelAssetsForGroup(channelKey).length + ' ' + (currentLang === 'es' ? 'Asignados' : 'Assigned')"></span>
                            </div>

                            {{-- Channel Assets List --}}
                            <div class="p-3 flex-1 flex flex-col min-h-0 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700/60">
                                <template x-for="item in getChannelAssetsForGroup(channelKey)" :key="item.asset_id">
                                    <div class="py-2.5 flex items-start gap-2.5">
                                        <div class="w-4 h-4 rounded mt-0.5 bg-primary-600 flex items-center justify-center text-white shrink-0">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate" x-text="item.title"></p>
                                            <p class="text-[10px] text-gray-400 dark:text-gray-500 font-mono mt-0.5 truncate" x-text="item.asset_id"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Bottom Stepper CTA --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <a href="{{ $currentLang === 'es' ? '/es/demo/data-sources' : '/demo/data-sources?lang=en' }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>{{ $currentLang === 'es' ? 'Volver al Paso 1: Fuentes de Datos' : 'Back to Step 1: Data Sources' }}</span>
            </a>

            <a href="{{ $currentLang === 'es' ? '/es/demo' : '/demo?lang=en' }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold bg-primary-600 hover:bg-primary-500 text-white shadow-xs transition-colors">
                <span>{{ $currentLang === 'es' ? 'Ver Dashboard con estos Grupos (Paso 3)' : 'View Dashboard with these Groups (Step 3)' }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </main>

    {{-- Footer CTA --}}
    <footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 py-6 px-4 mt-12">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ $currentLang === 'es' ? 'Demostración Interactiva Paso 2 de 3' : 'Interactive Demo Step 2 of 3' }}
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $currentLang === 'es'
                        ? 'Descubre la flexibilidad de filtrar métricas complejas asignando activos a grupos dedicados.'
                        : 'Discover the flexibility of filtering complex metrics by assigning assets to dedicated groups.' }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ $currentLang === 'es' ? '/es/planes' : '/plans' }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold bg-primary-600 hover:bg-primary-500 text-white shadow-sm transition-colors">
                    <span>{{ $currentLang === 'es' ? 'Comenzar Prueba Gratis' : 'Start Free Trial' }}</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        </div>
    </footer>

    <script>
        function demoAssetGroups(config) {
            return {
                groups: config.groups || [],
                search: '',
                activeGroup: config.groups && config.groups[0] ? config.groups[0] : null,
                showCreateModal: false,
                theme: config.theme || 'dark',
                currentLang: config.currentLang || 'es',

                get filteredGroups() {
                    if (!this.search) return this.groups;
                    const q = this.search.toLowerCase();
                    return this.groups.filter(g => 
                        g.name.toLowerCase().includes(q) || 
                        g.description.toLowerCase().includes(q)
                    );
                },

                getChannelSummaryBadges(group) {
                    const counts = {};
                    for (const item of group.items) {
                        counts[item.channel] = (counts[item.channel] || 0) + 1;
                    }
                    const labels = {
                        'google_search_console': 'GSC',
                        'google_analytics': 'GA4',
                        'google_ads': 'GAds',
                        'facebook_marketing': 'Meta Ads',
                        'facebook_organic': 'Meta Org',
                        'mailchimp': 'Mailchimp'
                    };
                    return Object.entries(counts).map(([ch, cnt]) => `${labels[ch] || ch} (${cnt})`);
                },

                getChannelLabel(channelKey) {
                    const map = {
                        'google_search_console': 'Google Search Console',
                        'google_analytics': 'Google Analytics 4',
                        'google_ads': 'Google Ads',
                        'facebook_marketing': 'Meta Ads',
                        'facebook_organic': 'Meta Organic',
                        'mailchimp': 'Mailchimp'
                    };
                    return map[channelKey] || channelKey;
                },

                getChannelAssetsForGroup(channelKey) {
                    if (!this.activeGroup) return [];
                    return this.activeGroup.items.filter(i => i.channel === channelKey);
                },

                viewGroup(group) {
                    this.activeGroup = group;
                    window.scrollTo({ top: document.body.scrollHeight / 2, behavior: 'smooth' });
                },

                viewGroupById(id) {
                    const found = this.groups.find(g => String(g.id) === String(id));
                    if (found) {
                        this.viewGroup(found);
                    }
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

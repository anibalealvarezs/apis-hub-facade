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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0/dist/css/jsvectormap.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/dashboard-builder.css') }}?v={{ time() }}"/>
    <link rel="stylesheet" href="{{ asset('css/dashboards.css') }}?v={{ time() }}"/>
    <link rel="stylesheet" href="{{ asset('css/branding.css') }}"/>
    <style>
        /* Map / List View Custom Styles for Demo */
        .dash-view-toggle {
            display: inline-flex !important;
            align-items: center !important;
            border-radius: 0.5rem !important;
            padding: 2px !important;
            background: #f1f5f9 !important;
            border: 1px solid #e2e8f0 !important;
        }
        .dark .dash-view-toggle, html.dark .dash-view-toggle {
            background: rgba(15, 23, 42, 0.6) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        .dash-view-btn {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.375rem !important;
            padding: 0.25rem 0.5rem !important;
            font-size: 0.7rem !important;
            font-weight: 600 !important;
            border-radius: 0.375rem !important;
            transition: all 0.15s ease !important;
            cursor: pointer !important;
            color: #64748b !important;
            background: transparent !important;
            border: none !important;
        }
        .dark .dash-view-btn, html.dark .dash-view-btn {
            color: #94a3b8 !important;
        }
        .dash-view-btn:hover {
            color: #0f172a !important;
        }
        .dark .dash-view-btn:hover, html.dark .dash-view-btn:hover {
            color: #f8fafc !important;
        }
        .dash-view-btn.is-active {
            background: #ffffff !important;
            color: #0284c7 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08) !important;
        }
        .dark .dash-view-btn.is-active, html.dark .dash-view-btn.is-active {
            background: #334155 !important;
            color: #38bdf8 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
        }

        /* Leaderboard and List View Containers */
        .dash-widget-list-view {
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        .dash-widget-list-scroll {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            flex: 1;
            overflow-y: auto;
            padding: 0.4rem 0.25rem;
        }
        .dash-leaderboard-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 0.75rem;
            transition: all 0.15s ease;
        }
        .dark .dash-leaderboard-item, html.dark .dash-leaderboard-item {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.08);
        }
        .dash-leaderboard-item:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        .dark .dash-leaderboard-item:hover, html.dark .dash-leaderboard-item:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.15);
        }
        .dash-leaderboard-rank {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            color: #94a3b8;
            font-weight: 700;
            width: 22px;
            flex-shrink: 0;
        }
        .dark .dash-leaderboard-rank, html.dark .dash-leaderboard-rank {
            color: #64748b;
        }
        .dash-leaderboard-name {
            font-weight: 600;
            color: #1e293b;
        }
        .dark .dash-leaderboard-name, html.dark .dash-leaderboard-name {
            color: #f1f5f9;
        }
        .dash-leaderboard-metric {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 700;
        }
        .dash-leaderboard-unit {
            color: #94a3b8;
            font-size: 0.625rem;
            letter-spacing: 0.05em;
        }

        /* ─── Refined Tile Typography (User Item 2) ─── */
        #demo-grid-stack .widget-content .text-3xl {
            font-size: 1.5rem !important; /* 24px instead of 30px text-3xl */
            line-height: 1.75rem !important;
            font-weight: 800 !important;
            letter-spacing: -0.025em !important;
            display: inline-flex !important;
            align-items: baseline !important;
            flex-wrap: wrap !important;
            column-gap: 0.25rem !important;
        }
        @media (min-width: 640px) {
            #demo-grid-stack .widget-content .text-3xl {
                font-size: 1.625rem !important;
                line-height: 1.875rem !important;
            }
        }
        /* Channel branding badges */
        .dash-channel-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.2rem 0.45rem;
            border-radius: 0.375rem;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            line-height: 1;
            border-width: 1px;
            transition: all 0.15s ease;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gridstack@12.6.0/dist/gridstack-all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0/dist/jsvectormap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap@1.6.0/dist/maps/world.js"></script>
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
    <main class="flex-1 w-full p-4 sm:p-6 space-y-4 transition-all duration-200"
          :class="isFullWidth ? 'max-w-none px-4 sm:px-8' : 'max-w-7xl mx-auto'">
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
                <div class="flex items-center gap-2 self-start md:self-auto flex-wrap">
                    {{-- Toggle Ancho Completo vs Contenido (User Item 3) --}}
                    <button type="button"
                            @click="toggleFullWidth()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-200 transition-colors shadow-xs cursor-pointer"
                            :title="isFullWidth ? '{{ __('Modo Contenido') }}' : '{{ __('Ancho Completo') }}'">
                        <template x-if="!isFullWidth">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                        </template>
                        <template x-if="isFullWidth">
                            <svg class="w-3.5 h-3.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9L4 4m0 0h4m-4 0v4m11 5l5 5m0 0v-4m0 4h-4m-7-7l5-5m0 0v4m0-4h-4M9 15l-5 5m0 0v-4m0 4h4"/></svg>
                        </template>
                        <span x-text="isFullWidth ? '{{ __('Contenido') }}' : '{{ __('Ancho Completo') }}'"></span>
                    </button>

                    <button @click="refreshAll()"
                            :disabled="isRefreshing"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-200 transition-colors shadow-xs cursor-pointer disabled:opacity-60">
                        <svg class="w-3.5 h-3.5 text-gray-500 transition-transform" :class="{ 'animate-spin text-primary-500': isRefreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span x-text="isRefreshing ? '{{ __('Actualizando...') }}' : '{{ __('Actualizar') }}'"></span>
                    </button>
                </div>
            </div>

            {{-- Asset Group & Date Granular Filters (User Item 4) --}}
            <div class="flex flex-wrap items-center justify-between gap-4 pt-1">
                {{-- Asset Group Selector (The Hero Control) --}}
                <div class="flex items-center gap-2.5 flex-wrap">
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

                    {{-- Interactive Date Range Filter (User Item 4) --}}
                    <div class="flex items-center gap-1.5 bg-gray-100/80 dark:bg-gray-800/80 p-0.5 rounded-lg border border-gray-200 dark:border-gray-700">
                        <button type="button"
                                @click="setDatePreset('7')"
                                class="px-2 py-1 rounded text-[11px] font-semibold transition-colors"
                                :class="datePreset === '7' ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                            {{ __('7D') }}
                        </button>
                        <button type="button"
                                @click="setDatePreset('14')"
                                class="px-2 py-1 rounded text-[11px] font-semibold transition-colors"
                                :class="datePreset === '14' ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                            {{ __('14D') }}
                        </button>
                        <button type="button"
                                @click="setDatePreset('30')"
                                class="px-2 py-1 rounded text-[11px] font-semibold transition-colors"
                                :class="datePreset === '30' ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                            {{ __('30D') }}
                        </button>
                        <button type="button"
                                @click="setDatePreset('90')"
                                class="px-2 py-1 rounded text-[11px] font-semibold transition-colors"
                                :class="datePreset === '90' ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-xs' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                            {{ __('90D') }}
                        </button>
                    </div>

                    {{-- Custom Date Inputs --}}
                    <div class="hidden lg:flex items-center gap-1.5 text-xs text-gray-500">
                        <input type="date"
                               x-model="dateStart"
                               @change="onDateInputChange()"
                               class="text-xs bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg px-2 py-1 text-gray-900 dark:text-gray-100 shadow-xs focus:ring-1 focus:ring-primary-500"/>
                        <span class="text-gray-400">→</span>
                        <input type="date"
                               x-model="dateEnd"
                               @change="onDateInputChange()"
                               class="text-xs bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg px-2 py-1 text-gray-900 dark:text-gray-100 shadow-xs focus:ring-1 focus:ring-primary-500"/>
                    </div>
                </div>

                {{-- Badges for the 6 Active Beta Channels with Vector Icons --}}
                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/60 font-semibold">
                        <svg class="w-3.5 h-3.5 text-blue-600 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        Meta Ads
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/60 font-semibold">
                        <svg class="w-3.5 h-3.5 text-sky-500 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        Meta Organic
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 font-semibold">
                        <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12.003 2c-5.522 0-10 4.477-10 10 0 4.418 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022.003 12c0-5.523-4.478-10-10-10z"/></svg>
                        Google Ads
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60 font-semibold">
                        <svg class="w-3.5 h-3.5 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        GSC
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 font-semibold">
                        <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        GA4
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-yellow-50 dark:bg-yellow-950/60 text-yellow-700 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-800/60 font-semibold">
                        <svg class="w-3.5 h-3.5 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Mailchimp
                    </span>
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
                        {{-- Widget Card Header with Interactive Action Toolbar --}}
                        <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40 flex items-center justify-between flex-shrink-0 gap-2">
                            <div class="flex items-center gap-2 min-w-0 pr-1">
                                <h3 class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate" title="{{ $widget['name'] }}">
                                    {{ $widget['name'] }}
                                </h3>
                            </div>

                            {{-- Interactive Action Toolbar (as in production dashboard) --}}
                            <div class="flex items-center gap-0.5 flex-shrink-0">
                                {{-- Quick Settings / Metric Selector --}}
                                <button type="button"
                                        @click="openSettings({{ $widget['id'] }})"
                                        class="p-1 rounded-md text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                        title="{{ __('Configuración / Métrica') }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </button>
                                {{-- Fullscreen Zoom Pop-out --}}
                                <button type="button"
                                        @click="openPopOut({{ $widget['id'] }})"
                                        class="p-1 rounded-md text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                        title="{{ __('Pantalla Completa') }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                </button>
                                {{-- Reload Widget --}}
                                <button type="button"
                                        @click="reloadWidget({{ $widget['id'] }})"
                                        class="p-1 rounded-md text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                        title="{{ __('Recargar') }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Widget Content Container --}}
                        <div class="widget-content flex-1 p-2 sm:p-3 flex flex-col justify-center overflow-auto"
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

    {{-- Interactive Fullscreen Modal --}}
    <div x-show="popOutActive" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-xs p-4 sm:p-6"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="closePopOut()">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 w-full max-w-5xl h-[85vh] flex flex-col overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/60 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white" x-text="popOutWidget?.name || 'Vista Detallada'"></h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="closePopOut()" class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="flex-1 p-4 relative overflow-hidden" id="demo-modal-container">
            </div>
        </div>
    </div>

    {{-- Interactive Settings / Granularity & Metric Modal --}}
    <div x-show="settingsActive" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.self="settingsActive = false">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 w-full max-w-md p-5 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-lg bg-primary-50 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white" x-text="activeWidget?.name || 'Configuración de Widget'"></h3>
                </div>
                <button type="button" @click="settingsActive = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Granularity Selector --}}
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('Granularidad Temporal / Dimensión:') }}
                </label>
                <select x-model="modalGranularity"
                        class="w-full text-xs font-medium bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg p-2 focus:ring-2 focus:ring-primary-500 text-gray-900 dark:text-gray-100">
                    <option value="daily">{{ __('Diario (Daily)') }}</option>
                    <option value="weekly">{{ __('Semanal (Weekly)') }}</option>
                    <option value="monthly">{{ __('Mensual (Monthly)') }}</option>
                    <option value="trailing_weekly">{{ __('Rolling 7 Días') }}</option>
                    <option value="trailing_monthly">{{ __('Rolling 30 Días') }}</option>
                </select>
                <p class="text-[11px] text-gray-400">
                    {{ __('Permite recalcular la serie de tiempo al vuelo sin recargar la página.') }}
                </p>
            </div>

            {{-- Metric Switcher (Choropleth Map / GSC) --}}
            <template x-if="activeWidget?.id === 113">
                <div class="space-y-1.5 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ __('Métrica Activa para el Mapa Global:') }}
                    </label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <button type="button"
                                @click="switchMapMetric('clicks')"
                                :class="selectedMapMetric === 'clicks' ? 'bg-primary-600 text-white font-bold' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                                class="p-2 rounded-lg border border-transparent transition-all text-center">
                            Clics Orgánicos
                        </button>
                        <button type="button"
                                @click="switchMapMetric('impressions')"
                                :class="selectedMapMetric === 'impressions' ? 'bg-primary-600 text-white font-bold' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                                class="p-2 rounded-lg border border-transparent transition-all text-center">
                            Impresiones
                        </button>
                        <button type="button"
                                @click="switchMapMetric('ctr')"
                                :class="selectedMapMetric === 'ctr' ? 'bg-primary-600 text-white font-bold' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                                class="p-2 rounded-lg border border-transparent transition-all text-center">
                            CTR Promedio (%)
                        </button>
                        <button type="button"
                                @click="switchMapMetric('position')"
                                :class="selectedMapMetric === 'position' ? 'bg-primary-600 text-white font-bold' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                                class="p-2 rounded-lg border border-transparent transition-all text-center">
                            Posición Media
                        </button>
                    </div>
                </div>
            </template>

            {{-- Apply Button --}}
            <div class="pt-3 flex items-center justify-end gap-2">
                <button type="button" @click="settingsActive = false" class="px-3 py-1.5 text-xs text-gray-600 dark:text-gray-400 hover:text-gray-800">
                    {{ __('Cancelar') }}
                </button>
                <button type="button" @click="applyModalSettings()" class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-primary-600 hover:bg-primary-500 text-white shadow-xs">
                    {{ __('Aplicar Cambios') }}
                </button>
            </div>
        </div>
    </div>

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
            const today = new Date();
            const formatDate = (d) => d.toISOString().split('T')[0];
            const defaultEnd = formatDate(today);
            const defaultStartDate = new Date();
            defaultStartDate.setDate(defaultStartDate.getDate() - 30);
            const defaultStart = formatDate(defaultStartDate);

            return {
                selectedGroup: config.selectedGroup || '1',
                currentLang: config.currentLang || 'es',
                assetGroups: config.assetGroups || {},
                widgets: config.widgets || [],
                isRefreshing: false,
                isFullWidth: localStorage.getItem('demo_full_width') === 'true',
                datePreset: '30',
                dateStart: defaultStart,
                dateEnd: defaultEnd,
                popOutActive: false,
                popOutWidget: null,
                settingsActive: false,
                activeWidget: null,
                modalGranularity: 'daily',
                selectedMapMetric: 'clicks',
                theme: localStorage.getItem('demo_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),

                init() {
                    const grid = GridStack.init({
                        staticGrid: true,
                        margin: 12,
                        cellHeight: 95,
                        animate: true
                    }, '#demo-grid-stack');

                    const renderer = window.dashboardRenderer || window.DashboardRenderer;
                    if (renderer && renderer.clearStorageCache) {
                        renderer.clearStorageCache();
                    }

                    this.loadAllWidgets(false);
                },

                toggleFullWidth() {
                    this.isFullWidth = !this.isFullWidth;
                    localStorage.setItem('demo_full_width', this.isFullWidth ? 'true' : 'false');
                    // Give GridStack a moment to recalculate widths
                    this.$nextTick(() => {
                        window.dispatchEvent(new Event('resize'));
                    });
                },

                setDatePreset(daysStr) {
                    this.datePreset = daysStr;
                    const days = parseInt(daysStr, 10);
                    const now = new Date();
                    const past = new Date();
                    past.setDate(past.getDate() - days);

                    this.dateEnd = formatDate(now);
                    this.dateStart = formatDate(past);

                    const renderer = window.dashboardRenderer || window.DashboardRenderer;
                    if (renderer && renderer.clearStorageCache) {
                        renderer.clearStorageCache();
                    }
                    this.loadAllWidgets(true);
                },

                onDateInputChange() {
                    this.datePreset = 'custom';
                    const renderer = window.dashboardRenderer || window.DashboardRenderer;
                    if (renderer && renderer.clearStorageCache) {
                        renderer.clearStorageCache();
                    }
                    this.loadAllWidgets(true);
                },

                toggleTheme() {
                    this.theme = this.theme === 'dark' ? 'light' : 'dark';
                    localStorage.setItem('demo_theme', this.theme);
                    if (this.theme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                    this.loadAllWidgets(true);
                },

                onGroupChange() {
                    const renderer = window.dashboardRenderer || window.DashboardRenderer;
                    if (renderer && renderer.clearStorageCache) {
                        renderer.clearStorageCache();
                    }
                    this.loadAllWidgets(true);
                },

                refreshAll() {
                    this.isRefreshing = true;
                    const renderer = window.dashboardRenderer || window.DashboardRenderer;
                    if (renderer && renderer.clearStorageCache) {
                        renderer.clearStorageCache();
                    }
                    this.loadAllWidgets(true);
                    setTimeout(() => {
                        this.isRefreshing = false;
                    }, 400);
                },

                reloadWidget(widgetId) {
                    const renderer = window.dashboardRenderer || window.DashboardRenderer;
                    const el = document.getElementById('widget-container-' + widgetId);
                    if (!el || !renderer) return;

                    el.innerHTML = renderer.loadingSkeleton();
                    renderer._widgetData.delete(el);

                    const controls = {
                        asset_group: this.selectedGroup,
                        lang: this.currentLang,
                        date_start: this.dateStart,
                        date_end: this.dateEnd
                    };
                    renderer.renderWidget(widgetId, el, controls, 'demo-tenant');
                },

                openSettings(widgetId) {
                    this.activeWidget = this.widgets.find(w => w.id === widgetId);
                    this.modalGranularity = 'daily';
                    this.settingsActive = true;
                },

                switchMapMetric(metric) {
                    this.selectedMapMetric = metric;
                    const mapEl = document.getElementById('widget-container-113');
                    if (mapEl) {
                        const pillIndices = { 'clicks': 0, 'impressions': 1, 'ctr': 2, 'position': 3 };
                        const targetPill = mapEl.querySelectorAll('.dash-map-pill')?.[pillIndices[metric]];
                        if (targetPill) {
                            targetPill.click();
                        }
                    }
                },

                applyModalSettings() {
                    this.settingsActive = false;
                    if (this.activeWidget) {
                        this.reloadWidget(this.activeWidget.id);
                    }
                },

                openPopOut(widgetId) {
                    this.popOutWidget = this.widgets.find(w => w.id === widgetId);
                    const renderer = window.dashboardRenderer || window.DashboardRenderer;
                    const modalContainer = document.getElementById('demo-modal-container');
                    const sourceContainer = document.getElementById('widget-container-' + widgetId);

                    if (!modalContainer || !renderer) return;

                    this.popOutActive = true;
                    this.$nextTick(() => {
                        modalContainer.innerHTML = '';
                        if (renderer.popOutWidget && sourceContainer) {
                            renderer.popOutWidget(sourceContainer, modalContainer);
                        } else {
                            const controls = {
                                asset_group: this.selectedGroup,
                                lang: this.currentLang,
                                date_start: this.dateStart,
                                date_end: this.dateEnd
                            };
                            renderer.renderWidget(widgetId, modalContainer, controls, 'demo-tenant');
                        }
                    });
                },

                closePopOut() {
                    const renderer = window.dashboardRenderer || window.DashboardRenderer;
                    const modalContainer = document.getElementById('demo-modal-container');
                    if (this.popOutWidget) {
                        const sourceContainer = document.getElementById('widget-container-' + this.popOutWidget.id);
                        if (renderer?.returnPoppedOutWidget && sourceContainer && modalContainer) {
                            renderer.returnPoppedOutWidget(sourceContainer, modalContainer);
                        }
                    }
                    this.popOutActive = false;
                    this.popOutWidget = null;
                },

                loadAllWidgets(force = false) {
                    const renderer = window.dashboardRenderer || window.DashboardRenderer;
                    if (!renderer) {
                        setTimeout(() => this.loadAllWidgets(force), 100);
                        return;
                    }

                    this.widgets.forEach(w => {
                        const el = document.getElementById('widget-container-' + w.id);
                        if (!el) return;

                        if (force) {
                            el.innerHTML = renderer.loadingSkeleton();
                            renderer._widgetData.delete(el);
                        }

                        const controls = {
                            asset_group: this.selectedGroup,
                            lang: this.currentLang,
                            date_start: this.dateStart,
                            date_end: this.dateEnd
                        };

                        renderer.renderWidget(w.id, el, controls, 'demo-tenant');
                    });
                }
            };
        }
    </script>
</body>
</html>

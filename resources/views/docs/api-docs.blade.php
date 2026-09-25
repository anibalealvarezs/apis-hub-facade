<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Preconnect / DNS Prefetch -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://www.googletagmanager.com">
    <link rel="preconnect" href="https://www.google.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Primary Meta Tags -->
    <title>{{ app()->getLocale() === 'es' ? 'Documentación de API y Especificación OpenAPI — APIs Hub' : 'API Documentation & OpenAPI Specification — APIs Hub' }}</title>
    <meta name="title" content="{{ app()->getLocale() === 'es' ? 'Documentación de API y Especificación OpenAPI — APIs Hub' : 'API Documentation & OpenAPI Specification — APIs Hub' }}">
    <meta name="description" content="{{ app()->getLocale() === 'es' ? 'Referencia pública completa y sandbox interactivo para la API de APIs Hub. Consultas analíticas multidimensionales, agregaciones, pings y métricas normalizadas.' : 'Complete public developer reference and interactive sandbox for the APIs Hub RESTful API. Multi-dimensional analytical queries, aggregations, pings, and normalized channel metrics.' }}">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="theme-color" content="#0f172a">
    <meta name="color-scheme" content="dark light">

    <!-- Favicons -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/apishub-favicon.png') }}">
    <link rel="icon" type="image/webp" href="{{ asset('images/branding/apishub-favicon.webp') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/branding/apishub-favicon.png') }}">

    <!-- Canonical URL & Hreflang -->
    <link rel="canonical" href="{{ app()->getLocale() === 'es' ? route('docs.api.es') : route('docs.api') }}" />
    <link rel="alternate" hreflang="en" href="{{ route('docs.api') }}" />
    <link rel="alternate" hreflang="es" href="{{ route('docs.api.es') }}" />
    <link rel="alternate" hreflang="x-default" href="{{ route('docs.api') }}" />

    <!-- Open Graph -->
    <meta property="og:site_name" content="APIs Hub">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ app()->getLocale() === 'es' ? 'Documentación de API — APIs Hub' : 'API Documentation — APIs Hub' }}">
    <meta property="og:description" content="{{ app()->getLocale() === 'es' ? 'Especificación OpenAPI 3.1 y portal para desarrolladores de APIs Hub.' : 'OpenAPI 3.1 specification and developer documentation portal for APIs Hub.' }}">
    <meta property="og:image" content="{{ asset('images/branding/apishub-620.png') }}">

    @vite(['resources/js/theme.js', 'resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* High-contrast and seamless theme adaptation for Scalar UI */
        .scalar-container {
            border-radius: 1rem;
            overflow: hidden;
            min-height: 800px;
        }

        /* Dark Mode High-Contrast Overrides: target html.dark, .dark-mode, [data-theme='dark'] */
        html.dark,
        html.dark body,
        html.dark .scalar-container,
        html.dark .scalar-api-reference,
        html.dark .scalar-app,
        .dark-mode {
            --scalar-color-1: #f8fafc !important; /* bright white/slate-50 for high contrast headers & titles */
            --scalar-color-2: #cbd5e1 !important; /* slate-300 for readable body text & descriptions */
            --scalar-color-3: #94a3b8 !important; /* slate-400 for subtext & labels */
            --scalar-color-accent: #00a7f9 !important; /* APIs Hub Brand Blue */
            --scalar-background-1: #090d16 !important; /* deep slate-950 for backdrop */
            --scalar-background-2: #0f172a !important; /* slate-900 for sidebar & cards */
            --scalar-background-3: #1e293b !important; /* slate-800 for inputs & panels */
            --scalar-border-color: rgba(255, 255, 255, 0.12) !important;
            --scalar-button-1: #00a7f9 !important;
            --scalar-button-1-color: #ffffff !important;
        }

        /* Light Mode High-Contrast Overrides: target html:not(.dark), .light-mode, [data-theme='light'] */
        html:not(.dark),
        html:not(.dark) body,
        html:not(.dark) .scalar-container,
        html:not(.dark) .scalar-api-reference,
        html:not(.dark) .scalar-app,
        .light-mode {
            --scalar-color-1: #0f172a !important; /* slate-900 for high-contrast dark text */
            --scalar-color-2: #334155 !important; /* slate-700 for readable body text */
            --scalar-color-3: #64748b !important; /* slate-500 for labels */
            --scalar-color-accent: #0284c7 !important; /* Brand Blue */
            --scalar-background-1: #ffffff !important; /* pure white canvas */
            --scalar-background-2: #f8fafc !important; /* slate-50 for cards & sidebar */
            --scalar-background-3: #f1f5f9 !important; /* slate-100 for code blocks */
            --scalar-border-color: rgba(0, 0, 0, 0.1) !important;
            --scalar-button-1: #00a7f9 !important;
            --scalar-button-1-color: #ffffff !important;
        }

        /* Force high contrast font colors on markdown prose & description elements in Dark Mode */
        html.dark .scalar-api-reference,
        html.dark .scalar-app,
        .dark-mode {
            color: #cbd5e1 !important;
        }
        html.dark .scalar-api-reference p,
        html.dark .scalar-api-reference li,
        html.dark .scalar-api-reference td,
        html.dark .scalar-api-reference span:not([class*="bg-"]):not([class*="badge"]),
        html.dark .scalar-app p,
        html.dark .scalar-app li,
        html.dark .scalar-app td,
        .dark-mode p,
        .dark-mode li,
        .dark-mode td {
            color: #cbd5e1 !important;
        }
        html.dark .scalar-api-reference h1,
        html.dark .scalar-api-reference h2,
        html.dark .scalar-api-reference h3,
        html.dark .scalar-api-reference h4,
        html.dark .scalar-api-reference h5,
        html.dark .scalar-api-reference h6,
        html.dark .scalar-api-reference strong,
        html.dark .scalar-app h1,
        html.dark .scalar-app h2,
        html.dark .scalar-app h3,
        html.dark .scalar-app strong,
        .dark-mode h1,
        .dark-mode h2,
        .dark-mode h3,
        .dark-mode strong {
            color: #f8fafc !important;
        }

        /* Force crisp dark text colors on markdown prose & description elements in Light Mode */
        html:not(.dark) .scalar-api-reference,
        html:not(.dark) .scalar-app,
        .light-mode {
            color: #334155 !important;
        }
        html:not(.dark) .scalar-api-reference p,
        html:not(.dark) .scalar-api-reference li,
        html:not(.dark) .scalar-api-reference td,
        html:not(.dark) .scalar-api-reference span:not([class*="bg-"]):not([class*="badge"]),
        html:not(.dark) .scalar-app p,
        html:not(.dark) .scalar-app li,
        html:not(.dark) .scalar-app td,
        .light-mode p,
        .light-mode li,
        .light-mode td {
            color: #334155 !important;
        }
        html:not(.dark) .scalar-api-reference h1,
        html:not(.dark) .scalar-api-reference h2,
        html:not(.dark) .scalar-api-reference h3,
        html:not(.dark) .scalar-api-reference h4,
        html:not(.dark) .scalar-api-reference h5,
        html:not(.dark) .scalar-api-reference h6,
        html:not(.dark) .scalar-api-reference strong,
        html:not(.dark) .scalar-app h1,
        html:not(.dark) .scalar-app h2,
        html:not(.dark) .scalar-app h3,
        html:not(.dark) .scalar-app strong,
        .light-mode h1,
        .light-mode h2,
        .light-mode h3,
        .light-mode strong {
            color: #0f172a !important;
        }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col justify-between bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 selection:bg-brand-blue selection:text-white"
      x-data="themeControl">

    <!-- Unified Header Navigation -->
    @include('components.public.header')

    <!-- Main Content -->
    <main class="relative pt-28 sm:pt-36 pb-20 px-4 sm:px-8 lg:px-12 w-full flex-grow flex flex-col">
        
        <!-- Hero Header -->
        <div class="space-y-4 mb-8">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-brand-blue/10 text-brand-blue border border-brand-blue/20">
                <span class="w-2 h-2 rounded-full bg-brand-blue animate-pulse"></span>
                <span>{{ __('OpenAPI 3.1 Reference & Interactive Sandbox') }}</span>
            </div>
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                        {{ __('APIs Hub Developer Reference') }}
                    </h1>
                    <p class="text-slate-600 dark:text-slate-300 text-sm sm:text-base max-w-3xl mt-2 leading-relaxed">
                        {{ __('Direct programmatic access to normalized cross-channel advertising data, real-time sync telemetry, entity discovery, and high-speed multidimensional analytical aggregations.') }}
                    </p>
                </div>
                <!-- Spec Download Button -->
                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ $specUrl }}" target="_blank" download="{{ app()->getLocale() === 'es' ? 'apis-hub-openapi-es.json' : 'apis-hub-openapi.json' }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 hover:text-brand-blue hover:border-brand-blue/40 transition-all shadow-sm">
                        <svg class="w-4 h-4 text-brand-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>{{ __('Download OpenAPI JSON') }}</span>
                    </a>
                </div>
            </div>

            <!-- Topic Quick Navigation -->
            <div class="flex items-center gap-2 overflow-x-auto py-2 text-xs scrollbar-none border-b border-slate-200/80 dark:border-slate-800/80 text-slate-600 dark:text-slate-400">
                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ __('Key Topics:') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('🔐 Authentication') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('💓 System Health') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('🔄 Data Sync') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('🔍 Assets Discovery') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('📄 Pagination & Sorting') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('📊 Aggregations') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('🌐 Omnichannel') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('⏱ Rate Limits') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('⚠️ Error Handling') }}</span>
            </div>
        </div>

        <!-- Scalar UI Interactive Reference Container -->
        <div class="scalar-container border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-slate-900/60 backdrop-blur-xl shadow-2xl">
            <script
                id="api-reference"
                data-url="{{ $specUrl }}"
            ></script>
            <script>
                // Initialize Scalar configuration dynamically based on the active theme
                (function() {
                    const isDark = document.documentElement.classList.contains('dark');
                    const scriptTag = document.getElementById('api-reference');
                    
                    const highContrastCss = `
                        .dark-mode, .dark-mode .scalar-api-reference, .dark-mode .scalar-app {
                            --scalar-color-1: #f8fafc !important;
                            --scalar-color-2: #cbd5e1 !important;
                            --scalar-color-3: #94a3b8 !important;
                            --scalar-background-1: #090d16 !important;
                            --scalar-background-2: #0f172a !important;
                            --scalar-background-3: #1e293b !important;
                            --scalar-color-accent: #00a7f9 !important;
                        }
                        .dark-mode p, .dark-mode li, .dark-mode td, .dark-mode .markdown {
                            color: #cbd5e1 !important;
                        }
                        .dark-mode h1, .dark-mode h2, .dark-mode h3, .dark-mode h4, .dark-mode h5, .dark-mode strong {
                            color: #f8fafc !important;
                        }
                        .light-mode, .light-mode .scalar-api-reference, .light-mode .scalar-app {
                            --scalar-color-1: #0f172a !important;
                            --scalar-color-2: #334155 !important;
                            --scalar-color-3: #64748b !important;
                            --scalar-background-1: #ffffff !important;
                            --scalar-background-2: #f8fafc !important;
                            --scalar-background-3: #f1f5f9 !important;
                            --scalar-color-accent: #0284c7 !important;
                        }
                        .light-mode p, .light-mode li, .light-mode td, .light-mode .markdown {
                            color: #334155 !important;
                        }
                        .light-mode h1, .light-mode h2, .light-mode h3, .light-mode strong {
                            color: #0f172a !important;
                        }
                    `;

                    const locale = '{{ app()->getLocale() }}' === 'es' ? 'es' : 'en';

                    scriptTag.setAttribute('data-configuration', JSON.stringify({
                        theme: 'default',
                        darkMode: isDark,
                        layout: 'modern',
                        showSidebar: true,
                        hideModels: false,
                        customCss: highContrastCss,
                        locale: locale,
                        localization: {
                            locale: locale
                        },
                        defaultHttpClient: {
                            targetKey: 'shell',
                            clientKey: 'curl'
                        }
                    }));
                })();
            </script>
            <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference@latest"></script>
        </div>

        <script>
            // Synchronize Scalar color mode in real-time when user clicks the theme toggle in header
            document.addEventListener('DOMContentLoaded', () => {
                const syncThemeToScalar = () => {
                    const isDark = document.documentElement.classList.contains('dark');
                    const targets = document.querySelectorAll('.scalar-api-reference, .scalar-container, .scalar-app, [data-scalar-app]');
                    targets.forEach(el => {
                        if (isDark) {
                            el.classList.remove('light-mode');
                            el.classList.add('dark-mode');
                            el.setAttribute('data-theme', 'dark');
                        } else {
                            el.classList.remove('dark-mode');
                            el.classList.add('light-mode');
                            el.setAttribute('data-theme', 'light');
                        }
                    });

                    // Also check if Scalar has internal dark mode toggle button and trigger it if out of sync
                    const scalarThemeBtn = document.querySelector('.scalar-api-reference button[aria-label*="mode" i], .scalar-container button[aria-label*="mode" i]');
                    if (scalarThemeBtn) {
                        // Keep internal state aligned if applicable
                    }
                };

                const observer = new MutationObserver(syncThemeToScalar);
                observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

                // Also run periodic check during initial load to bind to dynamically rendered Scalar elements
                let attempts = 0;
                const interval = setInterval(() => {
                    syncThemeToScalar();
                    attempts++;
                    if (attempts > 15) clearInterval(interval);
                }, 300);
            });
        </script>

    </main>

    <!-- Semantic Footer -->
    @include('components.public.footer')

    <!-- Background Mesh -->
    <div class="hero-mesh" aria-hidden="true"></div>

    @vite(['resources/js/gtm.js'])
</body>
</html>

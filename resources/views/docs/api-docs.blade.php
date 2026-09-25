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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

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
</head>
<body class="antialiased min-h-screen flex flex-col justify-between bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 selection:bg-brand-blue selection:text-white"
      x-data="themeControl">

    <!-- Unified Header Navigation -->
    @include('components.public.header')

    <!-- Main Content -->
    <main class="relative pt-32 sm:pt-40 pb-20 px-6 sm:px-12 flex-grow flex flex-col items-center">
        <div class="w-full max-w-4xl space-y-8">
            
            <!-- Hero Title & Badge -->
            <div class="text-center space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-brand-blue/10 text-brand-blue border border-brand-blue/20">
                    <span class="w-2 h-2 rounded-full bg-brand-blue animate-pulse"></span>
                    <span>{{ __('OpenAPI 3.1 Specification & Interactive Portal') }}</span>
                </div>
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight">
                    {{ __('APIs Hub Developer Reference') }}
                </h1>
                <p class="text-slate-600 dark:text-slate-400 text-sm sm:text-base max-w-2xl mx-auto">
                    {{ __('Programmatic access to normalized cross-channel advertising data, real-time sync telemetry, and analytical reductions for BI tools and custom integrations.') }}
                </p>
            </div>

            <!-- Coming Soon / Portal Roadmap Card -->
            <div class="p-6 sm:p-8 rounded-2xl bg-white/70 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 backdrop-blur-xl shadow-xl space-y-6">
                <div class="flex items-start gap-4">
                    <div class="p-3 rounded-xl bg-brand-blue/10 text-brand-blue shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">
                            {{ __('Public Interactive Portal & Sandbox Under Active Development') }}
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                            {{ __('We are formalizing the complete OpenAPI 3.1 specification, browser try-it console, and downloadable Postman collections.') }}
                        </p>
                    </div>
                </div>

                <!-- Feature Grid Preview -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div class="p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 space-y-1.5">
                        <div class="flex items-center gap-2 font-semibold text-xs sm:text-sm text-slate-900 dark:text-slate-100">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            <span>{{ __('Analytical Aggregations') }}</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <code>POST /{channel}/metric/aggregate</code> {{ __('with multi-dimensional groupBy, relational filters, and weighted formulas.') }}
                        </p>
                    </div>

                    <div class="p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 space-y-1.5">
                        <div class="flex items-center gap-2 font-semibold text-xs sm:text-sm text-slate-900 dark:text-slate-100">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            <span>{{ __('Normalized Channel Metrics') }}</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <code>GET /{channel}/metric</code> {{ __('paginated time-series records across Google, Meta, Shopify, and more.') }}
                        </p>
                    </div>

                    <div class="p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 space-y-1.5">
                        <div class="flex items-center gap-2 font-semibold text-xs sm:text-sm text-slate-900 dark:text-slate-100">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            <span>{{ __('Heartbeat & Network Ping') }}</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <code>GET /api/v1/ping</code> {{ __('public connectivity and key validation healthcheck.') }}
                        </p>
                    </div>

                    <div class="p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 space-y-1.5">
                        <div class="flex items-center gap-2 font-semibold text-xs sm:text-sm text-slate-900 dark:text-slate-100">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            <span>{{ __('Interactive Sandbox') }}</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ __('Execute test queries directly against your dedicated node with dynamic key injection.') }}
                        </p>
                    </div>
                </div>

                <!-- CTA Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <p class="text-xs text-slate-500 dark:text-slate-400 text-center sm:text-left">
                        {{ __('Already have an account? You can test endpoints using your API key from the app console.') }}
                    </p>
                    <a href="/app" class="whitespace-nowrap px-5 py-2.5 text-xs sm:text-sm font-bold text-white bg-brand-blue rounded-xl hover:scale-105 active:scale-95 transition-all shadow-glow hover:shadow-glow-intense">
                        {{ __('Go to App Dashboard') }} &rarr;
                    </a>
                </div>
            </div>

        </div>
    </main>

    <!-- Semantic Footer -->
    @include('components.public.footer')

    <!-- Background Mesh -->
    <div class="hero-mesh" aria-hidden="true"></div>

    @vite(['resources/js/gtm.js'])
</body>
</html>

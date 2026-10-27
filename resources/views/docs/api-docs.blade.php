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

    <!-- Open Graph / Social Sharing -->
    <meta property="og:site_name" content="APIs Hub">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ app()->getLocale() === 'es' ? 'Documentación de API — APIs Hub' : 'API Documentation — APIs Hub' }}">
    <meta property="og:description" content="{{ app()->getLocale() === 'es' ? 'Especificación OpenAPI 3.1 y portal para desarrolladores de APIs Hub.' : 'OpenAPI 3.1 specification and developer documentation portal for APIs Hub.' }}">
    <meta property="og:image" content="{{ asset('images/branding/apishub-620.png') }}">
    <meta property="og:image:width" content="620">
    <meta property="og:image:height" content="135">
    <meta property="og:locale" content="{{ app()->getLocale() === 'es' ? 'es_ES' : 'en_US' }}">
    <meta property="og:locale:alternate" content="{{ app()->getLocale() === 'es' ? 'en_US' : 'es_ES' }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="{{ app()->getLocale() === 'es' ? 'Documentación de API — APIs Hub' : 'API Documentation — APIs Hub' }}">
    <meta name="twitter:description" content="{{ app()->getLocale() === 'es' ? 'Especificación OpenAPI 3.1 y portal para desarrolladores de APIs Hub.' : 'OpenAPI 3.1 specification and developer documentation portal for APIs Hub.' }}">
    <meta name="twitter:image" content="{{ asset('images/branding/apishub-620.png') }}">

    <!-- Structured Data -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "TechArticle",
      "headline": "{{ app()->getLocale() === 'es' ? 'Documentación de API y Especificación OpenAPI — APIs Hub' : 'API Documentation & OpenAPI Specification — APIs Hub' }}",
      "url": "{{ url()->current() }}",
      "inLanguage": "{{ app()->getLocale() }}",
      "description": "{{ app()->getLocale() === 'es' ? 'Referencia pública completa y sandbox interactivo para la API de APIs Hub. Consultas analíticas multidimensionales, agregaciones, pings y métricas normalizadas.' : 'Complete public developer reference and interactive sandbox for the APIs Hub RESTful API. Multi-dimensional analytical queries, aggregations, pings, and normalized channel metrics.' }}",
      "publisher": {
        "@@type": "Organization",
        "name": "APIs Hub Network",
        "url": "{{ url('/') }}",
        "logo": {
          "@@type": "ImageObject",
          "url": "{{ asset('images/branding/apishub-trans-620.webp') }}"
        }
      }
    }
    </script>

    @vite(['resources/js/theme.js', 'resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="{{ asset('css/scalar-theme.css') }}"/>
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
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('🔐 Authentication (Master & User Keys)') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('👥 Scoped RBAC') }}</span>
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
            <script src="{{ asset('js/scalar-docs.js') }}"></script>
            <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference@latest"></script>
        </div>

    </main>

    <!-- Semantic Footer -->
    @include('components.public.footer')

    <!-- Background Mesh -->
    <div class="hero-mesh" aria-hidden="true"></div>

    @vite(['resources/js/gtm.js'])
</body>
</html>

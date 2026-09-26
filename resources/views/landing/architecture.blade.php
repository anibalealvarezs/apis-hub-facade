<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        
        <!-- Preconnect / DNS Prefetch -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="dns-prefetch" href="https://fonts.gstatic.com">
        <link rel="preconnect" href="https://www.googletagmanager.com">
        <link rel="preconnect" href="https://www.google.com">
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

        <!-- Primary Meta Tags -->
        <title>{{ app()->getLocale() === 'es' ? 'Arquitectura de Datos Aislada | APIs Hub' : 'Isolated Data Cluster Architecture | APIs Hub' }}</title>
        <meta name="title" content="{{ app()->getLocale() === 'es' ? 'Arquitectura de Datos Aislada | APIs Hub' : 'Isolated Data Cluster Architecture | APIs Hub' }}">
        <meta name="description" content="{{ app()->getLocale() === 'es' ? 'Descubre cómo la arquitectura de clusters dedicados por proyecto en APIs Hub elimina caídas de cuota en Looker Studio y tiempos de espera en reportes de marketing.' : 'Learn how APIs Hub dedicated project data clusters eliminate Looker Studio API quota ceilings, connector timeouts, and multi-tenant lag.' }}">
        <meta name="keywords" content="{{ __('marketing data architecture, isolated tenant cluster, looker studio quota fix, automated scheduled syncing, marketing OLAP engine, APIs Hub') }}">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <meta name="theme-color" content="#0f172a">
        <meta name="color-scheme" content="dark light">

        <!-- Favicons -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/apishub-favicon.png') }}">
        <link rel="icon" type="image/webp" href="{{ asset('images/branding/apishub-favicon.webp') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/branding/apishub-favicon.png') }}">

        <!-- Canonical & Hreflang -->
        <link rel="canonical" href="{{ app()->getLocale() === 'es' ? route('landing.architecture.es') : route('landing.architecture') }}" />
        <link rel="alternate" hreflang="en" href="{{ route('landing.architecture') }}" />
        <link rel="alternate" hreflang="es" href="{{ route('landing.architecture.es') }}" />
        <link rel="alternate" hreflang="x-default" href="{{ route('landing.architecture') }}" />

        <!-- Open Graph -->
        <meta property="og:site_name" content="APIs Hub">
        <meta property="og:type" content="article">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ app()->getLocale() === 'es' ? 'Arquitectura de Datos Aislada | APIs Hub' : 'Isolated Data Cluster Architecture | APIs Hub' }}">
        <meta property="og:description" content="{{ app()->getLocale() === 'es' ? 'Sincronización diaria persistente y clusters independientes por cliente para analítica de marketing sin caídas.' : 'Persistent daily synchronization and isolated client clusters for unbreakable marketing analytics.' }}">
        <meta property="og:image" content="{{ asset('images/branding/apishub-620.png') }}">

        @vite(['resources/js/theme.js', 'resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased min-h-screen text-slate-900 dark:text-slate-100 selection:bg-brand-blue selection:text-white bg-slate-50 dark:bg-slate-900" x-data="themeControl">
        
        <!-- Navigation Header -->
        @include('components.public.header')

        <main class="relative pt-32 sm:pt-40 pb-24 px-4 sm:px-8 max-w-5xl mx-auto flex flex-col items-center">
            
            <!-- Breadcrumbs / Kicker -->
            <div class="w-full flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-blue mb-4">
                <a href="{{ app()->getLocale() === 'es' ? '/es' : '/' }}" class="hover:underline">{{ __('Home') }}</a>
                <span>/</span>
                <span>{{ __('Architecture') }}</span>
            </div>

            <!-- Page Hero -->
            <header class="text-left w-full mb-12 sm:mb-16">
                <h1 class="public-hero-title !text-left !text-3xl sm:!text-5xl mb-6">
                    {{ app()->getLocale() === 'es' ? 'Por qué los conectores directos fallan (y cómo lo resolvemos)' : 'Why Direct Connectors Fail (And How Isolated Clusters Fix Them)' }}
                </h1>
                <p class="public-hero-subtitle !text-left !text-lg sm:!text-xl text-slate-600 dark:text-slate-300 font-normal">
                    {{ app()->getLocale() === 'es'
                        ? 'La mayoría de herramientas de visualización consultan las plataformas de publicidad en tiempo real cada vez que abres un reporte. APIs Hub reemplaza esa fragilidad con un motor persistente de clusters aislados.'
                        : 'Most marketing dashboards query advertising APIs on the fly every time a report loads. APIs Hub replaces that fragility with dedicated, pre-synced client data clusters.' }}
                </p>
            </header>

            <!-- Contrast Visual: Direct Query vs APIs Hub -->
            <section class="w-full grid grid-cols-1 md:grid-cols-2 gap-6 mb-16" aria-label="Architecture Comparison">
                <!-- The Traditional Fragile Way -->
                <div class="p-6 sm:p-8 rounded-2xl glass-panel bg-red-500/5 border border-red-500/20 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-red-600 dark:text-red-400 mb-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                            {{ app()->getLocale() === 'es' ? 'El Modelo Tradicional (Conector en Vivo)' : 'The Traditional Way (Live Connectors)' }}
                        </div>
                        <h2 class="text-xl font-bold mb-4 text-slate-900 dark:text-white">
                            {{ app()->getLocale() === 'es' ? 'Consulta directa en tiempo real' : 'On-the-Fly API Requests' }}
                        </h2>
                        <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 font-bold shrink-0">✕</span>
                                <span>{{ app()->getLocale() === 'es' ? 'Cada vez que un cliente abre el dashboard, se disparan docenas de llamadas a Meta o Google.' : 'Every dashboard view triggers dozens of live calls to Meta and Google APIs.' }}</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 font-bold shrink-0">✕</span>
                                <span>{{ app()->getLocale() === 'es' ? 'Al consultar rangos de 90 o 180 días, la API agota la cuota y el reporte se congela.' : 'Selecting a 90+ day date range exhausts quota limits and crashes the visualization.' }}</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 font-bold shrink-0">✕</span>
                                <span>{{ app()->getLocale() === 'es' ? 'Si Meta cambia un campo o tiene latencia, el cliente ve un error crítico.' : 'Ad platform API changes or latency spikes directly break client reports.' }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="mt-6 p-3 rounded-xl bg-red-500/10 text-xs font-mono text-red-600 dark:text-red-400">
                        {{ app()->getLocale() === 'es' ? 'Resultado: Dashboards lentos, errores inesperados y llamadas urgentes de clientes.' : 'Result: Slow dashboards, unexpected quota errors, and client panic.' }}
                    </div>
                </div>

                <!-- The APIs Hub Isolated Cluster Way -->
                <div class="p-6 sm:p-8 rounded-2xl glass-panel bg-brand-blue/5 border border-brand-blue/30 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-brand-blue dark:text-brand-blue-400 mb-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-brand-blue"></span>
                            {{ app()->getLocale() === 'es' ? 'La Arquitectura APIs Hub (Cluster Aislado)' : 'The APIs Hub Way (Isolated Clusters)' }}
                        </div>
                        <h2 class="text-xl font-bold mb-4 text-slate-900 dark:text-white">
                            {{ app()->getLocale() === 'es' ? 'Sincronización diaria con persistencia dedicada' : 'Scheduled Sync with Local OLAP Persistence' }}
                        </h2>
                        <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                            <li class="flex items-start gap-2">
                                <span class="text-emerald-500 font-bold shrink-0">✓</span>
                                <span>{{ app()->getLocale() === 'es' ? 'Sincronización en segundo plano con control de límites de frecuencia (rate limits).' : 'Automated background syncing handles ad network rate limits gracefully.' }}</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-emerald-500 font-bold shrink-0">✓</span>
                                <span>{{ app()->getLocale() === 'es' ? 'Los datos se normalizan en una base de datos local dedicada por cada proyecto.' : 'Data is stored in a dedicated, high-speed local database per project.' }}</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-emerald-500 font-bold shrink-0">✓</span>
                                <span>{{ app()->getLocale() === 'es' ? 'Los dashboards responden en milisegundos sin consumir cuotas de las plataformas.' : 'Client dashboards load in milliseconds without exhausting external API quotas.' }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="mt-6 p-3 rounded-xl bg-brand-blue/10 text-xs font-mono text-brand-blue dark:text-brand-blue-400">
                        {{ app()->getLocale() === 'es' ? 'Resultado: Cero caídas, carga instantánea y total soberanía sobre los datos.' : 'Result: Zero quota crashes, instant loading, and complete data ownership.' }}
                    </div>
                </div>
            </section>

            <!-- The 3 Pillars of the Cluster Architecture -->
            <section class="w-full mb-16">
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-8 text-left">
                    {{ app()->getLocale() === 'es' ? 'Los 3 pilares de la arquitectura de APIs Hub' : 'The Three Pillars of the APIs Hub Cluster' }}
                </h2>

                <div class="space-y-6">
                    <!-- Pillar 1 -->
                    <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="w-8 h-8 rounded-lg bg-brand-blue/10 text-brand-blue font-bold flex items-center justify-center text-sm">01</span>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                {{ app()->getLocale() === 'es' ? 'Aislamiento Estricto por Proyecto (Zero Noisy-Neighbors)' : 'Dedicated Tenant Isolation (Zero Noisy Neighbors)' }}
                            </h3>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed pl-11">
                            {{ app()->getLocale() === 'es'
                                ? 'En lugar de compartir una única base de datos masiva donde las consultas pesadas de otros usuarios ralentizan tus reportes, cada proyecto en APIs Hub opera en su propio contenedor y base de datos. Los datos de tus clientes están completamente aislados tanto a nivel de rendimiento como de seguridad.'
                                : 'Instead of sharing a monolithic database where heavy queries from other companies slow down your dashboards, each project in APIs Hub runs on its own isolated data container and database. Client data remains strictly separated in performance and governance.' }}
                        </p>
                    </div>

                    <!-- Pillar 2 -->
                    <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="w-8 h-8 rounded-lg bg-brand-blue/10 text-brand-blue font-bold flex items-center justify-center text-sm">02</span>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                {{ app()->getLocale() === 'es' ? 'Diccionario de Equivalencias y Normalización Agnóstica' : 'Agnostic Metric Normalization Engine' }}
                            </h3>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed pl-11">
                            {{ app()->getLocale() === 'es'
                                ? 'Los datos recolectados no se guardan en crudo. Se procesan a través de drivers especializados que mapean las particularidades de Meta, Google, Shopify y Klaviyo a un diccionario común de equivalencias. Cuando consultas "gasto", "clics" o "conversiones", recibes métricas comparables sin tener que crear fórmulas en hojas de cálculo.'
                                : 'Collected metrics are never dumped as raw fragments. Specialized driver layers map disparate platform schemas into a unified dictionary of equivalences. When querying spend, clicks, or conversions, you receive standardized metrics ready for cross-channel comparison without custom spreadsheet formulas.' }}
                        </p>
                    </div>

                    <!-- Pillar 3 -->
                    <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="w-8 h-8 rounded-lg bg-brand-blue/10 text-brand-blue font-bold flex items-center justify-center text-sm">03</span>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                {{ app()->getLocale() === 'es' ? 'Doble Interfaz: Dashboards Interactivos y Servidor MCP para IA' : 'Dual Consumption: Visual Dashboards & Deterministic MCP' }}
                            </h3>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed pl-11">
                            {{ app()->getLocale() === 'es'
                                ? 'El cluster persistente alimenta tanto a los dashboards interactivos del equipo como a un servidor nativo de Model Context Protocol (MCP). Los asistentes de IA (Claude, Cursor) pueden auditar el rendimiento y hacer diagnósticos profundos consultando matemática normalizada y verificada, sin inventar números.'
                                : 'The persistent cluster serves both human-facing executive dashboards and an industrial Model Context Protocol (MCP) server. AI assistants (Claude, Cursor) can diagnose campaign performance using mathematically verified ground truth instead of hallucinating raw exports.' }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Bottom CTA -->
            <section class="w-full text-center p-8 sm:p-12 rounded-3xl glass-panel bg-gradient-to-b from-white/60 to-white/30 dark:from-slate-900/60 dark:to-slate-900/30 border border-slate-200 dark:border-slate-800">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mb-4">
                    {{ app()->getLocale() === 'es' ? 'Comprueba la velocidad de un cluster aislado' : 'Experience the Speed of Dedicated Data Clusters' }}
                </h2>
                <p class="text-base text-slate-600 dark:text-slate-300 max-w-xl mx-auto mb-6">
                    {{ app()->getLocale() === 'es'
                        ? 'Crea tu primer proyecto gratis y conecta tus fuentes en minutos. Cero tarjetas de crédito requeridas.'
                        : 'Launch your first project workspace for free. Connect your channels in minutes with zero credit card required.' }}
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <span data-portal="{{ $portals['app'] }}" class="js-portal-link px-8 py-3 text-sm font-bold text-white bg-brand-blue rounded-xl hover:scale-105 active:scale-95 transition-all shadow-glow hover:shadow-glow-intense cursor-pointer">
                        {{ __('Try beta for free') }}
                    </span>
                    <a href="{{ app()->getLocale() === 'es' ? route('landing.plans.es') : route('landing.plans') }}" class="px-6 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:text-brand-blue transition-colors">
                        {{ __('View Plans & Features') }} &rarr;
                    </a>
                </div>
            </section>

        </main>

        <!-- Footer -->
        @include('components.public.footer')

        <!-- Background Mesh -->
        <div class="hero-mesh" aria-hidden="true"></div>
    </body>
</html>

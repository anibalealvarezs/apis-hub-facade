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
        <title>{{ app()->getLocale() === 'es' ? 'Cómo Solucionar el Error de Cuota en Looker Studio con Google Search Console | APIs Hub' : 'How to Fix Looker Studio Google Search Console Quota Limit Errors | APIs Hub' }}</title>
        <meta name="title" content="{{ app()->getLocale() === 'es' ? 'Cómo Solucionar el Error de Cuota en Looker Studio con Google Search Console | APIs Hub' : 'How to Fix Looker Studio Google Search Console Quota Limit Errors | APIs Hub' }}">
        <meta name="description" content="{{ app()->getLocale() === 'es' ? 'Guía técnica para resolver definitivamente el error de cuota agotada de Google Search Console y GA4 en Looker Studio mediante un cluster de datos con persistencia local.' : 'Step-by-step technical guide to permanently eliminate Google Search Console and GA4 API quota exceeded errors in Looker Studio using local data clustering.' }}">
        <meta name="keywords" content="{{ __('looker studio google search console quota exceeded, looker studio quota limit fix, gsc api quota error, google analytics 4 quota exceeded, marketing data caching, APIs Hub') }}">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <meta name="theme-color" content="#0f172a">

        <!-- Favicons -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/apishub-favicon.png') }}">
        <link rel="icon" type="image/webp" href="{{ asset('images/branding/apishub-favicon.webp') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/branding/apishub-favicon.png') }}">

        <!-- Canonical & Hreflang -->
        <link rel="canonical" href="{{ app()->getLocale() === 'es' ? route('landing.guides.looker-quota.es') : route('landing.guides.looker-quota') }}" />
        <link rel="alternate" hreflang="en" href="{{ route('landing.guides.looker-quota') }}" />
        <link rel="alternate" hreflang="es" href="{{ route('landing.guides.looker-quota.es') }}" />
        <link rel="alternate" hreflang="x-default" href="{{ route('landing.guides.looker-quota') }}" />

        <!-- Open Graph -->
        <meta property="og:site_name" content="APIs Hub">
        <meta property="og:type" content="article">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ app()->getLocale() === 'es' ? 'Solución Definitiva al Error de Cuota de Google Search Console en Looker Studio' : 'Permanently Fix Looker Studio GSC Quota Errors' }}">
        <meta property="og:description" content="{{ app()->getLocale() === 'es' ? 'Aprende por qué ocurre este error y cómo eliminarlo para siempre.' : 'Understand why live connectors exhaust API tokens and how local caching solves it.' }}">
        <meta property="og:image" content="{{ asset('images/branding/apishub-620.png') }}">

        @vite(['resources/js/theme.js', 'resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased min-h-screen text-slate-900 dark:text-slate-100 selection:bg-brand-blue selection:text-white bg-slate-50 dark:bg-slate-900" x-data="themeControl">
        
        <!-- Navigation Header -->
        @include('components.public.header')

        <main class="relative pt-32 sm:pt-40 pb-24 px-4 sm:px-8 max-w-4xl mx-auto flex flex-col items-center">
            
            <!-- Breadcrumbs / Kicker -->
            <div class="w-full flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-blue mb-4">
                <a href="{{ app()->getLocale() === 'es' ? '/es' : '/' }}" class="hover:underline">{{ __('Home') }}</a>
                <span>/</span>
                <span class="text-slate-400">{{ __('Guides') }}</span>
                <span>/</span>
                <span>{{ __('Looker Quota Fix') }}</span>
            </div>

            <!-- Page Title -->
            <header class="text-left w-full mb-10">
                <h1 class="public-hero-title !text-left !text-3xl sm:!text-4xl mb-4">
                    {{ app()->getLocale() === 'es' ? 'Cómo solucionar definitivamente el error de cuota de Google Search Console en Looker Studio' : 'How to Permanently Fix Looker Studio Google Search Console Quota Errors' }}
                </h1>
                <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 font-normal leading-relaxed">
                    {{ app()->getLocale() === 'es'
                        ? 'Si gestionas dashboards para clientes en Looker Studio, es casi seguro que has visto el temido mensaje de error: "La solicitud no se pudo completar porque se ha alcanzado la cuota de la API". Aquí te explicamos por qué sucede y cómo erradicarlo.'
                        : 'If you manage client reporting dashboards in Looker Studio, you have almost certainly encountered the dreaded error: "This chart cannot be displayed because the underlying API quota has been exceeded". Here is why it happens and how to fix it permanently.' }}
                </p>
            </header>

            <!-- Error Box Visualization -->
            <div class="w-full p-4 mb-10 rounded-xl bg-red-500/10 border border-red-500/30 text-red-600 dark:text-red-400 font-mono text-xs sm:text-sm">
                <strong>Looker Studio System Error:</strong> Looker Studio cannot connect to your dataset. Quota exceeded: Google Search Console API daily/hourly tokens exhausted.
            </div>

            <!-- Content Body -->
            <article class="w-full text-left space-y-8 text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mb-3">
                        {{ app()->getLocale() === 'es' ? '1. ¿Por qué ocurre este error?' : '1. Why Does This Quota Error Happen?' }}
                    </h2>
                    <p>
                        {{ app()->getLocale() === 'es'
                            ? 'Google impone límites estrictos de tokens por hora y por día a la API de Search Console. Los conectores nativos y de terceros tradicionales ejecutan consultas "en vivo" cada vez que:'
                            : 'Google enforces strict hourly and daily token budgets on the Search Console API. Traditional live connectors execute queries in real time whenever:' }}
                    </p>
                    <ul class="list-disc pl-6 space-y-2 mt-2">
                        <li>{{ app()->getLocale() === 'es' ? 'Un cliente o miembro del equipo abre o refresca el reporte.' : 'A client or team member opens or refreshes the report.' }}</li>
                        <li>{{ app()->getLocale() === 'es' ? 'Se aplica un filtro de fechas largo (por ejemplo, comparar los últimos 6 meses).' : 'A long date filter is applied (e.g., comparing 6 months of performance).' }}</li>
                        <li>{{ app()->getLocale() === 'es' ? 'Hay múltiples gráficos en una misma página consultando dimensiones desglosadas (queries, páginas, países).' : 'Multiple charts on a single page query granular breakdowns simultaneously (queries, URLs, devices).' }}</li>
                    </ul>
                    <p class="mt-3">
                        {{ app()->getLocale() === 'es'
                            ? 'Cada gráfico consume tokens. Con 5 o 6 widgets en una página y un par de visualizaciones simultáneas, la cuota de la cuenta se agota en minutos.'
                            : 'Each chart consumes quota tokens. With 5 or 6 widgets on a page and a couple of concurrent viewers, your token bucket is drained in minutes.' }}
                    </p>
                </div>

                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mb-3">
                        {{ app()->getLocale() === 'es' ? '2. Por qué las soluciones temporales no funcionan' : '2. Why Band-Aid Fixes Fail' }}
                    </h2>
                    <p>
                        {{ app()->getLocale() === 'es'
                            ? 'La recomendación habitual de Google es "esperar a que se renueve la cuota por hora" o "reducir el número de gráficos". Para una agencia con clientes de pago, decirle a un cliente que espere o entregarle un reporte incompleto no es una opción aceptable.'
                            : 'Google standard advice is to "wait for the hourly quota to reset" or "remove charts from your report". For an agency with paying clients, telling a client to wait or stripping charts from their dashboard is simply not acceptable.' }}
                    </p>
                </div>

                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mb-3">
                        {{ app()->getLocale() === 'es' ? '3. La solución arquitectónica: Desacoplar la consulta de la fuente' : '3. The Architectural Fix: Decouple Dashboard from Source' }}
                    </h2>
                    <p>
                        {{ app()->getLocale() === 'es'
                            ? 'La única forma definitiva de eliminar el problema es no consultar la API de Google cada vez que alguien mira un reporte. En su lugar, se implementa una capa intermedia de sincronización programada:'
                            : 'The only permanent solution is to stop querying Google API on the fly whenever a dashboard is viewed. Instead, introduce a scheduled synchronization layer:' }}
                    </p>
                    <ol class="list-decimal pl-6 space-y-2 mt-2">
                        <li>
                            <strong class="text-slate-900 dark:text-white">{{ app()->getLocale() === 'es' ? 'Sincronización en segundo plano:' : 'Scheduled background sync:' }}</strong>
                            {{ app()->getLocale() === 'es' ? ' APIs Hub extrae los datos de Google Search Console una vez al día de forma controlada, respetando las cuotas sin agotarlas jamás.' : ' APIs Hub pulls GSC metrics on a controlled daily schedule, safely within rate limits.' }}
                        </li>
                        <li>
                            <strong class="text-slate-900 dark:text-white">{{ app()->getLocale() === 'es' ? 'Persistencia local por cliente:' : 'Isolated local storage:' }}</strong>
                            {{ app()->getLocale() === 'es' ? ' Los datos se almacenan en un cluster dedicado con índices optimizados.' : ' Data is indexed and persisted in a dedicated project database.' }}
                        </li>
                        <li>
                            <strong class="text-slate-900 dark:text-white">{{ app()->getLocale() === 'es' ? 'Dashboards instantáneos:' : 'Instant zero-token views:' }}</strong>
                            {{ app()->getLocale() === 'es' ? ' Tus clientes pueden abrir el reporte 1,000 veces al día o consultar años de histórico: la carga es instantánea y no consume ni un solo token de Google.' : ' Clients can view reports 1,000 times a day over multi-year ranges: loading is instant and zero Google quota tokens are used.' }}
                        </li>
                    </ol>
                </div>
            </article>

            <!-- Bottom CTA -->
            <section class="w-full text-center mt-12 p-8 sm:p-12 rounded-3xl glass-panel bg-gradient-to-b from-white/60 to-white/30 dark:from-slate-900/60 dark:to-slate-900/30 border border-slate-200 dark:border-slate-800">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mb-4">
                    {{ app()->getLocale() === 'es' ? 'Elimina los errores de cuota en tu próximo reporte' : 'Eliminate Quota Ceilings in Your Client Reports' }}
                </h2>
                <p class="text-base text-slate-600 dark:text-slate-300 max-w-xl mx-auto mb-6">
                    {{ app()->getLocale() === 'es'
                        ? 'Conecta tu Search Console a APIs Hub gratis y disfruta de dashboards rápidos, confiables y sin caídas.'
                        : 'Connect your Search Console to APIs Hub for free and experience fast, unbreakable reporting.' }}
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <span data-portal="{{ $portals['app'] }}" class="js-portal-link px-8 py-3 text-sm font-bold text-white bg-brand-blue rounded-xl hover:scale-105 active:scale-95 transition-all shadow-glow hover:shadow-glow-intense cursor-pointer">
                        {{ __('Try beta for free') }}
                    </span>
                    <a href="{{ app()->getLocale() === 'es' ? route('landing.architecture.es') : route('landing.architecture') }}" class="px-6 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:text-brand-blue transition-colors">
                        {{ app()->getLocale() === 'es' ? 'Ver cómo funciona la arquitectura' : 'Explore the Architecture' }} &rarr;
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

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
        <title>{{ app()->getLocale() === 'es' ? 'Normalización de Métricas Multi-Canal | APIs Hub' : 'Cross-Channel Marketing Metric Normalization | APIs Hub' }}</title>
        <meta name="title" content="{{ app()->getLocale() === 'es' ? 'Normalización de Métricas Multi-Canal | APIs Hub' : 'Cross-Channel Marketing Metric Normalization | APIs Hub' }}">
        <meta name="description" content="{{ app()->getLocale() === 'es' ? 'Reconcilia Meta, Google, Shopify y Klaviyo con un diccionario de equivalencias común. Calcula Blended ROAS y MER real sin fórmulas complejas de hojas de cálculo.' : 'Reconcile Meta, Google, Shopify, and Klaviyo with a shared metric dictionary. Calculate true Blended ROAS and MER without spreadsheet formula chaos.' }}">
        <meta name="keywords" content="{{ __('cross channel marketing data, blended roas formula, marketing efficiency ratio mer, metric normalization dictionary, reconcile meta and google ads, APIs Hub') }}">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <meta name="theme-color" content="#0f172a">

        <!-- Favicons -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/apishub-favicon.png') }}">
        <link rel="icon" type="image/webp" href="{{ asset('images/branding/apishub-favicon.webp') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/branding/apishub-favicon.png') }}">

        <!-- Canonical & Hreflang -->
        <link rel="canonical" href="{{ app()->getLocale() === 'es' ? route('landing.solutions.normalization.es') : route('landing.solutions.normalization') }}" />
        <link rel="alternate" hreflang="en" href="{{ route('landing.solutions.normalization') }}" />
        <link rel="alternate" hreflang="es" href="{{ route('landing.solutions.normalization.es') }}" />
        <link rel="alternate" hreflang="x-default" href="{{ route('landing.solutions.normalization') }}" />

        <!-- Open Graph -->
        <meta property="og:site_name" content="APIs Hub">
        <meta property="og:type" content="article">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ app()->getLocale() === 'es' ? 'Normalización de Métricas Cross-Channel | APIs Hub' : 'Cross-Channel Metric Normalization Engine | APIs Hub' }}">
        <meta property="og:description" content="{{ app()->getLocale() === 'es' ? 'Unifica clics, ventas y gasto entre Meta, Google y Shopify en un solo lenguaje.' : 'Unify clicks, revenue, and ad spend across Meta, Google, and Shopify into one language.' }}">
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
                <span class="text-slate-400">{{ __('Solutions') }}</span>
                <span>/</span>
                <span>{{ app()->getLocale() === 'es' ? 'Normalización' : 'Normalization' }}</span>
            </div>

            <!-- Page Hero -->
            <header class="text-left w-full mb-12 sm:mb-16">
                <h1 class="public-hero-title !text-left !text-3xl sm:!text-5xl mb-6">
                    {{ app()->getLocale() === 'es' ? 'Cada plataforma habla un dialecto. APIs Hub las reconcilia.' : 'Every Platform Speaks a Dialect. APIs Hub Reconciles Them.' }}
                </h1>
                <p class="public-hero-subtitle !text-left !text-lg sm:!text-xl text-slate-600 dark:text-slate-300 font-normal">
                    {{ app()->getLocale() === 'es'
                        ? 'Meta mide clics de 7 días. GA4 mide último clic no directo. Shopify registra ventas netas de órdenes completadas. APIs Hub aplica un diccionario de equivalencias para comparar peras con peras.'
                        : 'Meta tracks 7-day click attribution. GA4 attributes on last non-direct touch. Shopify records net order revenue. APIs Hub maps them to a shared dictionary of equivalences so your numbers actually reconcile.' }}
                </p>
            </header>

            <!-- Discrepancy Breakdown Table -->
            <section class="w-full mb-16" aria-label="Metric Reconciliation Breakdown">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mb-6 text-left">
                    {{ app()->getLocale() === 'es' ? 'El problema de la atribución fragmentada' : 'The Fragmented Attribution Problem' }}
                </h2>

                <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-100 dark:bg-slate-800/80 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-4">{{ app()->getLocale() === 'es' ? 'Canal' : 'Channel' }}</th>
                                <th class="p-4">{{ app()->getLocale() === 'es' ? 'Cómo define "Conversión / Venta"' : 'How It Defines "Sale / Conversion"' }}</th>
                                <th class="p-4">{{ app()->getLocale() === 'es' ? 'El Conflicto Típico' : 'The Typical Conflict' }}</th>
                                <th class="p-4">{{ app()->getLocale() === 'es' ? 'Solución APIs Hub' : 'APIs Hub Solution' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800/80 bg-white/40 dark:bg-slate-900/40">
                            <tr>
                                <td class="p-4 font-bold text-slate-900 dark:text-white">Meta Ads</td>
                                <td class="p-4 text-slate-600 dark:text-slate-300">Ventana de atribución de 7 días clic / 1 día vista.</td>
                                <td class="p-4 text-red-500 font-medium">Reclama ventas que también reclama Google.</td>
                                <td class="p-4 text-emerald-500 font-semibold" rowspan="3">
                                    Normalización a <strong>Blended ROAS</strong> y <strong>MER (Marketing Efficiency Ratio)</strong> usando ingresos reales auditados.
                                </td>
                            </tr>
                            <tr>
                                <td class="p-4 font-bold text-slate-900 dark:text-white">Google Ads</td>
                                <td class="p-4 text-slate-600 dark:text-slate-300">Atribución basada en datos (DDA) o último clic.</td>
                                <td class="p-4 text-red-500 font-medium">Ignora las interacciones tempranas en redes.</td>
                            </tr>
                            <tr>
                                <td class="p-4 font-bold text-slate-900 dark:text-white">Shopify</td>
                                <td class="p-4 text-slate-600 dark:text-slate-300">Transacciones completas procesadas en checkout.</td>
                                <td class="p-4 text-red-500 font-medium">La suma de Meta + Google supera el total real.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- How Normalization Works -->
            <section class="w-full mb-16 space-y-6">
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-4 text-left">
                    {{ app()->getLocale() === 'es' ? 'Cómo funciona el diccionario de equivalencias' : 'How the Normalization Engine Works' }}
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80">
                        <div class="w-8 h-8 rounded-lg bg-brand-blue/10 text-brand-blue font-bold flex items-center justify-center text-sm mb-3">01</div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">
                            {{ app()->getLocale() === 'es' ? 'Extracción por Drivers Especializados' : 'Dedicated Channel Drivers' }}
                        </h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            {{ app()->getLocale() === 'es'
                                ? 'Cada canal (Google Search Console, Google Analytics 4, Meta Ads, Shopify) cuenta con su propio driver que comprende los matices y alcances del proveedor sin mezclar conceptos.'
                                : 'Each channel uses a specialized driver that understands platform nuances, dimensional scopes, and token boundaries without cross-polluting concepts.' }}
                        </p>
                    </div>

                    <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80">
                        <div class="w-8 h-8 rounded-lg bg-brand-blue/10 text-brand-blue font-bold flex items-center justify-center text-sm mb-3">02</div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">
                            {{ app()->getLocale() === 'es' ? 'Mapeo a Entidades Canónicas' : 'Canonical Metric Mapping' }}
                        </h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            {{ app()->getLocale() === 'es'
                                ? 'Los datos se transforman a nombres canónicos (gasto, impresiones, clics, conversiones, ingresos) con definiciones estrictas. Puedes sumar gasto de Meta + Google con total seguridad.'
                                : 'Raw platform responses map to canonical entities (spend, impressions, clicks, conversions, revenue) with strict mathematical rules for cross-channel summation.' }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Bottom CTA -->
            <section class="w-full text-center p-8 sm:p-12 rounded-3xl glass-panel bg-gradient-to-b from-white/60 to-white/30 dark:from-slate-900/60 dark:to-slate-900/30 border border-slate-200 dark:border-slate-800">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mb-4">
                    {{ app()->getLocale() === 'es' ? 'Unifica tus canales en un solo lenguaje' : 'Reconcile Your Channels into One Language' }}
                </h2>
                <p class="text-base text-slate-600 dark:text-slate-300 max-w-xl mx-auto mb-6">
                    {{ app()->getLocale() === 'es'
                        ? 'Prueba el motor de normalización de APIs Hub en tu primer proyecto sin costo alguno.'
                        : 'Experience automated cross-channel reconciliation on your first project for free.' }}
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <span data-portal="{{ $portals['app'] }}" class="js-portal-link px-8 py-3 text-sm font-bold text-white bg-brand-blue rounded-xl hover:scale-105 active:scale-95 transition-all shadow-glow hover:shadow-glow-intense cursor-pointer">
                        {{ __('Try beta for free') }}
                    </span>
                    <a href="{{ app()->getLocale() === 'es' ? route('landing.architecture.es') : route('landing.architecture') }}" class="px-6 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:text-brand-blue transition-colors">
                        {{ app()->getLocale() === 'es' ? 'Ver detalles de arquitectura' : 'Explore Architecture' }} &rarr;
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

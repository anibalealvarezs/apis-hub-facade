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
        <title>{{ app()->getLocale() === 'es' ? 'Soluciones para Agencias: Reportes Multi-Cliente Sin Caídas | APIs Hub' : 'Agency Solutions: Multi-Client Reporting Without Timeouts | APIs Hub' }}</title>
        <meta name="title" content="{{ app()->getLocale() === 'es' ? 'Soluciones para Agencias: Reportes Multi-Cliente Sin Caídas | APIs Hub' : 'Agency Solutions: Multi-Client Reporting Without Timeouts | APIs Hub' }}">
        <meta name="description" content="{{ app()->getLocale() === 'es' ? 'Gestiona decenas de clientes con clusters aislados, separación entre proyectos y facturación, y reportes ejecutivos automatizados sin errores de cuota.' : 'Manage dozens of client portfolios with isolated data clusters, project vs billing separation, and zero connector timeouts.' }}">
        <meta name="keywords" content="{{ __('agency client reporting, multi client marketing dashboard, agency billing profile, white label marketing reports, client data isolation, APIs Hub') }}">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <meta name="theme-color" content="#0f172a">

        <!-- Favicons -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/apishub-favicon.png') }}">
        <link rel="icon" type="image/webp" href="{{ asset('images/branding/apishub-favicon.webp') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/branding/apishub-favicon.png') }}">

        <!-- Canonical & Hreflang -->
        <link rel="canonical" href="{{ app()->getLocale() === 'es' ? route('landing.solutions.agency.es') : route('landing.solutions.agency') }}" />
        <link rel="alternate" hreflang="en" href="{{ route('landing.solutions.agency') }}" />
        <link rel="alternate" hreflang="es" href="{{ route('landing.solutions.agency.es') }}" />
        <link rel="alternate" hreflang="x-default" href="{{ route('landing.solutions.agency') }}" />

        <!-- Open Graph -->
        <meta property="og:site_name" content="APIs Hub">
        <meta property="og:type" content="article">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ app()->getLocale() === 'es' ? 'Reportes Multi-Cliente para Agencias de Marketing | APIs Hub' : 'Multi-Client Reporting Engine for Marketing Agencies | APIs Hub' }}">
        <meta property="og:description" content="{{ app()->getLocale() === 'es' ? 'Aislamiento de clientes, perfiles de facturación independientes y dashboards instantáneos.' : 'Client data isolation, independent billing profiles, and instant client dashboards.' }}">
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
                <span>{{ __('For Agencies') }}</span>
            </div>

            <!-- Hero -->
            <header class="text-left w-full mb-12 sm:mb-16">
                <h1 class="public-hero-title !text-left !text-3xl sm:!text-5xl mb-6">
                    {{ app()->getLocale() === 'es' ? 'La infraestructura de reportes que tu agencia merece' : 'The Client Reporting Stack Built for Modern Agencies' }}
                </h1>
                <p class="public-hero-subtitle !text-left !text-lg sm:!text-xl text-slate-600 dark:text-slate-300 font-normal">
                    {{ app()->getLocale() === 'es'
                        ? 'Elimina el maratón de hojas de cálculo de los viernes y los dashboards que se caen por límites de API. APIs Hub le da a cada uno de tus clientes un cluster dedicado y gestiona accesos y facturación de forma impecable.'
                        : 'Eliminate Friday spreadsheet marathons and client dashboards that crash from API quota limits. APIs Hub gives every client account a dedicated data engine with clean access and billing controls.' }}
                </p>
            </header>

            <!-- Agency Headaches Grid -->
            <section class="w-full mb-16">
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-8 text-left">
                    {{ app()->getLocale() === 'es' ? 'Tres problemas operativos que resolvemos hoy en tu agencia' : 'Three Operational Bottlenecks We Solve for Your Team' }}
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Feature 1: Billing Duality -->
                    <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80">
                        <div class="w-10 h-10 rounded-xl bg-brand-blue/10 text-brand-blue flex items-center justify-center font-bold mb-4">
                            💳
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">
                            {{ app()->getLocale() === 'es' ? 'Dualidad Proyecto vs Facturación' : 'Project vs Billing Duality' }}
                        </h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            {{ app()->getLocale() === 'es'
                                ? 'No vuelvas a mezclar pagos. Puedes crear proyectos para tus clientes y asignar el cobro directo a su tarjeta, o consolidar 20 proyectos bajo el plan maestro de tu agencia.'
                                : 'Never mix up credit cards. Create client projects and assign billing directly to their company, or roll 20 clients under your agency master subscription.' }}
                        </p>
                    </div>

                    <!-- Feature 2: Strict Isolation -->
                    <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center font-bold mb-4">
                            🔒
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">
                            {{ app()->getLocale() === 'es' ? 'Aislamiento Total entre Clientes' : 'Strict Client Isolation' }}
                        </h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            {{ app()->getLocale() === 'es'
                                ? 'Cada cliente tiene su propio cluster y base de datos persistente. Cero riesgo de fugas de datos entre cuentas y cero lentitud por consultas masivas de otros clientes.'
                                : 'Every client runs on their own data cluster and persistent database. Zero risk of cross-client data exposure and zero lag from other accounts.' }}
                        </p>
                    </div>

                    <!-- Feature 3: Automated Normalization -->
                    <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80">
                        <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-500 flex items-center justify-center font-bold mb-4">
                            📊
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">
                            {{ app()->getLocale() === 'es' ? 'Números Reconciliados al Instante' : 'Instant Reconciled Metrics' }}
                        </h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            {{ app()->getLocale() === 'es'
                                ? 'Tus analistas no tienen que pasar horas unificando clics y conversiones. APIs Hub calcula Blended ROAS y MER automáticamente con matemática determinística.'
                                : 'Your analysts stop wasting hours aligning clicks and sales across tabs. APIs Hub computes Blended ROAS and MER automatically with deterministic math.' }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Agency Workflow Walkthrough -->
            <section class="w-full mb-16 p-8 rounded-3xl glass-panel bg-white/30 dark:bg-slate-900/30 border border-slate-200/80 dark:border-slate-800">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mb-6">
                    {{ app()->getLocale() === 'es' ? 'El flujo de trabajo en 3 pasos' : 'The 3-Step Agency Workflow' }}
                </h2>
                <div class="space-y-4 text-sm text-slate-600 dark:text-slate-300">
                    <div class="flex items-start gap-4">
                        <span class="w-7 h-7 rounded-full bg-brand-blue text-white font-bold flex items-center justify-center shrink-0 text-xs">1</span>
                        <div>
                            <strong class="text-slate-900 dark:text-white block mb-0.5">{{ app()->getLocale() === 'es' ? 'Crea un proyecto por cliente' : 'Create an isolated project per client' }}</strong>
                            {{ app()->getLocale() === 'es' ? 'El sistema despliega un cluster dedicado con persistencia propia en cuestión de segundos.' : 'The platform provisions a dedicated cluster with its own database in seconds.' }}
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <span class="w-7 h-7 rounded-full bg-brand-blue text-white font-bold flex items-center justify-center shrink-0 text-xs">2</span>
                        <div>
                            <strong class="text-slate-900 dark:text-white block mb-0.5">{{ app()->getLocale() === 'es' ? 'Conecta Meta, Google, Shopify y Klaviyo' : 'Connect advertising & ecommerce channels' }}</strong>
                            {{ app()->getLocale() === 'es' ? 'Los drivers sincronizan los datos a diario en segundo plano y aplican el diccionario de normalización.' : 'Drivers sync data daily in the background and map disparate metrics to unified schemas.' }}
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <span class="w-7 h-7 rounded-full bg-brand-blue text-white font-bold flex items-center justify-center shrink-0 text-xs">3</span>
                        <div>
                            <strong class="text-slate-900 dark:text-white block mb-0.5">{{ app()->getLocale() === 'es' ? 'Comparte dashboards o conecta tu IA' : 'Share dashboards or connect your AI assistant' }}</strong>
                            {{ app()->getLocale() === 'es' ? 'Comparte enlaces de dashboards con tu cliente o conecta Claude/Cursor vía MCP para auditar el rendimiento.' : 'Share read-only dashboard links with your client or connect Claude/Cursor via MCP for live audits.' }}
                        </div>
                    </div>
                </div>
            </section>

            <!-- Bottom CTA -->
            <section class="w-full text-center p-8 sm:p-12 rounded-3xl glass-panel bg-gradient-to-b from-white/60 to-white/30 dark:from-slate-900/60 dark:to-slate-900/30 border border-slate-200 dark:border-slate-800">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mb-4">
                    {{ app()->getLocale() === 'es' ? 'Empieza con tu primer cliente gratis' : 'Start with Your First Client for Free' }}
                </h2>
                <p class="text-base text-slate-600 dark:text-slate-300 max-w-xl mx-auto mb-6">
                    {{ app()->getLocale() === 'es'
                        ? 'Valida la sincronización y la velocidad del cluster sin compromisos. No pedimos tarjeta de crédito.'
                        : 'Verify cluster synchronization speed on real client channels. No credit card required.' }}
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

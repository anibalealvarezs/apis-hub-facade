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
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Primary Meta Tags -->
        <title>{{ app()->getLocale() === 'es' ? 'Planes y Funcionalidades | Analítica de Marketing Unificada — APIs Hub' : 'Plans & Features | Unified Marketing Analytics & Dashboards — APIs Hub' }}</title>
        <meta name="title" content="{{ app()->getLocale() === 'es' ? 'Planes y Funcionalidades | Analítica de Marketing Unificada — APIs Hub' : 'Plans & Features | Unified Marketing Analytics & Dashboards — APIs Hub' }}">
        <meta name="description" content="{{ app()->getLocale() === 'es' ? 'Descubre las funcionalidades y límites de cada plan en APIs Hub. Conectores de Meta, Google y Shopify, dashboards públicos, KPIs a medida y clasificación semántica por IA.' : 'Compare APIs Hub plans and capabilities. Discover marketing connectors, automated AI keyword classification, custom KPIs, and client reporting dashboards.' }}">
        <meta name="keywords" content="{{ __('marketing analytics plans, unified dashboards, data sync tiers, custom KPIs, agency client reporting, AI semantic classification, APIs Hub') }}">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <meta name="author" content="APIs Hub Network">
        <meta name="application-name" content="APIs Hub">
        <meta name="theme-color" content="#0f172a">
        <meta name="color-scheme" content="dark light">

        <!-- Favicons & App Icons -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/apishub-favicon.png') }}">
        <link rel="icon" type="image/webp" href="{{ asset('images/branding/apishub-favicon.webp') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/branding/apishub-favicon.png') }}">

        <!-- Canonical URL -->
        <link rel="canonical" href="{{ app()->getLocale() === 'es' ? route('landing.plans.es') : route('landing.plans') }}" />

        <!-- Multilingual Alternate Links (hreflang) -->
        <link rel="alternate" hreflang="en" href="{{ route('landing.plans') }}" />
        <link rel="alternate" hreflang="es" href="{{ route('landing.plans.es') }}" />
        <link rel="alternate" hreflang="x-default" href="{{ route('landing.plans') }}" />

        <!-- Open Graph / Social Sharing -->
        <meta property="og:site_name" content="APIs Hub">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ app()->getLocale() === 'es' ? 'Planes y Funcionalidades | APIs Hub' : 'Plans & Features | APIs Hub' }}">
        <meta property="og:description" content="{{ app()->getLocale() === 'es' ? 'Conoce la escala y funcionalidades incluidas en los planes Free, Pro, Ultra y Enterprise de APIs Hub.' : 'Explore tier capabilities and features included across Free, Pro, Ultra, and Enterprise plans.' }}">
        <meta property="og:image" content="{{ asset('images/branding/apishub-620.png') }}">
        <meta property="og:image:width" content="620">
        <meta property="og:image:height" content="135">
        <meta property="og:locale" content="{{ app()->getLocale() === 'es' ? 'es_ES' : 'en_US' }}">
        <meta property="og:locale:alternate" content="{{ app()->getLocale() === 'es' ? 'en_US' : 'es_ES' }}">

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:url" content="{{ url()->current() }}">
        <meta name="twitter:title" content="{{ app()->getLocale() === 'es' ? 'Planes y Funcionalidades | APIs Hub' : 'Plans & Features | APIs Hub' }}">
        <meta name="twitter:description" content="{{ app()->getLocale() === 'es' ? 'Descubre la escala técnica y arquitectura para analítica de marketing de APIs Hub.' : 'Discover APIs Hub technical architecture and features for unified marketing analytics.' }}">
        <meta name="twitter:image" content="{{ asset('images/branding/apishub-620.png') }}">

        <!-- Schema.org Structured Data: SoftwareApplication & FAQPage -->
        <script type="application/ld+json">
        {
          "@@context": "https://schema.org",
          "@@graph": [
            {
              "@@type": "SoftwareApplication",
              "name": "APIs Hub",
              "applicationCategory": "BusinessApplication",
              "operatingSystem": "Web Cloud",
              "description": "{{ app()->getLocale() === 'es' ? 'Plataforma desacoplada de analítica de marketing, sincronización multi-canal y dashboards unificados.' : 'Decoupled marketing analytics, multi-channel synchronization, and unified dashboard platform.' }}",
              "offers": [
                {
                  "@@type": "Offer",
                  "name": "Free Tier",
                  "description": "{{ app()->getLocale() === 'es' ? 'Plan inicial para proyectos personales y validación' : 'Entry plan for personal projects and early testing' }}"
                },
                {
                  "@@type": "Offer",
                  "name": "Pro Tier",
                  "description": "{{ app()->getLocale() === 'es' ? 'Plan para profesionales independientes y consultores' : 'Professional tier for freelancers and consultants' }}"
                },
                {
                  "@@type": "Offer",
                  "name": "Ultra / Founder Tier",
                  "description": "{{ app()->getLocale() === 'es' ? 'Plan para agencias con gestión de múltiples marcas y colaboradores' : 'Agency tier for multi-brand management and collaborators' }}"
                },
                {
                  "@@type": "Offer",
                  "name": "Enterprise Tier",
                  "description": "{{ app()->getLocale() === 'es' ? 'Infraestructura dedicada, SLA garantizado y cuentas a medida' : 'Dedicated infrastructure, custom quotas, and guaranteed SLA' }}"
                }
              ]
            },
            {
              "@@type": "FAQPage",
              "mainEntity": [
                {
                  "@@type": "Question",
                  "name": "{{ app()->getLocale() === 'es' ? '¿Puedo cambiar de plan en cualquier momento?' : 'Can I change my plan at any time?' }}",
                  "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "{{ app()->getLocale() === 'es' ? 'Sí. APIs Hub calcula los ciclos de facturación de forma proporcional y predecible, permitiéndote ascender o descender de plan según las necesidades de tu equipo.' : 'Yes. APIs Hub manages prorated cycles predictably, allowing you to upgrade or adjust plans as your agency or project scales.' }}"
                  }
                },
                {
                  "@@type": "Question",
                  "name": "{{ app()->getLocale() === 'es' ? '¿Qué ocurre si alcanzo el límite de dashboards o KPIs de mi plan?' : 'What happens if I reach the dashboard or KPI limit of my plan?' }}",
                  "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "{{ app()->getLocale() === 'es' ? 'Tus datos existentes y paneles permanecen seguros e intactos. La interfaz te ofrecerá una opción contextual e inmediata para ampliar la cuota al siguiente tier sin interrupción del servicio.' : 'Your existing data and dashboards remain fully functional and secure. The platform provides a seamless in-app upgrade path to expand your quota.' }}"
                  }
                },
                {
                  "@@type": "Question",
                  "name": "{{ app()->getLocale() === 'es' ? '¿Cómo funciona la clasificación de keywords de SEO asistida por IA?' : 'How does AI-assisted SEO keyword classification work?' }}",
                  "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "{{ app()->getLocale() === 'es' ? 'Clasifica automáticamente las consultas de Google Search Console en intención de búsqueda, relevancia y marca mediante IA, enriquecidas con el contexto específico de cada sitio web.' : 'It automatically tags and categorizes Search Console queries into search intent, brand, and relevance using AI, enriched with tailored business context per website.' }}"
                  }
                }
              ]
            }
          ]
        }
        </script>

        @vite(['resources/js/theme.js', 'resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased min-h-screen text-slate-900 dark:text-slate-100 selection:bg-brand-blue selection:text-white" x-data="themeControl">
        
        <!-- Header Sticky Navigation -->
        @include('components.public.header')

        <!-- Main Content -->
        <main class="relative pt-32 sm:pt-40 pb-24 px-4 sm:px-8 max-w-7xl mx-auto flex flex-col items-center">
            
            <!-- Hero Section -->
            <section class="text-center max-w-4xl mx-auto mb-16 sm:mb-20">
                <span class="public-kicker mb-4">
                    {{ __('Architected for Growth') }}
                </span>
                <h1 class="public-hero-title">
                    {{ app()->getLocale() === 'es' ? 'La arquitectura adecuada para cada etapa de tu analítica' : 'The Right Foundation for Every Stage of Your Analytics' }}
                </h1>
                <p class="public-hero-subtitle">
                    {{ app()->getLocale() === 'es'
                        ? 'Diseñado para escalar con total soberanía de datos: desde proyectos individuales hasta agencias con decenas de clientes e infraestructura cloud dedicada.'
                        : 'Engineered to scale with complete data sovereignty: from standalone testing projects to high-growth agencies managing dozens of client workspaces.' }}
                </p>
            </section>

            <!-- 4 Tier Overview Cards -->
            <section class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-20">
                
                <!-- FREE TIER CARD -->
                <div class="glass-panel p-6 sm:p-8 rounded-2xl flex flex-col justify-between transition-all glow-hover border-slate-200/80 dark:border-slate-800">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Tier 01</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20">Starter</span>
                        </div>
                        <h2 class="text-2xl font-extrabold mb-2 text-slate-900 dark:text-white">Free</h2>
                        <p class="text-base text-slate-600 dark:text-slate-300 mb-6 min-h-[52px]">
                            {{ app()->getLocale() === 'es' ? 'Diseñado para pruebas de conectores, evaluación y proyectos personales sin costo.' : 'Entry workspace for connector verification, early testing, and personal projects.' }}
                        </p>
                        
                        <div class="space-y-3 mb-6 border-t border-b border-slate-200/60 dark:border-slate-800/80 py-4 text-base">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">{{ __('Projects') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">1</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">{{ __('Accounts to Sync') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">5</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">{{ __('History Retention') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">6 {{ __('months') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">{{ __('Private Dashboards') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">1</span>
                            </div>
                        </div>

                        <ul class="space-y-3 text-base text-slate-600 dark:text-slate-300 mb-8">
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ __('Basic data synchronization') }}</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ __('Custom KPIs') }} (10)</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                <span class="text-slate-800 dark:text-slate-200">{{ __('AI-assisted keywords classification') }} ({{ __('limited time') }})</span>
                            </li>
                        </ul>
                    </div>

                    <span data-portal="{{ $portals['app'] }}" class="js-portal-link block w-full text-center py-3 px-4 text-base font-bold text-slate-700 dark:text-slate-200 bg-slate-200/50 dark:bg-slate-800/80 hover:bg-slate-300 dark:hover:bg-slate-700 rounded-xl transition-all cursor-pointer">
                        {{ __('Get started for free') }}
                    </span>
                </div>

                <!-- PRO TIER CARD -->
                <div class="glass-panel p-6 sm:p-8 rounded-2xl flex flex-col justify-between transition-all glow-hover border-brand-blue/30 relative">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-gradient-to-r from-brand-blue to-brand-teal text-white text-[11px] font-black uppercase tracking-wider px-3 py-1 rounded-full shadow-md">
                        {{ __('Freelancers & Consultants') }}
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-bold uppercase tracking-widest text-brand-blue">Tier 02</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-brand-blue/10 text-brand-blue border border-brand-blue/20">Pro</span>
                        </div>
                        <h2 class="text-2xl font-extrabold mb-2 text-slate-900 dark:text-white">Pro</h2>
                        <p class="text-base text-slate-600 dark:text-slate-300 mb-6 min-h-[52px]">
                            {{ app()->getLocale() === 'es' ? 'Potencia analítica y reportes compartibles ideales para profesionales con múltiples clientes.' : 'Analytics horsepower and client-ready shareable reports for independent marketers.' }}
                        </p>
                        
                        <div class="space-y-3 mb-6 border-t border-b border-slate-200/60 dark:border-slate-800/80 py-4 text-base">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">{{ __('Projects') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">5</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">{{ __('Accounts to Sync') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">100</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">{{ __('History Retention') }}</span>
                                <span class="font-bold text-brand-blue">{{ __('Full') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">{{ __('Public Dashboards') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">5</span>
                            </div>
                        </div>

                        <ul class="space-y-3 text-base text-slate-600 dark:text-slate-300 mb-8">
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ __('Export dashboards & reports to PDF') }}</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ __('Custom KPIs') }} (30)</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-brand-blue shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ __('AI-assisted keywords classification') }}</span>
                            </li>
                        </ul>
                    </div>

                    <span data-portal="{{ $portals['app'] }}" class="js-portal-link block w-full text-center py-3 px-4 text-base font-bold text-white bg-brand-blue hover:opacity-95 rounded-xl transition-all shadow-glow cursor-pointer">
                        {{ __('Join the beta') }}
                    </span>
                </div>

                <!-- ULTRA / FOUNDER TIER CARD -->
                <div class="glass-panel p-6 sm:p-8 rounded-2xl flex flex-col justify-between transition-all glow-hover border-brand-teal/40 dark:border-brand-teal/30">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-bold uppercase tracking-widest text-brand-teal">Tier 03</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-brand-teal/10 text-brand-teal border border-brand-teal/20">Ultra</span>
                        </div>
                        <h2 class="text-2xl font-extrabold mb-2 text-slate-900 dark:text-white">Ultra / Founder</h2>
                        <p class="text-base text-slate-600 dark:text-slate-300 mb-6 min-h-[52px]">
                            {{ app()->getLocale() === 'es' ? 'La suite definitiva para agencias: colaboración en equipo, acceso API y hasta 15 proyectos.' : 'The agency workhorse: full team collaboration, programmatic API access, and up to 15 workspaces.' }}
                        </p>
                        
                        <div class="space-y-3 mb-6 border-t border-b border-slate-200/60 dark:border-slate-800/80 py-4 text-base">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">{{ __('Projects') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">15</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">{{ __('Accounts to Sync') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">500</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">{{ __('Team Members') }}</span>
                                <span class="font-bold text-brand-teal">{{ __('Invite users to collaborate') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">{{ __('Public Dashboards') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">15</span>
                            </div>
                        </div>

                        <ul class="space-y-3 text-base text-slate-600 dark:text-slate-300 mb-8">
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ __('API access') }} ({{ __('External Integration') }})</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ __('Model Context Protocol (MCP) Server') }}</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ __('Custom KPIs') }} (50)</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-brand-teal shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                <span>{{ __('AI-assisted keywords classification') }}</span>
                            </li>
                        </ul>
                    </div>

                    <span data-portal="{{ $portals['app'] }}" class="js-portal-link block w-full text-center py-3 px-4 text-base font-bold text-white bg-gradient-to-r from-brand-blue to-brand-teal hover:opacity-95 rounded-xl transition-all shadow-glow cursor-pointer">
                        {{ __('Get started with Ultra') }}
                    </span>
                </div>

                <!-- ENTERPRISE TIER CARD -->
                <div class="glass-panel p-6 sm:p-8 rounded-2xl flex flex-col justify-between transition-all glow-hover border-amber-500/30">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-bold uppercase tracking-widest text-amber-500">Tier 04</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20">Custom</span>
                        </div>
                        <h2 class="text-2xl font-extrabold mb-2 text-slate-900 dark:text-white">Enterprise</h2>
                        <p class="text-base text-slate-600 dark:text-slate-300 mb-6 min-h-[52px]">
                            {{ app()->getLocale() === 'es' ? 'Infraestructura dedicada, límites a medida y soporte corporativo de misión crítica.' : 'Corporate deployment with dedicated syncing engine, custom limits, and premium SLA.' }}
                        </p>
                        
                        <div class="space-y-3 mb-6 border-t border-b border-slate-200/60 dark:border-slate-800/80 py-4 text-base">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">{{ __('Projects') }}</span>
                                <span class="font-bold text-amber-500">{{ __('Custom (Base 15)') }}+</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">{{ __('Accounts to Sync') }}</span>
                                <span class="font-bold text-amber-500">500+</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">{{ __('Dashboards & KPIs') }}</span>
                                <span class="font-bold text-emerald-500 uppercase">{{ __('unlimited') }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">{{ __('Billing Profiles') }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ __('Shareable') }}</span>
                            </div>
                        </div>

                        <ul class="space-y-3 text-base text-slate-600 dark:text-slate-300 mb-8">
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ __('Guaranteed SLA') }} & {{ __('Dedicated Support') }}</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ __('Model Context Protocol (MCP) Server') }}</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ __('Higher API rate limits') }} (120+/min)</span>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span class="flex items-center gap-2">
                                    <span>{{ __('Cross-project Analytics') }}</span>
                                    <span class="px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30">{{ __('Soon') }}</span>
                                </span>
                            </li>
                        </ul>
                    </div>

                    <span data-portal="{{ $portals['app'] }}" class="js-portal-link block w-full text-center py-3 px-4 text-base font-bold text-slate-900 dark:text-white bg-amber-500 hover:bg-amber-400 rounded-xl transition-all shadow-glow cursor-pointer">
                        {{ __('Get started with Enterprise') }}
                    </span>
                </div>

            </section>

            <!-- Detailed Feature Breakdown Matrix (SEO Pillar Section) -->
            <section class="w-full mb-20" aria-labelledby="matrix-headline">
                <div class="text-center max-w-2xl mx-auto mb-10">
                    <h2 id="matrix-headline" class="public-section-title mb-3">
                        {{ app()->getLocale() === 'es' ? 'Comparativa exhaustiva de capacidades' : 'Comprehensive Feature Comparison' }}
                    </h2>
                    <p class="public-section-subtitle">
                        {{ app()->getLocale() === 'es' ? 'Revisa al detalle los límites de datos, gobernanza y herramientas analíticas por nivel.' : 'A side-by-side technical breakdown of data retention, governance, and analytics capabilities.' }}
                    </p>
                </div>

                <div class="overflow-x-auto rounded-2xl glass-panel border border-slate-200/80 dark:border-slate-800 shadow-xl">
                    <table class="w-full text-left text-sm sm:text-base border-collapse">
                        <!-- Table Header -->
                        <thead class="bg-slate-100/70 dark:bg-slate-800/70 text-slate-700 dark:text-slate-300 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th scope="col" class="py-4 px-6 font-bold uppercase tracking-wider text-xs sm:text-sm w-2/5">{{ __('Feature & Capability') }}</th>
                                <th scope="col" class="py-4 px-4 font-bold text-center uppercase tracking-wider text-xs sm:text-sm w-1/6">Free</th>
                                <th scope="col" class="py-4 px-4 font-bold text-center uppercase tracking-wider text-xs sm:text-sm w-1/6 text-brand-blue">Pro</th>
                                <th scope="col" class="py-4 px-4 font-bold text-center uppercase tracking-wider text-xs sm:text-sm w-1/6 text-brand-teal">Ultra</th>
                                <th scope="col" class="py-4 px-4 font-bold text-center uppercase tracking-wider text-xs sm:text-sm w-1/6 text-amber-500">Enterprise</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200/60 dark:divide-slate-800/80">
                            
                            <!-- Category: Infrastructure & Data -->
                            <tr class="bg-slate-50/50 dark:bg-slate-900/40">
                                <th colspan="5" class="py-3.5 px-6 font-bold text-slate-900 dark:text-slate-100 uppercase tracking-widest text-xs sm:text-sm bg-slate-200/30 dark:bg-slate-800/30">
                                    {{ __('Infrastructure & Data Syncing') }}
                                </th>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('Max Active Projects') }}
                                    <span class="block text-xs sm:text-sm font-normal text-slate-400 mt-0.5">{{ __('Independent environments with dedicated syncing engine and isolated data.') }}</span>
                                </td>
                                <td class="py-4 px-4 text-center font-bold">1</td>
                                <td class="py-4 px-4 text-center font-bold text-brand-blue">5</td>
                                <td class="py-4 px-4 text-center font-bold text-brand-teal">15</td>
                                <td class="py-4 px-4 text-center font-bold text-amber-500">{{ __('Custom (Base 15)') }}+</td>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('Accounts to Sync') }}
                                    <span class="block text-xs sm:text-sm font-normal text-slate-400 mt-0.5">{{ __('Ad accounts, Google properties, Facebook pages, and Shopify stores.') }}</span>
                                </td>
                                <td class="py-4 px-4 text-center font-bold">5</td>
                                <td class="py-4 px-4 text-center font-bold text-brand-blue">100</td>
                                <td class="py-4 px-4 text-center font-bold text-brand-teal">500</td>
                                <td class="py-4 px-4 text-center font-bold text-amber-500">500+</td>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('Historical Data Retention') }}
                                    <span class="block text-xs sm:text-sm font-normal text-slate-400 mt-0.5">{{ __('Depth of historical daily metrics stored in queryable local cache.') }}</span>
                                </td>
                                <td class="py-4 px-4 text-center">6 {{ __('months') }}</td>
                                <td class="py-4 px-4 text-center font-bold text-brand-blue">{{ __('Full') }}</td>
                                <td class="py-4 px-4 text-center font-bold text-brand-teal">{{ __('Full') }}</td>
                                <td class="py-4 px-4 text-center font-bold text-amber-500">{{ __('Full') }}</td>
                            </tr>

                            <!-- Category: Dashboards & Reporting -->
                            <tr class="bg-slate-50/50 dark:bg-slate-900/40">
                                <th colspan="5" class="py-3.5 px-6 font-bold text-slate-900 dark:text-slate-100 uppercase tracking-widest text-xs sm:text-sm bg-slate-200/30 dark:bg-slate-800/30">
                                    {{ __('Dashboards & Client Reporting') }}
                                </th>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('Private Dashboards') }}
                                    <span class="block text-xs sm:text-sm font-normal text-slate-400 mt-0.5">{{ __('Internal operational dashboards customized with modular widgets.') }}</span>
                                </td>
                                <td class="py-4 px-4 text-center font-bold">1</td>
                                <td class="py-4 px-4 text-center font-bold text-brand-blue">5</td>
                                <td class="py-4 px-4 text-center font-bold text-brand-teal">15</td>
                                <td class="py-4 px-4 text-center font-bold text-emerald-500 uppercase">{{ __('unlimited') }}</td>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('Public & Shareable Dashboards') }}
                                    <span class="block text-xs sm:text-sm font-normal text-slate-400 mt-0.5">{{ __('Live share links with token authentication for clients and partners.') }}</span>
                                </td>
                                <td class="py-4 px-4 text-center text-slate-400">—</td>
                                <td class="py-4 px-4 text-center font-bold text-brand-blue">5</td>
                                <td class="py-4 px-4 text-center font-bold text-brand-teal">15</td>
                                <td class="py-4 px-4 text-center font-bold text-emerald-500 uppercase">{{ __('unlimited') }}</td>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('Export dashboards & reports to PDF') }}
                                </td>
                                <td class="py-4 px-4 text-center text-slate-400">—</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('Custom KPIs') }}
                                    <span class="block text-xs sm:text-sm font-normal text-slate-400 mt-0.5">{{ __('Custom formulas, cross-channel blended metrics, and ROAS calculations.') }}</span>
                                </td>
                                <td class="py-4 px-4 text-center font-bold">10</td>
                                <td class="py-4 px-4 text-center font-bold text-brand-blue">30</td>
                                <td class="py-4 px-4 text-center font-bold text-brand-teal">50</td>
                                <td class="py-4 px-4 text-center font-bold text-emerald-500 uppercase">{{ __('unlimited') }}</td>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('Derived Metrics & Formulas') }}
                                </td>
                                <td class="py-4 px-4 text-center font-bold">10</td>
                                <td class="py-4 px-4 text-center font-bold text-brand-blue">30</td>
                                <td class="py-4 px-4 text-center font-bold text-brand-teal">50</td>
                                <td class="py-4 px-4 text-center font-bold text-emerald-500 uppercase">{{ __('unlimited') }}</td>
                            </tr>

                            <!-- Category: Artificial Intelligence -->
                            <tr class="bg-slate-50/50 dark:bg-slate-900/40">
                                <th colspan="5" class="py-3.5 px-6 font-bold text-slate-900 dark:text-slate-100 uppercase tracking-widest text-xs sm:text-sm bg-slate-200/30 dark:bg-slate-800/30">
                                    {{ __('Artificial Intelligence & Semantic SEO') }}
                                </th>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('AI-assisted keywords classification') }}
                                    <span class="block text-xs sm:text-sm font-normal text-slate-400 mt-0.5">{{ __('Intent, Brand, and Relevance query labeling for Google Search Console.') }}</span>
                                </td>
                                <td class="py-4 px-4 text-center text-amber-500 font-semibold">{{ __('Promo') }}</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('Tailored Website Context') }}
                                    <span class="block text-xs sm:text-sm font-normal text-slate-400 mt-0.5">{{ __('Custom Brand, Description, and Competitors injection per web property.') }}</span>
                                </td>
                                <td class="py-4 px-4 text-center text-amber-500 font-semibold">{{ __('Promo') }}</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('Dedicated TypeSafe API Key') }}
                                    <span class="block text-xs sm:text-sm font-normal text-slate-400 mt-0.5">{{ __('Use your own dedicated classification key to bypass shared limits.') }}</span>
                                </td>
                                <td class="py-4 px-4 text-center text-slate-400">—</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                            </tr>

                            <!-- Category: Governance & Team -->
                            <tr class="bg-slate-50/50 dark:bg-slate-900/40">
                                <th colspan="5" class="py-3.5 px-6 font-bold text-slate-900 dark:text-slate-100 uppercase tracking-widest text-xs sm:text-sm bg-slate-200/30 dark:bg-slate-800/30">
                                    {{ __('Team Collaboration & Access Control') }}
                                </th>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('Invite Team Collaborators') }}
                                    <span class="block text-xs sm:text-sm font-normal text-slate-400 mt-0.5">{{ __('Grant access to designers, team analysts, or external clients.') }}</span>
                                </td>
                                <td class="py-4 px-4 text-center text-slate-400">{{ __('Owner only') }}</td>
                                <td class="py-4 px-4 text-center text-slate-400">{{ __('Owner only') }}</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('Granular Asset Groups Sharing') }}
                                    <span class="block text-xs sm:text-sm font-normal text-slate-400 mt-0.5">{{ __('Restrict collaborators to specific stores, pages, or ad accounts.') }}</span>
                                </td>
                                <td class="py-4 px-4 text-center text-slate-400">—</td>
                                <td class="py-4 px-4 text-center text-slate-400">—</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('Shared Billing Profiles') }}
                                    <span class="block text-xs sm:text-sm font-normal text-slate-400 mt-0.5">{{ __('Allow multiple agency admins to link projects to a corporate pool.') }}</span>
                                </td>
                                <td class="py-4 px-4 text-center text-slate-400">—</td>
                                <td class="py-4 px-4 text-center text-slate-400">—</td>
                                <td class="py-4 px-4 text-center text-slate-400">—</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                            </tr>

                            <!-- Category: Integration & API -->
                            <tr class="bg-slate-50/50 dark:bg-slate-900/40">
                                <th colspan="5" class="py-3.5 px-6 font-bold text-slate-900 dark:text-slate-100 uppercase tracking-widest text-xs sm:text-sm bg-slate-200/30 dark:bg-slate-800/30">
                                    {{ __('Developer & Programmatic API') }}
                                </th>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('REST API Access') }}
                                    <span class="block text-xs sm:text-sm font-normal text-slate-400 mt-0.5">{{ __('Query raw normalized metrics, extract timeseries data, and trigger syncs.') }}</span>
                                </td>
                                <td class="py-4 px-4 text-center text-slate-400">—</td>
                                <td class="py-4 px-4 text-center text-slate-400">—</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('Model Context Protocol (MCP) Server') }}
                                    <span class="block text-xs sm:text-sm font-normal text-slate-400 mt-0.5">{{ __('Direct integration with AI agents (Antigravity, Claude, Cursor) via SSE for live query execution.') }}</span>
                                </td>
                                <td class="py-4 px-4 text-center text-slate-400">—</td>
                                <td class="py-4 px-4 text-center text-slate-400">—</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                                <td class="py-4 px-4 text-center text-emerald-500 font-bold">✓</td>
                            </tr>
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-800/30">
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    {{ __('API Rate Limits') }}
                                </td>
                                <td class="py-4 px-4 text-center text-slate-400">—</td>
                                <td class="py-4 px-4 text-center text-slate-400">—</td>
                                <td class="py-4 px-4 text-center font-medium">60 req/min</td>
                                <td class="py-4 px-4 text-center font-bold text-amber-500">120+ req/min</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- FAQ Accordion Section (Rich Snippet SEO) -->
            <section class="w-full max-w-4xl mx-auto mb-20" aria-labelledby="faq-headline">
                <div class="text-center mb-10">
                    <h2 id="faq-headline" class="public-section-title mb-2">
                        {{ __('Frequently Asked Questions') }}
                    </h2>
                    <p class="public-section-subtitle">
                        {{ app()->getLocale() === 'es' ? 'Todo lo que necesitas saber sobre la gobernanza y escala de tus datos.' : 'Everything you need to know about our data architecture and governance.' }}
                    </p>
                </div>

                <div class="space-y-4" x-data="{ openFaq: null }">
                    
                    <div class="glass-panel rounded-xl p-6 border border-slate-200 dark:border-slate-800">
                        <button @click="openFaq = (openFaq === 1 ? null : 1)" class="w-full flex items-center justify-between text-left text-base sm:text-lg font-bold text-slate-800 dark:text-slate-200">
                            <span>{{ app()->getLocale() === 'es' ? '¿Cómo se maneja la soberanía y privacidad de los datos de mis clientes?' : 'How is client data sovereignty and privacy protected?' }}</span>
                            <svg class="w-5 h-5 shrink-0 transition-transform ml-4" :class="openFaq === 1 ? 'rotate-180 text-brand-blue' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="openFaq === 1" x-collapse x-cloak class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ app()->getLocale() === 'es'
                                ? 'Cada proyecto en APIs Hub opera de forma completamente aislada con su propio motor de sincronización y almacenamiento dedicado. La soberanía de los datos es total: nadie, ni siquiera el dueño del perfil de facturación, puede acceder a los datos de un proyecto a menos que el creador lo invite explícitamente como colaborador.'
                                : 'Each project in APIs Hub operates in total isolation with its own dedicated syncing engine and secure storage. Data sovereignty is absolute: no one, not even billing profile owners, can access project metrics unless explicitly invited by the project creator as a collaborator.' }}
                        </div>
                    </div>

                    <div class="glass-panel rounded-xl p-6 border border-slate-200 dark:border-slate-800">
                        <button @click="openFaq = (openFaq === 2 ? null : 2)" class="w-full flex items-center justify-between text-left text-base sm:text-lg font-bold text-slate-800 dark:text-slate-200">
                            <span>{{ app()->getLocale() === 'es' ? '¿Puedo transferir la propiedad de un proyecto a un cliente en el futuro?' : 'Can I transfer project ownership to a client later?' }}</span>
                            <svg class="w-5 h-5 shrink-0 transition-transform ml-4" :class="openFaq === 2 ? 'rotate-180 text-brand-blue' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="openFaq === 2" x-collapse x-cloak class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ app()->getLocale() === 'es'
                                ? 'Sí. APIs Hub incluye un flujo completo de transferencia de proyectos con tokens seguros por correo electrónico. Puedes transferir la propiedad del proyecto reteniendo o desacoplando el perfil de facturación.'
                                : 'Yes. APIs Hub provides an end-to-end tokenized transfer workflow, allowing agencies to build analytics projects and cleanly hand over ownership and billing to clients.' }}
                        </div>
                    </div>

                    <div class="glass-panel rounded-xl p-6 border border-slate-200 dark:border-slate-800">
                        <button @click="openFaq = (openFaq === 3 ? null : 3)" class="w-full flex items-center justify-between text-left text-base sm:text-lg font-bold text-slate-800 dark:text-slate-200">
                            <span>{{ app()->getLocale() === 'es' ? '¿Qué requisitos técnicos tiene la clasificación asistida por IA?' : 'What are the technical requirements for AI semantic classification?' }}</span>
                            <svg class="w-5 h-5 shrink-0 transition-transform ml-4" :class="openFaq === 3 ? 'rotate-180 text-brand-blue' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="openFaq === 3" x-collapse x-cloak class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ app()->getLocale() === 'es'
                                ? 'Requiere conectar Google Search Console al motor de sincronización de APIs Hub. Las consultas de búsqueda se analizan y clasifican semánticamente preservando la privacidad de tu negocio.'
                                : 'It requires connecting Google Search Console to your APIs Hub syncing engine. Search queries are automatically analyzed and semantically classified while maintaining strict business privacy.' }}
                        </div>
                    </div>

                    <div class="glass-panel rounded-xl p-6 border border-slate-200 dark:border-slate-800">
                        <button @click="openFaq = (openFaq === 4 ? null : 4)" class="w-full flex items-center justify-between text-left text-base sm:text-lg font-bold text-slate-800 dark:text-slate-200">
                            <span>{{ app()->getLocale() === 'es' ? '¿Cómo funciona el servidor Model Context Protocol (MCP) y qué planes lo incluyen?' : 'How does the Model Context Protocol (MCP) server work and which tiers include it?' }}</span>
                            <svg class="w-5 h-5 shrink-0 transition-transform ml-4" :class="openFaq === 4 ? 'rotate-180 text-brand-blue' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="openFaq === 4" x-collapse x-cloak class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                            {{ app()->getLocale() === 'es'
                                ? 'El servidor MCP está disponible exclusivamente en las cuentas Ultra y Enterprise. Permite conectar asistentes de IA como Google Antigravity, Claude Desktop o Cursor directamente a tu nodo mediante transporte Server-Sent Events (SSE). Los agentes pueden realizar llamadas a herramientas para consultar resúmenes de rendimiento multi-canal, calcular ROAS y auditar la cobertura de datos en tiempo real de forma segura y autenticada.'
                                : 'The MCP server is exclusively available on Ultra and Enterprise tiers. It connects AI assistants such as Google Antigravity, Claude Desktop, and Cursor directly to your dedicated node using Server-Sent Events (SSE). Autonomous agents can invoke tools to summarize cross-channel metrics, calculate blended ROAS, and audit data coverage in real-time under authenticated access.' }}
                        </div>
                    </div>

                </div>
            </section>

            <!-- Bottom CTA Banner -->
            <section class="w-full max-w-4xl mx-auto text-center p-8 sm:p-12 glass-panel rounded-3xl border border-brand-blue/30 shadow-2xl relative overflow-hidden">
                <div class="relative z-10">
                    <h2 class="public-section-title mb-4">
                        {{ app()->getLocale() === 'es' ? '¿Listo para unificar tu analítica de marketing?' : 'Ready to Unify Your Marketing Analytics?' }}
                    </h2>
                    <p class="public-section-subtitle mb-8">
                        {{ app()->getLocale() === 'es'
                            ? 'Conecta Meta Ads, Google Ads, GA4, Search Console y Shopify en segundos. Comienza gratis durante nuestra fase Beta.'
                            : 'Connect Meta, Google Ads, GA4, Search Console, and Shopify in seconds. Start free during our Beta program.' }}
                    </p>
                    <span data-portal="{{ $portals['app'] }}" class="js-portal-link inline-block px-8 py-3.5 text-sm font-bold text-white bg-brand-blue rounded-xl hover:scale-105 active:scale-95 transition-all shadow-glow hover:shadow-glow-intense cursor-pointer">
                        {{ __('Try beta for free') }}
                    </span>
                </div>
                <div class="absolute -right-20 -bottom-20 w-60 h-60 bg-brand-teal/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -left-20 -top-20 w-60 h-60 bg-brand-blue/10 rounded-full blur-3xl pointer-events-none"></div>
            </section>

        </main>

        <!-- Semantic Footer / Micro Branding -->
        @include('components.public.footer')
        
        <!-- Background Mesh -->
        <div class="hero-mesh" aria-hidden="true"></div>

        <!-- External Marketing & Analytics (Vite Optimized) -->
        @vite(['resources/js/gtm.js'])
    </body>
</html>

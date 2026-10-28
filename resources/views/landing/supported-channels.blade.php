@php
    $isEs = app()->getLocale() === 'es';
@endphp

<x-layouts.public 
    :title="$isEs ? 'Canales y Proveedores Soportados | APIs Hub' : 'Supported Channels & Providers Matrix | APIs Hub'"
    :description="$isEs ? 'Consulta la matriz de integraciones activas y en fase de pruebas: Google, Meta, Mailchimp, Shopify, Klaviyo y más.' : 'Explore our live provider integration status: Google, Meta, Mailchimp, Shopify, Klaviyo, and lead capture pipelines.'"
    :keywords="__('supported marketing channels, apis hub integrations, google ads api, meta marketing api, shopify connector')"
    :og-type="'website'"
    :portals="$portals"
>
    <main class="relative pt-32 sm:pt-40 pb-24 px-4 sm:px-8 max-w-5xl mx-auto flex flex-col items-center">
        
        <!-- Breadcrumbs Navigation -->
        <nav aria-label="{{ __('Breadcrumb') }}" class="w-full mb-6">
            <ol class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-blue flex-wrap">
                <li>
                    <a href="{{ $isEs ? '/es' : '/' }}" class="hover:underline">{{ __('Home') }}</a>
                </li>
                <li class="text-slate-400" aria-hidden="true">/</li>
                <li>
                    <a href="{{ $isEs ? route('landing.plans.es') : route('landing.plans') }}" class="hover:underline">{{ __('Solutions') }}</a>
                </li>
                <li class="text-slate-400" aria-hidden="true">/</li>
                <li class="text-slate-600 dark:text-slate-400" aria-current="page">
                    {{ $isEs ? 'Canales Soportados' : 'Supported Channels' }}
                </li>
            </ol>
        </nav>

        <!-- Hero Header -->
        <header class="text-left w-full mb-12 sm:mb-16">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 mb-4">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>{{ $isEs ? 'Integraciones Oficiales & Telemetría' : 'Live Platform Capability Matrix' }}</span>
            </div>
            <h1 class="public-hero-title !text-left !text-3xl sm:!text-5xl mb-6">
                {{ $isEs ? 'Canales y Proveedores Soportados' : 'Supported Channels & Integrations' }}
            </h1>
            <p class="public-hero-subtitle !text-left !text-lg sm:!text-xl text-slate-600 dark:text-slate-300 font-normal">
                {{ $isEs 
                    ? 'Transparencia total sobre los canales activos con normalización canónica completa y las integraciones actualmente en fase de pruebas.'
                    : 'Real-time visibility into actively supported ad platforms with canonical normalization and incoming connectors in testing.' }}
            </p>
        </header>

        <!-- Active / Supported Channels Section -->
        <section class="w-full mb-16" aria-label="Active Channels">
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2.5">
                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                <span>{{ $isEs ? 'Canales Soportados Activos' : 'Actively Supported Channels' }}</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($supported as $item)
                    <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">
                                    {{ $item['provider'] }}
                                </h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                    {{ __('Active') }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                                {{ $item['description'] }}
                            </p>
                            
                            <div class="space-y-3 pt-3 border-t border-slate-200/50 dark:border-slate-800/60">
                                @foreach($item['channels'] as $channel)
                                    <div class="text-xs">
                                        <div class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-brand-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            {{ $channel['name'] }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 pl-5">
                                            {{ $channel['metrics'] }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Testing Phase Section -->
        <section class="w-full mb-16" aria-label="Testing Phase Channels">
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2.5">
                <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                <span>{{ $isEs ? 'En Fase de Pruebas' : 'In Testing Phase' }}</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($testing as $testItem)
                    <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80 border-dashed">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                {{ $testItem['provider'] }}
                            </h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                {{ $testItem['status'] }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                            {{ $testItem['description'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Bottom CTA -->
        <section class="w-full text-center p-8 sm:p-12 rounded-3xl glass-panel bg-gradient-to-b from-white/60 to-white/30 dark:from-slate-900/60 dark:to-slate-900/30 border border-slate-200 dark:border-slate-800">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mb-4">
                {{ $isEs ? '¿Necesitas un proveedor o conector adicional?' : 'Need an additional data provider or custom pipeline?' }}
            </h2>
            <p class="text-base text-slate-600 dark:text-slate-300 max-w-xl mx-auto mb-6">
                {{ $isEs
                    ? 'Nuestra arquitectura de drivers modulares permite normalizar nuevos canales en tiempo récord.'
                    : 'Our modular driver architecture enables fast integration and canonical normalization of custom sources.' }}
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="{{ $isEs ? route('demo.data-sources.es') : route('demo.data-sources', ['lang' => 'en']) }}" class="px-8 py-3 text-sm font-bold text-white bg-brand-blue rounded-xl hover:scale-105 active:scale-95 transition-all shadow-glow hover:shadow-glow-intense">
                    {{ __('Test Supported Channels in Demo') }}
                </a>
            </div>
        </section>

    </main>
</x-layouts.public>

<x-layouts.public 
    :title="app()->getLocale() === 'es' ? 'Normalización de Métricas Multi-Canal | APIs Hub' : 'Cross-Channel Marketing Metric Normalization | APIs Hub'"
    :description="app()->getLocale() === 'es' ? 'Reconcilia Meta, Google, Shopify y Klaviyo con un diccionario de equivalencias común. Calcula Blended ROAS y MER real sin fórmulas complejas de hojas de cálculo.' : 'Reconcile Meta, Google, Shopify, and Klaviyo with a shared metric dictionary. Calculate true Blended ROAS and MER without spreadsheet formula chaos.'"
    :keywords="__('cross channel marketing data, blended roas formula, marketing efficiency ratio mer, metric normalization dictionary, reconcile meta and google ads, APIs Hub')"
    :meta-image="asset('images/branding/apishub-620.png')"
    :og-type="'article'"
    :portals="$portals"
>
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
                    ? 'Meta llama a los clics "link clicks" o "all clicks". Google Ads reporta "interactions". Shopify cuenta "orders" mientras Google Analytics cuenta "key events". Unificamos estas discrepancias bajo una matemática limpia y verificable.'
                    : 'Meta reports "link clicks" vs "outbound clicks". Google Ads reports "interactions". Shopify counts "orders" while GA4 tracks "purchase events". We reconcile these platform definitions under one ground truth.' }}
            </p>
        </header>

        <!-- Metric Equivalence Matrix Table -->
        <section class="w-full mb-16 overflow-x-auto" aria-label="Metric Normalization Table">
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white mb-6">
                {{ app()->getLocale() === 'es' ? 'Matriz de Equivalencias de Métricas de Marketing' : 'The Cross-Channel Metric Equivalence Matrix' }}
            </h2>

            <div class="rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80 p-6 min-w-[640px]">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-mono uppercase">
                            <th class="py-3 font-semibold">{{ app()->getLocale() === 'es' ? 'Concepto Unificado' : 'Unified Concept' }}</th>
                            <th class="py-3 font-semibold">Meta Ads</th>
                            <th class="py-3 font-semibold">Google Ads</th>
                            <th class="py-3 font-semibold">Shopify</th>
                            <th class="py-3 font-semibold">Klaviyo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-mono text-slate-700 dark:text-slate-300">
                        <tr>
                            <td class="py-3 font-bold text-brand-blue font-sans">Spend / Inversión</td>
                            <td class="py-3">spend</td>
                            <td class="py-3">metrics.cost_micros / 1M</td>
                            <td class="py-3 text-slate-400">—</td>
                            <td class="py-3 text-slate-400">—</td>
                        </tr>
                        <tr>
                            <td class="py-3 font-bold text-brand-blue font-sans">Clicks</td>
                            <td class="py-3">actions.link_click</td>
                            <td class="py-3">metrics.clicks</td>
                            <td class="py-3 text-slate-400">—</td>
                            <td class="py-3">clicked_email</td>
                        </tr>
                        <tr>
                            <td class="py-3 font-bold text-brand-blue font-sans">Conversions</td>
                            <td class="py-3">actions.purchase</td>
                            <td class="py-3">metrics.conversions</td>
                            <td class="py-3">orders_count</td>
                            <td class="py-3">placed_order_count</td>
                        </tr>
                        <tr>
                            <td class="py-3 font-bold text-brand-blue font-sans">Revenue / Ingresos</td>
                            <td class="py-3">action_values.purchase</td>
                            <td class="py-3">metrics.conversions_value</td>
                            <td class="py-3">total_price</td>
                            <td class="py-3">placed_order_value</td>
                        </tr>
                        <tr>
                            <td class="py-3 font-bold text-brand-teal font-sans">Blended ROAS</td>
                            <td colspan="4" class="py-3 font-sans font-semibold text-brand-teal">
                                Total Store Revenue &divide; (Meta Spend + Google Spend + Otros Canales)
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Driver Architecture Diagram Info -->
        <section class="w-full mb-16" aria-label="Driver Architecture">
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white mb-6">
                {{ app()->getLocale() === 'es' ? 'Arquitectura por Drivers Especializados' : 'How Normalization Drivers Work' }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-slate-600 dark:text-slate-300">
                <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80">
                    <h3 class="font-bold text-slate-900 dark:text-white mb-2 text-base">
                        {{ app()->getLocale() === 'es' ? 'Aislamiento de SDK vs Driver' : 'SDK vs Driver Separation' }}
                    </h3>
                    <p class="leading-relaxed">
                        {{ app()->getLocale() === 'es'
                            ? 'Cada integración consta de dos capas desacopladas: el SDK (comunicación HTTP y autenticación OAuth pura) y el Driver (lógica de normalización de negocio). Esta arquitectura previene que los cambios en las APIs externas rompan tus reportes.'
                            : 'Each integration uses two decoupled layers: the SDK (raw HTTP transport and OAuth token refresh) and the Driver (normalization business logic). Upstream API changes never cascade breaking changes into your reports.' }}
                    </p>
                </div>

                <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80">
                    <h3 class="font-bold text-slate-900 dark:text-white mb-2 text-base">
                        {{ app()->getLocale() === 'es' ? 'Validación Agnóstica de Tipos' : 'Agnostic Type Verification' }}
                    </h3>
                    <p class="leading-relaxed">
                        {{ app()->getLocale() === 'es'
                            ? 'Los datos recibidos son tipados y mapeados a entidades canónicas (gasto, impresiones, clics, conversiones, ingresos) con reglas matemáticas estrictas que garantizan la consistencia al sumar múltiples canales.'
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
</x-layouts.public>

<x-layouts.public 
    :title="app()->getLocale() === 'es' ? 'Arquitectura de Datos Aislada | APIs Hub' : 'Isolated Data Cluster Architecture | APIs Hub'"
    :description="app()->getLocale() === 'es' ? 'Descubre cómo la arquitectura de clusters dedicados por proyecto en APIs Hub elimina caídas de cuota en Looker Studio y tiempos de espera en reportes de marketing.' : 'Learn how APIs Hub dedicated project data clusters eliminate Looker Studio API quota ceilings, connector timeouts, and multi-tenant lag.'"
    :keywords="__('marketing data architecture, isolated tenant cluster, looker studio quota fix, automated scheduled syncing, marketing OLAP engine, APIs Hub')"
    :meta-image="asset('images/branding/apishub-620.png')"
    :og-type="'article'"
    :portals="$portals"
>
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
                    <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300 mb-6">
                        <li class="flex items-start gap-2">
                            <span class="text-red-500 font-bold mt-0.5">&times;</span>
                            <span>{{ app()->getLocale() === 'es' ? 'Cada recarga de página agota tokens de API en Meta y Google.' : 'Every dashboard reload burns API quota on Meta and Google.' }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-red-500 font-bold mt-0.5">&times;</span>
                            <span>{{ app()->getLocale() === 'es' ? 'Timeouts y errores "Quota Exceeded" en Looker Studio.' : 'Timeouts and "Quota Exceeded" crashes in Looker Studio.' }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-red-500 font-bold mt-0.5">&times;</span>
                            <span>{{ app()->getLocale() === 'es' ? 'El cliente final ve widgets en blanco y reportes rotos.' : 'Clients see broken widgets and incomplete executive reports.' }}</span>
                        </li>
                    </ul>
                </div>
                <div class="text-xs font-mono text-red-700 dark:text-red-300 bg-red-500/10 p-3 rounded-lg border border-red-500/20">
                    STATUS: 429 Too Many Requests
                </div>
            </div>

            <!-- The APIs Hub Isolated Cluster Way -->
            <div class="p-6 sm:p-8 rounded-2xl glass-panel bg-brand-blue/5 border border-brand-blue/30 flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -top-12 -right-12 w-32 h-32 bg-brand-blue/10 rounded-full blur-2xl"></div>
                <div>
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-brand-blue mb-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-brand-blue animate-pulse"></span>
                        {{ app()->getLocale() === 'es' ? 'La Arquitectura APIs Hub (Cluster Persistente)' : 'The APIs Hub Way (Persistent Cluster)' }}
                    </div>
                    <h2 class="text-xl font-bold mb-4 text-slate-900 dark:text-white">
                        {{ app()->getLocale() === 'es' ? 'Sincronización automatizada por proyecto' : 'Scheduled Ingestion & Local Isolation' }}
                    </h2>
                    <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300 mb-6">
                        <li class="flex items-start gap-2">
                            <span class="text-brand-blue font-bold mt-0.5">&check;</span>
                            <span>{{ app()->getLocale() === 'es' ? 'Tus dashboards consultan tu cluster dedicado, no las APIs externas.' : 'Dashboards query your dedicated local cluster, never live APIs.' }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-brand-blue font-bold mt-0.5">&check;</span>
                            <span>{{ app()->getLocale() === 'es' ? 'Tiempos de respuesta de sub-segundo con matemática normalizada.' : 'Sub-second response times with reconciled metric definitions.' }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-brand-blue font-bold mt-0.5">&check;</span>
                            <span>{{ app()->getLocale() === 'es' ? 'Cero límites de cuota para clientes finales y reportes de equipo.' : 'Zero quota errors for executive viewers or shared links.' }}</span>
                        </li>
                    </ul>
                </div>
                <div class="text-xs font-mono text-brand-blue bg-brand-blue/10 p-3 rounded-lg border border-brand-blue/20">
                    STATUS: 200 OK — 14ms (Cache Hit)
                </div>
            </div>
        </section>

        <!-- Technical Deep Dive: The 3 Architectural Pillars -->
        <section class="w-full mb-16" aria-label="Technical Details">
            <h2 class="text-2xl sm:text-3xl font-extrabold mb-8 text-center text-slate-900 dark:text-white">
                {{ app()->getLocale() === 'es' ? 'Los 3 Pilares Técnicos de APIs Hub' : 'The 3 Technical Pillars of APIs Hub' }}
            </h2>

            <div class="space-y-6">
                <!-- Pillar 1 -->
                <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="w-8 h-8 rounded-lg bg-brand-blue/10 text-brand-blue font-bold flex items-center justify-center text-sm">01</span>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                            {{ app()->getLocale() === 'es' ? 'Aislamiento de Datos por Proyecto (Multi-Tenant Decoupled)' : 'Tenant Data Isolation per Project' }}
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
</x-layouts.public>

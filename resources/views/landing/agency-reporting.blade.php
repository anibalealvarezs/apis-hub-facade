<x-layouts.public 
    :title="app()->getLocale() === 'es' ? 'Soluciones para Agencias: Reportes Multi-Cliente Sin Caídas | APIs Hub' : 'Agency Solutions: Multi-Client Reporting Without Timeouts | APIs Hub'"
    :description="app()->getLocale() === 'es' ? 'Gestiona decenas de clientes con clusters aislados, separación entre proyectos y facturación, y reportes ejecutivos automatizados sin errores de cuota.' : 'Manage dozens of client portfolios with isolated data clusters, project vs billing separation, and zero connector timeouts.'"
    :keywords="__('agency client reporting, multi client marketing dashboard, agency billing profile, white label marketing reports, client data isolation, APIs Hub')"
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
            <span>{{ __('For Agencies') }}</span>
        </div>

        <!-- Hero -->
        <header class="text-left w-full mb-12 sm:mb-16">
            <h1 class="public-hero-title !text-left !text-3xl sm:!text-5xl mb-6">
                {{ app()->getLocale() === 'es' ? 'Reportes de clientes sin caídas de cuota ni conectores rotos' : 'Multi-Client Reporting Without Timeouts or Broken Connectors' }}
            </h1>
            <p class="public-hero-subtitle !text-left !text-lg sm:!text-xl text-slate-600 dark:text-slate-300 font-normal">
                {{ app()->getLocale() === 'es'
                    ? 'Gestiona decenas de marcas con clusters aislados por cliente, perfiles de facturación independientes y dashboards ejecutivos de alta velocidad.'
                    : 'Manage dozens of client brands with isolated project data clusters, independent billing profiles, and instant executive dashboards.' }}
            </p>
        </header>

        <!-- Agency Problems & How APIs Hub Solves Them -->
        <section class="w-full grid grid-cols-1 md:grid-cols-3 gap-6 mb-16" aria-label="Agency Challenges">
            <!-- Card 1 -->
            <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80 flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-brand-blue/10 text-brand-blue flex items-center justify-center font-bold text-lg mb-4">
                        &infin;
                    </div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">
                        {{ app()->getLocale() === 'es' ? 'Aislamiento Total entre Clientes' : 'Complete Client Data Isolation' }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                        {{ app()->getLocale() === 'es'
                            ? 'Cada cliente opera en su propio contenedor y base de datos persistente. Las consultas pesadas de una marca nunca ralentizan los reportes de las demás.'
                            : 'Each client portfolio operates inside its own isolated database container. Heavy reporting queries for one brand never degrade another client’s dashboards.' }}
                    </p>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80 flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-brand-teal/10 text-brand-teal flex items-center justify-center font-bold text-lg mb-4">
                        &dollar;
                    </div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">
                        {{ app()->getLocale() === 'es' ? 'Facturación Independiente por Proyecto' : 'Decoupled Billing Profiles' }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                        {{ app()->getLocale() === 'es'
                            ? 'Asigna perfiles fiscales y métodos de pago separados a cada proyecto. Puedes asumir la suscripción como agencia o delegarla directamente a la tarjeta de tu cliente.'
                            : 'Assign independent payment methods and tax billing profiles to distinct client projects. Cover infrastructure fees as an agency or pass invoices directly to clients.' }}
                    </p>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80 flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center font-bold text-lg mb-4">
                        &star;
                    </div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">
                        {{ app()->getLocale() === 'es' ? 'Blended ROAS y MER Normalizado' : 'True Cross-Channel Blended ROAS' }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                        {{ app()->getLocale() === 'es'
                            ? 'Mapea Meta Ads, Google Ads, Shopify y Klaviyo a un diccionario común de métricas. Entrega reportes con ROAS consolidado sin fórmulas frágiles de Google Sheets.'
                            : 'Reconcile Meta, Google, Shopify, and Klaviyo with normalized definitions. Deliver unified ROAS and MER reports without manual spreadsheet reconciliation.' }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Agency Workflow Featurette -->
        <section class="w-full p-8 sm:p-10 rounded-3xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80 mb-16">
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white mb-6">
                {{ app()->getLocale() === 'es' ? 'Cómo funciona el flujo de trabajo para agencias' : 'The Agency Workflow in Action' }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs sm:text-sm text-slate-600 dark:text-slate-300">
                <div class="flex gap-3 items-start">
                    <span class="w-6 h-6 rounded-full bg-brand-blue text-white flex-shrink-0 flex items-center justify-center text-xs font-bold">1</span>
                    <div>
                        <strong class="text-slate-900 dark:text-white block mb-0.5">{{ app()->getLocale() === 'es' ? 'Crea un proyecto por cliente' : 'Provision a client project' }}</strong>
                        {{ app()->getLocale() === 'es' ? 'Configura el subdominio dedicado y conecta las cuentas de Meta, Google, Shopify o Search Console del cliente.' : 'Spin up an isolated tenant cluster and authenticate client channels via secure OAuth flows.' }}
                    </div>
                </div>

                <div class="flex gap-3 items-start">
                    <span class="w-6 h-6 rounded-full bg-brand-blue text-white flex-shrink-0 flex items-center justify-center text-xs font-bold">2</span>
                    <div>
                        <strong class="text-slate-900 dark:text-white block mb-0.5">{{ app()->getLocale() === 'es' ? 'Sincronización automatizada' : 'Scheduled ingestion runs' }}</strong>
                        {{ app()->getLocale() === 'es' ? 'APIs Hub extrae métricas diarias y las consolida en el cluster local, aplicando el diccionario de normalización.' : 'The engine runs daily sync workers, normalizes platform quirks, and stages verified metrics.' }}
                    </div>
                </div>

                <div class="flex gap-3 items-start">
                    <span class="w-6 h-6 rounded-full bg-brand-blue text-white flex-shrink-0 flex items-center justify-center text-xs font-bold">3</span>
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
</x-layouts.public>

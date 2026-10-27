<x-layouts.public 
    :title="app()->getLocale() === 'es' ? 'Cómo Solucionar el Error de Cuota en Looker Studio con Google Search Console | APIs Hub' : 'How to Fix Looker Studio Google Search Console Quota Limit Errors | APIs Hub'"
    :description="app()->getLocale() === 'es' ? 'Guía técnica para resolver definitivamente el error de cuota agotada de Google Search Console y GA4 en Looker Studio mediante un cluster de datos con persistencia local.' : 'Step-by-step technical guide to permanently eliminate Google Search Console and GA4 API quota exceeded errors in Looker Studio using local data clustering.'"
    :keywords="__('looker studio google search console quota exceeded, looker studio quota limit fix, gsc api quota error, google analytics 4 quota exceeded, marketing data caching, APIs Hub')"
    :meta-image="asset('images/branding/apishub-620.png')"
    :og-type="'article'"
    :portals="$portals"
>
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
            <p class="text-slate-600 dark:text-slate-300 text-base sm:text-lg leading-relaxed">
                {{ app()->getLocale() === 'es'
                    ? 'Si administras paneles de Looker Studio conectados directamente a Google Search Console, probablemente te has encontrado con el temido error "Resource has been exhausted (e.g. check quota)". Aquí te explicamos por qué sucede y cómo resolverlo de raíz.'
                    : 'If you manage Looker Studio dashboards directly connected to Google Search Console, you have likely encountered "Resource has been exhausted (e.g. check quota)" errors. Here is why it happens and the architectural fix.' }}
            </p>
        </header>

        <!-- Technical Guide Sections -->
        <article class="w-full space-y-10 text-slate-700 dark:text-slate-300 text-sm sm:text-base leading-relaxed">
            
            <!-- Section 1: The Root Cause -->
            <section class="p-6 sm:p-8 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">
                    {{ app()->getLocale() === 'es' ? '1. Por qué ocurre este error' : '1. Why API Quotas Get Exhausted' }}
                </h2>
                <p class="mb-4">
                    {{ app()->getLocale() === 'es'
                        ? 'Google Search Console impone límites estrictos de cuota por minuto y por día para consultas vía API. Cada vez que abres un dashboard de Looker Studio, cambias un filtro de fecha o añades una dimensión, Looker lanza múltiples consultas simultáneas a la API de GSC.'
                        : 'Google Search Console enforces strict per-minute and per-day API limits. Every time an executive opens a Looker Studio report, changes a date range, or interacts with a filter, Looker executes concurrent requests directly against the GSC API.' }}
                </p>
                <div class="bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-300 p-4 rounded-xl font-mono text-xs">
                    Error: GSC API returned 429 Too Many Requests — Daily Quota Exceeded for Project.
                </div>
            </section>

            <!-- Section 2: Why Native Connectors Fail -->
            <section class="p-6 sm:p-8 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">
                    {{ app()->getLocale() === 'es' ? '2. Las limitaciones de los conectores directos' : '2. The Flaw of Live Connectors' }}
                </h2>
                <p class="mb-4">
                    {{ app()->getLocale() === 'es'
                        ? 'Los conectores convencionales (tanto el nativo de Google como conectores de terceros como Supermetrics) operan bajo el modelo "request-on-demand". No guardan una réplica estructurada de tus datos, sino que actúan como un simple puente. Si 5 personas de tu equipo revisan el dashboard al mismo tiempo, la cuota se agota en segundos.'
                        : 'Conventional connectors operate on an on-demand pass-through model. They do not store a persistent local replica of your metrics. If multiple team members open reports simultaneously, your project API token bucket empties in seconds.' }}
                </p>
            </section>

            <!-- Section 3: The Fix -->
            <section class="p-6 sm:p-8 rounded-2xl glass-panel bg-brand-blue/5 border border-brand-blue/30">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">
                    {{ app()->getLocale() === 'es' ? '3. La solución: Clusters de persistencia desacoplados' : '3. The Architectural Fix: Decoupled Data Clusters' }}
                </h2>
                <p class="mb-4">
                    {{ app()->getLocale() === 'es'
                        ? 'La única solución definitiva es desacoplar la ingesta de la visualización. En lugar de consultar la API en cada vista de página, un worker automatizado extrae los datos una vez al día fuera de horario laboral y los almacena en tu cluster dedicado de APIs Hub.'
                        : 'The definitive solution is decoupling data ingestion from visual querying. Automated background sync workers pull Search Console metrics once daily during off-peak hours and store them inside your dedicated APIs Hub project cluster.' }}
                </p>
                <p>
                    {{ app()->getLocale() === 'es'
                        ? 'Cuando Looker Studio, tu dashboard o tu asistente de IA consultan los datos, responden desde el cluster local en 10-20 milisegundos con cero consumo de cuota de Google.'
                        : 'When Looker Studio, custom dashboards, or AI agents query performance, requests resolve against your local database in 10-20 milliseconds with zero API token consumption.' }}
                </p>
            </section>
        </article>

        <!-- Bottom CTA -->
        <section class="w-full text-center p-8 sm:p-12 rounded-3xl glass-panel bg-gradient-to-b from-white/60 to-white/30 dark:from-slate-900/60 dark:to-slate-900/30 border border-slate-200 dark:border-slate-800 mt-16">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mb-4">
                {{ app()->getLocale() === 'es' ? 'Elimina los errores de cuota hoy mismo' : 'Eliminate Connector Quota Errors Today' }}
            </h2>
            <p class="text-base text-slate-600 dark:text-slate-300 max-w-xl mx-auto mb-6">
                {{ app()->getLocale() === 'es'
                    ? 'Conecta tus propiedades de Search Console y GA4 en tu primer cluster gratuito de APIs Hub.'
                    : 'Provision your dedicated data cluster and eliminate Search Console quota limits for good.' }}
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <span data-portal="{{ $portals['app'] }}" class="js-portal-link px-8 py-3 text-sm font-bold text-white bg-brand-blue rounded-xl hover:scale-105 active:scale-95 transition-all shadow-glow hover:shadow-glow-intense cursor-pointer">
                    {{ __('Try beta for free') }}
                </span>
                <a href="{{ app()->getLocale() === 'es' ? route('landing.architecture.es') : route('landing.architecture') }}" class="px-6 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:text-brand-blue transition-colors">
                    {{ app()->getLocale() === 'es' ? 'Leer sobre la arquitectura' : 'Explore Architecture' }} &rarr;
                </a>
            </div>
        </section>

    </main>
</x-layouts.public>

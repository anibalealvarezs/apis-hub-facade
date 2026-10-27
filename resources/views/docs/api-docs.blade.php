<x-layouts.public 
    :title="app()->getLocale() === 'es' ? 'Documentación de API y Especificación OpenAPI — APIs Hub' : 'API Documentation & OpenAPI Specification — APIs Hub'"
    :description="app()->getLocale() === 'es' ? 'Referencia pública completa y sandbox interactivo para la API de APIs Hub. Consultas analíticas multidimensionales, agregaciones, pings y métricas normalizadas.' : 'Complete public developer reference and interactive sandbox for the APIs Hub RESTful API. Multi-dimensional analytical queries, aggregations, pings, and normalized channel metrics.'"
    :meta-image="asset(app()->getLocale() === 'es' ? 'images/seo/pages/og-api-docs-es.webp' : 'images/seo/pages/og-api-docs.webp')"
    :og-type="'website'"
    :body-class="'bg-slate-50 dark:bg-slate-950'"
    :portals="$portals"
>
    <x-slot:extra-head>
        <link rel="stylesheet" href="{{ asset('css/scalar-theme.css') }}"/>
    </x-slot:extra-head>

    <!-- Main Content -->
    <main class="relative pt-28 sm:pt-36 pb-20 px-4 sm:px-8 lg:px-12 w-full flex-grow flex flex-col">
        
        <!-- Hero Header -->
        <div class="space-y-4 mb-8">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-brand-blue/10 text-brand-blue border border-brand-blue/20">
                <span class="w-2 h-2 rounded-full bg-brand-blue animate-pulse"></span>
                <span>{{ __('OpenAPI 3.1 Reference & Interactive Sandbox') }}</span>
            </div>
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="public-hero-title !text-left !text-3xl sm:!text-4xl lg:!text-5xl !mb-2">
                        {{ __('APIs Hub Developer Reference') }}
                    </h1>
                    <p class="text-slate-600 dark:text-slate-300 text-sm sm:text-base max-w-3xl mt-2 leading-relaxed">
                        {{ __('Direct programmatic access to normalized cross-channel advertising data, real-time sync telemetry, entity discovery, and high-speed multidimensional analytical aggregations.') }}
                    </p>
                </div>
                <!-- Spec Download Button -->
                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ $specUrl }}" target="_blank" download="{{ app()->getLocale() === 'es' ? 'apis-hub-openapi-es.json' : 'apis-hub-openapi.json' }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 hover:text-brand-blue hover:border-brand-blue/40 transition-all shadow-sm">
                        <svg class="w-4 h-4 text-brand-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>{{ __('Download OpenAPI JSON') }}</span>
                    </a>
                </div>
            </div>

            <!-- Topic Quick Navigation -->
            <div class="flex items-center gap-2 overflow-x-auto py-2 text-xs scrollbar-none border-b border-slate-200/80 dark:border-slate-800/80 text-slate-600 dark:text-slate-400">
                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ __('Key Topics:') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('🔐 Authentication (Master & User Keys)') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('👥 Scoped RBAC') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('💓 System Health') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('🔄 Data Sync') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('🔍 Assets Discovery') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('📄 Pagination & Sorting') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('📊 Aggregations') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('🌐 Omnichannel') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('⏱ Rate Limits') }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 hover:text-brand-blue cursor-default">{{ __('⚠️ Error Handling') }}</span>
            </div>
        </div>

        <!-- Scalar UI Interactive Reference Container -->
        <div class="scalar-container border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-slate-900/60 backdrop-blur-xl shadow-2xl">
            <script
                id="api-reference"
                data-url="{{ $specUrl }}"
            ></script>
            <script src="{{ asset('js/scalar-docs.js') }}"></script>
            <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference@latest"></script>
        </div>

    </main>
</x-layouts.public>

<x-layouts.public 
    :title="app()->getLocale() === 'es' ? 'Blog & Artículos Técnicos de Marketing Engineering | APIs Hub' : 'Blog & Technical Marketing Engineering Articles | APIs Hub'"
    :description="app()->getLocale() === 'es' ? 'Artículos técnicos, guías de infraestructura y soluciones a límites de cuotas de APIs, normalización cross-channel y analítica para agencias.' : 'Engineering articles, architectural breakdowns, API quota limit solutions, and cross-channel marketing data normalization for agencies.'"
    :keywords="__('marketing data blog, looker studio quota fix, cross-channel attribution, marketing api architecture, apis hub')"
    :meta-image="asset(app()->getLocale() === 'es' ? 'images/seo/pages/og-guia-looker-cuota.webp' : 'images/seo/pages/og-guide-looker-quota.webp')"
    :og-type="'website'"
    :portals="$portals"
>
    <main class="relative pt-32 sm:pt-40 pb-24 px-4 sm:px-8 max-w-6xl mx-auto flex flex-col items-center">
        
        <!-- Breadcrumbs / Kicker -->
        <div class="w-full flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-blue mb-4">
            <a href="{{ app()->getLocale() === 'es' ? '/es' : '/' }}" class="hover:underline">{{ __('Home') }}</a>
            <span>/</span>
            <span class="text-slate-400">{{ __('Blog') }}</span>
        </div>

        <!-- Page Hero -->
        <header class="text-left w-full mb-12 sm:mb-16">
            <h1 class="public-hero-title !text-left !text-3xl sm:!text-5xl mb-6">
                {{ app()->getLocale() === 'es' ? 'Ingeniería de Datos de Marketing & Artículos Técnicos' : 'Marketing Data Engineering & Technical Insights' }}
            </h1>
            <p class="public-hero-subtitle !text-left !text-lg sm:!text-xl text-slate-600 dark:text-slate-300 font-normal">
                {{ app()->getLocale() === 'es'
                    ? 'Soluciones profundas a problemas de cuotas de APIs, modelos de atribución cross-channel y arquitectura de datos para agencias de alto crecimiento.'
                    : 'Deep architectural breakdowns of ad platform API limits, cross-channel attribution models, and data pipeline solutions for scaling agencies.' }}
            </p>
        </header>

        <!-- Articles Feed Grid -->
        <section class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" aria-label="Articles Feed">
            @foreach($posts as $post)
                <article class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80 flex flex-col justify-between hover:border-brand-blue/50 transition-all glow-hover group">
                    <div>
                        <!-- Article Preview Image -->
                        @if(!empty($post['image']))
                            <a href="{{ $post['url'] }}" class="block overflow-hidden rounded-xl mb-5 aspect-[16/10] bg-slate-100 dark:bg-slate-800/60">
                                <img src="{{ $post['image'] }}" 
                                     alt="{{ $post['title'] }}" 
                                     width="800"
                                     height="500"
                                     loading="lazy" 
                                     class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-300">
                            </a>
                        @endif

                        <!-- Meta Info: Category & Reading Time -->
                        <div class="flex items-center gap-3 text-xs mb-3">
                            <span class="px-2.5 py-0.5 rounded-full font-bold bg-brand-blue/10 text-brand-blue border border-brand-blue/20 uppercase tracking-wider">
                                {{ $post['category'] }}
                            </span>
                            <span class="text-slate-400">&bull;</span>
                            <span class="text-slate-500 dark:text-slate-400 font-medium">
                                {{ $post['reading_time'] }}
                            </span>
                        </div>

                        <!-- Title -->
                        <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mb-3 group-hover:text-brand-blue transition-colors">
                            <a href="{{ $post['url'] }}">
                                {{ $post['title'] }}
                            </a>
                        </h2>

                        <!-- Excerpt -->
                        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-6">
                            {{ $post['excerpt'] }}
                        </p>
                    </div>

                    <!-- Card Footer: Tags & Read Link -->
                    <div class="pt-4 border-t border-slate-200/50 dark:border-slate-800/60 flex items-center justify-between">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @foreach(array_slice($post['tags'], 0, 2) as $tag)
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-md font-mono">
                                    #{{ $tag }}
                                </span>
                            @endforeach
                        </div>
                        <a href="{{ $post['url'] }}" class="inline-flex items-center gap-1 text-xs font-bold text-brand-blue group-hover:translate-x-1 transition-transform">
                            <span>{{ __('Read article') }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </article>
            @endforeach
        </section>

    </main>
</x-layouts.public>

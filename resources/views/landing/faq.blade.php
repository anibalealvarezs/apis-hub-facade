@php
    $isEs = app()->getLocale() === 'es';
    $canonicalUrl = url()->current();

    // Generate FAQPage schema.org JSON-LD
    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(function($faq) {
            return [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer'],
                ],
            ];
        }, $faqs),
    ];
@endphp

<x-layouts.public 
    :title="$isEs ? 'Preguntas Frecuentes (FAQ) | APIs Hub' : 'Frequently Asked Questions (FAQ) | APIs Hub'"
    :description="$isEs ? 'Respuestas claras sobre cuotas de Looker Studio, canales publicitarios soportados, normalización de datos y aislamiento para agencias.' : 'Clear technical answers about Looker Studio quota elimination, supported channels, client data isolation, and API access.'"
    :keywords="__('apis hub faq, marketing api frequently asked questions, looker studio quota faq, cross-channel normalization faq')"
    :og-type="'website'"
    :portals="$portals"
    :structured-data="json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)"
>
    <main class="relative pt-32 sm:pt-40 pb-24 px-4 sm:px-8 max-w-4xl mx-auto flex flex-col items-center">
        
        <!-- Breadcrumbs Navigation -->
        <nav aria-label="{{ __('Breadcrumb') }}" class="w-full mb-6">
            <ol class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-blue flex-wrap">
                <li>
                    <a href="{{ $isEs ? '/es' : '/' }}" class="hover:underline">{{ __('Home') }}</a>
                </li>
                <li class="text-slate-400" aria-hidden="true">/</li>
                <li class="text-slate-600 dark:text-slate-400" aria-current="page">FAQ</li>
            </ol>
        </nav>

        <!-- Hero Header -->
        <header class="text-left w-full mb-12 sm:mb-16">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-brand-blue/10 text-brand-blue border border-brand-blue/20 mb-4">
                <span class="w-2 h-2 rounded-full bg-brand-blue animate-pulse"></span>
                <span>{{ $isEs ? 'Centro de Respuestas Técnicas' : 'Engineering & Product Knowledge' }}</span>
            </div>
            <h1 class="public-hero-title !text-left !text-3xl sm:!text-5xl mb-6">
                {{ $isEs ? 'Preguntas Frecuentes' : 'Frequently Asked Questions' }}
            </h1>
            <p class="public-hero-subtitle !text-left !text-lg sm:!text-xl text-slate-600 dark:text-slate-300 font-normal">
                {{ $isEs 
                    ? 'Todo lo que necesitas saber sobre la arquitectura desacoplada, límites de cuotas de APIs y el ecosistema de APIs Hub.'
                    : 'Everything you need to know about our decoupled local data clusters, API quota relief, and agency infrastructure.' }}
            </p>
        </header>

        <!-- FAQ Accordion List -->
        <section class="w-full space-y-4" aria-label="Frequently Asked Questions">
            @foreach($faqs as $index => $faq)
                <div x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }" 
                     class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80 transition-all glow-hover">
                    <button @click="open = !open" 
                            class="w-full flex items-center justify-between text-left gap-4 font-bold text-base sm:text-lg text-slate-900 dark:text-white group">
                        <span class="group-hover:text-brand-blue transition-colors">
                            {{ $faq['question'] }}
                        </span>
                        <span class="p-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 shrink-0 group-hover:text-brand-blue transition-colors">
                            <svg x-show="!open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            <svg x-show="open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                        </span>
                    </button>
                    <div x-show="open" 
                         x-collapse
                         class="mt-4 pt-4 border-t border-slate-200/50 dark:border-slate-800/50 text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                        <p>{{ $faq['answer'] }}</p>
                    </div>
                </div>
            @endforeach
        </section>

        <!-- Bottom Assistance CTA -->
        <section class="w-full text-center p-8 sm:p-12 rounded-3xl glass-panel bg-gradient-to-b from-white/60 to-white/30 dark:from-slate-900/60 dark:to-slate-900/30 border border-slate-200 dark:border-slate-800 mt-16">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mb-4">
                {{ $isEs ? '¿Tienes una pregunta específica sobre tu stack?' : 'Have a specific question about your data stack?' }}
            </h2>
            <p class="text-base text-slate-600 dark:text-slate-300 max-w-xl mx-auto mb-6">
                {{ $isEs
                    ? 'Explora nuestra documentación interactiva de la API o pruébalo directamente con datos de muestra.'
                    : 'Check out our OpenAPI developer reference or test the live platform with realistic sample data.' }}
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="{{ $isEs ? route('docs.api.es') : route('docs.api') }}" class="px-8 py-3 text-sm font-bold text-white bg-brand-blue rounded-xl hover:scale-105 active:scale-95 transition-all shadow-glow hover:shadow-glow-intense">
                    {{ __('Explore API Docs') }}
                </a>
                <a href="{{ $isEs ? route('demo.data-sources.es') : route('demo.data-sources', ['lang' => 'en']) }}" class="px-6 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:text-brand-blue transition-colors">
                    {{ __('Try Demo Flow') }} &rarr;
                </a>
            </div>
        </section>

    </main>
</x-layouts.public>

@props([
    'title',
    'description',
    'keywords' => null,
    'metaImage' => null,
    'articleTitle',
    'articleSubtitle' => null,
    'breadcrumbTitle',
    'publishedAt' => '2026-10-27T18:37:05-04:00',
    'modifiedAt' => '2026-10-27T18:37:05-04:00',
    'readingTime' => null,
    'category' => null,
    'tags' => [],
    'authorName' => 'Aníbal Álvarez',
    'authorUrl' => 'https://anibalalvarez.com',
    'showFeaturedImage' => true,
    'featuredImage' => null,
    'featuredImageCaption' => null,
    'portals' => [],
])

@php
    $currentLocale = app()->getLocale();
    $isEs = $currentLocale === 'es';
    $canonicalUrl = url()->current();
    $readingTime = $readingTime ?? ($isEs ? '5 min de lectura' : '5 min read');
    $category = $category ?? ($isEs ? 'Ingeniería de Datos' : 'Data Engineering');
    $activeFeaturedImage = $featuredImage ?? $metaImage;

    // Generate Schema.org JSON-LD structured data for BlogPosting with BreadcrumbList
    $blogStructuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => $isEs ? 'Inicio' : 'Home',
                        'item' => $isEs ? url('/es') : url('/'),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => 'Blog',
                        'item' => $isEs ? route('landing.blog.index.es') : route('landing.blog.index'),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => $breadcrumbTitle ?? $articleTitle,
                        'item' => $canonicalUrl,
                    ],
                ],
            ],
            [
                '@type' => 'BlogPosting',
                '@id' => $canonicalUrl . '#article',
                'isPartOf' => [
                    '@type' => 'WebPage',
                    '@id' => $canonicalUrl,
                ],
                'headline' => $articleTitle,
                'description' => $description,
                'url' => $canonicalUrl,
                'inLanguage' => $currentLocale,
                'mainEntityOfPage' => $canonicalUrl,
                'datePublished' => $publishedAt,
                'dateModified' => $modifiedAt,
                'image' => $metaImage,
                'author' => [
                    '@type' => 'Person',
                    'name' => $authorName,
                    'url' => $authorUrl,
                ],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => 'APIs Hub Network',
                    'url' => url('/'),
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => asset('images/branding/apishub-trans-620.webp'),
                    ],
                ],
                'articleSection' => $category,
                'keywords' => is_array($tags) && count($tags) ? implode(', ', $tags) : $keywords,
            ],
        ],
    ];
@endphp

<x-layouts.public 
    :title="$title"
    :description="$description"
    :keywords="$keywords"
    :meta-image="$metaImage"
    :og-type="'article'"
    :portals="$portals"
    :structured-data="json_encode($blogStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)"
>
    <main class="relative pt-32 sm:pt-40 pb-24 px-4 sm:px-8 max-w-4xl mx-auto flex flex-col items-center">
        
        <!-- Breadcrumbs Navigation -->
        <nav aria-label="{{ __('Breadcrumb') }}" class="w-full mb-6">
            <ol class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-blue flex-wrap">
                <li>
                    <a href="{{ $isEs ? '/es' : '/' }}" class="hover:underline">{{ __('Home') }}</a>
                </li>
                <li class="text-slate-400" aria-hidden="true">/</li>
                <li>
                    <a href="{{ $isEs ? route('landing.blog.index.es') : route('landing.blog.index') }}" class="hover:underline">Blog</a>
                </li>
                <li class="text-slate-400" aria-hidden="true">/</li>
                <li class="text-slate-600 dark:text-slate-400 line-clamp-1" aria-current="page">
                    {{ $breadcrumbTitle ?? $articleTitle }}
                </li>
            </ol>
        </nav>

        <!-- Article Header -->
        <header class="text-left w-full mb-10 pb-6 border-b border-slate-200/60 dark:border-slate-800/80">
            <!-- Meta Bar: Category, Date, Reading Time -->
            <div class="flex items-center gap-3 text-xs mb-4 flex-wrap">
                <span class="px-3 py-1 rounded-full font-bold bg-brand-blue/10 text-brand-blue border border-brand-blue/20 uppercase tracking-wider">
                    {{ $category }}
                </span>
                <span class="text-slate-400" aria-hidden="true">&bull;</span>
                <time datetime="{{ $publishedAt }}" class="text-slate-500 dark:text-slate-400 font-medium">
                    {{ \Carbon\Carbon::parse($publishedAt)->locale($currentLocale)->translatedFormat('F j, Y') }}
                </time>
                <span class="text-slate-400" aria-hidden="true">&bull;</span>
                <span class="text-slate-500 dark:text-slate-400 font-medium">
                    {{ $readingTime }}
                </span>
            </div>

            <!-- Main Title -->
            <h1 class="public-hero-title !text-left !text-3xl sm:!text-4xl lg:!text-5xl mb-4">
                {{ $articleTitle }}
            </h1>

            <!-- Subtitle / Hook -->
            @if($articleSubtitle)
                <p class="text-slate-600 dark:text-slate-300 text-base sm:text-lg leading-relaxed">
                    {{ $articleSubtitle }}
                </p>
            @endif
        </header>

        <!-- Featured Image Section (Enabled by default, configurable via :show-featured-image) -->
        @if($showFeaturedImage && !empty($activeFeaturedImage))
            <section class="w-full mb-12" aria-label="{{ __('Featured Image') }}">
                <figure class="overflow-hidden rounded-2xl glass-panel border border-slate-200/60 dark:border-slate-800/80 shadow-sm">
                    <img src="{{ $activeFeaturedImage }}" 
                         alt="{{ $articleTitle }}" 
                         class="w-full h-auto aspect-[16/10] object-cover object-center" 
                         loading="eager"
                         width="1500" 
                         height="991">
                    @if(!empty($featuredImageCaption))
                        <figcaption class="px-4 py-2.5 text-xs text-center text-slate-500 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-900/50 border-t border-slate-200/50 dark:border-slate-800/50">
                            {{ $featuredImageCaption }}
                        </figcaption>
                    @endif
                </figure>
            </section>
        @endif

        <!-- Read-Only Article Body Slot -->
        <article class="w-full space-y-10 text-slate-700 dark:text-slate-300 text-sm sm:text-base leading-relaxed">
            {{ $slot }}
        </article>

        <!-- Article Tags Footer -->
        @unless(empty($tags))
            <div class="w-full mt-10 pt-6 border-t border-slate-200/60 dark:border-slate-800/80 flex items-center gap-2 flex-wrap text-xs text-slate-500">
                <span class="font-bold text-slate-700 dark:text-slate-300">{{ __('Topics:') }}</span>
                @foreach($tags as $tag)
                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 font-mono">
                        #{{ $tag }}
                    </span>
                @endforeach
            </div>
        @endunless

        <!-- Bottom Return & CTA -->
        <section class="w-full text-center p-8 sm:p-12 rounded-3xl glass-panel bg-gradient-to-b from-white/60 to-white/30 dark:from-slate-900/60 dark:to-slate-900/30 border border-slate-200 dark:border-slate-800 mt-16">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mb-4">
                {{ $isEs ? 'Elimina los errores de cuota hoy mismo' : 'Eliminate Connector Quota Errors Today' }}
            </h2>
            <p class="text-base text-slate-600 dark:text-slate-300 max-w-xl mx-auto mb-6">
                {{ $isEs
                    ? 'Conecta tus propiedades de Search Console y GA4 en tu primer cluster gratuito de APIs Hub.'
                    : 'Provision your dedicated data cluster and eliminate Search Console quota limits for good.' }}
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <span data-portal="{{ $portals['app'] ?? '' }}" class="js-portal-link px-8 py-3 text-sm font-bold text-white bg-brand-blue rounded-xl hover:scale-105 active:scale-95 transition-all shadow-glow hover:shadow-glow-intense cursor-pointer">
                    {{ __('Try beta for free') }}
                </span>
                <a href="{{ $isEs ? route('landing.architecture.es') : route('landing.architecture') }}" class="px-6 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:text-brand-blue transition-colors">
                    {{ $isEs ? 'Leer sobre la arquitectura' : 'Explore Architecture' }} &rarr;
                </a>
            </div>
        </section>

    </main>
</x-layouts.public>

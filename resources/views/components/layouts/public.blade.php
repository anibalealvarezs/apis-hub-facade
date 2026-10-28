@props([
    'title' => null,
    'description' => null,
    'keywords' => null,
    'metaImage' => null,
    'canonicalUrl' => null,
    'alternateEn' => null,
    'alternateEs' => null,
    'alternateDefault' => null,
    'ogType' => 'website',
    'bodyClass' => null,
    'xData' => 'themeControl',
    'xInit' => null,
    'gtmId' => null,
    'showHeader' => true,
    'showFooter' => true,
    'showMesh' => true,
    'portals' => [],
    'extraHead' => null,
    'extraScripts' => null,
    'structuredData' => null,
])

@php
    $currentLocale = app()->getLocale();
    $isEs = $currentLocale === 'es';

    // Fallbacks for SEO metadata
    $pageTitle = $title ?? ($isEs ? 'APIs Hub | Analítica y Dashboards de Marketing' : 'APIs Hub | Normalized Marketing Data Engine for Agencies');
    $pageDesc = $description ?? ($isEs 
        ? 'Plataforma desacoplada de analítica de marketing, sincronización multi-canal y dashboards unificados.'
        : 'Decoupled marketing analytics, multi-channel synchronization, and unified dashboard platform.');
    $pageImage = $metaImage ?? asset('images/branding/apishub-620.png');
    $pageCanonical = $canonicalUrl ?? url()->current();

    // Resolving route alternates if not passed explicitly
    $routeName = request()->route() ? request()->route()->getName() : null;
    if (!$alternateEn && !$alternateEs && $routeName) {
        $baseRoute = str_replace('.es', '', $routeName);
        if (\Illuminate\Support\Facades\Route::has($baseRoute)) {
            $alternateEn = route($baseRoute);
        }
        if (\Illuminate\Support\Facades\Route::has($baseRoute . '.es')) {
            $alternateEs = route($baseRoute . '.es');
        }
        $alternateDefault = $alternateEn;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $currentLocale) }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Anti-Flicker Synchronous Early Theme Init -->
    <script>
        (function() {
            var theme = localStorage.getItem('color-theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (theme === 'dark' || (!theme && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    
    <!-- Preconnect / DNS Prefetch -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Primary Meta Tags -->
    <title>{{ $pageTitle }}</title>
    <meta name="title" content="{{ $pageTitle }}">
    <meta name="description" content="{{ $pageDesc }}">
    @if($keywords)
        <meta name="keywords" content="{{ $keywords }}">
    @endif
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="author" content="APIs Hub Network">
    <meta name="application-name" content="APIs Hub">
    <meta name="theme-color" content="#0f172a">
    <meta name="color-scheme" content="dark light">

    <!-- Favicons -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/apishub-favicon.png') }}">
    <link rel="icon" type="image/webp" href="{{ asset('images/branding/apishub-favicon.webp') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/branding/apishub-favicon.png') }}">

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ $pageCanonical }}" />

    <!-- Multilingual Alternate Links (hreflang) -->
    @if($alternateEn)
        <link rel="alternate" hreflang="en" href="{{ $alternateEn }}" />
    @endif
    @if($alternateEs)
        <link rel="alternate" hreflang="es" href="{{ $alternateEs }}" />
    @endif
    @if($alternateDefault)
        <link rel="alternate" hreflang="x-default" href="{{ $alternateDefault }}" />
    @endif

    <!-- Open Graph / Social Sharing -->
    <meta property="og:site_name" content="APIs Hub">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:url" content="{{ $pageCanonical }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDesc }}">
    @php
        $isOgCustom = $pageImage && !str_contains($pageImage, 'apishub-620.png');
        $imgWidth = $isOgCustom ? '1500' : '620';
        $imgHeight = $isOgCustom ? '991' : '135';
    @endphp
    <meta property="og:image" content="{{ $pageImage }}">
    <meta property="og:image:width" content="{{ $imgWidth }}">
    <meta property="og:image:height" content="{{ $imgHeight }}">
    <meta property="og:locale" content="{{ $isEs ? 'es_ES' : 'en_US' }}">
    <meta property="og:locale:alternate" content="{{ $isEs ? 'en_US' : 'es_ES' }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $pageCanonical }}">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDesc }}">
    <meta name="twitter:image" content="{{ $pageImage }}">

    <!-- Structured Data (JSON-LD) -->
    @if($structuredData)
        @if(str_contains($structuredData, '<script'))
            {!! $structuredData !!}
        @else
            <script type="application/ld+json">
            {!! $structuredData !!}
            </script>
        @endif
    @else
        <script type="application/ld+json">
        {
          "@@context": "https://schema.org",
          "@@type": "WebPage",
          "name": "{{ $pageTitle }}",
          "url": "{{ $pageCanonical }}",
          "inLanguage": "{{ $currentLocale }}",
          "description": "{{ $pageDesc }}",
          "publisher": {
            "@@type": "Organization",
            "name": "APIs Hub Network",
            "url": "{{ url('/') }}",
            "logo": {
              "@@type": "ImageObject",
              "url": "{{ asset('images/branding/apishub-trans-620.webp') }}"
            }
          }
        }
        </script>
    @endif

    <!-- Base App Assets (Vite) -->
    @vite(['resources/js/theme.js', 'resources/css/app.css', 'resources/js/app.js'])

    {{ $extraHead ?? '' }}
</head>
<body class="antialiased min-h-screen flex flex-col justify-between text-slate-900 dark:text-slate-100 selection:bg-brand-blue selection:text-white relative {{ $bodyClass ?? 'bg-slate-50 dark:bg-slate-900' }}"
      @if($xData) x-data="{{ $xData }}" @endif
      @if($xInit) x-init="{{ $xInit }}" @endif
      @if($gtmId) data-gtm-id="{{ $gtmId }}" @endif>

    <!-- Unified Sticky Public Header -->
    @if($showHeader)
        <x-public.header :portals="$portals" />
    @endif

    <!-- Optional Sub-navigation (e.g. Demo Stepper) -->
    {{ $stepper ?? '' }}

    <!-- Page Main Content Slot -->
    {{ $slot }}

    <!-- Semantic 3-Column Footer -->
    @if($showFooter)
        <x-public.footer />
    @endif

    <!-- Background Mesh -->
    @if($showMesh)
        <div class="hero-mesh" aria-hidden="true"></div>
    @endif

    {{ $extraScripts ?? '' }}

    @if($gtmId)
        @vite(['resources/js/gtm.js'])
    @endif
</body>
</html>

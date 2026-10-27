<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | APIs Hub</title>
    @vite(['resources/js/theme.js', 'resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://www.googletagmanager.com">
    <link rel="preconnect" href="https://www.google.com">
    <!-- Google Fonts: Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @php
        $routeName = request()->route() ? request()->route()->getName() : null;
        $baseRoute = $routeName ? str_replace('.es', '', $routeName) : null;
        $isEs = app()->getLocale() === 'es';
        $metaDescription = $isEs 
            ? 'Documentación legal, términos de servicio y políticas de privacidad y cumplimiento para la plataforma APIs Hub.'
            : 'Legal, terms of service, privacy policies, and compliance documentation for the APIs Hub platform.';
    @endphp

    <meta name="description" content="{{ $metaDescription }}">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}" />

    @if($baseRoute)
        <!-- Localization / Hreflang -->
        <link rel="alternate" hreflang="en" href="{{ route($baseRoute) }}" />
        <link rel="alternate" hreflang="es" href="{{ route($baseRoute . '.es') }}" />
        <link rel="alternate" hreflang="x-default" href="{{ route($baseRoute) }}" />
    @endif

    <!-- Open Graph / Facebook -->
    <meta property="og:site_name" content="APIs Hub">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title') | APIs Hub">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ asset('images/branding/apishub-620.png') }}">
    <meta property="og:image:width" content="620">
    <meta property="og:image:height" content="135">
    <meta property="og:locale" content="{{ $isEs ? 'es_ES' : 'en_US' }}">
    <meta property="og:locale:alternate" content="{{ $isEs ? 'en_US' : 'es_ES' }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="@yield('title') | APIs Hub">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ asset('images/branding/apishub-620.png') }}">

    <!-- Structured Data -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "WebPage",
      "name": "@yield('title') | APIs Hub",
      "url": "{{ url()->current() }}",
      "inLanguage": "{{ app()->getLocale() }}",
      "description": "{{ $metaDescription }}",
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
</head>
<body class="antialiased min-h-screen flex flex-col justify-between text-slate-900 dark:text-slate-100 selection:bg-brand-blue selection:text-white" 
      x-data="themeControl">
    
    <!-- Unified Header Navigation -->
    @include('components.public.header')

    <!-- Main Content Flow -->
    <main class="relative pt-36 sm:pt-44 pb-24 px-6 sm:px-8 flex-grow flex flex-col items-center">
        <div class="w-full max-w-4xl legal-document">
            @yield('content')
        </div>
    </main>

    <!-- Semantic Footer / Micro Branding -->
    @include('components.public.footer')
    
    <!-- Background Mesh -->
    <div class="hero-mesh" aria-hidden="true"></div>

    @vite(['resources/js/gtm.js'])
</body>
</html>

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

    <meta name="description" content="Legal and compliance documentation for the APIs Hub platform.">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}" />

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title') | APIs Hub">
    <meta property="og:description" content="Legal and compliance documentation for the APIs Hub platform.">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="@yield('title') | APIs Hub">
    <meta property="twitter:description" content="Legal and compliance documentation for the APIs Hub platform.">

    @if(request()->route() && request()->route()->getName())
        <!-- Localization -->
        <link rel="alternate" hreflang="en" href="{{ route(request()->route()->getName(), ['locale' => null]) }}" />
        <link rel="alternate" hreflang="es" href="{{ route(request()->route()->getName(), ['locale' => 'es']) }}" />
        <link rel="alternate" hreflang="x-default" href="{{ route(request()->route()->getName(), ['locale' => null]) }}" />
    @endif

    <!-- Structured Data -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "WebPage",
      "name": "@yield('title') | APIs Hub",
      "url": "{{ url()->current() }}",
      "publisher": {
        "@@type": "Organization",
        "name": "APIs Hub Network"
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

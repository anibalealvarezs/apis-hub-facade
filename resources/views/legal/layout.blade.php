<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="themeControl" 
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | APIs Hub</title>
    @vite(['resources/js/theme.js', 'resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
</head>
<body class="antialiased min-h-screen bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-500">
    
    <nav class="fixed top-0 w-full z-50 border-b border-slate-200 dark:border-white/5 bg-white/80 dark:bg-slate-950/80 backdrop-blur-xl h-24">
        <div class="max-w-7xl mx-auto px-8 h-full flex items-center justify-between">
            <a href="/" class="hover:opacity-80 transition-all flex items-center">
                <img src="{{ asset('images/branding/apishub-trans-620.webp') }}" 
                     alt="APIs Hub" class="h-8" 
                     x-show="!darkMode" x-cloak>
                <img src="{{ asset('images/branding/apishub-trans-light-620.webp') }}" 
                     alt="APIs Hub" class="h-8" 
                     x-show="darkMode" x-cloak>
            </a>
            
            <div class="flex items-center">
                <button @click="darkMode = !darkMode" class="p-2 rounded-full border border-slate-200 dark:border-slate-800 mr-8">
                    <span x-show="darkMode" x-cloak>☀️</span>
                    <span x-show="!darkMode" x-cloak>🌙</span>
                </button>
                <a href="/" class="text-[10px] font-bold tracking-[0.2em] text-slate-500 uppercase hover:text-brand-blue transition-colors">
                    Back to Home
                </a>
            </div>
        </div>
    </nav>

    <!-- Content Spacer to avoid overlap -->
    <div class="h-32"></div>

    <main class="relative pb-24 px-8">
        <div class="max-w-4xl mx-auto">
            @yield('content')
        </div>
    </main>

    <footer class="border-t border-slate-100 dark:border-white/5 py-12 px-8">
        <div class="max-w-7xl mx-auto flex flex-col items-center">
            <!-- Separated Links with MX -->
            <div class="flex items-center justify-center mb-6 text-[10px] uppercase tracking-[0.3em] font-bold text-slate-400">
                <a href="/privacy" class="mx-6 hover:text-brand-blue transition-colors">Privacy</a>
                <span class="w-1 h-1 bg-brand-blue rounded-full"></span>
                <a href="/tos" class="mx-6 hover:text-brand-blue transition-colors">Terms</a>
                <span class="w-1 h-1 bg-brand-teal rounded-full"></span>
                <a href="/data-deletion" class="mx-6 hover:text-brand-blue transition-colors">Data Deletion</a>
            </div>
            
            <div class="text-[10px] uppercase tracking-[0.3em] font-bold text-slate-400 opacity-80">
                Engineered by <a href="https://anibalalvarez.com" target="_blank" class="hover:text-brand-blue transition-colors">Aníbal Álvarez</a> for the APIs Hub Network. (v1.0)
            </div>
        </div>
    </footer>

    <div class="hero-mesh opacity-20 dark:opacity-10"></div>
</body>
</html>

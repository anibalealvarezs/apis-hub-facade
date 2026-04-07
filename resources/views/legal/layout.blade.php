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
    
    <nav class="fixed top-0 w-full z-50 border-b border-slate-200 dark:border-white/5 bg-white/80 dark:bg-slate-950/80 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto px-8 h-24 flex items-center justify-between">
            <a href="/" class="hover:opacity-80 transition-all hover:scale-105 active:scale-95 flex items-center gap-3">
                <img src="{{ asset('images/branding/apishub-trans-620.webp') }}" 
                     alt="APIs Hub" class="h-10" 
                     x-show="!darkMode" x-cloak>
                <img src="{{ asset('images/branding/apishub-trans-light-620.webp') }}" 
                     alt="APIs Hub" class="h-10" 
                     x-show="darkMode" x-cloak>
            </a>
            
            <div class="flex items-center gap-8">
                <button @click="darkMode = !darkMode" class="p-2.5 rounded-full border border-slate-200 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md transition-all hover:shadow-glow">
                    <span x-show="darkMode" x-cloak class="text-yellow-400">☀️</span>
                    <span x-show="!darkMode" x-cloak class="text-slate-700">🌙</span>
                </button>
                <a href="/" class="text-sm font-bold tracking-wider text-slate-500 hover:text-brand-blue transition-colors uppercase underline-offset-8 hover:underline">
                    Back to Home
                </a>
            </div>
        </div>
    </nav>

    <main class="relative pt-48 pb-32 px-8">
        <article class="max-w-4xl mx-auto prose prose-slate dark:prose-invert prose-brand prose-lg">
            @yield('content')
        </article>
    </main>

    <footer class="border-t border-slate-100 dark:border-white/5 py-16 px-8 bg-slate-50/50 dark:bg-slate-950/50 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto flex flex-col items-center gap-8 text-[11px] uppercase tracking-[0.4em] font-black text-slate-400 dark:text-slate-500">
            <div class="flex flex-wrap justify-center items-center gap-12">
                <a href="/privacy" class="hover:text-brand-blue transition-colors">Privacy Policy</a>
                <span class="w-1.5 h-1.5 bg-brand-blue/30 dark:bg-brand-blue/20 rounded-full"></span>
                <a href="/tos" class="hover:text-brand-blue transition-colors">Terms of Service</a>
                <span class="w-1.5 h-1.5 bg-brand-teal/30 dark:bg-brand-teal/20 rounded-full"></span>
                <a href="/data-deletion" class="hover:text-brand-blue transition-colors">Data Deletion</a>
            </div>
            <div class="opacity-80">
                Engineered by <a href="https://anibalalvarez.com" target="_blank" class="hover:text-brand-blue transition-colors">Aníbal Álvarez</a> for the APIs Hub Network. (v1.0)
            </div>
        </div>
    </footer>

    <div class="hero-mesh opacity-30 dark:opacity-20 animate-pulse"></div>
</body>
</html>

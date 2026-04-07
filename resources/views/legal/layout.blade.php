<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | APIs Hub</title>
    
    <!-- Sync theme init to avoid flash -->
    @vite(['resources/js/theme.js'])
    
    <!-- All other assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
</head>
<body class="antialiased min-h-screen bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-500" 
      x-data="themeControl">
    
    <nav class="fixed top-0 w-full z-50 border-b border-slate-200 dark:border-white/5 bg-white/80 dark:bg-slate-950/80 backdrop-blur-xl h-24">
        <div class="max-w-7xl mx-auto px-8 h-full flex items-center justify-between">
            <a href="/" class="hover:opacity-80 transition-all flex items-center">
                <!-- Reacts to Alpine darkMode -->
                <img src="{{ asset('images/branding/apishub-trans-620.webp') }}" 
                     alt="APIs Hub" class="h-8" 
                     x-show="!darkMode" x-cloak>
                <img src="{{ asset('images/branding/apishub-trans-light-620.webp') }}" 
                     alt="APIs Hub" class="h-8" 
                     x-show="darkMode" x-cloak>
            </a>
            
            <div class="flex items-center">
                <button @click="darkMode = !darkMode" class="p-2.5 rounded-full border border-slate-200 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md mr-8 hover:shadow-glow transition-all">
                    <span x-show="darkMode" x-cloak class="text-yellow-400">☀️</span>
                    <span x-show="!darkMode" x-cloak class="text-slate-700">🌙</span>
                </button>
                <a href="/" class="text-[10px] font-black tracking-[0.3em] text-slate-500 uppercase hover:text-brand-blue transition-colors underline-offset-8 hover:underline">
                    Back to Home
                </a>
            </div>
        </div>
    </nav>

    <!-- Content Spacer -->
    <div class="h-32"></div>

    <main class="relative pb-24 px-8">
        <div class="max-w-4xl mx-auto prose prose-slate dark:prose-invert prose-brand prose-lg">
            @yield('content')
        </div>
    </main>

    <footer class="border-t border-slate-100 dark:border-white/5 py-16 px-8 bg-slate-50/50 dark:bg-slate-950/50 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto flex flex-col items-center">
            <div class="flex items-center justify-center mb-8 text-[10px] uppercase tracking-[0.4em] font-black text-slate-400 dark:text-slate-500">
                <a href="/privacy" class="mx-6 hover:text-brand-blue transition-colors">Privacy</a>
                <span class="w-1.5 h-1.5 bg-brand-blue/30 rounded-full"></span>
                <a href="/tos" class="mx-6 hover:text-brand-blue transition-colors">Terms</a>
                <span class="w-1.5 h-1.5 bg-brand-teal/30 rounded-full"></span>
                <a href="/data-deletion" class="mx-6 hover:text-brand-blue transition-colors">Data Deletion</a>
            </div>
            
            <div class="text-[10px] uppercase tracking-[0.4em] font-black text-slate-400 dark:text-slate-500 opacity-80">
                Engineered by <a href="https://anibalalvarez.com" target="_blank" class="hover:text-brand-blue transition-colors underline-offset-4 hover:underline">Aníbal Álvarez</a> for the APIs Hub Network. (v1.0)
            </div>
        </div>
    </footer>

    <div class="hero-mesh opacity-20 dark:opacity-10"></div>
</body>
</html>

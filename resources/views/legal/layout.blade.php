<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | APIs Hub</title>
    
    <!-- Use Vite for Assets (CSS, Global JS, Theme Init) -->
    @vite(['resources/js/theme.js', 'resources/css/app.css', 'resources/js/app.js'])
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
</head>
<body class="antialiased min-h-screen text-slate-900 dark:text-slate-100 selection:bg-brand-blue selection:text-white" 
      x-data="themeControl">
    
    <!-- Theme Toggle / UI Navigation (Faithful Positioning) -->
    <div class="fixed top-8 left-8 z-50">
        <a href="/" class="hover:opacity-80 transition-all block">
            <img src="{{ asset('images/branding/apishub-trans-620.webp') }}?v=1.3" 
                 alt="APIs Hub" class="h-10 md:h-12" 
                 :class="darkMode ? 'hidden' : 'block'">
            <img src="{{ asset('images/branding/apishub-trans-light-620.webp') }}?v=1.3" 
                 alt="APIs Hub" class="h-10 md:h-12" 
                 :class="darkMode ? 'block' : 'hidden'">
        </a>
    </div>

    <div class="theme-toggle" x-cloak>
        <div class="fixed top-8 right-8 flex items-center gap-6 z-50">
            <a href="/" class="text-[10px] font-bold tracking-[0.3em] text-slate-400 hover:text-brand-blue transition-colors uppercase pointer-events-auto">
                Back to Home
            </a>
            <button @click="darkMode = !darkMode" class="p-2 rounded-full border border-slate-200 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md glow-hover">
                <template x-if="darkMode">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </template>
                <template x-if="!darkMode">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </template>
            </button>
        </div>
    </div>

    <!-- Main Section -->
    <main class="relative pt-40 pb-20 px-8">
        <div class="w-full max-w-4xl mx-auto
            [&>h1]:text-6xl [&>h1]:md:text-8xl [&>h1]:font-extrabold [&>h1]:mb-16 [&>h1]:unicorn-title [&>h1]:tracking-tight
            [&>h2]:text-3xl [&>h2]:font-bold [&>h2]:mt-20 [&>h2]:mb-8 [&>h2]:text-brand-blue
            [&>p]:text-xl [&>p]:leading-relaxed [&>p]:text-slate-800 [&>p]:dark:text-slate-400 [&>p]:font-light [&>p]:mb-10
            [&>ul]:list-disc [&>ul]:ml-8 [&>ul]:mb-10 [&>ul]:text-slate-800 [&>ul]:dark:text-slate-400 [&>ul]:font-light [&>ul]:space-y-4
            [&>ol]:list-decimal [&>ol]:ml-8 [&>ol]:mb-10 [&>ol]:text-slate-800 [&>ol]:dark:text-slate-400 [&>ol]:font-light [&>ol]:space-y-4
            [&>hr]:border-slate-200 [&>hr]:dark:border-white/5 [&>hr]:my-16">
            @yield('content')
        </div>
    </main>

    <!-- Footer: Exact copy lines 147-158 of Welcome -->
    <div class="fixed bottom-8 w-full flex flex-col items-center gap-4 px-8 text-[10px] uppercase tracking-[0.3em] font-bold text-slate-400 dark:text-slate-500 select-none pointer-events-none">
        <div class="flex items-center justify-center opacity-70">
            <a href="/privacy" class="mx-6 pointer-events-auto hover:text-brand-blue transition-colors">Privacy</a>
            <span class="w-1 h-1 bg-brand-blue/30 dark:bg-brand-blue/20 rounded-full"></span>
            <a href="/tos" class="mx-6 pointer-events-auto hover:text-brand-blue transition-colors">Terms</a>
            <span class="w-1 h-1 bg-brand-teal/30 dark:bg-brand-teal/20 rounded-full"></span>
            <a href="/data-deletion" class="mx-6 pointer-events-auto hover:text-brand-blue transition-colors">Data Deletion</a>
        </div>
        <div class="opacity-80">
            Engineered by <a href="https://anibalalvarez.com" target="_blank" class="pointer-events-auto hover:text-brand-blue transition-colors underline-offset-4 hover:underline">Aníbal Álvarez</a> for the APIs Hub Network. (v1.0)
        </div>
    </div>
    
    <!-- Background Mesh: The Soul of the Design -->
    <div class="hero-mesh"></div>

    <!-- Analytics -->
    @vite(['resources/js/gtm.js'])
</body>
</html>

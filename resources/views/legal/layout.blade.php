<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | APIs Hub</title>
    
    <!-- Sync init -->
    @vite(['resources/js/theme.js'])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
</head>
<body class="antialiased min-h-screen transition-colors duration-500 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 selection:bg-brand-blue selection:text-white" 
      x-data="themeControl">
    
    <!-- Branding Header: Fixed Top Left -->
    <div class="fixed top-8 left-8 z-50">
        <a href="/" class="hover:opacity-80 transition-all block">
            <img src="{{ asset('images/branding/apishub-trans-620.webp') }}?v=1.3" 
                 alt="APIs Hub" class="h-10 md:h-12" 
                 x-show="!darkMode" x-cloak>
            <img src="{{ asset('images/branding/apishub-trans-light-620.webp') }}?v=1.3" 
                 alt="APIs Hub" class="h-10 md:h-12" 
                 x-show="darkMode" x-cloak>
        </a>
    </div>

    <!-- Theme Toggle: Fixed Top Right -->
    <div class="fixed top-8 right-8 z-50 flex items-center gap-6" x-cloak>
        <a href="/" class="text-[10px] font-black tracking-[0.3em] text-slate-400 hover:text-brand-blue transition-colors uppercase">
            Back to Home
        </a>
        <button @click="darkMode = !darkMode" class="p-2.5 rounded-full border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md shadow-sm hover:shadow-glow transition-all">
            <span x-show="darkMode" class="text-yellow-400">☀️</span>
            <span x-show="!darkMode" class="text-slate-700">🌙</span>
        </button>
    </div>

    <!-- Main Content Stream -->
    <main class="relative pt-40 pb-20 px-8">
        <div class="max-w-4xl mx-auto
            [&>h1]:text-5xl [&>h1]:md:text-7xl [&>h1]:font-black [&>h1]:mb-16 [&>h1]:unicorn-title [&>h1]:tracking-tighter
            [&>h2]:text-3xl [&>h2]:font-extrabold [&>h2]:mt-20 [&>h2]:mb-8 [&>h2]:text-brand-blue [&>h2]:tracking-tight
            [&>p]:text-lg [&>p]:leading-relaxed [&>p]:text-slate-700 [&>p]:dark:text-slate-400 [&>p]:font-light [&>p]:mb-8
            [&>ul]:list-disc [&>ul]:ml-8 [&>ul]:mb-10 [&>ul]:text-slate-700 [&>ul]:dark:text-slate-400 [&>ul]:font-light [&>ul]:space-y-4
            [&>ol]:list-decimal [&>ol]:ml-8 [&>ol]:mb-10 [&>ol]:text-slate-700 [&>ol]:dark:text-slate-400 [&>ol]:font-light [&>ol]:space-y-4
            [&>hr]:border-slate-200 [&>hr]:dark:border-white/5 [&>hr]:my-16">
            @yield('content')
        </div>
    </main>

    <!-- Footer: Static bottom-of-page -->
    <footer class="border-t border-slate-100 dark:border-white/5 py-16 px-8 bg-slate-50/50 dark:bg-slate-950/50 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto flex flex-col items-center gap-10">
            <div class="flex flex-wrap justify-center items-center">
                <a href="/privacy" class="mx-8 text-[10px] font-black tracking-[0.4em] text-slate-400 hover:text-brand-blue transition-colors uppercase">Privacy</a>
                <span class="w-1.5 h-1.5 bg-brand-blue/30 rounded-full"></span>
                <a href="/tos" class="mx-8 text-[10px] uppercase font-black tracking-[0.4em] text-slate-400 hover:text-brand-blue transition-colors">Terms</a>
                <span class="w-1.5 h-1.5 bg-brand-teal/30 rounded-full"></span>
                <a href="/data-deletion" class="mx-8 text-[10px] uppercase font-black tracking-[0.4em] text-slate-400 hover:text-brand-blue transition-colors">Data Deletion</a>
            </div>
            <div class="text-[10px] uppercase tracking-[0.4em] font-black text-slate-400 dark:text-slate-500 opacity-60">
                Engineered by <a href="https://anibalalvarez.com" target="_blank" class="hover:text-brand-blue transition-colors underline-offset-4 hover:underline">Aníbal Álvarez</a> for the APIs Hub Network. (v1.0)
            </div>
        </div>
    </footer>
    
    <div class="hero-mesh opacity-20 dark:opacity-10 pointer-events-none"></div>
    @vite(['resources/js/gtm.js'])
</body>
</html>

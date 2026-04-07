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
<body class="antialiased bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 selection:bg-brand-blue selection:text-white transition-colors duration-300">
    
    <nav class="fixed top-0 w-full z-50 border-b border-slate-200 dark:border-white/5 bg-white/80 dark:bg-slate-950/50 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <a href="/" class="hover:opacity-80 transition-opacity">
                <!-- Light Mode Logo -->
                <img src="{{ asset('images/branding/apishub-trans-620.webp') }}" alt="APIs Hub" class="h-8 dark:hidden">
                <!-- Dark Mode Logo -->
                <img src="{{ asset('images/branding/apishub-trans-light-620.webp') }}" alt="APIs Hub" class="h-8 hidden dark:block">
            </a>
            
            <div class="flex items-center gap-6">
                <!-- Theme Toggle -->
                <button @click="darkMode = !darkMode" class="p-2 rounded-full border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 transition-all hover:scale-110">
                    <template x-if="darkMode">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </template>
                    <template x-if="!darkMode">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </template>
                </button>
                <a href="/" class="text-sm font-semibold text-slate-500 hover:text-brand-blue transition-colors">Back to Home</a>
            </div>
        </div>
    </nav>

    <main class="relative pt-32 pb-24 px-6">
        <div class="max-w-3xl mx-auto
            [&>h1]:text-4xl [&>h1]:font-extrabold [&>h1]:mb-8 [&>h1]:text-slate-900 [&>h1]:dark:text-white
            [&>h2]:text-2xl [&>h2]:font-bold [&>h2]:mt-12 [&>h2]:mb-4 [&>h2]:text-brand-blue
            [&>h3]:text-xl [&>h3]:font-bold [&>h3]:mt-8 [&>h3]:mb-2 [&>h3]:text-slate-800 [&>h3]:dark:text-slate-200
            [&>p]:text-slate-600 [&>p]:dark:text-slate-400 [&>p]:leading-relaxed [&>p]:mb-4
            [&>ul]:list-disc [&>ul]:ml-6 [&>ul]:mb-6 [&>ul]:text-slate-600 [&>ul]:dark:text-slate-400 [&>ul]:space-y-2
            [&>ol]:list-decimal [&>ol]:ml-6 [&>ol]:mb-6 [&>ol]:text-slate-600 [&>ol]:dark:text-slate-400 [&>ol]:space-y-2
            [&>a]:text-brand-blue [&>a]:hover:underline
            [&>hr]:border-slate-200 [&>hr]:dark:border-slate-800 [&>hr]:my-12">
            @yield('content')
        </div>
    </main>

    <footer class="border-t border-slate-200 dark:border-white/5 py-12 px-6 bg-slate-50 dark:bg-slate-950">
        <div class="max-w-7xl mx-auto flex flex-col items-center gap-6 text-[10px] uppercase tracking-[0.3em] font-bold text-slate-400 dark:text-slate-500">
            <div class="flex flex-wrap justify-center gap-x-12 gap-y-4">
                <a href="/privacy" class="hover:text-brand-blue transition-colors">Privacy</a>
                <a href="/tos" class="hover:text-brand-blue transition-colors">Terms</a>
                <a href="/data-deletion" class="hover:text-brand-blue transition-colors">Data Deletion</a>
            </div>
            <div class="opacity-80">
                Engineered by <a href="https://anibalalvarez.com" target="_blank" class="hover:text-brand-blue transition-colors">Aníbal Álvarez</a>
            </div>
        </div>
    </footer>

    <div class="hero-mesh opacity-20 dark:opacity-10"></div>
</body>
</html>

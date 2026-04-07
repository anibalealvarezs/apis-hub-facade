<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | APIs Hub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .legal-content h1 { @apply text-4xl font-extrabold mb-8 text-white; }
        .legal-content h2 { @apply text-2xl font-bold mt-10 mb-4 text-brand-blue; }
        .legal-content h3 { @apply text-xl font-bold mt-6 mb-2 text-slate-200; }
        .legal-content p { @apply text-slate-400 leading-relaxed mb-4; }
        .legal-content ul { @apply list-disc ml-6 mb-6 text-slate-400 space-y-2; }
        .legal-content a { @apply text-brand-blue hover:underline; }
        .legal-content hr { @apply border-slate-800 my-12; }
    </style>
</head>
<body class="antialiased bg-slate-950 text-slate-100 selection:bg-brand-blue selection:text-white">
    
    <nav class="fixed top-0 w-full z-50 border-b border-white/5 bg-slate-950/50 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <a href="/" class="hover:opacity-80 transition-opacity">
                <img src="{{ asset('images/branding/apishub-trans-light-620.webp') }}" alt="APIs Hub" class="h-10">
            </a>
            <a href="/" class="text-sm font-semibold text-slate-400 hover:text-white transition-colors">Back to Home</a>
        </div>
    </nav>

    <main class="relative pt-32 pb-24 px-6">
        <div class="max-w-3xl mx-auto legal-content">
            @yield('content')
        </div>
    </main>

    <footer class="border-t border-white/5 py-12 px-6 bg-slate-950">
        <div class="max-w-7xl mx-auto flex flex-col items-center gap-6 text-[10px] uppercase tracking-[0.3em] font-bold text-slate-500">
            <div class="flex gap-8">
                <a href="/privacy" class="hover:text-brand-blue transition-colors">Privacy</a>
                <a href="/tos" class="hover:text-brand-blue transition-colors">Terms</a>
                <a href="/data-deletion" class="hover:text-brand-blue transition-colors">Data Deletion</a>
            </div>
            <div class="opacity-80">
                Engineered by <a href="https://anibalalvarez.com" target="_blank" class="hover:text-brand-blue transition-colors">Aníbal Álvarez</a>
            </div>
        </div>
    </footer>

    <div class="hero-mesh opacity-20"></div>
</body>
</html>

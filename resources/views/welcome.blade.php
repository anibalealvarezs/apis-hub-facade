<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: true }" :class="{ 'dark': darkMode }">
    <head>
        @if($gtmId)
        <!-- Performance Hints -->
        <link rel="preconnect" href="https://www.googletagmanager.com">
        <link rel="dns-prefetch" href="https://www.googletagmanager.com">
        <link rel="preconnect" href="https://www.google-analytics.com">
        <link rel="dns-prefetch" href="https://www.google-analytics.com">

        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gtmId }}');</script>
        <!-- End Google Tag Manager -->
        @endif
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>APIs Hub | The Silicon Valley Gateway</title>

        <!-- Google Fonts: Outfit -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="dns-prefetch" href="https://fonts.gstatic.com">
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">

        <!-- Tailwind 4 Direct Integration (if using @theme) or pre-built styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- APIs Hub Branding CSS -->
        <link rel="stylesheet" href="{{ asset('css/branding.css') }}">

        <!-- Alpine.js to handle interactivity -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

        <style>
            :root {
                --brand-blue: #00A7F9;
                --brand-teal: #00CAC4;
            }
            [x-cloak] { display: none !important; }

            body {
                font-family: 'Outfit', sans-serif;
                transition: background-color 0.5s ease;
            }

            .hero-mesh {
                position: fixed;
                inset: 0;
                background-image: 
                    radial-gradient(at 0% 0%, rgba(0, 167, 249, 0.1) 0, transparent 50%), 
                    radial-gradient(at 100% 100%, rgba(0, 202, 196, 0.1) 0, transparent 50%);
                z-index: -1;
            }

            .dark .hero-mesh {
                background-color: #0f172a;
                background-image: 
                    radial-gradient(at 0% 0%, rgba(0, 167, 249, 0.08) 0, transparent 40%), 
                    radial-gradient(at 70% 30%, rgba(139, 92, 246, 0.05) 0, transparent 40%),
                    radial-gradient(at 100% 100%, rgba(0, 202, 196, 0.08) 0, transparent 40%);
            }

            /* Silicon Valley "Unicorn" Typography */
            .unicorn-title {
                background: linear-gradient(135deg, #FFF 20%, var(--brand-blue) 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                letter-spacing: -3px;
                line-height: 1;
            }

            .dark .unicorn-title {
                background: linear-gradient(135deg, #FFFFFF 30%, var(--brand-teal) 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            /* Custom Input Glows */
            .form-input-glow:focus {
                border-color: var(--brand-blue);
                box-shadow: 0 0 12px rgba(0, 167, 249, 0.3);
            }

            /* Dark/Light Toggle Button */
            .theme-toggle {
                position: fixed;
                top: 2rem;
                right: 2rem;
                z-index: 50;
            }
        </style>
    </head>
    <body class="antialiased min-h-screen text-slate-900 dark:text-slate-100 selection:bg-brand-blue selection:text-white">
        @if($gtmId)
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
        @endif
        
        <!-- Background Mesh -->
        <div class="hero-mesh"></div>

        <!-- Navigation Placeholder / Global Theme UI -->
        <div class="theme-toggle" x-cloak>
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

        <main class="relative flex flex-col items-center justify-center min-h-screen px-6 py-12 text-center lg:px-8">
            
            <!-- Branding Header -->
            <div class="mb-10 animate-fade-in">
                <!-- Light Mode Logo: Standard Colored -->
                <img x-show="!darkMode" src="{{ asset('images/branding/apishub-trans-620.webp') }}" 
                     alt="APIs Hub" width="620" height="152" class="h-24 md:h-32 mx-auto drop-shadow-xl" 
                     fetchpriority="high" decoding="async">
                <!-- Dark Mode Logo: White/Waitlist Friendly -->
                <img x-show="darkMode" src="{{ asset('images/branding/apishub-trans-light-600.webp') }}" 
                     alt="APIs Hub" width="600" height="131" class="h-24 md:h-32 mx-auto drop-shadow-glow" 
                     fetchpriority="high" decoding="async">
            </div>

            <!-- Hero Headline -->
            <div class="max-w-3xl mx-auto mb-10">
                <span class="inline-block px-4 py-1.5 mb-6 text-sm font-semibold tracking-wider text-brand-blue uppercase bg-brand-blue/10 border border-brand-blue/30 rounded-full animate-bounce">
                    Alpha Access Open
                </span>
                <h1 class="text-6xl font-extrabold sm:text-8xl unicorn-title mb-6">
                    Connect Any Data.<br>Anywhere.
                </h1>
                <p class="text-xl leading-relaxed text-slate-500 dark:text-slate-400 font-light max-w-2xl mx-auto">
                    The central command for cross-platform data extraction, multi-tenant analytics, and marketing automation infrastructure. One portal to rule them all.
                </p>
            </div>

            <!-- Waitlist Form (Lead Intake) -->
            <div class="w-full max-w-md mx-auto mb-12">
                @if(session('success'))
                    <div class="p-4 mb-6 text-emerald-700 bg-emerald-100/80 backdrop-blur-md rounded-xl border border-emerald-200 animate-pulse">
                        {{ session('success') }}
                    </div>
                @else
                    <form action="{{ route('landing.subscribe') }}" method="POST" class="relative group">
                        @csrf
                        <div class="flex flex-col sm:flex-row gap-3 p-2 rounded-2xl glass-panel shadow-2xl bg-white/50 dark:bg-slate-900/50">
                            <input 
                                type="email" 
                                name="email" 
                                required 
                                placeholder="name@agency.com"
                                class="flex-grow px-5 py-4 text-lg bg-transparent border-none focus:ring-0 text-slate-900 dark:text-white placeholder-slate-400"
                                value="{{ old('email') }}"
                            >
                            <button 
                                type="submit"
                                class="px-8 py-4 text-white font-bold bg-brand-blue rounded-xl hover:scale-105 active:scale-95 transition-all shadow-glow hover:shadow-glow-intense"
                            >
                                Join Waitlist
                            </button>
                        </div>
                        @if(session('error'))
                            <p class="mt-3 text-red-500 text-sm font-medium">{{ session('error') }}</p>
                        @endif
                        <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">
                            Guaranteed early access + 50% discount for first 50 agencies. No credit card required.
                        </p>
                    </form>
                @endif
            </div>

            <!-- Portals Link (Internal / Admin) -->
            <div class="flex gap-4 sm:gap-8 justify-center items-center opacity-70 hover:opacity-100 transition-opacity">
                <span data-portal="{{ $portals['app'] }}" class="js-portal-link text-sm font-semibold tracking-wide text-brand-blue hover:underline decoration-2 underline-offset-4 cursor-pointer">Project Portal</span>
                <span class="w-1 h-1 bg-slate-400 dark:bg-slate-600 rounded-full"></span>
                <span data-portal="{{ $portals['admin'] }}" class="js-portal-link text-sm font-semibold tracking-wide text-brand-teal hover:underline decoration-2 underline-offset-4 cursor-pointer">Admin Console</span>
                <span class="w-1 h-1 bg-slate-400 dark:bg-slate-600 rounded-full"></span>
                <span data-portal="{{ $portals['docs'] }}" class="js-portal-link text-sm font-semibold tracking-wide text-slate-500 hover:underline cursor-pointer">Documentation</span>
            </div>

        </main>

        <!-- Dynamic Grid / Micro Interactions -->
        <div class="fixed bottom-0 left-0 p-8 text-[10px] uppercase tracking-[0.3em] font-bold text-slate-300 dark:text-slate-800 pointer-events-none select-none">
            Powered by Orchestrator Engine v1.0
        </div>
        
    </body>
</html>

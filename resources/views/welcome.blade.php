<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://www.google.com/recaptcha/enterprise.js?render={{ config('services.recaptcha.site_key') }}"></script>
        <title>APIs Hub | Elite Data & Caching Infrastructure</title>
        <meta name="description" content="The headless data engine for elite marketing agencies. Standardize multi-platform data into high-speed, analytics-ready pipelines with intelligent formulas and gap-filling.">
        <meta name="keywords" content="marketing data infrastructure, api caching, ecommerce etl, standardized marketing metrics, data gap filling, headless data engine">
        <meta name="robots" content="index, follow">
        <meta name="author" content="APIs Hub Network">

        <!-- Use Vite for Assets (CSS, Global JS, Theme Init) -->
        @vite(['resources/js/theme.js', 'resources/css/app.css', 'resources/js/app.js'])

        <!-- Google Fonts: Outfit -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="dns-prefetch" href="https://fonts.gstatic.com">
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    </head>
    <body class="antialiased min-h-screen text-slate-900 dark:text-slate-100 selection:bg-brand-blue selection:text-white" 
          x-data="themeControl" 
          data-gtm-id="{{ $gtmId }}">
        
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
            <div class="mb-10">
                <!-- Light Mode Logo: Standard Colored -->
                <img src="{{ asset('images/branding/apishub-trans-620.webp') }}?v=1.3" 
                     alt="APIs Hub" width="620" height="152" 
                     :class="darkMode ? 'hidden' : 'block'"
                     class="h-24 md:h-32 mx-auto" 
                     fetchpriority="high" decoding="async">
                <!-- Dark Mode Logo: White/Waitlist Friendly -->
                <img src="{{ asset('images/branding/apishub-trans-light-620.webp') }}?v=1.3" 
                     alt="APIs Hub" width="620" height="152" 
                     :class="darkMode ? 'block' : 'hidden'"
                     class="h-24 md:h-32 mx-auto" 
                     fetchpriority="high" decoding="async">
            </div>

            <!-- ... Hero Headline ... -->
            <div class="max-w-3xl mx-auto mb-10">
                <span class="inline-block px-4 py-1.5 mb-6 text-sm font-semibold tracking-wider text-brand-blue uppercase bg-brand-blue/10 border border-brand-blue/30 rounded-full animate-bounce">
                    Alpha Access Open
                </span>
                <h1 id="main-headline" class="text-6xl font-extrabold sm:text-7xl lg:text-8xl unicorn-title mb-6">
                    Unified Data.<br>Correct Formulas.
                </h1>
                <p class="text-xl leading-relaxed text-slate-500 dark:text-slate-400 font-light max-w-2xl mx-auto">
                    The headless data engine for elite analytics teams. Standardize your multi-platform source data into a high-speed infrastructure with automatic gap-filling and weighted-average mathematical validation.
                </p>
            </div>

            <!-- Waitlist Form (Lead Intake) -->
            <div class="w-full max-w-md mx-auto mb-12">
                @if(session('success'))
                    <div id="success-alert" class="p-4 mb-6 text-emerald-700 bg-emerald-100/80 backdrop-blur-md rounded-xl border border-emerald-200 animate-pulse">
                        {{ session('success') }}
                    </div>
                @elseif(isset($unsubscribe_message))
                    <div id="unsubscribe-alert" class="p-4 mb-6 text-slate-700 bg-slate-100/80 backdrop-blur-md rounded-xl border border-slate-200">
                        {{ $unsubscribe_message }}
                    </div>
                @else
                    <form id="waitlist-form" action="{{ route('landing.subscribe') }}" method="POST" class="relative group">
                        @csrf
                        <input type="hidden" name="recaptcha_token" id="recaptcha_token">
                        <div class="flex flex-col sm:flex-row gap-3 p-2 rounded-2xl glass-panel shadow-2xl bg-white/50 dark:bg-slate-900/50">
                            <input 
                                id="email-input"
                                type="email" 
                                name="email" 
                                required 
                                placeholder="name@agency.com"
                                class="flex-grow px-5 py-4 text-lg bg-transparent border-none focus:ring-0 text-slate-900 dark:text-white placeholder-slate-400"
                                value="{{ old('email') }}"
                            >
                            <button 
                                id="submit-button"
                                type="submit"
                                class="px-8 py-4 text-white font-bold bg-brand-blue rounded-xl hover:scale-105 active:scale-95 transition-all shadow-glow hover:shadow-glow-intense"
                            >
                                Join Alpha
                            </button>
                        </div>
                        
                        <script>
                            document.getElementById('waitlist-form').addEventListener('submit', function(e) {
                                e.preventDefault();
                                const form = this;
                                
                                if (typeof grecaptcha !== 'undefined') {
                                    grecaptcha.enterprise.ready(function() {
                                        grecaptcha.enterprise.execute('{{ config('services.recaptcha.site_key') }}', {action: 'subscribe'}).then(function(token) {
                                            document.getElementById('recaptcha_token').value = token;
                                            form.submit();
                                        });
                                    });
                                } else {
                                    form.submit();
                                }
                            });
                        </script>

                        @if(session('error'))
                            <p class="mt-3 text-red-500 text-sm font-medium">{{ session('error') }}</p>
                        @endif
                        <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">
                            Guaranteed early access for agencies and data teams. No credit card required.
                        </p>
                    </form>
                @endif
            </div>

            <!-- Portals Link (Internal / Admin / Documentation) -->
            <div class="flex gap-4 sm:gap-8 justify-center items-center opacity-70 hover:opacity-100 transition-opacity">
                {{-- 
                <span data-portal="{{ $portals['app'] }}" class="js-portal-link text-sm font-semibold tracking-wide text-brand-blue hover:underline decoration-2 underline-offset-4 cursor-pointer">Project Portal</span>
                <span class="w-1 h-1 bg-slate-400 dark:bg-slate-600 rounded-full"></span>
                <span data-portal="{{ $portals['admin'] }}" class="js-portal-link text-sm font-semibold tracking-wide text-brand-teal hover:underline decoration-2 underline-offset-4 cursor-pointer">Admin Console</span>
                <span class="w-1 h-1 bg-slate-400 dark:bg-slate-600 rounded-full"></span>
                <span data-portal="{{ $portals['docs'] }}" class="js-portal-link text-sm font-semibold tracking-wide text-slate-500 hover:underline cursor-pointer">Documentation</span>
                --}}
            </div>

        </main>

        <!-- Dynamic Footer / Micro Branding -->
        <div class="fixed bottom-8 w-full flex flex-col items-center gap-4 px-8 text-[10px] uppercase tracking-[0.3em] font-bold text-slate-400 dark:text-slate-500 select-none pointer-events-none">
            <div class="flex flex-wrap justify-center items-center gap-10 opacity-70 hover:opacity-100 transition-opacity">
                <a href="/privacy" class="pointer-events-auto hover:text-brand-blue transition-colors">Privacy</a>
                <span class="w-1.5 h-1.5 bg-slate-200 dark:bg-slate-800 rounded-full"></span>
                <a href="/tos" class="pointer-events-auto hover:text-brand-blue transition-colors">Terms</a>
                <span class="w-1.5 h-1.5 bg-slate-200 dark:bg-slate-800 rounded-full"></span>
                <a href="/data-deletion" class="pointer-events-auto hover:text-brand-blue transition-colors">Data Deletion</a>
            </div>
            <div class="opacity-80">
                Engineered by <a href="https://anibalalvarez.com" target="_blank" class="pointer-events-auto hover:text-brand-blue transition-colors">Aníbal Álvarez</a> for the APIs Hub Network. (v1.0)
            </div>
        </div>
        
        <!-- Background Mesh -->
        <div class="hero-mesh"></div>

        <!-- External Marketing & Analytics (Vite Optimized) -->
        @vite(['resources/js/gtm.js'])
    </body>
</html>

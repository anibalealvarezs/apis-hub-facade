<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        
        <!-- Preconnect / DNS Prefetch -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="dns-prefetch" href="https://fonts.gstatic.com">
        <link rel="preconnect" href="https://www.googletagmanager.com">
        <link rel="preconnect" href="https://www.google.com">

        <!-- Google reCAPTCHA Enterprise -->
        <script src="https://www.google.com/recaptcha/enterprise.js?render={{ config('services.recaptcha.site_key') }}" async defer></script>
        
        <!-- Primary Meta Tags -->
        <title>APIs Hub | {{ __('Normalized Marketing Data Engine for Agencies') }}</title>
        <meta name="title" content="APIs Hub | {{ __('Normalized Marketing Data Engine for Agencies') }}">
        <meta name="description" content="{{ __('Stop fighting broken connectors and conflicting metrics. APIs Hub syncs Meta, Google, Shopify, and Klaviyo into dedicated client clusters with unified definitions for true ROAS and MER.') }}">
        <meta name="keywords" content="{{ __('marketing agency reporting, cross channel attribution, normalized marketing data, blended ROAS, looker studio quota fix, multi client dashboards, apis hub') }}">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <meta name="author" content="APIs Hub Network">
        <meta name="application-name" content="APIs Hub">
        <meta name="apple-mobile-web-app-title" content="APIs Hub">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="theme-color" content="#0f172a">
        <meta name="color-scheme" content="dark light">

        <!-- Favicons & App Icons -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/apishub-favicon.png') }}">
        <link rel="icon" type="image/webp" href="{{ asset('images/branding/apishub-favicon.webp') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/branding/apishub-favicon.png') }}">

        <!-- Canonical URL -->
        <link rel="canonical" href="{{ url()->current() }}" />

        <!-- Multilingual Alternate Links (hreflang) -->
        <link rel="alternate" hreflang="en" href="{{ url('/') }}" />
        <link rel="alternate" hreflang="es" href="{{ url('/es') }}" />
        <link rel="alternate" hreflang="x-default" href="{{ url('/') }}" />

        <!-- Open Graph / Facebook / WhatsApp / LinkedIn -->
        <meta property="og:site_name" content="APIs Hub">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="APIs Hub | {{ __('Unified Marketing Analytics & Dashboards') }}">
        <meta property="og:description" content="{{ __('Connect your advertising, social, and ecommerce platforms to instantly aggregate and visualize your marketing data in high-performance dashboards.') }}">
        <meta property="og:image" content="{{ asset('images/branding/apishub-620.png') }}">
        <meta property="og:image:secure_url" content="{{ asset('images/branding/apishub-620.png') }}">
        <meta property="og:image:type" content="image/png">
        <meta property="og:image:width" content="620">
        <meta property="og:image:height" content="135">
        <meta property="og:image:alt" content="APIs Hub - {{ __('Unified Marketing Analytics & Dashboards') }}">
        <meta property="og:locale" content="{{ app()->getLocale() === 'es' ? 'es_ES' : 'en_US' }}">
        <meta property="og:locale:alternate" content="{{ app()->getLocale() === 'es' ? 'en_US' : 'es_ES' }}">

        <!-- Twitter / X Cards -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:url" content="{{ url()->current() }}">
        <meta name="twitter:title" content="APIs Hub | {{ __('Unified Marketing Analytics & Dashboards') }}">
        <meta name="twitter:description" content="{{ __('Connect your advertising, social, and ecommerce platforms to instantly aggregate and visualize your marketing data in high-performance dashboards.') }}">
        <meta name="twitter:image" content="{{ asset('images/branding/apishub-620.png') }}">
        <meta name="twitter:image:alt" content="APIs Hub - {{ __('Unified Marketing Analytics & Dashboards') }}">

        <!-- JSON-LD Structured Data -->
        <script type="application/ld+json">
        {
          "@@context": "https://schema.org",
          "@@graph": [
            {
              "@@type": "WebSite",
              "@@id": "{{ url('/') }}/#website",
              "url": "{{ url('/') }}/",
              "name": "APIs Hub",
              "inLanguage": "{{ app()->getLocale() }}",
              "description": "{{ __('Connect your advertising, social, and ecommerce platforms to instantly aggregate and visualize your marketing data in high-performance dashboards.') }}",
              "publisher": {
                "@@id": "{{ url('/') }}/#organization"
              }
            },
            {
              "@@type": "Organization",
              "@@id": "{{ url('/') }}/#organization",
              "name": "APIs Hub Network",
              "url": "{{ url('/') }}/",
              "logo": {
                "@@type": "ImageObject",
                "url": "{{ asset('images/branding/apishub-trans-620.webp') }}",
                "width": 620,
                "height": 135
              }
            },
            {
              "@@type": "SoftwareApplication",
              "@@id": "{{ url('/') }}/#software",
              "name": "APIs Hub",
              "inLanguage": "{{ app()->getLocale() }}",
              "applicationCategory": "BusinessApplication",
              "operatingSystem": "WebBrowser",
              "description": "{{ __('Connect your advertising, social, and ecommerce platforms to instantly aggregate and visualize your marketing data in high-performance dashboards.') }}",
              "offers": {
                "@@type": "Offer",
                "price": "0",
                "priceCurrency": "USD",
                "description": "Free during Beta"
              },
              "provider": {
                "@@id": "{{ url('/') }}/#organization"
              }
            }
          ]
        }
        </script>

        <!-- Use Vite for Assets (CSS, Global JS, Theme Init) -->
        @vite(['resources/js/theme.js', 'resources/css/app.css', 'resources/js/app.js'])

        <!-- Google Fonts: Outfit -->
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <style>
            body {
                background-color: #f8fafc;
            }
            html.dark body {
                background-color: #0f172a;
            }
            .hero-mesh {
                position: fixed;
                inset: 0;
                z-index: -10;
                background-color: #f8fafc;
                background-repeat: no-repeat;
                background-image: 
                    radial-gradient(at 0% 0%, rgba(0, 167, 249, 0.12) 0, transparent 50%), 
                    radial-gradient(at 100% 100%, rgba(0, 202, 196, 0.12) 0, transparent 50%);
            }
            html.dark .hero-mesh {
                background-color: #0f172a;
                background-image: 
                    radial-gradient(at 0% 0%, rgba(0, 167, 249, 0.08) 0, transparent 40%), 
                    radial-gradient(at 70% 30%, rgba(139, 92, 246, 0.05) 0, transparent 40%), 
                    radial-gradient(at 100% 100%, rgba(0, 202, 196, 0.08) 0, transparent 40%);
            }
        </style>
    </head>
    <body class="antialiased min-h-screen flex flex-col justify-between bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 selection:bg-brand-blue selection:text-white relative" 
          x-data="themeControl" 
          data-gtm-id="{{ $gtmId }}">
        
        <!-- Header / Navigation & Theme Controls -->
        <header class="w-full flex justify-end items-center gap-3 px-6 pt-4 pb-2 sm:fixed sm:top-8 sm:right-8 sm:w-auto sm:p-0 z-50" x-cloak>
            <nav aria-label="{{ __('Language switcher') }}" class="flex items-center gap-3 px-4 py-2 text-xs font-bold tracking-wider rounded-full border border-slate-200 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md">
                <a href="{{ route('landing.index') }}" class="hover:text-brand-blue transition-colors {{ app()->getLocale() === 'en' ? 'text-brand-blue' : 'text-slate-400 dark:text-slate-500' }}">EN</a>
                <span class="w-1 h-1 bg-slate-300 dark:bg-slate-700 rounded-full" aria-hidden="true"></span>
                <a href="{{ route('landing.index', ['locale' => 'es']) }}" class="hover:text-brand-blue transition-colors {{ app()->getLocale() === 'es' ? 'text-brand-blue' : 'text-slate-400 dark:text-slate-500' }}">ES</a>
            </nav>
            <button @click="darkMode = !darkMode" aria-label="{{ __('Toggle Dark Mode') }}" class="p-2 rounded-full border border-slate-200 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md glow-hover">
                <template x-if="darkMode">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </template>
                <template x-if="!darkMode">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </template>
            </button>
        </header>

        <main class="relative flex-grow flex flex-col items-center justify-start sm:justify-center w-full px-6 pt-2 pb-8 sm:py-16 text-center lg:px-8">
            
            <!-- Branding Header -->
            <div class="mb-8 md:mb-10 w-full px-4 sm:px-0">
                <!-- Light Mode Logo: Standard Colored -->
                <img src="{{ asset('images/branding/apishub-trans-620.webp') }}?v=1.3" 
                     alt="APIs Hub" width="620" height="135" 
                     :class="darkMode ? 'hidden' : 'block'"
                     class="w-full h-auto max-w-[280px] xs:max-w-[320px] sm:max-w-[400px] md:max-w-none md:w-auto md:h-32 mx-auto dark:hidden" 
                     fetchpriority="high" decoding="async">
                <!-- Dark Mode Logo: White/Waitlist Friendly -->
                <img src="{{ asset('images/branding/apishub-trans-light-620.webp') }}?v=1.3" 
                     alt="APIs Hub" width="620" height="135" 
                     :class="darkMode ? 'block' : 'hidden'"
                     class="w-full h-auto max-w-[280px] xs:max-w-[320px] sm:max-w-[400px] md:max-w-none md:w-auto md:h-32 mx-auto hidden dark:block" 
                     fetchpriority="high" decoding="async">
            </div>

            <!-- Hero Section -->
            <section class="max-w-5xl mx-auto mb-10" aria-labelledby="main-headline">
                <span data-portal="{{ $portals['app'] }}" class="js-portal-link inline-block px-8 py-3 mb-6 text-sm font-bold text-white bg-brand-blue rounded-xl hover:scale-105 active:scale-95 transition-all shadow-glow hover:shadow-glow-intense cursor-pointer">
                    {{ __('Try beta for free') }}
                </span>
                <h1 id="main-headline" class="public-hero-title">
                    {!! __('Every marketing channel speaks a different language.') !!}<br><span class="text-brand-blue dark:text-brand-blue-400">{!! __('APIs Hub translates them into one.') !!}</span>
                </h1>
                <p class="public-hero-subtitle max-w-3xl mx-auto text-base sm:text-lg text-slate-600 dark:text-slate-300 font-normal leading-relaxed mt-4">
                    {{ __('Meta, Google, Shopify, and Klaviyo all define clicks, conversions, and revenue differently. APIs Hub syncs them daily, reconciles their metrics, and gives each of your clients an isolated data hub—so your reporting never breaks, and your numbers always match.') }}
                </p>
            </section>

            <!-- Waitlist Form Section (Lead Intake) -->
            <section class="w-full max-w-md mx-auto mb-8 sm:mb-12" aria-label="{{ __('Subscribe to updates') }}">
                @if(session('success'))
                    <div id="success-alert" class="p-4 mb-6 text-emerald-700 bg-emerald-100/80 backdrop-blur-md rounded-xl border border-emerald-200 animate-pulse" role="alert">
                        {{ session('success') }}
                    </div>
                @elseif(isset($unsubscribe_message))
                    <div id="unsubscribe-alert" class="p-4 mb-6 text-slate-700 bg-slate-100/80 backdrop-blur-md rounded-xl border border-slate-200" role="alert">
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
                                placeholder="{{ __('name@agency.com') }}"
                                aria-label="{{ __('Email Address') }}"
                                class="flex-grow px-4 py-3 text-base bg-transparent border-none focus:ring-0 text-slate-900 dark:text-white placeholder-slate-400"
                                value="{{ old('email') }}"
                            >
                            <button 
                                id="submit-button"
                                type="submit"
                                class="whitespace-nowrap px-6 py-3 text-sm text-white font-bold bg-brand-blue rounded-xl hover:scale-105 active:scale-95 transition-all shadow-glow hover:shadow-glow-intense"
                            >
                                {{ __('Subscribe') }}
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
                            <p class="mt-3 text-red-500 text-sm font-medium" role="alert">{{ session('error') }}</p>
                        @endif
                        <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">
                            {{ __('Subscribe to receive the latest updates, new features, and exclusive promotions.') }}
                        </p>
                    </form>
                @endif
            </section>

            <!-- The 4 Core Operational Pains -->
            <section class="max-w-6xl mx-auto my-12 text-left" aria-label="{{ __('Why APIs Hub') }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Pain 1: Attribution -->
                    <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80 shadow-sm hover:border-brand-blue/40 transition-all">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="w-8 h-8 rounded-lg bg-red-500/10 text-red-500 flex items-center justify-center font-bold text-sm">01</span>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('The Multi-Platform Attribution Fight') }}</h2>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-3">
                            {{ __('Meta says you made $80k. GA4 reports $35k. Shopify shows $50k. Your team wastes hours explaining attribution windows to a skeptical client.') }}
                        </p>
                        <p class="text-xs text-brand-blue dark:text-brand-blue-400 font-semibold flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ __('Unified into a shared dictionary of metrics: true Blended ROAS, uniform MER, and reconcilable numbers.') }}
                        </p>
                    </div>

                    <!-- Pain 2: Broken Connectors -->
                    <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80 shadow-sm hover:border-brand-blue/40 transition-all">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold text-sm">02</span>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('The Monday Morning Dashboard Crash') }}</h2>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-3">
                            {{ __('Live connectors query ad platforms on the fly. The moment a client opens a 90-day report, the API hits a quota limit and displays an ugly error message.') }}
                        </p>
                        <p class="text-xs text-brand-blue dark:text-brand-blue-400 font-semibold flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ __('APIs Hub syncs on schedule into an isolated cluster. Your dashboards load instantly with zero live API timeouts.') }}
                        </p>
                    </div>

                    <!-- Pain 3: Agency Multi-Client -->
                    <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80 shadow-sm hover:border-brand-blue/40 transition-all">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="w-8 h-8 rounded-lg bg-blue-500/10 text-brand-blue flex items-center justify-center font-bold text-sm">03</span>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Multi-Client Billing & Workspace Chaos') }}</h2>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-3">
                            {{ __('Juggling different client logins, mixed credit cards, and shared workspace permission trees eats margins and creates operational chaos.') }}
                        </p>
                        <p class="text-xs text-brand-blue dark:text-brand-blue-400 font-semibold flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ __('Built-in Project vs. Billing separation. Manage 30 client projects cleanly and route invoices directly or consolidated.') }}
                        </p>
                    </div>

                    <!-- Pain 4: Safe AI -->
                    <div class="p-6 rounded-2xl glass-panel bg-white/40 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-800/80 shadow-sm hover:border-brand-blue/40 transition-all">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center font-bold text-sm">04</span>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('AI Analysis Without Hallucinations') }}</h2>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-3">
                            {{ __('Copy-pasting raw CSV exports into ChatGPT causes math errors and made-up metrics. You cannot afford to send hallucinated numbers to clients.') }}
                        </p>
                        <p class="text-xs text-brand-blue dark:text-brand-blue-400 font-semibold flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ __('Native Model Context Protocol (MCP) server. Claude or Cursor queries verified, mathematically normalized ground truth.') }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Portals Link (Internal / Admin / Documentation) -->
            <div class="flex gap-4 sm:gap-8 justify-center items-center mt-4 mb-8">
                <a href="{{ app()->getLocale() === 'es' ? route('landing.plans.es') : route('landing.plans') }}" class="text-sm font-semibold tracking-wide text-brand-blue hover:underline decoration-2 underline-offset-4">
                    {{ __('View Plans & Features') }} &rarr;
                </a>
            </div>

        </main>

        <!-- Semantic Footer / Micro Branding -->
        @include('components.public.footer')
        
        <!-- Background Mesh -->
        <div class="hero-mesh" aria-hidden="true"></div>

        <!-- External Marketing & Analytics (Vite Optimized) -->
        @vite(['resources/js/gtm.js'])
    </body>
</html>

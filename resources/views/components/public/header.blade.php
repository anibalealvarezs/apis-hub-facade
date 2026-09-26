<!-- Unified Header Sticky Navigation -->
<header class="fixed top-0 left-0 w-full z-50 h-20 md:h-24 px-6 md:px-12 flex items-center justify-between glass-panel border-b border-slate-200/50 dark:border-slate-800/50 transition-all duration-300">
    <!-- Brand Logo Left -->
    <a href="{{ app()->getLocale() === 'es' ? '/es' : '/' }}" class="hover:opacity-85 transition-opacity block">
        <img src="{{ asset('images/branding/apishub-trans-620.webp') }}?v=1.3" 
             alt="APIs Hub" class="h-9 md:h-11 dark:hidden" 
             :class="darkMode ? 'hidden' : 'block'">
        <img src="{{ asset('images/branding/apishub-trans-light-620.webp') }}?v=1.3" 
             alt="APIs Hub" class="h-9 md:h-11 hidden dark:block" 
             :class="darkMode ? 'block' : 'hidden'">
    </a>

    <!-- Right Controls: Language Selector, Theme Toggle, Home & Try Beta CTA -->
    <div class="flex items-center gap-4 sm:gap-6" x-cloak>
        <!-- Language Switcher -->
        <nav aria-label="{{ __('Language switcher') }}" class="flex items-center gap-3 px-4 py-2 text-xs font-bold tracking-wider rounded-full border border-slate-200 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md">
            @php
                $currentRoute = request()->route() ? request()->route()->getName() : null;
                
                // Determine counterpart routes cleanly for landing, plans, docs, architecture, solutions, and guides
                if ($currentRoute === 'landing.plans' || $currentRoute === 'landing.plans.es') {
                    $enUrl = route('landing.plans');
                    $esUrl = route('landing.plans.es');
                } elseif ($currentRoute === 'landing.architecture' || $currentRoute === 'landing.architecture.es') {
                    $enUrl = route('landing.architecture');
                    $esUrl = route('landing.architecture.es');
                } elseif ($currentRoute === 'landing.solutions.agency' || $currentRoute === 'landing.solutions.agency.es') {
                    $enUrl = route('landing.solutions.agency');
                    $esUrl = route('landing.solutions.agency.es');
                } elseif ($currentRoute === 'landing.solutions.normalization' || $currentRoute === 'landing.solutions.normalization.es') {
                    $enUrl = route('landing.solutions.normalization');
                    $esUrl = route('landing.solutions.normalization.es');
                } elseif ($currentRoute === 'landing.guides.looker-quota' || $currentRoute === 'landing.guides.looker-quota.es') {
                    $enUrl = route('landing.guides.looker-quota');
                    $esUrl = route('landing.guides.looker-quota.es');
                } elseif ($currentRoute === 'docs.api' || $currentRoute === 'docs.api.es') {
                    $enUrl = route('docs.api');
                    $esUrl = route('docs.api.es');
                } elseif ($currentRoute && str_starts_with($currentRoute, 'legal.')) {
                    $baseLegal = str_replace('.es', '', $currentRoute);
                    $enUrl = route($baseLegal);
                    $esUrl = route($baseLegal . '.es');
                } else {
                    $enUrl = route('landing.index');
                    $esUrl = route('landing.index', ['locale' => 'es']);
                }
            @endphp
            <a href="{{ $enUrl }}" class="hover:text-brand-blue transition-colors {{ app()->getLocale() === 'en' ? 'text-brand-blue' : 'text-slate-400 dark:text-slate-500' }}">EN</a>
            <span class="w-1 h-1 bg-slate-300 dark:bg-slate-700 rounded-full" aria-hidden="true"></span>
            <a href="{{ $esUrl }}" class="hover:text-brand-blue transition-colors {{ app()->getLocale() === 'es' ? 'text-brand-blue' : 'text-slate-400 dark:text-slate-500' }}">ES</a>
        </nav>

        <!-- Theme Toggle Button -->
        <button @click="darkMode = !darkMode" aria-label="{{ __('Toggle Dark Mode') }}" class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md glow-hover transition-all">
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

        <!-- Back to Home Button -->
        <a href="{{ app()->getLocale() === 'es' ? '/es' : '/' }}" class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold tracking-wider uppercase text-slate-500 hover:text-brand-blue border border-slate-200 dark:border-slate-800 rounded-xl transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>{{ __('Home') }}</span>
        </a>

        <!-- Try Beta CTA -->
        <span data-portal="{{ $portals['app'] ?? base64_encode('/app') }}" class="js-portal-link inline-flex px-4 sm:px-5 py-2 text-xs sm:text-sm font-bold text-white bg-brand-blue rounded-xl hover:scale-105 active:scale-95 transition-all shadow-glow hover:shadow-glow-intense cursor-pointer">
            {{ __('Try beta') }}
        </span>
    </div>
</header>

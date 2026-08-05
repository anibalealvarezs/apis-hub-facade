<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ $dashboard->name }}</title>
    <script>
        (function() {
            var theme = localStorage.getItem('pv_theme');
            if (!theme) {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    <script>
        const origWarn = console.warn;
        console.warn = function(...args) {
            if (args[0] && typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) return;
            origWarn.apply(console, args);
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: { '50': '#eff6ff', '100': '#dbeafe', '200': '#bfdbfe', '300': '#93c5fd', '400': '#60a5fa', '500': '#3b82f6', '600': '#2563eb', '700': '#1d4ed8', '800': '#1e40af', '900': '#1e3a8a', '950': '#172554' },
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="{{ asset('css/dashboard-builder.css') }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Outfit', system-ui, sans-serif; }
        select { color-scheme: light; }
        .dark select { color-scheme: dark; }
        input[type="date"] { color-scheme: light; }
        .dark input[type="date"] { color-scheme: dark; }
        select option, select optgroup { background-color: #ffffff; color: #111827; }
        .dark select option, .dark select optgroup { background-color: #1f2937; color: #ffffff; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-950 min-h-screen text-gray-900 dark:text-gray-100 {{ $isEmbedded ? 'p-2' : 'p-6 max-w-7xl mx-auto' }}">
    <script>
        window.pvToken = '{{ $pv->token }}';
        window.isEmbedded = {{ $isEmbedded ? 'true' : 'false' }};
        const config = { isEmbedded: {{ $isEmbedded ? 'true' : 'false' }} };
        if (window.isEmbedded) {
            const sendResize = () => {
                window.parent.postMessage({ type: 'apis-hub-resize', height: document.body.scrollHeight }, '*');
            };
            window.addEventListener('load', sendResize);
            window.addEventListener('resize', sendResize);
        }
    </script>

    {{-- Public View Bar: Dark/Light Mode & Language Selector --}}
    <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-200 dark:border-gray-800 text-xs" x-data="{
        theme: localStorage.getItem('pv_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
        currentLang: '{{ app()->getLocale() }}',
        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('pv_theme', this.theme);
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            if (window.isEmbedded) {
                window.parent.postMessage({ type: 'apis-hub-theme', theme: this.theme }, '*');
            }
        },
        changeLang(newLang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', newLang);
            window.location.href = url.toString();
        }
    }">
        <div class="flex items-center gap-2">
            {{-- Theme Toggle Button --}}
            <button @click="toggleTheme()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors shadow-sm focus:outline-none">
                <template x-if="theme === 'dark'">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <span>{{ __('Light Mode') }}</span>
                    </span>
                </template>
                <template x-if="theme !== 'dark'">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                        <span>{{ __('Dark Mode') }}</span>
                    </span>
                </template>
            </button>
        </div>

        {{-- Language Selector --}}
        <div class="flex items-center gap-1">
            <span class="text-gray-400 dark:text-gray-500 font-medium mr-1">{{ __('Language:') }}</span>
            <button @click="changeLang('en')" class="px-2 py-1 rounded text-xs font-semibold transition-colors" :class="currentLang === 'en' ? 'bg-primary-600 text-white' : 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-700'">EN</button>
            <button @click="changeLang('es')" class="px-2 py-1 rounded text-xs font-semibold transition-colors" :class="currentLang === 'es' ? 'bg-primary-600 text-white' : 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-700'">ES</button>
        </div>
    </div>

    @include('filament.app.pages.partials.dashboard-view-content', ['viewObj' => $viewModel])

    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/focus@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>

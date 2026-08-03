<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ $dashboard->name }}</title>
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
    <style>
        body { font-family: 'Outfit', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-950 min-h-screen {{ $isEmbedded ? 'p-2' : 'p-6 max-w-7xl mx-auto' }}">
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

    @include('filament.app.pages.partials.dashboard-view-content', ['viewObj' => $viewModel])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>

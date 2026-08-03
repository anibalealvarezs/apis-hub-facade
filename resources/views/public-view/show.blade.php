<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ $dashboard->name }}</title>
    <link rel="stylesheet" href="{{ asset('css/filament/filament/app.css') }}" />
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

    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/focus@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>

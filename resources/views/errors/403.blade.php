<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('Forbidden') }}</title>

        <style>
            html{font-family:system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;line-height:1.5}*,:after,:before{box-sizing:border-box;border:0 solid #e2e8f0}a{color:inherit;text-decoration:inherit}.bg-gray-100{--bg-opacity:1;background-color:#f7fafc;background-color:rgba(247,250,252,var(--bg-opacity))}.border-gray-400{--border-opacity:1;border-color:#cbd5e0;border-color:rgba(203,213,224,var(--border-opacity))}.border-r{border-right-width:1px}.flex{display:flex}.items-center{align-items:center}.text-lg{font-size:1.125rem}.ml-4{margin-left:1rem}.mt-8{margin-top:2rem}.px-4{padding-left:1rem;padding-right:1rem}.pt-8{padding-top:2rem}.mx-auto{margin-left:auto;margin-right:auto}.max-w-xl{max-width:36rem}.min-h-screen{min-height:100vh}.relative{position:relative}.tracking-wider{letter-spacing:.05em}.uppercase{text-transform:uppercase}.antialiased{-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}@media (min-width:640px){.sm\:px-6{padding-left:1.5rem;padding-right:1.5rem}.sm\:pt-0{padding-top:0}.sm\:items-center{align-items:center}.sm\:justify-start{justify-content:flex-start}}@media (min-width:1024px){.lg\:px-8{padding-left:2rem;padding-right:2rem}}@media (prefers-color-scheme:dark){.dark\:bg-gray-900{--bg-opacity:1;background-color:#1a202c;background-color:rgba(26,32,44,var(--bg-opacity))}.dark\:text-gray-300{--text-opacity:1;color:#e2e8f0;color:rgba(226,232,240,var(--text-opacity))}.dark\:border-gray-700{--border-opacity:1;border-color:#4a5568;border-color:rgba(74,85,104,var(--border-opacity))}.dark\:text-white{--text-opacity:1;color:#fff;color:rgba(255,255,255,var(--text-opacity))}}
        </style>

        <style>
            .btn-home{display:inline-block;padding:.5rem 1.25rem;border-radius:.5rem;background-color:#00a7f9;color:#fff;font-weight:600;transition:opacity .15s ease} .btn-home:hover{opacity:.85}
        </style>
    </head>
    <body class="antialiased">
        <div class="relative flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900 sm:pt-0" role="main">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8 text-center">
                <div class="flex items-center pt-8 sm:justify-start sm:pt-0">
                    <h1 class="px-4 text-lg dark:text-gray-300 text-gray-700 border-r border-gray-400 tracking-wider">
                        403
                    </h1>

                    <div class="ml-4 text-lg dark:text-gray-300 text-gray-700 uppercase tracking-wider">
                        {{ __($exception->getMessage() ?: 'Forbidden') }}
                    </div>
                </div>

                <div class="mt-8">
                    <a href="{{ url('/app') }}" class="btn-home">
                        {{ __('Back to Home') }}
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>

@php
    $pageTitle = trim($__env->yieldContent('title')) . ' | APIs Hub';
@endphp
<x-layouts.public 
    :title="$pageTitle"
    :og-type="'article'"
    :portals="$portals ?? []"
    :gtm-id="$gtmId ?? null"
>
    <!-- Main Content Flow -->
    <main class="relative pt-36 sm:pt-44 pb-24 px-6 sm:px-8 flex-grow flex flex-col items-center">
        <div class="w-full max-w-4xl legal-document">
            @yield('content')
        </div>
    </main>
</x-layouts.public>

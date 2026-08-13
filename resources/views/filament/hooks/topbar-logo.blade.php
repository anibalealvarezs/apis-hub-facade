<div class="fi-topbar-hook-start flex items-center gap-8 flex-1">
    <a
        href="{{ filament()->getHomeUrl() ?? url('/') }}"
        class="topbar-logo-anchor hidden lg:flex items-center me-6 shrink-0"
        x-show="!$store.sidebar.isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-x-[-10px]"
        x-transition:enter-end="opacity-100 translate-x-0"
    >
        <!-- Light Mode Logo -->
        <img src="{{ asset('images/branding/apishub-trans-620.webp') }}" class="h-10 w-auto dark:hidden"
             alt="APIs Hub"/>

        <!-- Dark Mode Logo -->
        <img src="{{ asset('images/branding/apishub-trans-light-620.webp') }}" class="h-10 w-auto hidden dark:flex"
             alt="APIs Hub"/>
    </a>

    @livewire('global-infrastructure-status')
</div>

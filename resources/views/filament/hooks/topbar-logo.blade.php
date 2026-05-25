<a 
    href="{{ filament()->getHomeUrl() ?? url('/') }}" 
    class="hidden lg:flex items-center me-6" 
    x-show="!$store.sidebar.isOpen" 
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-x-[-10px]"
    x-transition:enter-end="opacity-100 translate-x-0"
>
    <!-- Light Mode Logo -->
    <img src="{{ asset('images/branding/apishub-trans-620.webp') }}" class="h-10 w-auto dark:hidden" alt="APIs Hub" />
    
    <!-- Dark Mode Logo -->
    <img src="{{ asset('images/branding/apishub-trans-light-620.webp') }}" class="h-10 w-auto hidden dark:block" alt="APIs Hub" />
</a>

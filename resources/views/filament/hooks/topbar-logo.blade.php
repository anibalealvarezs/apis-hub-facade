<a 
    href="{{ filament()->getHomeUrl() ?? url('/') }}" 
    class="flex items-center me-6" 
    x-show="!$store.sidebar.isOpen"
>
    <span class="font-bold text-red-500">TESTING TOPBAR</span>
    <!-- Light Mode Logo -->
    <img src="{{ asset('images/branding/apishub-trans-620.webp') }}" class="h-10 w-auto dark:hidden" alt="APIs Hub" />
    
    <!-- Dark Mode Logo -->
    <img src="{{ asset('images/branding/apishub-trans-light-620.webp') }}" class="h-10 w-auto hidden dark:block" alt="APIs Hub" />
</a>

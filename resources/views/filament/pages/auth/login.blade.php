<x-filament-panels::page.simple>
    @if (filament()->hasRegistration())
        <x-slot name="subheading">
            {{ __('filament-panels::pages/auth/login.actions.register.before') }}
            {{ $this->registerAction }}
        </x-slot>
    @endif

    <div class="mb-6 text-center">
        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200">Welcome Back to APIs Hub</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
            Log in to access your unified marketing dashboard. 
            Reconnect and manage your Meta Ads, Google Ads, Shopify, and Klaviyo data instantly.
        </p>
    </div>

    <div class="mb-4 flex justify-center">
        <a href="{{ url('/') }}" class="text-sm font-medium text-brand-blue hover:text-blue-500 hover:underline flex items-center gap-1 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Return to Home
        </a>
    </div>

    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}
        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    <!-- Social Authentication Divider -->
    <div class="relative my-6 flex items-center justify-center">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200 dark:border-gray-800"></div>
        </div>
        <div class="relative flex justify-center text-xs uppercase">
            <span class="bg-gray-50 dark:bg-gray-950 px-3 text-gray-500 dark:text-gray-400 font-medium tracking-wider">
                {{ __('or') }}
            </span>
        </div>
    </div>

    <!-- Continue with Google Button -->
    <x-filament::button
        :href="route('auth.google.redirect')"
        tag="a"
        color="gray"
        outlined
        class="w-full py-2.5 shadow-sm"
    >
        <div class="inline-flex items-center justify-center">
            <svg class="w-4 h-4 mr-3 shrink-0" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span class="text-sm font-semibold tracking-normal">{{ __('Continue with Google') }}</span>
        </div>
    </x-filament::button>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
</x-filament-panels::page.simple>

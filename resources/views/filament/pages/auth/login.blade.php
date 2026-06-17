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
</x-filament-panels::page.simple>

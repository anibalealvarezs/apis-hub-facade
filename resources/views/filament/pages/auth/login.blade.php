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

    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}
        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>
</x-filament-panels::page.simple>

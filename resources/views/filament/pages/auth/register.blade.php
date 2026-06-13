<x-filament-panels::page.simple>
    @if (filament()->hasLogin())
        <x-slot name="subheading">
            {{ __('filament-panels::pages/auth/register.actions.login.before') }}
            {{ $this->loginAction }}
        </x-slot>
    @endif

    <div class="mb-6 text-center">
        <h2 class="text-lg font-bold text-brand-blue">Start Your Analytics Journey</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
            Create an APIs Hub account to instantly aggregate your advertising, social, and ecommerce metrics into high-performance dashboards. Free during Beta!
        </p>
    </div>

    <x-filament-panels::form wire:submit="register">
        {{ $this->form }}
        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>
</x-filament-panels::page.simple>

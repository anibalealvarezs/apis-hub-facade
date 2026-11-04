<header class="fi-header flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    @if (filament()->hasBreadcrumbs())
        <x-filament::breadcrumbs :breadcrumbs="$this->getBreadcrumbs()" />
    @endif

    <div class="flex shrink-0 items-center gap-3 sm:ml-auto">
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE, scopes: $this->getRenderHookScopes()) }}

        <x-filament::actions :actions="$this->getCachedHeaderActions()" />

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::PAGE_HEADER_ACTIONS_AFTER, scopes: $this->getRenderHookScopes()) }}
    </div>
</header>

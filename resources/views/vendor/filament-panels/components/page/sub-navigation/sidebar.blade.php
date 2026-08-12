@props([
    'navigation',
])

<div
    x-data
    x-bind:class="$store.subnav.isOpen ? 'fi-subnav-open' : 'fi-subnav-closed'"
    {{ $attributes->class(['fi-page-sub-navigation-sidebar-ctn hidden flex-col overflow-hidden md:flex']) }}
>
    <button
        type="button"
        x-on:click="$store.subnav.toggle()"
        x-bind:aria-expanded="$store.subnav.isOpen"
        x-bind:title="$store.subnav.isOpen ? @js(__('Collapse')) : @js(__('Expand'))"
        class="fi-subnav-toggle"
    >
        <x-filament::icon
            x-show="$store.subnav.isOpen"
            icon="heroicon-o-chevron-left"
            class="h-5 w-5"
        />
        <x-filament::icon
            x-show="! $store.subnav.isOpen"
            icon="heroicon-o-chevron-right"
            class="h-5 w-5"
        />
    </button>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::PAGE_SUB_NAVIGATION_SIDEBAR_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <ul
        wire:ignore
        class="fi-page-sub-navigation-sidebar flex flex-col gap-y-7"
    >
        @foreach ($navigation as $navigationGroup)
            <x-filament-panels::sidebar.group
                :active="$navigationGroup->isActive()"
                :collapsible="$navigationGroup->isCollapsible()"
                :icon="$navigationGroup->getIcon()"
                :items="$navigationGroup->getItems()"
                :label="$navigationGroup->getLabel()"
                :sidebar-collapsible="false"
                sub-navigation
                :attributes="\Filament\Support\prepare_inherited_attributes($navigationGroup->getExtraSidebarAttributeBag())"
            />
        @endforeach
    </ul>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::PAGE_SUB_NAVIGATION_SIDEBAR_AFTER, scopes: $this->getRenderHookScopes()) }}
</div>

<script>
    (() => {
        const registerSubNavStore = () => {
            if (window.Alpine.store('subnav')) {
                return
            }

            window.Alpine.store('subnav', {
                isOpen: window.Alpine.$persist(true).as('subnavOpen'),

                toggle() {
                    this.isOpen = ! this.isOpen
                },
            })
        }

        if (window.Alpine) {
            registerSubNavStore()
        } else {
            document.addEventListener('alpine:init', registerSubNavStore)
        }
    })()
</script>

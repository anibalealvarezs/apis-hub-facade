<x-filament-panels::page>
    <div style="background: red; color: white; padding: 10px;">
        Tenant ID: {{ \Filament\Facades\Filament::getTenant()?->id ?? 'NULL' }} <br>
        Default DB: {{ \App\Models\Dashboard::where('project_id', \Filament\Facades\Filament::getTenant()?->id)->where('is_default', true)->first()?->id ?? 'NULL' }}
    </div>
    @if(isset($this->dashboard))
        @include('filament.app.pages.partials.dashboard-view-content')
    @else
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <x-filament::icon name="heroicon-o-presentation-chart-line"
                              class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4"/>
            <p class="text-gray-500 dark:text-gray-400 text-lg">Create your first dashboard and set it as default</p>
            <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Dashboards set as default will automatically appear on this page.</p>
        </div>
    @endif
</x-filament-panels::page>

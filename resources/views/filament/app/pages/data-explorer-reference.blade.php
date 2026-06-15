<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose prose-sm max-w-none text-gray-500 dark:text-gray-400 mb-2">
            {{ __('Understand the purpose of the Data Explorer pages and how they differ from full-fledged analytics dashboards.') }}
        </div>

        @php
            $id = \Illuminate\Support\Str::slug(__('Exploring Cached Data'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="{ copied: false }">
                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-5 w-5 text-primary-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           navigator.clipboard.writeText(window.location.origin + window.location.pathname + '#' + $id);
                           copied = true;
                           setTimeout(() => copied = false, 2000);
                       ">
                        <span>{{ __('Exploring Cached Data') }}</span>
                        <x-filament::icon 
                            icon="heroicon-o-link" 
                            class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" 
                            x-show="!copied"
                        />
                        <x-filament::icon 
                            icon="heroicon-o-check" 
                            class="h-4 w-4 text-success-500" 
                            x-show="copied"
                            style="display: none;"
                        />
                    </a>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {{ __('While the Data Explorer pages contain charts, tables, and graphs, they are not intended to be your final BI analytics dashboards.') }}
                </p>
                <p>
                    {!! __('Instead, these pages act as a :strong_start transparency and accuracy tool :strong_end. They allow you to directly explore the raw and normalized data that the syncing engine has cached in your database, confirming its reliability before you connect APIs Hub to your own external visualization tools (like Looker Studio or PowerBI).', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Maximal Granularity and Interaction'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="{ copied: false }">
                    <x-filament::icon icon="heroicon-o-chart-pie" class="h-5 w-5 text-indigo-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           navigator.clipboard.writeText(window.location.origin + window.location.pathname + '#' + $id);
                           copied = true;
                           setTimeout(() => copied = false, 2000);
                       ">
                        <span>{{ __('Maximal Granularity and Interaction') }}</span>
                        <x-filament::icon 
                            icon="heroicon-o-link" 
                            class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" 
                            x-show="!copied"
                        />
                        <x-filament::icon 
                            icon="heroicon-o-check" 
                            class="h-4 w-4 text-success-500" 
                            x-show="copied"
                            style="display: none;"
                        />
                    </a>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {{ __('Even though they serve as exploration tools, these charts and tables provide analytics data with maximal granularity in an extremely interactive way.') }}
                </p>
                <p>
                    {{ __('By normalizing and restructuring the data schemas, APIs Hub allows you to slice, filter, and compare metrics in ways that are often more powerful and flexible than what is available natively within the source platform\'s own interface.') }}
                </p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

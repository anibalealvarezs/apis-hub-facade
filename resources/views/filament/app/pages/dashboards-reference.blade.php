<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose prose-sm max-w-none text-gray-500 dark:text-gray-400 mb-2">
            {{ __('Understand the purpose of Dashboards, the Widget Builder, and how they fit into the analytics workflow.') }}
        </div>

        @php
            $id = \Illuminate\Support\Str::slug(__('What is a Dashboard?'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-squares-2x2" class="h-5 w-5 text-primary-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('What is a Dashboard?') }}</span>
                        <x-filament::icon
                            icon="heroicon-o-link"
                            class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity"
                            x-show="!copied"
                        />
                        <x-filament::icon
                            icon="heroicon-o-check"
                            class="h-4 w-4 text-success-500"
                            x-show="copied"
                            x-cloak
                        />
                    </a>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {{ __('A Dashboard is your personalized analytics workspace. While the Data Explorer pages are designed for maximum-granularity exploration of cached data, Dashboards are the curated, visual layer on top — where you assemble exactly the metrics that matter to you into a clean, shareable view.') }}
                </p>
                <p>
                    {!! __('Each dashboard is built from :strong_start widgets :strong_end that you place freely on a drag-and-drop grid. Widgets can come from three types of sources: :strong_start Custom KPIs :strong_end (analytics-engine computations), :strong_start raw metrics :strong_end (direct aggregations), and :strong_start Derived Metrics :strong_end (locally computed formula series).', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('The Dashboard Builder'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-squares-plus" class="h-5 w-5 text-indigo-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('The Dashboard Builder') }}</span>
                        <x-filament::icon
                            icon="heroicon-o-link"
                            class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity"
                            x-show="!copied"
                        />
                        <x-filament::icon
                            icon="heroicon-o-check"
                            class="h-4 w-4 text-success-500"
                            x-show="copied"
                            x-cloak
                        />
                    </a>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {{ __('The Dashboard Builder is a visual, drag-and-drop editor. You start by adding widgets to the canvas, then resize, reorder, and configure each one independently:') }}
                </p>
                <ul>
                    <li><strong>{{ __('Add') }}:</strong> {{ __('Pick a source type (KPI, Metric, or Derived Metric), choose the specific source, and drop a new widget onto the grid.') }}</li>
                    <li><strong>{{ __('Configure') }}:</strong> {{ __('For every widget you define the channel, metric, granularity, date range behavior, asset selection, and the visual widget type.') }}</li>
                    <li><strong>{{ __('Arrange') }}:</strong> {{ __('Drag widgets anywhere on the responsive grid — the layout adapts automatically across desktop and mobile widths.') }}</li>
                    <li><strong>{{ __('Duplicate') }}:</strong> {{ __('Clone any widget to quickly build variations of the same visualization.') }}</li>
                </ul>
                <p>
                    {{ __('The builder always keeps you in control: it tracks unsaved changes against the last saved version, and every widget-level setting is stored per widget so the layout and data configuration are preserved exactly as you designed them.') }}
                </p>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Widget Types'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-chart-bar" class="h-5 w-5 text-fuchsia-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Widget Types') }}</span>
                        <x-filament::icon
                            icon="heroicon-o-link"
                            class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity"
                            x-show="!copied"
                        />
                        <x-filament::icon
                            icon="heroicon-o-check"
                            class="h-4 w-4 text-success-500"
                            x-show="copied"
                            x-cloak
                        />
                    </a>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {{ __('APIs Hub offers nine widget types, each suited to a different analytical purpose. Available options depend on the widget source:') }}
                </p>
                <ul>
                    <li><strong>{{ __('Number Tile') }}:</strong> {{ __('A single large number for totals — the go-to for headline KPIs.') }}</li>
                    <li><strong>{{ __('Line Chart') }}:</strong> {{ __('Continuous trends over time.') }}</li>
                    <li><strong>{{ __('Bar Chart') }}:</strong> {{ __('Side-by-side comparison of discrete volumes.') }}</li>
                    <li><strong>{{ __('Scatter Plot') }}:</strong> {{ __('Correlations and trendlines between two variables.') }}</li>
                    <li><strong>{{ __('Combo Chart') }}:</strong> {{ __('Dual-axis bars and lines (e.g., MACD-style indicators).') }}</li>
                    <li><strong>{{ __('Table') }}:</strong> {{ __('Detailed, row-by-row data view.') }}</li>
                    <li><strong>{{ __('Gauge') }}:</strong> {{ __('Percentage or progress towards a target.') }}</li>
                    <li><strong>{{ __('Sparkline') }}:</strong> {{ __('Minimalist trendline without axes.') }}</li>
                    <li><strong>{{ __('Anomaly Chart') }}:</strong> {{ __('Highlights statistical outliers (only available for Custom KPI sources).') }}</li>
                </ul>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Widget Data Sources'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-arrow-path" class="h-5 w-5 text-emerald-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Widget Data Sources') }}</span>
                        <x-filament::icon
                            icon="heroicon-o-link"
                            class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity"
                            x-show="!copied"
                        />
                        <x-filament::icon
                            icon="heroicon-o-check"
                            class="h-4 w-4 text-success-500"
                            x-show="copied"
                            x-cloak
                        />
                    </a>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <ul>
                    <li>
                        <strong>{{ __('Custom KPI (Analytics Engine)') }}:</strong>
                        {{ __('Widgets that run a saved Custom KPI against the analytics engine. These support the full widget-type set, including anomaly detection and scatter plots.') }}
                    </li>
                    <li>
                        <strong>{{ __('Metric (Raw Aggregation)') }}:</strong>
                        {{ __('Direct, raw aggregations of a single metric for a channel. Ideal for simple totals and trends without the overhead of a full KPI.') }}
                    </li>
                    <li>
                        <strong>{{ __('Derived Metric (Computed Series)') }}:</strong>
                        {{ __('Widgets that evaluate a saved Derived Metric locally and render the resulting computed series as a tile, chart, gauge, sparkline, or table.') }}
                    </li>
                </ul>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Asset Groups and Access'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5 text-sky-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Asset Groups and Access') }}</span>
                        <x-filament::icon
                            icon="heroicon-o-link"
                            class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity"
                            x-show="!copied"
                        />
                        <x-filament::icon
                            icon="heroicon-o-check"
                            class="h-4 w-4 text-success-500"
                            x-show="copied"
                            x-cloak
                        />
                    </a>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {{ __('Widgets resolve their data through a progressive chain: dashboard-level controls set defaults, and each widget can override them. For every asset selection, the viewer\'s permissions are enforced — restricted collaborators only ever see widgets filtered to the assets they are allowed to access.') }}
                </p>
                <p>
                    {{ __('When a widget has no valid assets for a given viewer, it is clearly marked as having missing assets instead of silently showing data the viewer is not entitled to see.') }}
                </p>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Versioning'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5 text-purple-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Versioning') }}</span>
                        <x-filament::icon
                            icon="heroicon-o-link"
                            class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity"
                            x-show="!copied"
                        />
                        <x-filament::icon
                            icon="heroicon-o-check"
                            class="h-4 w-4 text-success-500"
                            x-show="copied"
                            x-cloak
                        />
                    </a>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {{ __('Dashboards support full-state versioning. Each saved version snapshots not only the dashboard name, description, and layout, but also the complete widget configuration — so restoring a version faithfully restores every widget to its exact historical state.') }}
                </p>
                <ul>
                    <li><strong>{{ __('Save Version') }}:</strong> {{ __('Capture the current state with an optional label.') }}</li>
                    <li><strong>{{ __('Version History') }}:</strong> {{ __('Browse, view, and restore any past snapshot.') }}</li>
                    <li><strong>{{ __('Duplicate') }}:</strong> {{ __('Clone the current dashboard, or rebuild a new dashboard from any historical version.') }}</li>
                    <li><strong>{{ __('Prune Versions') }}:</strong> {{ __('Remove old versions to keep your history tidy.') }}</li>
                </ul>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Sharing and Public Views'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-share" class="h-5 w-5 text-rose-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Sharing and Public Views') }}</span>
                        <x-filament::icon
                            icon="heroicon-o-link"
                            class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity"
                            x-show="!copied"
                        />
                        <x-filament::icon
                            icon="heroicon-o-check"
                            class="h-4 w-4 text-success-500"
                            x-show="copied"
                            x-cloak
                        />
                    </a>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {{ __('Dashboards can be shared in several ways, depending on your plan:') }}
                </p>
                <ul>
                    <li><strong>{{ __('Collaborators') }}:</strong> {{ __('Share the dashboard directly with project collaborators, optionally restricting which asset groups they can see.') }}</li>
                    <li><strong>{{ __('Public dashboards') }}:</strong> {{ __('Mark a dashboard as public to make it accessible via a dedicated public view.') }}</li>
                    <li><strong>{{ __('Embed') }}:</strong> {{ __('Public dashboards can be embedded on external sites via a snippet with configurable max-height and full widget interaction, including expandable pop-out widgets.') }}</li>
                </ul>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

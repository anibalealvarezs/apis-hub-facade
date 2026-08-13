<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose prose-sm max-w-none text-gray-500 dark:text-gray-400 mb-2">
            {{ __('Learn how Derived Metrics let you create reusable, computed metrics on top of your synced analytics data.') }}
        </div>

        @php
            $id = \Illuminate\Support\Str::slug(__('What is a Derived Metric?'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-calculator" class="h-5 w-5 text-primary-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('What is a Derived Metric?') }}</span>
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
                    {{ __('A Derived Metric is a formula that combines one or more source series into a brand-new computed metric. Instead of receiving a single number per asset, the formula is evaluated for every day in your data range, producing a complete time series that you can display, compare, and reuse.') }}
                </p>
                <p>
                    {!! __('Typical examples are metrics like :strong_start Cost per Click (CPC), Click-Through Rate (CTR), Cost per Acquisition (CPA), Conversion Rate (CVR), or ROAS :strong_end — but any combination expressible as a formula is possible.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
                <p>
                    {{ __('Derived Metrics are computed locally from independently fetched source series, so they work consistently across every connected channel — including cross-channel formulas that blend data from multiple platforms into a single, normalized metric.') }}
                </p>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Source Series'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-5 w-5 text-indigo-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Source Series') }}</span>
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
                    {{ __('Every Derived Metric is built from at least two source series. Each series defines:') }}
                </p>
                <ul>
                    <li><strong>{{ __('Label') }}:</strong> {{ __('A short name (a, b, c...) used inside the formula.') }}</li>
                    <li><strong>{{ __('Channel') }}:</strong> {{ __('The platform the series comes from (e.g., Facebook Marketing, Facebook Organic, Google Search Console, Google Analytics).') }}</li>
                    <li><strong>{{ __('Metric') }}:</strong> {{ __('The raw metric to fetch from that channel (e.g., spend, clicks, impressions, reach, results).') }}</li>
                    <li><strong>{{ __('Granularity') }}:</strong> {{ __('The time grain of the series (daily, weekly, monthly).') }}</li>
                    <li><strong>{{ __('Asset Group / Asset Filter') }}:</strong> {{ __('Optionally restricts the series to specific assets, so the formula runs against a curated set rather than all connected assets.') }}</li>
                </ul>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('The Formula'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-variable" class="h-5 w-5 text-fuchsia-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('The Formula') }}</span>
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
                    {{ __('The formula is stored as a structured expression tree (an AST) that can reference your source series by label, and can reference other Derived Metrics for recursive, multi-level calculations.') }}
                </p>
                <p>{{ __('Supported operations include:') }}</p>
                <ul>
                    <li><strong>{{ __('Ratio (/)') }}:</strong> {{ __('Divide one series by another (e.g., spend / clicks = CPC).') }}</li>
                    <li><strong>{{ __('Average') }}:</strong> {{ __('Mean of a series over the requested window.') }}</li>
                    <li><strong>{{ __('Minimum / Maximum') }}:</strong> {{ __('Extremes of a series.') }}</li>
                    <li><strong>{{ __('Absolute Difference') }}:</strong> {{ __('Positive difference between two series (e.g., paid reach vs organic reach).') }}</li>
                    <li><strong>{{ __('Percentage Change') }}:</strong> {{ __('Day-over-day or period-over-period change.') }}</li>
                </ul>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Predefined Templates'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-squares-plus" class="h-5 w-5 text-emerald-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Predefined Templates') }}</span>
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
                    {{ __('APIs Hub ships with a library of predefined formulas covering the most common marketing calculations. When you create a Derived Metric you can either start from one of these templates or build your own from scratch. Templates are filtered to match your active channels, so only applicable options are offered.') }}
                </p>
                <ul>
                    <li>{{ __('Single-Channel Paid Media: CPC, CTR, CPA, CVR, ROAS, cost per conversion, result rate, engagement metrics.') }}</li>
                    <li>{{ __('Single-Channel Organic Social: engagement rate, reach efficiency, impression engagement.') }}</li>
                    <li>{{ __('Single-Channel SEO: CTR, click-position and impression-position efficiency.') }}</li>
                    <li>{{ __('Cross-Channel: blended CPC/CPA/CTR/ROAS, budget share ratio, paid-organic reach ratios, revenue per click.') }}</li>
                </ul>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Evaluation and Caching'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-bolt" class="h-5 w-5 text-amber-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Evaluation and Caching') }}</span>
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
                    {{ __('Each source series is fetched independently, and the formula is evaluated locally in PHP — it never round-trips through the analytics engine. Results are cached using a cache key derived from the full set of request controls (date range, granularity, asset group and assets), so repeated views with the same settings are served instantly.') }}
                </p>
                <p>
                    {{ __('Because the cache key is sensitive to the exact controls, changing any of the selection (dates, granularity, or assets) transparently produces and caches the appropriate result.') }}
                </p>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Where Derived Metrics Can Be Used'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-squares-2x2" class="h-5 w-5 text-sky-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Where Derived Metrics Can Be Used') }}</span>
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
                    {{ __('Derived Metrics are first-class citizens of the analytics stack:') }}
                </p>
                <ul>
                    <li><strong>{{ __('As Dashboard Widgets') }}:</strong> {{ __('Add a Derived Metric directly to any dashboard as a number tile, line chart, bar chart, gauge, sparkline, or table.') }}</li>
                    <li><strong>{{ __('Inside Custom KPIs') }}:</strong> {{ __('A Derived Metric can be used as a dependent or independent variable within a Custom KPI formula, chaining computations together.') }}</li>
                    <li><strong>{{ __('Nested') }}:</strong> {{ __('Derived Metrics can reference other Derived Metrics, allowing multi-level calculations built from reusable building blocks.') }}</li>
                </ul>
                <p class="text-xs italic mt-2 text-gray-500">
                    {{ __('Note: Each consumer applies its own progressive asset restriction on top of the Derived Metric definition, so a metric is only ever evaluated against assets the viewer is allowed to see.') }}
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
                    {{ __('Derived Metrics support manual versioning: you can save a labeled version of the current definition, restore any historical version, duplicate the metric, and prune old versions. This gives you a full audit trail of how your metrics have evolved over time.') }}
                </p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

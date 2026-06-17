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
        @php
            $id = \Illuminate\Support\Str::slug(__('Performance Correlations'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="{ copied: false }">
                    <x-filament::icon icon="heroicon-o-arrows-right-left" class="h-5 w-5 text-fuchsia-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           navigator.clipboard.writeText(window.location.origin + window.location.pathname + '#' + $id);
                           copied = true;
                           setTimeout(() => copied = false, 2000);
                       ">
                        <span>{{ __('Performance Correlations') }}</span>
                        <x-filament::icon icon="heroicon-o-link" class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" x-show="!copied" />
                        <x-filament::icon icon="heroicon-o-check" class="h-4 w-4 text-success-500" x-show="copied" style="display: none;" />
                    </a>
                </div>
            </x-slot>

            <div class="prose dark:prose-invert max-w-none text-sm">
                <p>
                    {{ __('The Performance Correlations dashboard is an advanced exploratory analytics tool designed to help marketers find hidden relationships, synergies, and cannibalizations across their entire digital ecosystem.') }}
                </p>

                <h4 class="mt-6 font-bold text-gray-900 dark:text-white">{{ __('Core Concepts') }}</h4>
                <ul class="space-y-4">
                    <li>
                        <strong>{{ __('1. Pearson Correlation Coefficient:') }}</strong> {{ __('At the heart of the dashboard is the Pearson Correlation Coefficient, a mathematical formula that returns a value between -1 and +1:') }}
                        <ul class="list-disc ml-5 mt-2">
                            <li><strong>+1 (Strong Positive):</strong> {{ __('When Metric A goes up, Metric B goes up proportionally.') }}</li>
                            <li><strong>0 (No Correlation):</strong> {{ __('The two metrics have completely random movement relative to each other.') }}</li>
                            <li><strong>-1 (Strong Negative):</strong> {{ __('When Metric A goes up, Metric B goes down proportionally.') }}</li>
                        </ul>
                        <p class="text-xs italic mt-2 text-gray-500">{{ __('Note: Correlation does NOT imply causation. A strong correlation means the metrics move together, but it does not prove that one caused the other.') }}</p>
                    </li>
                    <li>
                        <strong>{{ __('2. Analysis Levels:') }}</strong> {{ __('Raw numbers can sometimes hide true volatility. The dashboard allows you to transform the data before correlating it:') }}
                        <ul class="list-disc ml-5 mt-2">
                            <li><strong>{{ __('Level (Original)') }}:</strong> {{ __('The raw, daily numbers (e.g., total impressions).') }}</li>
                            <li><strong>{{ __('Z-Score (Normalized)') }}:</strong> {{ __('Our recommended default. It converts the numbers into "Standard Deviations". This allows you to perfectly compare a metric that has millions of views with a metric that ranges from 1 to 10 on the same visual axis.') }}</li>
                            <li><strong>{{ __('1st Difference (Δ)') }}:</strong> {{ __('Day-over-day change. It answers the question: "Did the metric grow or shrink today compared to yesterday?" Useful for removing long-term seasonal trends.') }}</li>
                            <li><strong>{{ __('2nd Difference (ΔΔ)') }}:</strong> {{ __('The acceleration of change. Useful for highly exponential growth curves.') }}</li>
                        </ul>
                    </li>
                    <li>
                        <strong>{{ __('3. Lag (Time Shifts):') }}</strong> {{ __('Marketing effects are rarely instantaneous. If you run a branding campaign on Facebook today, a user might not search for your brand on Google until 3 days later. The "Lag" selector allows you to shift a metric forward or backward in time.') }}
                        <br><em>{{ __('Example:') }}</em> {{ __('Comparing Facebook Spend (Lag 0) vs Google Search Clicks (Lag +3) will test if Monday\'s spend correlates with Thursday\'s clicks.') }}
                    </li>
                </ul>

                <h4 class="mt-8 font-bold text-gray-900 dark:text-white">{{ __('Understanding the Visualizations') }}</h4>
                <ul class="space-y-4">
                    <li>
                        <strong>{{ __('The Comparison View:') }}</strong> {{ __('This is the main dual-line chart. If you are using Z-Score, the zero-line (0) represents the monthly average for each metric. Peaks above 0 are good days, valleys below 0 are bad days. If the blue and red lines dance together, you have a strong positive correlation.') }}
                    </li>
                    <li>
                        <strong>{{ __('Rolling Correlation (7-Day Window):') }}</strong> {{ __('A single correlation number for a 30-day period can hide when a campaign stopped working. This chart calculates the Pearson correlation using a sliding 7-day window. It draws a line between -1 and +1. If you see the line drop from +0.8 to 0 in the middle of the month, that is the exact day your campaign suffered from "Ad Fatigue" or an algorithm update broke the synergy.') }}
                    </li>
                    <li>
                        <strong>{{ __('Scatter Plot (Distribution):') }}</strong> {{ __('This chart removes time entirely. It plots Metric A on the X-axis and Metric B on the Y-axis. Look for clusters or lines. This is extremely useful for finding "diminishing returns"—for example, you might notice that once Spend crosses $500/day on the X-axis, the Conversions on the Y-axis stop growing and flatten out.') }}
                    </li>
                </ul>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

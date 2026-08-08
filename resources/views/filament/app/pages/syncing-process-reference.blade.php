<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose prose-sm max-w-none text-gray-500 dark:text-gray-400 mb-2">
            {{ __('Understand how APIs Hub\'s syncing engine operates, including scheduling, worker availability, and resilience.') }}
        </div>

        @php
            $id = \Illuminate\Support\Str::slug(__('What is the Syncing Engine?'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-server-stack" class="h-5 w-5 text-primary-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('What is the Syncing Engine?') }}</span>
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
                    {{ __('APIs Hub operates on a decoupled architecture. While you manage your configurations in the administrative panel, the actual data extraction, normalization, and persistence are handled by a dedicated, high-performance background worker known as the Syncing Engine.') }}
                </p>
                <p>
                    {!! __('Crucially, the syncing engine and its underlying database are :strong_start unique per project and completely isolated :strong_end.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
                <ul>
                    <li>{{ __('If a single account (e.g., a Facebook Page) is connected to two different projects, it will be processed independently by two separate syncing engines and cached in two separate databases.') }}</li>
                    <li>{{ __('As a consequence, removing or modifying an asset in one project has absolutely no effect on the syncing process of that same asset in any other project.') }}</li>
                </ul>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Daily Granularity & Today\'s Data'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-calendar" class="h-5 w-5 text-purple-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Daily Granularity & Today\'s Data') }}</span>
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
                    {!! __('APIs Hub :strong_start does not cache data for the current date :strong_end. The standard granularity across the entire platform is strictly daily.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
                <p>
                    {{ __('Today\'s data is inherently incomplete and constantly fluctuating. Because our goal is to provide stable, actionable analytics and reliable cross-channel comparisons, data is only synced and locked in once the day has officially closed.') }}
                </p>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Historic Caching Schedule'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-archive-box-arrow-down" class="h-5 w-5 text-indigo-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Historic Caching Schedule') }}</span>
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
                    {!! __('When a new asset is enabled and locked, the syncing engine schedules a one-time :strong_start Historic Caching Job :strong_end.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
                <p>
                    {{ __('This job is responsible for backfilling your data. Depending on your subscription tier and the specific channel limits, this process pulls historical data from the past months or years. Because this involves heavy data transfer and API rate limits, historic caching jobs are queued and executed gradually to ensure stability.') }}
                </p>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Recent Syncing Schedule'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-arrow-path" class="h-5 w-5 text-success-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Recent Syncing Schedule') }}</span>
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
                    {!! __('For ongoing data updates, the engine relies on the :strong_start Recent Syncing Schedule :strong_end.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
                <p>
                    {!! __('These jobs run frequently to capture new data. To account for delayed attributions and platform data updates (such as a purchase being attributed to an ad click 2 days later), the recent syncs always cover a rolling :strong_start 3-day timeframe :strong_end.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
                <p>
                    {{ __('This overlapping methodology guarantees that delayed metrics are accurately captured and updated in your cache.') }}
                </p>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Workers Availability'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-cpu-chip" class="h-5 w-5 text-warning-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Workers Availability') }}</span>
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
                    {{ __('The speed and concurrency of data extraction depend directly on the processing power assigned to your project, executed by background workers.') }}
                </p>
                <ul>
                    <li>{!! __('Projects on the :strong_start Free Tier :strong_end run at minimum capacity, processing queues sequentially. Syncs may take longer during peak hours.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li>{!! __('Projects on :strong_start Pro Tiers :strong_end are assigned to more powerful engines with robust parallel processing. This ensures maximum API exploitation based on known rate limits and optimal architecture.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                </ul>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Sync Engine Resilience'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5 text-green-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Sync Engine Resilience') }}</span>
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
                    {{ __('Third-party APIs are often unpredictable, prone to sudden outages, rate-limiting, and timeout errors. APIs Hub\'s syncing engine is built with high resilience to handle these scenarios gracefully.') }}
                </p>
                <p>
                    {{ __('Failed jobs are automatically retried using exponential backoff strategies. If a platform\'s API completely fails, the engine suspends syncing for that specific channel to prevent IP bans, resuming automatically once the provider resolves the outage.') }}
                </p>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('How to read the Telemetry'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-chart-bar-square" class="h-5 w-5 text-blue-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('How to read the Telemetry') }}</span>
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
                    {{ __('The Data Sync page provides real-time telemetry into the background workers. It allows you to track the exact status of your data queues and overall completion percentages.') }}
                </p>
                <p>
                    {!! __('It is important to understand that :strong_start "jobs" are just technical abstractions :strong_end and do not represent data size or volume. APIs Hub splits data extraction into stable syncing batches with strong dependency logic to guarantee that cached data is properly classified and can be cross-channel compared.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}
                </p>
                <p>
                    {{ __('While jobs are individually monitored, you should primarily focus on the overall completion percentage. The detailed job telemetry ultimately serves as debugging information that is extremely useful for our technical team and support staff in case of persistent errors.') }}
                </p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

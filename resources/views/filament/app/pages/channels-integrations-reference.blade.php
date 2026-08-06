<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose prose-sm max-w-none text-gray-500 dark:text-gray-400 mb-2">
            {{ __('Check the current availability and development status of data source integrations.') }}
        </div>

        @php
            $id = \Illuminate\Support\Str::slug(__('Available Channels'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 text-success-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Available Channels') }}</span>
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
                    {{ __('These channels are fully stable, actively maintained, and available for use in production projects.') }}
                </p>
                <ul>
                    <li>{!! __(':strong_start Meta :strong_end: Facebook Marketing, Facebook Organic.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li>{!! __(':strong_start Google :strong_end: Google Search Console.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                </ul>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('In Testing Phase'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-beaker" class="h-5 w-5 text-warning-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('In Testing Phase') }}</span>
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
                    {{ __('These channels are currently undergoing beta testing to ensure data accuracy and edge-case resilience.') }}
                </p>
                <ul>
                    <li>{!! __(':strong_start Meta :strong_end: Facebook Leads.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li>{!! __(':strong_start Google :strong_end: Google Analytics, Google Ads.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                </ul>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('In Development'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-code-bracket" class="h-5 w-5 text-blue-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('In Development') }}</span>
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
                    {{ __('Our engineering team is actively building the normalization and synchronization logic for these channels.') }}
                </p>
                <ul>
                    <li>{!! __(':strong_start Google :strong_end: Google Business Profile.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li>{!! __(':strong_start Klaviyo :strong_end: Events, Metrics.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li>{!! __(':strong_start Shopify :strong_end: eCommerce.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li>{!! __(':strong_start TikTok :strong_end: TikTok Leads, TikTok Marketing, TikTok Organic.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                </ul>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Further Integrations'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-map" class="h-5 w-5 text-primary-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="
                           copy('{{ $id }}');
                       ">
                        <span>{{ __('Further Integrations') }}</span>
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
                    {{ __('These channels are part of our upcoming roadmap and will be developed in future release cycles.') }}
                </p>
                <ul>
                    <li>{!! __(':strong_start Amazon :strong_end: Marketplace (eCommerce), Amazon Marketing.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li>{!! __(':strong_start LinkedIn :strong_end: LinkedIn Organic, LinkedIn Marketing, LinkedIn Leads.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li>{!! __(':strong_start Pinterest :strong_end: Pinterest Organic, Pinterest Marketing, Pinterest Leads.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                    <li>{!! __(':strong_start X :strong_end: X Organic, X Marketing, X Leads.', ['strong_start' => '<strong>', 'strong_end' => '</strong>']) !!}</li>
                </ul>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

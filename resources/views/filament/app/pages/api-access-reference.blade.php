<x-filament-panels::page>
    @php
        $baseUrl = $this->baseUrl;
        $apiKey = $this->apiKey;
    @endphp

    <div class="space-y-6" x-data="{
        baseUrl: @js($baseUrl),
        getApiKey() {
            return $wire.data?.app_api_key || @js($apiKey);
        },
        copySnippet(template) {
            const currentKey = this.getApiKey();
            const resolved = template
                .replace(/YOUR_API_KEY/g, currentKey || 'YOUR_API_KEY')
                .replace(/https:\/\/<subdomain>\.apis-hub\.cloud/g, this.baseUrl)
                .replace(/https:\/\/<your-project-subdomain>\.apis-hub\.cloud/g, this.baseUrl);
            navigator.clipboard.writeText(resolved);
        }
    }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <span>{{ __('Connect external reporting platforms, BI tools (PowerBI, Looker Studio), and custom applications directly to your dedicated APIs Hub node.') }}</span>
            <a href="{{ route('docs.api') }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline shrink-0">
                <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="w-4 h-4" />
                <span>{{ __('Full API Docs & OpenAPI Spec') }}</span>
            </a>
        </div>

        @php
            $id = \Illuminate\Support\Str::slug(__('API Overview & Authentication'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-key" class="h-5 w-5 text-primary-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="copy('{{ $id }}');">
                         <span>{{ __('API Overview & Authentication') }}</span>
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

            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ __('Every project has a dedicated public API key for authentication. All requests must provide this key via HTTP headers.') }}
                </p>

                {{-- Key Type Callout: Master Key vs User-Scoped Key --}}
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-gray-600 dark:text-gray-300 space-y-2">
                    <div class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                        <x-filament::icon icon="heroicon-m-shield-check" class="w-4 h-4 text-primary-500" />
                        <span>{{ __('API Key Security & Access Scopes') }}</span>
                    </div>
                    <ul class="list-disc list-inside space-y-1 pl-1">
                        <li>
                            <strong class="text-gray-900 dark:text-white">{{ __('Project Master API Key:') }}</strong>
                            {{ __('Assigned to Project Owners and Editors. Grants unrestricted read-only access across all connected accounts, channels, and asset groups.') }}
                        </li>
                        <li>
                            <strong class="text-gray-900 dark:text-white">{{ __('User-Scoped API Keys:') }}</strong>
                            {{ __('Assigned to Viewers and Collaborators. Restricts queries strictly to the specific asset groups granted to that user. Requests targeting unassigned accounts or channels are filtered out or denied.') }}
                        </li>
                    </ul>
                </div>

                {{-- Livewire API Key Field with Reveal & Rotate --}}
                <div class="mb-4">
                    {{ $this->form }}
                </div>

                {{-- Base Endpoint URL (Copyable) --}}
                <div>
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 block mb-1.5">
                        {{ __('Base Endpoint URL:') }}
                    </span>
                    <div class="api-ref-code-container" x-data="{ copied: false }">
                        <pre class="api-ref-code-block"><code class="select-all">{{ $baseUrl }}</code></pre>
                        <button type="button" 
                                @click="navigator.clipboard.writeText('{{ $baseUrl }}'); copied = true; setTimeout(() => copied = false, 2000)" 
                                class="api-ref-copy-btn">
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">
                        {{ __('Looking for the complete endpoint catalog, query parameters, and interactive testing?') }}
                        <a href="{{ route('docs.api') }}" target="_blank" class="underline text-primary-600 dark:text-primary-400 font-semibold">{{ __('Visit the Public API Documentation Portal') }} &rarr;</a>
                    </p>
                </div>

                {{-- Authentication Header Options --}}
                <div class="api-ref-header-options text-xs space-y-3">
                    <div>
                        <span class="text-gray-600 dark:text-gray-300 block mb-1 font-semibold">{{ __('Header Option 1 (Recommended):') }}</span>
                        <div class="api-ref-code-container" x-data="{ copied: false }">
                            <pre class="api-ref-code-block"><code class="select-all">X-API-Key: YOUR_API_KEY</code></pre>
                            <button type="button" 
                                    @click="copySnippet('X-API-Key: YOUR_API_KEY'); copied = true; setTimeout(() => copied = false, 2000)" 
                                    class="api-ref-copy-btn">
                                <span x-show="!copied">{{ __('Copy') }}</span>
                                <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                            </button>
                        </div>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-300 block mb-1 font-semibold">{{ __('Header Option 2 (Bearer Token):') }}</span>
                        <div class="api-ref-code-container" x-data="{ copied: false }">
                            <pre class="api-ref-code-block"><code class="select-all">Authorization: Bearer YOUR_API_KEY</code></pre>
                            <button type="button" 
                                    @click="copySnippet('Authorization: Bearer YOUR_API_KEY'); copied = true; setTimeout(() => copied = false, 2000)" 
                                    class="api-ref-copy-btn">
                                <span x-show="!copied">{{ __('Copy') }}</span>
                                <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Testing Connection (Ping / Heartbeat)'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-heart" class="h-5 w-5 text-rose-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="copy('{{ $id }}');">
                        <span>{{ __('Testing Connection (Ping / Heartbeat)') }}</span>
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

            <div class="space-y-3">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ __('To verify network connectivity and authenticate your API key, call the public ping endpoint:') }}
                </p>
                @php
                    $pingSnippet = "curl -s -H \"X-API-Key: YOUR_API_KEY\" \\\n  {$baseUrl}/api/v1/ping";
                @endphp
                <div class="api-ref-code-container" x-data="{ copied: false }">
                    <pre class="api-ref-code-block"><code class="select-all">{{ $pingSnippet }}</code></pre>
                    <button type="button" 
                            @click="copySnippet(@js($pingSnippet)); copied = true; setTimeout(() => copied = false, 2000)" 
                            class="api-ref-copy-btn">
                        <span x-show="!copied">{{ __('Copy') }}</span>
                        <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                    </button>
                </div>

                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Expected Response (HTTP 200):') }}</p>
                @php
                    $pingResponse = "{\n  \"status\": \"ok\",\n  \"message\": \"APIs Hub API connection verified successfully.\",\n  \"timestamp\": \"2026-09-24T23:15:00Z\"\n}";
                @endphp
                <div class="api-ref-code-container" x-data="{ copied: false }">
                    <pre class="api-ref-code-block is-success"><code class="select-all">{
  "status": "ok",
  "message": "APIs Hub API connection verified successfully.",
  "timestamp": "2026-09-24T23:15:00Z"
}</code></pre>
                    <button type="button" 
                            @click="navigator.clipboard.writeText(@js($pingResponse)); copied = true; setTimeout(() => copied = false, 2000)" 
                            class="api-ref-copy-btn">
                        <span x-show="!copied">{{ __('Copy') }}</span>
                        <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                    </button>
                </div>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Sample Requests & Code Snippets'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-command-line" class="h-5 w-5 text-indigo-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="copy('{{ $id }}');">
                        <span>{{ __('Sample Requests & Code Snippets') }}</span>
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

            <div class="space-y-4 text-sm">
                <div>
                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">1. cURL (Fetch Channel Metrics)</h4>
                    @php
                        $curlMetric = "curl -s -H \"X-API-Key: YOUR_API_KEY\" \\\n  \"{$baseUrl}/google_search_console/metric?limit=50\"";
                    @endphp
                    <div class="api-ref-code-container" x-data="{ copied: false }">
                        <pre class="api-ref-code-block"><code class="select-all">{{ $curlMetric }}</code></pre>
                        <button type="button" 
                                @click="copySnippet(@js($curlMetric)); copied = true; setTimeout(() => copied = false, 2000)" 
                                class="api-ref-copy-btn">
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                        </button>
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">2. Python (requests)</h4>
                    @php
                        $pythonSnippet = "import requests\n\nurl = \"{$baseUrl}/api/sync/status\"\nheaders = {\"X-API-Key\": \"YOUR_API_KEY\"}\n\nresponse = requests.get(url, headers=headers)\nprint(response.json())";
                    @endphp
                    <div class="api-ref-code-container" x-data="{ copied: false }">
                        <pre class="api-ref-code-block"><code class="select-all">{{ $pythonSnippet }}</code></pre>
                        <button type="button" 
                                @click="copySnippet(@js($pythonSnippet)); copied = true; setTimeout(() => copied = false, 2000)" 
                                class="api-ref-copy-btn">
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                        </button>
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">3. JavaScript / Node.js (fetch)</h4>
                    @php
                        $jsSnippet = "const res = await fetch(\"{$baseUrl}/api/sync/account-stats\", {\n  headers: { \"X-API-Key\": \"YOUR_API_KEY\" }\n});\nconst data = await res.json();\nconsole.log(data);";
                    @endphp
                    <div class="api-ref-code-container" x-data="{ copied: false }">
                        <pre class="api-ref-code-block"><code class="select-all">{{ $jsSnippet }}</code></pre>
                        <button type="button" 
                                @click="copySnippet(@js($jsSnippet)); copied = true; setTimeout(() => copied = false, 2000)" 
                                class="api-ref-copy-btn">
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                        </button>
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">4. Power BI / Looker Studio</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                        {{ __('In Power BI "Web Data Source" or Looker Studio JSON Connector, configure an HTTP header with:') }}
                    </p>
                    <div class="api-ref-code-container" x-data="{ copied: false }">
                        <pre class="api-ref-code-block"><code class="select-all">Header: X-API-Key | Value: YOUR_API_KEY</code></pre>
                        <button type="button" 
                                @click="copySnippet('Header: X-API-Key | Value: YOUR_API_KEY'); copied = true; setTimeout(() => copied = false, 2000)" 
                                class="api-ref-copy-btn">
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Analytical Aggregations (Widget-Grade Queries)'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-chart-pie" class="h-5 w-5 text-amber-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="copy('{{ $id }}');">
                        <span>{{ __('Analytical Aggregations (Widget-Grade Queries)') }}</span>
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

            <div class="space-y-4 text-sm">
                <p class="text-gray-600 dark:text-gray-300">
                    {{ __('Query multi-dimensional time series, calculate weighted reductions, and group by any dimension supported by your connected channels.') }}
                </p>

                <div>
                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">Scorecard Summary Example (Single Totals Row)</h4>
                    @php
                        $scorecardSnippet = "curl -s -X POST {$baseUrl}/google_search_console/metric/aggregate \\\n  -H \"X-API-Key: YOUR_API_KEY\" \\\n  -H \"Content-Type: application/json\" \\\n  -d '{\n    \"aggregations\": {\n      \"clicks\": \"clicks\",\n      \"impressions\": \"impressions\",\n      \"ctr\": \"ctr\",\n      \"position\": \"position\"\n    },\n    \"groupBy\": [],\n    \"filters\": {\n      \"channeledAccount\": \"12\",\n      \"dimensions.searchAppearance\": \"standard\"\n    },\n    \"startDate\": \"2026-09-01\",\n    \"endDate\": \"2026-09-24\"\n  }'";
                    @endphp
                    <div class="api-ref-code-container" x-data="{ copied: false }">
                        <pre class="api-ref-code-block"><code class="select-all">{{ $scorecardSnippet }}</code></pre>
                        <button type="button" 
                                @click="copySnippet(@js($scorecardSnippet)); copied = true; setTimeout(() => copied = false, 2000)" 
                                class="api-ref-copy-btn">
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                        </button>
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">Time-Series Line Chart Example (Daily Trend Grouping)</h4>
                    @php
                        $chartSnippet = "curl -s -X POST {$baseUrl}/facebook_marketing/metric/aggregate \\\n  -H \"X-API-Key: YOUR_API_KEY\" \\\n  -H \"Content-Type: application/json\" \\\n  -d '{\n    \"aggregations\": {\n      \"spend\": \"spend\",\n      \"clicks\": \"clicks\",\n      \"impressions\": \"impressions\",\n      \"cpc\": \"cpc\",\n      \"conversions\": \"results\"\n    },\n    \"groupBy\": [\"date\"],\n    \"filters\": {\n      \"channeledAccount\": \"5\"\n    },\n    \"startDate\": \"2026-09-01\",\n    \"endDate\": \"2026-09-24\",\n    \"orderBy\": \"date\",\n    \"orderDir\": \"ASC\"\n  }'";
                    @endphp
                    <div class="api-ref-code-container" x-data="{ copied: false }">
                        <pre class="api-ref-code-block"><code class="select-all">{{ $chartSnippet }}</code></pre>
                        <button type="button" 
                                @click="copySnippet(@js($chartSnippet)); copied = true; setTimeout(() => copied = false, 2000)" 
                                class="api-ref-copy-btn">
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </x-filament::section>

        @php
            $id = \Illuminate\Support\Str::slug(__('Rate Limits & Tier Quotas'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5 text-emerald-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="copy('{{ $id }}');">
                        <span>{{ __('Rate Limits & Tier Quotas') }}</span>
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

            <div class="space-y-3">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ __('API access is rate-limited on a per-minute sliding window according to your project tier:') }}
                </p>
                <div class="overflow-x-auto ring-1 ring-gray-200 dark:ring-white/10 rounded-lg api-ref-table-wrap">
                    <table class="w-full text-xs text-left divide-y divide-gray-200 dark:divide-white/5 api-ref-table">
                        <thead class="bg-gray-50 dark:bg-white/5 text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">
                            <tr>
                                <th scope="col" class="px-4 py-3">{{ __('Tier') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Requests / Minute') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            <tr class="transition-colors hover:bg-gray-50/60 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Free / Pro</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    <code class="px-1.5 py-0.5 rounded text-xs">0</code>
                                </td>
                                <td class="px-4 py-3 text-warning-600 dark:text-warning-400 font-medium">{{ __('Requires Ultra or Enterprise') }}</td>
                            </tr>
                            <tr class="transition-colors hover:bg-gray-50/60 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Ultra / Founder</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    <code class="px-1.5 py-0.5 rounded text-xs">500 req/min</code>
                                </td>
                                <td class="px-4 py-3 text-success-600 dark:text-success-400 font-medium">{{ __('Active') }}</td>
                            </tr>
                            <tr class="transition-colors hover:bg-gray-50/60 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Enterprise</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    <code class="px-1.5 py-0.5 rounded text-xs">1,000 req/min</code>
                                </td>
                                <td class="px-4 py-3 text-success-600 dark:text-success-400 font-medium">{{ __('Active') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('If a request exceeds quota limits, the server will return HTTP 429 Too Many Requests. If your current tier does not include API access, the server returns HTTP 403 Forbidden.') }}
                </p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

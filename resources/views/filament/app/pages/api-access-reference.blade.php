<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose prose-sm max-w-none text-gray-500 dark:text-gray-400 mb-2">
            {{ __('Connect external reporting platforms, BI tools (PowerBI, Looker Studio), and custom applications directly to your dedicated APIs Hub node.') }}
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

            <div class="prose dark:prose-invert max-w-none text-sm space-y-3">
                <p>
                    {{ __('Every project has a dedicated public API key available under Project Settings -> API Access. All requests must provide this key via HTTP headers.') }}
                </p>
                <div class="p-3 bg-gray-100 dark:bg-gray-800 rounded-lg text-xs space-y-2">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 block mb-1 font-semibold">{{ __('Header Option 1 (Recommended):') }}</span>
                        <div class="flex items-center gap-2" x-data="{ copied: false }">
                            <pre class="api-ref-code-block flex-1"><code class="select-all">X-API-Key: YOUR_API_KEY</code></pre>
                            <button type="button" @click="navigator.clipboard.writeText('X-API-Key: YOUR_API_KEY'); copied = true; setTimeout(() => copied = false, 2000)" class="px-2 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded text-xs transition">
                                <span x-show="!copied">{{ __('Copy') }}</span>
                                <span x-show="copied" class="text-success-600 dark:text-success-400" x-cloak>{{ __('Copied!') }}</span>
                            </button>
                        </div>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400 block mb-1 font-semibold">{{ __('Header Option 2 (Bearer Token):') }}</span>
                        <div class="flex items-center gap-2" x-data="{ copied: false }">
                            <pre class="api-ref-code-block flex-1"><code class="select-all">Authorization: Bearer YOUR_API_KEY</code></pre>
                            <button type="button" @click="navigator.clipboard.writeText('Authorization: Bearer YOUR_API_KEY'); copied = true; setTimeout(() => copied = false, 2000)" class="px-2 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded text-xs transition">
                                <span x-show="!copied">{{ __('Copy') }}</span>
                                <span x-show="copied" class="text-success-600 dark:text-success-400" x-cloak>{{ __('Copied!') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Base Endpoint URL format:') }} <code>https://&lt;your-project-subdomain&gt;.apis-hub.cloud</code>
                </p>
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
                <div class="relative group" x-data="{ copied: false }">
                    <pre class="api-ref-code-block"><code class="select-all">curl -s -H "X-API-Key: YOUR_API_KEY" \
  https://&lt;subdomain&gt;.apis-hub.cloud/api/v1/ping</code></pre>
                    <button type="button" @click="navigator.clipboard.writeText('curl -s -H &quot;X-API-Key: YOUR_API_KEY&quot; https://<subdomain>.apis-hub.cloud/api/v1/ping'); copied = true; setTimeout(() => copied = false, 2000)" class="absolute top-2 right-2 px-2 py-1 bg-gray-700/80 hover:bg-gray-600 text-white rounded text-xs transition">
                        <span x-show="!copied">{{ __('Copy') }}</span>
                        <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                    </button>
                </div>

                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Expected Response (HTTP 200):') }}</p>
                <div class="relative group" x-data="{ copied: false }">
                    <pre class="api-ref-code-block is-success"><code class="select-all">{
  "status": "ok",
  "message": "APIs Hub API connection verified successfully.",
  "timestamp": "2026-09-24T23:15:00Z"
}</code></pre>
                    <button type="button" @click="navigator.clipboard.writeText('{\n  &quot;status&quot;: &quot;ok&quot;,\n  &quot;message&quot;: &quot;APIs Hub API connection verified successfully.&quot;,\n  &quot;timestamp&quot;: &quot;2026-09-24T23:15:00Z&quot;\n}'); copied = true; setTimeout(() => copied = false, 2000)" class="absolute top-2 right-2 px-2 py-1 bg-gray-700/80 hover:bg-gray-600 text-white rounded text-xs transition">
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
                    <div class="relative group" x-data="{ copied: false }">
                        <pre class="api-ref-code-block"><code class="select-all">curl -s -H "X-API-Key: YOUR_API_KEY" \
  "https://&lt;subdomain&gt;.apis-hub.cloud/google_search_console/metric?limit=50"</code></pre>
                        <button type="button" @click="navigator.clipboard.writeText('curl -s -H &quot;X-API-Key: YOUR_API_KEY&quot; &quot;https://<subdomain>.apis-hub.cloud/google_search_console/metric?limit=50&quot;'); copied = true; setTimeout(() => copied = false, 2000)" class="absolute top-2 right-2 px-2 py-1 bg-gray-700/80 hover:bg-gray-600 text-white rounded text-xs transition">
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                        </button>
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">2. Python (requests)</h4>
                    <div class="relative group" x-data="{ copied: false }">
                        <pre class="api-ref-code-block"><code class="select-all">import requests

url = "https://&lt;subdomain&gt;.apis-hub.cloud/api/sync/status"
headers = {"X-API-Key": "YOUR_API_KEY"}

response = requests.get(url, headers=headers)
print(response.json())</code></pre>
                        <button type="button" @click="navigator.clipboard.writeText('import requests\n\nurl = &quot;https://<subdomain>.apis-hub.cloud/api/sync/status&quot;\nheaders = {&quot;X-API-Key&quot;: &quot;YOUR_API_KEY&quot;}\n\nresponse = requests.get(url, headers=headers)\nprint(response.json())'); copied = true; setTimeout(() => copied = false, 2000)" class="absolute top-2 right-2 px-2 py-1 bg-gray-700/80 hover:bg-gray-600 text-white rounded text-xs transition">
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                        </button>
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">3. JavaScript / Node.js (fetch)</h4>
                    <div class="relative group" x-data="{ copied: false }">
                        <pre class="api-ref-code-block"><code class="select-all">const res = await fetch("https://&lt;subdomain&gt;.apis-hub.cloud/api/sync/account-stats", {
  headers: { "X-API-Key": "YOUR_API_KEY" }
});
const data = await res.json();
console.log(data);</code></pre>
                        <button type="button" @click="navigator.clipboard.writeText('const res = await fetch(&quot;https://<subdomain>.apis-hub.cloud/api/sync/account-stats&quot;, {\n  headers: { &quot;X-API-Key&quot;: &quot;YOUR_API_KEY&quot; }\n});\nconst data = await res.json();\nconsole.log(data);'); copied = true; setTimeout(() => copied = false, 2000)" class="absolute top-2 right-2 px-2 py-1 bg-gray-700/80 hover:bg-gray-600 text-white rounded text-xs transition">
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
                    <div class="flex items-center gap-2" x-data="{ copied: false }">
                        <pre class="api-ref-code-block flex-1"><code class="select-all">Header: X-API-Key | Value: YOUR_API_KEY</code></pre>
                        <button type="button" @click="navigator.clipboard.writeText('Header: X-API-Key | Value: YOUR_API_KEY'); copied = true; setTimeout(() => copied = false, 2000)" class="px-2 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded text-xs transition">
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" class="text-success-600 dark:text-success-400" x-cloak>{{ __('Copied!') }}</span>
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
                    <div class="relative group" x-data="{ copied: false }">
                        <pre class="api-ref-code-block"><code class="select-all">curl -s -X POST https://&lt;subdomain&gt;.apis-hub.cloud/google_search_console/metric/aggregate \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "aggregations": {
      "clicks": "clicks",
      "impressions": "impressions",
      "ctr": "ctr",
      "position": "position"
    },
    "groupBy": [],
    "filters": {
      "channeledAccount": "12",
      "dimensions.searchAppearance": "standard"
    },
    "startDate": "2026-09-01",
    "endDate": "2026-09-24"
  }'</code></pre>
                        <button type="button" @click="navigator.clipboard.writeText('curl -s -X POST https://<subdomain>.apis-hub.cloud/google_search_console/metric/aggregate \\\n  -H &quot;X-API-Key: YOUR_API_KEY&quot; \\\n  -H &quot;Content-Type: application/json&quot; \\\n  -d \'{\n    &quot;aggregations&quot;: {\n      &quot;clicks&quot;: &quot;clicks&quot;,\n      &quot;impressions&quot;: &quot;impressions&quot;,\n      &quot;ctr&quot;: &quot;ctr&quot;,\n      &quot;position&quot;: &quot;position&quot;\n    },\n    &quot;groupBy&quot;: [],\n    &quot;filters&quot;: {\n      &quot;channeledAccount&quot;: &quot;12&quot;,\n      &quot;dimensions.searchAppearance&quot;: &quot;standard&quot;\n    },\n    &quot;startDate&quot;: &quot;2026-09-01&quot;,\n    &quot;endDate&quot;: &quot;2026-09-24&quot;\n  }\''); copied = true; setTimeout(() => copied = false, 2000)" class="absolute top-2 right-2 px-2 py-1 bg-gray-700/80 hover:bg-gray-600 text-white rounded text-xs transition">
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                        </button>
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">Time-Series Line Chart Example (Daily Trend Grouping)</h4>
                    <div class="relative group" x-data="{ copied: false }">
                        <pre class="api-ref-code-block"><code class="select-all">curl -s -X POST https://&lt;subdomain&gt;.apis-hub.cloud/facebook_marketing/metric/aggregate \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "aggregations": {
      "spend": "spend",
      "clicks": "clicks",
      "impressions": "impressions",
      "cpc": "cpc",
      "conversions": "results"
    },
    "groupBy": ["date"],
    "filters": {
      "channeledAccount": "5"
    },
    "startDate": "2026-09-01",
    "endDate": "2026-09-24",
    "orderBy": "date",
    "orderDir": "ASC"
  }'</code></pre>
                        <button type="button" @click="navigator.clipboard.writeText('curl -s -X POST https://<subdomain>.apis-hub.cloud/facebook_marketing/metric/aggregate \\\n  -H &quot;X-API-Key: YOUR_API_KEY&quot; \\\n  -H &quot;Content-Type: application/json&quot; \\\n  -d \'{\n    &quot;aggregations&quot;: {\n      &quot;spend&quot;: &quot;spend&quot;,\n      &quot;clicks&quot;: &quot;clicks&quot;,\n      &quot;impressions&quot;: &quot;impressions&quot;,\n      &quot;cpc&quot;: &quot;cpc&quot;,\n      &quot;conversions&quot;: &quot;results&quot;\n    },\n    &quot;groupBy&quot;: [&quot;date&quot;],\n    &quot;filters&quot;: {\n      &quot;channeledAccount&quot;: &quot;5&quot;\n    },\n    &quot;startDate&quot;: &quot;2026-09-01&quot;,\n    &quot;endDate&quot;: &quot;2026-09-24&quot;,\n    &quot;orderBy&quot;: &quot;date&quot;,\n    &quot;orderDir&quot;: &quot;ASC&quot;\n  }\''); copied = true; setTimeout(() => copied = false, 2000)" class="absolute top-2 right-2 px-2 py-1 bg-gray-700/80 hover:bg-gray-600 text-white rounded text-xs transition">
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
                <div class="overflow-x-auto ring-1 ring-gray-200 dark:ring-white/10 rounded-lg">
                    <table class="w-full text-xs text-left divide-y divide-gray-200 dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5 text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">
                            <tr>
                                <th scope="col" class="px-4 py-3">{{ __('Tier') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Requests / Minute') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5 bg-white dark:bg-gray-900/50">
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Free / Pro</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300"><code>0</code></td>
                                <td class="px-4 py-3 text-warning-600 dark:text-warning-400 font-medium">{{ __('Requires Ultra or Enterprise') }}</td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Ultra / Founder</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300"><code>500 req/min</code></td>
                                <td class="px-4 py-3 text-success-600 dark:text-success-400 font-medium">{{ __('Active') }}</td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Enterprise</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300"><code>1,000 req/min</code></td>
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

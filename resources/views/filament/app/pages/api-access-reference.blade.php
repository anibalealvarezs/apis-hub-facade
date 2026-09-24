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
                <div class="p-3 bg-gray-100 dark:bg-gray-800 rounded-lg font-mono text-xs">
                    <div><strong>Header Option 1:</strong> X-API-Key: &lt;YOUR_API_KEY&gt;</div>
                    <div class="mt-1"><strong>Header Option 2:</strong> Authorization: Bearer &lt;YOUR_API_KEY&gt;</div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Base Endpoint URL format:') }} <code>https://&lt;your-project-subdomain&gt;.apis-hub.cloud/api</code>
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

            <div class="prose dark:prose-invert max-w-none text-sm space-y-3">
                <p>
                    {{ __('To verify network connectivity and authenticate your API key, call the public ping endpoint:') }}
                </p>
                <pre class="bg-gray-900 text-gray-100 p-3 rounded-lg text-xs overflow-x-auto"><code>curl -s -H "X-API-Key: YOUR_API_KEY" \
  https://&lt;subdomain&gt;.apis-hub.cloud/api/v1/ping</code></pre>

                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Expected Response (HTTP 200):') }}</p>
                <pre class="bg-gray-900 text-green-400 p-3 rounded-lg text-xs overflow-x-auto"><code>{
  "status": "ok",
  "message": "APIs Hub API connection verified successfully.",
  "timestamp": "2026-09-24T23:15:00Z"
}</code></pre>
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
                    <pre class="bg-gray-900 text-gray-100 p-3 rounded-lg text-xs overflow-x-auto"><code>curl -s -H "X-API-Key: YOUR_API_KEY" \
  "https://&lt;subdomain&gt;.apis-hub.cloud/google_search_console/metric?limit=50"</code></pre>
                </div>

                <div>
                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">2. Python (requests)</h4>
                    <pre class="bg-gray-900 text-gray-100 p-3 rounded-lg text-xs overflow-x-auto"><code>import requests

url = "https://&lt;subdomain&gt;.apis-hub.cloud/api/sync/status"
headers = {"X-API-Key": "YOUR_API_KEY"}

response = requests.get(url, headers=headers)
print(response.json())</code></pre>
                </div>

                <div>
                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">3. JavaScript / Node.js (fetch)</h4>
                    <pre class="bg-gray-900 text-gray-100 p-3 rounded-lg text-xs overflow-x-auto"><code>const res = await fetch("https://&lt;subdomain&gt;.apis-hub.cloud/api/sync/account-stats", {
  headers: { "X-API-Key": "YOUR_API_KEY" }
});
const data = await res.json();
console.log(data);</code></pre>
                </div>

                <div>
                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">4. Power BI / Looker Studio</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                        {{ __('In Power BI "Web Data Source" or Looker Studio JSON Connector, configure an HTTP header with:') }}
                    </p>
                    <div class="p-2 bg-gray-100 dark:bg-gray-800 rounded font-mono text-xs">
                        Header: X-API-Key | Value: YOUR_API_KEY
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

            <div class="prose dark:prose-invert max-w-none text-sm space-y-3">
                <p>
                    {{ __('API access is rate-limited on a per-minute sliding window according to your project tier:') }}
                </p>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 uppercase">
                            <tr>
                                <th class="p-2">Tier</th>
                                <th class="p-2">Requests / Minute</th>
                                <th class="p-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr>
                                <td class="p-2 font-medium">Free / Pro</td>
                                <td class="p-2">0</td>
                                <td class="p-2 text-warning-500">Requires Ultra or Enterprise</td>
                            </tr>
                            <tr>
                                <td class="p-2 font-medium">Ultra / Founder</td>
                                <td class="p-2">500 req/min</td>
                                <td class="p-2 text-success-500">Active</td>
                            </tr>
                            <tr>
                                <td class="p-2 font-medium">Enterprise</td>
                                <td class="p-2">1,000 req/min</td>
                                <td class="p-2 text-success-500">Active</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-500">
                    {{ __('If a request exceeds quota limits, the server will return HTTP 429 Too Many Requests. If your current tier does not include API access, the server returns HTTP 403 Forbidden.') }}
                </p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

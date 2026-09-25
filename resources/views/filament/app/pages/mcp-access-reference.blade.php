<x-filament-panels::page>
    @php
        $baseUrl = $this->baseUrl;
        $sseEndpoint = $this->sseEndpoint;
        $apiKey = $this->apiKey;
        $isAvailable = $this->isMcpAvailable;
        $tenant = $this->tenant;
        $tier = $tenant?->fresh()?->billingProfile?->fresh()?->tier?->value ?? 'free';
    @endphp

    <div class="space-y-6" x-data="{
        baseUrl: @js($baseUrl),
        sseEndpoint: @js($sseEndpoint),
        getApiKey() {
            return $wire.data?.app_api_key || @js($apiKey);
        },
        copySnippet(template) {
            const currentKey = this.getApiKey();
            const resolved = template
                .replace(/YOUR_API_KEY/g, currentKey || 'YOUR_API_KEY')
                .replace(/https:\/\/<subdomain>\.apis-hub\.cloud/g, this.baseUrl)
                .replace(/https:\/\/<your-project-subdomain>\.apis-hub\.cloud/g, this.baseUrl)
                .replace(/YOUR_SSE_ENDPOINT/g, this.sseEndpoint);
            navigator.clipboard.writeText(resolved);
        }
    }">
        {{-- Intro banner --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <span>{{ __('Connect AI assistants, LLM coding tools (Google Antigravity, Claude Desktop, Cursor), and autonomous agents directly to your real-time marketing data node via Model Context Protocol.') }}</span>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $isAvailable ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' }}">
                    <span class="w-1.5 h-1.5 mr-1.5 rounded-full {{ $isAvailable ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    {{ $isAvailable ? __('MCP Active (:tier)', ['tier' => ucfirst($tier)]) : __('Requires Ultra / Enterprise') }}
                </span>
            </div>
        </div>

        @if(! $isAvailable)
            {{-- Upgrade Callout Banner --}}
            <div class="p-5 rounded-2xl bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border border-amber-500/30 dark:border-amber-500/20">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="p-2 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 shrink-0">
                            <x-filament::icon icon="heroicon-o-lock-closed" class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                {{ __('Model Context Protocol (MCP) Server Locked') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5">
                                {{ __('Your project is currently on the :tier plan. Automated agent queries, Claude Desktop integration, and Antigravity tooling are exclusively available on Ultra and Enterprise tiers.', ['tier' => ucfirst($tier)]) }}
                            </p>
                        </div>
                    </div>
                    @if($tenant?->billingProfile?->user_id === \Illuminate\Support\Facades\Auth::id())
                        <a href="/account/account-subscription?profile={{ $tenant->billingProfile?->id }}" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-bold text-white bg-primary-600 hover:bg-primary-500 rounded-xl shadow-sm transition shrink-0">
                            <x-filament::icon icon="heroicon-m-sparkles" class="w-4 h-4 mr-1.5" />
                            {{ __('Upgrade to Ultra') }}
                        </a>
                    @else
                        <span class="inline-block px-3 py-1.5 text-xs font-medium text-amber-700 bg-amber-100 dark:bg-amber-500/20 dark:text-amber-300 rounded-lg shrink-0">
                            {{ __('Contact billing profile owner to upgrade') }}
                        </span>
                    @endif
                </div>
            </div>
        @endif

        {{-- Connection & Authentication Section --}}
        @php
            $id = \Illuminate\Support\Str::slug(__('MCP Endpoint & Authentication'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group">
                    <x-filament::icon icon="heroicon-o-cpu-chip" class="h-5 w-5 text-primary-500" />
                    <span>{{ __('MCP Server Endpoint & Credentials') }}</span>
                </div>
            </x-slot>

            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ __('Your dedicated node exposes an enterprise Model Context Protocol (MCP) server over Server-Sent Events (SSE). Use your API key for authentication.') }}
                </p>

                {{-- Key Scoping & Security Callout --}}
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-xs text-gray-600 dark:text-gray-300 space-y-2">
                    <div class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-200">
                        <x-filament::icon icon="heroicon-m-key" class="w-4 h-4 text-primary-500" />
                        <span>{{ __('MCP Authentication & Access Scopes') }}</span>
                    </div>
                    <ul class="list-disc list-inside space-y-1 pl-1">
                        <li>
                            <strong class="text-gray-900 dark:text-white">{{ __('Project Master API Key:') }}</strong>
                            {{ __('Owners and Editors use the master key displayed below. AI agents connected with this key have full visibility across all project integrations, accounts, and performance data.') }}
                        </li>
                        <li>
                            <strong class="text-gray-900 dark:text-white">{{ __('User-Scoped API Keys:') }}</strong>
                            {{ __('Viewers and Collaborators use their own user-scoped API key. AI agents connected with a user-scoped key are automatically restricted to only query and summarize the specific asset groups assigned to that collaborator.') }}
                        </li>
                        <li>
                            <strong class="text-gray-900 dark:text-white">{{ __('Authentication Methods:') }}</strong>
                            {{ __('MCP clients can pass the API key via the HTTP Authorization header (Bearer YOUR_API_KEY), the X-API-Key header, or directly as a URL parameter (?key=YOUR_API_KEY) in clients like Claude Desktop and Cursor that do not support custom SSE headers.') }}
                        </li>
                    </ul>
                </div>

                {{-- Livewire API Key Field --}}
                <div class="mb-4">
                    {{ $this->form }}
                </div>

                {{-- Server-Sent Events (SSE) Endpoint --}}
                <div>
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 block mb-1.5">
                        {{ __('SSE Transport URL:') }}
                    </span>
                    <div class="api-ref-code-container" x-data="{ copied: false }">
                        <pre class="api-ref-code-block"><code class="select-all">{{ $sseEndpoint }}</code></pre>
                        <button type="button" 
                                @click="navigator.clipboard.writeText('{{ $sseEndpoint }}'); copied = true; setTimeout(() => copied = false, 2000)" 
                                class="api-ref-copy-btn">
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">
                        {{ __('Supports Authorization Bearer header, X-API-Key, or passing ?key=YOUR_API_KEY directly in the SSE query URL.') }}
                    </p>
                </div>

                {{-- Team Collaborators Keys Management (Owners / Editors only) --}}
                @if($this->isEditorOrOwner && $tenant)
                    @php
                        $collaborators = $tenant->users ?? collect();
                        $nonEditors = $collaborators->filter(fn($c) => !$tenant->isEditorOrOwner($c));
                    @endphp
                    @if($nonEditors->isNotEmpty())
                        <div class="pt-4 border-t border-gray-100 dark:border-white/5 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        {{ __('Viewer & Collaborator Scoped API Keys') }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('Non-editor team members access the node with asset-restricted keys. You can force-rotate individual keys to immediately revoke compromised tokens; the user will be alerted via email and in-app notification.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="overflow-x-auto ring-1 ring-gray-200 dark:ring-white/10 rounded-lg">
                                <table class="w-full text-xs text-left divide-y divide-gray-200 dark:divide-white/5">
                                    <thead class="bg-gray-50 dark:bg-white/5 text-gray-500 dark:text-gray-400 uppercase font-semibold">
                                        <tr>
                                            <th class="px-4 py-2.5">{{ __('User') }}</th>
                                            <th class="px-4 py-2.5">{{ __('Role') }}</th>
                                            <th class="px-4 py-2.5">{{ __('Assigned Asset Groups') }}</th>
                                            <th class="px-4 py-2.5 text-right">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                        @foreach($nonEditors as $collab)
                                            @php
                                                $sharedGroups = app(\App\Services\CollaboratorAssetAccessService::class)->getSharedAssetGroups($tenant, $collab->id);
                                            @endphp
                                            <tr>
                                                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">
                                                    <div>{{ $collab->name }}</div>
                                                    <div class="text-[11px] text-gray-400">{{ $collab->email }}</div>
                                                </td>
                                                <td class="px-4 py-2.5 text-gray-500">
                                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300">
                                                        {{ __('Viewer') }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2.5 text-gray-500">
                                                    @if($sharedGroups->isNotEmpty())
                                                        <div class="flex flex-wrap gap-1">
                                                            @foreach($sharedGroups as $group)
                                                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] bg-primary-50 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400">
                                                                    {{ $group->name }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <span class="text-amber-500 text-[11px] italic">{{ __('No asset groups assigned') }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2.5 text-right">
                                                    <button type="button"
                                                            wire:click="forceRotateCollaboratorKey({{ $collab->id }})"
                                                            wire:confirm="{{ __('Are you sure you want to force-rotate the API key for :name? The user will be notified immediately.', ['name' => $collab->name]) }}"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300 transition">
                                                        <x-filament::icon icon="heroicon-m-arrow-path" class="w-3.5 h-3.5" />
                                                        <span>{{ __('Force Rotate Key') }}</span>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </x-filament::section>

        {{-- Client Configuration Snippets --}}
        @php
            $idConfig = \Illuminate\Support\Str::slug(__('AI Client Configuration'));
        @endphp
        <x-filament::section id="{{ $idConfig }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-command-line" class="h-5 w-5 text-indigo-500" />
                    <span>{{ __('Client Configuration (Antigravity, Claude, Cursor)') }}</span>
                </div>
            </x-slot>

            <div class="space-y-6" x-data="{ activeTab: 'antigravity' }">
                {{-- Tabs --}}
                <div class="flex border-b border-gray-200 dark:border-gray-700 gap-4 text-sm font-semibold">
                    <button type="button" 
                            @click="activeTab = 'antigravity'"
                            :class="activeTab === 'antigravity' ? 'border-primary-500 text-primary-600 dark:text-primary-400 border-b-2 pb-2' : 'text-gray-500 hover:text-gray-700 pb-2'">
                        Google Antigravity
                    </button>
                    <button type="button" 
                            @click="activeTab = 'claude'"
                            :class="activeTab === 'claude' ? 'border-primary-500 text-primary-600 dark:text-primary-400 border-b-2 pb-2' : 'text-gray-500 hover:text-gray-700 pb-2'">
                        Claude Desktop
                    </button>
                    <button type="button" 
                            @click="activeTab = 'cursor'"
                            :class="activeTab === 'cursor' ? 'border-primary-500 text-primary-600 dark:text-primary-400 border-b-2 pb-2' : 'text-gray-500 hover:text-gray-700 pb-2'">
                        Cursor / Windsurf
                    </button>
                </div>

                {{-- Tab 1: Antigravity --}}
                <div x-show="activeTab === 'antigravity'" class="space-y-3">
                    <p class="text-xs text-gray-600 dark:text-gray-300">
                        {{ __('Add this entry to your Antigravity IDE configuration or mcp_config.json:') }}
                    </p>
                    @php
                        $antigravityJson = json_encode([
                            "apis-hub" => [
                                "url" => "{$sseEndpoint}",
                                "headers" => [
                                    "Authorization" => "Bearer YOUR_API_KEY",
                                    "X-API-Key" => "YOUR_API_KEY"
                                ]
                            ]
                        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    @endphp
                    <div class="api-ref-code-container" x-data="{ copied: false }">
                        <pre class="api-ref-code-block"><code class="select-all">{{ $antigravityJson }}</code></pre>
                        <button type="button" 
                                @click="copySnippet(@js($antigravityJson)); copied = true; setTimeout(() => copied = false, 2000)" 
                                class="api-ref-copy-btn">
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Tab 2: Claude Desktop --}}
                <div x-show="activeTab === 'claude'" x-cloak class="space-y-3">
                    <p class="text-xs text-gray-600 dark:text-gray-300">
                        {{ __('Add this configuration to your claude_desktop_config.json:') }}
                    </p>
                    @php
                        $claudeJson = json_encode([
                            "mcpServers" => [
                                "apis-hub" => [
                                    "url" => "{$sseEndpoint}?key=YOUR_API_KEY",
                                    "headers" => [
                                        "Authorization" => "Bearer YOUR_API_KEY"
                                    ]
                                ]
                            ]
                        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    @endphp
                    <div class="api-ref-code-container" x-data="{ copied: false }">
                        <pre class="api-ref-code-block"><code class="select-all">{{ $claudeJson }}</code></pre>
                        <button type="button" 
                                @click="copySnippet(@js($claudeJson)); copied = true; setTimeout(() => copied = false, 2000)" 
                                class="api-ref-copy-btn">
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Tab 3: Cursor --}}
                <div x-show="activeTab === 'cursor'" x-cloak class="space-y-3">
                    <p class="text-xs text-gray-600 dark:text-gray-300">
                        {{ __('In Cursor Settings > Features > MCP, add a new server with type SSE and this URL:') }}
                    </p>
                    @php
                        $cursorUrl = "{$sseEndpoint}?key=YOUR_API_KEY";
                    @endphp
                    <div class="api-ref-code-container" x-data="{ copied: false }">
                        <pre class="api-ref-code-block"><code class="select-all">{{ $cursorUrl }}</code></pre>
                        <button type="button" 
                                @click="copySnippet('{{ $cursorUrl }}'); copied = true; setTimeout(() => copied = false, 2000)" 
                                class="api-ref-copy-btn">
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Available Tools & Prompts --}}
        @php
            $idTools = \Illuminate\Support\Str::slug(__('Available MCP Tools & Example Queries'));
        @endphp
        <x-filament::section id="{{ $idTools }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-wrench-screwdriver" class="h-5 w-5 text-amber-500" />
                    <span>{{ __('Available Tools & Example Agent Prompts') }}</span>
                </div>
            </x-slot>

            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-white/[0.02]">
                        <div class="flex items-center gap-2 mb-1.5 font-bold text-sm text-gray-900 dark:text-white">
                            <x-filament::icon icon="heroicon-m-chart-bar" class="w-4 h-4 text-primary-500" />
                            <span>summarize_performance</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                            {{ __('Calculates aggregated metrics (spend, clicks, impressions, CTR, CPC, ROAS) across Meta, Google Ads, GSC, and Shopify with built-in memory caching.') }}
                        </p>
                        <span class="inline-flex text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">
                            Ultra & Enterprise
                        </span>
                    </div>

                    <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-white/[0.02]">
                        <div class="flex items-center gap-2 mb-1.5 font-bold text-sm text-gray-900 dark:text-white">
                            <x-filament::icon icon="heroicon-m-check-circle" class="w-4 h-4 text-emerald-500" />
                            <span>check_coverage</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                            {{ __('Scans date gaps in your synced marketing channels (e.g. facebook_marketing, google_search_console) over a requested timeframe.') }}
                        </p>
                        <span class="inline-flex text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                            Ultra & Enterprise
                        </span>
                    </div>

                    <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-white/[0.02]">
                        <div class="flex items-center gap-2 mb-1.5 font-bold text-sm text-gray-900 dark:text-white">
                            <x-filament::icon icon="heroicon-m-queue-list" class="w-4 h-4 text-indigo-500" />
                            <span>get_available_instances</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                            {{ __('Lists active synchronization workers and explorer schedules configured for your project node.') }}
                        </p>
                        <span class="inline-flex text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                            Ultra & Enterprise
                        </span>
                    </div>
                </div>

                {{-- Example Prompts for Agents --}}
                <div class="space-y-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Recommended Prompts for your AI Assistant:') }}
                    </h4>

                    @php
                        $prompt1 = "Using the apis-hub MCP server, summarize our total ad spend, clicks, impressions, and ROAS across Facebook and Google Ads for the last 30 days.";
                        $prompt2 = "Check the data coverage for google_search_console and facebook_marketing over the last 60 days to see if there are missing dates or gaps.";
                        $prompt3 = "Call summarize_performance for google_search_console grouped by daily for the past 14 days, analyze click and position trends, and give me key SEO recommendations.";
                    @endphp

                    <div class="space-y-4">
                        <div>
                            <h5 class="font-bold text-gray-800 dark:text-gray-200 mb-1 text-sm">{{ __('1. Cross-Channel Performance Summary:') }}</h5>
                            <div class="api-ref-code-container" x-data="{ copied: false }">
                                <pre class="api-ref-code-block"><code class="select-all">{{ $prompt1 }}</code></pre>
                                <button type="button" 
                                        @click="navigator.clipboard.writeText(@js($prompt1)); copied = true; setTimeout(() => copied = false, 2000)" 
                                        class="api-ref-copy-btn">
                                    <span x-show="!copied">{{ __('Copy') }}</span>
                                    <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <h5 class="font-bold text-gray-800 dark:text-gray-200 mb-1 text-sm">{{ __('2. Data Health & Sync Coverage Check:') }}</h5>
                            <div class="api-ref-code-container" x-data="{ copied: false }">
                                <pre class="api-ref-code-block"><code class="select-all">{{ $prompt2 }}</code></pre>
                                <button type="button" 
                                        @click="navigator.clipboard.writeText(@js($prompt2)); copied = true; setTimeout(() => copied = false, 2000)" 
                                        class="api-ref-copy-btn">
                                    <span x-show="!copied">{{ __('Copy') }}</span>
                                    <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <h5 class="font-bold text-gray-800 dark:text-gray-200 mb-1 text-sm">{{ __('3. Executive Insights & SEO Breakdown:') }}</h5>
                            <div class="api-ref-code-container" x-data="{ copied: false }">
                                <pre class="api-ref-code-block"><code class="select-all">{{ $prompt3 }}</code></pre>
                                <button type="button" 
                                        @click="navigator.clipboard.writeText(@js($prompt3)); copied = true; setTimeout(() => copied = false, 2000)" 
                                        class="api-ref-copy-btn">
                                    <span x-show="!copied">{{ __('Copy') }}</span>
                                    <span x-show="copied" class="text-green-400" x-cloak>{{ __('Copied!') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Rate Limits & Tier Availability Section --}}
        @php
            $idTiers = \Illuminate\Support\Str::slug(__('MCP Tier Availability & Rate Limits'));
        @endphp
        <x-filament::section id="{{ $idTiers }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5 text-emerald-500" />
                    <span>{{ __('MCP Tier Availability & Rate Limits') }}</span>
                </div>
            </x-slot>

            <div class="space-y-3">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ __('Model Context Protocol (MCP) server access is exclusively provisioned on Ultra and Enterprise tiers with built-in sliding window rate limiting to protect nodes against autonomous agent swarm abuse:') }}
                </p>
                <div class="overflow-x-auto ring-1 ring-gray-200 dark:ring-white/10 rounded-lg api-ref-table-wrap">
                    <table class="w-full text-xs text-left divide-y divide-gray-200 dark:divide-white/5 api-ref-table">
                        <thead class="bg-gray-50 dark:bg-white/5 text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">
                            <tr>
                                <th scope="col" class="px-4 py-3">{{ __('Tier') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('MCP SSE Server Access') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Sliding Rate Limit') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            <tr class="transition-colors hover:bg-gray-50/60 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Free / Pro</td>
                                <td class="px-4 py-3 text-slate-400">—</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">0</td>
                                <td class="px-4 py-3 text-warning-600 dark:text-warning-400 font-medium">{{ __('Requires Ultra or Enterprise') }}</td>
                            </tr>
                            <tr class="transition-colors hover:bg-gray-50/60 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Ultra / Founder</td>
                                <td class="px-4 py-3 text-emerald-600 dark:text-emerald-400 font-semibold">{{ __('Included (Dedicated Node)') }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    <code class="px-1.5 py-0.5 rounded text-xs">60 req/min</code>
                                </td>
                                <td class="px-4 py-3 text-success-600 dark:text-success-400 font-medium">{{ __('Active') }}</td>
                            </tr>
                            <tr class="transition-colors hover:bg-gray-50/60 dark:hover:bg-white/[0.02]">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Enterprise</td>
                                <td class="px-4 py-3 text-emerald-600 dark:text-emerald-400 font-semibold">{{ __('Included + Custom Tooling') }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    <code class="px-1.5 py-0.5 rounded text-xs">120+ req/min</code>
                                </td>
                                <td class="px-4 py-3 text-success-600 dark:text-success-400 font-medium">{{ __('Active') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Calls exceeding the sliding window threshold will receive standard HTTP 429 Too Many Requests (or JSON-RPC error code -32000). Projects on Free or Pro tiers receive HTTP 401 Unauthorized.') }}
                </p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

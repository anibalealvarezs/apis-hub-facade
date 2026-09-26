<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose prose-sm max-w-none text-gray-500 dark:text-gray-400 mb-2">
            {{ __('Comprehensive guide on inviting collaborators, role hierarchies, granular asset group restrictions for viewers, and user-scoped API keys.') }}
        </div>

        {{-- Section 1: Overview & Collaboration Flow --}}
        @php
            $id = \Illuminate\Support\Str::slug(__('Team Collaboration & Invitation Flow'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-user-plus" class="h-5 w-5 text-primary-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="copy('{{ $id }}');">
                        <span>{{ __('Team Collaboration & Invitation Flow') }}</span>
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
                    {{ __('Project owners and editors can invite collaborators to work together on their tenant node. Collaborators are invited by email and assigned specific roles and permissions.') }}
                </p>
                <ul>
                    <li><strong>{{ __('Invitation Process:') }}</strong> {{ __('Navigate to Team & Collaborators > Invite Collaborator. Enter the collaborator\'s email and select their initial role (Owner, Editor, Viewer). An invitation email containing a secure acceptance link will be dispatched automatically.') }}</li>
                    <li><strong>{{ __('Free Tier Rules:') }}</strong> {{ __('To prevent abuse, users with only a Free Tier billing profile must either delete their personal free project or accept the invitation via the direct invitation link to join an organization.') }}</li>
                    <li><strong>{{ __('Managing Members:') }}</strong> {{ __('Owners and editors can inspect members, modify their asset access, remove collaborators from the project, or force-rotate compromised credentials.') }}</li>
                </ul>
            </div>
        </x-filament::section>

        {{-- Section 2: Roles & Permission Matrix --}}
        @php
            $id = \Illuminate\Support\Str::slug(__('Project Roles & Capabilities'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5 text-indigo-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="copy('{{ $id }}');">
                        <span>{{ __('Project Roles & Capabilities') }}</span>
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
                <div class="overflow-x-auto ring-1 ring-gray-200 dark:ring-white/10 rounded-lg">
                    <table class="w-full text-xs text-left divide-y divide-gray-200 dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5 text-gray-700 dark:text-gray-300 font-semibold uppercase">
                            <tr>
                                <th class="px-4 py-3">{{ __('Role') }}</th>
                                <th class="px-4 py-3">{{ __('Description') }}</th>
                                <th class="px-4 py-3">{{ __('API Key Access') }}</th>
                                <th class="px-4 py-3">{{ __('Asset Group Scope') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-600 dark:text-gray-300">
                            <tr>
                                <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300">
                                        {{ __('Project Owner') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ __('Absolute authority. Manages billing, can delete or transfer project, invite/remove collaborators, and adjust sync settings.') }}</td>
                                <td class="px-4 py-3 font-semibold text-emerald-600 dark:text-emerald-400">{{ __('Tenant Master API Key') }}</td>
                                <td class="px-4 py-3">{{ __('Unrestricted (all connected channels & accounts)') }}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">
                                        {{ __('Project Editor') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ __('Can manage channels, sync parameters, dashboards, and collaborators. Cannot delete or transfer project ownership.') }}</td>
                                <td class="px-4 py-3 font-semibold text-emerald-600 dark:text-emerald-400">{{ __('Tenant Master API Key') }}</td>
                                <td class="px-4 py-3">{{ __('Unrestricted (all connected channels & accounts)') }}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300">
                                        {{ __('Project Viewer') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ __('Read-only access to dashboards, reports, and real-time analytical queries. Cannot alter configuration or manage members.') }}</td>
                                <td class="px-4 py-3 font-semibold text-indigo-600 dark:text-indigo-400">{{ __('Personal Scoped API Key') }}</td>
                                <td class="px-4 py-3">{{ __('Strictly restricted to assigned Asset Groups') }}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300">
                                        {{ __('Project User') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ __('Basic collaborator without administrative rights. Accesses only explicitly permitted data views.') }}</td>
                                <td class="px-4 py-3 font-semibold text-indigo-600 dark:text-indigo-400">{{ __('Personal Scoped API Key') }}</td>
                                <td class="px-4 py-3">{{ __('Strictly restricted to assigned Asset Groups') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </x-filament::section>

        {{-- Section 3: Asset Group Restrictions --}}
        @php
            $id = \Illuminate\Support\Str::slug(__('Asset Group Access Restrictions for Viewers & Users'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-lock-closed" class="h-5 w-5 text-amber-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="copy('{{ $id }}');">
                        <span>{{ __('Asset Group Access Restrictions for Viewers & Users') }}</span>
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
                    {{ __('To protect client confidentiality, multi-brand environments, or segregated department analytics, APIs Hub enables granular Asset Group scoping for non-editor members:') }}
                </p>
                <ul>
                    <li><strong>{{ __('Asset Groups:') }}</strong> {{ __('Custom bundles of connected assets (e.g., "US Brand Facebook Ad Accounts", "European Shopify Stores", or "Staging Sites") created in the Asset Groups manager.') }}</li>
                    <li><strong>{{ __('Assigning Restrictions:') }}</strong> {{ __('In Team & Collaborators, click "Manage Asset Groups" on any collaborator. Toggle "Allow all assets" off to select specific asset groups. The user will only see analytics, metrics, and instances belonging to the selected groups.') }}</li>
                    <li><strong>{{ __('Automatic Filtering:') }}</strong> {{ __('When a restricted user opens a dashboard or queries the node, the engine automatically filters the results. Any request attempting to fetch unassigned accounts or properties will return an empty dataset or an authorization error.') }}</li>
                </ul>
            </div>
        </x-filament::section>

        {{-- Section 4: User-Scoped API Keys & Lifecycle --}}
        @php
            $id = \Illuminate\Support\Str::slug(__('User-Scoped API Keys & Lifecycle'));
        @endphp
        <x-filament::section id="{{ $id }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2 group" x-data="copyLink()">
                    <x-filament::icon icon="heroicon-o-key" class="h-5 w-5 text-emerald-500" />
                    <a href="#{{ $id }}"
                       class="flex items-center gap-2 hover:underline text-inherit"
                       @click.prevent="copy('{{ $id }}');">
                        <span>{{ __('User-Scoped API Keys & Lifecycle') }}</span>
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
                    {{ __('Non-editor collaborators (Viewers and Users) do not have access to the project\'s master API key. Instead, they receive personal user-scoped API keys for external tools and AI agents.') }}
                </p>
                <ul>
                    <li><strong>{{ __('Master Key vs. User Key:') }}</strong> {{ __('Owners and Editors use the Master Key (APP_API_KEY), which grants global access. Viewers and Users access the node using a user-scoped key restricted strictly to their assigned asset groups.') }}</li>
                    <li><strong>{{ __('MCP & AI Integration:') }}</strong> {{ __('Collaborators can connect Google Antigravity, Claude Desktop, or Cursor using their personal scoped key. The MCP server automatically scopes all analytical tools (summarize_performance, get_available_instances) to the user\'s allowed accounts.') }}</li>
                    <li><strong>{{ __('Self-Rotation:') }}</strong> {{ __('Collaborators can regenerate their personal key anytime in the Model Context Protocol (MCP) or API Access page. The new key is pushed instantly to the tenant node.') }}</li>
                    <li><strong>{{ __('Administrative Forced Rotation:') }}</strong> {{ __('Owners and Editors can force-rotate individual collaborator keys in the MCP Access Reference table to immediately revoke compromised tokens. The affected user is notified in real time via email and in-app database alert.') }}</li>
                </ul>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

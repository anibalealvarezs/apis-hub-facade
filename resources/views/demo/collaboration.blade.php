<x-layouts.public 
    :title="$currentLang === 'es' ? 'Paso 4: Colaboración & Equipos | Demostración Interactiva — APIs Hub' : 'Step 4: Team Collaboration | Interactive Demo — APIs Hub'"
    :description="$currentLang === 'es' ? 'Demostración pública interactiva de APIs Hub: Proyectos compartidos, gestión de equipos, roles de acceso y enlaces públicos de dashboards.' : 'APIs Hub interactive public demo: Shareable client projects, team management, role-based asset scoping, and protected public dashboard links.'"
    :keywords="$currentLang === 'es' ? 'colaboracion, equipos, gestion de roles, dashboards compartidos, APIs Hub demo' : 'collaboration, teams, role management, shared dashboards, APIs Hub demo'"
    :canonical-url="$currentLang === 'es' ? route('demo.collaboration.es') : route('demo.collaboration', ['lang' => 'en'])"
    :alternate-en="route('demo.collaboration', ['lang' => 'en'])"
    :alternate-es="route('demo.collaboration.es')"
    :alternate-default="route('demo.collaboration')"
    :meta-image="asset('images/branding/apishub-620.png')"
    :og-type="'website'"
    :body-class="'font-sans'"
    :x-data="'demoCollaboration({
        theme: localStorage.getItem(\'demo_theme\') || (window.matchMedia(\'(prefers-color-scheme: dark)\').matches ? \'dark\' : \'light\'),
        currentLang: \'' . $currentLang . '\',
        showInviteModal: false
    })'"
    :portals="$portals"
>
    <x-slot:extra-head>
        <link rel="stylesheet" href="{{ asset('css/demo-theme.css') }}"/>
        <script src="{{ asset('js/demo-tailwind-config.js') }}"></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="{{ asset('js/demo-controllers.js') }}"></script>
    </x-slot:extra-head>

    <x-slot:stepper>
        <div class="pt-20 md:pt-24">
            <x-demo.stepper :current-step="4" :current-lang="$currentLang" />
        </div>
    </x-slot:stepper>

    <main class="flex-1 w-full max-w-7xl mx-auto p-4 sm:p-6 space-y-6">

        {{-- Page Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-xs">
            <div>
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                            {{ $currentLang === 'es' ? 'Colaboración en Equipo & Proyectos Compartidos' : 'Team Collaboration & Shareable Projects' }}
                        </h1>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5 max-w-3xl">
                            {{ $currentLang === 'es'
                                ? 'Invita a tu equipo y agencias externas con control de acceso por Grupos de Activos, o comparte dashboards en vivo mediante enlaces públicos protegidos.'
                                : 'Invite in-house teams and external media agencies with granular Asset Group access scopes, or publish secure read-only live dashboards with tokenized links.' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button"
                        @click="showInviteModal = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-primary-600 hover:bg-primary-500 text-white shadow-xs transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    <span>{{ $currentLang === 'es' ? 'Invitar Colaborador' : 'Invite Collaborator' }}</span>
                </button>
            </div>
        </div>

        {{-- Section 1: Active Collaborators with Role-Based Asset Scope --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                        {{ $currentLang === 'es' ? 'Miembros del Equipo & Alcance de Acceso' : 'Team Members & Granular Asset Group Scopes' }}
                    </h3>
                    <p class="text-[11px] text-gray-400 mt-0.5">
                        {{ $currentLang === 'es'
                            ? 'Cada colaborador solo ve los Grupos de Activos y datos que le correspondan (ej. Agencia US vs Gerente Regional EU).'
                            : 'Each user is restricted to their assigned Asset Groups so agencies only see their relevant market.' }}
                    </p>
                </div>
                <span class="text-xs text-gray-400 font-mono">{{ count($collaborators) }} {{ $currentLang === 'es' ? 'miembros' : 'members' }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400 uppercase font-semibold">
                        <tr>
                            <th class="px-4 py-3">{{ $currentLang === 'es' ? 'Usuario' : 'Member' }}</th>
                            <th class="px-4 py-3">{{ $currentLang === 'es' ? 'Rol en el Proyecto' : 'Project Role' }}</th>
                            <th class="px-4 py-3">{{ $currentLang === 'es' ? 'Grupos de Activos Asignados' : 'Assigned Asset Scope' }}</th>
                            <th class="px-4 py-3 text-center">{{ $currentLang === 'es' ? 'Estado' : 'Status' }}</th>
                            <th class="px-4 py-3 text-right">{{ $currentLang === 'es' ? 'Último Acceso' : 'Last Active' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach($collaborators as $member)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                                <td class="px-4 py-3.5 font-bold text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-primary-600 to-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                            {{ $member['avatar'] }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $member['name'] }}</p>
                                            <p class="text-[10px] text-gray-400 font-normal">{{ $member['email'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold 
                                            {{ ($member['role_key'] ?? '') === 'project_owner' ? 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700' : '' }}
                                            {{ ($member['role_key'] ?? '') === 'project_editor' ? 'bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 border border-blue-300 dark:border-blue-700' : '' }}
                                            {{ ($member['role_key'] ?? '') === 'project_viewer' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700' : '' }}
                                            {{ ($member['role_key'] ?? '') === 'project_user' ? 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-300 border border-gray-300 dark:border-gray-700' : '' }}
                                        ">
                                            {{ $member['role'] }}
                                        </span>
                                        <span class="text-[10px] font-mono text-gray-400">({{ $member['role_key'] ?? 'role' }})</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full {{ ($member['role_key'] ?? '') === 'project_owner' ? 'bg-emerald-500' : 'bg-primary-500' }}"></span>
                                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $member['asset_scope'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-300">
                                        {{ $member['status'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right text-gray-400 font-mono text-[11px]">
                                    {{ $member['last_login'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Section 2: Shareable Live Dashboards (Public Views) --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>{{ $currentLang === 'es' ? 'Dashboards Compartibles & Enlaces Públicos' : 'Shareable Dashboards & Public Views' }}</span>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-semibold">Live Embedded View</span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $currentLang === 'es'
                            ? 'Publica dashboards interactivos de solo lectura para clientes o inversionistas con protección de contraseña o tokens firmados.'
                            : 'Publish read-only interactive live dashboards with tokenized security, auto-refreshing charts, and full-screen kiosk support.' }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($shareableDashboards as $sd)
                    <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 flex flex-col justify-between space-y-3">
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ $sd['title'] }}</h4>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                    {{ $sd['status'] }}
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 font-mono break-all bg-white dark:bg-gray-900 p-2 rounded border border-gray-200 dark:border-gray-800">
                                {{ $sd['url'] }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between text-[11px] pt-2 border-t border-gray-200 dark:border-gray-700 text-gray-500">
                            <span>🔒 {{ $sd['access'] }}</span>
                            <span class="font-bold">{{ $sd['views_count'] }} {{ $currentLang === 'es' ? 'visitas' : 'views' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Interactive Invitation Modal (Mock) --}}
        <div x-show="showInviteModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
             @click.self="showInviteModal = false">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 w-full max-w-md p-5 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        {{ $currentLang === 'es' ? 'Invitar Nuevo Colaborador' : 'Invite New Team Member' }}
                    </h3>
                    <button type="button" @click="showInviteModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ $currentLang === 'es' ? 'Correo Electrónico' : 'Email Address' }}</label>
                        <input type="email" placeholder="colleague@agency.com" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-2 text-xs text-gray-900 dark:text-gray-100"/>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ $currentLang === 'es' ? 'Rol del Proyecto' : 'Project Role' }}</label>
                        <select class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-2 text-xs text-gray-900 dark:text-gray-100 font-medium">
                            <option value="project_editor">{{ $currentLang === 'es' ? 'Project Editor (Editor del Proyecto)' : 'Project Editor' }}</option>
                            <option value="project_viewer" selected>{{ $currentLang === 'es' ? 'Project Viewer (Visualizador del Proyecto)' : 'Project Viewer' }}</option>
                            <option value="project_user">{{ $currentLang === 'es' ? 'Project User (Usuario Básico del Proyecto)' : 'Project User' }}</option>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">
                            {{ $currentLang === 'es'
                                ? 'Los roles de colaborador disponibles para invitar son project_editor, project_viewer y project_user.'
                                : 'Available assignable collaborator roles are project_editor, project_viewer, and project_user.' }}
                        </p>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ $currentLang === 'es' ? 'Alcance de Grupos de Activos' : 'Asset Group Scope' }}</label>
                        <select class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-2 text-xs text-gray-900 dark:text-gray-100">
                            <option>{{ $currentLang === 'es' ? 'Todos los Grupos de Activos' : 'All Asset Groups' }}</option>
                            <option>{{ $currentLang === 'es' ? 'Marca Principal (Global / EE.UU.)' : 'Main Brand (Global / US)' }}</option>
                            <option>{{ $currentLang === 'es' ? 'Línea Apparel & Calzado (Europa)' : 'Apparel & Footwear Line (Europe)' }}</option>
                            <option>{{ $currentLang === 'es' ? 'Aura Hogar & Deco (Argentina)' : 'Aura Home & Living (Argentina)' }}</option>
                        </select>
                    </div>
                </div>
                <div class="pt-3 flex items-center justify-end gap-2 border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="showInviteModal = false" class="px-3 py-1.5 text-xs text-gray-600 dark:text-gray-400">
                        {{ $currentLang === 'es' ? 'Cancelar' : 'Cancel' }}
                    </button>
                    <button type="button" @click="showInviteModal = false; alert(currentLang === 'es' ? 'Invitación enviada exitosamente.' : 'Invitation sent successfully.')" class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-primary-600 hover:bg-primary-500 text-white shadow-xs">
                        {{ $currentLang === 'es' ? 'Enviar Invitación' : 'Send Invitation' }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Bottom Stepper CTA --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <a href="{{ $currentLang === 'es' ? route('demo.show.es') : route('demo.show', ['lang' => 'en']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>{{ $currentLang === 'es' ? 'Volver al Paso 3: Dashboard Omnicanal' : 'Back to Step 3: Omnichannel Dashboard' }}</span>
            </a>

            <a href="{{ $currentLang === 'es' ? route('demo.intelligence.es') : route('demo.intelligence', ['lang' => 'en']) }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold bg-primary-600 hover:bg-primary-500 text-white shadow-xs transition-colors">
                <span>{{ $currentLang === 'es' ? 'Continuar al Paso 5: Data Intelligence' : 'Continue to Step 5: Data Intelligence' }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </main>
</x-layouts.public>

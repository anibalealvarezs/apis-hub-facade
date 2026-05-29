<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6">
        <div class="fi-ta-ctn rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">Project Roles & Capabilities</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    This table displays all available roles within a project and the specific permissions granted to each. 
                    Roles are assigned per-project, meaning a user can be an Owner in one project and a Viewer in another.
                </p>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach($roles as $role)
                    <div class="p-6 flex flex-col md:flex-row md:items-start gap-4">
                        <div class="w-full md:w-1/3">
                            <h3 class="text-lg font-semibold text-gray-950 dark:text-white">{{ \Illuminate\Support\Str::headline($role->name) }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                @if($role->name === 'project_owner')
                                    The absolute owner of the project. Has full destructive and administrative rights.
                                @elseif($role->name === 'project_editor')
                                    Can manage infrastructure, billing, and collaborators, but cannot delete or transfer the project.
                                @elseif($role->name === 'project_viewer')
                                    Can view data and dashboards, but cannot make any modifications to the infrastructure.
                                @elseif($role->name === 'project_user')
                                    Basic access. Restricted to viewing public data only.
                                @endif
                            </p>
                        </div>
                        <div class="w-full md:w-2/3">
                            <div class="flex flex-wrap gap-2">
                                @forelse($role->permissions as $permission)
                                    <span class="inline-flex items-center rounded-md bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-700/10 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30">
                                        {{ \Illuminate\Support\Str::headline($permission->name) }}
                                    </span>
                                @empty
                                    <span class="text-sm text-gray-500">No specific permissions.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>

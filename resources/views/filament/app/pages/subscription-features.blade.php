<x-filament-panels::page>
    <div class="space-y-8">
        <div class="flex flex-col gap-2">
            <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">Funcionalidades por Plan</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Los permisos establecen qué tipo de tareas pueden hacer los usuarios según el rol asignado, pero los <strong>planes (Tiers)</strong> definen las capacidades estratégicas disponibles para el proyecto de manera transversal.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            
            <!-- FREE TIER -->
            <div class="relative flex flex-col p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                        <x-heroicon-o-paper-airplane class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white uppercase tracking-wider">Free</h3>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 flex-grow">
                    Plan de acceso básico. Funcionalidades limitadas diseñadas para pruebas y proyectos pequeños.
                </p>
                <div class="space-y-3">
                    <div class="flex gap-2 items-start">
                        <x-heroicon-m-check-circle class="w-5 h-5 text-gray-400 shrink-0 mt-0.5" />
                        <span class="text-sm text-gray-600 dark:text-gray-300">Sincronización básica de datos</span>
                    </div>
                    <div class="flex gap-2 items-start">
                        <x-heroicon-m-check-circle class="w-5 h-5 text-gray-400 shrink-0 mt-0.5" />
                        <span class="text-sm text-gray-600 dark:text-gray-300">Uso exclusivo por el propietario</span>
                    </div>
                </div>
            </div>

            <!-- PRO TIER -->
            <div class="relative flex flex-col p-6 bg-white dark:bg-gray-900 rounded-2xl border border-primary-200 dark:border-primary-900/50 shadow-sm transition-all hover:shadow-md hover:border-primary-500 overflow-hidden">
                <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-primary-400 to-primary-600"></div>
                <div class="flex items-center gap-3 mb-4 mt-2">
                    <div class="p-2 bg-primary-50 dark:bg-primary-900/30 rounded-lg">
                        <x-heroicon-o-rocket-launch class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <h3 class="text-lg font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wider">Pro</h3>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 flex-grow">
                    Ideal para agencias y equipos pequeños. Incluye todas las capacidades del plan Free, más:
                </p>
                <div class="space-y-3">
                    <div class="flex gap-2 items-start">
                        <x-heroicon-m-check-circle class="w-5 h-5 text-primary-500 shrink-0 mt-0.5" />
                        <span class="text-sm text-gray-700 dark:text-gray-200 font-medium">Invitar usuarios a colaborar</span>
                    </div>
                    <div class="flex gap-2 items-start">
                        <x-heroicon-m-check-circle class="w-5 h-5 text-primary-500 shrink-0 mt-0.5" />
                        <span class="text-sm text-gray-700 dark:text-gray-200 font-medium">Vistas públicas para clientes <span class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">(próximamente)</span></span>
                    </div>
                </div>
            </div>

            <!-- ULTRA TIER -->
            <div class="relative flex flex-col p-6 bg-white dark:bg-gray-900 rounded-2xl border border-purple-200 dark:border-purple-900/50 shadow-sm transition-all hover:shadow-md hover:border-purple-500 overflow-hidden">
                <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-purple-400 to-purple-600"></div>
                <div class="flex items-center gap-3 mb-4 mt-2">
                    <div class="p-2 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                        <x-heroicon-o-bolt class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <h3 class="text-lg font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider">Ultra</h3>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 flex-grow">
                    Para operaciones automatizadas y masivas. Incluye todas las capacidades del plan Pro, más:
                </p>
                <div class="space-y-3">
                    <div class="flex gap-2 items-start">
                        <x-heroicon-m-check-circle class="w-5 h-5 text-purple-500 shrink-0 mt-0.5" />
                        <span class="text-sm text-gray-700 dark:text-gray-200 font-medium">Acceso Completo a la API de APIs Hub</span>
                    </div>
                    <div class="flex gap-2 items-start">
                        <x-heroicon-m-check-circle class="w-5 h-5 text-purple-500 shrink-0 mt-0.5" />
                        <span class="text-sm text-gray-700 dark:text-gray-200 font-medium">Capacidad de integraciones internas programáticas</span>
                    </div>
                </div>
            </div>

            <!-- ENTERPRISE TIER -->
            <div class="relative flex flex-col p-6 bg-gradient-to-br from-gray-900 to-gray-800 dark:from-gray-950 dark:to-gray-900 rounded-2xl border border-gray-700 shadow-lg transition-all hover:shadow-xl overflow-hidden">
                <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-amber-400 to-amber-600"></div>
                <div class="flex items-center gap-3 mb-4 mt-2">
                    <div class="p-2 bg-amber-500/20 rounded-lg">
                        <x-heroicon-o-building-office-2 class="w-6 h-6 text-amber-400" />
                    </div>
                    <h3 class="text-lg font-bold text-amber-400 uppercase tracking-wider">Enterprise</h3>
                </div>
                <p class="text-sm text-gray-300 dark:text-gray-400 mb-6 flex-grow">
                    Solución de grado corporativo e infraestructura dedicada. Incluye todas las capacidades de Ultra, más:
                </p>
                <div class="space-y-3">
                    <div class="flex gap-2 items-start">
                        <x-heroicon-m-check-circle class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" />
                        <span class="text-sm text-gray-100 font-medium">Compartir Billing Profiles</span>
                    </div>
                    <div class="flex gap-2 items-start">
                        <x-heroicon-m-check-circle class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" />
                        <span class="text-sm text-gray-100 font-medium">Soporte y Estabilidad SLA garantizada</span>
                    </div>
                </div>
            </div>

        </div>

        <div class="mt-8 bg-amber-50 dark:bg-amber-900/20 rounded-xl p-5 border border-amber-200 dark:border-amber-700/50 flex gap-4 items-start">
            <x-heroicon-o-information-circle class="w-6 h-6 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
            <div>
                <h4 class="text-sm font-bold text-amber-800 dark:text-amber-300 mb-1">Nota Importante sobre Acceso a la API</h4>
                <p class="text-sm text-amber-700 dark:text-amber-400/90 leading-relaxed">
                    Las credenciales de acceso a la API (Internal Integration) están reservadas para usuarios del plan <strong>Ultra</strong> y <strong>Enterprise</strong>. Si te encuentras en un plan <em>Free</em> o <em>Pro</em> y necesitas usar la API, deberás actualizar tu Billing Profile o transferir el proyecto a un Billing Profile de nivel superior.
                </p>
            </div>
        </div>

    </div>
</x-filament-panels::page>

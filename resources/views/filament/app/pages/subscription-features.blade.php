<x-filament-panels::page>
    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        Los permisos establecen qué tipo de tareas pueden hacer los usuarios según el rol asignado, pero los <strong>planes (Tiers)</strong> definen las capacidades estratégicas disponibles para el proyecto de manera transversal.
    </div>

    <x-filament::grid default="1" md="2" xl="4" class="gap-6">
        
        <!-- FREE TIER -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-paper-airplane" class="h-6 w-6 text-gray-500" />
                    Free
                </div>
            </x-slot>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Plan de acceso básico. Funcionalidades limitadas diseñadas para pruebas y proyectos pequeños.
            </p>
            <div class="space-y-2 mb-4">
                <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-white/10">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Proyectos</span>
                    <x-filament::badge color="gray">1 máximo</x-filament::badge>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-white/10">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Cuentas por Sincronizar</span>
                    <x-filament::badge color="gray">5 máximo</x-filament::badge>
                </div>
            </div>
            <ul class="space-y-3 pt-2">
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-400 shrink-0" />
                        <span class="text-sm">Sincronización básica de datos</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-gray-400 shrink-0" />
                        <span class="text-sm">Uso exclusivo por el propietario</span>
                    </li>
                </ul>
            </div>
        </x-filament::section>

        <!-- PRO TIER -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400">
                    <x-filament::icon icon="heroicon-o-rocket-launch" class="h-6 w-6" />
                    Pro
                </div>
            </x-slot>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Ideal para agencias y equipos pequeños. Capacidad ampliada para colaboración.
            </p>
            <div class="space-y-2 mb-4">
                <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-white/10">
                    <span class="text-sm font-medium text-primary-700 dark:text-primary-400">Proyectos</span>
                    <x-filament::badge color="primary">5 máximo</x-filament::badge>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-white/10">
                    <span class="text-sm font-medium text-primary-700 dark:text-primary-400">Cuentas por Sincronizar</span>
                    <x-filament::badge color="primary">100 máximo</x-filament::badge>
                </div>
            </div>
            <ul class="space-y-3 pt-2">
                    <li class="flex items-start gap-2 opacity-75">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-primary-400 shrink-0" />
                        <span class="text-sm">Sincronización básica de datos</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-primary-500 shrink-0" />
                        <span class="text-sm font-medium">Invitar usuarios a colaborar</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-primary-500 shrink-0" />
                        <span class="text-sm font-medium">Vistas públicas para clientes <span class="text-xs text-primary-600 dark:text-primary-400 italic font-normal">(próximamente)</span></span>
                    </li>
                </ul>
            </div>
        </x-filament::section>

        <!-- ULTRA TIER -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-purple-600 dark:text-purple-400">
                    <x-filament::icon icon="heroicon-o-bolt" class="h-6 w-6" />
                    Ultra
                </div>
            </x-slot>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Para operaciones automatizadas, masivas y acceso programático total.
            </p>
            <div class="space-y-2 mb-4">
                <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-white/10">
                    <span class="text-sm font-medium text-purple-700 dark:text-purple-400">Proyectos</span>
                    <x-filament::badge color="purple">15 máximo</x-filament::badge>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-white/10">
                    <span class="text-sm font-medium text-purple-700 dark:text-purple-400">Cuentas por Sincronizar</span>
                    <x-filament::badge color="purple">500 máximo</x-filament::badge>
                </div>
            </div>
            <ul class="space-y-3 pt-2">
                    <li class="flex items-start gap-2 opacity-75">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-purple-400 shrink-0" />
                        <span class="text-sm">Sincronización básica de datos</span>
                    </li>
                    <li class="flex items-start gap-2 opacity-75">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-purple-400 shrink-0" />
                        <span class="text-sm">Invitar usuarios a colaborar</span>
                    </li>
                    <li class="flex items-start gap-2 opacity-75">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-purple-400 shrink-0" />
                        <span class="text-sm">Vistas públicas para clientes</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-purple-500 shrink-0" />
                        <span class="text-sm font-medium">Acceso Completo a la API de APIs Hub</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-purple-500 shrink-0" />
                        <span class="text-sm font-medium">Capacidad de integraciones internas</span>
                    </li>
                </ul>
            </div>
        </x-filament::section>

        <!-- ENTERPRISE TIER -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-warning-500 dark:text-warning-400">
                    <x-filament::icon icon="heroicon-o-building-office-2" class="h-6 w-6" />
                    Enterprise
                </div>
            </x-slot>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Solución de grado corporativo e infraestructura y soporte dedicados.
            </p>
            <div class="space-y-2 mb-4">
                <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-white/10">
                    <span class="text-sm font-medium text-warning-700 dark:text-warning-400">Proyectos</span>
                    <x-filament::badge color="warning">A medida (Base 10)</x-filament::badge>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-white/10">
                    <span class="text-sm font-medium text-warning-700 dark:text-warning-400">Cuentas por Sincronizar</span>
                    <x-filament::badge color="warning">A medida (Base 500)</x-filament::badge>
                </div>
            </div>
            <ul class="space-y-3 pt-2">
                    <li class="flex items-start gap-2 opacity-75">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-warning-400 shrink-0" />
                        <span class="text-sm">Sincronización de datos + Colaboradores</span>
                    </li>
                    <li class="flex items-start gap-2 opacity-75">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-warning-400 shrink-0" />
                        <span class="text-sm">Vistas públicas + Acceso total a API</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-warning-500 shrink-0" />
                        <span class="text-sm font-medium">Compartir Billing Profiles</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5 text-warning-500 shrink-0" />
                        <span class="text-sm font-medium">Soporte y Estabilidad SLA garantizada</span>
                    </li>
                </ul>
            </div>
        </x-filament::section>

    </x-filament::grid>

    <x-filament::section icon="heroicon-o-information-circle" icon-color="warning">
        <x-slot name="heading">
            <span class="text-warning-600 dark:text-warning-400">Nota Importante sobre Acceso a la API</span>
        </x-slot>
        <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
            Las credenciales de acceso a la API (Internal Integration) están reservadas para usuarios del plan <strong>Ultra</strong> y <strong>Enterprise</strong>. Si te encuentras en un plan <em>Free</em> o <em>Pro</em> y necesitas usar la API, deberás actualizar tu Billing Profile o transferir el proyecto a un Billing Profile de nivel superior.
        </p>
    </x-filament::section>

</x-filament-panels::page>

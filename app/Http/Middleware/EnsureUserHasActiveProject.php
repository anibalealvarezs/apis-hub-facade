<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use Filament\Facades\Filament;

class EnsureUserHasActiveProject
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 0. Si es logout, validación de correo, o CREACIÓN de proyecto, lo dejamos pasar sin preguntas
        if (
            $request->is('*/logout') || 
            $request->is('*/email-verification*') || 
            $request->routeIs('filament.app.tenant.registration') ||
            $request->routeIs('filament.app.tenant.profile')
        ) {
            return $next($request);
        }

        // 1. Verificamos que el usuario esté logueado
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        
        // 2. Si el usuario está en el Account Panel, verificar si tiene proyectos
        // Si no tiene, forzar la creación. Si tiene, dejarlo pasar.
        if ($request->routeIs('filament.account.*') || $request->is('account*')) {
            $hasProjects = $user->projects()->exists();
            if (!$hasProjects) {
                return redirect()->route('filament.app.tenant.registration');
            }
            return $next($request);
        }

        // 3. Obtenemos el slug de la URL (si existe) para el App Panel
        // En Filament, el parámetro del tenant suele llamarse 'tenant'
        $slugFromUrl = $request->route('tenant');

        // 4. Verificamos si el slug actual es válido para este usuario
        // Si ya estamos en un subdominio válido, dejamos pasar el request
        if ($slugFromUrl) {
            $currentProjectExists = $user->projects()
                ->where('subdomain', $slugFromUrl)
                ->first();

            if ($currentProjectExists) {
                // Prevent full functionality if there is no billing profile
                if (empty($currentProjectExists->billing_profile_id) && !$request->routeIs('filament.app.pages.project-billing-settings')) {
                    if ($currentProjectExists->user_id === $user->id) {
                        \Filament\Notifications\Notification::make()
                            ->title('Billing Profile Required')
                            ->body('This project is missing a billing profile. Please assign one to continue.')
                            ->warning()
                            ->send();
                        return redirect()->route('filament.app.pages.project-billing-settings', ['tenant' => $currentProjectExists->subdomain]);
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Project Inactive')
                            ->body('This project is missing a billing profile. The project owner must configure billing to restore access.')
                            ->danger()
                            ->send();
                        
                        $alt = $user->projects()->whereNotNull('billing_profile_id')->first();
                        if ($alt) {
                            return redirect()->route('filament.app.pages.dashboard', ['tenant' => $alt->subdomain]);
                        }
                        return redirect()->route('filament.account.pages.dashboard');
                    }
                }

                // 4.1 Set Spatie Permissions Team ID globally for this request
                if (function_exists('setPermissionsTeamId')) {
                    setPermissionsTeamId($currentProjectExists->id);
                }

                return $next($request);
            }
        }

        // 4. Si llegamos aquí es porque: No hay slug, o el slug es de un proyecto archivado/inexistente.
        
        // Buscamos el primer proyecto alternativo
        $alternativeProject = $user->projects()->first();

        // 5. Si encontramos uno, lo mandamos allí
        if ($alternativeProject) {
             // Evitamos bucles si ya estamos intentando redirigir al mismo lugar
            if ($slugFromUrl === $alternativeProject->subdomain) {
                return $next($request);
            }

            return redirect()->route('filament.app.pages.dashboard', ['tenant' => $alternativeProject->subdomain]);
        }

        // 6. Si no tiene proyectos, lo mandamos a crear uno
        return redirect()->route('filament.app.tenant.registration');
    }
}

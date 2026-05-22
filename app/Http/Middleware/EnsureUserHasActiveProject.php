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
        
        // 2. Obtenemos el slug de la URL (si existe)
        // En Filament, el parámetro del tenant suele llamarse 'tenant'
        $slugFromUrl = $request->route('tenant');

        // 3. Verificamos si el slug actual es válido y activo para este usuario
        // Si ya estamos en un subdominio válido, dejamos pasar el request
        if ($slugFromUrl) {
            $currentProjectExists = $user->projects()
                ->where('subdomain', $slugFromUrl)
                ->where('is_active', true)
                ->exists();

            if ($currentProjectExists) {
                return $next($request);
            }
        }

        // 4. Si llegamos aquí es porque: No hay slug, o el slug es de un proyecto archivado/inexistente.
        
        // Buscamos el primer proyecto alternativo que esté activo
        $alternativeProject = $user->projects()
            ->where('is_active', true)
            ->first();

        // 5. Si encontramos uno, lo mandamos allí
        if ($alternativeProject) {
             // Evitamos bucles si ya estamos intentando redirigir al mismo lugar
            if ($slugFromUrl === $alternativeProject->subdomain) {
                return $next($request);
            }

            return redirect()->route('filament.app.pages.dashboard', ['tenant' => $alternativeProject->subdomain]);
        }

        // 6. Si no tiene proyectos activos, lo mandamos a crear uno
        return redirect()->route('filament.app.tenant.registration');
    }
}

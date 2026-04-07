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
        // 1. Verificamos que el usuario esté logueado
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        
        // Obtenemos el panel actual (asumiendo que es 'app')
        $panelId = Filament::getCurrentPanel()?->getId();

        if ($panelId !== 'app') {
            return $next($request);
        }

        // 2. Intentamos obtener el tenant (proyecto) actual de la URL (si existe)
        try {
            $tenant = Filament::getTenant();
        } catch (\Throwable $e) {
            $tenant = null;
        }

        // 3. Si no hay proyecto válido en la URL o el proyecto está en la papelera
        if (!$tenant) {
            // Buscamos el primer proyecto activo del usuario
            $firstProject = $user->projects()->where('is_active', true)->first();

            if ($firstProject) {
                // Redirigimos al Dashboard del primer proyecto válido
                return redirect()->route('filament.app.pages.dashboard', ['tenant' => $firstProject->subdomain]);
            }

            // 4. Si no tiene proyectos activos, lo mandamos a crear uno (si no estamos ya allí)
            $registrationRoute = 'filament.app.tenant.registration';
            if ($request->routeIs($registrationRoute)) {
                return $next($request);
            }

            return redirect()->route($registrationRoute);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ProjectInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvitationController extends Controller
{
    public function accept(Request $request, $token)
    {
        $invitation = ProjectInvitation::where('token', $token)->firstOrFail();

        // 1. Validar que la invitación no haya expirado
        if ($invitation->expires_at->isPast()) {
            return redirect('/')->withErrors(['invitation' => 'Esta invitación ha expirado.']);
        }

        // 2. Si el usuario ESTÁ logueado
        if (Auth::check()) {
            $user = Auth::user();

            // Verificar si el correo coincide
            if ($user->email !== $invitation->email) {
                // Cerramos sesión porque el correo no coincide con la invitación
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Guardamos el token y mandamos a login/registro
                $request->session()->put('invitation_token', $token);
                return redirect()->route('filament.app.auth.login')
                    ->with('warning', 'La invitación es para otro correo. Por favor inicia sesión o regístrate con ' . $invitation->email);
            }

            // El correo coincide, lo vinculamos
            $this->processInvitation($user, $invitation);

            // Redirigimos al panel del proyecto
            return redirect()->route('filament.app.pages.dashboard', ['tenant' => $invitation->project->subdomain])
                ->with('success', '¡Invitación aceptada exitosamente!');
        }

        // 3. Si el usuario NO ESTÁ logueado
        // Guardamos el token en sesión para recuperarlo tras el registro o login
        $request->session()->put('invitation_token', $token);
        
        // Redirigir a la página de registro
        return redirect()->route('filament.app.auth.register')
            ->with('info', 'Por favor, crea una cuenta para unirte al proyecto.');
    }

    /**
     * Vincula el usuario al proyecto, asigna el rol y elimina la invitación.
     */
    protected function processInvitation($user, $invitation)
    {
        // 1. Vincular al proyecto (si no está ya)
        if (!$user->projects()->where('project_id', $invitation->project_id)->exists()) {
            $user->projects()->attach($invitation->project_id);
        }

        // 2. Asignar rol usando Spatie (inserción directa para evitar problemas de caché)
        $roleObj = \Spatie\Permission\Models\Role::where('name', $invitation->role)->first();
        if ($roleObj) {
            \Illuminate\Support\Facades\DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $roleObj->id,
                'model_type' => get_class($user),
                'model_id' => $user->id,
                'project_id' => $invitation->project_id,
            ]);
        }

        // 3. Eliminar invitación
        $invitation->delete();
    }
}

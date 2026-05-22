<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Login;
use App\Models\ProjectInvitation;

class ProcessProjectInvitation
{
    /**
     * Handle the event.
     */
    public function handle(Registered|Login|\Filament\Events\Auth\Registered $event): void
    {
        $token = session('invitation_token');

        if (!$token) {
            return;
        }

        $invitation = ProjectInvitation::where('token', $token)->first();

        // Si la invitación no existe o ya expiró
        if (!$invitation || $invitation->expires_at->isPast()) {
            session()->forget('invitation_token');
            return;
        }

        // Filament Registered event uses getUser() instead of user property
        $user = $event instanceof \Filament\Events\Auth\Registered 
            ? $event->getUser() 
            : $event->user;

        // Si el correo no coincide con la invitación, no hacemos nada 
        // (el usuario se registró con otro correo distinto al invitado)
        if ($user->email !== $invitation->email) {
            return;
        }

        // 1. Vincular al proyecto (si no está ya)
        if (!$user->projects()->where('project_id', $invitation->project_id)->exists()) {
            $user->projects()->attach($invitation->project_id);
        }

        // 2. Asignar rol usando Spatie
        setPermissionsTeamId($invitation->project_id);
        
        if (!$user->hasRole($invitation->role)) {
            $user->assignRole($invitation->role);
        }

        // 3. Autovalidar correo si es un nuevo registro
        if (($event instanceof Registered || $event instanceof \Filament\Events\Auth\Registered) && !$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        // 4. Eliminar invitación y limpiar sesión
        $invitation->delete();
        session()->forget('invitation_token');
    }
}

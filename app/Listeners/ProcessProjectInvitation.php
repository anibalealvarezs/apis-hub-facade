<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Login;
use App\Models\ProjectInvitation;
use Illuminate\Support\Facades\DB;

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

        // Validar límite de 1 proyecto para plan gratuito
        if ($user->hasOnlyFreeProfiles() && $user->getTotalAccessibleProjectsCount() >= 1) {
            session()->flash('warning', 'Para colaborar en este proyecto en tu plan gratuito, primero debes eliminar tu proyecto de pruebas propio para desmontar su infraestructura y liberar recursos.');
            return;
        }

        // 1. Vincular al proyecto (si no está ya)
        if (!$user->projects()->where('project_id', $invitation->project_id)->exists()) {
            $user->projects()->attach($invitation->project_id);
        }

        // 2. Asignar rol (inserción directa para evitar problemas de caché)
        $roleObj = \Spatie\Permission\Models\Role::where('name', $invitation->role)->first();
        if ($roleObj) {
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $roleObj->id,
                'model_type' => get_class($user),
                'model_id' => $user->id,
                'project_id' => $invitation->project_id,
            ]);
        }

        // 3. Notificar a editores y owner del proyecto
        $project = $invitation->project;
        $editorAndOwnerIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn('roles.name', ['project_editor', 'project_owner'])
            ->where('model_has_roles.project_id', $project->id)
            ->where('model_has_roles.model_id', '!=', $user->id)
            ->pluck('model_has_roles.model_id')
            ->unique()
            ->values()
            ->toArray();

        $usersToNotify = User::whereIn('id', $editorAndOwnerIds)->get();
        foreach ($usersToNotify as $notifyUser) {
            $notifyUser->notify(new \App\Notifications\InvitationAccepted($project, $user));
        }

        // 4. Autovalidar correo si es un nuevo registro
        if (($event instanceof Registered || $event instanceof \Filament\Events\Auth\Registered) && !$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        // 5. Eliminar invitación y limpiar sesión
        $invitation->delete();
        session()->forget('invitation_token');
    }
}

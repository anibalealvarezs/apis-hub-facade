<?php

namespace App\Policies;

use App\Models\Dashboard;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DashboardPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Dashboard $dashboard): bool
    {
        if ($dashboard->is_public) {
            return $user->projects->contains($dashboard->project_id);
        }

        if ($dashboard->user_id === $user->id) {
            return true;
        }

        if ($dashboard->sharedUsers()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return \Illuminate\Support\Facades\DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('model_has_roles.project_id', $dashboard->project_id)
            ->whereIn('roles.name', ['project_owner', 'project_editor'])
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('edit_preferences') || $user->can('create_dashboard');
    }

    public function update(User $user, Dashboard $dashboard): bool
    {
        return $user->can('edit_preferences') || $user->can('update_dashboard');
    }

    public function delete(User $user, Dashboard $dashboard): bool
    {
        return $user->can('edit_preferences') || $user->can('delete_dashboard');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('edit_preferences') || $user->can('delete_any_dashboard');
    }
}

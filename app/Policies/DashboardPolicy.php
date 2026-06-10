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

        return false;
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

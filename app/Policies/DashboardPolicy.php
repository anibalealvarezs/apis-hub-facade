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
        return $user->can('view_any_dashboard');
    }

    public function view(User $user, Dashboard $dashboard): bool
    {
        if ($user->can('view_dashboard')) {
            return true;
        }

        if ($dashboard->is_public) {
            return $user->projects->contains($dashboard->project_id);
        }

        if ($dashboard->user_id === $user->id) {
            return true;
        }

        return $dashboard->sharedUsers()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('create_dashboard');
    }

    public function update(User $user, Dashboard $dashboard): bool
    {
        return $user->can('update_dashboard');
    }

    public function delete(User $user, Dashboard $dashboard): bool
    {
        return $user->can('delete_dashboard');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_dashboard');
    }
}

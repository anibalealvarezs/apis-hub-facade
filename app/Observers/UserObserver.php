<?php

namespace App\Observers;

use App\Models\User;
use App\Enums\UserTier;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        // Automatically assign FREE tier if not explicitly set
        if (empty($user->tier)) {
            $user->tier = UserTier::FREE;
        }
    }
}

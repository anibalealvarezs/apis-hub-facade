<?php

namespace App\Observers;

use App\Models\User;
use App\Enums\UserTier;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Automatically provision a default billing profile for the user
        $user->billingProfiles()->create([
            'name' => $user->name . ' Default Profile',
            'type' => 'personal',
            'tier' => UserTier::FREE,
            'country_code' => 'ES',
            'is_default' => true,
            'health_status' => 'healthy',
        ]);
    }
}

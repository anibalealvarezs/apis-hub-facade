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
        $token = session('invitation_token');
        $hasValidInvitation = false;

        if ($token) {
            $invitation = \App\Models\ProjectInvitation::where('token', $token)->first();
            if ($invitation && !$invitation->expires_at->isPast() && $invitation->email === $user->email) {
                $hasValidInvitation = true;
            }
        }

        // Automatically provision a default billing profile for the user, only if not invited
        if (!$hasValidInvitation) {
            $user->billingProfiles()->create([
                'name' => $user->name . ' Default Profile',
                'type' => 'personal',
                'tier' => UserTier::FREE,
                'country_code' => 'ES',
                'is_default' => true,
                'health_status' => 'healthy',
                'status' => 'active',
            ]);
        }
    }
}

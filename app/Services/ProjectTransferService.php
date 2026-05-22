<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Models\BillingProfile;
use App\Enums\UserTier;
use Illuminate\Support\Facades\Log;
use Exception;

class ProjectTransferService
{
    /**
     * Validates if a transfer can be initiated from the sender to the recipient.
     */
    public function canInitiateTransfer(Project $project, User $recipient): array
    {
        $sender = $project->trueOwner;

        if (!$sender) {
            return ['status' => false, 'message' => 'Project has no valid owner.'];
        }

        if ($sender->id === $recipient->id) {
            return ['status' => false, 'message' => 'Cannot transfer project to yourself.'];
        }

        // Ecosystem Rule: Enterprise projects only to Enterprise users
        if ($sender->tier === UserTier::ENTERPRISE && $recipient->tier !== UserTier::ENTERPRISE) {
            return ['status' => false, 'message' => 'Enterprise projects can only be transferred to Enterprise accounts.'];
        }

        // Ecosystem Rule: Individual projects only to Individual users
        if ($sender->tier !== UserTier::ENTERPRISE && $recipient->tier === UserTier::ENTERPRISE) {
            return ['status' => false, 'message' => 'Individual tier projects cannot be transferred to Enterprise accounts.'];
        }

        // Quota check
        if (!$recipient->canCreateMoreProjects()) {
            return ['status' => false, 'message' => 'Recipient has no available project quota in their current tier.'];
        }

        return ['status' => true, 'message' => 'Transfer can be initiated.'];
    }

    /**
     * Executes the transfer after recipient has accepted and provided a billing profile if needed.
     */
    public function executeTransfer(Project $project, User $recipient, ?BillingProfile $newBillingProfile = null, bool $keepSenderBilling = false): bool
    {
        // Re-validate quota right before transfer to prevent race conditions
        $validation = $this->canInitiateTransfer($project, $recipient);
        if (!$validation['status']) {
            throw new Exception("Transfer validation failed: " . $validation['message']);
        }

        // Billing Assignment Logic
        // If the sender wants to revoke their billing profile (which is the default expectation)
        if (!$keepSenderBilling) {
            if (!$newBillingProfile || $newBillingProfile->user_id !== $recipient->id) {
                throw new Exception("Recipient must provide a valid billing profile to assume ownership.");
            }

            // Remove old billing profiles from this project
            $project->authorizedBillingProfiles()->detach();

            // Attach the new billing profile
            $project->authorizedBillingProfiles()->attach($newBillingProfile->id, [
                'is_primary' => true,
                'status' => 'active',
                'assigned_by_user_id' => $recipient->id,
            ]);
        } else {
            // Keep sender's billing, meaning sender shares their billing profile with the recipient for this project
            // In a more robust system, we would create a record in `billing_profile_user` allowing the recipient to use it
            $currentPrimary = $project->authorizedBillingProfiles()->wherePivot('is_primary', true)->first();
            if ($currentPrimary) {
                // Ensure recipient has shared access
                $currentPrimary->sharedWithUsers()->syncWithoutDetaching([
                    $recipient->id => ['role' => 'shared_payer']
                ]);
            }
        }

        // Change Ownership
        $project->user_id = $recipient->id;
        $project->save();

        // Ensure the recipient is also a member in the ProjectUser pivot
        $project->users()->syncWithoutDetaching([$recipient->id]);

        Log::info("Project {$project->id} successfully transferred from to User {$recipient->id}.");

        return true;
    }
}

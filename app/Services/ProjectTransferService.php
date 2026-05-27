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

        $senderProfile = $project->billingProfile;
        $senderTier = $senderProfile?->tier ?? UserTier::FREE;

        $recipientProfile = $recipient->billingProfiles()->where('is_default', true)->first();
        if (!$recipientProfile) {
            return ['status' => false, 'message' => 'Recipient has no valid billing profile.'];
        }
        $recipientTier = $recipientProfile->tier;

        // Ecosystem Rule: Enterprise projects only to Enterprise users
        if ($senderTier === UserTier::ENTERPRISE && $recipientTier !== UserTier::ENTERPRISE) {
            return ['status' => false, 'message' => 'Enterprise projects can only be transferred to Enterprise accounts.'];
        }

        // Ecosystem Rule: Individual projects only to Individual users
        if ($senderTier !== UserTier::ENTERPRISE && $recipientTier === UserTier::ENTERPRISE) {
            return ['status' => false, 'message' => 'Individual tier projects cannot be transferred to Enterprise accounts.'];
        }

        // Quota check
        $activeProjectsCount = $recipientProfile->projects()->where('billing_status', 'active')->count();
        $maxProjects = $this->getMaxProjectsForTier($recipientTier);
        if ($activeProjectsCount >= $maxProjects) {
            return ['status' => false, 'message' => 'Recipient\'s default billing profile has no available project quota.'];
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
        if (!$keepSenderBilling) {
            if (!$newBillingProfile) {
                throw new Exception("Recipient must provide a valid billing profile to assume ownership.");
            }

            // Quota check on the specific new billing profile chosen!
            $activeProjectsCount = $newBillingProfile->projects()->where('billing_status', 'active')->count();
            $maxProjects = $this->getMaxProjectsForTier($newBillingProfile->tier);
            if ($activeProjectsCount >= $maxProjects) {
                throw new Exception("The selected billing profile ({$newBillingProfile->name}) has no available project quota (limit: {$maxProjects}).");
            }

            // Assign the new billing profile directly to the project
            $project->billing_profile_id = $newBillingProfile->id;

            // If the billing profile belongs to someone other than the recipient
            if ($newBillingProfile->user_id !== $recipient->id) {
                // Check if the profile is shared with the recipient
                $isShared = $newBillingProfile->sharedWithUsers()->where('users.id', $recipient->id)->exists();
                if (!$isShared) {
                    throw new Exception("The selected billing profile is not shared with the recipient.");
                }

                // If shared, it requires approval from the billing profile owner (the "padre")
                $project->billing_status = 'pending_approval';
                $project->is_active = false; // Inactive until approved
            } else {
                // Owner's own profile - automatically active
                $project->billing_status = 'active';
                $project->is_active = true;
            }
        } else {
            // Keep sender's billing, meaning sender shares their billing profile with the recipient for this project
            $currentProfile = $project->billingProfile;
            if ($currentProfile) {
                // Ensure recipient has shared access
                $currentProfile->sharedWithUsers()->syncWithoutDetaching([
                    $recipient->id => ['role' => 'shared_payer']
                ]);
            }
        }

        // Change Ownership (Technical ownership changes, administrative billing profile pays)
        $project->user_id = $recipient->id;
        $project->save();

        // Ensure the recipient is also a member in the ProjectUser pivot
        $project->users()->syncWithoutDetaching([$recipient->id]);

        Log::info("Project {$project->id} successfully transferred to User {$recipient->id} under Billing Profile {$project->billing_profile_id} (Status: {$project->billing_status}).");

        return true;
    }

    protected function getMaxProjectsForTier(UserTier $tier): int
    {
        return match ($tier) {
            UserTier::FREE => 1,
            UserTier::PRO => 5,
            UserTier::ULTRA, UserTier::FOUNDER => 15,
            UserTier::ENTERPRISE => 15,
            UserTier::SUSPENDED => 0,
            default => 1,
        };
    }
}

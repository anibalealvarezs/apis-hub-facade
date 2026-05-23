<?php

namespace App\Services;

use App\Models\User;
use App\Models\Project;
use App\Enums\UserTier;
use App\Models\BillingLog;
use Illuminate\Support\Facades\Log;

class BillingLifecycleService
{
    /**
     * Enforces the downgrade cascading suspension for a specific user.
     * Starts suspending newest projects until the user's active projects match the allowed quota.
     *
     * @param User $user The user (project owner)
     * @param UserTier $targetTier The tier to enforce limits for
     * @return array Information about suspended projects
     */
    public function enforceDowngradeLimits(User $user, UserTier $targetTier): array
    {
        $suspendedProjects = [];

        // Note: For Enterprise, we suspend until base limits (Ultra limits) are met.
        // There is no downgrade below Enterprise for Enterprise users.
        $enforcementTier = $targetTier;
        if ($user->tier === UserTier::ENTERPRISE && $targetTier !== UserTier::SUSPENDED) {
            $enforcementTier = UserTier::ULTRA; // Enterprise base quota equals Ultra
        }

        $maxProjects = $this->getMaxProjectsForTier($enforcementTier);

        // We only enforce limits on projects OWNED by this user.
        $activeOwnedProjects = $user->ownedProjects()->where('billing_status', 'active')->orderBy('created_at', 'desc')->get();

        // 1. Enforce Project Limits
        $excessProjectsCount = $activeOwnedProjects->count() - $maxProjects;
        
        if ($excessProjectsCount > 0) {
            // Suspend the newest ones first
            $projectsToSuspend = $activeOwnedProjects->take($excessProjectsCount);
            foreach ($projectsToSuspend as $project) {
                $project->update(['billing_status' => 'suspended']);
                $suspendedProjects[] = $project->id;
                
                BillingLog::create([
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'event_type' => 'project_suspended',
                    'gateway' => 'system',
                    'description' => "Suspended project {$project->id} for user {$user->id} due to project quota limits.",
                    'metadata' => [
                        'target_tier' => $targetTier->value,
                        'reason' => 'quota_exceeded'
                    ]
                ]);
                
                Log::info("BillingLifecycleService: Suspended project {$project->id} for user {$user->id} due to project quota limits.");
            }
            
            // Refresh active projects list
            $activeOwnedProjects = $user->ownedProjects()->where('billing_status', 'active')->orderBy('created_at', 'desc')->get();
        }

        // 2. Enforce Account Limits (Future Implementation when we track account counts)
        // For now, the user mentioned limits like "100 accounts for Pro". 
        // We will implement the skeleton for this here.
        /*
        $maxAccounts = $this->getMaxAccountsForTier($enforcementTier);
        while ($this->getTotalActiveAccounts($activeOwnedProjects) > $maxAccounts && $activeOwnedProjects->count() > 0) {
            $projectToSuspend = $activeOwnedProjects->first(); // The newest active one
            $projectToSuspend->update(['billing_status' => 'suspended']);
            $suspendedProjects[] = $projectToSuspend->id;
            Log::info("BillingLifecycleService: Suspended project {$projectToSuspend->id} for user {$user->id} due to account quota limits.");
            
            // Remove from the collection for the next loop iteration
            $activeOwnedProjects->shift();
        }
        */

        // Finally, if it's an actual tier change, update the user
        if ($user->tier !== $targetTier && $user->tier !== UserTier::ENTERPRISE) {
            $user->update(['tier' => $targetTier]);
        }

        return $suspendedProjects;
    }

    /**
     * Preview the consequences of a downgrade without actually performing it.
     */
    public function previewDowngrade(User $user, UserTier $targetTier): array
    {
        // ... (Similar logic but without updating the DB, just returning project IDs)
        $projectsToSuspend = [];

        $enforcementTier = $targetTier;
        if ($user->tier === UserTier::ENTERPRISE) {
            $enforcementTier = UserTier::ULTRA;
        }

        $maxProjects = $this->getMaxProjectsForTier($enforcementTier);
        $activeOwnedProjects = $user->ownedProjects()->where('billing_status', 'active')->orderBy('created_at', 'desc')->get();

        $excessProjectsCount = $activeOwnedProjects->count() - $maxProjects;
        
        if ($excessProjectsCount > 0) {
            $projectsToSuspend = $activeOwnedProjects->take($excessProjectsCount)->pluck('id')->toArray();
            $activeOwnedProjects = $activeOwnedProjects->slice($excessProjectsCount);
        }

        // Preview account limits here in the future...

        return $projectsToSuspend;
    }

    protected function getMaxProjectsForTier(UserTier $tier): int
    {
        return match ($tier) {
            UserTier::FREE => 1,
            UserTier::PRO => 5,
            UserTier::ULTRA, UserTier::FOUNDER => 15,
            UserTier::ENTERPRISE => 15, // Base limits
            UserTier::SUSPENDED => 0,
            default => 1,
        };
    }

    protected function getMaxAccountsForTier(UserTier $tier): int
    {
        return match ($tier) {
            UserTier::FREE => 5,
            UserTier::PRO => 100,
            UserTier::ULTRA, UserTier::FOUNDER => 500,
            UserTier::ENTERPRISE => 500, // Base limits
            UserTier::SUSPENDED => 0,
            default => 5,
        };
    }
}

<?php

namespace App\Services;

use App\Models\User;
use App\Models\Project;
use App\Enums\UserTier;
use App\Models\BillingProfile;
use App\Models\BillingLog;
use Illuminate\Support\Facades\Log;

class BillingLifecycleService
{
    /**
     * Enforces the downgrade cascading suspension for a specific billing profile.
     * Starts suspending newest projects until the profile's active projects match the allowed quota.
     *
     * @param BillingProfile $profile The billing profile
     * @param UserTier $targetTier The tier to enforce limits for
     * @return array Information about suspended projects
     */
    public function enforceDowngradeLimits(BillingProfile $profile, UserTier $targetTier): array
    {
        $suspendedProjects = [];

        $enforcementTier = $targetTier;
        if ($profile->tier === UserTier::ENTERPRISE) {
            if ($targetTier === UserTier::SUSPENDED) {
                $profile->status = 'suspended';
                $profile->saveQuietly();
                $enforcementTier = UserTier::SUSPENDED;
            } else {
                // Enterprise cannot suffer downgrade, so they directly get suspended instead!
                $profile->status = 'suspended';
                $profile->saveQuietly();
                $enforcementTier = UserTier::SUSPENDED;
            }
        } else {
            // For non-Enterprise, update the tier quietly
            if ($profile->tier !== $targetTier) {
                $profile->tier = $targetTier;
            }
            if ($targetTier === UserTier::SUSPENDED) {
                $profile->status = 'suspended';
                $enforcementTier = UserTier::SUSPENDED;
            }
            $profile->saveQuietly();
        }

        $maxProjects = $this->getMaxProjectsForTier($enforcementTier);

        // Active projects assigned directly to this billing profile
        $activeProjects = $profile->projects()->where('billing_status', 'active')->orderBy('created_at', 'desc')->get();

        // 1. Enforce Project Limits
        $excessProjectsCount = $activeProjects->count() - $maxProjects;
        
        if ($excessProjectsCount > 0) {
            // Suspend the newest ones first
            $projectsToSuspend = $activeProjects->take($excessProjectsCount);
            foreach ($projectsToSuspend as $project) {
                $project->update([
                    'billing_status' => 'suspended',
                    'is_active' => false,
                ]);
                $suspendedProjects[] = $project->id;
                
                // Dispatch domain and container suspension background jobs
                \App\Jobs\SuspendProjectDomainJob::dispatch($project);
                \App\Jobs\StopProjectContainersJob::dispatch($project);
                
                BillingLog::create([
                    'user_id' => $profile->user_id,
                    'project_id' => $project->id,
                    'event_type' => 'project_suspended',
                    'gateway' => 'system',
                    'description' => "Suspended project {$project->id} for billing profile {$profile->id} due to project quota limits.",
                    'metadata' => [
                        'target_tier' => $targetTier->value,
                        'reason' => 'quota_exceeded'
                    ]
                ]);
                
                Log::info("BillingLifecycleService: Suspended project {$project->id} for billing profile {$profile->id} due to project quota limits.");
            }
        }

        return $suspendedProjects;
    }

    /**
     * Preview the consequences of a downgrade without actually performing it.
     */
    public function previewDowngrade(BillingProfile $profile, UserTier $targetTier): array
    {
        $projectsToSuspend = [];

        $enforcementTier = $targetTier;
        if ($profile->tier === UserTier::ENTERPRISE) {
            $enforcementTier = UserTier::SUSPENDED;
        }

        $maxProjects = $this->getMaxProjectsForTier($enforcementTier);
        $activeProjects = $profile->projects()->where('billing_status', 'active')->orderBy('created_at', 'desc')->get();

        $excessProjectsCount = $activeProjects->count() - $maxProjects;
        
        if ($excessProjectsCount > 0) {
            $projectsToSuspend = $activeProjects->take($excessProjectsCount)->pluck('id')->toArray();
        }

        return $projectsToSuspend;
    }

    public function getMaxProjectsForTier(UserTier $tier): int
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

    public function getMaxAccountsForTier(UserTier $tier): int
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

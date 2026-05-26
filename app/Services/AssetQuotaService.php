<?php

namespace App\Services;

use App\Models\AssetBillingLock;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Carbon;

class AssetQuotaService
{
    /**
     * Get the total locked assets across ALL projects for a specific user (Account Owner).
     * This considers unique assets globally for that user.
     */
    public function getGlobalLedgerCount(User $owner): int
    {
        return AssetBillingLock::where('user_id', $owner->id)
            ->distinct('asset_identifier')
            ->count('asset_identifier');
    }

    /**
     * Get the total locked assets for a specific project.
     */
    public function getProjectLedgerCount(Project $project): int
    {
        return AssetBillingLock::where('project_id', $project->id)
            ->distinct('asset_identifier')
            ->count('asset_identifier');
    }

    /**
     * Lock an asset for billing in a specific project.
     */
    public function lockAsset(Project $project, string $channel, string $assetIdentifier): void
    {
        AssetBillingLock::updateOrCreate(
            [
                'user_id' => $project->owner_id ?? $project->user_id, // Assuming Project belongs to a User (owner)
                'project_id' => $project->id,
                'channel' => $channel,
                'asset_identifier' => $assetIdentifier,
            ],
            [
                'locked_at' => now(),
            ]
        );
    }

    /**
     * Unlock an asset for a specific project.
     */
    public function unlockAsset(Project $project, string $channel, string $assetIdentifier): void
    {
        AssetBillingLock::where('project_id', $project->id)
            ->where('channel', $channel)
            ->where('asset_identifier', $assetIdentifier)
            ->delete();
    }

    /**
     * Calculate the synthetic limits for UI display, masking global usage from guests.
     */
    public function calculateLimits(Project $project, User $currentUser, int $stagedAssets = 0): array
    {
        // We assume $project->owner_id or $project->user_id points to the subscription owner
        $ownerId = $project->owner_id ?? $project->user_id;
        $owner = User::find($ownerId);
        
        // Fetch the owner's tier limit (placeholder for actual subscription logic)
        $ownerLimit = $owner->tier_limit ?? 100; 

        $globalLedgerCount = $this->getGlobalLedgerCount($owner);
        $projectLedgerCount = $this->getProjectLedgerCount($project);

        $isOwner = $currentUser->id === $ownerId;

        if ($isOwner) {
            // Absolute truth for the owner
            $usage = $globalLedgerCount + $stagedAssets;
            $limit = $ownerLimit;
        } else {
            // Synthetic total for guests
            $availableGlobalQuota = max(0, $ownerLimit - $globalLedgerCount);
            $limit = $projectLedgerCount + $availableGlobalQuota;
            $usage = $projectLedgerCount + $stagedAssets;
        }

        return [
            'usage' => $usage,
            'limit' => $limit,
            'is_owner' => $isOwner,
        ];
    }

    /**
     * Scan current project config and apply state transitions.
     * Called when the user clicks 'Save Configuration'.
     */
    public function processGracePeriodLocks(Project $project): void
    {
        $syncConfig = $project->sync_config ?? [];
        $ownerId = $project->owner_id ?? $project->user_id;

        $activeAssets = [];

        foreach ($syncConfig as $channelKey => $channelConfig) {
            if (!is_array($channelConfig)) continue;
            if (empty($channelConfig['enabled'])) continue;

            foreach ($channelConfig as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $asset) {
                        if (!empty($asset['enabled']) && empty($asset['lost_access'])) {
                            $identifier = $asset['id'] ?? $asset['url'] ?? null;
                            if ($identifier) {
                                $activeAssets[$channelKey][] = $identifier;
                                
                                // Create 'staged' lock if it doesn't exist
                                $lock = AssetBillingLock::where('project_id', $project->id)
                                    ->where('channel', $channelKey)
                                    ->where('asset_identifier', $identifier)
                                    ->first();
                                    
                                if (!$lock) {
                                    AssetBillingLock::create([
                                        'user_id' => $ownerId,
                                        'project_id' => $project->id,
                                        'channel' => $channelKey,
                                        'asset_identifier' => $identifier,
                                        'status' => 'staged',
                                        'staged_at' => now(),
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Process disabled assets
        $existingLocks = AssetBillingLock::where('project_id', $project->id)->get();
        foreach ($existingLocks as $lock) {
            $isActive = in_array($lock->asset_identifier, $activeAssets[$lock->channel] ?? []);
            
            if (!$isActive) {
                if ($lock->status === 'staged') {
                    // Grace Period refund! The user disabled it before it locked.
                    $lock->delete();
                } elseif ($lock->status === 'locked') {
                    // It was already locked, move to pending_release
                    $lock->update([
                        'status' => 'pending_release',
                        'disabled_at' => now(),
                    ]);
                }
            } else {
                // If the user re-enables an asset that was pending_release, restore it to locked.
                if ($lock->status === 'pending_release') {
                    $lock->update([
                        'status' => 'locked',
                        'disabled_at' => null,
                    ]);
                }
            }
        }
    }
}

<?php

namespace App\Services;

use App\Models\AssetBillingLock;
use App\Models\Project;
use App\Models\User;
use App\Models\BillingProfile;
use App\Enums\UserTier;
use Illuminate\Support\Carbon;

class AssetQuotaService
{
    /**
     * Get the total locked assets across ALL projects for a specific billing profile.
     */
    public function getGlobalLedgerCount(BillingProfile $profile): int
    {
        $projectIds = $profile->projects()->pluck('id');

        return AssetBillingLock::whereIn('project_id', $projectIds)
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
        $ownerId = $project->owner_id ?? $project->user_id;

        AssetBillingLock::updateOrCreate(
            [
                'user_id' => $ownerId,
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
     * Calculate the synthetic limits for UI display, masking global usage from guests and technical owners who don't own the billing profile.
     */
    public function calculateLimits(Project $project, User $currentUser, int $stagedAssets = 0): array
    {
        $billingProfile = $project->billingProfile;
        if (!$billingProfile) {
            $ownerId = $project->owner_id ?? $project->user_id;
            $owner = User::find($ownerId);
            $billingProfile = $owner?->billingProfiles()->where('is_default', true)->first();
        }

        $isProfileOwner = $billingProfile ? ($currentUser->id === $billingProfile->user_id) : false;

        $profileTier = $billingProfile?->tier ?? UserTier::FREE;
        $profileLimit = $this->getMaxAssetsForTier($profileTier);

        $globalLedgerCount = $billingProfile ? $this->getGlobalLedgerCount($billingProfile) : 0;
        $projectLedgerCount = $this->getProjectLedgerCount($project);

        if ($isProfileOwner) {
            // Absolute truth for the owner of the billing profile
            $usage = $globalLedgerCount + $stagedAssets;
            $limit = $profileLimit;
        } else {
            // Synthetic total for technical owners and collaborators (hiding other projects' usage)
            $availableGlobalQuota = max(0, $profileLimit - $globalLedgerCount);
            $limit = $projectLedgerCount + $availableGlobalQuota;
            $usage = $projectLedgerCount + $stagedAssets;
        }

        return [
            'usage' => $usage,
            'limit' => $limit,
            'is_owner' => $isProfileOwner,
        ];
    }

    /**
     * Scan current project config and apply state transitions.
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

        $existingLocks = AssetBillingLock::where('project_id', $project->id)->get();
        foreach ($existingLocks as $lock) {
            $isActive = in_array($lock->asset_identifier, $activeAssets[$lock->channel] ?? []);

            if (!$isActive) {
                if ($lock->status === 'staged') {
                    $lock->delete();
                } elseif ($lock->status === 'locked') {
                    $lock->update([
                        'status' => 'pending_release',
                        'disabled_at' => now(),
                    ]);
                }
            } else {
                if ($lock->status === 'pending_release') {
                    $lock->update([
                        'status' => 'locked',
                        'disabled_at' => null,
                    ]);
                }
            }
        }
    }

    public function getMaxAssetsForTier(UserTier $tier): int
    {
        return match ($tier) {
            UserTier::FREE => 5,
            UserTier::PRO => 100,
            UserTier::ULTRA, UserTier::FOUNDER => 500,
            UserTier::ENTERPRISE => 500,
            UserTier::SUSPENDED => 0,
            default => 5,
        };
    }
}

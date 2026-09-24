<?php

namespace App\Services;

use App\Enums\UserTier;
use App\Models\Project;
use App\Models\BillingProfile;
use App\Models\User;

class FeatureGateService
{
    /**
     * Determine if a project has access to a specific feature based on its billing tier and feature flags.
     */
    public function canAccess(string $featureKey, ?Project $project = null): bool
    {
        if (!$project) {
            $project = \Filament\Facades\Filament::getTenant();
        }

        if (!$project) {
            return false;
        }

        $profile = $project->billingProfile;
        $tier = $profile?->tier ?? UserTier::FREE;

        return match ($featureKey) {
            'ai_classification' => $project->supportsAiClassification(),
            'public_dashboards' => $tier !== UserTier::FREE && $tier !== UserTier::SUSPENDED,
            'invite_collaborators' => in_array($tier, [UserTier::ULTRA, UserTier::FOUNDER, UserTier::ENTERPRISE]),
            'share_billing_profiles' => $tier === UserTier::ENTERPRISE,
            'api_access' => in_array($tier, [UserTier::ULTRA, UserTier::FOUNDER, UserTier::ENTERPRISE]),
            default => true,
        };
    }

    /**
     * Get the next logical upgrade tier for quota expansion.
     */
    public function getNextTier(UserTier $currentTier): UserTier
    {
        return match ($currentTier) {
            UserTier::FREE => UserTier::PRO,
            UserTier::PRO => UserTier::ULTRA,
            UserTier::ULTRA, UserTier::FOUNDER => UserTier::ENTERPRISE,
            default => UserTier::PRO,
        };
    }

    /**
     * Get the minimum tier required to unlock a feature.
     */
    public function getMinimumTierForFeature(string $featureKey): UserTier
    {
        return match ($featureKey) {
            'ai_classification', 'public_dashboards' => UserTier::PRO,
            'invite_collaborators', 'api_access' => UserTier::ULTRA,
            'share_billing_profiles' => UserTier::ENTERPRISE,
            default => UserTier::FREE,
        };
    }

    /**
     * Get upgrade context data for a project and feature.
     * Returns whether the current user owns the billing profile, the owner's name, and the direct URL.
     */
    public function getUpgradeContext(string $featureKey, ?Project $project = null, ?User $user = null): array
    {
        if (!$project) {
            $project = \Filament\Facades\Filament::getTenant();
        }

        if (!$user) {
            $user = auth()->user();
        }

        $profile = $project?->billingProfile;
        $requiredTier = $this->getMinimumTierForFeature($featureKey);
        $currentTier = $profile?->tier ?? UserTier::FREE;

        $isOwner = false;
        $ownerName = null;
        $upgradeUrl = null;

        if ($profile) {
            $isOwner = $user && $user->id === $profile->user_id;
            $ownerName = $profile->user?->name ?? __('Unknown');
            if ($isOwner && $profile->id) {
                $upgradeUrl = route('filament.account.pages.account-subscription', [
                    'profile' => $profile->id,
                ]);
            }
        }

        return [
            'project' => $project,
            'profile' => $profile,
            'current_tier' => $currentTier,
            'required_tier' => $requiredTier,
            'is_profile_owner' => $isOwner,
            'profile_owner_name' => $ownerName,
            'upgrade_url' => $upgradeUrl,
        ];
    }
}

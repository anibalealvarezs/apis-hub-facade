<?php

use App\Enums\UserTier;
use App\Models\Project;
use App\Models\User;
use App\Services\BillingLifecycleService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->billingService = app(BillingLifecycleService::class);
});

it('correctly reports API access permissions and rate limits per subscription tier', function () {
    // 1. FREE Tier: No API access, 0 rate limit
    expect($this->billingService->canAccessApi(UserTier::FREE))->toBeFalse();
    expect($this->billingService->getApiRateLimitForTier(UserTier::FREE))->toBe(0);

    // 2. PRO Tier: No API access, 0 rate limit
    expect($this->billingService->canAccessApi(UserTier::PRO))->toBeFalse();
    expect($this->billingService->getApiRateLimitForTier(UserTier::PRO))->toBe(0);

    // 3. SUSPENDED: No API access, 0 rate limit
    expect($this->billingService->canAccessApi(UserTier::SUSPENDED))->toBeFalse();
    expect($this->billingService->getApiRateLimitForTier(UserTier::SUSPENDED))->toBe(0);

    // 4. ULTRA Tier: Has API access, 500 requests per minute
    expect($this->billingService->canAccessApi(UserTier::ULTRA))->toBeTrue();
    expect($this->billingService->getApiRateLimitForTier(UserTier::ULTRA))->toBe(500);

    // 5. FOUNDER Tier: Has API access, 500 requests per minute
    expect($this->billingService->canAccessApi(UserTier::FOUNDER))->toBeTrue();
    expect($this->billingService->getApiRateLimitForTier(UserTier::FOUNDER))->toBe(500);

    // 6. ENTERPRISE Tier: Has API access, 1000 requests per minute
    expect($this->billingService->canAccessApi(UserTier::ENTERPRISE))->toBeTrue();
    expect($this->billingService->getApiRateLimitForTier(UserTier::ENTERPRISE))->toBe(1000);
});

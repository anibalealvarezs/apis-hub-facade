<?php

use App\Models\DerivedMetric;
use App\Models\DerivedMetricResult;
use App\Models\Project;
use App\Models\User;
use App\Services\DerivedMetricCacheService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user);

    $this->service = new DerivedMetricCacheService();
});

function makeDerivedMetric(array $overrides = []): DerivedMetric
{
    return DerivedMetric::create(array_merge([
        'project_id' => test()->project->id,
        'name' => 'Test DM',
        'ast' => ['type' => 'addition', 'values' => ['a', 'b']],
        'source_series' => [['key' => 'a', 'channel' => 'google_ads', 'metric' => 'impressions']],
        'is_active' => true,
    ], $overrides));
}

// ─── DM-010: controls hash stability ───

it('produces a stable 64-char sha256 hash for identical controls', function () {
    $controls = ['date_start' => '2026-01-01', 'date_end' => '2026-01-07', 'granularity' => 'daily'];

    $hash1 = $this->service->computeControlsHash($controls);
    $hash2 = $this->service->computeControlsHash($controls);

    expect($hash1)->toBe($hash2);
    expect(strlen($hash1))->toBe(64);
});

it('is insensitive to control key ordering', function () {
    $hash1 = $this->service->computeControlsHash(['a' => 1, 'b' => 2]);
    $hash2 = $this->service->computeControlsHash(['b' => 2, 'a' => 1]);

    expect($hash1)->toBe($hash2);
});

it('changes when a control value differs', function () {
    $hash1 = $this->service->computeControlsHash(['date_start' => '2026-01-01']);
    $hash2 = $this->service->computeControlsHash(['date_start' => '2026-01-02']);

    expect($hash1)->not->toBe($hash2);
});

it('changes when an asset filter differs', function () {
    $hash1 = $this->service->computeControlsHash(['assets' => ['adset:1']]);
    $hash2 = $this->service->computeControlsHash(['assets' => ['adset:2']]);

    expect($hash1)->not->toBe($hash2);
});

// ─── DM-008: cache miss → store → hit ───

it('stores and retrieves a cached result with a default 60-minute TTL', function () {
    $dm = makeDerivedMetric();
    $hash = $this->service->computeControlsHash(['date_start' => '2026-01-01']);
    $result = ['dates' => ['2026-01-01'], 'values' => [10.0]];

    expect($this->service->getCachedResult($dm->id, $hash))->toBeNull();

    $this->service->cacheResult($dm->id, $this->project->id, $hash, $result);

    $cached = $this->service->getCachedResult($dm->id, $hash);
    expect($cached)->not->toBeNull();
    expect($cached->result['dates'])->toBe(['2026-01-01']);
    expect($cached->result['values'][0])->toBe(10);
    expect($cached->expires_at->gt(now()->addMinutes(59)))->toBeTrue();
    expect($cached->expires_at->lt(now()->addMinutes(61)))->toBeTrue();
});

it('updates an existing cache row on re-cache', function () {
    $dm = makeDerivedMetric();
    $hash = $this->service->computeControlsHash(['date_start' => '2026-01-01']);

    $this->service->cacheResult($dm->id, $this->project->id, $hash, ['values' => [1.0]]);
    $this->service->cacheResult($dm->id, $this->project->id, $hash, ['values' => [2.0]]);

    expect(DerivedMetricResult::where('derived_metric_id', $dm->id)->count())->toBe(1);
    expect($this->service->getCachedResult($dm->id, $hash)->result['values'][0])->toBe(2);
});

it('treats an expired result as a cache miss', function () {
    $dm = makeDerivedMetric();
    $hash = $this->service->computeControlsHash(['date_start' => '2026-01-01']);

    $this->service->cacheResult($dm->id, $this->project->id, $hash, ['values' => [1.0]]);
    DerivedMetricResult::where('derived_metric_id', $dm->id)->update(['expires_at' => now()->subMinute()]);

    expect($this->service->getCachedResult($dm->id, $hash))->toBeNull();
});

// ─── DM-009: invalidation ───

it('invalidates cache rows for a derived metric', function () {
    $dm = makeDerivedMetric();
    $hash = $this->service->computeControlsHash(['date_start' => '2026-01-01']);

    $this->service->cacheResult($dm->id, $this->project->id, $hash, ['values' => [1.0]]);
    expect(DerivedMetricResult::where('derived_metric_id', $dm->id)->count())->toBe(1);

    $this->service->invalidateCache($dm->id);

    expect(DerivedMetricResult::where('derived_metric_id', $dm->id)->count())->toBe(0);
});

it('flushes all cache rows for a project', function () {
    $dm1 = makeDerivedMetric();
    $dm2 = makeDerivedMetric(['name' => 'Other DM']);

    $this->service->cacheResult($dm1->id, $this->project->id, 'h1', ['values' => [1.0]]);
    $this->service->cacheResult($dm2->id, $this->project->id, 'h2', ['values' => [2.0]]);

    expect(DerivedMetricResult::where('project_id', $this->project->id)->count())->toBe(2);

    $this->service->flushAllForProject($this->project->id);

    expect(DerivedMetricResult::count())->toBe(0);
});

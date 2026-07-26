<?php

namespace App\Services;

use App\Models\DerivedMetricResult;

class DerivedMetricCacheService
{
    public function computeControlsHash(array $controls): string
    {
        ksort($controls);

        return hash('sha256', json_encode($controls, JSON_UNESCAPED_UNICODE));
    }

    public function getCachedResult(int $derivedMetricId, string $controlsHash): ?DerivedMetricResult
    {
        return DerivedMetricResult::where('derived_metric_id', $derivedMetricId)
            ->where('controls_hash', $controlsHash)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function cacheResult(int $derivedMetricId, int $projectId, string $controlsHash, array $result, int $ttlMinutes = 60): void
    {
        DerivedMetricResult::updateOrCreate(
            [
                'derived_metric_id' => $derivedMetricId,
                'controls_hash' => $controlsHash,
            ],
            [
                'project_id' => $projectId,
                'result' => $result,
                'cached_at' => now(),
                'expires_at' => now()->addMinutes($ttlMinutes),
            ]
        );
    }

    public function invalidateCache(int $derivedMetricId): void
    {
        DerivedMetricResult::where('derived_metric_id', $derivedMetricId)->delete();
    }

    public function flushAllForProject(int $projectId): void
    {
        DerivedMetricResult::where('project_id', $projectId)->delete();
    }
}

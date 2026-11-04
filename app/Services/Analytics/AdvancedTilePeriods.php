<?php

namespace App\Services\Analytics;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class AdvancedTilePeriods
{
    public function resolve(string $start, string $end, string $window = 'dashboard'): array
    {
        try {
            $startDate = CarbonImmutable::createFromFormat('!Y-m-d', $start);
            $endDate = CarbonImmutable::createFromFormat('!Y-m-d', $end);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Invalid tile date range.', previous: $exception);
        }

        if (!$startDate || !$endDate || $startDate->format('Y-m-d') !== $start || $endDate->format('Y-m-d') !== $end || $startDate->greaterThan($endDate)) {
            throw new InvalidArgumentException('Invalid tile date range.');
        }

        $days = match ($window) {
            'dashboard' => null,
            'last_7_days' => 7,
            'last_30_days' => 30,
            default => throw new InvalidArgumentException('Invalid tile period.'),
        };
        if ($days !== null) {
            $startDate = $endDate->subDays($days - 1);
        }
        $length = (int) $startDate->diffInDays($endDate) + 1;
        $previousEnd = $startDate->subDay();

        return [
            'current' => ['start' => $startDate->toDateString(), 'end' => $endDate->toDateString()],
            'previous' => ['start' => $previousEnd->subDays($length - 1)->toDateString(), 'end' => $previousEnd->toDateString()],
        ];
    }

    public function reduce(array $values, string $reducer): ?float
    {
        if (!in_array($reducer, ['latest', 'sum', 'average'], true)) {
            throw new InvalidArgumentException('Invalid tile reducer.');
        }
        $numeric = array_values(array_filter($values, static fn ($value) => is_numeric($value) && is_finite((float) $value)));
        if (!$numeric) {
            return null;
        }

        return match ($reducer) {
            'latest' => (float) end($numeric),
            'sum' => (float) array_sum($numeric),
            'average' => (float) array_sum($numeric) / count($numeric),
        };
    }
}

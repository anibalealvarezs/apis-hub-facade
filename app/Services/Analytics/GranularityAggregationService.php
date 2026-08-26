<?php

namespace App\Services\Analytics;

class GranularityAggregationService
{
    private array $ratioMetrics;

    public function __construct(array $ratioMetrics = ['ctr', 'bounce_rate', 'result_rate'])
    {
        $this->ratioMetrics = $ratioMetrics;
    }

    public function aggregateRows(array $rows, string $granularity, string $dateKey = 'date'): array
    {
        $needsAggregation = in_array($granularity, ['weekly', 'monthly', 'quarterly', 'semiannual', 'annually', 'lifetime']) && count($rows) > 1;
        if (!$needsAggregation) {
            return $rows;
        }

        $groups = [];
        foreach ($rows as $row) {
            $gKey = $this->groupKey($row[$dateKey] ?? '', $granularity);

            if (!isset($groups[$gKey])) {
                $groups[$gKey] = ['date' => $gKey, 'metrics' => [], 'companion' => []];
            }

            $rowImpressions = $this->findRowImpressions($row, $dateKey);

            foreach ($row as $k => $v) {
                if ($k === $dateKey || !is_numeric($v)) {
                    continue;
                }
                $ck = preg_replace('/^trend_(?:total|average)_/', '', $k);

                if (!isset($groups[$gKey]['metrics'][$k])) {
                    $groups[$gKey]['metrics'][$k] = ['sum' => 0.0, 'count' => 0];
                }
                $groups[$gKey]['metrics'][$k]['sum'] += (float) $v;
                $groups[$gKey]['metrics'][$k]['count']++;

                if ($ck === 'impressions') {
                    $groups[$gKey]['companion']['impressions_sum'] = ($groups[$gKey]['companion']['impressions_sum'] ?? 0) + (float) $v;
                }
                if ($ck === 'clicks') {
                    $groups[$gKey]['companion']['clicks_sum'] = ($groups[$gKey]['companion']['clicks_sum'] ?? 0) + (float) $v;
                }
                if ($ck === 'spend') {
                    $groups[$gKey]['companion']['spend_sum'] = ($groups[$gKey]['companion']['spend_sum'] ?? 0) + (float) $v;
                }
                if ($ck === 'reach') {
                    $groups[$gKey]['companion']['reach_sum'] = ($groups[$gKey]['companion']['reach_sum'] ?? 0) + (float) $v;
                }
                if ($ck === 'results') {
                    $groups[$gKey]['companion']['results_sum'] = ($groups[$gKey]['companion']['results_sum'] ?? 0) + (float) $v;
                }
                if (str_contains($ck, 'position') && $rowImpressions !== null) {
                    if (!isset($groups[$gKey]['companion']['weighted_position_sum'])) {
                        $groups[$gKey]['companion']['weighted_position_sum'] = [];
                    }
                    $groups[$gKey]['companion']['weighted_position_sum'][$k] = ($groups[$gKey]['companion']['weighted_position_sum'][$k] ?? 0) + (float) $v * $rowImpressions;
                }
            }
        }

        $aggregated = [];
        foreach ($groups as $group) {
            $row = [$dateKey => $group['date']];
            $comp = $group['companion'] ?? [];
            foreach ($group['metrics'] as $k => $m) {
                $ck = preg_replace('/^trend_(?:total|average)_/', '', $k);

                if ($ck === 'ctr' && ($comp['impressions_sum'] ?? 0) > 0) {
                    $row[$k] = $comp['clicks_sum'] / $comp['impressions_sum'];
                } elseif ($ck === 'frequency' && ($comp['reach_sum'] ?? 0) > 0) {
                    $row[$k] = ($comp['impressions_sum'] ?? 0) / $comp['reach_sum'];
                } elseif ($ck === 'cpm' && isset($comp['spend_sum']) && ($comp['impressions_sum'] ?? 0) > 0) {
                    $row[$k] = $comp['spend_sum'] / $comp['impressions_sum'] * 1000;
                } elseif ($ck === 'cpc' && isset($comp['spend_sum']) && ($comp['clicks_sum'] ?? 0) > 0) {
                    $row[$k] = $comp['spend_sum'] / $comp['clicks_sum'];
                } elseif ($ck === 'cost_per_result' && isset($comp['spend_sum']) && ($comp['results_sum'] ?? 0) > 0) {
                    $row[$k] = $comp['spend_sum'] / $comp['results_sum'];
                } elseif (str_contains($ck, 'position') && ($comp['impressions_sum'] ?? 0) > 0) {
                    $weightedSum = $comp['weighted_position_sum'][$k] ?? 0;
                    $row[$k] = $weightedSum / $comp['impressions_sum'];
                } elseif ($this->isRatioOrPosition($ck) && $m['count'] > 0) {
                    $row[$k] = $m['sum'] / $m['count'];
                } else {
                    $row[$k] = $m['sum'];
                }
            }
            $aggregated[] = $row;
        }

        usort($aggregated, fn($a, $b) => $a[$dateKey] <=> $b[$dateKey]);

        return $aggregated;
    }

    public function aggregateFlatMap(array $dateValueMap, string $granularity): array
    {
        if ($granularity === 'daily' || empty($dateValueMap)) {
            return $dateValueMap;
        }

        $buckets = [];
        foreach ($dateValueMap as $date => $value) {
            $periodStart = $this->periodStartDate($date, $granularity);
            $buckets[$periodStart] = ($buckets[$periodStart] ?? 0) + $value;
        }

        ksort($buckets);

        return $buckets;
    }

    public function periodStartDate(string $date, string $granularity): string
    {
        if ($granularity === 'lifetime') {
            return 'Lifetime';
        }

        $dt = \Carbon\Carbon::parse($date);
        switch ($granularity) {
            case 'weekly':
                $dt->startOfWeek(\Carbon\Carbon::MONDAY);
                break;
            case 'monthly':
                $dt->startOfMonth();
                break;
            case 'quarterly':
                $dt->firstOfQuarter();
                break;
            case 'semiannual':
                $dt->month($dt->month <= 6 ? 1 : 7)->startOfMonth();
                break;
            case 'annually':
                $dt->startOfYear();
                break;
            default:
                return $date;
        }
        return $dt->format('Y-m-d');
    }

    public function groupKey(string $date, string $granularity): string
    {
        if ($granularity === 'daily') {
            return $date;
        }

        $dt = \Carbon\Carbon::parse($date);
        switch ($granularity) {
            case 'weekly':
                $start = $dt->startOfWeek(\Carbon\Carbon::MONDAY);
                $end = $start->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
                return $start->format('Y-m-d') . ' to ' . $end->format('d');
            case 'monthly':
                return $dt->startOfMonth()->format('Y-m');
            case 'quarterly':
                return $dt->firstOfQuarter()->format('Y') . '-Q' . $dt->quarter;
            case 'semiannual':
                return $dt->format('Y') . '-S' . ($dt->month <= 6 ? '1' : '2');
            case 'annually':
                return $dt->startOfYear()->format('Y');
            case 'lifetime':
                return 'Lifetime';
            default:
                return $date;
        }
    }

    private function isRatioOrPosition(string $ck): bool
    {
        return in_array($ck, $this->ratioMetrics)
            || str_contains($ck, 'position')
            || str_contains($ck, 'average')
            || str_contains($ck, 'avg');
    }

    private function findRowImpressions(array $row, string $dateKey): ?float
    {
        foreach ($row as $k => $v) {
            if ($k === $dateKey || !is_numeric($v)) {
                continue;
            }
            $ck = preg_replace('/^trend_(?:total|average)_/', '', $k);
            if ($ck === 'impressions') {
                return (float) $v;
            }
        }
        return null;
    }
}

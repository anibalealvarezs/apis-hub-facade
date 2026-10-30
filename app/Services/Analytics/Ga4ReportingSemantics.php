<?php

namespace App\Services\Analytics;

final class Ga4ReportingSemantics
{
    public static function metricsForScope(string $scope): array
    {
        return ChannelCapabilityRegistry::getAnalyticsTree('google_analytics')['scopes'][$scope]['metrics'] ?? [];
    }

    public static function metricOptions(?string $scope): array
    {
        $tree = ChannelCapabilityRegistry::getAnalyticsTree('google_analytics');
        $metrics = $scope ? self::metricsForScope($scope) : array_values(array_unique(array_merge(...array_map(fn ($key) => self::metricsForScope($key), ['traffic_matrix', 'acquisition_matrix', 'event_matrix', 'ad_touchpoint_matrix']))));
        $aliases = ['screenPageViews' => 'pageviews', 'bounceRate' => 'bounce_rate', 'newUsers' => 'new_users', 'activeUsers' => 'reach', 'totalUsers' => 'total_users', 'totalRevenue' => 'revenue', 'averageSessionDuration' => 'average_session_duration', 'eventCount' => 'event_count'];
        $options = [];
        foreach ($metrics as $metric) {
            $definition = $tree['metrics'][$metric];
            $label = __($definition['label']);
            if (in_array($metric, ['newUsers', 'activeUsers', 'totalUsers'], true)) $label .= ' '.__('(Reported Sum)');
            $options[$aliases[$metric] ?? $metric] = $label;
        }
        return $options;
    }

    public static function userCountNotice(): string
    {
        return __('GA4 user figures are sums of the reported daily and breakdown counts. The same person can be counted more than once; these are not unique users for the selected period.');
    }

    public static function containsGa4(array $configuration): bool
    {
        foreach ($configuration as $key => $value) {
            if (($key === 'channel' || str_ends_with((string)$key, '_channel')) && in_array($value, ['google_analytics', 'google_analytics_4'], true)) return true;
            if (is_array($value) && self::containsGa4($value)) return true;
        }
        return false;
    }

    public static function assertSuccessfulResults(array $results): void
    {
        foreach ($results as $response) {
            if (!is_array($response) || ($response['status'] ?? null) === 'error' || isset($response['error']) || ($response['success'] ?? true) === false) {
                throw new \RuntimeException(__('The GA4 report could not be completed. Partial results have not been displayed. Please review the selected metrics or try again.'));
            }
        }
    }

    public static function assertWidgetRatesAvailable(array $data): void
    {
        foreach ($data as $key => $value) {
            if ($key === 'previous') continue;
            if ($value === null && in_array(strtolower((string)$key), ['bouncerate', 'bounce_rate', 'averagesessionduration', 'average_session_duration'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['analytics' => __('This GA4 rate is unavailable because matching session weights are missing for part of the selected period.')]);
            }
            if (is_array($value)) self::assertWidgetRatesAvailable($value);
        }
    }
}

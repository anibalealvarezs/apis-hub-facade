<?php

namespace App\Services;

class WidgetTypeRegistry
{
    protected static array $compatibility = [
        'kpi' => ['tile', 'line_chart', 'bar_chart', 'gauge', 'sparkline', 'anomaly_chart', 'scatter_plot', 'combo_chart', 'table'],
        'metric' => ['tile', 'line_chart', 'bar_chart', 'gauge', 'sparkline', 'table'],
    ];

    protected static array $sourceLabels = [
        'kpi' => 'Custom KPI (Analytics Engine)',
        'metric' => 'Metric (Raw Aggregation)',
    ];

    protected static array $widgetLabels = [
        'tile' => 'Number Tile',
        'line_chart' => 'Line Chart',
        'bar_chart' => 'Bar Chart',
        'scatter_plot' => 'Scatter Plot',
        'combo_chart' => 'Combo Chart',
        'table' => 'Table',
        'gauge' => 'Gauge',
        'sparkline' => 'Sparkline',
        'anomaly_chart' => 'Anomaly Chart',
    ];

    public static function getWidgetTypesForSource(string $sourceType): array
    {
        return static::$compatibility[$sourceType] ?? [];
    }

    public static function isWidgetTypeCompatible(string $sourceType, string $widgetType): bool
    {
        return in_array($widgetType, static::$compatibility[$sourceType] ?? []);
    }

    public static function getSourceLabels(): array
    {
        return static::$sourceLabels;
    }

    public static function getWidgetLabels(): array
    {
        return static::$widgetLabels;
    }

    public static function getSourceLabel(string $sourceType): string
    {
        return static::$sourceLabels[$sourceType] ?? $sourceType;
    }

    public static function getWidgetLabel(string $widgetType): string
    {
        return static::$widgetLabels[$widgetType] ?? $widgetType;
    }
}

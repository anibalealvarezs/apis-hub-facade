<?php

namespace App\Services;

class WidgetTypeRegistry
{
    protected static array $compatibility = [
        'kpi' => ['tile', 'line_chart', 'bar_chart', 'gauge', 'sparkline', 'anomaly_chart', 'scatter_plot', 'combo_chart', 'table'],
        'metric' => ['tile', 'line_chart', 'bar_chart', 'gauge', 'sparkline', 'table'],
        'derived_metric' => ['tile', 'line_chart', 'bar_chart', 'gauge', 'sparkline', 'table'],
    ];

    protected static array $sourceLabels = [
        'kpi' => 'Custom KPI (Analytics Engine)',
        'metric' => 'Metric (Raw Aggregation)',
        'derived_metric' => 'Derived Metric (Computed Series)',
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

    protected static array $widgetDescriptions = [
        'tile' => 'Single large number for totals',
        'line_chart' => 'Track continuous trends over time',
        'bar_chart' => 'Compare discrete volumes side-by-side',
        'scatter_plot' => 'Find correlations and trendlines',
        'combo_chart' => 'Dual-axis bars and lines (e.g. MACD)',
        'table' => 'Detailed row-by-row data view',
        'gauge' => 'Percentage or progress to a target',
        'sparkline' => 'Minimalist trendline without axes',
        'anomaly_chart' => 'Highlights statistical outliers in red',
    ];

    protected static array $widgetSvgs = [
        'tile' => '<svg viewBox="0 0 40 24" class="w-full h-full"><text x="20" y="16" text-anchor="middle" font-weight="bold" font-size="14" class="fill-gray-800 dark:fill-gray-200">12K</text><path d="M 28 8 L 32 4 L 36 8 M 32 4 L 32 16" class="stroke-green-500" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>',
        'line_chart' => '<svg viewBox="0 0 40 24" class="w-full h-full"><path d="M 4 18 L 12 11 L 20 15 L 28 6 L 36 8" class="stroke-primary-500" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/><circle cx="4" cy="18" r="1.5" class="fill-primary-500"/><circle cx="12" cy="11" r="1.5" class="fill-primary-500"/><circle cx="20" cy="15" r="1.5" class="fill-primary-500"/><circle cx="28" cy="6" r="1.5" class="fill-primary-500"/><circle cx="36" cy="8" r="1.5" class="fill-primary-500"/></svg>',
        'bar_chart' => '<svg viewBox="0 0 40 24" class="w-full h-full"><rect x="6" y="10" width="6" height="10" rx="1" class="fill-primary-400"/><rect x="17" y="6" width="6" height="14" rx="1" class="fill-primary-600"/><rect x="28" y="14" width="6" height="6" rx="1" class="fill-primary-300"/></svg>',
        'scatter_plot' => '<svg viewBox="0 0 40 24" class="w-full h-full"><line x1="4" y1="20" x2="36" y2="4" class="stroke-gray-300 dark:stroke-gray-600" stroke-width="1" stroke-dasharray="2 2"/><circle cx="8" cy="17" r="1.5" class="fill-primary-500"/><circle cx="14" cy="13" r="1.5" class="fill-primary-500"/><circle cx="20" cy="15" r="1.5" class="fill-primary-500"/><circle cx="26" cy="8" r="1.5" class="fill-primary-500"/><circle cx="32" cy="6" r="1.5" class="fill-primary-500"/></svg>',
        'combo_chart' => '<svg viewBox="0 0 40 24" class="w-full h-full"><rect x="6" y="12" width="4" height="8" rx="0.5" class="fill-primary-400 opacity-60"/><rect x="15" y="8" width="4" height="12" rx="0.5" class="fill-primary-400 opacity-60"/><rect x="24" y="14" width="4" height="6" rx="0.5" class="fill-primary-400 opacity-60"/><rect x="33" y="6" width="4" height="14" rx="0.5" class="fill-primary-400 opacity-60"/><path d="M 4 16 L 14 7 L 24 12 L 36 4" class="stroke-amber-500" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>',
        'table' => '<svg viewBox="0 0 40 24" class="w-full h-full"><rect x="4" y="3" width="32" height="18" rx="2" class="stroke-primary-500 fill-none" stroke-width="1.5"/><path d="M 4 8 L 36 8" class="stroke-primary-500" stroke-width="1.5"/><path d="M 4 14 L 36 14" class="stroke-gray-400 dark:stroke-gray-500" stroke-width="1" stroke-dasharray="1 1"/><path d="M 16 8 L 16 21" class="stroke-gray-400 dark:stroke-gray-500" stroke-width="1"/></svg>',
        'gauge' => '<svg viewBox="0 0 40 24" class="w-full h-full"><path d="M 7 19 A 13 13 0 0 1 33 19" class="stroke-gray-300 dark:stroke-gray-600 fill-none" stroke-width="4" stroke-linecap="round"/><path d="M 7 19 A 13 13 0 0 1 27 8" class="stroke-primary-500 fill-none" stroke-width="4" stroke-linecap="round"/><circle cx="20" cy="19" r="2" class="fill-gray-800 dark:fill-gray-100"/><line x1="20" y1="19" x2="25" y2="9" class="stroke-gray-800 dark:stroke-gray-100" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'sparkline' => '<svg viewBox="0 0 40 24" class="w-full h-full"><path d="M 4 17 C 10 17, 12 6, 18 10 C 24 14, 28 4, 36 8" class="stroke-primary-500 fill-none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'anomaly_chart' => '<svg viewBox="0 0 40 24" class="w-full h-full"><path d="M 4 17 L 12 15 L 20 7 L 28 14 L 36 12" class="stroke-gray-400 dark:stroke-gray-500 fill-none" stroke-width="1.5" stroke-dasharray="2 2"/><circle cx="20" cy="7" r="3.5" class="fill-red-500/20 stroke-red-500" stroke-width="1.2"/><circle cx="20" cy="7" r="1.5" class="fill-red-600"/></svg>',
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

    public static function getWidgetDescriptions(): array
    {
        return static::$widgetDescriptions;
    }

    public static function getWidgetSvgs(): array
    {
        return static::$widgetSvgs;
    }

    public static function getSourceLabel(string $sourceType): string
    {
        return static::$sourceLabels[$sourceType] ?? $sourceType;
    }

    public static function getWidgetLabel(string $widgetType): string
    {
        return static::$widgetLabels[$widgetType] ?? $widgetType;
    }

    public static function getWidgetDescription(string $widgetType): string
    {
        return static::$widgetDescriptions[$widgetType] ?? 'Standard widget';
    }

    public static function getWidgetSvg(string $widgetType): string
    {
        return static::$widgetSvgs[$widgetType] ?? static::$widgetSvgs['tile'];
    }
}

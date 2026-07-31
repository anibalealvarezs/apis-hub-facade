<?php

use App\Models\CustomKpi;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Services\WidgetDataService;
use App\Services\WidgetTypeRegistry;

function heritageDashboard(array $controls = []): Dashboard
{
    return new Dashboard(['controls' => $controls]);
}

function heritageWidget(array $overrides = []): DashboardWidget
{
    return new DashboardWidget(array_merge([
        'source_type' => 'metric',
        'widget_type' => 'tile',
        'controls' => [],
    ], $overrides));
}

// ─── HIER-009 / WID-009: widget type compatibility per source ───

it('exposes all nine widget types for the kpi source', function () {
    expect(WidgetTypeRegistry::getWidgetTypesForSource('kpi'))
        ->toBe(['tile', 'line_chart', 'bar_chart', 'gauge', 'sparkline', 'anomaly_chart', 'scatter_plot', 'combo_chart', 'table']);
});

it('exposes the six shared widget types for metric and derived_metric sources', function () {
    $shared = ['tile', 'line_chart', 'bar_chart', 'gauge', 'sparkline', 'table'];
    expect(WidgetTypeRegistry::getWidgetTypesForSource('metric'))->toBe($shared);
    expect(WidgetTypeRegistry::getWidgetTypesForSource('derived_metric'))->toBe($shared);
});

it('flags every compatible widget type for its source', function ($source, $widgetType) {
    expect(WidgetTypeRegistry::isWidgetTypeCompatible($source, $widgetType))->toBeTrue();
})->with([
    ['kpi', 'tile'], ['kpi', 'line_chart'], ['kpi', 'bar_chart'], ['kpi', 'gauge'],
    ['kpi', 'sparkline'], ['kpi', 'anomaly_chart'], ['kpi', 'scatter_plot'],
    ['kpi', 'combo_chart'], ['kpi', 'table'],
    ['metric', 'tile'], ['metric', 'line_chart'], ['metric', 'bar_chart'],
    ['metric', 'gauge'], ['metric', 'sparkline'], ['metric', 'table'],
    ['derived_metric', 'tile'], ['derived_metric', 'line_chart'], ['derived_metric', 'bar_chart'],
    ['derived_metric', 'gauge'], ['derived_metric', 'sparkline'], ['derived_metric', 'table'],
]);

it('rejects widget types outside the source compatibility set', function () {
    expect(WidgetTypeRegistry::isWidgetTypeCompatible('metric', 'scatter_plot'))->toBeFalse();
    expect(WidgetTypeRegistry::isWidgetTypeCompatible('metric', 'anomaly_chart'))->toBeFalse();
    expect(WidgetTypeRegistry::isWidgetTypeCompatible('metric', 'combo_chart'))->toBeFalse();
    expect(WidgetTypeRegistry::isWidgetTypeCompatible('derived_metric', 'scatter_plot'))->toBeFalse();
    expect(WidgetTypeRegistry::isWidgetTypeCompatible('derived_metric', 'anomaly_chart'))->toBeFalse();
    expect(WidgetTypeRegistry::isWidgetTypeCompatible('derived_metric', 'combo_chart'))->toBeFalse();
    expect(WidgetTypeRegistry::isWidgetTypeCompatible('unknown_source', 'tile'))->toBeFalse();
});

it('provides labels, descriptions and svg art for every widget type', function () {
    $labels = WidgetTypeRegistry::getWidgetLabels();
    $descriptions = WidgetTypeRegistry::getWidgetDescriptions();
    $svgs = WidgetTypeRegistry::getWidgetSvgs();
    $types = ['tile', 'line_chart', 'bar_chart', 'gauge', 'sparkline', 'anomaly_chart', 'scatter_plot', 'combo_chart', 'table'];

    foreach ($types as $type) {
        expect($labels)->toHaveKey($type);
        expect($descriptions)->toHaveKey($type);
        expect($svgs)->toHaveKey($type);
    }
});

// ─── REQ-010: controls resolution precedence (widget > dashboard > default) ───

it('applies dashboard defaults for every compatible widget type', function ($widgetType) {
    $resolved = (new WidgetDataService())->resolveControls(heritageDashboard(), heritageWidget(['widget_type' => $widgetType]));

    expect($resolved['zero_handling'])->toBe('remove');
    expect($resolved['granularity'])->toBe('daily');
    expect($resolved['edge_case_weighted'])->toBeTrue();
    expect($resolved['edge_case_grouping'])->toBe('none');
    expect($resolved['date_end'])->toBe(\Carbon\Carbon::now()->subDays(1)->format('Y-m-d'));
})->with(['tile', 'line_chart', 'bar_chart', 'gauge', 'sparkline', 'anomaly_chart', 'scatter_plot', 'combo_chart', 'table']);

it('lets widget controls override dashboard-level controls', function () {
    $dashboard = heritageDashboard(['granularity' => 'monthly', 'zero_handling' => 'keep', 'date_end' => '2026-07-30']);
    $widget = heritageWidget(['controls' => ['granularity' => 'weekly', 'date_end' => '2026-07-15']]);

    $resolved = (new WidgetDataService())->resolveControls($dashboard, $widget);

    expect($resolved['granularity'])->toBe('weekly');
    expect($resolved['date_end'])->toBe('2026-07-15');
    expect($resolved['zero_handling'])->toBe('keep');
});

it('inherits dashboard controls when the widget uses __inherit__ or empty values', function () {
    $dashboard = heritageDashboard(['granularity' => 'quarterly', 'zero_handling' => 'keep', 'date_end' => '2026-07-01']);
    $widget = heritageWidget(['controls' => ['granularity' => '__inherit__', 'zero_handling' => '', 'date_end' => '']]);

    $resolved = (new WidgetDataService())->resolveControls($dashboard, $widget);

    expect($resolved['granularity'])->toBe('quarterly');
    expect($resolved['zero_handling'])->toBe('keep');
    expect($resolved['date_end'])->toBe('2026-07-01');
});

it('preserves widget-specific keys (metrics, channel, assets) into the resolved controls', function () {
    $widget = heritageWidget(['controls' => [
        'metrics' => ['spend', 'impressions'],
        'channel' => 'facebook_marketing',
        'assets' => ['act_1'],
    ]]);

    $resolved = (new WidgetDataService())->resolveControls(heritageDashboard(), $widget);

    expect($resolved['metrics'])->toBe(['spend', 'impressions']);
    expect($resolved['channel'])->toBe('facebook_marketing');
    expect($resolved['assets'])->toBe(['act_1']);
});

it('overrides edge case and max ratio from the KPI _ui_state for kpi widgets', function () {
    $kpi = new CustomKpi(['filters' => ['_ui_state' => ['edge_case_grouping' => 'histogram', 'max_ratio' => 4.5]]]);
    $widget = heritageWidget(['source_type' => 'kpi']);
    $widget->setRelation('customKpi', $kpi);

    $resolved = (new WidgetDataService())->resolveControls(heritageDashboard(), $widget);

    expect($resolved['edge_case_grouping'])->toBe('histogram');
    expect($resolved['max_ratio'])->toBe(4.5);
});

it('does not apply KPI _ui_state overrides to non-kpi widgets', function () {
    $kpi = new CustomKpi(['filters' => ['_ui_state' => ['edge_case_grouping' => 'histogram', 'max_ratio' => 4.5]]]);
    $widget = heritageWidget(['source_type' => 'metric']);
    $widget->setRelation('customKpi', $kpi);

    $resolved = (new WidgetDataService())->resolveControls(heritageDashboard(['edge_case_grouping' => 'none']), $widget);

    expect($resolved['edge_case_grouping'])->toBe('none');
    expect($resolved)->not->toHaveKey('max_ratio');
});

it('produces a full resolved control set for every compatible widget type (heritage to view)', function ($source, $widgetType) {
    $dashboard = heritageDashboard(['granularity' => 'weekly', 'zero_handling' => 'keep', 'date_end' => '2026-07-01']);
    $widget = heritageWidget([
        'source_type' => $source,
        'widget_type' => $widgetType,
        'controls' => ['channel' => 'facebook_marketing', 'metrics' => ['spend']],
    ]);

    $resolved = (new WidgetDataService())->resolveControls($dashboard, $widget);

    expect($resolved['channel'])->toBe('facebook_marketing');
    expect($resolved['metrics'])->toBe(['spend']);
    expect($resolved['granularity'])->toBe('weekly');
    expect($resolved['zero_handling'])->toBe('keep');
    expect($resolved['date_end'])->toBe('2026-07-01');
    expect($resolved['edge_case_weighted'])->toBeTrue();
    expect($resolved['edge_case_grouping'])->toBe('none');
})->with([
    ['metric', 'tile'], ['metric', 'line_chart'], ['metric', 'bar_chart'],
    ['metric', 'gauge'], ['metric', 'sparkline'], ['metric', 'table'],
    ['derived_metric', 'tile'], ['derived_metric', 'line_chart'], ['derived_metric', 'bar_chart'],
    ['derived_metric', 'gauge'], ['derived_metric', 'sparkline'], ['derived_metric', 'table'],
    ['kpi', 'tile'], ['kpi', 'line_chart'], ['kpi', 'bar_chart'], ['kpi', 'gauge'],
    ['kpi', 'sparkline'], ['kpi', 'anomaly_chart'], ['kpi', 'scatter_plot'],
    ['kpi', 'combo_chart'], ['kpi', 'table'],
]);

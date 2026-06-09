<?php

namespace App\Filament\App\Pages;

use App\Services\Analytics\PredefinedKpiRegistry;
use Filament\Pages\Page;

class KpiReference extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static string $view = 'filament.app.pages.kpi-reference';

    public static function getNavigationLabel(): string
    {
        return __('KPI Reference');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Knowledge Base');
    }

    public function getTitle(): string
    {
        return __('Predefined KPI Templates');
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public function getKpis(): array
    {
        return PredefinedKpiRegistry::getPredefinedKpis();
    }

    public function getCalculationTypeLabel(string $type): string
    {
        return match ($type) {
            'calculate_regression' => 'Multiple Linear Regression',
            'calculate_elasticity' => 'Elasticity',
            'calculate_autocorrelation' => 'Autocorrelation',
            'calculate_granger' => 'Granger Causality',
            'calculate_macd' => 'MACD Momentum',
            'calculate_anomaly' => 'Anomaly Detection',
            default => $type,
        };
    }

    public function getCalculationTypeDescription(string $type): string
    {
        return match ($type) {
            'calculate_regression' => 'Quantifies how changes in independent variables (X) affect the dependent variable (Y). Returns coefficients, R-squared, p-values, and confidence intervals.',
            'calculate_elasticity' => 'Measures the percentage change in Y for a 1% change in X. Values > 1 indicate elastic (amplified) response; < 1 indicates inelastic (diminished) response.',
            'calculate_autocorrelation' => 'Detects whether a time series is correlated with its own past values. Useful for identifying seasonality patterns and momentum.',
            'calculate_granger' => 'Tests whether one time series can predict another. Determines if X precedes Y in a statistically significant way (predictive causality).',
            'calculate_macd' => 'Moving Average Convergence Divergence — identifies momentum shifts by comparing short-term and long-term moving averages. Signals trend reversals.',
            'calculate_anomaly' => 'Flags statistically significant outliers in a time series using z-scores and moving statistics. Useful for automated alerting.',
            default => '',
        };
    }
}

<?php

namespace App\Filament\App\Resources\DerivedMetricResource\Pages;

use App\Filament\App\Resources\DerivedMetricResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDerivedMetrics extends ListRecords
{
    protected static string $resource = DerivedMetricResource::class;

    protected function getHeaderActions(): array
    {
        $project = \Filament\Facades\Filament::getTenant();
        $isQuotaReached = false;
        $maxMetrics = 10;
        $tier = \App\Enums\UserTier::FREE;

        if ($project && $project->billingProfile) {
            $tier = $project->billingProfile->tier;
            $currentCount = \App\Models\DerivedMetric::where('project_id', $project->id)->count();
            $maxMetrics = app(\App\Services\BillingLifecycleService::class)->getMaxDerivedMetricsForTier($tier);
            $isQuotaReached = $currentCount >= $maxMetrics;
        }

        $actions = [];

        if ($isQuotaReached) {
            $gate = app(\App\Services\FeatureGateService::class);
            $nextTier = $gate->getNextTier($tier);

            $actions[] = Actions\Action::make('upgradeDerivedMetricQuota')
                ->visible(fn () => auth()->user()?->can('edit_preferences') ?? false)
                ->label(new \Illuminate\Support\HtmlString(
                    '<span class="inline-flex items-center gap-1.5 font-semibold">' .
                        '<svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>' .
                        '<span>' . e(__('New Derived Metric')) . '</span>' .
                        '<span class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-500/20 text-amber-700 dark:text-amber-300 font-bold uppercase">' . $nextTier->getLabel() . '</span>' .
                    '</span>'
                ))
                ->color('warning')
                ->icon('heroicon-m-sparkles')
                ->modalHeading(__('Derived Metric Limit Reached'))
                ->modalDescription(function () use ($project, $maxMetrics, $tier, $nextTier, $gate) {
                    $ctx = $gate->getUpgradeContext('derived_metrics_quota', $project);
                    $nextTierMax = app(\App\Services\BillingLifecycleService::class)->getMaxDerivedMetricsForTier($nextTier);
                    $nextTierMaxLabel = $nextTierMax >= 999999 ? __('unlimited') : (string)$nextTierMax;

                    if ($ctx['is_profile_owner']) {
                        return __('You have reached the limit of :current derived metrics allowed on your current plan (:tier). Upgrade to :next to unlock up to :next_max metrics.', [
                            'current' => $maxMetrics,
                            'tier' => $tier->getLabel(),
                            'next' => $nextTier->getLabel(),
                            'next_max' => $nextTierMaxLabel,
                        ]);
                    }

                    return __('This project has reached its limit of :current derived metrics allowed on its current plan (:tier). The billing profile is owned by :owner. Please request them to upgrade or link another billing profile.', [
                        'current' => $maxMetrics,
                        'tier' => $tier->getLabel(),
                        'owner' => $ctx['profile_owner_name'] ?? __('the profile owner'),
                    ]);
                })
                ->modalSubmitAction(function (\Filament\Actions\StaticAction $action) use ($project, $nextTier, $gate) {
                    $ctx = $gate->getUpgradeContext('derived_metrics_quota', $project);
                    if ($ctx['is_profile_owner'] && !empty($ctx['upgrade_url'])) {
                        return $action
                            ->label(__('Upgrade to :tier', ['tier' => $nextTier->getLabel()]))
                            ->color('primary')
                            ->url($ctx['upgrade_url'], shouldOpenInNewTab: true);
                    }

                    return false;
                });
        } else {
            $actions[] = Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('edit_preferences') ?? false);
        }

        return $actions;
    }
}

<?php

namespace App\Services\Analytics;

use App\Enums\UserTier;
use App\Models\Project;
use App\Services\BillingLifecycleService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DashboardSpecValidator
{
    public function __construct(
        protected BillingLifecycleService $billingService
    ) {}

    /**
     * Validate dashboard spec against schema rules and project tier quotas.
     *
     * @param Project $project
     * @param array $spec
     * @param bool $isNewDashboard
     * @return array Validated data
     * @throws ValidationException
     */
    public function validate(Project $project, array $spec, bool $isNewDashboard = true): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'target_audience' => 'nullable|string|in:client,agency,internal,executive',
            'account_mode' => 'nullable|string|in:standalone,asset_groups,blended',
            'grid_layout' => 'nullable|array',
            'widgets' => 'required|array',
            'widgets.*.name' => 'required|string|max:255',
            'widgets.*.title' => 'nullable|string|max:255',
            'widgets.*.description' => 'nullable|string|max:1000',
            'widgets.*.widget_type' => 'required|string|in:tile,line_chart,bar_chart,table,gauge,sparkline,anomaly_list',
            'widgets.*.source_type' => 'required|string|in:kpi,metric,entity,derived_metric',
            'widgets.*.source_config' => 'nullable|array',
            'widgets.*.controls' => 'nullable|array',
            'widgets.*.grid_x' => 'nullable|integer|min:0|max:12',
            'widgets.*.grid_y' => 'nullable|integer|min:0',
            'widgets.*.grid_w' => 'nullable|integer|min:1|max:12',
            'widgets.*.grid_h' => 'nullable|integer|min:1|max:12',
            'widgets.*.custom_kpi_id' => 'nullable|integer',
            'widgets.*.derived_metric_id' => 'nullable|integer',
        ];

        $validator = Validator::make($spec, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $tier = $project->billingProfile ? $project->billingProfile->tier : UserTier::FREE;

        // Check widget quota per dashboard
        $maxWidgets = $this->billingService->getMaxWidgetsPerDashboardForTier($tier);
        $widgetCount = count($validated['widgets'] ?? []);

        if ($widgetCount > $maxWidgets) {
            throw ValidationException::withMessages([
                'widgets' => [
                    "Widget count ({$widgetCount}) exceeds the maximum allowed ({$maxWidgets}) for the '{$tier->value}' tier."
                ]
            ]);
        }

        // Check private dashboard quota if this is creating a new dashboard
        if ($isNewDashboard) {
            $maxDashboards = $this->billingService->getMaxPrivateDashboardsForTier($tier);
            $currentDashboards = $project->dashboards()->count();

            if ($currentDashboards >= $maxDashboards) {
                throw ValidationException::withMessages([
                    'dashboards' => [
                        "Project has reached the maximum allowed dashboards ({$maxDashboards}) for the '{$tier->value}' tier."
                    ]
                ]);
            }
        }

        return $validated;
    }
}

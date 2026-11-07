<?php

namespace App\Http\Controllers\Api\Internal;

use App\Enums\UserTier;
use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\DashboardDraft;
use App\Models\DashboardWidget;
use App\Models\Project;
use App\Services\Analytics\ChannelCapabilityRegistry;
use App\Services\Analytics\DashboardSpecValidator;
use App\Services\BillingLifecycleService;
use App\Services\DeployerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TenantDashboardDraftController extends Controller
{
    public function __construct(
        protected DashboardSpecValidator $specValidator,
        protected BillingLifecycleService $billingService,
        protected DeployerService $deployerService
    ) {}

    /**
     * Get active authoring context (channels, asset groups, current counts, limits).
     */
    public function getContext(Request $request): JsonResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $tier = $project->billingProfile ? $project->billingProfile->tier : UserTier::FREE;

        // Active channels
        $channels = [];
        if ($project->sync_config && is_array($project->sync_config)) {
            foreach ($project->sync_config as $channelKey => $cfg) {
                if (!empty($cfg['enabled']) || !empty($cfg['active'])) {
                    $channels[] = $channelKey;
                }
            }
        }

        // Available asset groups
        $assetGroups = $project->assetGroups()->get()->map(fn ($ag) => [
            'id' => $ag->id,
            'name' => $ag->name,
            'description' => $ag->description,
        ])->values()->toArray();

        // Dashboards count and quotas
        $maxDashboards = $this->billingService->getMaxPrivateDashboardsForTier($tier);
        $currentDashboards = $project->dashboards()->count();
        $maxWidgetsPerDashboard = $this->billingService->getMaxWidgetsPerDashboardForTier($tier);

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'subdomain' => $project->subdomain,
                'tier' => $tier->value,
            ],
            'channels' => $channels,
            'asset_groups' => $assetGroups,
            'quotas' => [
                'current_dashboards' => $currentDashboards,
                'max_dashboards' => $maxDashboards,
                'can_create_dashboard' => $currentDashboards < $maxDashboards,
                'max_widgets_per_dashboard' => $maxWidgetsPerDashboard,
            ],
            'allowed_source_types' => ['kpi', 'metric', 'entity', 'entity_measure', 'derived_metric'],
            'allowed_widget_types' => [
                'tile', 'advanced_tile', 'metric_breakdown', 'stacked_area', 'line_chart',
                'bar_chart', 'table', 'gauge', 'sparkline', 'anomaly_list', 'pie_chart',
                'combo_chart', 'anomaly_chart', 'scatter_plot',
            ],
            'entity_measures' => [
                'supported_entities' => [
                    'orders' => [
                        'measures' => ['order_count', 'canonical_order_count', 'unique_customers', 'revenue', 'subtotal', 'total_tax', 'total_discount', 'aov'],
                        'dimensions' => ['store', 'channel', 'account', 'product', 'discount_code', 'financial_status', 'fulfillment_status', 'currency', 'shipping_country'],
                    ],
                    'customers' => [
                        'measures' => ['customer_count', 'canonical_customer_count', 'customer_ltv', 'avg_revenue_per_customer', 'repeat_customer_count', 'high_frequency_customer_count', 'repeat_buyer_rate', 'repeat_frequency', 'pre_purchase_touches'],
                        'dimensions' => ['store', 'channel', 'status', 'account'],
                    ],
                    'stores' => [
                        'measures' => ['store_count'],
                        'dimensions' => ['channel', 'currency', 'account'],
                    ],
                    'products' => [
                        'measures' => ['product_count', 'canonical_product_count', 'average_price', 'min_price', 'max_price'],
                        'dimensions' => ['store', 'channel', 'vendor', 'product_type', 'sku', 'currency'],
                    ],
                    'discounts' => [
                        'aliases' => ['promotions', 'promo-codes'],
                        'measures' => ['discount_count', 'total_usage'],
                        'dimensions' => ['store', 'channel', 'code', 'status'],
                    ],
                    'campaigns' => [
                        'measures' => ['campaign_count', 'total_budget', 'average_budget'],
                        'dimensions' => ['status', 'objective', 'buying_type', 'channel', 'account'],
                    ],
                    'ad-groups' => [
                        'aliases' => ['ad-sets'],
                        'measures' => ['ad_group_count'],
                        'dimensions' => ['status', 'campaign', 'optimization_goal', 'billing_event', 'channel', 'account'],
                    ],
                    'ads' => [
                        'measures' => ['ad_count'],
                        'dimensions' => ['status', 'campaign', 'ad_group', 'channel', 'account'],
                    ],
                    'posts' => [
                        'measures' => ['post_count'],
                        'dimensions' => ['page', 'media_type', 'account'],
                    ],
                    'events' => [
                        'measures' => ['event_occurrence_count', 'distinct_event_names'],
                        'dimensions' => ['channel', 'action', 'account'],
                    ],
                ],
                'time_buckets' => ['day', 'week', 'month', 'year'],
            ],
            'catalog_versions' => ChannelCapabilityRegistry::getCatalogVersionManifest(),
            'channel_metrics_catalog' => ChannelCapabilityRegistry::getAllAnalyticsTrees(),
        ]);
    }

    /**
     * Read full specification of an existing dashboard.
     */
    public function getDashboardSpec(Request $request, int $dashboardId): JsonResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        $dashboard = $project->dashboards()
            ->with(['widgets' => fn ($q) => $q->orderBy('id', 'asc')])
            ->find($dashboardId);

        if (! $dashboard) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'not_found',
                'message' => 'Dashboard not found in this project',
            ], 404);
        }

        return response()->json([
            'dashboard' => [
                'id' => $dashboard->id,
                'name' => $dashboard->getTranslations('name'),
                'description' => $dashboard->getTranslations('description'),
                'grid_layout' => $dashboard->grid_layout,
                'is_default' => (bool) $dashboard->is_default,
                'widgets' => $dashboard->widgets->map(fn ($w) => [
                    'id' => $w->id,
                    'name' => $w->getTranslations('name'),
                    'title' => $w->getTranslations('title'),
                    'description' => $w->getTranslations('description'),
                    'widget_type' => $w->widget_type,
                    'source_type' => $w->source_type,
                    'source_config' => $w->source_config,
                    'controls' => $w->controls,
                    'grid_x' => $w->grid_x,
                    'grid_y' => $w->grid_y,
                    'grid_w' => $w->grid_w,
                    'grid_h' => $w->grid_h,
                    'custom_kpi_id' => $w->custom_kpi_id,
                    'derived_metric_id' => $w->derived_metric_id,
                ])->values()->toArray(),
            ],
        ]);
    }

    /**
     * Create a staged draft session.
     */
    public function createDraft(Request $request): JsonResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');
        $dashboardId = $request->input('dashboard_id');

        $isNew = empty($dashboardId);
        if (! $isNew) {
            $existing = $project->dashboards()->find($dashboardId);
            if (! $existing) {
                return response()->json([
                    'status' => 'error',
                    'error_code' => 'not_found',
                    'message' => 'Target dashboard not found in project',
                ], 404);
            }
        }

        try {
            $validated = $this->specValidator->validate($project, $request->all(), $isNew);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'quota_or_validation_error',
                'errors' => $e->errors(),
            ], 422);
        }

        $draftToken = 'drf_' . Str::random(40);
        $draft = DashboardDraft::create([
            'project_id' => $project->id,
            'dashboard_id' => $dashboardId,
            'draft_token' => $draftToken,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'target_audience' => $validated['target_audience'] ?? 'client',
            'account_mode' => $validated['account_mode'] ?? 'asset_groups',
            'widgets' => $validated['widgets'],
            'grid_layout' => $validated['grid_layout'] ?? null,
            'version' => 1,
            'expires_at' => now()->addHours(48),
        ]);

        $previewUrl = config('app.url') . "/app/dashboards/preview/{$draft->draft_token}";

        return response()->json([
            'status' => 'success',
            'draft_token' => $draft->draft_token,
            'version' => $draft->version,
            'preview_url' => $previewUrl,
            'expires_at' => $draft->expires_at->toIso8601String(),
            'message' => 'Dashboard draft created successfully.',
        ], 201);
    }

    /**
     * Update an existing staged draft.
     */
    public function updateDraft(Request $request, string $token): JsonResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        $draft = DashboardDraft::where('project_id', $project->id)
            ->where('draft_token', $token)
            ->first();

        if (! $draft) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'draft_not_found',
                'message' => 'Draft session not found or already committed/discarded',
            ], 404);
        }

        if ($draft->isExpired()) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'draft_expired',
                'message' => 'Draft session has expired',
            ], 410);
        }

        // Merge existing draft data with incoming changes for validation
        $spec = array_merge([
            'name' => $draft->name,
            'description' => $draft->description,
            'target_audience' => $draft->target_audience,
            'account_mode' => $draft->account_mode,
            'grid_layout' => $draft->grid_layout,
            'widgets' => $draft->widgets,
        ], $request->all());

        try {
            $validated = $this->specValidator->validate($project, $spec, empty($draft->dashboard_id));
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'quota_or_validation_error',
                'errors' => $e->errors(),
            ], 422);
        }

        $draft->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'target_audience' => $validated['target_audience'] ?? $draft->target_audience,
            'account_mode' => $validated['account_mode'] ?? $draft->account_mode,
            'widgets' => $validated['widgets'],
            'grid_layout' => $validated['grid_layout'] ?? $draft->grid_layout,
            'version' => $draft->version + 1,
            'expires_at' => now()->addHours(48),
        ]);

        $previewUrl = config('app.url') . "/app/dashboards/preview/{$draft->draft_token}";

        return response()->json([
            'status' => 'success',
            'draft_token' => $draft->draft_token,
            'version' => $draft->version,
            'preview_url' => $previewUrl,
            'expires_at' => $draft->expires_at->toIso8601String(),
            'message' => 'Draft updated successfully.',
        ]);
    }

    /**
     * Atomically commit staged draft to production.
     */
    public function commitDraft(Request $request, string $token): JsonResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        $draft = DashboardDraft::where('project_id', $project->id)
            ->where('draft_token', $token)
            ->first();

        if (! $draft) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'draft_not_found',
                'message' => 'Draft not found or already committed',
            ], 404);
        }

        if ($draft->isExpired()) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'draft_expired',
                'message' => 'Draft session has expired',
            ], 410);
        }

        $dashboard = null;

        DB::transaction(function () use ($project, $draft, &$dashboard) {
            if ($draft->dashboard_id) {
                // Update existing dashboard
                $dashboard = $project->dashboards()->find($draft->dashboard_id);
                if ($dashboard) {
                    $dashboard->update([
                        'name' => $draft->name,
                        'description' => $draft->description,
                        'grid_layout' => $draft->grid_layout,
                    ]);
                    // Clean previous widgets
                    $dashboard->widgets()->delete();
                }
            }

            if (! $dashboard) {
                // Create new dashboard
                // Assign first project user or owner
                $owner = $project->users()->first();
                $dashboard = Dashboard::create([
                    'project_id' => $project->id,
                    'user_id' => $owner ? $owner->id : 1,
                    'name' => $draft->name,
                    'description' => $draft->description,
                    'grid_layout' => $draft->grid_layout,
                    'is_public' => false,
                    'is_default' => false,
                ]);
            }

            // Create widgets
            foreach ($draft->widgets as $w) {
                DashboardWidget::create([
                    'dashboard_id' => $dashboard->id,
                    'name' => $w['name'],
                    'title' => $w['title'] ?? null,
                    'description' => $w['description'] ?? null,
                    'widget_type' => $w['widget_type'],
                    'source_type' => $w['source_type'],
                    'source_config' => $w['source_config'] ?? null,
                    'controls' => $w['controls'] ?? null,
                    'grid_x' => $w['grid_x'] ?? 0,
                    'grid_y' => $w['grid_y'] ?? 0,
                    'grid_w' => $w['grid_w'] ?? 4,
                    'grid_h' => $w['grid_h'] ?? 2,
                    'custom_kpi_id' => $w['custom_kpi_id'] ?? null,
                    'derived_metric_id' => $w['derived_metric_id'] ?? null,
                ]);
            }

            // Delete the draft after commit
            $draft->delete();
        });

        // Synchronize updated project context to remote apis-hub node
        try {
            $this->deployerService->syncProjectMetadata($project);
        } catch (\Throwable $e) {
            // Log but don't fail transaction
            \Illuminate\Support\Facades\Log::warning("DeployerService::syncProjectMetadata failed during draft commit: {$e->getMessage()}");
        }

        return response()->json([
            'status' => 'success',
            'dashboard_id' => $dashboard->id,
            'message' => 'Dashboard committed to production successfully.',
        ]);
    }

    /**
     * Discard an active draft.
     */
    public function discardDraft(Request $request, string $token): JsonResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        $draft = DashboardDraft::where('project_id', $project->id)
            ->where('draft_token', $token)
            ->first();

        if (! $draft) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'draft_not_found',
                'message' => 'Draft session not found',
            ], 404);
        }

        $draft->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Draft discarded successfully.',
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\Dashboard;
use App\Models\DashboardWidget;
use Illuminate\Support\Collection;

class DashboardService
{
    public function createDashboard(array $data): Dashboard
    {
        return Dashboard::create($data);
    }

    public function updateDashboard(Dashboard $dashboard, array $data): Dashboard
    {
        $dashboard->update($data);
        return $dashboard->fresh();
    }

    public function cloneDashboard(Dashboard $dashboard, ?string $newName = null): Dashboard
    {
        $dashboard->load('widgets');

        $clone = $dashboard->replicate();
        $clone->name = $newName ?? $dashboard->name . ' (Copy)';
        $clone->is_default = false;
        unset($clone->widgets_count);
        $clone->push();

        foreach ($dashboard->widgets as $widget) {
            $widgetClone = $widget->replicate();
            $widgetClone->dashboard_id = $clone->id;
            $widgetClone->push();
        }

        return $clone->fresh(['widgets']);
    }

    public function cloneDashboardFromVersion(Dashboard $dashboard, $version): Dashboard
    {
        $versionData = collect($version->toArray())
            ->only($dashboard->getTrackableFields())
            ->toArray();

        $clone = $dashboard->replicate();
        $clone->fill($versionData);
        $clone->name = ($versionData['name'] ?? $dashboard->name) . ' (From v' . $version->version_number . ')';
        $clone->is_default = false;
        unset($clone->widgets_count);
        $clone->push();

        $widgetVersionIds = $version->widget_version_ids ?? [];
        $widgetVersions = \App\Models\WidgetVersion::whereIn('id', array_values($widgetVersionIds))->get()->keyBy('id');

        foreach ($widgetVersionIds as $origWidgetId => $widgetVersionId) {
            $wv = $widgetVersions->get($widgetVersionId);
            if (!$wv) {
                continue;
            }

            $widgetData = collect($wv->toArray())
                ->only((new \App\Models\DashboardWidget)->getTrackableFields())
                ->toArray();
            $widgetData['dashboard_id'] = $clone->id;

            $widgetClone = new \App\Models\DashboardWidget();
            $widgetClone->fill($widgetData);
            $widgetClone->push();
        }

        return $clone->fresh(['widgets']);
    }

    public function saveLayout(Dashboard $dashboard, array $gridItems): void
    {
        foreach ($gridItems as $item) {
            $widget = DashboardWidget::find($item['id']);
            if ($widget && $widget->dashboard_id === $dashboard->id) {
                $oldVals = [
                    'grid_x' => $widget->grid_x,
                    'grid_y' => $widget->grid_y,
                    'grid_w' => $widget->grid_w,
                    'grid_h' => $widget->grid_h,
                ];

                $widget->update([
                    'grid_x' => $item['x'] ?? 0,
                    'grid_y' => $item['y'] ?? 0,
                    'grid_w' => $item['w'] ?? 4,
                    'grid_h' => $item['h'] ?? 2,
                ]);

                $widget->refresh();
                $latestVersion = $widget->versions()->latest('version_number')->first();

                \Log::debug('[DashboardService:saveLayout] widget updated', [
                    'widget_id' => $widget->id,
                    'old' => $oldVals,
                    'new' => ['grid_x' => $widget->grid_x, 'grid_y' => $widget->grid_y, 'grid_w' => $widget->grid_w, 'grid_h' => $widget->grid_h],
                    'latest_version_id' => $latestVersion?->id,
                    'latest_version_number' => $latestVersion?->version_number,
                    'latest_version_grid_w' => $latestVersion?->grid_w,
                ]);
            }
        }

        $dashboard->update(['grid_layout' => $gridItems]);
    }

    public function addWidget(Dashboard $dashboard, array $data): DashboardWidget
    {
        $data['dashboard_id'] = $dashboard->id;
        if (!isset($data['grid_x'])) $data['grid_x'] = 0;
        if (!isset($data['grid_y'])) $data['grid_y'] = 0;
        return DashboardWidget::create($data);
    }

    public function removeWidget(DashboardWidget $widget): void
    {
        $widget->delete();
    }

    public function duplicateWidget(DashboardWidget $widget, ?array $currentLayout = null): DashboardWidget
    {
        $clone = $widget->replicate();
        $clone->name = $widget->name . ' (Copy)';

        $widget->dashboard->load('widgets');
        $allWidgets = $widget->dashboard->widgets;

        $sourceW = $widget->grid_w ?? 4;
        $sourceH = $widget->grid_h ?? 3;

        $maxY = 0;
        foreach ($allWidgets as $w) {
            $bottom = ($w->grid_y ?? 0) + ($w->grid_h ?? 3);
            if ($bottom > $maxY) {
                $maxY = $bottom;
            }
        }

        if ($currentLayout && is_array($currentLayout)) {
            foreach ($currentLayout as $item) {
                if (isset($item['id']) && (int)$item['id'] === (int)$widget->id) {
                    $sourceW = $item['w'] ?? $sourceW;
                    $sourceH = $item['h'] ?? $sourceH;
                }
            }
        }

        $clone->grid_x = 0;
        $clone->grid_y = $maxY;
        $clone->grid_w = $sourceW;
        $clone->grid_h = $sourceH;
        $clone->save();

        return $clone;
    }

    public function getAccessibleDashboards(int $projectId, int $userId, array $userRoles): Collection
    {
        $query = Dashboard::where('project_id', $projectId)
            ->with('widgets');

        $isOwnerOrEditor = in_array('project_owner', $userRoles) || in_array('project_editor', $userRoles);

        if ($isOwnerOrEditor) {
            return $query->orderBy('is_default', 'desc')
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        return $query->where(function ($q) use ($userId) {
            $q->where('is_public', true)
                ->orWhere('user_id', $userId)
                ->orWhereHas('sharedUsers', fn ($q) => $q->where('user_id', $userId));
        })
            ->orderBy('is_default', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function setDefaultDashboard(Dashboard $dashboard): void
    {
        Dashboard::where('project_id', $dashboard->project_id)
            ->where('id', '!=', $dashboard->id)
            ->update(['is_default' => false]);

        $dashboard->update(['is_default' => true]);
    }

    public function getDefaultDashboard(int $projectId): ?Dashboard
    {
        return Dashboard::where('project_id', $projectId)
            ->where('is_default', true)
            ->first();
    }
}

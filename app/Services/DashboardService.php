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

    public function saveLayout(Dashboard $dashboard, array $gridItems): void
    {
        foreach ($gridItems as $item) {
            $widget = DashboardWidget::find($item['id']);
            if ($widget && $widget->dashboard_id === $dashboard->id) {
                $widget->update([
                    'grid_x' => $item['x'] ?? 0,
                    'grid_y' => $item['y'] ?? 0,
                    'grid_w' => $item['w'] ?? 4,
                    'grid_h' => $item['h'] ?? 2,
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

    public function duplicateWidget(DashboardWidget $widget): DashboardWidget
    {
        $clone = $widget->replicate();
        $clone->name = $widget->name . ' (Copy)';
        $clone->grid_x = $widget->grid_x + 1;
        $clone->grid_y = $widget->grid_y + 1;
        $clone->push();
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

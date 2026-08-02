<?php

namespace App\Models;

use App\Traits\TracksVersions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dashboard extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TracksVersions;

    protected $fillable = [
        'project_id',
        'user_id',
        'name',
        'description',
        'grid_layout',
        'controls',
        'is_public',
        'is_default',
    ];

    protected $casts = [
        'grid_layout' => 'array',
        'controls' => 'array',
        'is_public' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Dashboard $dashboard) {
            if ($dashboard->isForceDeleting()) {
                $dashboard->widgets()->withTrashed()->forceDelete();
                $dashboard->publicViews()->withTrashed()->forceDelete();
            } else {
                $dashboard->widgets()->delete();
                $dashboard->publicViews()->delete();
            }
        });

        static::restoring(function (Dashboard $dashboard) {
            $dashboard->widgets()->onlyTrashed()->restore();
            $dashboard->publicViews()->onlyTrashed()->restore();
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class);
    }

    public function publicViews(): HasMany
    {
        return $this->hasMany(DashboardPublicView::class);
    }

    public function sharedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'dashboard_user')
            ->withTimestamps();
    }

    protected function getVersionModelClass(): string
    {
        return \App\Models\DashboardVersion::class;
    }

    public function getTrackableFields(): array
    {
        return ['name', 'description', 'grid_layout', 'controls', 'is_public', 'is_default'];
    }

    protected function getVersionForeignKey(): string
    {
        return 'dashboard_id';
    }

    protected function getVersionExtraAttributes(): array
    {
        $this->load('widgets');

        $widgetIds = [];
        $widgetVersionIds = [];

        foreach ($this->widgets as $widget) {
            $widgetIds[] = $widget->id;
            $latestVersion = $widget->versions()->latest('version_number')->first();
            if (!$latestVersion) {
                $widget->createVersion('Initial snapshot');
                $latestVersion = $widget->versions()->latest('version_number')->first();
            }
            if ($latestVersion) {
                $widgetVersionIds[$widget->id] = $latestVersion->id;
            }
        }

        \Log::debug('[Dashboard:getVersionExtraAttributes]', [
            'dashboard_id' => $this->id,
            'widget_ids' => $widgetIds,
            'widget_version_ids' => $widgetVersionIds,
        ]);

        return [
            'widget_ids' => $widgetIds,
            'widget_version_ids' => $widgetVersionIds,
        ];
    }

    public function restoreFullVersion(DashboardVersion $version): void
    {
        \Log::debug('[Dashboard:restoreFullVersion] start', [
            'dashboard_id' => $this->id,
            'version_id' => $version->id,
            'version_number' => $version->version_number,
            'version_widget_ids' => $version->widget_ids,
            'version_widget_version_ids' => $version->widget_version_ids,
        ]);

        $this->withoutVersioning = true;

        $trackable = collect($version->toArray())
            ->only($this->getTrackableFields())
            ->toArray();

        $this->update($trackable);

        $this->reconcileWidgetsFromVersion($version);

        $this->withoutVersioning = false;
    }

    protected function reconcileWidgetsFromVersion(DashboardVersion $version): void
    {
        if ($version->widget_ids === null) {
            \Log::debug('[Dashboard:reconcileWidgetsFromVersion] skipped: widget_ids is null');
            return;
        }

        $snapshotWidgetIds = collect($version->widget_ids);
        $snapshotWidgetVersionIds = $version->widget_version_ids ?? [];

        $this->load('widgets');

        \Log::debug('[Dashboard:reconcileWidgetsFromVersion] load widgets', [
            'widget_count' => $this->widgets->count(),
            'widget_ids_in_relation' => $this->widgets->pluck('id')->toArray(),
        ]);

        $currentWidgets = $this->widgets->keyBy('id');

        $currentWidgetIds = $currentWidgets->keys();

        $toDelete = $currentWidgetIds->diff($snapshotWidgetIds);
        $toRestoreFromTrash = $snapshotWidgetIds->diff($currentWidgetIds);

        \Log::debug('[Dashboard:reconcileWidgetsFromVersion] widget diff', [
            'toDelete' => $toDelete->toArray(),
            'toRestoreFromTrash' => $toRestoreFromTrash->toArray(),
            'snapshotWidgetIds' => $snapshotWidgetIds->toArray(),
            'currentWidgetIds' => $currentWidgetIds->toArray(),
        ]);

        DashboardWidget::withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class)
            ->whereIn('id', $toDelete)
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        if ($toRestoreFromTrash->isNotEmpty()) {
            DashboardWidget::withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class)
                ->where('dashboard_id', $this->id)
                ->whereIn('id', $toRestoreFromTrash)
                ->update(['deleted_at' => null, 'updated_at' => now()]);
        }

        $this->load('widgets');

        foreach ($snapshotWidgetIds as $widgetId) {
            $widget = DashboardWidget::withTrashed()->find($widgetId);
            if (!$widget) {
                \Log::debug('[Dashboard:reconcileWidgetsFromVersion] widget not found', ['widgetId' => $widgetId]);
                continue;
            }

            $widgetVersionId = $snapshotWidgetVersionIds[$widgetId] ?? null;

            \Log::debug('[Dashboard:reconcileWidgetsFromVersion] restoring widget', [
                'widgetId' => $widgetId,
                'widgetVersionId' => $widgetVersionId,
                'current_grid_values' => [
                    'grid_x' => $widget->grid_x,
                    'grid_y' => $widget->grid_y,
                    'grid_w' => $widget->grid_w,
                    'grid_h' => $widget->grid_h,
                ],
            ]);

            if ($widgetVersionId) {
                $widgetVersion = WidgetVersion::find($widgetVersionId);
                if ($widgetVersion) {
                    $trackable = collect($widgetVersion->toArray())
                        ->only($widget->getTrackableFields())
                        ->toArray();

                    \Log::debug('[Dashboard:reconcileWidgetsFromVersion] applying version', [
                        'widgetId' => $widgetId,
                        'versionNumber' => $widgetVersion->version_number,
                        'trackable' => $trackable,
                    ]);

                    DashboardWidget::withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class)
                        ->where('id', $widget->id)
                        ->update(array_merge($trackable, ['updated_at' => now()]));
                    $widget->fill($trackable);

                    \Log::debug('[Dashboard:reconcileWidgetsFromVersion] after update', [
                        'widgetId' => $widgetId,
                        'new_grid_values' => [
                            'grid_x' => $widget->grid_x,
                            'grid_y' => $widget->grid_y,
                            'grid_w' => $widget->grid_w,
                            'grid_h' => $widget->grid_h,
                        ],
                    ]);
                } else {
                    \Log::debug('[Dashboard:reconcileWidgetsFromVersion] WidgetVersion not found', ['widgetVersionId' => $widgetVersionId]);
                }
            } else {
                \Log::debug('[Dashboard:reconcileWidgetsFromVersion] no widgetVersionId in snapshot for widget', ['widgetId' => $widgetId]);
            }
        }
    }
}

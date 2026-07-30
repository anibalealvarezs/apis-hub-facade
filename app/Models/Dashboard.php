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

    public function sharedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'dashboard_user')
            ->withTimestamps();
    }

    protected function getVersionModelClass(): string
    {
        return \App\Models\DashboardVersion::class;
    }

    protected function getTrackableFields(): array
    {
        return ['name', 'description', 'grid_layout', 'controls', 'is_public', 'is_default'];
    }

    protected function getVersionForeignKey(): string
    {
        return 'dashboard_id';
    }

    protected function getVersionExtraAttributes(): array
    {
        $this->loadMissing('widgets');

        $widgetIds = [];
        $widgetVersionIds = [];

        foreach ($this->widgets as $widget) {
            $widgetIds[] = $widget->id;
            $latestVersion = $widget->versions()->latest('version_number')->first();
            if ($latestVersion) {
                $widgetVersionIds[$widget->id] = $latestVersion->id;
            }
        }

        return [
            'widget_ids' => $widgetIds,
            'widget_version_ids' => $widgetVersionIds,
        ];
    }

    public function restoreFullVersion(DashboardVersion $version): void
    {
        $this->withoutVersioning = true;

        $trackable = collect($version->toArray())
            ->only($this->getTrackableFields())
            ->toArray();

        $this->update($trackable);

        $this->withoutVersioning = false;

        $this->reconcileWidgetsFromVersion($version);
    }

    protected function reconcileWidgetsFromVersion(DashboardVersion $version): void
    {
        if ($version->widget_ids === null) {
            return;
        }

        $snapshotWidgetIds = collect($version->widget_ids);
        $snapshotWidgetVersionIds = $version->widget_version_ids ?? [];

        $this->loadMissing('widgets');
        $currentWidgets = $this->widgets->keyBy('id');

        $currentWidgetIds = $currentWidgets->keys();

        $toDelete = $currentWidgetIds->diff($snapshotWidgetIds);
        $toRestoreFromTrash = $snapshotWidgetIds->diff($currentWidgetIds);

        DashboardWidget::whereIn('id', $toDelete)->get()->each(function ($widget) {
            $widget->delete();
        });

        if ($toRestoreFromTrash->isNotEmpty()) {
            DashboardWidget::onlyTrashed()
                ->whereIn('dashboard_id', [$this->id])
                ->whereIn('id', $toRestoreFromTrash)
                ->get()
                ->each(function ($widget) {
                    $widget->restore();
                });
        }

        $this->loadMissing('widgets');

        foreach ($snapshotWidgetIds as $widgetId) {
            $widget = DashboardWidget::withTrashed()->find($widgetId);
            if (!$widget) {
                continue;
            }

            $widgetVersionId = $snapshotWidgetVersionIds[$widgetId] ?? null;

            if ($widgetVersionId) {
                $widgetVersion = WidgetVersion::find($widgetVersionId);
                if ($widgetVersion) {
                    $widget->withoutVersioning = true;
                    $trackable = collect($widgetVersion->toArray())
                        ->only($widget->getTrackableFields())
                        ->toArray();
                    $widget->update($trackable);
                    $widget->withoutVersioning = false;
                }
            }
        }
    }
}

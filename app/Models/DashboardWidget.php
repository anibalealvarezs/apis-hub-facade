<?php

namespace App\Models;

use App\Traits\TracksVersions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

use Spatie\Translatable\HasTranslations;

class DashboardWidget extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TracksVersions;
    use HasTranslations;

    public array $translatable = ['name', 'title', 'description'];

    protected $fillable = [
        'dashboard_id',
        'custom_kpi_id',
        'derived_metric_id',
        'name',
        'title',
        'description',
        'source_type',
        'source_config',
        'widget_type',
        'controls',
        'grid_x',
        'grid_y',
        'grid_w',
        'grid_h',
    ];

    protected $casts = [
        'source_config' => 'array',
        'controls' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (DashboardWidget $widget) {
            $autoDuplicate = $widget->auto_duplicate_translations ?? true;
            unset($widget->attributes['auto_duplicate_translations']);

            if ($autoDuplicate) {
                $locales = ['en', 'es'];
                foreach ($widget->translatable as $field) {
                    $val = $widget->getAttributes()[$field] ?? null;
                    if (! empty($val) && is_string($val) && ! str_starts_with(trim($val), '{')) {
                        $translations = [];
                        foreach ($locales as $loc) {
                            $translations[$loc] = $val;
                        }
                        $widget->setTranslations($field, $translations);
                    }
                }
            }
        });
    }

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function customKpi(): BelongsTo
    {
        return $this->belongsTo(CustomKpi::class);
    }

    public function derivedMetric(): BelongsTo
    {
        return $this->belongsTo(DerivedMetric::class);
    }

    /**
     * Get all active alerts associated with this widget or its underlying KPI/Derived Metric.
     */
    public function getAssociatedAlerts(): \Illuminate\Database\Eloquent\Collection
    {
        $projectId = $this->dashboard?->project_id;
        if (!$projectId) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return Alert::where('project_id', $projectId)
            ->where(function ($q) {
                $q->where('source_config->widget_id', $this->id);
                if ($this->custom_kpi_id) {
                    $q->orWhere(function ($sub) {
                        $sub->where('source_type', 'kpi')
                            ->where('source_config->kpi_id', $this->custom_kpi_id);
                    });
                }
                if ($this->derived_metric_id) {
                    $q->orWhere(function ($sub) {
                        $sub->where('source_type', 'derived_metric')
                            ->where('source_config->dm_id', $this->derived_metric_id);
                    });
                }
            })
            ->get();
    }

    protected function getVersionModelClass(): string
    {
        return \App\Models\WidgetVersion::class;
    }

    public function getTrackableFields(): array
    {
        return [
            'custom_kpi_id', 'derived_metric_id',
            'name', 'title', 'description',
            'source_type', 'source_config', 'widget_type',
            'controls', 'grid_x', 'grid_y', 'grid_w', 'grid_h',
        ];
    }

    protected function getVersionForeignKey(): string
    {
        return 'dashboard_widget_id';
    }

    protected function getVersionExtraAttributes(): array
    {
        $this->loadMissing('dashboard');
        return [
            'dashboard_id' => $this->dashboard_id,
            'project_id' => $this->dashboard?->project_id,
        ];
    }

    protected function shouldAutoVersionOnUpdate(): bool
    {
        return true;
    }
}

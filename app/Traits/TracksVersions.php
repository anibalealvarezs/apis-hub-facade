<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait TracksVersions
{
    protected bool $withoutVersioning = false;

    protected static function bootTracksVersions(): void
    {
        static::created(function (Model $model) {
            if (!$model->withoutVersioning) {
                $model->createVersion('Created');
            }
        });

        static::updated(function (Model $model) {
            if ($model->shouldAutoVersionOnUpdate() && !$model->withoutVersioning && $model->wasChanged($model->getTrackableFields())) {
                $model->createVersion($model->detectChangeSummary());
            }
        });
    }

    protected function shouldAutoVersionOnUpdate(): bool
    {
        return false;
    }

    public function createVersion(?string $changeSummary = null, ?string $versionLabel = null, ?string $versionName = null): void
    {
        $versionClass = $this->getVersionModelClass();
        $lastVersion = $versionClass::where($this->getVersionForeignKey(), $this->id)
            ->max('version_number') ?? 0;

        $snapshot = $this->getTrackableSnapshot();

        $versionClass::create(array_merge(
            $snapshot,
            [
                $this->getVersionForeignKey() => $this->id,
                'project_id' => $this->project_id,
                'user_id' => auth()->id(),
                'version_number' => $lastVersion + 1,
                'change_summary' => $changeSummary,
                'label' => $versionName ?? $versionLabel,
            ],
            $this->getVersionExtraAttributes(),
        ));

        if (in_array('grid_w', $this->getTrackableFields())) {
            \Log::debug('[TracksVersions] WidgetVersion created', [
                'widget_id' => $this->id,
                'class' => get_class($this),
                'version_number' => $lastVersion + 1,
                'snapshot_grid_w' => $snapshot['grid_w'] ?? 'N/A',
                'snapshot_grid_h' => $snapshot['grid_h'] ?? 'N/A',
            ]);
        }
    }

    public function versions(): HasMany
    {
        return $this->hasMany($this->getVersionModelClass(), $this->getVersionForeignKey());
    }

    public function getVersions(): HasMany
    {
        return $this->versions();
    }

    public function restoreVersion(int $versionId): bool
    {
        $version = $this->getVersions()->findOrFail($versionId);
        $trackable = collect($version->toArray())
            ->only($this->getTrackableFields())
            ->toArray();

        $this->withoutVersioning = true;
        $result = $this->update($trackable);
        $this->withoutVersioning = false;

        return $result;
    }

    public function hasUnsavedChanges(): bool
    {
        $latestVersion = $this->versions()->latest('version_number')->first();
        if (!$latestVersion) {
            return true;
        }

        $current = $this->getTrackableSnapshot();
        $stored = collect($latestVersion->toArray())
            ->only($this->getTrackableFields())
            ->toArray();

        foreach ($current as $key => $value) {
            $storedValue = $stored[$key] ?? null;
            if (is_array($value) && is_array($storedValue)) {
                if (json_encode($value) !== json_encode($storedValue)) {
                    return true;
                }
            } elseif ($value !== $storedValue) {
                return true;
            }
        }

        return false;
    }

    protected function getTrackableSnapshot(): array
    {
        return $this->only($this->getTrackableFields());
    }

    protected function detectChangeSummary(): ?string
    {
        $changed = $this->getDirty();
        $changed = array_intersect_key($changed, array_flip($this->getTrackableFields()));

        if (empty($changed)) {
            return null;
        }

        $parts = [];
        foreach ($changed as $field => $newValue) {
            $parts[] = str_replace('_', ' ', $field);
        }

        return 'Updated: ' . implode(', ', $parts);
    }

    protected function getVersionExtraAttributes(): array
    {
        return [];
    }

    abstract protected function getVersionModelClass(): string;
    abstract protected function getTrackableFields(): array;
    abstract protected function getVersionForeignKey(): string;
}

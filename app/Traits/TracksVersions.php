<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait TracksVersions
{
    protected static function bootTracksVersions(): void
    {
        static::created(function (Model $model) {
            $model->createVersion('Created');
        });

        static::updated(function (Model $model) {
            if ($model->wasChanged($model->getTrackableFields())) {
                $model->createVersion($model->detectChangeSummary());
            }
        });
    }

    public function createVersion(?string $changeSummary = null): void
    {
        $versionClass = $this->getVersionModelClass();
        $lastVersion = $versionClass::where($this->getVersionForeignKey(), $this->id)
            ->max('version_number') ?? 0;

        $versionClass::create(array_merge(
            $this->getTrackableSnapshot(),
            [
                $this->getVersionForeignKey() => $this->id,
                'project_id' => $this->project_id,
                'user_id' => auth()->id(),
                'version_number' => $lastVersion + 1,
                'change_summary' => $changeSummary,
            ]
        ));
    }

    public function getVersions(): HasMany
    {
        return $this->hasMany($this->getVersionModelClass(), $this->getVersionForeignKey());
    }

    public function restoreVersion(int $versionId): bool
    {
        $version = $this->getVersions()->findOrFail($versionId);
        $trackable = collect($version->toArray())
            ->only($this->getTrackableFields())
            ->toArray();

        return $this->update($trackable);
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

    abstract protected function getVersionModelClass(): string;
    abstract protected function getTrackableFields(): array;
    abstract protected function getVersionForeignKey(): string;
}

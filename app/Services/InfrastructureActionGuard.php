<?php

namespace App\Services;

use App\Models\Project;
use Filament\Notifications\Notification;

class InfrastructureActionGuard
{
    public static function allows(Project $project): bool
    {
        if (!$project->isInfrastructureBusy()) return true;
        Notification::make()->title(__('Infrastructure changes in progress'))
            ->body(__('Please wait for the current deployment or upgrade to finish.'))->warning()->send();
        return false;
    }

    public static function queue(Project $project, string $state, callable $dispatch): bool
    {
        $previous = $project->newQuery()->whereKey($project->getKey())->value('health_status');
        $changes = ['health_status' => $state];
        if ($state !== 'upgrading') $changes['deploy_started_at'] = now();
        $claimed = $project->newQuery()->whereKey($project->getKey())
            ->where(fn ($query) => $query->whereNull('health_status')->orWhereNotIn('health_status', Project::INFRASTRUCTURE_BUSY_STATES))
            ->update($changes);
        if (!$claimed) {
            self::allows($project);
            return false;
        }
        try {
            if ($dispatch() === false) {
                $project->newQuery()->whereKey($project->getKey())->where('health_status', $state)->update(['health_status' => $previous]);
                return false;
            }
        }
        catch (\Throwable $error) {
            $project->newQuery()->whereKey($project->getKey())->where('health_status', $state)->update(['health_status' => $previous]);
            throw $error;
        }
        return true;
    }
}

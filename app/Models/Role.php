<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Role extends SpatieRole
{
    /**
     * Filament multi-tenancy requirement.
     * Maps the Spatie 'project_id' (team_foreign_key) to the actual Project model.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, config('permission.column_names.team_foreign_key', 'project_id'));
    }
}

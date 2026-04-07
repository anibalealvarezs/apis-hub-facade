<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectStatusLog extends Model
{
    protected $fillable = [
        'project_id',
        'is_active',
        'event_type',
        'created_by_id',
        'notes',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}

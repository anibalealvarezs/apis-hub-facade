<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectDeploymentLog extends Model
{
    protected $table = 'project_deployment_logs';

    protected $fillable = [
        'project_id',
        'status',
        'output',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
